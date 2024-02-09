<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\handbooks;

use backend\controllers\Assistant;
use backend\controllers\cachemanagers\WorkerCacheController;
use backend\controllers\const_amicum\DepartmentTypeEnum;
use backend\controllers\const_amicum\ParamEnum;
use backend\controllers\UsersController;
use backend\controllers\WorkerBasicController;
use backend\controllers\WorkerMainController;
use Exception;
use frontend\controllers\Assistant as FrontEndAssistant;
use frontend\controllers\HandbookCachedController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\AccessCheck;
use frontend\models\Asmtp;
use frontend\models\Company;
use frontend\models\CompanyDepartment;
use frontend\models\Department;
use frontend\models\DepartmentType;
use frontend\models\Employee;
use frontend\models\Func;
use frontend\models\FunctionType;
use frontend\models\GroupAlarm;
use frontend\models\KindParameter;
use frontend\models\ParameterType;
use frontend\models\Place;
use frontend\models\PlanShift;
use frontend\models\Position;
use frontend\models\Sensor;
use frontend\models\SensorType;
use frontend\models\ShiftDepartment;
use frontend\models\ShiftMine;
use frontend\models\ShiftWorker;
use frontend\models\TypeObjectParameter;
use frontend\models\TypicalObject;
use frontend\models\Unit;
use frontend\models\Worker;
use frontend\models\WorkerObject;
use frontend\models\WorkerParameter;
use frontend\models\WorkerParameterHandbookValue;
use frontend\models\WorkerParameterSensor;
use frontend\models\WorkerParameterValue;
use frontend\models\WorkerType;
use Throwable;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\Response;

class HandbookEmployeeController extends Controller
{
    // GetCompanyDepartment                 -   Метод получения списка участков
    // GetWorkerGroupByCompanyDepartment    -   Метод получения списка людей сгруппированных по департаментам (департаменты отдельно, люди отдельно)
    // GetWorkerInfoPersonalCard            -   Метод получения информации по конкретному сотруднику для личного кабинета на мобильном устройстве
    // actionInitWorkerMain                 -   Метод инициализации кеша работников по всей шахте
    // GetRoleByWorker                      -   Метод возвращает основную роль работника по идентификатору
    // GetCompanyList                       -   Метод получения списка компаний (справочник)
    // GetCompanyListWithParent()           -   Метод получения списка компаний c родителем (справочник)
    // GetWorkersForHandbook                -   Метод получения списка людей
    // GetWorkersWithCompany                -   Метод получения списка людей с их подразделениями
    // GetWorkersWithCompanyForMo()         -   Метод получения списка людей с их подразделениями для медицинских осмотров
    // SaveUserAccount                      -   Метод сохранения настроек пользователя
    // SaveUserPhoto                        -   Метод сохранения фото пользователя

    // GetWorkerType()      - Получение справочника типов работников
    // SaveWorkerType()     - Сохранение справочника типов работников
    // DeleteWorkerType()   - Удаление справочника типов работников

    /**
     * Название метода: GetWorkerInfoPersonalCard()
     * Метод получения информации по конкретному сотруднику для личного кабинета на мобильном устройстве
     * http://127.0.0.1/read-manager-amicum?controller=handbooks\HandbookEmployee&method=GetWorkerInfoPersonalCard&subscribe=&data={%22worker_id%22:1801}
     * @param
     * @return array
     * @package frontend\controllers\handbooks
     * @example
     *
     * Документация на портале:
     * @author Якимов М.Н.
     * Created date: on 10.06.2019 17:35
     * @since ver
     */
    public static function GetWorkerInfoPersonalCard($data_post): array
    {
        $log = new LogAmicumFront("GetWorkerInfoPersonalCard");

        $result = null;

        try {

            $log->addLog("Начало выполнения метода");

            $data_post = json_decode($data_post);
            if (
                !property_exists($data_post, 'worker_id')
            ) {
                throw new Exception("Не передан входной параметр worker_id");
            }

            $worker_id = $data_post->worker_id;

            $worker = Worker::find()
                ->with('position')
                ->with('department')
                ->with('company')
                ->with('employee')
                ->with('workerObjects')
                ->where('id=' . $worker_id)
                ->limit(1)
                ->one();
            if (!$worker) {
                throw new Exception("Искомого работника в БД не найдено. ключ: $worker_id");
            }

            /**
             * Блок обработки базовых свойств работника
             */
            $worker_object_id = $worker->workerObjects[0]->id;


            $worker_personal_card = array(
                'worker_id' => $worker_id,
                'worker_object_id' => $worker_object_id,
                'stuff_number' => $worker->tabel_number,
                'date_work_start' => $worker->date_start,
                'first_name' => $worker->employee->first_name,
                'last_name' => $worker->employee->last_name,
                'patronymic' => $worker->employee->patronymic,
                'full_name' => $worker->employee->last_name . " " . $worker->employee->first_name . " " . $worker->employee->patronymic,
                'gender' => $worker->employee->gender,
                'birthdate' => $worker->employee->birthdate,
                'position_title' => $worker->position->title,
                'position_id' => $worker->position->id,
                'department_title' => $worker->department->title,
                'company_id' => $worker->company->id,
                'company_title' => $worker->company->title,
                'phone' => "",
                'photo_src' => null,
                'e_mail' => "",
            );

            /**
             * Создание пустых обязательных параметров (при выгрузке из сап этих параметров нет, потому и в БД у нас нет),
             * потому создаем пустую структуру, а при наличии параметров в БД ее потом сохраняем.
             */
            $worker_cache_parameter = (new WorkerCacheController());

            //e-mail
            $worker_personal_card['parameters'][8]['parameter_id'] = 8;
            $worker_value = $worker_cache_parameter->getParameterValueHash($worker_id, 8, 1);
            if ($worker_value and $worker_value['value'] != -1) {
                $worker_personal_card['parameters'][8]['worker_parameter_value'] = $worker_value['value'];
                $worker_personal_card['parameters'][8]['worker_parameter_id'] = $worker_value['worker_parameter_id'];
            } else if ($worker_value) {
                $worker_personal_card['parameters'][8]['worker_parameter_value'] = "";
                $worker_personal_card['parameters'][8]['worker_parameter_id'] = $worker_value['worker_parameter_id'];
            } else {
                $worker_personal_card['parameters'][8]['worker_parameter_value'] = "";
                $worker_personal_card['parameters'][8]['worker_parameter_id'] = null;
            }

            $worker_personal_card['e_mail'] = $worker_personal_card['parameters'][8]['worker_parameter_value'];

            //фото - 2D модель
            $worker_personal_card['parameters'][3]['parameter_id'] = 3;
            $worker_value = $worker_cache_parameter->getParameterValueHash($worker_id, 3, 1);
            if ($worker_value and $worker_value['value'] != -1) {
                $worker_personal_card['parameters'][3]['worker_parameter_value'] = $worker_value['value'];
                $worker_personal_card['parameters'][3]['worker_parameter_id'] = $worker_value['worker_parameter_id'];
            } else if ($worker_value) {
                $worker_personal_card['parameters'][3]['worker_parameter_value'] = "";
                $worker_personal_card['parameters'][3]['worker_parameter_id'] = $worker_value['worker_parameter_id'];
            } else {
                $worker_personal_card['parameters'][3]['worker_parameter_value'] = "";
                $worker_personal_card['parameters'][3]['worker_parameter_id'] = null;
            }
            $worker_personal_card['parameters'][3]['attachment_blob'] = null;
            $worker_personal_card['parameters'][3]['attachment_status'] = null;
            $worker_personal_card['parameters'][3]['attachment_type'] = null;
            $worker_personal_card['parameters'][3]['title'] = null;

            $worker_personal_card['photo_src'] = $worker_personal_card['parameters'][3]['worker_parameter_value'];

            //номер телефона
            $worker_personal_card['parameters'][7]['parameter_id'] = 7;
            $worker_value = $worker_cache_parameter->getParameterValueHash($worker_id, 7, 1);
            if ($worker_value and $worker_value['value'] != -1) {
                $worker_personal_card['parameters'][7]['worker_parameter_value'] = $worker_value['value'];
                $worker_personal_card['parameters'][7]['worker_parameter_id'] = $worker_value['worker_parameter_id'];
            } else if ($worker_value) {
                $worker_personal_card['parameters'][7]['worker_parameter_value'] = "";
                $worker_personal_card['parameters'][7]['worker_parameter_id'] = $worker_value['worker_parameter_id'];
            } else {
                $worker_personal_card['parameters'][7]['worker_parameter_value'] = "";
                $worker_personal_card['parameters'][7]['worker_parameter_id'] = null;
            }

            $worker_personal_card['phone'] = $worker_personal_card['parameters'][7]['worker_parameter_value'];


            $result = $worker_personal_card;

            unset($worker_personal_card);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * Название метода: SaveUserAccount - Метод сохранения настроек пользователя
     * Метод сохранения настроек пользователя
     * входные параметры:
     *      personalCard    -   карточка работника
     *      depConfig       -   настройки блока уведомлений
     * алгоритм:
     *  1. Сохранить номер телефона в БД
     *  2. Сохранить e-mail в БД
     *  3. Сохранить вложение
     *  4. Сохранить настройки пользователя
     * http://127.0.0.1/read-manager-amicum?controller=handbooks\HandbookEmployee&method=SaveUserAccount&subscribe=&data={%22worker_id%22:1801}
     * @param
     * @return array
     * @package frontend\controllers\handbooks
     * @example
     *
     * Документация на портале:
     * @author Якимов М.Н.
     * Created date: on 10.06.2019 17:35
     * @since ver
     */
    public static function SaveUserAccount($data_post): array
    {
        $log = new LogAmicumFront("SaveUserAccount");

        $result = array();                                                                                              // промежуточный результирующий массив

        $path1 = 0;

        try {
            $log->addLog("Начало выполнения метода");

            $data_post = json_decode($data_post);

            $date_time = date('Y:m:d H:i:s', strtotime(Assistant::GetDateNow()));

            /**
             * Сохранение сведений о сотруднике (телефон, фото и e-mail)
             */
            if (property_exists($data_post, 'personalCard') and property_exists($data_post->personalCard, 'parameters')) {
                $personalCard = $data_post->personalCard;

                // 1. Сохранить номер телефона в БД
                $parameters = (array)$personalCard->parameters;
                $worker_parameter_id = $parameters[7]->worker_parameter_id;
                if (!$worker_parameter_id) {
                    $response = WorkerMainController::getOrSetWorkerParameter($personalCard->worker_id, 7, 1);
                    $log->addLogAll($response);
                    if ($response['status'] != 1) {
                        throw new Exception("Ошибка при создании параметра 7 createWorkerParameter");
                    }
                    $worker_parameter_id = $response['worker_parameter_id'];
                }
                $data_post->personalCard->parameters->{7}->worker_parameter_id = $worker_parameter_id;

                if (isset($parameters[7]) and property_exists($parameters[7], 'worker_parameter_value')) {
                    if (!$parameters[7]->worker_parameter_value) {
                        $value = -1;
                    } else {
                        $value = $parameters[7]->worker_parameter_value;
                    }
                    $response = WorkerBasicController::addWorkerParameterHandbookValue($worker_parameter_id, $value, 1, $date_time);
                    $log->addLogAll($response);
                    if ($response['status'] != 1) {
                        throw new Exception("Ошибка при сохранении значения в БД параметра сотовый телефон пользователя 7");
                    }
                }
                $date_to_cache[] = WorkerCacheController::buildStructureWorkerParametersValue($personalCard->worker_id, $worker_parameter_id, 7, 1, $date_time, $value, 1);

                // 2. Сохранить email в БД
                $worker_parameter_id = $parameters[8]->worker_parameter_id;
                if (!$worker_parameter_id) {
                    $response = WorkerMainController::getOrSetWorkerParameter($personalCard->worker_id, 8, 1);
                    $log->addLogAll($response);

                    if ($response['status'] != 1) {
                        throw new Exception("Ошибка при создании параметра 8 createWorkerParameter");
                    }

                    $worker_parameter_id = $response['worker_parameter_id'];

                }
                $data_post->personalCard->parameters->{8}->worker_parameter_id = $worker_parameter_id;

                if (isset($parameters[8]) and property_exists($parameters[8], 'worker_parameter_value')) {
                    if (!$parameters[8]->worker_parameter_value) {
                        $value = -1;
                    } else {
                        $value = $parameters[8]->worker_parameter_value;
                    }
                    $response = WorkerBasicController::addWorkerParameterHandbookValue($worker_parameter_id, $value, 1, $date_time);
                    $log->addLogAll($response);
                    if ($response['status'] != 1) {
                        throw new Exception("Ошибка при сохранении значения в БД параметра email пользователя 8");
                    }
                }
                $date_to_cache[] = WorkerCacheController::buildStructureWorkerParametersValue($personalCard->worker_id, $worker_parameter_id, 8, 1, $date_time, $value, 1);

                // 3. Сохранить ФОТО/вложение в БД
                $worker_parameter_id = $parameters[3]->worker_parameter_id;
                if (!$worker_parameter_id) {
                    $response = WorkerMainController::getOrSetWorkerParameter($personalCard->worker_id, 3, 1);
                    $log->addLogAll($response);

                    if ($response['status'] != 1) {
                        throw new Exception("Ошибка при создании параметра 3 createWorkerParameter");
                    }

                    $worker_parameter_id = $response['worker_parameter_id'];
                }
                $data_post->personalCard->parameters->{3}->worker_parameter_id = $worker_parameter_id;


                if (isset($parameters[3]) and property_exists($parameters[3], 'attachment_blob')) {
                    if ($parameters[3]->attachment_blob and $parameters[3]->attachment_blob != "") {
                        $path1 = FrontEndAssistant::UploadFile(
                            $parameters[3]->attachment_blob,
                            $parameters[3]->attachment_title,
                            'attachment',
                            $parameters[3]->attachment_type);

                    } else {
                        $path1 = $data_post->personalCard->parameters->{3}->worker_parameter_value;
                    }
                    if (!$path1) {
                        $path1 = -1;
                    }

                    $data_post->personalCard->parameters->{3}->attachment_blob = null;
                    $data_post->personalCard->parameters->{3}->worker_parameter_value = $path1;

                    $response = WorkerBasicController::addWorkerParameterHandbookValue($worker_parameter_id, $path1, 1, $date_time);
                    $log->addLogAll($response);
                    if ($response['status'] != 1) {
                        throw new Exception("Ошибка при сохранении значения в БД параметра вложения 3");
                    }

                    $date_to_cache[] = WorkerCacheController::buildStructureWorkerParametersValue($personalCard->worker_id, $worker_parameter_id, 3, 1, $date_time, $parameters[3]->worker_parameter_value, 1);
                }

                if (isset($date_to_cache)) {
                    $ask_from_method = (new WorkerCacheController)->multiSetWorkerParameterValueHash($date_to_cache, $personalCard->worker_id);
                    $log->addLogAll($ask_from_method);
                    if (!$ask_from_method['status'] == 1) {
                        throw new Exception('Не смог обновить параметры в кеше работника' . $personalCard->worker_id);
                    }
                }

                // 19. Сохранить ПАРОЛЬ
                $worker_parameter_id = $parameters[19]->worker_parameter_id;
                if (!$worker_parameter_id) {
                    $response = WorkerMainController::getOrSetWorkerParameter($personalCard->worker_id, 19, 1);
                    $log->addLogAll($response);

                    if ($response['status'] != 1) {
                        throw new Exception("Ошибка при создании параметра 3 createWorkerParameter");
                    }

                    $worker_parameter_id = $response['worker_parameter_id'];
                }
                $data_post->personalCard->parameters->{19}->worker_parameter_id = $worker_parameter_id;

                if (isset($parameters[19]) and property_exists($parameters[19], 'worker_parameter_value') and $parameters[19]->worker_parameter_value != -1) {
                    $session = Yii::$app->session;
                    $response = UsersController::EditPassword($session['user_id'], $session['sessionLogin'], $parameters[19]->worker_parameter_value);
                    $log->addLogAll($response);
                    if ($response['status'] != 1) {
                        throw new Exception("Ошибка при сохранении пароля 19");
                    }
                }
            }

            /**
             * Сохранение настроек уведомлений пользователя
             */
            // 4. Сохранить настройки пользователя
            if (property_exists($data_post, 'depConfig')) {
                $depConfig = $data_post->depConfig;
                foreach ($depConfig as $worker_conf) {
                    $response = DepartmentController::publicUpdateStatusDepParamSummaryWorkerSettingDB($worker_conf->department_parameter_id, $worker_conf->employee_id, $worker_conf->status_id);

                    $log->addLogAll($response);
                    if ($response['status'] != 1) {
                        throw new Exception("Ошибка при сохранения настроек уведомлений");
                    }
                }
            }

            $result = $data_post;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(["Items" => $result], $log->getLogAll());
    }


    /**
     * Название метода: SaveUserPhoto - Метод сохранения фото пользователя
     * Метод сохранения фото пользователя
     * входные параметры:
     *      worker    -   карточка работника усеченная
     * алгоритм:
     * http://127.0.0.1/read-manager-amicum?controller=handbooks\HandbookEmployee&method=SaveUserPhoto&subscribe=&data={%22worker%22:{}}
     * @param
     * @return array
     * @package frontend\controllers\handbooks
     * @example
     *
     * Документация на портале:
     * @author Якимов М.Н.
     * Created date: on 10.06.2019 17:35
     * @since ver
     */
    public static function SaveUserPhoto($data_post): array
    {
        $log = new LogAmicumFront("SaveUserPhoto");
        $result = null;
        $path1 = 0;

        try {
            $log->addLog("Начало выполнения метода");

            $data_post = json_decode($data_post);
            if (
                !property_exists($data_post, 'worker')
            ) {
                throw new Exception("Не передан входной параметр worker_id");
            }

            $personalCard = $data_post->worker;
            $date_time = date('Y:m:d H:i:s', strtotime(Assistant::GetDateNow()));

            // 3. Сохранить вложение в БД
            $response = WorkerMainController::getOrSetWorkerParameter($personalCard->worker_id, 3, 1);

            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка при создании параметра 3 createWorkerParameter");
            }
            $worker_parameter_id = $response['worker_parameter_id'];

            if ($personalCard->attachment_blob) {
                $path1 = FrontEndAssistant::UploadFile($personalCard->attachment_blob, $personalCard->attachment_title, 'attachment', $personalCard->attachment_type);

                $personalCard->worker_parameter_value = $path1;
                $personalCard->attachment_blob = "";
            }

            if (isset($path1)) {
                $response = WorkerBasicController::addWorkerParameterHandbookValue($worker_parameter_id, $path1, 1, $date_time);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception("Ошибка при сохранении значения в БД параметра вложения 3");
                }

                $date_to_cache[] = WorkerCacheController::buildStructureWorkerParametersValue($personalCard->worker_id, $worker_parameter_id, 3, 1, $date_time, $personalCard->worker_parameter_value, 1);
            }
            if (isset($date_to_cache)) {
                $ask_from_method = (new WorkerCacheController)->multiSetWorkerParameterValueHash($date_to_cache, $personalCard->worker_id);
                $log->addLogAll($ask_from_method);
                if ($ask_from_method['status'] != 1) {
                    throw new Exception("'Не смог обновить параметры в кеше работника' . $personalCard->worker_id");
                }

            }
            $result = $personalCard;
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(["Items" => $result], $log->getLogAll());
    }

    /**
     * Название метода: actionSaveworkerParametersValuesBase()
     * Входные параметры:
     * Функция сохранения значений с вкладки
     * $post['table_name'] - имя таблицы
     * $post['parameter_values_array'] - массив значений
     * $post['specificObjectId'] - id конкретного объекта
     * Кэш добавляется только с помощью очередь!
     * Функция добавляет значений параметров воркера
     *
     * @url http://192.168.1.5/specific-sensor/save-specific-parameters-values-base?
     * Документация на портале: http://192.168.1.4/products/community/modules/forum/posts.aspx?&t=173&p=1#191
     * @author Якимов М.Н.
     * Created date: on 11.01.2019 9:55
     */
    public function actionSaveWorkerParametersValuesBase()
    {

        $errors = array();                                                                                              // массив ошибок
        $warnings = array();                                                                                              // массив предупреждений
        $worker_id = -1;
        $response = array();
        $objectParameters = null;
        $objects = array();
        $mine_id = false;
        $w_p_h_v_to_db = false;
        $w_p_v_to_db = false;
        $w_p_s_to_db = false;
        $flag_save_to_cache = false;
        $warnings[] = "actionSaveWorkerParametersValuesBase. Начал выполнять метод";
        try {
            /**
             * Блок проверки прав пользователя
             */
            $session = Yii::$app->session;
            $session->open();
            $warnings[] = "actionSaveWorkerParametersValuesBase. Начинаю проверять права пользователя";
            if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
                if (!AccessCheck::checkAccess($session['sessionLogin'], 24)) {
                    throw new Exception("actionSaveWorkerParametersValuesBase. Недостаточно прав для совершения данной операции");
                }
            } else {
                $errors[] = "actionSaveWorkerParametersValuesBase. Время сессии закончилось. Требуется повторный ввод пароля";
                $this->redirect('/');
                throw new Exception("actionSaveWorkerParametersValuesBase. Время сессии закончилось. Требуется повторный ввод пароля");
            }

            /**
             * Блок проверки наличия входных параметров и их распарсивание
             */
            $post = Yii::$app->request->post(); //получение данных от ajax-запроса
            if (isset($post['parameter_values_array']) and isset($post['worker_object_id'])) {

                $parameterValues = $post['parameter_values_array'];                                                         //массив параметров и их значений
                $worker_object_id = $post['worker_object_id'];
                $date_time = Assistant::GetDateNow();
                $warnings[] = "actionSaveWorkerParametersValuesBase. ТЕКУЩЕЕ ВРЕМЯ: $date_time";
                $warnings[] = "actionSaveWorkerParametersValuesBase. ТЕКУЩЕЕ ВРЕМЯ: " . date("Y-m-d H:i:s");
                $warnings[] = "actionSaveWorkerParametersValuesBase. ТЕКУЩАЯ временная зона:" . date_default_timezone_get();
                $warnings[] = "actionSaveWorkerParametersValuesBase. Проверил блок входных параметров. редактируемый сенсор: $worker_object_id";
            } else {
                throw new Exception("actionSaveWorkerParametersValuesBase. Входные параметры со страницы фронт энд не переданы");
            }

            $worker_object = WorkerObject::findOne(['id' => $worker_object_id]);//найти объект
            if ($worker_object) {
                $worker_id = $worker_object->worker_id;
            } else {
                throw new Exception("actionSaveWorkerParametersValuesBase. По входным параметрам workerObject $worker_object_id не найден ");
            }

            /**
             * Проверяем или инициализируем входные параметры перед вставкой
             * делаем проверку на вставку значений параметра. Если не задано, то прописываем -1.
             */
            if ($parameterValues) {
                $worker_parameter_value_to_caches = array();
                /**
                 * Записываем параметры работника
                 */
                foreach ($parameterValues as $parameter) {
                    if ($parameter['parameterValue'] == "" or $parameter['parameterValue'] == "empty") {
                        $parameter_value = '-1';
                    } else {
                        $parameter_value = $parameter['parameterValue'];
                    }
                    $parameter_id = (int)$parameter['parameterId'];
                    $parameter_type_id = $parameter['parameterTypeId'];
                    $worker_parameter_id = $parameter['specificParameterId'];
                    switch ($parameter['parameterStatus']) {
                        case 'handbook':
                            /**
                             * Сохранение самого значения в сенсор параметр Value
                             */
                            $w_p_h_v['worker_parameter_id'] = (int)$worker_parameter_id;
                            $w_p_h_v['date_time'] = $date_time;
                            $w_p_h_v['value'] = (string)$parameter_value;
                            $w_p_h_v['status_id'] = 1;
                            $w_p_h_v_to_db[] = $w_p_h_v;
                            $warnings[] = "actionSaveWorkerParametersValuesBase. Значение параметра " . $parameter['parameterId'] . " сохранено. specificParameterId = " . $parameter['specificParameterId'] . "Идентификатор объекта " . $worker_object_id;//сохранить соответствующую ошибку
                            // создаем массив для вставки разовой в кеш
                            $worker_parameter_value_to_caches[] = WorkerCacheController::buildStructureWorkerParametersValue(
                                $worker_id, $worker_parameter_id,
                                $parameter_id, 1,
                                $date_time, $parameter_value, 1);

                            break;
                        case 'sensor':
                            /**
                             * Сохранение самого значения в сенсор параметр валуе
                             */
                            $w_p_s['worker_parameter_id'] = (int)$worker_parameter_id;
                            $w_p_s['date_time'] = $date_time;
                            $w_p_s['sensor_id'] = (int)$parameter_value;
                            $w_p_s['type_relation_sensor'] = 1;
                            $w_p_s_to_db[] = $w_p_s;
                            break;
                        case 'manual':
                        case 'calc':
                        case 'edge':
                        case 'place':
                            switch ($parameter_id) {
                                case 346:       // Параметр шахтное поле - инициализируется переменная здесь, для определения необходимости инициализации кеша шахта - для подвижных сенсоров
                                    $mine_id = (int)$parameter_value;
                                    break;
                                case 158:       // Параметр чекина в шахту. Если был, то значение будет равно 1 или 0
                                    $flag_save_to_cache = (int)$parameter_value;
                                    break;
                            }
                            /**
                             * Сохранение самого значения в работника  параметр Value
                             */
                            $w_p_v['worker_parameter_id'] = (int)$worker_parameter_id;
                            $w_p_v['date_time'] = $date_time;
                            $w_p_v['value'] = (string)$parameter_value;
                            $w_p_v['status_id'] = 1;
                            $w_p_v_to_db[] = $w_p_v;

                            $warnings[] = "actionSaveWorkerParametersValuesBase. Значение параметра " . $parameter['parameterId'] . " сохранено. specificParameterId = " . $parameter['specificParameterId'] . "Идентификатор объекта " . $worker_object_id;//сохранить соответствующую ошибку
                            // создаем массив для вставки разовой в кеш
                            $worker_parameter_value_to_caches[] = WorkerCacheController::buildStructureWorkerParametersValue(
                                $worker_id, $worker_parameter_id,
                                $parameter_id, $parameter_type_id,           //важно!!!! с фронта может прилетать тип параметра 4, что, то же самое, что и 2. что бы работал кеш поставил жестко 2. Якимов М.Н.
                                $date_time, $parameter_value, 1);

                            break;
                        default:
                            $errors[] = "actionSaveWorkerParametersValuesBase. Неизвестный статус параметра при сохранении. parameterStatus:" . $parameter['parameterStatus'];
                    }
                }
                /**
                 * Сохраняем массово Worker_parameter_handbook_value
                 */
                if ($w_p_h_v_to_db) {
                    $insert_result = Yii::$app->db->createCommand()->batchInsert('worker_parameter_handbook_value',
                        ['worker_parameter_id', 'date_time', 'value', 'status_id'], $w_p_h_v_to_db)->execute();
                    $warnings[] = "actionSaveWorkerParametersValuesBase. Количество вставленных записей в worker_parameter_handbook_value " . $insert_result;
                } else {
                    $warnings[] = "actionSaveWorkerParametersValuesBase. Значения в worker_parameter_handbook_value параметров не было сохранено ";
                }
                unset($w_p_h_v_to_db);

                /**
                 * Сохраняем массово Worker_parameter_sensor
                 */
                if ($w_p_s_to_db) {
                    $insert_result = Yii::$app->db->createCommand()->batchInsert('worker_parameter_sensor',
                        ['worker_parameter_id', 'date_time', 'sensor_id', 'type_relation_sensor'], $w_p_s_to_db)->execute();
                    $warnings[] = "actionSaveWorkerParametersValuesBase. Количество вставленных записей в worker_parameter_sensor " . $insert_result;
                } else {
                    $warnings[] = "actionSaveWorkerParametersValuesBase. Значения в worker_parameter_sensor параметров не было сохранено ";
                }
                unset($w_p_s_to_db);

                /**
                 * Сохраняем массово Worker_parameter_value
                 */
                if ($w_p_v_to_db) {
                    $insert_result = Yii::$app->db->createCommand()->batchInsert('worker_parameter_value',
                        ['worker_parameter_id', 'date_time', 'value', 'status_id'], $w_p_v_to_db)->execute();
                    $warnings[] = "actionSaveWorkerParametersValuesBase. Количество вставленных записей в worker_parameter_value " . $insert_result;
                } else {
                    $warnings[] = "actionSaveWorkerParametersValuesBase. Значения в worker_parameter_value параметров не было сохранено ";
                }
                unset($w_p_v_to_db);
                /**
                 * Важный комментарий!!!! если у воркера нет параметра 346 - шахтное поле, то для него кеш WorkerMine не инициализируется
                 * совсем, это значит, что для того, что бы воркер попал в кеш для него должен существовать хотя бы пустой параметр 346 и должен быть 158 =1 (зарегистрирован в шахте)
                 * соответственно перенос воркера из шахты в шахту осуществляется только при изменении параметра 346
                 * соответственно инициализация воркера первичная происходит при записи как раз этого самого параметра 346.
                 * если этого параметра нет, то мы базовые сведения о сенсоре просто сохраняем в БД
                 * Если же у воркера есть параметр 346, и мы меняем базовые сведения об этом параметре, то мы их применим при записи параметра 346.
                 * важно!!! при записи параметра 346 сведения в данном методе всегда беруться из БД!
                 * В то время как перенос сенсоров при работе служб может осуществляется и без забора данных с БД - путем получения старого значения кеша
                 * WorkerMine и записи всего, что там, в новый, но с учетом измененной шахты
                 *
                 * ЕЩЕ РАЗ:
                 * перенос шахты для этого метода возможен только при инициализации воркера из БД
                 * перенос шахты из служб сбора данных, где не меняются базовые сведения возможен через старое значение в кеше
                 */


                /**
                 * Блок переноса сенсора в новую шахту если таковое требуется
                 * если шахта есть, то делаем перенос или инициализацию, в зависимости от описанного выше
                 */
                $warnings[] = "actionSaveWorkerParametersValuesBase. Ищу статус спуска у воркера";

                if ($flag_save_to_cache == 0) {
                    (new WorkerCacheController())->delWorkerMineHash($worker_id);
                }

                if ($mine_id and $flag_save_to_cache) {
                    $worker = (new Query())
                        ->select(
                            [
                                'position_title',
                                'department_title',
                                'first_name',
                                'last_name',
                                'patronymic',
                                'gender',
                                'stuff_number',
                                'worker_object_id',
                                'worker_id',
                                'object_id',
                                'mine_id',
                                'checkin_status'
                            ])
                        ->from(['view_initWorkerMineCheckin'])
                        ->where(['mine_id' => $mine_id, 'worker_id' => $worker_id])
                        ->one();
                    if ($worker) {
                        $worker_to_cache = WorkerCacheController::buildStructureWorker(
                            $worker_id,
                            $worker['worker_object_id'],
                            $worker['object_id'],
                            $worker['stuff_number'],
                            $worker['last_name'] . " " . $worker['first_name'] . " " . $worker['patronymic'],
                            $worker['mine_id'],
                            $worker['position_title'],
                            $worker['department_title'],
                            $worker['gender']);
                        $ask_from_method = WorkerMainController::AddMoveWorkerMineInitDB($worker_to_cache);
                        $warnings[] = $ask_from_method['warnings'];
                        if ($ask_from_method['status'] != 1) {
                            $errors[] = $ask_from_method['errors'];
                            throw new Exception(" actionSaveWorkerParametersValuesBase::AddMoveWorkerMineInitDB. Ошибка добавления" . $worker_object_id);
                        }
                    } else {
                        $warnings[] = "actionSaveWorkerParametersValuesBase. Не смог найти данные работника в БД с привязкой к шахте";
                    }
                }

                /**
                 * Обновление параметров воркера в кеше
                 */
                $ask_from_method = (new WorkerCacheController())->multiSetWorkerParameterValueHash($worker_parameter_value_to_caches, $worker_id);
                $warnings[] = $ask_from_method['warnings'];
                if ($ask_from_method['status'] != 1) {
                    $errors[] = $ask_from_method['errors'];
                    throw new Exception("actionSaveWorkerParametersValuesBase. Не смог обновить параметры в кеше сенсора" . $worker_object_id);
                }


            } else {
                $warnings[] = 'actionSaveWorkerParametersValuesBase. Массив параметров на сохранение пуст. изменений в БД сделано не было';
            }
            /**
             * Блок построения выходных параметров после сохранения параметров
             * 1. Строим инфу по самому сенсору
             * 2. строим инфу для менюшки
             */
            $worker_object = WorkerObject::findOne(['id' => $worker_object_id]);//найти объект
            if ($worker_object) {//если найден, то построить массив объектов, если нет, то сохранить ошибку
                $response = $this->buildWorkerParameterArrayNew($worker_object_id);
                if ($response['status'] == 1) {
                    $objectParameters = $response['Items'];
                } else {
                    $errors[] = $response['errors'];
                }
            } else {
                throw new Exception("actionSaveWorkerParametersValuesBase. Объект с id $worker_object_id не найден");
            }


        } catch (Throwable $e) {
//            $status = 0;
            $errors[] = 'actionSaveWorkerParametersValuesBase. Исключение';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "actionSaveWorkerParametersValuesBase . Вышел с метода";
        $result_main = array('response' => $response, 'errors' => $errors, 'warnings' => $warnings, 'objectProps' => $objectParameters, 'objects' => $objects, 'worker_id' => $worker_id);//составить результирующий массив как массив полученных массивов         //вернуть AJAX-запросу данные и ошибки
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /* Метод удаления подразделений предприятия
         * Входные параметры:
         * - $post['id'] - (int) идентификатор подразделения предприятия
         * Выходные параметры: результат выполнения метода buildArray в формате json
         */
    public function actionDeleteDepartment()
    {
        $log = new LogAmicumFront("actionDeleteDepartment");

        $result = array();

        try {
            $session = Yii::$app->session;                                                                              // старт сессии
            $session->open();                                                                                           // открыть сессию
            if (!isset($session['sessionLogin'])) {                                                                     // если в сессии есть логин
                throw new Exception("Недостаточно прав для выполнения запроса. Нет авторизации");
            }

            if (!AccessCheck::checkAccess($session['sessionLogin'], 17)) {                                      // если пользователю разрешен доступ к функции
                throw new Exception("Недостаточно прав для выполнения запроса. Нет прав");
            }

            $post = Yii::$app->request->post(); //получение данных от ajax-запроса
            if (!isset($post['id']) or $post['id'] == "") {
                throw new Exception("Не передан идентификатор");
            }

            //Запросить подразделение предприятия по полученному идентификатору
            $company_department = CompanyDepartment::findOne($post['id']);

            //если подразделение предприятия есть
            if (!$company_department) {
                throw new Exception("Указанного подразделения не существует");
            }

            //вызов форсированного удаления подразделения
            $this->forcedDeleteDepartment($company_department);

            //строится массив данных с учетом добавленного предприятия
            $result = self::GetCompanyDepartmentForHandbook()['model'];

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result, 'companies' => $result], $log->getLogAll());
    }

    /* Функция форсированного удаления подразделения предприятия
     * Входные параметры
     * - $company_department - (CompanyDepartment) объект ПодразделениеПредприятия
     */
    public function forcedDeleteDepartment(CompanyDepartment $company_department)
    {
        if ($company_department->workers) {
            $workerCacheController = new WorkerCacheController();

            foreach ($company_department->workers as $worker) {                                                         // для каждого работника подразделения предприятия
                Employee::deleteAll(['id' => $worker->employee_id]);                                                    // !!!!!!!!! Удаляет у работника все данные, все-все)
                $workerCacheController->delParameterValueHash($worker->id);
                $workerCacheController->delWorkerMineHash($worker->id);
            }
        }

        $company_department->delete();                                                                                  // удалить подразделение предприятия
        $companies = Company::findAll(['upper_company_id' => $company_department->company_id]);
        foreach ($companies as $company) {
            self::recursiveCompanyDelete($company['id']);
        }
        Company::deleteAll(['id' => $company_department->company_id]);

        HandbookCachedController::clearDepartmentCache();
        HandbookCachedController::clearWorkerCache();
    }

    public static function recursiveCompanyDelete($company_id)
    {
        $companies = Company::findAll(['upper_company_id' => $company_id]);
        foreach ($companies as $company) {
            self::recursiveCompanyDelete($company['id']);
        }
        Company::deleteAll(['id' => $company_id]);
    }

    /********************************************************************
     *                          Функции удаления                        *
     ********************************************************************/

    /* Метод удаления предприятий
     * Входные параметры:
     * - $post['id'] - (int) идентификатор предприятия
     * Выходные параметры: результат выполнения метода buildArray в формате json
     */
    public function actionDeleteCompany()
    {
        $errors = array();
        $arr = array();
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 25)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                // Запросить предприятие по полученному идентификатору
                $company = Company::findOne($post['id']);
                //если предприятие есть
                if ($company) {
                    //вызов форсированного удаления предприятия
                    $this->forcedDeleteCompany($company);
                    //строится массив данных с учетом удаленного предприятия
                    $arr1 = self::GetCompanyDepartmentForHandbook();
                    $arr = $arr1['model'];
                } else {
                    $errors[] = "Указанного предприятия не существует";
                }
            } else {
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";
            $this->redirect('/');
        }
        $result = array('errors' => $errors, 'companies' => $arr);
        //массив возвращается ajax-запросу в формате json
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /* Функция форсированного удаления предприятия
     * Входные параметры
     * - $company - (Company) объект Предприятие
     */
    public function forcedDeleteCompany(Company $company)
    {
        if ($company->companyDepartments) {
            //для каждого подразделения предприятия
            foreach ($company->companyDepartments as $company_department) {
                //вызвать функцию форсированного удаления подразделения предприятия
                $this->forcedDeleteDepartment($company_department);
            }
        }
        //удалить предприятие
        $company->delete();
    }

    /**
     * Название метода: GetWorkerGroupByCompanyDepartment()
     * Метод получения списка людей сгруппированных по департаментам
     * http://127.0.0.1/read-manager-amicum?controller=handbooks\HandbookEmployee&method=GetWorkerGroupByCompanyDepartment&subscribe=&data=
     * @param
     * @return array
     * @package frontend\controllers\handbooks
     * @example
     *
     * Документация на портале:
     * @author Якимов М.Н.
     * Created date: on 22.05.2019 17:35
     * @since ver
     */
    public static function GetWorkerGroupByCompanyDepartment($data_post): array
    {
        $result = array();                                                                                                // промежуточный результирующий массив
        $errors = array();                                                                                              // массив ошибок
        $warnings = array();                                                                                              // массив предупреждений
        $status = 1;

        $warnings[] = 'GetWorkerGroupByCompanyDepartment. Зашел в метод';
        try {
            $workers = Worker::find()
                ->with('position')
                ->with('employee')
                ->all();
            if ($workers) {
                foreach ($workers as $worker) {
                    $list_worker[$worker->id]['worker_id'] = $worker->id;
                    $list_worker[$worker->id]['worker_position_title'] = $worker->position->title;
                    $list_worker[$worker->id]['worker_position_id'] = $worker->position->id;
                    $list_worker[$worker->id]['worker_position_qualification'] = $worker->position->qualification;
                    $list_worker[$worker->id]['birthday'] = date('d.m.Y', strtotime($worker->position->birthdate));
                    $list_worker[$worker->id]['worker_full_name'] = $worker->employee->last_name . ' ' . $worker->employee->first_name . ' ' . $worker->employee->patronymic;
                    $list_worker[$worker->id]['worker_tabel_number'] = $worker->tabel_number;
                }
                $result['list_worker'] = $list_worker;
            } else {
                throw new Exception("GetWorkerGroupByCompanyDepartment . список работников пуст");
            }

            $response = self::GetCompanyDepartment();
            if ($response['status'] == 1) {
                $status *= $response['status'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $result['list_company_department'] = $response['model'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("GetWorkerGroupByCompanyDepartment . Ошибка получения списка департаментов");
            }


        } catch (Throwable $e) {
            $status = 0;
            $errors[] = "GetWorkerGroupByCompanyDepartment . Исключение ";                                               // Добавляем в массив ошибок, полученную ошибку
            $errors[] = $e->getMessage();                                                                                // Добавляем в массив ошибок, полученную ошибку
            $errors[] = $e->getLine();                                                                                   // Добавляем в массив ошибок, полученную ошибку
        }
        $warnings[] = "GetWorkerGroupByCompanyDepartment . Вышел с метода";

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    /**
     * Название метода: GetWorkersForHandbook()
     * Метод получения списка людей
     * Выходные параметры:
     *      {worker_id}             - ключ работника
     *              worker_id                       - ключ работника
     *              gender                          - гендерный признак
     *              worker_position_id              - ключ должности
     *              worker_position_title           - название должности
     *              worker_position_qualification   - квалификакция
     *              company_id                      - ключ подразделения
     *              worker_full_name                - ФИО
     *              worker_tabel_number             - табельный номер
     *              birthday                        - форматированная дата рождения
     *              worker_role_id                  - ключ роли
     *              worker_worked                   - признак работает/не работает
     * @param
     * @return array
     * @package frontend\controllers\handbooks
     * @example http://127.0.0.1/read-manager-amicum?controller=handbooks\HandbookEmployee&method=GetWorkersForHandbook&subscribe=&data={}
     *
     * Документация на портале:
     * @author Некрасов Е.П.
     * Created date: on 01.07.2019 17:35
     * @since ver
     */
    public static function GetWorkersForHandbook($data_post): array
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("GetWorkersForHandbook");

        try {

            $log->addLog("Начало выполнения метода");

            $cache = Yii::$app->cache;
            $key = "GetWorkersForHandbook";
            $keyHash = "GetWorkersForHandbookHash";
            $list_worker = $cache->get($key);
            if (!$list_worker) {
                $log->addLog("Кеша не было, получаю данные из БД");
                $workers = Worker::find()
                    ->select(['worker.id as worker_id', 'position.title as worker_position_title',
                        'position.id as worker_position_id',
                        'employee.birthdate as birthdate',
                        'position.qualification as worker_position_qualification',
                        'employee.last_name as last_name',
                        'employee.first_name as first_name',
                        'employee.gender as gender',
                        'employee.patronymic as patronymic',
//                    'CONCAT(employee.last_name," ",employee.first_name," ",employee.patronymic) as full_name',
                        'worker.date_end as worker_date_end',
                        'worker.tabel_number as worker_tabel_number',
                        'worker.company_department_id as company_department_id',
                        'worker_object.role_id as worker_role_id'])
                    ->leftJoin('position', 'worker.position_id = position.id')
                    ->innerJoin('employee', 'worker.employee_id = employee.id')
                    ->leftJoin('worker_object', 'worker.id = worker_object.worker_id')
                    ->asArray()
                    ->all();

                $log->addLog("Получил данные");

                $date_time_now = strtotime(Assistant::GetDateTimeNow());
                foreach ($workers as $worker) {
                    $list_worker[$worker['worker_id']]['worker_id'] = $worker['worker_id'];
                    $list_worker[$worker['worker_id']]['worker_position_title'] = $worker['worker_position_title'];
                    $list_worker[$worker['worker_id']]['gender'] = $worker['gender'];
                    $list_worker[$worker['worker_id']]['worker_position_id'] = $worker['worker_position_id'];
                    $list_worker[$worker['worker_id']]['worker_position_qualification'] = $worker['worker_position_qualification'];
                    $patronymic = $worker['patronymic'];
                    $list_worker[$worker['worker_id']]['company_id'] = $worker['company_department_id'];
                    $list_worker[$worker['worker_id']]['worker_full_name'] = "{$worker['last_name']} {$worker['first_name']} $patronymic";
                    $list_worker[$worker['worker_id']]['worker_tabel_number'] = $worker['worker_tabel_number'];
                    $list_worker[$worker['worker_id']]['birthday'] = date('d.m.Y', strtotime($worker['birthdate']));
                    $list_worker[$worker['worker_id']]['worker_role_id'] = $worker['worker_role_id'];
                    if (!$worker['worker_date_end'] or strtotime($worker['worker_date_end']) > $date_time_now) {
                        $list_worker[$worker['worker_id']]['worker_worked'] = 1;    // работает
                    } else {
                        $list_worker[$worker['worker_id']]['worker_worked'] = 0;    // или не работает сейчас сотрудник
                    }
                }
                $hash = md5(json_encode($list_worker));
                $cache->set($keyHash, $hash, 60 * 60 * 24);
                $cache->set($key, $list_worker, 60 * 60 * 24);   // 60 * 60 * 24 = сутки
            } else {
                $log->addLog("Кеш был");
                $hash = $cache->get($keyHash);
            }

            $log->addLog("Закончил перепаковку данных");
            $result['hash'] = $hash;
            $result['handbook'] = $list_worker;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        if ($result == null) {
            $result = (object)array();
        }

        $log->addLog("Окончил выполнение метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Название метода: GetCompanyDepartment()
     * Метод получения списка участков
     *
     * @param $type_func = 1, где 1 - справочник, 2 - график выходов
     * @return array
     * @package frontend\controllers\handbooks
     * @example
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 22.05.2019 17:35
     * @since ver
     */
    public static function GetCompanyDepartment($type_func = 1): array
    {
        $companies_array = array();                                                                                          // Массив содержит список бригад сгруппированный по участкам
        $warnings = array();
        $errors = array();                                                                                                   // Массив ошибок при выполнении метода
        $status = 1;
        $warnings[] = 'GetCompanyDepartment type_func = ' . $type_func . ' тип данных = ' . gettype($type_func);
        $warnings[] = 'GetCompanyDepartment. Зашел в метод получения списка подразделений';
        try {
            // Получаем список всех компаний и департаментов
            // Жадная загрузка используется для получения всех вложеностей, все 10 глубоких вложеностей
            $companies = Company::find()
                ->innerJoin('company_department', 'company.id = company_department.company_id')
                ->innerJoin('department', 'company_department.department_id = department.id');
            if ($type_func === 2) {
                $companies->innerJoin('worker', 'worker.company_department_id = company_department.id');
            }
            $companies->groupBy('company_department.company_id')
                ->with('companies.companies.companies.companies.companies.companies.companies.companies.companies.companies')
                ->with(['companyDepartments',
                    'companies.companyDepartments',
                    'companies.companies.companyDepartments',
                    'companies.companies.companies.companyDepartments',
                    'companies.companies.companies.companies.companyDepartments',
                    'companies.companies.companies.companies.companies.companyDepartments',
                    'companies.companies.companies.companies.companies.companies.companyDepartments',
                    'companies.companies.companies.companies.companies.companies.companies.companyDepartments',
                    'companies.companies.companies.companies.companies.companies.companies.companies.companyDepartments',
                    'companies.companies.companies.companies.companies.companies.companies.companies.companies.companyDepartments',
                    'companies.companies.companies.companies.companies.companies.companies.companies.companies.companies.companyDepartments'])
                ->with(['companyDepartments.department',
                    'companies.companyDepartments.department',
                    'companies.companies.companyDepartments.department',
                    'companies.companies.companies.companyDepartments.department',
                    'companies.companies.companies.companies.companyDepartments.department',
                    'companies.companies.companies.companies.companies.companyDepartments.department',
                    'companies.companies.companies.companies.companies.companies.companyDepartments.department',
                    'companies.companies.companies.companies.companies.companies.companies.companyDepartments.department',
                    'companies.companies.companies.companies.companies.companies.companies.companies.companyDepartments.department',
                    'companies.companies.companies.companies.companies.companies.companies.companies.companies.companyDepartments.department',
                    'companies.companies.companies.companies.companies.companies.companies.companies.companies.companies.companyDepartments.department'])
                ->with(['companyDepartments.workers',
                    'companies.companyDepartments.workers',
                    'companies.companies.companyDepartments.workers',
                    'companies.companies.companies.companyDepartments.workers',
                    'companies.companies.companies.companies.companyDepartments.workers',
                    'companies.companies.companies.companies.companies.companyDepartments.workers',
                    'companies.companies.companies.companies.companies.companies.companyDepartments.workers',
                    'companies.companies.companies.companies.companies.companies.companies.companyDepartments.workers',
                    'companies.companies.companies.companies.companies.companies.companies.companies.companyDepartments.workers',
                    'companies.companies.companies.companies.companies.companies.companies.companies.companies.companyDepartments.workers',
                    'companies.companies.companies.companies.companies.companies.companies.companies.companies.companies.companyDepartments.workers']);

            $indexes = ["lvl1" => 0, "lvl2" => 0, "lvl3" => 0, "lvl4" => 0, "lvl5" => 0,
                "lvl6" => 0, "lvl7" => 0, "lvl8" => 0, "lvl9" => 0, "lvl10" => 0];
            $warnings[] = 'GetCompanyDepartment. Данные по подразделениям получены, группировка';
            // Начинаем перебор полученных данных, компаний самого верхнего уровня у которых нет upper
            foreach ($companies->each() as $company) {
                // Начинаем перебор с самого нижнего уровня
                if ($company->upper_company_id === NULL) {
                    $companies_lvl1 = self::buildArray($company, $type_func);                                           // Записываем данные в массив для компаний первого уровня
                    $companies_array[$indexes['lvl1']] = $companies_lvl1;
                    foreach ($company->companies as $company_down_lvl2)                                                 // Для каждого из них находим нижестоящие
                    {
                        $companies_lvl2 = self::buildArray($company_down_lvl2, $type_func);                             // Записываем данные в массив для компаний второго и последующих уровней
                        $companies_array[$indexes['lvl1']]['companies'][$indexes['lvl2']] = $companies_lvl2;
                        foreach ($company_down_lvl2->companies as $company_down_lv3) {
                            $companies_lvl3 = self::buildArray($company_down_lv3, $type_func);
                            $companies_array[$indexes['lvl1']]['companies'][$indexes['lvl2']]
                            ['companies'][$indexes['lvl3']] = $companies_lvl3;
                            foreach ($company_down_lv3->companies as $company_down_lv4) {
                                $companies_lvl4 = self::buildArray($company_down_lv4, $type_func);
                                $companies_array[$indexes['lvl1']]['companies'][$indexes['lvl2']]['companies']
                                [$indexes['lvl3']]['companies'][$indexes['lvl4']] = $companies_lvl4;
                                foreach ($company_down_lv4->companies as $company_down_lv5) {
                                    $companies_lvl5 = self::buildArray($company_down_lv5, $type_func);
                                    $companies_array[$indexes['lvl1']]['companies'][$indexes['lvl2']]['companies']
                                    [$indexes['lvl3']]['companies'][$indexes['lvl5']] = $companies_lvl5;
                                    foreach ($company_down_lv5->companies as $company_down_lv6) {
                                        $companies_lvl6 = self::buildArray($company_down_lv6, $type_func);
                                        $companies_array[$indexes['lvl1']]['companies'][$indexes['lvl2']]['companies']
                                        [$indexes['lvl3']]['companies'][$indexes['lvl4']]
                                        ['companies'][$indexes['lvl6']] = $companies_lvl6;
                                        foreach ($company_down_lv6->companies as $company_down_lv7) {
                                            $companies_lvl7 = self::buildArray($company_down_lv7, $type_func);
                                            $companies_array[$indexes['lvl1']]['companies'][$indexes['lvl2']]['companies']
                                            [$indexes['lvl3']]['companies'][$indexes['lvl4']]['companies']
                                            [$indexes['lvl5']]['companies'][$indexes['lvl7']] = $companies_lvl7;
                                            foreach ($company_down_lv7->companies as $company_down_lv8) {
                                                $companies_lvl8 = self::buildArray($company_down_lv8, $type_func);
                                                $companies_array[$indexes['lvl1']]['companies'][$indexes['lvl2']]['companies']
                                                [$indexes['lvl3']]['companies'][$indexes['lvl4']]['companies']
                                                [$indexes['lvl5']]['companies'][$indexes['lvl6']]['companies']
                                                [$indexes['lvl8']] = $companies_lvl8;
                                                foreach ($company_down_lv8->companies as $company_down_lv9) {
                                                    $companies_lvl9 = self::buildArray($company_down_lv9, $type_func);
                                                    $companies_array[$indexes['lvl1']]['companies'][$indexes['lvl2']]['companies']
                                                    [$indexes['lvl3']]['companies'][$indexes['lvl4']]['companies']
                                                    [$indexes['lvl5']]['companies'][$indexes['lvl6']]['companies']
                                                    [$indexes['lvl8']]['companies'][$indexes['lvl9']] = $companies_lvl9;
                                                    foreach ($company_down_lv9->companies as $company_down_lv10) {
                                                        $companies_lvl10 = self::buildArray($company_down_lv10, $type_func);
                                                        $companies_array[$indexes['lvl1']]['companies'][$indexes['lvl2']]['companies']
                                                        [$indexes['lvl3']]['companies'][$indexes['lvl4']]['companies']
                                                        [$indexes['lvl5']]['companies'][$indexes['lvl6']]['companies']
                                                        [$indexes['lvl8']]['companies'][$indexes['lvl9']]['companies']
                                                        [$indexes['lvl10']] = $companies_lvl10;
                                                        $indexes['lvl10']++;
                                                    }
                                                    $indexes['lvl10'] = 0;
                                                    $indexes['lvl9']++;
                                                }
                                                $indexes['lvl9'] = 0;
                                                $indexes['lvl8']++;
                                            }
                                            $indexes['lvl8'] = 0;
                                            $indexes['lvl7']++;
                                        }
                                        $indexes['lvl7'] = 0;
                                        $indexes['lvl6']++;
                                    }
                                    $indexes['lvl6'] = 0;
                                    $indexes['lvl5']++;
                                }
                                $indexes['lvl5'] = 0;
                                $indexes['lvl4']++;
                            }
                            $indexes['lvl4'] = 0;
                            $indexes['lvl3']++;
                        }
                        $indexes['lvl3'] = 0;
                        $indexes['lvl2']++;
                    }
                    $indexes['lvl2'] = 0;
                    $indexes['lvl1']++;
                }
            }
        } catch (Throwable $ex) {
            $status = 0;
            $errors[] = 'GetCompanyDepartment. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();                                                                             // Добавляем в массив ошибок, полученную ошибку
        }
        $warnings[] = 'GetCompanyDepartment. Данные по подразделениям получены';
        return array('model' => $companies_array, 'warnings' => $warnings, 'errors' => $errors, 'status' => $status);
    }

    /**
     * Метод GetCompanyList() - метод получения списка компаний (справочник)
     * @return array - массив со следующей структурой: [company_id]
     *                                                          id:                                                     -идентификатор компании
     *                                                          title:                                                  -наименование компании
     *
     * @package frontend\controllers\handbooks
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\HandbookEmployee&method=GetCompanyList&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.09.2019 15:40
     */
    public static function GetCompanyList(): array
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetCompanyList. Начало метода';
        try {
            $result = Company::find()
                ->select(['id', 'title', 'upper_company_id'])
                ->indexBy('id')
                ->all();
        } catch (Throwable $exception) {
            $errors[] = 'GetCompanyList. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetCompanyList. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetCompanyListWithParent() - метод получения списка компаний c родителем (справочник)
     * @return array - массив со следующей структурой: [company_id]
     *                                                          id:                                                     -идентификатор компании
     *                                                          title:                                                  -наименование компании
     *                                                          upper_company_id:                                       -ключ родительской компании
     *
     * @package frontend\controllers\handbooks
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\HandbookEmployee&method=GetCompanyListWithParent&subscribe=&data={}
     */
    public static function GetCompanyListWithParent(): array
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = (object)array();
        $warnings[] = 'GetCompanyListWithParent. Начало метода';
        try {
            $result = Company::find()
                ->indexBy('id')
                ->all();
        } catch (Throwable $exception) {
            $errors[] = 'GetCompanyList. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetCompanyListWithParent. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Название метода: GetCompanyDepartmentWithoutWorkers()
     * Метод получения списка участков
     *
     * @package frontend\controllers\handbooks
     * @example
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 22.05.2019 17:35
     * @since ver
     */
    public static function GetCompanyDepartmentWithoutWorkers(): array
    {
        $companies_array = array();                                                                                          // Массив содержит список бригад сгруппированный по участкам
        $warnings = array();
        $errors = array();                                                                                                   // Массив ошибок при выполнении метода
        $status = 1;

        $warnings[] = 'GetCompanyDepartment. Зашел в метод получения списка подразделений';
        try {
            // Получаем список всех компаний и департаментов
            // Жадная загрузка используется для получения всех вложеностей, все 10 глубоких вложеностей
            $companies = Company::find()
                ->innerJoin('company_department', 'company.id = company_department.company_id')
                ->innerJoin('department', 'company_department.department_id = department.id')
//                ->innerJoin('worker', 'worker.company_department_id = company_department.id')
                ->groupBy('company_department.company_id')
                ->with('companies.companies.companies.companies.companies.companies.companies.companies.companies.companies')
                ->with(['companyDepartments',
                    'companies.companyDepartments',
                    'companies.companies.companyDepartments',
                    'companies.companies.companies.companyDepartments',
                    'companies.companies.companies.companies.companyDepartments',
                    'companies.companies.companies.companies.companies.companyDepartments',
                    'companies.companies.companies.companies.companies.companies.companyDepartments',
                    'companies.companies.companies.companies.companies.companies.companies.companyDepartments',
                    'companies.companies.companies.companies.companies.companies.companies.companies.companyDepartments',
                    'companies.companies.companies.companies.companies.companies.companies.companies.companies.companyDepartments',
                    'companies.companies.companies.companies.companies.companies.companies.companies.companies.companies.companyDepartments'])
                ->with(['companyDepartments.department',
                    'companies.companyDepartments.department',
                    'companies.companies.companyDepartments.department',
                    'companies.companies.companies.companyDepartments.department',
                    'companies.companies.companies.companies.companyDepartments.department',
                    'companies.companies.companies.companies.companies.companyDepartments.department',
                    'companies.companies.companies.companies.companies.companies.companyDepartments.department',
                    'companies.companies.companies.companies.companies.companies.companies.companyDepartments.department',
                    'companies.companies.companies.companies.companies.companies.companies.companies.companyDepartments.department',
                    'companies.companies.companies.companies.companies.companies.companies.companies.companies.companyDepartments.department',
                    'companies.companies.companies.companies.companies.companies.companies.companies.companies.companies.companyDepartments.department']);

            $indexes = ["lvl1" => 0, "lvl2" => 0, "lvl3" => 0, "lvl4" => 0, "lvl5" => 0,
                "lvl6" => 0, "lvl7" => 0, "lvl8" => 0, "lvl9" => 0, "lvl10" => 0];
            $warnings[] = 'GetCompanyDepartment. Данные по подразделениям получены, группировка';
            // Начинаем перебор полученных данных, компаний самого верхнего уровня у которых нет upper
            foreach ($companies->each() as $company) {
                // Начинаем перебор с самого нижнего уровня
                if ($company->upper_company_id === NULL) {
                    $companies_lvl1 = self::buildDepartmentArrayWithoutWorkers($company);                                           // Записываем данные в массив для компаний первого уровня
                    $companies_array[$indexes['lvl1']] = $companies_lvl1;
                    foreach ($company->companies as $company_down_lvl2)                                                 // Для каждого из них находим нижестоящие
                    {
                        $companies_lvl2 = self::buildDepartmentArrayWithoutWorkers($company_down_lvl2);                             // Записываем данные в массив для компаний второго и последующих уровней
                        $companies_array[$indexes['lvl1']]['companies'][$indexes['lvl2']] = $companies_lvl2;
                        foreach ($company_down_lvl2->companies as $company_down_lv3) {
                            $companies_lvl3 = self::buildDepartmentArrayWithoutWorkers($company_down_lv3);
                            $companies_array[$indexes['lvl1']]['companies'][$indexes['lvl2']]
                            ['companies'][$indexes['lvl3']] = $companies_lvl3;
                            foreach ($company_down_lv3->companies as $company_down_lv4) {
                                $companies_lvl4 = self::buildDepartmentArrayWithoutWorkers($company_down_lv4);
                                $companies_array[$indexes['lvl1']]['companies'][$indexes['lvl2']]['companies']
                                [$indexes['lvl3']]['companies'][$indexes['lvl4']] = $companies_lvl4;
                                foreach ($company_down_lv4->companies as $company_down_lv5) {
                                    $companies_lvl5 = self::buildDepartmentArrayWithoutWorkers($company_down_lv5);
                                    $companies_array[$indexes['lvl1']]['companies'][$indexes['lvl2']]['companies']
                                    [$indexes['lvl3']]['companies'][$indexes['lvl5']] = $companies_lvl5;
                                    foreach ($company_down_lv5->companies as $company_down_lv6) {
                                        $companies_lvl6 = self::buildDepartmentArrayWithoutWorkers($company_down_lv6);
                                        $companies_array[$indexes['lvl1']]['companies'][$indexes['lvl2']]['companies']
                                        [$indexes['lvl3']]['companies'][$indexes['lvl4']]
                                        ['companies'][$indexes['lvl6']] = $companies_lvl6;
                                        foreach ($company_down_lv6->companies as $company_down_lv7) {
                                            $companies_lvl7 = self::buildDepartmentArrayWithoutWorkers($company_down_lv7);
                                            $companies_array[$indexes['lvl1']]['companies'][$indexes['lvl2']]['companies']
                                            [$indexes['lvl3']]['companies'][$indexes['lvl4']]['companies']
                                            [$indexes['lvl5']]['companies'][$indexes['lvl7']] = $companies_lvl7;
                                            foreach ($company_down_lv7->companies as $company_down_lv8) {
                                                $companies_lvl8 = self::buildDepartmentArrayWithoutWorkers($company_down_lv8);
                                                $companies_array[$indexes['lvl1']]['companies'][$indexes['lvl2']]['companies']
                                                [$indexes['lvl3']]['companies'][$indexes['lvl4']]['companies']
                                                [$indexes['lvl5']]['companies'][$indexes['lvl6']]['companies']
                                                [$indexes['lvl8']] = $companies_lvl8;
                                                foreach ($company_down_lv8->companies as $company_down_lv9) {
                                                    $companies_lvl9 = self::buildDepartmentArrayWithoutWorkers($company_down_lv9);
                                                    $companies_array[$indexes['lvl1']]['companies'][$indexes['lvl2']]['companies']
                                                    [$indexes['lvl3']]['companies'][$indexes['lvl4']]['companies']
                                                    [$indexes['lvl5']]['companies'][$indexes['lvl6']]['companies']
                                                    [$indexes['lvl8']]['companies'][$indexes['lvl9']] = $companies_lvl9;
                                                    foreach ($company_down_lv9->companies as $company_down_lv10) {
                                                        $companies_lvl10 = self::buildDepartmentArrayWithoutWorkers($company_down_lv10);
                                                        $companies_array[$indexes['lvl1']]['companies'][$indexes['lvl2']]['companies']
                                                        [$indexes['lvl3']]['companies'][$indexes['lvl4']]['companies']
                                                        [$indexes['lvl5']]['companies'][$indexes['lvl6']]['companies']
                                                        [$indexes['lvl8']]['companies'][$indexes['lvl9']]['companies']
                                                        [$indexes['lvl10']] = $companies_lvl10;
                                                        $indexes['lvl10']++;
                                                    }
                                                    $indexes['lvl10'] = 0;
                                                    $indexes['lvl9']++;
                                                }
                                                $indexes['lvl9'] = 0;
                                                $indexes['lvl8']++;
                                            }
                                            $indexes['lvl8'] = 0;
                                            $indexes['lvl7']++;
                                        }
                                        $indexes['lvl7'] = 0;
                                        $indexes['lvl6']++;
                                    }
                                    $indexes['lvl6'] = 0;
                                    $indexes['lvl5']++;
                                }
                                $indexes['lvl5'] = 0;
                                $indexes['lvl4']++;
                            }
                            $indexes['lvl4'] = 0;
                            $indexes['lvl3']++;
                        }
                        $indexes['lvl3'] = 0;
                        $indexes['lvl2']++;
                    }
                    $indexes['lvl2'] = 0;
                    $indexes['lvl1']++;
                }
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = $e->getMessage();                                                                                // Добавляем в массив ошибок, полученную ошибку
        }
        $warnings[] = 'GetCompanyDepartment. Данные по подразделениям получены';
        return array('Items' => $companies_array, 'warnings' => $warnings, 'errors' => $errors, 'status' => $status);
    }

    /**
     * Название метода: buildArray()
     * Метод получения информации о компании, используется дабы не загромождать код в методе  GetCompanyDepartment
     *
     * @param $company - объект компании для которой нужно подготовить данные в нормальном виде
     * @param $type_func : 1 - справочник, 2 - график выходов
     * @return mixed
     * @package frontend\controllers\ordersystem\workertimetable
     * @example
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 21.05.2019 10:32
     * @since ver
     */
    public static function buildArray($company, $type_func): mixed
    {

        // Для каждой из компании нужно получить список нижестоящих компаний
        $companies_array['id'] = $company->id;                                                              // Сохраняется данные предприятия
        $companies_array['title'] = $company->title;
        $companies_array['upper_company_id'] = $company->upper_company_id;

        if ($company->companyDepartments)                                                                            // Если у компании есть подразделения
        {
            $j = 0;
            foreach ($company->companyDepartments as $companyDepartment) {                                            // Для каждого подразделения предприятия
                if ($companyDepartment->workers) {
                    if ($type_func === 2) {
                        foreach ($companyDepartment->workers as $worker) {
                            $companies_array['departments'][$j]['worker'][$worker->id] = $worker->id;
                        }
                    }
                    $companies_array['departments'][$j]['id'] = $companyDepartment->id;                         // Добавление предприятия подразделению
                    $companies_array['departments'][$j]['title'] =
                        $companyDepartment->department->title;
                    $j++;
                }
            }
        }
        return $companies_array;
    }

    /**
     * Название метода: buildDepartmentArrayWithoutWorkers()
     * Метод получения информации о компании, используется дабы не загромождать код в методе  GetCompanyDepartment
     *
     * @param $company - объект компании для которой нужно подготовить данные в нормальном виде
     * @return mixed
     * @package frontend\controllers\ordersystem\workertimetable
     * @example
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 21.05.2019 10:32
     * @since ver
     */
    public static function buildDepartmentArrayWithoutWorkers($company): mixed
    {

        // Для каждой из компании нужно получить список нижестоящих компаний
        $companies_array['id'] = $company->id;                                                              // Сохраняется данные предприятия
        $companies_array['title'] = $company->title;
        $companies_array['upper_company_id'] = $company->upper_company_id;

        if ($company->companyDepartments)                                                                            // Если у компании есть подразделения
        {
            $j = 0;
            foreach ($company->companyDepartments as $companyDepartment) {                                            // Для каждого подразделения предприятия
                $companies_array['departments'][$j]['id'] = $companyDepartment->id;                         // Добавление предприятия подразделению
                $companies_array['departments'][$j]['title'] =
                    $companyDepartment->department->title;
                $j++;
            }
        }
        return $companies_array;
    }

    /**
     * Функция вывода списка сотрудников на странице Справочник сотрудников
     */
    public function actionGetEmployeeData()
    {
        $post = Yii::$app->request->post();
        $errors = array();
        $employees = array();
        if (isset($post['company_id'], $post['company_department_id']) && $post['company_id'] != '' && $post['company_department_id'] != '') {
            $company_id = (int)$post['company_id'];
            $company_department_id = (int)$post['company_department_id'];
            $employees = $this->getWorkersFromView($company_id, $company_department_id);
        }


        $result = array('errors' => $errors, 'employees' => $employees);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    public function getWorkersFromView($company_id, $company_department_id): array
    {
        $sql_filter_company = 'company_id = ' . $company_id;
        $sql_filter_department = 'company_department_id = ' . $company_department_id;
        return (new Query())
            ->select([
                'worker_id',
                'worker_object_id',
                'fio',
                'DATE_FORMAT(birthdate, " %d.%m.%Y") as birthdate',
                'position_title',
//                 'company_department_id',
                'company_title',
                'department_title',
                'tabel_number',
                'DATE_FORMAT(date_start, " %d.%m.%Y") as date_start',
                'DATE_FORMAT(date_end, " %d.%m.%Y") as date_end',
                'vgk_status'
            ])
            ->from('view_handbook_employee')
            ->where($sql_filter_company)
            ->andWhere($sql_filter_department)
            ->orderBy('fio')
            ->all();

    }

    public function actionGetArraysForAddingCompany()
    {
        $companies = Company::find()
            ->select(['title', 'id'])
            ->orderBy(['title' => SORT_ASC])
            ->asArray()
            ->all();
        $plan_shifts = PlanShift::find()
            ->select(['title', 'id'])
            ->orderBy(['title' => SORT_ASC])
            ->asArray()
            ->all();
        $result = array('companies' => $companies, 'workmodes' => $plan_shifts);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /* Метод добавления предприятий.
     * Входные параметры:
     * - $post['title'] - (string) название нового предприятия
     * - $post['upper'] - (int) идентификатор вышестоящего предприятия
     * - $post['work_mode'] – (int) идентификатор режима работы (план смен)
     * Выходные параметры: результат выполнения метода buildArray в формате json
     */
    public function actionAddCompany()
    {
        $log = new LogAmicumFront("actionAddCompany");

        $result = array();
        $company_id = -1;
        $arr = array();

        try {
            $log->addLog("Начал выполнение метода");

            $session = Yii::$app->session;                                                                              // старт сессии
            $session->open();                                                                                           // открыть сессию

            if (!isset($session['sessionLogin'])) {                                                                     // если в сессии есть логин
                throw new Exception("Время сессии закончилось. Требуется повторный ввод пароля");
            }

            if (!AccessCheck::checkAccess($session['sessionLogin'], 18)) {                                       // если пользователю разрешен доступ к функции
                throw new Exception("Недостаточно прав для совершения данной операции");
            }

            $post = Yii::$app->request->post(); //получение данных от ajax-запроса

            if (!isset($post['title']) or $post['title'] == '') {//если название предприятия задано
                throw new Exception("Название предприятия не задано");
            }

            $company_title = $post['title'];

            if (!isset($post['work_mode']) or $post['work_mode'] == '') {//если название предприятия задано
                throw new Exception("Не указан режим работы предприятия");
            }

            $work_mode = $post['work_mode'];

            if (isset($post['upper']) and $post['upper'] != '') {                                                       // если название предприятия задано
                $upper_company_id = $post['upper'];
            } else {
                $upper_company_id = null;
            }

            $response = self::AddDepartment($upper_company_id, $company_title, DepartmentTypeEnum::OTHER, $work_mode);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Не удалось сохранить новое подразделение");
            }

            $company_id = $response['company_id'];


            $arr = self::GetCompanyDepartmentForHandbook()['model'];                                                    // строится массив данных с учетом добавленного предприятия

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }


        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result, 'arr' => $arr, 'company_id' => $company_id], $log->getLogAll());
    }

    public function actionGetArraysForAddingDepartment()
    {
        $companies = Company::find()
            ->select(['title', 'id'])
            ->orderBy(['title' => SORT_ASC])
            ->asArray()
            ->all();
        $department_types = DepartmentType::find()
            ->select(['title', 'id'])
            ->orderBy(['title' => SORT_ASC])
            ->asArray()
            ->all();
        $plan_shifts = PlanShift::find()
            ->select(['title', 'id'])
            ->orderBy(['title' => SORT_ASC])
            ->asArray()
            ->all();
        $result = array('companies' => $companies, 'department_types' => $department_types, 'workmodes' => $plan_shifts);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    //перенос метода, добавления департаментов в базовый метод - выполнен его полный рефакторинг - изменены входные данные.

    public function actionGetArraysForWorker()
    {
        $companies = Company::find()
            ->select(['title', 'id'])
            ->orderBy(['title' => SORT_ASC])
            ->asArray()
            ->all();
        $department_types = DepartmentType::find()
            ->select(['title', 'id'])
            ->orderBy(['title' => SORT_ASC])
            ->asArray()
            ->all();
        $plan_shifts = PlanShift::find()
            ->select(['title', 'id'])
            ->orderBy(['title' => SORT_ASC])
            ->asArray()
            ->all();
        $departments = Department::find()
            ->select(['title', 'id'])
            ->orderBy(['title' => SORT_ASC])
            ->asArray()
            ->all();
        $positions = Position::find()
            ->select(['title', 'id'])
            ->orderBy(['title' => SORT_ASC])
            ->asArray()
            ->all();

        $post = Assistant::GetServerMethod();

        $errors = array();
        $employee_info['errors'] = '';
        if (isset($post['worker_id']) && $post['worker_id'] != '') {
            $worker_id = (int)$post['worker_id'];
            $employee_info = $this->getEmployeeEditInformation($worker_id);
//            echo " < pre>";
//            var_dump($employee_info);
//            var_dump($errors);
//            echo " </pre > ";
            $errors = array_merge($errors, $employee_info['errors']);
            $result = array('companies' => $companies, 'department_types' => $department_types, 'workmodes' => $plan_shifts,
                'positions' => $positions, 'departments' => $departments, 'errors' => $errors, 'employee_info' => $employee_info['employee_data']);
        } else {
            $result = array('companies' => $companies, 'department_types' => $department_types, 'workmodes' => $plan_shifts,
                'positions' => $positions, 'departments' => $departments, 'errors' => $errors);

        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }


    /**
     * Название метода: actionAddWorker()
     *
     * !!!!!!ВАЖНО!!!!!!!
     * Нужно чтоб в БД была заполнена таблица с ролями(role). id = 9 title = Прочее
     * Нужно чтоб в БД была заполнена таблица с типами департамента(department_type). id = 5 title = Прочее
     * !!!!!ВАЖНО!!!!!!!
     *
     * Документация на портале:
     * @package app\controllers
     * Метод добавления работника
     *
     * Входные обязательные параметры:
     * - $post['first_name'] — (string) имя работника
     * - $post['last_name'] — (string) фамилия работника
     * - $post['patronymic'] – (string) отчество работника
     * - $post['gender'] – (char/string) пол работника (м/ж)
     * - $post['work_mode'] – (int) идентификатор режима работы
     * - $post['birth_date'] – (date) дата рождения
     * - $post['date_start'] – (date) дата начала работы
     * - $post['date_end'] – (date) дата окончания работы
     * - $post['type_obj'] – (int) идентификатор типа работы
     * - $post['height'] – (int) рост работника
     * - $post['company'] – (int) идентификатор предприятия
     * - $post['department'] – (int) идентификатор подразделения
     * - $post['position'] – (int) идентификатор должности
     * - $post['photo'] – (string) адрес фотографии
     * - $post['tabel_number'] – (string) табельный номер работника
     * - $post['pass_number'] – (string) номер пропуска работника
     * Входные необязательные параметры
     *
     * @url
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 17.01.2019 17:14
     * @since ver1.1
     */
    public function actionAddWorker()
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        try {
            $warnings[] = "actionAddWorker. Начал выполнять метод";

            $arrWorkers = array();
            $arrMines = array();
            $worker_id = null;
            $photo_url = null;
            $session = Yii::$app->session;
            $session->open();                                                                                               //открыть сессию
            if (!isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
                throw new Exception("actionAddWorker.Время сессии закончилось . Требуется повторный ввод пароля");
            }

            if (!AccessCheck::checkAccess($session['sessionLogin'], 19)) {                                                //если пользователю разрешен доступ к функции
                throw new Exception("actionAddWorker. Недостаточно прав для совершения данной операции");
            }

            $post = Assistant::GetServerMethod();
            $worker_id = null;//объявляем переменную для хранения id добавленного работника
            //По ФИО и дате рождения выбирается человек (employee)
            if (
                isset($post['first_name']) && $post['first_name'] != '' &&
                isset($post['last_name']) && $post['last_name'] != '' &&
                isset($post['birth_date']) && $post['birth_date'] != '' &&
                isset($post['company']) && $post['company'] != '' &&
                isset($post['department']) && $post['department'] != '' &&
                isset($post['tabel_number']) && $post['tabel_number'] != '' &&
                isset($post['position']) && $post['position'] != '' &&
                isset($post['department_type_id']) && $post['department_type_id'] != ''
            ) {
                $first_name = (string)$post['first_name'];                                                                        //++
                $last_name = (string)$post['last_name'];                                                                          //++
                $patronymic = (isset($post['patronymic']) and $post['patronymic'] != '') ? (string)$post['patronymic'] : '';      //------
                $birth_date = strtotime($post['birth_date']);                                                                     //++
                $gender = (string)$post['gender'];                                                                                //++
                $company_id = (int)$post['company'];                                                                           //++
                $department_id = (int)$post['department'];
                $position_id = (int)$post['position'];                                                                         //++
                $staff_number = (string)$post['tabel_number'];                                                                    //++
                $file = isset($_FILES['imageFile']) ? $_FILES['imageFile'] : null;
                $file_name = isset($post['imageName']) ? explode('.', $post['imageName']) : null;
                $file_extension = isset($file_name) ? $file_name[count($file_name) - 1] : null;
                $height = $post['height'];
            } else {
                throw new Exception("actionAddWorker. Переданы не все обязательные параметры");
            }
            //Если задано подразделение
            if (isset($post['department_type_id']) && $post['department_type_id'] != '') {
                //отметить новый тип подразделения
                $department_type_id = (int)$post['department_type_id'];
            } else {
                $department_type_id = DepartmentTypeEnum::OTHER;                                                                            //прочее
            }
            $date_start = isset($post['date_start']) ? $post['date_start'] : null;
            if (isset($post['date_end']) and $post['date_end'] !== '') {
                $date_end = $post['date_end'];
            } else {
                $date_end = null;
            }
            if (isset($post['vgk_status']) and $post['vgk_status'] !== '') {
                $vgk_status = $post['vgk_status'];
            } else {
                $vgk_status = null;
            }
            if (!isset($post['work_mode']) || $post['work_mode'] == '' || $post['work_mode'] === 'undefined') {                                               //если не задан режим работы
                $work_mode = 1;                 //он наследуется от подразделения
            } else {
                $work_mode = (int)$post['work_mode'];
            }
            $type_obj = isset($post['type_obj']) ? (string)$post['type_obj'] : null;
            if (isset($post['role_id']) && $post['role_id'] && $post['role_id'] != '')                     //если передали роль работника
            {
                $role_id = $post['role_id'];                                                //записываем его роль
            } else                                                                                           //иначе пишем что роль Прочее
            {
                $role_id = 9;
            }
            $pass_number = isset($post['pass_number']) ? $post['pass_number'] : null;
            $arrWorkers = array();
            $arrMines = array();
            $worker_id = null;
            $photo_url = null;
            $saving_data = $this->SaveWorkerData($first_name, $last_name, $patronymic, $birth_date, $gender, $company_id, $department_id,
                $department_type_id, $position_id, $staff_number, $file, $file_name, $file_extension, $height, $date_start,
                $date_end, $vgk_status, $work_mode, $type_obj, $role_id, $pass_number);
            if ($saving_data['status'] == 1) {
                $arrWorkers = $saving_data['Items']['arrWorkers'];
                $arrMines = $saving_data['Items']['arrMines'];
                $worker_id = $saving_data['Items']['worker_id'];
                if (isset($saving_data['Items']['photo_url']) && !empty($saving_data['Items']['photo_url'])) {
                    $photo_url = $saving_data['Items']['photo_url'];
                } else {
                    $photo_url = null;
                }
            } else {
                $errors[] = $saving_data['errors'];
                throw new Exception('addWorker. Ошибка при сохранении данных работника');
            }

        } catch (Throwable $ex) {
            $errors[] = "actionAddWorker. Исключение";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $result_main = array(
            'Items' => $result,
            'workers' => $arrWorkers,
            'mines' => $arrMines,
            'worker_id' => $worker_id,
            'url' => $photo_url,
            'status' => $status,
            'errors' => $errors,
            'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    //метод копирует параметры типового параметра в параметры конкретного объекта - нужен для создания конкретного объекта по шаблону типового объекта
    public function actionCopyTypicalParametersToWorker($typical_object_id, $worker_object_id): array
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        try {
            //копирование параметров справочных
            $type_object_parameters = TypeObjectParameter::find()->where(['object_id' => $typical_object_id])->all();
            if ($type_object_parameters)                                                                                                               //Находим все параметры типового объекта
            {
                foreach ($type_object_parameters as $type_object_parameter) {
                    $worker_parameter = new WorkerParameter();
                    $worker_parameter->parameter_id = $type_object_parameter->parameter_id;
                    $worker_parameter->parameter_type_id = $type_object_parameter->parameter_type_id;
                    $worker_parameter->worker_object_id = $worker_object_id;
                    if (!$worker_parameter->save()) {
                        $errors[] = $worker_parameter->errors;
                        throw new Exception("actionCopyTypicalParametersToWorker. Не удалось сохранить WorkerParameter");
                    }
                }
            }
        } catch (Throwable $ex) {
            $errors[] = "actionCopyTypicalParametersToWorker. Исключение";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        return array(
            'Items' => $result,
            'status' => $status,
            'errors' => $errors,
            'warnings' => $warnings);
    }

    //добавление нового параметра работника из страницы фронтэнда
    public function actionAddWorkerParameterBase()
    {
        $errors = array();
        $parametersArray = array();
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 21)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                if (isset($post['id']) && isset($post['parameter_id']) && isset($post['parameter_type_id'])) {
                    $worker_object_id = $post['id'];
                    $parameter_id = $post['parameter_id'];
                    $parameter_type_id = $post['parameter_type_id'];
                    //            $worker_id = WorkerObject::findOne($worker_object_id)->worker_id;

                    $worker_parameter = $this->actionAddWorkerParameter($worker_object_id, $parameter_id, $parameter_type_id);
                    if ($worker_parameter == -1)
                        $errors[] = "не удалось сохранить параметр";
                    $response = $this->buildWorkerParameterArrayNew($worker_object_id);
                    if ($response['status'] == 1) {
                        $parametersArray = $response['Items'];
                    } else {
                        $errors[] = $response['errors'];
                    }
                }
                //Вызвать метод построения структуры данных
            } else {
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";
            $this->redirect(' / ');
        }
        $result = array('errors' => $errors, 'paramArray' => $parametersArray);
        //массив возвращается ajax-запросу в формате json
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    public function actionSearchEmployee()
    {
        $post = Yii::$app->request->post();
        $sql_filter = '';
        $search_query = "";
        $result_array = array();
        if (isset($post['query']) and $post['query'] != '') {
            $search_query = strval($post['query']);
            $sql_filter = "fio like '%" . $search_query . "%' or tabel_number like '%" . $search_query . "%' or 
             company_title like '%" . $search_query . "%' or department_title like '%" . $search_query . "%' or
             position_title like '%" . $search_query . "%'";
        }
        $employees = (new Query())
            ->select([
                'worker_id',
                'worker_object_id',
                'fio',
                'birthdate',
                'position_title',
//                 'company_department_id',
                'company_title',
                'department_title',
                'tabel_number',
                'date_start',
                'date_end',
                'vgk_status'
            ])
            ->from('view_handbook_employee')
            ->where($sql_filter)
            ->orderBy('fio')
            ->all();
        $i = 0;
        foreach ($employees as $worker) {
            $result_array[$i]['worker_id'] = $worker['worker_id'];
            $result_array[$i]['worker_object_id'] = $worker['worker_object_id'];
            $result_array[$i]['birthdate'] = $worker['birthdate'];
            $result_array[$i]['FIO'] = Assistant::MarkSearched($search_query, $worker['FIO']);
            $result_array[$i]['position_title'] = Assistant::MarkSearched($search_query, $worker['position_title']);
            $result_array[$i]['company_title'] = Assistant::MarkSearched($search_query, $worker['company_title']);
            $result_array[$i]['department_title'] = Assistant::MarkSearched($search_query, $worker['department_title']);
            $result_array[$i]['tabel_number'] = Assistant::MarkSearched($search_query, $worker['tabel_number']);
            $result_array[$i]['date_start'] = $worker['date_start'];
            $result_array[$i]['date_end'] = $worker['date_end'];
            $result_array[$i]['vgk_status'] = $worker['vgk_status'];
            $i++;
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_array;
    }

    /*
     * Функция загрузки изображения на сервер и сохранения параметра Фотография для worker'a в БД
                * Входные данные:
     * imageFile(file) - файл с изображением
                * last_name (string)-фамилия сотрудника
                * first_name (string)-имя сотрудника
                * patronymic (string)-отчество сотрудника
                * worker_id (int)-id сотрудника из таблицы worker
                * image_type (string)-расширение изображения
                */
    public function uploadPhoto($worker_object_id, $file, $full_name, $image_type): array
    {
        $errors = array();                                                                                              //объявляем массив для хранения ошибок
        $url = null;                                                                                                    //объявляем переменную для хранения пути изображения для передачи на фронтэнд

        $upload_dir = 'img/miners/';                                                                          //объявляем и инициируем переменную для хранения пути к папке с изображениями
        $upload_bd_dir = '/img/miners/';                                                                          //объявляем и инициируем переменную для хранения пути к папке с изображениями
        $uploaded_file = $upload_dir . $full_name . '_' .                              //объявляем и инициируем переменную для хранения названия файла, состоящего из
            date('d-m-Y H-i') . '.' . $image_type;                                                  //фамилии, имени, отчества (если есть), даты и времени загрузки изображения, расширения изображения
        $uploaded_bd_file = $upload_bd_dir . $full_name . '_' .                              //путь для базы данных абсолютный //TODO 03.04.2020 Deus: Необходимость этой переменной нужна чтобы в бд был путь через "/img/miners"
            date('d-m-Y H-i') . '.' . $image_type;


        if (move_uploaded_file($file['tmp_name'], $uploaded_file))                                                  //если удалось сохранить переданный файл в указанную директорию
        {


            $workerPhotoParameter = WorkerParameter::findOne(['worker_object_id' => $worker_object_id,         //ищем параметр Фотография у данного worker_object'a
                'parameter_id' => 3, 'parameter_type_id' => 1]);
            if ($workerPhotoParameter) {                                                             //если есть такой параметр
                $workerHandbookValue = WorkerParameterHandbookValue::find()//ищем значение этого параметра
                ->where(['worker_parameter_id' => $workerPhotoParameter->id])
                    ->orderBy(['date_time' => SORT_DESC])
                    ->one();
                if (isset($workerHandbookValue)) {                                                              //если такое значение есть
                    if ($workerHandbookValue->value != $uploaded_file) {                                        //если найденное значение не совпадает с создаваемым
                        // то создаем новую запись в таблице WorkerHandbookParameterValue
                        $workerNewHandbookValueFlag = $this->actionAddWorkerParameterHandbookValue($workerPhotoParameter->id, $uploaded_bd_file, 1, date('Y-m-d H:i:s'));
                        if ($workerNewHandbookValueFlag == -1) {                                                //если флаг выполнения функции добавления записи в таблицу равен -1
                            $errors[] = 'не удалось сохранить справочное значение';                             //сохраняем ошибку в массиве ошибок
                        }
                        //иначе ищем созданную запись
                        $workerNewHandbookValue = WorkerParameterHandbookValue::find()->where(['worker_parameter_id' => $workerPhotoParameter->id])->orderBy(['date_time' => SORT_DESC])->one();
                        $url = $workerNewHandbookValue->value;                                                  //записываем в переменную $url значение пути до изображения
                    }
                } else {                                                                                        //если у сотрудника не было еще загружено ни одного фото
                    // то создаем запись в таблице WorkerHandbookParameterValue
                    $workerNewHandbookValueFlag = $this->actionAddWorkerParameterHandbookValue($workerPhotoParameter->id, $uploaded_bd_file, 1, date('Y-m-d H:i:s'));
                    if ($workerNewHandbookValueFlag == -1) {                                                    //если флаг выполнения функции добавления записи в таблицу равен -1
                        $errors[] = "не удалось сохранить новое справочное значение (первое для этого worker'a)";//сохраняем ошибку в массиве ошибок
                    }
                    //иначе ищем созданную запись
                    $workerNewHandbookValue = WorkerParameterHandbookValue::findOne(['worker_parameter_id' => $workerPhotoParameter->id]);
                    $url = $workerNewHandbookValue->value;                                                      //записываем в переменную $url значение пути до изображения
                }
            } else {                                                                                            //иначе если такого параметра нет
                //то создаем новую запись в таблице WorkerParameter
                $workerNewPhotoParameterFlag = $this->actionAddWorkerParameter($worker_object_id, 3, 1);

                if ($workerNewPhotoParameterFlag == -1) {                                                       //если флаг выполнения функции добавления записи в таблицу равен -1
                    $errors[] = 'не удалось сохранить новый параметр';                                          //сохраняем ошибку в массиве ошибок
                }
                //иначе ищем созданную запись
                $workerNewPhotoParameter = WorkerParameter::findOne(['id' => $workerNewPhotoParameterFlag]);
                //сохраняем значение этого параметра в таблице WorkerParameterHandbookValue
                $workerNewPhotoParameterHandbookValueFlag = $this->actionAddWorkerParameterHandbookValue($workerNewPhotoParameter->id, $uploaded_bd_file, 1, date('Y-m-d H:i:s'));
                if ($workerNewPhotoParameterHandbookValueFlag == -1) {                                          //если флаг выполнения функции добавления записи в таблицу равен -1
                    $errors[] = 'не удалось сохранить значение нового параметра';                               //сохраняем ошибку в массиве ошибок
                }
                $workerNewPhotoParameterHandbookValue = WorkerParameterHandbookValue::findOne(['worker_parameter_id' => $workerNewPhotoParameter->id]);
                $url = $workerNewPhotoParameterHandbookValue->value;                                            //записываем в переменную $url значение пути до изображения
            }

        } else {
            $errors[] = "не удалось сохранить файл\n";                                                              //сохраняем ошибку в массиве ошибок
        }

        return array('errors' => $errors, 'url' => $url);                                                                                      //возвращаем на фронтэнд сериализованный получившийся массив
    }

    /**
     * Название метода: actionDeleteWorkerParameter()
     * @package app\controllers
     * Метод удаление параметров воркера.
     * Входные обязательные параметры:
     * -- $post['action_type'] - тип действия. Принимает local и global. Случаи использования:
     * 1. Если указать local, то удаляет конкретных тип параметр со значением из БД и из кэша.
     * 2. Если указать global, то удаляет у этого параметра все значения и потом удаляет этот же параметр у конкретного
     * работника
     * -- $post['worker_object_id'] - идентификатор объекта работника, то есть worker_object_id
     * -- $post['parameter_id'] - идентификатор параметра
     * Входные необязательные параметры
     *
     * @url http://localhost/handbook-employee/delete-worker-parameter?
     * @url http://localhost/handbook-employee/delete-worker-parameter?parameter_id=318&action_type=global&specific_parameter_id=&worker_object_id=14010
     * @url http://localhost/handbook-employee/delete-worker-parameter?parameter_id=&action_type=local&specific_parameter_id=23298&worker_object_id=14010
     *
     * Документация на портале:
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 20.01.2019 16:49
     * @since ver2.0
     */
    public function actionDeleteWorkerParameter()
    {
        $errors = array();
        $warnings = array();
        $paramsArray = array();
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 27)) {                                               //если пользователю разрешен доступ к функции
                $post = Assistant::GetServerMethod(); //получение данных от ajax-запроса
                if (isset($post['action_type']) && $post['action_type'] != "" and isset($post['worker_object_id']) and $post['worker_object_id'] != "") {
                    $actionType = $post['action_type'];
                    $worker_object_id = $post['worker_object_id'];
                    /**
                     * Определяем worker_id по worker_object_id
                     */
                    $worker_object = WorkerObject::findOne(['id' => $worker_object_id]);
                    if ($worker_object) {
                        $worker_id = $worker_object->worker_id;
                    } else {
                        throw new Exception(" WorkerObject::findOne. Указанного работника не в БД");
                    }
                    $workerCacheController = new WorkerCacheController();
                    /** Удаление конкретного тип параметра + параметр у конкретного работника */
                    if ($actionType == "local") {
                        if (isset($post['specific_parameter_id']) and $post['specific_parameter_id'] != "") {
                            $specificParameterId = $post['specific_parameter_id'];
                            $parameter = (new Query())
                                ->select([
                                    'worker_id',
                                    'parameter_id',
                                    'parameter_type_id'
                                ])
                                ->from('worker_object')
                                ->join('JOIN', 'worker_parameter', 'worker_parameter.worker_object_id = worker_object.id')
                                ->where('worker_parameter.id = ' . $specificParameterId)
                                ->one();


                            WorkerParameterSensor::deleteAll(['worker_parameter_id' => $specificParameterId]);
                            WorkerParameterHandbookValue::deleteAll(['worker_parameter_id' => $specificParameterId]);
                            WorkerParameterValue::deleteAll(['worker_parameter_id' => $specificParameterId]);
                            WorkerParameter::deleteAll(['id' => $specificParameterId]);
                            $response = $this->buildWorkerParameterArrayNew($worker_object_id);
                            if ($response['status'] == 1) {
                                $paramsArray = $response['Items'];
                            } else {
                                $errors[] = $response['errors'];
                            }
                            /**
                             * Удаление конкретный параметр работника по конкретному ИД типа параметра из кэша
                             */
                            if ($parameter) {
                                $parameter_id = $parameter['parameter_id'];
                                $worker_cache_del = $workerCacheController->delParameterValueHash($worker_id, $parameter['parameter_id'], $parameter['parameter_type_id']);
                                $errors = array_merge($errors, $worker_cache_del['errors']);
                                $warnings[] = $worker_cache_del['warnings'];
                                if ($parameter_id == 346) {
                                    $workerCacheController->delWorkerMineHash($worker_id);
                                }
                            }

                        } else {
                            $errors[] = "не передан параметр specific_parameter_id=" . $post['specific_parameter_id'];
                        }
                    } /*************  Удаляем параметр и все типы значений у этого параметра по конкретному воркеру *****/
                    else {
                        /**
                         * Удаление конкретного параметра воркера со всеми значениями
                         */
                        if (isset($post['parameter_id']) and $post['parameter_id'] != "") {
                            $parameterId = $post['parameter_id'];
//                            $parameters = (new Query())//найти удаляемые параметры в БД
//                            ->select(['worker_id', 'type_parameter_parameter_id'])
//                                ->from('view_worker_checkIn_parameter_value_main')
//                                ->where("worker_object_id = $worker_object_id")
//                                ->andWhere("parameter_id = $parameterId")
//                                ->all();
//                            $worker_object = (new Query())->select('worker_id')->from('worker_object')->where(['id' => $worker_object_id])->one();


                            $parameters = WorkerParameter::find()->where(['parameter_id' => $parameterId])->all();
                            foreach ($parameters as $parameter) {
                                WorkerParameterSensor::deleteAll(['worker_parameter_id' => $parameter->id]);
                                WorkerParameterHandbookValue::deleteAll(['worker_parameter_id' => $parameter->id]);
                                WorkerParameterValue::deleteAll(['worker_parameter_id' => $parameter->id]);
                            }

                            WorkerParameter::deleteAll(['worker_object_id' => $worker_object_id, 'parameter_id' => $parameterId]);
                            $response = $this->buildWorkerParameterArrayNew($worker_object_id);
                            if ($response['status'] == 1) {
                                $paramsArray = $response['Items'];
                            } else {
                                $errors[] = $response['errors'];
                            }
                            /**
                             * Удаление параметра работника со всеми типами параметров из кэша
                             */
                            $worker_cache_del = $workerCacheController->delParameterValueHash($worker_id, $parameterId);
                            $errors = array_merge($errors, $worker_cache_del['errors']);
                            $warnings[] = $worker_cache_del['warnings'];

                        } else {
                            $errors[] = "не передан id параметра";
                        }
                    }
                } else {
                    $errors[] = "не передан тип удаления параметра action_type = " . $post['action_type'] . ", worker_object_id = " . $post['worker_object_id'];
                }
            } else {
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";
            $this->redirect('/');
        }
        $result = array('paramArray' => $paramsArray, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    //сохранение справочного значения конкретного параметра работника
    public function actionAddWorkerParameterHandbookValue($worker_parameter_id, $value, $status_id, $date_time): int
    {
        $worker_parameter_handbook_value = new WorkerParameterHandbookValue();
        $worker_parameter_handbook_value->worker_parameter_id = $worker_parameter_id;
        if ($date_time == 1) {
            $worker_parameter_handbook_value->date_time = date('Y-m-d H:i:s', strtotime('-1 second'));
        } else {
            $worker_parameter_handbook_value->date_time = $date_time;
            $worker_parameter_handbook_value->value = (string)$value;
            $worker_parameter_handbook_value->status_id = $status_id;
        }

        if (!$worker_parameter_handbook_value->save()) {
            return (-1);
        }

// Якимов М.Н.: закомментировал по причине неверности подхода, в данном подходе выполняется полное перестроение всего кэша системы всех шахт,
        // а должен добавляется только лишь добавленный работник в кэш
        // методы необходимо использовать другие
//            ScheduleWorkerController::actionGetWorkers();                                               //обновить КЭШ по воркерам
//            ScheduleWorkerController::actionGetWorkersParameters();
        return 1;
    }

    public function actionAddWorkerParameter($worker_object_id, $parameter_id, $parameter_type_id)
    {
        $debug_flag = 0;

        if ($debug_flag == 1)
            echo nl2br("----зашел в функцию создания параметров worker'a  =" . $worker_object_id . "\n");

        //делаем проверку на наличие уже такой связки в базе данных, если нет, то создаем новый, если есть то, возвращаем айди
        if (
            $worker_parameter = WorkerParameter::find()->where
            (
                [
                    'worker_object_id' => $worker_object_id,
                    'parameter_id' => $parameter_id,
                    'parameter_type_id' => $parameter_type_id
                ]
            )->one()
        ) {
            return $worker_parameter->id;
        }

        $worker_parameter_new = new WorkerParameter();
        $worker_parameter_new->worker_object_id = $worker_object_id;                                                                 //айди воркер обджекта
        $worker_parameter_new->parameter_id = $parameter_id;                                                           //айди параметра
        $worker_parameter_new->parameter_type_id = $parameter_type_id;                                                 //айди типа параметра

        if ($worker_parameter_new->save()) {
            $worker_parameter_new->refresh();
            return $worker_parameter_new->id;
        } else
            return (-1); //"Ошибка сохранения значения параметра сопряжения" . $worker_id->id;
    }


    /* Метод редактирования работника
     * Входные данные:
     * - $post['id'] – (int) идентификатор работника
     * - $post['first_name'] — (string) имя работника
     * - $post['last_name'] — (string) фамилия работника
     * - $post['patronymic'] – (string) отчество работника
     * - $post['gender'] – (char/string) пол работника (м/ж)
     * - $post['work_mode'] – (int) идентификатор режима работы
     * - $post['birth_date'] – (date) дата рождения
     * - $post['date_start'] – (date) дата начала работы
     * - $post['date_end'] – (date) дата окончания работы
     * - $post['type_obj'] – (int) идентификатор типа работы
     * - $post['height'] – (int) рост работника
     * - $post['company'] – (int) идентификатор предприятия
     * - $post['department'] – (int) идентификатор подразделения
     * - $post['position'] – (int) идентификатор должности
     * - $post['photo'] – (string) адрес фотографии
     * - $post['tabel_number'] – (string) табельный номер работника
     * - $post['pass_number'] – (string) номер пропуска работника
     * Выходные параметры: результат выполнения метода buildArray в формате json
     */
    public function actionEditWorker()
    {
        $log = new LogAmicumFront("actionEditWorker");

        $arrMines = array();
        $arrWorkers = array();
        $properties = array();

        try {
            $log->addLog("Начало выполнения метода");
            $session = Yii::$app->session;                                                                              // старт сессии
            $worker_object = null;
            $session->open();
            if (!isset($session['sessionLogin'])) {                                                                     // если в сессии есть логин
                $this->redirect('/');
                throw new Exception("Время сессии закончилось. Требуется повторный ввод пароля");
            }

            if (!AccessCheck::checkAccess($session['sessionLogin'], 23)) {                                      // если пользователю разрешен доступ к функции
                throw new Exception("Недостаточно прав для совершения данной операции");
            }

            $post = Yii::$app->request->post(); //получение данных от ajax-запроса

            $worker_id = $post['id'];
            $worker = Worker::findOne(['id' => $worker_id]);

            //Если работник есть
            if (!$worker) {
                throw new Exception("Редактируемого работника $worker_id нет в БД ");
            }

            $employee = $worker->employee;                                                                              // Выбрать привязанного к нему человека (Employee)

            if (isset($post['first_name'])) {                                                                           // Если задано имя, изменить его
                $employee->first_name = $post['first_name'];
            }

            if (isset($post['last_name'])) {                                                                            // Если задана фамилия, изменить ее
                $employee->last_name = $post['last_name'];
            }

            if (isset($post['patronymic'])) {                                                                           // Если задано отчество, изменить его
                $employee->patronymic = $post['patronymic'];
            }

            if (isset($post['birth_date']) and $post['birth_date'] != "") {                                             // Если задана дата рождения, изменить ее
                $employee->birthdate = date("Y-m-d H:i:s", strtotime($post['birth_date']));
            }

            if (isset($post['gender']) and $post['gender'] != "") {                                                     // Если задан пол, изменить его
                $employee->gender = $post['gender'];
            }
            if (!$employee->save()) {
                $log->addData($employee->errors, '$employee->errors', __LINE__);
                throw new Exception("Не удалось сохранить employee");
            }

            $log->addLog("Дошел до сохранения файла");

            $file = isset($_FILES['imageFile']) ? $_FILES['imageFile'] : null;                                          // -----
            $file_name = isset($post['imageName']) ? explode('.', $post['imageName']) : null;                   // -----
            $file_extension = isset($file_name) ? $file_name[count($file_name) - 1] : null;

            if (isset($post['work_mode']) and $post['work_mode'] != "") {                                               // Если задан идентификатор режима работы
                $plan_shift = PlanShift::findOne($post['work_mode']);                                                   // Если такой режим работы существует
                if ($plan_shift) {
                    $shift_worker = new ShiftWorker();
                    $shift_worker->date_time = date('Y-m-d H:i:s');
                    $shift_worker->plan_shift_id = $post['work_mode'];
                    $shift_worker->worker_id = $worker->id;
                    if (!$shift_worker->save()) {
                        $log->addData($shift_worker->errors, '$shift_worker->errors', __LINE__);
                        throw new Exception("Не удалось сохранить shift_worker");
                    }
                }
            }

            $log->addLog("Сохранил смену работник");

            if (isset($post['type_obj']) and $post['type_obj'] != "") {                                                 // Если задан идентификатор типа работника
                $log->addLog("Начинаю получать типовой объект.");

                $object = TypicalObject::findOne(['title' => $post['type_obj']]);                                       // Получить класс рабочего
                $worker_object = WorkerObject::findOne(['worker_id' => $worker->id]);
                if (!$worker_object) {
                    $worker_object = new WorkerObject();
                    $maxWorkerObject = WorkerObject::find()->max('id');
                    $worker_object->id = $maxWorkerObject ? $maxWorkerObject + 1 : 1;                                   // Привязать идентификатор типового объекта (тип работы)
                }
                $worker_object->object_id = $object->id;

                $log->addLog("Уложил для сохранения типовой объект работника в воркер обджект");

                $worker_object->worker_id = $worker->id;                                                                // Привязать id работника
                if (isset($post['role_id']) and $post['role_id'] and $post['role_id'] != "") {                          // если передали роль работника
                    $worker_object->role_id = $post['role_id'];                                                         // записываем его роль
                } else {                                                                                                // иначе пишем что роль Прочее
                    $worker_object->role_id = 1;
                }

                if (!$worker_object->save()) {
                    $log->addData($worker_object->errors, '$worker_object->errors', __LINE__);
                    throw new Exception("Ошибка сохранения модели WorkerObject");
                }
            }

            if (isset($post['tabel_number']) and $post['tabel_number'] != "") {
                $worker->tabel_number = $post['tabel_number'];
            }

            if (isset($post['date_start']) and $post['date_start'] != "") {
                $worker->date_start = date("Y-m-d H:i:s", strtotime($post['date_start']));
            }

            if (isset($post['date_end']) and $post['date_end'] != "") {
                $worker->date_end = date("Y-m-d H:i:s", strtotime($post['date_end']));
            }

            if (isset($post['vgk_status']) and $post['vgk_status'] != "") {
                $worker->vgk = $post['vgk_status'];
            }

            $company_id = $worker->companyDepartment->company_id;
            $department_id = $worker->companyDepartment->department_id;

            if (isset($post['company']) and $post['company'] != "") {
                $company = Company::findOne($post['company']);
                if (!$company) {
                    throw new Exception("Подразделение работника не найдено");
                }
                $company_id = $company->id;
            }

            $companyWorkMode = ShiftMine::findOne(['company_id' => $company_id]);
            if ($companyWorkMode) {
                $plan_shift_id = $companyWorkMode->plan_shift_id;
            } else {
                $plan_shift_id = 3;
            }

            if (isset($post['department']) and $post['department'] != "") {
                $department = Department::findOne($post['department']);
                if ($department) {
                    $department_id = $department->id;
                }
            }

            if (isset($post['department_type_id']) and $post['department_type_id'] != "") {
                $department_type_id = $post['department_type_id'];
            } else {
                $department_type_id = DepartmentTypeEnum::OTHER;
            }

            $company_department = CompanyDepartment::find()
                ->where([
                    'company_id' => $company_id,
                    'department_id' => $department_id,
                ])
                ->one();

            if (!$company_department) {
                $company_department = new CompanyDepartment();
                $company_department->company_id = $company_id;
                $company_department->department_id = $department_id;
                $company_department->department_type_id = $department_type_id;
                if (!$company_department->save()) {
                    $log->addData($company_department->errors, '$company_department->errors', __LINE__);
                    throw new Exception("Не удалось сохранить CompanyDepartment");
                }

                $workMode = new ShiftDepartment();
                $workMode->company_department_id = $company_department->id;
                $workMode->plan_shift_id = $plan_shift_id;
                $workMode->date_time = date("Y-m-d H:i:s");
                if (!$workMode->save()) {
                    $log->addData($workMode->errors, '$workMode->errors', __LINE__);
                    throw new Exception("Не удалось сохранить ShiftDepartment");
                }
            }

            $worker->company_department_id = $company_department->id;

            if (isset($post['position']) and $post['position'] != "") {
                $worker->position_id = $post['position'];
            }

            if (!$worker->save()) {
                $log->addData($worker->errors, '$worker->errors', __LINE__);
                throw new Exception("Не удалось сохранить worker");
            }

            if (isset($post['height']) and $post['height'] != "") {
                $this->actionAddWorkerParameter($worker_object->id, 1, 1);
                $this->saveWorkerParameterHandbookValue($worker_object, 1, $post['height']);                 //параметр рост = id=1

            }
            //если задана фотография
//                    if(isset($post['imageName']) and $post['imageName']!=""){
//                        //вызвать функцию сохранения значения справочного параметра
//                        $this->saveWorkerParameterHandbookValue($worker_object,3,$file_extension);                    //параметр фотография = id=3
//                    }

            $full_name = $employee->last_name . '_' . $employee->first_name . (isset($employee->patronymic) ? ('_' . $employee->patronymic) : '');
            if (isset($file)) {
                $this->uploadPhoto($worker_object->id, $file, $full_name, $file_extension);
            }

            //Если задан номер пропуска
            if (isset($post['pass_number']) and $post['pass_number'] != "") {
                $this->actionAddWorkerParameter($worker_object->id, 2, 1);
                $this->saveWorkerParameterHandbookValue($worker_object, 2, $post['pass_number']);            // параметр номер пропуска = id=2

            }
            if (isset($post['tabel_number']) and $post['tabel_number'] != "") {
                $this->actionAddWorkerParameter($worker_object->id, 392, 1);
                $this->saveWorkerParameterHandbookValue($worker_object, 392, $post['tabel_number']);         // параметр табельный номер = id=392
            }

            /**
             * Блок переноса сенсора в новую шахту если таковое требуется
             * если шахта есть, то делаем перенос или инициализацию, в зависимости от описанного выше
             */
            $response = (new WorkerCacheController())->getParameterValueHash($worker_id, 158, 2);
            if ($response) {
                $checkin = $response['value'];
            } else {
                $checkin = false;
            }

            $response = (new WorkerCacheController())->getParameterValueHash($worker_id, 346, 2);
            if ($response) {
                $mine_id = $response['value'];
            } else {
                $mine_id = false;
            }

            if ($mine_id and $checkin == 1) {
                $workers = (new Query())
                    ->select(
                        [
                            'position_title',
                            'department_title',
                            'first_name',
                            'last_name',
                            'patronymic',
                            'gender',
                            'stuff_number',
                            'worker_object_id',
                            'worker_id',
                            'object_id',
                            'mine_id',
                            'checkin_status'
                        ])
                    ->from(['view_initWorkerMineCheckin'])
                    ->where(['mine_id' => $mine_id, 'worker_id' => $worker_id])
                    ->one();
                if ($workers) {
                    $worker_to_cache = WorkerCacheController::buildStructureWorker(
                        $worker_id,
                        $workers['worker_object_id'],
                        $workers['object_id'],
                        $workers['stuff_number'],
                        $workers['last_name'] . " " . $workers['first_name'] . " " . $workers['patronymic'],
                        $workers['mine_id'],
                        $workers['position_title'],
                        $workers['department_title'],
                        $workers['gender']);
                    $ask_from_method = WorkerMainController::AddMoveWorkerMineInitDB($worker_to_cache);
                    if ($ask_from_method['status'] != 1) {
                        throw new Exception(" WorkerMainController::AddMoveWorkerMineInitDB. Ошибка добавления" . $worker_id);
                    }
                }
            }

            // строится массив данных с учетом добавленного предприятия
            // Вызвать метод построения структуры данных
            $arrMines = $this->GetCompanyDepartment();
            $arrWorkers = $this->buildEmployeeArray("", $post['company'], isset($company_department->id) ? $company_department->id : $post['companyDepartment']);
            $response = $this->buildWorkerParameterArrayNew($worker_object->id);

            if ($response['status'] == 1) {
                $properties = $response['Items'];
            } else {
                $log->addLogAll($response);
            }

            HandbookCachedController::clearWorkerCache();

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['workers' => $arrWorkers, 'mines' => $arrMines, 'params' => $properties], $log->getLogAll());
    }

    /**
     * Функция построения массива данных о сотрудниках с поиском
     * @param string $search
     * @param int $companyId
     * @param int|null $companyDepartmentId
     * @return array
     */
    public function buildEmployeeArray(string $search, int $companyId, int $companyDepartmentId = null): array
    {
        $workers = array();
        $sql_filter2 = "";
        $company = Company::findOne($companyId);
        if ($company) {
            if ($companyDepartmentId) {
                $sql_filter = "company_department_id = " . $companyDepartmentId;
            } else {
                $sql_filter = "company_id = " . $companyId;
            }
            if ($search) {
                $search_like = 'LIKE "%' . $search . '%"';
                $sql_filter2 .= 'table_number ' . $search . ' OR last_name ' . $search_like . ' OR birthdate = ' . $search . ' 
                OR gender ' . $search_like . ' OR position_title = ' . $search_like . ' OR titleCompany ' . $search_like . ' OR 
                titleDepartment ' . $search_like . ' OR date_start = ' . $search . ' OR date_end = ' . $search;
            }
            $department = (new Query())
                ->select
                (
                    'employee_id, 
                    table_number, 
                    last_name, 
                    birthdate, 
                    gender, 
                    worker_id,
                    tabel_number,
                    position_id, 
                    position_title, 
                    company_department_id, 
                    company_id, 
                    titleCompany, 
                    department_id, 
                    titleDepartment, 
                    department_type_id, 
                    department_type_title, 
                    date_start, date_end, 
                    dep_id, 
                    shift_worker_id, 
                    worker_object_id'
                )
                ->from('view_worker_datas')
                ->where($sql_filter)
                ->andWhere($sql_filter2)
                ->all();
            //var_dump($department);
            if ($department) {
                foreach ($department as $worker) {
                    $worker_id = $worker['worker_id'];
                    //сохраняются ФИО, дата рождения и пол человека
                    $workers[$worker_id]['id'] = $worker_id;
                    $workers[$worker_id]['worker_object_id'] = $worker['worker_object_id'];
                    $fio = explode(' ', $worker['last_name'], 3);

                    $last_name = "";
                    if (isset($fio[0])) {
                        $last_name = $fio[0];
                    }

                    $first_name = "";
                    if (isset($fio[1])) {
                        $first_name = $fio[1];
                    }

                    $patronymic = "";
                    if (isset($fio[2])) {
                        $patronymic = $fio[2];
                    }

                    $workers[$worker_id]['last_name'] = $last_name;
                    $workers[$worker_id]['first_name'] = $first_name;
                    $workers[$worker_id]['patronymic'] = $patronymic;
                    $workers[$worker_id]['FIO'] = $last_name . ' ' . $first_name . ' ' . $patronymic;

                    $workers[$worker_id]['gender'] = $worker['gender'];
                    //Сохраняется табельный номер работника
                    $workers[$worker_id]['tabel_number'] = $worker['tabel_number'];
                    //выбирается и сохраняется должность работника
                    $workers[$worker_id]['positionId'] = $worker['position_id'];
                    $workers[$worker_id]['position_title'] = $worker['position_title'];
                    //выбирается дата начала работы работника на текущей должности в текущем подразделении предприятия
                    $workers[$worker_id]['date_start'] = date("d.m.Y", strtotime($worker['date_start']));
                    //выбирается дата окончания работы работника на текущей должности в текущем подразделении предприятия
                    $workers[$worker_id]['date_end'] = date("d.m.Y", strtotime($worker['date_end']));
                    $workers[$worker_id]['birthdate'] = date("d.m.Y", strtotime($worker['birthdate']));

                    $worker_work_mode = (new Query())
                        ->select('plan_shift_id')
                        ->from('shift_worker')
                        ->where('worker_id = ' . $worker_id)
                        ->orderBy(['date_time' => SORT_DESC])
                        ->one();
                    if ($worker_work_mode)
                        $workers[$worker_id]['work_mode'] = $worker_work_mode['plan_shift_id'];
                    $workers[$worker_id]['companyId'] = $worker['company_id'];
                    $workers[$worker_id]['company_title'] = $worker['titleCompany'];
                    $workers[$worker_id]['companyDepartmentId'] = $worker['company_department_id'];
                    $workers[$worker_id]['department_type_id'] = $worker['department_type_id'];
                    $workers[$worker_id]['department_type_title'] = $worker['department_type_title'];
                    $workers[$worker_id]['departmentId'] = $worker['department_id'];
                    $workers[$worker_id]['department_title'] = $worker['titleDepartment'];
                    $additional_arr = $this->showWorker($worker_id);

                    if (isset($additional_arr['type_obj'])) {
                        $workers[$worker_id]['type_obj'] = $additional_arr['type_obj'];
                        if (isset($additional_arr['photo'])) {
                            $workers[$worker_id]['photo'] = $additional_arr['photo'];
                        } else {
                            $workers[$worker_id]['photo'] = "";
                        }
                        if (isset($additional_arr['pass_number'])) {
                            $workers[$worker_id]['pass_number'] = $additional_arr['pass_number'];
                        } else {
                            $workers[$worker_id]['pass_number'] = "";
                        }
                        if (isset($additional_arr['height'])) {
                            $workers[$worker_id]['height'] = $additional_arr['height'];
                        } else {
                            $workers[$worker_id]['height'] = "";
                        }
                    }
                }
            }
//            if($company->companies && !$companyDepartmentId){
//                foreach ($company->companies as $subcompany){
//                    $new_workers = $this->buildEmployeeArray($search,$subcompany->id);
//                    $workers = array_merge($workers, $new_workers);
//                }
//            }
        }
        return array_merge($workers);
    }

    /* метод отправки данных о работнике
     * Входные параметры:
     * - $post['worker'] - (int) идентификатор работника
     * Выходные параметры:
     * - $worker (array) - ассоциативный массив с информацией о работнике
     * |-- $worker['type_obj'] — int: тип работы работника (подземная/поверхностная работа) (id)
     * |-- $worker['height'] — int: рост работника, см
     * |-- $worker['photo'] — string: адрес фотографии на сервере
     * |-- $worker['pass_number'] — string: номер пропуска
     * |-- $worker['work_mode'] — int: режим работы работника (id)
     */
    public function showWorker($id): array
    {
        //получение данных от ajax
//        $post = Yii::$app->request->post();
//        $worker = Worker::findOne($post['worker']);
        $worker = Worker::findOne($id);
        $worker_info = array();
//        $worker_work_mode = $worker->getLastShiftWorker();                                                              //выборка последнего заданного режима работы работника
//        //привязка режима работы к работнику
//        if(isset($worker_work_mode[0]['plan_shift_id'])) {
//            $worker_info['work_mode'] = $worker_work_mode[0]['plan_shift_id'];
//        }
        //var_dump($worker->workerObjects);
        //определение типа работника (подземный/поверхностный)
        if (isset($worker->workerObjects[0])) {
            $worker_object = $worker->workerObjects[0];

            $worker_info['department'] = $worker->companyDepartment->department_id;
            if ($worker_object) {
                /*foreach ($worker_objects as $wo) {
                    $worker_object = $wo;
                    break;
                }*/
                // $worker_object = $worker_objects
                //привязка типа работника к работнику
                $worker_info['type_obj'] = $worker_object->object->title;
                //echo $worker_info['type_obj'];
                //определение параметров работника (рост, номер пропуска, фотография)
                $parameters[] = array('id' => 1, 'title' => 'Рост');
                $parameters[] = array('id' => 2, 'title' => 'Номер пропуска');
                $parameters[] = array('id' => 3, 'title' => 'Фотография');
                if ($parameters) {
                    //для каждого параметра
                    foreach ($parameters as $parameter) {
                        $worker_parameter = WorkerParameter::findOne(
                            ['parameter_id' => $parameter['id'], 'worker_object_id' => $worker_object->id]
                        );

                        //$worker_parameter->
                        if ($worker_parameter) {
                            switch ($parameter['title']) {
                                //если параметр - 'рост'
                                case 'Рост':
                                    //выбрать и сохранить в массив значение параметра 'рост'
                                    $height = $worker_parameter->getWorkerParameterHandbookValues()
                                        ->orderBy(['date_time' => SORT_DESC])->one();
                                    if (isset($height->value) && $height->value != "empty") {
                                        $worker_info['height'] = $height->value;
                                    } else {
                                        $worker_info['height'] = "";
                                    }
                                    break;
                                //если параметр - 'Номер пропуска'
                                case 'Номер пропуска':
                                    //выбрать и сохранить в массив значение параметра 'Номер пропуска'
                                    $pass_number = $worker_parameter->getWorkerParameterHandbookValues()
                                        ->orderBy(['date_time' => SORT_DESC])->one();
                                    if (isset($pass_number->value) && $pass_number->value != "empty") {
                                        $worker_info['pass_number'] = $pass_number->value;
                                    } else {
                                        $worker_info['pass_number'] = "";
                                    }
                                    break;
                                //если параметр - 'Фотография'
                                case 'Фотография':
                                    //выбрать и сохранить в массив значение параметра 'Фотография'
                                    $photo = $worker_parameter->getWorkerParameterHandbookValues()
                                        ->orderBy(['date_time' => SORT_DESC])->one();
                                    if (isset($photo->value) && $photo->value != "empty") {
                                        $worker_info['photo'] = $photo->value;
                                    } else {
                                        $worker_info['photo'] = "";
                                    }

                                    break;
                            }
                        }
                    }
                }
            }
        }
        return $worker_info;
    }

    public function saveWorkerParameterHandbookValue($worker_object, $parameter_id, $val)
    {

//        var_dump($param_title);
//        var_dump($val);
        //создать новую запись в таблице значений справочных параметров
        $value = new WorkerParameterHandbookValue();
        //привязать параметр из справочника

        $worker_parameter = WorkerParameter::findOne([
            'parameter_id' => $parameter_id,
            'parameter_type_id' => 1,
            'worker_object_id' => $worker_object->id,
        ]);
        //var_dump($worker_parameter);
        if (isset($worker_parameter->id)) {
            $value->worker_parameter_id = $worker_parameter->id;
            //сохранить значение параметра и текущую дату, статус — актуально
            $value->date_time = date("Y-m-d H:i:s");
            $value->status_id = 1;
            $value->value = $val;
            if (!$value->save()) {
                echo "не удалось сохранить справочное значение воркера";
            }
        }
    }

    public function actionSendArraysForEditingDepartment()
    {
        $department_types = DepartmentType::find()
            ->select(['title', 'id'])
            ->orderBy(['title' => SORT_ASC])
            ->asArray()
            ->all();
        $plan_shifts = PlanShift::find()
            ->select(['title', 'id'])
            ->orderBy(['title' => SORT_ASC])
            ->asArray()
            ->all();
        $result = array('department_types' => $department_types, 'workmodes' => $plan_shifts);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    //функция получения функций воркера
    public function buildWorkerFunctionArray($worker_object_id): array
    {
        $object_functions = array();
        if ($worker_object_id != '') {
            $objects = (new Query())
                ->select(
                    [
                        'function_type_title functionTypeTitle',
                        'function_type_id functionTypeId',
                        'worker_function_id id',
                        'function_id',
                        'worker_object_id',
                        'func_title functionTitle',
                        'func_script_name scriptName'
                    ])
                ->from(['view_worker_function'])
                ->where('worker_object_id = ' . $worker_object_id)
                ->orderBy('function_type_id')
                ->all();

            $i = -1;
            $j = 0;

            foreach ($objects as $object) {
                if ($i == -1 || $object_functions[$i]['id'] != $object['functionTypeId']) {
                    $i++;
                    $object_functions[$i]['id'] = $object['functionTypeId'];
                    $object_functions[$i]['title'] = $object['functionTypeTitle'];
                    $j = 0;
                }
                $object_functions[$i]['funcs'][$j]['id'] = $object['id'];
                $object_functions[$i]['funcs'][$j]['title'] = $object['functionTitle'];
                $object_functions[$i]['funcs'][$j]['script_name'] = $object['scriptName'];
                $j++;

            }

        }
//        $result = array('objectFunctions' => $object_functions);
        return $object_functions;
    }

    /*
    * Функция построения массива параметров конкретных объектов
    * Входные параметры:
    * - $specificObjectId (int) - id конкретного объекта, для которого запрашиваются параметры
    * Выходные параметры:
    * - $paramsArray (array) – массив групп параметров конкретного объекта (по сути вкладок);
    * - $paramsArray[i][“id”] (int) – id вида параметров;
    * - $paramsArray[i][“title”] (string) – наименование вида параметров;
    * - $paramsArray[i]['params'] (array) – массив параметров вида параметров;
    * - $paramsArray[i]['params'][j][“id”] (int) – id параметра;
    * - $paramsArray[i]['params'][j][“title”] (string) – наименование параметра;
    * - $paramsArray[i]['params'][j][“units”] (string) – единица измерения;
    * - $paramsArray[i]['params'][$j]["units_id"] (int) - id единицы измерения
    * - $paramsArray[$i]['params'][$j]['specific'][$k]['id'] (int) - тип(вычисленный/измеренный/справочный) параметра
    * - $paramsArray[$i]['params'][$j]['specific'][$k]['title'] (string) - наименование типа параметра
    * - $paramsArray[$i]['params'][$j]['specific'][$k]['specificObjectParameterId'] (int) - id привязки параметра к конкретному объекту
    * - $paramsArray[$i]['params'][$j]['specific'][$k]['value'] (int) - измеряемое/справочное значение параметра
    */
    public function actionGetWorkerParametersArray()
    {
        $post = Yii::$app->request->post();                                                                             //получение данных от ajax-запроса
        $paramsArray = array();                                                                                         //массив для сохранения параметров         //массив для сохранения функций
        $errors = array();
        $warnings = array();
        if (isset($post['id']) && $post['id'] != '') {
            $specificId = $post['id'];
            $worker = Worker::findOne(['id' => $specificId]);
            if ($worker) {
                $workerObject = WorkerObject::findOne(['worker_id' => $worker->id]);
                if ($workerObject) {
                    $response = $this->buildWorkerParameterArrayNew($workerObject->id);
                    if ($response['status'] == 1) {
                        $paramsArray = $response['Items'];
                    } else {
                        $errors[] = $response['errors'];
                    }
                    $warnings = $response['warnings'];
                } else {
                    $errors[] = 'worker_object не найден';
                }

            } else {
                $errors[] = 'Указанного сотрудника нет в БД';
            }
        } else {
            $errors[] = 'Не передан идентификатор сотрудника';
        }
        ArrayHelper::multisort($paramsArray, 'title');

        $result = array('paramArray' => $paramsArray, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    public function buildWorkerParameterArray($specificObjectId): array
    {
        $paramsArray = array();//массив для сохранения параметров

        $kinds = KindParameter::find()
            ->with('parameters')
            ->with('parameters.unit')
            ->all();
        $i = 0;
        if ($specificObjectId) {//если передан id конкретного объекта
            foreach ($kinds as $kind) {//перебираем все виды параметров
                $paramsArray[$i]['id'] = $kind->id;//сохраняем id вида параметров
                $paramsArray[$i]['title'] = $kind->title;//сохраняем имя вида параметра
                if ($parameters = $kind->parameters) {//если у вида параметра есть параметры
                    $j = 0;
                    foreach ($parameters as $parameter) {//перебираем все параметры
                        if ($specificObjParameters = $parameter->getWorkerParameters()->where(['worker_object_id' => $specificObjectId])->orderBy(['parameter_type_id' => SORT_ASC])->all()) {//если есть типовые параметры переданного объекта
                            $paramsArray[$i]['params'][$j]['id'] = $parameter->id;//сохраняем id параметра
                            $paramsArray[$i]['params'][$j]['title'] = $parameter->title;//сохраняем наименование параметра
                            $paramsArray[$i]['params'][$j]['units'] = $parameter->unit->short;//сохраняем единицу измерения
                            $paramsArray[$i]['params'][$j]['units_id'] = $parameter->unit_id;//сохраняем id единицы измерения
                            $k = 0;
                            foreach ($specificObjParameters as $specificObjParameter) {//перебираем конкретный параметр
                                $paramsArray[$i]['params'][$j]['specific'][$k]['id'] = $specificObjParameter->parameter_type_id;//id типа параметра
                                $paramsArray[$i]['params'][$j]['specific'][$k]['title'] = $specificObjParameter->parameterType->title;//название параметра
                                $paramsArray[$i]['params'][$j]['specific'][$k]['specificObjectParameterId'] = $specificObjParameter->id;//id параметра конкретного объекта
                                switch ($specificObjParameter->parameter_type_id) {
                                    case 1:
                                        if ($value = $specificObjParameter->getWorkerParameterHandbookValues()->orderBy(['date_time' => SORT_DESC])->one()) {
                                            $paramsArray[$i]['params'][$j]['specific'][$k]['value'] = $value->value;//сохраняем справочное значение
                                            if ($parameter->id == 337) {
                                                // echo "зашли в условие для асутп " .(int)$value->value."\n";
                                                $asmtpTitle = $value->value == -1 ? '' : ASMTP::findOne((int)$value->value)->title;
                                                $paramsArray[$i]['params'][$j]['specific'][$k]['asmtpTitle'] = $asmtpTitle;
                                            } else if ($parameter->id == 338) {
//                                                echo "зашли в условие для типов датчика ". $value->value. "\n";
                                                $sensorTypeTitle = $value->value == -1 ? '' : SensorType::findOne((int)$value->value)->title;
                                                $paramsArray[$i]['params'][$j]['specific'][$k]['sensorTypeTitle'] = $sensorTypeTitle;
                                            } else if ($parameter->id == 274) {
//                                                echo "зашли в условие для типов датчика ". $value->value. "\n";
                                                if ($objectTitle = TypicalObject::findOne(['id' => $value->value])) {
                                                    $paramsArray[$i]['params'][$j]['specific'][$k]['objectTitle'] = $objectTitle->title;
                                                }
                                            } else if ($parameter->id == 122) {
                                                if ($placeTitle = Place::findOne($value->value)) {
                                                    $paramsArray[$i]['params'][$j]['specific'][$k]['placeTitle'] = $placeTitle->title;
                                                } else {
                                                    $paramsArray[$i]['params'][$j]['specific'][$k]['placeTitle'] = '';
                                                }
                                            }
                                        }
                                        break;
                                    case 2:
                                        if ($valueFromParameterValue = $specificObjParameter->getWorkerParameterValues()->orderBy(['date_time' => SORT_DESC])->one()) {
                                            $paramsArray[$i]['params'][$j]['specific'][$k]['value'] = $valueFromParameterValue->value;
                                        } else {
                                            $paramsArray[$i]['params'][$j]['specific'][$k]['value'] = '-1';
                                        }

                                        break;
                                    case 3:
                                        if ($valueFromParameterValue = $specificObjParameter->getWorkerParameterValues()->orderBy(['date_time' => SORT_DESC])->one()) {
                                            $paramsArray[$i]['params'][$j]['specific'][$k]['value'] = $valueFromParameterValue->value;
                                        } else {
                                            $paramsArray[$i]['params'][$j]['specific'][$k]['value'] = '-1';
                                        }
                                        $k++;
                                        $paramsArray[$i]['params'][$j]['specific'][$k]['id'] = 5;//id типа параметра
                                        $paramsArray[$i]['params'][$j]['specific'][$k]['title'] = "Привязка датчика";//название параметра
                                        $paramsArray[$i]['params'][$j]['specific'][$k]['specificObjectParameterId'] = $specificObjParameter->id;//id параметра кон
                                        if ($value = $specificObjParameter->getWorkerParameterSensors()->orderBy(['date_time' => SORT_DESC])->one()) {
                                            $paramsArray[$i]['params'][$j]['specific'][$k]['sensor_id'] = $value->sensor_id;//сохраняем измеряемое значение
                                        } else {
                                            $paramsArray[$i]['params'][$j]['specific'][$k]['sensor_id'] = -1;
                                        }

                                        break;
                                }
                                $k++;
                            }
                            $j++;
                        }
                    }
                    ArrayHelper::multisort($paramsArray[$i]['params'], 'title');
                }
                $i++;
            }
        }
        ArrayHelper::multisort($paramsArray, 'title');
        return $paramsArray;
    }

    public function buildWorkerParameterArrayNew($worker_object_id): array
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();

        $warnings[] = 'buildWorkerParameterArrayNew. Начало метода';

        try {
            /**
             * Получение последних справочных параметров сенсора
             */
            $worker_parameter_values = array();
            $worker_parameter_values_handbook = (new Query())
                ->select('*')
                ->from('view_GetWorkerParameterHandbookWithLastValue')
                ->where(['worker_object_id' => $worker_object_id])
                ->all();
            if ($worker_parameter_values_handbook) {
                $worker_parameter_values = array_merge($worker_parameter_values, $worker_parameter_values_handbook);
            }
            $worker_parameter_values_measure = (new Query())
                ->select('*')
                ->from('view_GetWorkerParameterWithLastValue')
                ->where(['worker_object_id' => $worker_object_id])
                ->all();
            if ($worker_parameter_values_measure) {
                $worker_parameter_values = array_merge($worker_parameter_values, $worker_parameter_values_measure);
            }

            //return $sensor_parameter_values;

            foreach ($worker_parameter_values as $epv) {
                $group_worker_parameter[$epv['kind_parameter_id']][$epv['parameter_id']]['parameter_type_id'][] = $epv;
                $group_worker_parameter[$epv['kind_parameter_id']][$epv['parameter_id']]['parameter_id'] = $epv['parameter_id'];
                $group_worker_parameter[$epv['kind_parameter_id']][$epv['parameter_id']]['parameter_title'] = $epv['parameter_title'];
                $group_worker_parameter[$epv['kind_parameter_id']][$epv['parameter_id']]['units'] = $epv['units'];
                $group_worker_parameter[$epv['kind_parameter_id']][$epv['parameter_id']]['units_id'] = $epv['units_id'];
            }


            if (isset($group_worker_parameter)) {
                $worker_parameter_sensor = (new Query())
                    ->select('*')
                    ->from('view_GetWorkerParameterSensorMain')
                    ->where(['worker_object_id' => $worker_object_id])
                    ->indexBy('worker_parameter_id')
                    ->all();
            }

            /**
             * Получение видов параметров
             */
            $kinds = KindParameter::find()->all();
            if (!$kinds) {
                throw new Exception('buildWorkerParameterArrayNew. Нет видов параметров');
            }

            /**
             * Генерация структуры для отправки на фронт
             */
            $kind_parameters = array();
            foreach ($kinds as $kind) {
                $kind_parameters['id'] = $kind->id;
                $kind_parameters['title'] = $kind->title;
                $kind_parameters['params'] = array();
                if (isset($group_worker_parameter[$kind->id])) {
                    $j = 0;
                    foreach ($group_worker_parameter[$kind->id] as $parameter) {
                        $kind_parameters['params'][$j]['id'] = (int)$parameter['parameter_id'];
                        $kind_parameters['params'][$j]['title'] = $parameter['parameter_title'];
                        $kind_parameters['params'][$j]['units'] = $parameter['units'];
                        $kind_parameters['params'][$j]['units_id'] = $parameter['units_id'];
                        $k = 0;
                        foreach ($parameter['parameter_type_id'] as $parameter_type) {//перебираем конкретный параметр
                            $kind_parameters['params'][$j]['specific'][$k]['id'] = (int)$parameter_type['parameter_type_id'];//id типа параметра
                            $kind_parameters['params'][$j]['specific'][$k]['title'] = $parameter_type['parameter_type_title'];//название параметра
                            $kind_parameters['params'][$j]['specific'][$k]['specificObjectParameterId'] = (int)$parameter_type['worker_parameter_id'];//id параметра конкретного объекта
                            $kind_parameters['params'][$j]['specific'][$k]['value'] = $parameter_type['value'];

                            switch ($parameter_type['parameter_type_id']) {
                                case 1:
                                    if ($parameter_type['parameter_id'] == 337) {//название АСУТП

                                        $asmtpTitle = $parameter_type['value'] == -1 ? '' : ASMTP::findOne((int)$parameter_type['value'])->title;
                                        $kind_parameters['params'][$j]['specific'][$k]['asmtpTitle'] = $asmtpTitle;
                                    } else if ($parameter_type['parameter_id'] == 338) {//ТИП сенсора

                                        $sensorTypeTitle = $parameter_type['value'] == -1 ? '' : SensorType::findOne((int)$parameter_type['value'])->title;
                                        $kind_parameters['params'][$j]['specific'][$k]['sensorTypeTitle'] = $sensorTypeTitle;
                                    } else if ($parameter_type['parameter_id'] == 274) {// Типовой объект

                                        if ($objectTitle = TypicalObject::findOne($parameter_type['value'])) {
                                            $kind_parameters['params'][$j]['specific'][$k]['objectTitle'] = $objectTitle->title;
                                        }
                                    } else if ($parameter_type['parameter_id'] == 122) {
                                        if ($placeTitle = Place::findOne($parameter_type['value'])) {// Название места
                                            $kind_parameters['params'][$j]['specific'][$k]['placeTitle'] = $placeTitle->title;
                                        } else {
                                            $kind_parameters['params'][$j]['specific'][$k]['placeTitle'] = '';
                                        }
                                    } else if ($parameter_type['parameter_id'] == 523) {
                                        if ($alarm_group_title = GroupAlarm::findOne($parameter_type['value'])) { // Название группы оповещения
                                            $kind_parameters['params'][$j]['specific'][$k]['alarmGroupTitle'] = $alarm_group_title->title;
                                        } else {
                                            $kind_parameters['params'][$j]['specific'][$k]['alarmGroupTitle'] = '';
                                        }
                                        $warnings[] = "buildWorkerParameterArrayNew. Группа оповещения = " . $parameter_type['value'];
                                    }
                                    break;
                                case 2:
                                    if ($parameter_type['value']) {
                                        $kind_parameters['params'][$j]['specific'][$k]['value'] = $parameter_type['value'];
                                    } else {
                                        $kind_parameters['params'][$j]['specific'][$k]['value'] = '-1';
                                    }
                                    break;
                                case 3:
                                    if ($parameter_type['value']) {
                                        $kind_parameters['params'][$j]['specific'][$k]['value'] = $parameter_type['value'];
                                    } else {
                                        $kind_parameters['params'][$j]['specific'][$k]['value'] = '-1';
                                    }
                                    $k++;
                                    $kind_parameters['params'][$j]['specific'][$k]['id'] = 5;//id типа параметра
                                    $kind_parameters['params'][$j]['specific'][$k]['title'] = 'Привязка датчика';//название параметра
                                    $kind_parameters['params'][$j]['specific'][$k]['specificObjectParameterId'] = $parameter_type['worker_parameter_id'];//id параметра кон
                                    if (isset($worker_parameter_sensor[$parameter_type['worker_parameter_id']]) && $worker_parameter_sensor !== false) {
                                        $kind_parameters['params'][$j]['specific'][$k]['sensor_id'] = $worker_parameter_sensor[$parameter_type['worker_parameter_id']]['sensor_id'];
                                    } else {
                                        $kind_parameters['params'][$j]['specific'][$k]['sensor_id'] = -1;
                                    }
                                    break;
                            }
                            $k++;
                        }
                        $j++;
                    }
                    ArrayHelper::multisort($kind_parameters['params'], 'title', SORT_ASC);
                }
                $result[] = $kind_parameters;
            }

            ArrayHelper::multisort($result, 'title', SORT_ASC);
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'buildWorkerParameterArrayNew. Исключение';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $warnings[] = 'buildWorkerParameterArrayNew. Вышел с метода';
        return array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод получении полной информации об конкретном сотруднике
     * Автор: Одилов О.У.
     */
    /**
     * @param $worker_id
     * @return array
     */
    public function getEmployeeEditInformation($worker_id): array
    {
        $errors = array();
        $employee_info = array();
        if (isset($worker_id) && $worker_id != '') {
            $employee_datas = (new Query())
                ->select([
                    'worker_id',
//                     'employee_id',
                    'parameter_id',
                    'parameter_type_id',
                    'last_name',
                    'first_name',
                    'patronymic',
                    'gender',
                    'worker_height',
                    'birthdate',
                    'date_start',
                    'date_end',
                    'plan_shift_id',
                    'plan_shift_title',
                    'worker_work_type_id',
                    'company_id',
                    'company_title',
                    'company_department_id',
                    'department_id',
                    'department_title',
                    'department_type_id',
                    'department_type_title',
                    'position_title',
                    'position_id',
                    'tabel_number',
                    'worker_pass_number',
                    'photo',
                    'vgk_status'
                ])
                ->from('view_handbook_employee_full')
                ->where(['worker_id' => $worker_id])
                ->all();
            if ($employee_datas) {
                foreach ($employee_datas as $data) {
                    if (!isset($employee_info['worker_id'])) {
                        $employee_info['worker_id'] = (int)$data['worker_id'];
                        $employee_info['last_name'] = $data['last_name'];
                        $employee_info['first_name'] = $data['first_name'];
                        $employee_info['patronymic'] = $data['patronymic'];
                        $employee_info['gender'] = $data['gender'];
                        $employee_info['birthdate'] = $data['birthdate'];
                        $employee_info['date_start'] = $data['date_start'];
                        $employee_info['date_end'] = $data['date_end'];
                        $employee_info['plan_shift_id'] = (int)$data['plan_shift_id'];
                        $employee_info['plan_shift_title'] = $data['plan_shift_title'];
                        $employee_info['worker_work_type_id'] = (int)$data['worker_work_type_id'];
                        $employee_info['company_id'] = (int)$data['company_id'];
                        $employee_info['company_title'] = $data['company_title'];
                        $employee_info['company_department_id'] = (int)$data['company_department_id'];
                        $employee_info['department_id'] = $data['department_id'];
                        $employee_info['department_title'] = $data['department_title'];
                        $employee_info['department_type_id'] = (int)$data['department_type_id'];
                        $employee_info['department_type_title'] = $data['department_type_title'];
                        $employee_info['position_title'] = $data['position_title'];
                        $employee_info['position_id'] = $data['position_id'];
                        $employee_info['tabel_number'] = $data['tabel_number'];
                        $employee_info['vgk_status'] = $data['vgk_status'];
                        $employee_info['worker_height'] = "";
                        $employee_info['worker_pass_number'] = "";
                        $employee_info['photo'] = "";
                    }
                    if ($data['parameter_id'] == 1 && $data['parameter_type_id'] == 1) {                                // Рост
                        $employee_info['worker_height'] = $data['worker_height'];
                    } else if ($data['parameter_id'] == 2 && $data['parameter_type_id'] == 1) {                         // номер пропуска
                        $employee_info['worker_pass_number'] = $data['worker_pass_number'];
                    } else if ($data['parameter_id'] == 3 && $data['parameter_type_id'] == 1) {                         // фото
                        $employee_info['photo'] = $data['photo'];
                    }
                }
            } else {
                $errors[] = 'Нет данных в БД по указанному сотруднику';
            }
        } else {
            $errors[] = "Параметр worker_id не передан или имеет пустое значение(worker_id = $worker_id)";
        }
        return array('errors' => $errors, 'employee_data' => $employee_info);
    }

    // 127.0.0.1/handbooks/handbook-employee/send-array?table=model
    public function actionSendArray()
    {
//        header('Content-Type: application/json');
//        header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
//        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Дата в прошлом
        $post = Assistant::GetServerMethod();
        $table_name = null;
        $errors = array();
        $table_array = array();
        if (isset($post['table']) && $post['table'] != '') {
            $table_name = $post['table'];
            switch ($table_name) {
                case 'function':
                    $functions = Func::findAll([]);
                    $i = 0;
                    foreach ($functions as $function) {
                        $table_array[$i]['id'] = $function->id;
                        $table_array[$i]['title'] = $function->title;
                        $table_array[$i]['type'] = $function->functionType->title;
                        $table_array[$i]['typeId'] = $function->functionType->id;
                        $i++;
                    }
                    break;
                case 'company':                                                                                         //выборка списка предприятий
                    $table_array = Company::find()
                        ->select(['title', 'id'])
                        ->orderBy(['title' => SORT_ASC])
                        ->asArray()
                        ->all();
                    break;
                case 'department':                                                                                      //выборка списка подразделений
                    $table_array = Department::find()
                        ->select(['title', 'id'])
                        ->orderBy(['title' => SORT_ASC])
                        ->asArray()
                        ->all();
                    break;
                case 'position':                                                                                        //выборка списка должностей
                    $table_array = Position::find()
                        ->select(['title', 'id'])
                        ->orderBy(['title' => SORT_ASC])
                        ->asArray()
                        ->all();
                    break;
                case 'plan_shifts':                                                                                     //выборка списка режимов работ
                    $table_array = PlanShift::find()
                        ->select(['title', 'id'])
                        ->orderBy(['title' => SORT_ASC])
                        ->asArray()
                        ->all();
                    break;
                case 'department_type':                                                                                 //выборка списка типов подразделения
                    $table_array = DepartmentType::find()
                        ->select(['title', 'id'])
                        ->orderBy(['title' => SORT_ASC])
                        ->asArray()
                        ->all();
                    break;
                case 'parameter_type':                                                                                  //выборка списка типов параметра
                    $table_array = ParameterType::find()
                        ->select(['title', 'id'])
                        ->orderBy(['id' => SORT_ASC])
                        ->asArray()
                        ->all();
                    break;
                case 'sensor':                                                                                          //выборка списка объектов АС
                    $table_array = Sensor::find()
                        ->select(['title', 'id'])
                        ->orderBy(['title' => SORT_ASC])
                        ->asArray()
                        ->all();
                    break;
                case 'kind_parameter':                                                                                  //выборка списка видов параметров
                    $table_array = KindParameter::find()
                        ->select(['id', 'title'])
                        ->orderBy('title')
                        ->asArray()
                        ->all();
                    break;
                case 'unit':                                                                                            //выборка списка единиц измерения
                    $table_array = Unit::find()
                        ->select(['id', 'title'])
                        ->orderBy(['title' => SORT_ASC])
                        ->asArray()
                        ->all();
                    break;
                case 'function_type':                                                                                   //выборка списка типов функций
                    $table_array = FunctionType::find()
                        ->select(['id', 'title'])
                        ->orderBy(['title' => SORT_ASC])
                        ->asArray()
                        ->all();
                    break;
                case 'place':                                                                                           //выборка списка мест для параметра Местоположение (place) id = 122
                    $table_array = Place::find()
                        ->select(['id', 'title'])
                        ->orderBy(['title' => SORT_ASC])
                        ->asArray()
                        ->all();
                    break;
                case 'model':
                    $table_array = self::GetCompanyDepartmentForHandbook()['model'];
                    break;
                default:
                    $table_array = array();
                    $errors[] = 'нет совпадений по названию таблицы';
            }
        } else {
            $errors[] = 'Не передан параметр';
        }
        $result = array('errors' => $errors, (string)$table_name => $table_array);
//        echo json_encode($result);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /* Метод передачи основных данных на страницу справочника.
     * Метод запрашивает все данные из БД.
     * Входные параметры отсутствуют.
     * Выходные данные:
     * - $model - (array) массив, содержащий все выводимые в справочнике данные
     * - $companies - (array) массив названий предприятий (нумерация по id)
     * - $departments - (array) массив названий подразделений (нумерация по id)
     * - $department_types - (array) массив названий типов подразделений (нумерация по id)
     * - $positions - (array) массив названий должностей (нумерация по id)
     * - $plan_shifts - (array) массив названий режимов работ (нумерация по id)
     */
    public function actionIndex(): string
    {
        $func_array = (new Query())
            ->select([
                'func.id',
                'func.title',
                'func.function_type_id typeId',
                'function_type.title as type'
            ])
            ->from('func')
            ->leftJoin('function_type', 'function_type.id = func.function_type_id')
            ->all();
        $parameterTypes = ParameterType::find()
            ->select(['title', 'id'])
            ->orderBy(['id' => SORT_ASC])
            ->asArray()
            ->all();

        $sensorList = (new Query())
            ->select(['title', 'id'])
            ->from('sensor')
            ->orderBy(['title' => SORT_ASC])
            ->all();

        $sensorObj = array();
        foreach ($sensorList as $sensorList_item) {
            $sensorObj[$sensorList_item['id']] = $sensorList_item['title'];
        }

        $kindParameters = (new Query())
            ->select(['id', 'title'])
            ->from('kind_parameter')
            ->orderBy('title')
            ->all();

        $units = (new Query())
            ->select(['id', 'title'])
            ->from('unit')
            ->orderBy(['title' => SORT_ASC])
            ->all();
        $functionTypes = (new Query())
            ->select(['id', 'title'])
            ->from('function_type')
            ->orderBy(['title' => SORT_ASC])
            ->all();
        $asmtp = (new Query())
            ->select(['id', 'title'])
            ->from('asmtp')
            ->orderBy(['title' => SORT_ASC])
            ->all();
        $sensorType = (new Query())
            ->select(['id', 'title'])
            ->from('sensor_type')
            ->orderBy(['title' => SORT_ASC])
            ->all();
        $place = (new Query())
            ->select(['id', 'title'])
            ->from('place')
            ->orderBy(['title' => SORT_ASC])
            ->all();
        $place_obj = array();
        foreach ($place as $place_item) {
            $place_obj[$place_item['id']] = $place_item['title'];
        }

        $alarm_groups = GroupAlarm::find()
            ->orderBy(['title' => SORT_ASC])
            ->asArray()
            ->all();

        $this->view->registerJsVar('placeObj', $place_obj);
        $this->view->registerJsVar('sensorObject', $sensorObj);
        return $this->render('index', [
            'parameterTypes' => $parameterTypes,
            'sensorList' => $sensorList,
            'sensorObj' => $sensorObj,
            'kindParameters' => $kindParameters,
            'units' => $units,
            'functionTypes' => $functionTypes,
            'functions' => $func_array,
            'asmtp' => $asmtp,
            'sensorType' => $sensorType,
            'place' => $place,
            'placeObj' => $place_obj,
            'alarm_groups' => $alarm_groups
        ]);
    }

    // actionInitWorkerMain - метод инициализации кеша работников по всей шахте
    // входные параметры
    //      mine_id - ключ шахты
    //  выходные параметры:
    //      стандартный набор
    // пример использования: 127.0.0.1/handbooks/handbook-employee/init-worker-main?mine_id=290
    // разработал Якимов М.Н.
    // дата создания 10.08.2019
    public function actionInitWorkerMain()
    {
        $errors = array();                                                                                                //массив ошибок
        $result = array();
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = "actionInitWorkerMain. Начало выполнения метода";
        try {
            $post = Assistant::GetServerMethod();
            $mine_id = $post['mine_id'];
            (new WorkerCacheController())->amicum_flushall();
            $response = (new WorkerCacheController())->runInitHash($mine_id);
            $errors[] = $response['errors'];
            $status = $response['status'];

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'actionInitWorkerMain. Исключение';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $warnings[] = "actionInitWorkerMain. Закончил выполнение метода";

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        unset($result);
        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result_main;
    }

    /********************************************************************
     *                          Функция удаления                        *
     ********************************************************************/

    /**
     * Название метода: actionDeleteWorker()
     * Назначение метода: метод удаления работника из БД и из кэша. Метод полностью удаляет данные работника из кэша и из БД
     * Входные обязательные параметры:
     * @package frontend\controllers\handbooks
     * @example http://amicum.advanced/handbooks/handbook-employee/delete-worker
     *
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 11.06.2019 14:10
     */
    public function actionDeleteWorker()
    {
        $arr = array();
        $errors = array();
        $post = Yii::$app->request->post(); //получение данных от ajax-запроса
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 26)) {                                                //если пользователю разрешен доступ к функции

                if (isset($post['id']) and $post['id'] != "") {
                    $worker = Worker::findOne(['id' => $post['id']]);
                    if ($worker) //если работника есть
                    {
                        Employee::deleteAll(['id' => $worker->employee_id]);// !!!!!!!!! Удаляет у работника все данные, все-все)

                        /************** Удаление работника из кэша ****************************/
                        $worker_id = $worker->id;
                        $workerCacheController = new WorkerCacheController();

                        $worker_cache_del = $workerCacheController->delParameterValueHash($worker_id);
                        $errors = array_merge($errors, $worker_cache_del['errors']);
                        $workerCacheController->delWorkerMineHash($worker_id);
                    } else {
                        $errors[] = "Указанного сотрудника не существует";
                    }
                } else {
                    $errors[] = "Не передан worker_id сотрудника";
                }
            } else {
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";
            $this->redirect('/');
        }
        if (isset($post['mainCompany']) && $post['mainCompany'] != "" && isset($post['companyDepartment']) && $post['companyDepartment'] != "")
            $arr = $this->buildEmployeeArray("", $post['mainCompany'], $post['companyDepartment']);
        $result = array('errors' => $errors, 'workers' => $arr);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /* Метод добавления подразделений.
     * Входные параметры:
     * - $post['title'] - (string) название нового подразделения
     * - $post['company'] - (int) идентификатор предприятия
     * - $post['type'] - (int) идентификатор типа подразделения
     * - $post['work_mode'] – (int) идентификатор режима работы (план смен)
     * Выходные параметры: результат выполнения метода buildArray в формате json
     */
    public function actionAddDepartment()
    {
        $log = new LogAmicumFront("actionAddDepartment");
        $result = null;

        try {
            $log->addLog("Начал выполнять метод");

            $session = Yii::$app->session;                                                                              // старт сессии
            $session->open();                                                                                           // открыть сессию
            if (!isset($session['sessionLogin'])) {                                                                     // если в сессии есть логин
                throw new Exception("Время сессии закончилось. Требуется повторный ввод пароля");
            }

            if (!AccessCheck::checkAccess($session['sessionLogin'], 15)) {                                       // если пользователю разрешен доступ к функции
                throw new Exception("Недостаточно прав для совершения данной операции");
            }
            $post = Yii::$app->request->post(); //получение данных от ajax-запроса

            if (!isset($post['work_mode']) or !$post['work_mode']) {                                                     // режим работы подразделения
                throw new Exception("Режим работы не задан");
            }

            $work_mode = $post['work_mode'];
            $plan_shift = PlanShift::findOne(['id' => $work_mode]);                                                     // если такой режим работы существует
            if (!$plan_shift) {
                throw new Exception("В БД нет такого режима работы");
            }

            // ключ вышестоящей компании
            if (!isset($post['company']) or !$post['company']) {
                throw new Exception("Ключ вышестоящего подразделения не передан");
            }
            $upper_company_id = $post['company'];

            // название вновь создаваемого подразделения
            if (!isset($post['title']) or $post['title'] == '') {                                                       // если название подразделения задано
                throw new Exception("Название подразделения не задано");
            }
            $department_title = $post['title'];

            // тип вновь создаваемого подразделения (очистной, вспомогательный и т.д.)
            if (!isset($post['type']) or $post['type'] == '') {                                                         // если название подразделения задано
                throw new Exception("Тип подразделения не задан");
            }
            $department_type = $post['type'];

            $response = self::AddDepartment($upper_company_id, $department_title, $department_type, $work_mode);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Не удалось сохранить новое подразделение");
            }

            /**
             * блок возврата обновленного списка компаний/департаментов во фронт
             */
            $result = self::GetCompanyDepartmentForHandbook()['model'];

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());

        }

        $log->addLog('Закончил выполнять метод');

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['companies' => $result, 'Items' => $result], $log->getLogAll());
    }

    /* Метод добавления подразделений.
     * Входные параметры:
     * - $post['title'] - (string) название нового подразделения
     * - $post['company'] - (int) идентификатор предприятия
     * - $post['type'] - (int) идентификатор типа подразделения
     * - $post['work_mode'] – (int) идентификатор режима работы (план смен)
     * Выходные параметры: результат выполнения метода buildArray в формате json
     */
    public static function AddDepartment($upper_company_id, $department_title, $department_type, $work_mode): array
    {
        $log = new LogAmicumFront("AddDepartment");

        $result = array();
        $company_id = -1;

        try {
            $log->addLog('Начал выполнять метод');
            /**
             * Теперь таблица департаментов является избыточной, потому делаем заглушку, на эту таблицу,
             * дабы не ломать всю логику и схему строения таблиц (необходим рефакторинг огромного количества запросов и методов
             */
            $department = Department::findOne(['title' => $department_title]);
            if (!$department) {
                $department = new Department();
                $department->title = $department_title;
                if (!$department->save()) {                                                                        //сохранить модель
                    $log->addData($department->errors, '$department->errors', __LINE__);
                    throw new Exception("ошибка сохранения модели Department");
                }
                $department->refresh();
            }


            /**
             * Блок проверки такого департамента в таблице компаний
             * считаем все департаментами, что имеет вышестоящую компанию,
             * логика такая, если такое название уже есть в компании/департаменте в которую пытаемся вставить, то выкидываем ошибку,
             * иначе делаем вставку
             */
            $company = Company::find()->where(['title' => $department_title, 'upper_company_id' => $upper_company_id])->one();

            if ($company) {                                                                                          //если модели нет
                throw new Exception("Такой департамент уже существует в данном подразделении");
            }

            $company = new Company();                                                                                //создается новая модель
            $company->title = $department_title;                                                                     //сохраняется название подразделения
            $company->upper_company_id = $upper_company_id;                                                          //сохраняется название подразделения
            if (!$company->save()) {                                                                                 //сохранить модель
                $log->addData($company->errors, '$company->errors', __LINE__);
                throw new Exception("Ошибка сохранения модели Company. Не удалось сохранить подразделение");
            }
            $company->refresh();

            $company_id = $company->id;

            /**
             * Блок создания конкретного департамента. В старой логике здесь привязывался департамент, в данном случае
             * оставлена старая структура, что бы не ломать весь код, ключ из таблицы Company совпадает с ключем из таблицы CompanyDepartment
             */
            $company_department = new CompanyDepartment();
            $company_department->id = $company_id;                                                                      //сохранить id предприятия
            $company_department->company_id = $company_id;                                                              //сохранить id предприятия
            $company_department->department_id = $department->id;                                                  //сохранить id подразделения
            $company_department->department_type_id = $department_type;                                                 //сохранить тип подразделения
            if (!$company_department->save()) {                                                                         //если привязка сохранится в БД
                $log->addData($company_department->errors, '$company_department->errors', __LINE__);
                throw new Exception("Ошибка сохранения модели CompanyDepartment. Не удалось сохранить привязку");
            }

            /**
             * Блок создания режима работы для подразделения,
             * если задан, то берется из заданной части, если нет то ищется у вышестоящего структурного подразделения или у шахты в целом
             * переделал на обязательность задания режима работы, если режим работы не задан, то вылетает исключение
             */
            $shift_department = new ShiftDepartment();                                                                  //создать новую модель привязки режима работы к подразделению
            $shift_department->company_department_id = $company_id;                                                     //сохранить id подразделения
            $shift_department->plan_shift_id = $work_mode;                                                              //сохранить id режима работы
            $shift_department->date_time = Assistant::GetDateNow();
            $shift_department->save();
            if (!$shift_department->save()) {                                                                           //если привязка сохранится в БД
                $log->addData($shift_department->errors, '$shift_department->errors', __LINE__);
                throw new Exception("Ошибка сохранения модели ShiftDepartment. Не удалось сохранить режим работы");
            }

            HandbookCachedController::clearDepartmentCache();
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog('Закончил выполнять метод');

        return array_merge(['Items' => $result, 'company_id' => $company_id,], $log->getLogAll());
    }

    public static function GetCompanyDepartmentForHandbook(): array
    {
        $companies_array = array();                                                                                          // Массив содержит список бригад сгруппированный по участкам
        $warnings = array();
        $errors = array();                                                                                                   // Массив ошибок при выполнении метода
        $status = 1;

        $warnings[] = 'GetCompanyDepartment. Зашел в метод получения списка подразделений';
        try {
            // Получаем список всех компаний и департаментов
            // Жадная загрузка используется для получения всех вложеностей, все 10 глубоких вложеностей
            $companies = Company::find();
            $companies->leftJoin('company_department', 'company.id = company_department.company_id')
                ->leftJoin('department', 'company_department.department_id = department.id')
                ->leftJoin('shift_department', 'shift_department.company_department_id = company_department.id')
                ->leftJoin('shift_mine', 'shift_mine.company_id = company.id');
            $companies->groupBy('company.id')
                ->with('companies.companies.companies.companies.companies.companies.companies.companies.companies.companies')
                ->with(['companyDepartments',
                    'companies.companyDepartments',
                    'companies.companies.companyDepartments',
                    'companies.companies.companies.companyDepartments',
                    'companies.companies.companies.companies.companyDepartments',
                    'companies.companies.companies.companies.companies.companyDepartments',
                    'companies.companies.companies.companies.companies.companies.companyDepartments',
                    'companies.companies.companies.companies.companies.companies.companies.companyDepartments',
                    'companies.companies.companies.companies.companies.companies.companies.companies.companyDepartments',
                    'companies.companies.companies.companies.companies.companies.companies.companies.companies.companyDepartments',
                    'companies.companies.companies.companies.companies.companies.companies.companies.companies.companies.companyDepartments'])
                ->with(['companyDepartments.department',
                    'companies.companyDepartments.department',
                    'companies.companies.companyDepartments.department',
                    'companies.companies.companies.companyDepartments.department',
                    'companies.companies.companies.companies.companyDepartments.department',
                    'companies.companies.companies.companies.companies.companyDepartments.department',
                    'companies.companies.companies.companies.companies.companies.companyDepartments.department',
                    'companies.companies.companies.companies.companies.companies.companies.companyDepartments.department',
                    'companies.companies.companies.companies.companies.companies.companies.companies.companyDepartments.department',
                    'companies.companies.companies.companies.companies.companies.companies.companies.companies.companyDepartments.department',
                    'companies.companies.companies.companies.companies.companies.companies.companies.companies.companies.companyDepartments.department'])
                ->with(['companyDepartments.shiftDepartments',
                    'companies.companyDepartments.shiftDepartments',
                    'companies.companies.companyDepartments.shiftDepartments',
                    'companies.companies.companies.companyDepartments.shiftDepartments',
                    'companies.companies.companies.companies.companyDepartments.shiftDepartments',
                    'companies.companies.companies.companies.companies.companyDepartments.shiftDepartments',
                    'companies.companies.companies.companies.companies.companies.companyDepartments.shiftDepartments',
                    'companies.companies.companies.companies.companies.companies.companies.companyDepartments.shiftDepartments',
                    'companies.companies.companies.companies.companies.companies.companies.companies.companyDepartments.shiftDepartments',
                    'companies.companies.companies.companies.companies.companies.companies.companies.companies.companyDepartments.shiftDepartments',
                    'companies.companies.companies.companies.companies.companies.companies.companies.companies.companies.companyDepartments.shiftDepartments'])
                ->with(['companies.shiftMines',
                    'companies.companies.shiftMines',
                    'companies.companies.companies.shiftMines',
                    'companies.companies.companies.companies.shiftMines',
                    'companies.companies.companies.companies.companies.shiftMines',
                    'companies.companies.companies.companies.companies.companies.shiftMines',
                    'companies.companies.companies.companies.companies.companies.companies.shiftMines',
                    'companies.companies.companies.companies.companies.companies.companies.companies.shiftMines',
                    'companies.companies.companies.companies.companies.companies.companies.companies.companies.shiftMines',
                    'companies.companies.companies.companies.companies.companies.companies.companies.companies.companies.shiftMines',
                    'companies.companies.companies.companies.companies.companies.companies.companies.companies.companies.companies.shiftMines']);
            $indexes = ["lvl1" => 0, "lvl2" => 0, "lvl3" => 0, "lvl4" => 0, "lvl5" => 0,
                "lvl6" => 0, "lvl7" => 0, "lvl8" => 0, "lvl9" => 0, "lvl10" => 0];
            $warnings[] = 'GetCompanyDepartment. Данные по подразделениям получены, группировка';
            // Начинаем перебор полученных данных, компаний самого верхнего уровня у которых нет upper
            foreach ($companies->each() as $company) {
                // Начинаем перебор с самого нижнего уровня
                if ($company->upper_company_id === NULL) {
                    $companies_lvl1 = self::buildArrayForHandbook($company);                                           // Записываем данные в массив для компаний первого уровня
                    $companies_array[$indexes['lvl1']] = $companies_lvl1;
                    foreach ($company->companies as $company_down_lvl2)                                                 // Для каждого из них находим нижестоящие
                    {
                        $companies_lvl2 = self::buildArrayForHandbook($company_down_lvl2);                             // Записываем данные в массив для компаний второго и последующих уровней
                        $companies_array[$indexes['lvl1']]['companies'][$indexes['lvl2']] = $companies_lvl2;
                        foreach ($company_down_lvl2->companies as $company_down_lv3) {
                            $companies_lvl3 = self::buildArrayForHandbook($company_down_lv3);

                            $companies_array[$indexes['lvl1']]['companies'][$indexes['lvl2']]
                            ['companies'][$indexes['lvl3']] = $companies_lvl3;
                            foreach ($company_down_lv3->companies as $company_down_lv4) {
                                $companies_lvl4 = self::buildArrayForHandbook($company_down_lv4);

                                $companies_array[$indexes['lvl1']]['companies'][$indexes['lvl2']]
                                ['companies'][$indexes['lvl3']]['companies'][$indexes['lvl4']] = $companies_lvl4;
                                foreach ($company_down_lv4->companies as $company_down_lv5) {
                                    $companies_lvl5 = self::buildArrayForHandbook($company_down_lv5);

                                    $companies_array[$indexes['lvl1']]['companies'][$indexes['lvl2']]
                                    ['companies'][$indexes['lvl3']]['companies'][$indexes['lvl4']]
                                    ['companies'][$indexes['lvl5']] = $companies_lvl5;
                                    foreach ($company_down_lv5->companies as $company_down_lv6) {
                                        $companies_lvl6 = self::buildArrayForHandbook($company_down_lv6);

                                        $companies_array[$indexes['lvl1']]['companies'][$indexes['lvl2']]
                                        ['companies'][$indexes['lvl3']]['companies'][$indexes['lvl4']]
                                        ['companies'][$indexes['lvl5']]['companies'][$indexes['lvl6']] = $companies_lvl6;
                                        foreach ($company_down_lv6->companies as $company_down_lv7) {
                                            $companies_lvl7 = self::buildArrayForHandbook($company_down_lv7);

                                            $companies_array[$indexes['lvl1']]['companies'][$indexes['lvl2']]
                                            ['companies'][$indexes['lvl3']]['companies'][$indexes['lvl4']]
                                            ['companies'][$indexes['lvl5']]['companies'][$indexes['lvl6']]
                                            ['companies'][$indexes['lvl7']] = $companies_lvl7;
                                            foreach ($company_down_lv7->companies as $company_down_lv8) {
                                                $companies_lvl8 = self::buildArrayForHandbook($company_down_lv8);

                                                $companies_array[$indexes['lvl1']]['companies'][$indexes['lvl2']]
                                                ['companies'][$indexes['lvl3']]['companies'][$indexes['lvl4']]
                                                ['companies'][$indexes['lvl5']]['companies'][$indexes['lvl6']]
                                                ['companies'][$indexes['lvl7']]['companies'][$indexes['lvl8']] = $companies_lvl8;
                                                foreach ($company_down_lv8->companies as $company_down_lv9) {
                                                    $companies_lvl9 = self::buildArrayForHandbook($company_down_lv9);

                                                    $companies_array[$indexes['lvl1']]['companies'][$indexes['lvl2']]
                                                    ['companies'][$indexes['lvl3']]['companies'][$indexes['lvl4']]
                                                    ['companies'][$indexes['lvl5']]['companies'][$indexes['lvl6']]
                                                    ['companies'][$indexes['lvl7']]['companies'][$indexes['lvl8']]
                                                    ['companies'][$indexes['lvl9']] = $companies_lvl9;
                                                    foreach ($company_down_lv9->companies as $company_down_lv10) {
                                                        $companies_lvl10 = self::buildArrayForHandbook($company_down_lv10);

                                                        $companies_array[$indexes['lvl1']]['companies'][$indexes['lvl2']]
                                                        ['companies'][$indexes['lvl3']]['companies'][$indexes['lvl4']]
                                                        ['companies'][$indexes['lvl5']]['companies'][$indexes['lvl6']]
                                                        ['companies'][$indexes['lvl7']]['companies'][$indexes['lvl8']]
                                                        ['companies'][$indexes['lvl9']]['companies'][$indexes['lvl10']] = $companies_lvl10;
                                                        $indexes['lvl10']++;
                                                    }
                                                    $indexes['lvl10'] = 0;
                                                    $indexes['lvl9']++;
                                                }
                                                $indexes['lvl9'] = 0;
                                                $indexes['lvl8']++;
                                            }
                                            $indexes['lvl8'] = 0;
                                            $indexes['lvl7']++;
                                        }
                                        $indexes['lvl7'] = 0;
                                        $indexes['lvl6']++;
                                    }
                                    $indexes['lvl6'] = 0;
                                    $indexes['lvl5']++;
                                }
                                $indexes['lvl5'] = 0;
                                $indexes['lvl4']++;
                            }
                            $indexes['lvl4'] = 0;
                            $indexes['lvl3']++;
                        }
                        $indexes['lvl3'] = 0;
                        $indexes['lvl2']++;
                    }
                    $indexes['lvl2'] = 0;
                    $indexes['lvl1']++;
                }
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = $e->getMessage();                                                                                // Добавляем в массив ошибок, полученную ошибку

        }
        $warnings[] = 'GetCompanyDepartment. Данные по подразделениям получены';
        return array('model' => $companies_array, 'warnings' => $warnings, 'errors' => $errors, 'status' => $status);
    }

    public static function buildArrayForHandbook($company)
    {
        try {
            // Для каждой из компании нужно получить список нижестоящих компаний
            $companies_array['id'] = $company->id;                                                              // Сохраняется данные предприятия
            $companies_array['title'] = str_replace("  ", " ", $company->title);
            $companies_array['upper_company_id'] = $company->upper_company_id;

            if (isset($company->shiftMines[0])) {
                $companies_array['plan_shift_id'] = $company->shiftMines[0]->plan_shift_id;
            } else {
                $companies_array['plan_shift_id'] = null;
            }

            if (isset($company->companyDepartments[0])) {
                $companies_array['department_type_id'] = $company->companyDepartments[0]->department_type_id;
            } else {
                $companies_array['department_type_id'] = null;
            }

            if ($company->companyDepartments)                                                                            // Если у компании есть подразделения
            {
                $j = 0;
                foreach ($company->companyDepartments as $companyDepartment) {                                            // Для каждого подразделения предприятия


                    $companies_array['departments'][$j]['id'] = $companyDepartment->id;                         // Добавление предприятия подразделению
                    $companies_array['departments'][$j]['dep_id'] = $companyDepartment->department_id;                         // Добавление предприятия подразделению
                    $companies_array['departments'][$j]['type'] = $companyDepartment->department_type_id;                         // Добавление предприятия подразделению
                    if (isset($companyDepartment->shiftDepartments[0])) {
                        $companies_array['departments'][$j]['plan_shift_id'] = $companyDepartment->shiftDepartments[0]->plan_shift_id;                         // Добавление предприятия подразделению
                    } else {
                        $companies_array['departments'][$j]['plan_shift_id'] = null;                         // Добавление предприятия подразделению
                    }

                    $companies_array['departments'][$j]['title'] =
                        $companyDepartment->department->title;
                    $j++;
                }

            }
            return $companies_array;
        } catch (Throwable $ex) {
            print_r($ex->getMessage());
            print_r($ex->getLine());
            die;
        }
    }

    /* Метод редактирования подразделения
    * Входные данные:
    * - $post['id'] – (int) идентификатор подразделения предприятия
    * - $post['title'] – (string) новое название подразделения
    * - $post['type'] – (int) идентификатор нового типа подразделения
    * - $post['work_mode'] – (int) идентификатор нового режима работы (план смен)
    * Выходные параметры: результат выполнения метода buildArray в формате json
    */
    public function actionEditDepartment()
    {
        $errors = array();
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 16)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                //Выбрать подразделение предприятия (CompanyDepartment) по полученному идентификатору
                if (isset($post['id']) and $post['id'] != "") {
                    $company_department = CompanyDepartment::findOne($post['id']);
                    //Если оно существует
                    if ($company_department) {
                        //Если задано новое название,
                        if (isset($post['title']) and $post['title'] != "") {
                            //запросить подразделение с новым названием
                            $department = Department::find()->where(['title' => $post['title']])->one();
                            //Если подразделения с новым названием не существует,
                            if (!$department) {
                                //Создать новое подразделение с новым названием
                                $department = new Department();
                                $department->title = $post['title'];
                                if (!$department->save()) {
                                    $errors[] = "Не удалось сохранить новое подразделение";
                                } else {
                                    //Привязать его вместо старого
                                    $company_department->department_id = $department->id;
                                }
                            }
                        } else {
                            $errors[] = "Не передано название подразделения";
                        }
                        //Если задан тип подразделения
                        if (isset($post['type']) and $post['type'] != "") {
                            //Если такой тип существует,
                            $department_type = DepartmentType::findOne($post['type']);
                            if ($department_type) {
                                //сохранить его
                                $company_department->department_type_id = $post['type'];
                            } else {
                                $errors[] = "Тип подразделения не найден";
                            }
                        } else {
                            $errors[] = "Не передан идентификатор типа подразделения";
                        }
                        if (!$company_department->save()) {
                            $errors[] = "Не удалось сохранить объект привязки подразделения с предприятием";
                        }
                        //Если задан идентификатор режима работы
                        if (isset($post['work_mode']) and $post['work_mode'] != "") {
                            //Если такой режим работы существует,
                            $work_mode = PlanShift::findOne($post['work_mode']);
                            if ($work_mode) {
                                //сохранить его
                                $shift_dep = new ShiftDepartment();
                                $shift_dep->company_department_id = $company_department->id;
                                $shift_dep->plan_shift_id = $post['work_mode'];
                                $shift_dep->date_time = date('Y-m-d H:i:s');
                                if (!$shift_dep->save()) {
                                    $errors[] = "Не удалось сохранить режим работы";
                                }
                            }
                        } else {
                            $errors[] = "Не передан идентификатор режима работы";
                        }
                    } else {
                        $errors[] = "Нет такой привязки подразделения с предприятием";
                    }
                } else {
                    $errors[] = "Не передан company_department_id";
                }
            } else {
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";
            $this->redirect('/');
        }
        HandbookCachedController::clearDepartmentCache();
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $errors;
    }

    /********************************************************************
     *                      Функции редактирования                      *
     ********************************************************************/

    /* Метод редактирования предприятия
     * Входные данные:
     * - $post['id'] – (int) идентификатор предприятия
     * - $post['title'] – (string) новое название предприятия
     * - $post['upper'] – (int) идентификатор нового вышестоящего предприятия
     * - $post['work_mode'] – (int) идентификатор нового режима работы (план смен)
     * Выходные параметры: результат выполнения метода buildArray в формате json
     */
    public function actionEditCompany()
    {
        $errors = array();
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 22)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                //Выбрать предприятие с идентификатором, равным полученному идентификатору
                if (isset($post['id']) and $post['id'] != "") {
                    $company = Company::findOne(['id' => $post['id']]);
                    //Если предприятие есть
                    if ($company) {
                        //Если задано новое название, сохранить его
                        if ($post['title']) {
                            $company->title = $post['title'];
                            if (!$company->save()) {
                                $errors[] = "не удалось сохранить  название";
                            }
                        }

                        //Если тип подразделения задан
                        if ($post['type_department_id']) {
                            //Если такой режим работы существует
                            $company_department = CompanyDepartment::findOne(['id' => $post['id']]);
                            if (!$company_department) {
                                //сохранить его
                                $company_department = new CompanyDepartment();
                                $company_department->company_id = $post['id'];
                                $company_department->id = $post['id'];
                                $company_department->department_id = 1;
                            }

                            $company_department->department_type_id = $post['type_department_id'];
                            if (!$company_department->save()) {
                                $errors[] = "не удалось сохранить тип подразделения";
                            }

                        } else {
                            $errors[] = "не передан type_department_id";
                        }

                        //Если режим работы задан
                        if ($post['work_mode']) {
                            //Если такой режим работы существует
                            $work_mode = PlanShift::findOne(['id' => $post['work_mode']]);
                            if ($work_mode) {
                                //сохранить его
                                $shift_mine = new ShiftMine();
                                $shift_mine->company_id = $company->id;
                                $shift_mine->plan_shift_id = $post['work_mode'];
                                $shift_mine->date_time = date('Y-m-d H:i:s');
                                if (!$shift_mine->save()) {
                                    $errors[] = "не удалось сохранить режим работы";
                                }
                            }
                        } else {
                            $errors[] = "не передан work_mode";
                        }
                        //строится массив данных с учетом добавленного предприятия
                        //$arr = $this->buildArray();
                        //массив возвращается ajax-запросу в формате json
                    }
                } else {
                    $errors[] = "не передан идентификатор компании";
                }
            } else {
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        } else {
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";
            $this->redirect('/');
        }
        // $result = array('errors' => $errors, 'companies' => $arr);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $errors;
    }

    /*
     * функция построения параметров конкретного объекта
     * */
    public static function buildSpecificParameterArray($specificObjectId): array
    {
        $paramsArray = array();//массив для сохранения параметров

        $kinds = KindParameter::find()
            ->with('parameters')
            ->with('parameters.unit')
            ->all();//находим все виды параметров
        $i = 0;
        if ($specificObjectId) {//если передан id конкретного объекта
            foreach ($kinds as $kind) {//перебираем все виды параметров
                $paramsArray[$i]['id'] = $kind->id;//сохраняем id вида параметров
                $paramsArray[$i]['title'] = $kind->title;//сохраняем имя вида параметра
                if ($parameters = $kind->parameters) {//если у вида параметра есть параметры
                    $j = 0;
                    foreach ($parameters as $parameter) {//перебираем все параметры
                        try {
                            if ($specificObjParameters = $parameter->getWorkerParameters()
                                ->where(['worker_object_id' => $specificObjectId])->orderBy(['parameter_type_id' => SORT_ASC])->all()) {//если есть типовые параметры переданного объекта
                                $paramsArray[$i]['params'][$j]['id'] = $parameter->id;//сохраняем id параметра
                                $paramsArray[$i]['params'][$j]['title'] = $parameter->title;//сохраняем наименование параметра
                                $paramsArray[$i]['params'][$j]['units'] = $parameter->unit->short;//сохраняем единицу измерения
                                $paramsArray[$i]['params'][$j]['units_id'] = $parameter->unit_id;//сохраняем id единицы измерения
                                $k = 0;
                                foreach ($specificObjParameters as $specificObjParameter) {//перебираем конкретный параметр
                                    $paramsArray[$i]['params'][$j]['specific'][$k]['id'] = $specificObjParameter->parameter_type_id;//id типа параметра
                                    $paramsArray[$i]['params'][$j]['specific'][$k]['title'] = $specificObjParameter->parameterType->title;//название параметра
                                    $paramsArray[$i]['params'][$j]['specific'][$k]['specificObjectParameterId'] = $specificObjParameter->id;//id параметра конкретного объекта

                                    switch ($specificObjParameter->parameter_type_id) {
                                        case 1:
                                            if ($value = $specificObjParameter->getWorkerParameterHandbookValues()->orderBy(['date_time' => SORT_DESC])->one()) {
                                                if ($value->value != 'empty')
                                                    $paramsArray[$i]['params'][$j]['specific'][$k]['value'] = $value->value;//сохраняем справочное значение

                                                if ($parameter->id == 337) {
                                                    // echo "зашли в условие для асутп " .(int)$value->value."\n";
                                                    $asmtpTitle = $value->value == -1 ? '' : ASMTP::findOne((int)$value->value)->title;
                                                    $paramsArray[$i]['params'][$j]['specific'][$k]['asmtpTitle'] = $asmtpTitle;
                                                } else if ($parameter->id == 338) {
//                                                echo "зашли в условие для типов датчика ". $value->value. "\n";
                                                    $sensorTypeTitle = $value->value == -1 ? '' : SensorType::findOne((int)$value->value)->title;
                                                    $paramsArray[$i]['params'][$j]['specific'][$k]['sensorTypeTitle'] = $sensorTypeTitle;
                                                } else if ($parameter->id == 274) {
//                                                echo "зашли в условие для типов датчика ". $value->value. "\n";
                                                    if ($objectTitle = TypicalObject::findOne($value->value)) {
                                                        $paramsArray[$i]['params'][$j]['specific'][$k]['objectTitle'] = $objectTitle->title;
                                                    }
                                                } else if ($parameter->id == 122) {
                                                    if ($placeTitle = Place::findOne($value->value)) {
                                                        $paramsArray[$i]['params'][$j]['specific'][$k]['placeTitle'] = $placeTitle->title;
                                                    } else {
                                                        $paramsArray[$i]['params'][$j]['specific'][$k]['placeTitle'] = '';
                                                    }
                                                } else if ($parameter->id == ParamEnum::ALARM_GROUP) {
                                                    //в 523 параметре 1 - Заполярная, 2 - Воркутинская -  данная часть хранится в справочнике group_alarm - Параметр группы оповещения
                                                    if ($alarm_group_title = GroupAlarm::findOne($value->value)) { // Название группы оповещения
                                                        $paramsArray['params'][$j]['specific'][$k]['alarmGroupTitle'] = $alarm_group_title->title;
                                                    } else {
                                                        $paramsArray['params'][$j]['specific'][$k]['alarmGroupTitle'] = '';
                                                    }
                                                } else if ($parameter->id == ParamEnum::PREDPRIYATIE) {
                                                    //в 18 параметре 1 - Заполярная, 2 - Воркутинская -  данная часть хранится в справочнике group_alarm - Разделение на Воркутинску и Заполярку
                                                    if ($alarm_group_title = GroupAlarm::findOne($value->value)) { // Название группы оповещения
                                                        $paramsArray['params'][$j]['specific'][$k]['alarmGroupTitle'] = $alarm_group_title->title;
                                                    } else {
                                                        $paramsArray['params'][$j]['specific'][$k]['alarmGroupTitle'] = '';
                                                    }
                                                }
                                            }

                                            break;
                                        case 2:
                                            if ($valueFromParameterValue = $specificObjParameter->getWorkerParameterValues()->orderBy(['date_time' => SORT_DESC])->one()) {
                                                $paramsArray[$i]['params'][$j]['specific'][$k]['value'] = $valueFromParameterValue->value;
                                            } else {
                                                $paramsArray[$i]['params'][$j]['specific'][$k]['value'] = '-1';
                                            }

                                            break;
                                        case 3:
                                            if ($valueFromParameterValue = $specificObjParameter->getWorkerParameterValues()->orderBy(['date_time' => SORT_DESC])->one()) {
                                                $paramsArray[$i]['params'][$j]['specific'][$k]['value'] = $valueFromParameterValue->value;
                                            } else {
                                                $paramsArray[$i]['params'][$j]['specific'][$k]['value'] = '-1';
                                            }
                                            $k++;
                                            $paramsArray[$i]['params'][$j]['specific'][$k]['id'] = 5;//id типа параметра
                                            $paramsArray[$i]['params'][$j]['specific'][$k]['title'] = 'Привязка датчика';//название параметра
                                            $paramsArray[$i]['params'][$j]['specific'][$k]['specificObjectParameterId'] = $specificObjParameter->id;//id параметра кон
                                            if ($value = $specificObjParameter->getWorkerParameterSensor()->orderBy(['date_time' => SORT_DESC])->one()) {
                                                $paramsArray[$i]['params'][$j]['specific'][$k]['sensor_id'] = $value->sensor_id;//сохраняем измеряемое значение
                                            } else {
                                                $paramsArray[$i]['params'][$j]['specific'][$k]['sensor_id'] = -1;
                                            }
                                            break;
                                    }
                                    $k++;
                                }
                                $j++;
                            }
                        } catch (Throwable $exception) {
                            Assistant::VarDump($exception->getLine());
                            Assistant::VarDump($exception->getMessage());
                            Assistant::VarDump($exception->getTraceAsString());
                        }
                    }
                    ArrayHelper::multisort($paramsArray[$i]['params'], 'title', SORT_ASC);
                }
                $i++;
            }
        }
        ArrayHelper::multisort($paramsArray, 'title', SORT_ASC);
        return $paramsArray;
    }

    /**
     * Метод GetRoleByWorker() - возвращает основную роль работника по идентификатору
     * @param null $data_post - JSON  с идентификатором работника
     * @return array - стандартный массив выходных данных: Items: (role_id)
     *                                                     status:
     *                                                     [errors]
     *                                                     [warnings]
     *
     * @package frontend\controllers\handbooks
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\HandbookEmployee&method=GetRoleByWorker&subscribe=&data={%22worker_id%22:2023080}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 20.08.2019 14:47
     */
    public static function GetRoleByWorker($data_post = NULL): array
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $role_id = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = 'GetRoleByWorker. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('GetRoleByWorker. Не переданы входные параметры');
            }
            $warnings[] = 'GetRoleByWorker. Данные успешно переданы';
            $warnings[] = 'GetRoleByWorker. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GetRoleByWorker. Декодировал входные параметры';
            if (!property_exists($post_dec, 'worker_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('GetRoleByWorker. Переданы некорректные входные параметры');
            }
            $warnings[] = 'GetRoleByWorker. Данные с фронта получены';
            $worker_id = $post_dec->worker_id;
            $found_role_id = WorkerObject::findOne(['worker_id' => $worker_id]);
            if ($found_role_id) {
                $role_id = $found_role_id->role_id;
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetRoleByWorker. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetRoleByWorker. Конец метода';
        $result = $role_id;

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    public function SaveWorkerData($first_name, $last_name, $patronymic, $birth_date, $gender, $company_id,
                                   $department_id, $department_type_id, $position_id, $staff_number, $file,
                                   $file_name, $file_extension, $height, $date_start, $date_end, $vgk_status,
                                   $work_mode, $type_obj, $role_id, $pass_number): array
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveWorkerData';
        $saving_data = array();                                                                                // Промежуточный результирующий массив
        $isset_tabel_number = true;
        $warnings[] = $method_name . '. Начало метода';
        try {
            $max_million_worker_id = Employee::find()
                ->select('max(employee.id) as max_employee_id')
                ->where(['<', 'id', 90000])
                ->scalar();

            $employee = Employee::findOne([
                'first_name' => $first_name,
                'last_name' => $last_name,
                'patronymic' => $patronymic,
                'birthdate' => date('Y-m-d', $birth_date),
            ]);
            //Если такого человека нет
            if (!$employee) {
                //Создать новую модель Employee
                $employee = new Employee();
                //Сохранить ФИО, пол и дату рождения
                $employee->id = $max_million_worker_id + 1;
                $employee->first_name = $first_name;
                $employee->last_name = $last_name;
                $employee->patronymic = $patronymic;
                $employee->birthdate = date('Y-m-d', $birth_date);
                $employee->gender = $gender;
                //Сохранить модель
                if (!$employee->save()) {
                    $errors[] = $employee->errors;
                    throw new Exception("actionAddWorker. Не удалось сохранить сотрудника в employee Employee");
                }
            }

            $full_name = $employee->last_name . '_' . $employee->first_name . (isset($employee->patronymic) ? ('_' . $employee->patronymic) : '');


            //Запросить подразделение предприятия по полученным идентификаторам подразделения и предприятия
            $company_department = CompanyDepartment::findOne(['id' => $company_id]);

            //Создать новую модель CompanyDepartment
            $company_department->department_type_id = $department_type_id;
            //Сохранить модель
            if (!$company_department->save()) {
                $errors[] = $company_department->errors;
                throw new Exception("actionAddWorker. Не удалось сохранить привязку подразделения к компании CompanyDepartment");
            }

            //Запросить работника по табельному номеру
            $worker = Worker::find()->where(['tabel_number' => $staff_number])->one();
            $worker_from_employee = Worker::find()->where(['id' => $employee->id])->one();
            if ($worker_from_employee) {
                if ($worker_from_employee['tabel_number'] !== $worker['tabel_number']) {
                    $errors[] = 'actionAddWorker. Сотрудник есть в системе с другим табельным номером';
                    $status = 0;
//                    throw new \Exception("actionAddWorker. Сотрудник есть в системе с другим табельным номером");
                    $isset_tabel_number = false;
                } else {
                    $isset_tabel_number = true;
                }
            }

            //Если работника нет
            if ($worker) {
                throw new Exception("actionAddWorker. Сотрудник с таким табельным номером уже есть в БД");
            }


            if ($isset_tabel_number) {
                $worker = new Worker();//Создать новую модель Worker
                $worker->id = (int)$employee->id;//Привязать человека
                $worker->employee_id = (int)$employee->id;//Привязать человека
                $worker->company_department_id = is_array($company_department) ? (int)$company_department['id'] : $company_department->id;//Привязать подразделение предприятия
                $worker->position_id = $position_id;//Привязать должность
                $worker->tabel_number = $staff_number;//Сохранить табельный номер
                $worker->date_start = date('Y-m-d', strtotime($date_start));//Сохранить дату начала работы в нужном формате
                if (empty($date_end)) {
                    $date_end = '9999-12-31';
                }
                $worker->date_end = $date_end;//Если задана дата окончания работы, сохранить ее
                $worker->vgk = $vgk_status;//Если задана дата окончания работы, сохранить ее
                if (!$worker->save()) {
                    $errors[] = $worker->errors;
                    throw new Exception("actionAddWorker. Не удалось сохранить нового worker'a");
                }//Сохранить модель
                $worker->refresh();
                $worker_id = $worker->id;
                $saving_data['worker_id'] = $worker_id;
                $shift_worker = new ShiftWorker();
                $shift_worker->worker_id = $worker->id;//сохранить id работника
                $shift_worker->plan_shift_id = $work_mode;//сохранить id режима работы
                $shift_worker->date_time = date('Y-m-d H:i:s');
                if (!$shift_worker->save()) {
                    $errors[] = $shift_worker->errors;
                    throw new Exception("actionAddWorker. Не удалось сохранить режим работы у сотрудника ShiftWorker");
                }
                $worker_object = new WorkerObject();//Создать новую модель WorkerObject
                $object = TypicalObject::findOne(['title' => $type_obj]);//получить класс рабочего
                $worker_object->id = (int)$employee->id;//Привязать идентификатор типового объекта (тип работы)
                $worker_object->object_id = $object->id;
                $worker_object->worker_id = $worker->id;//Привязать id работника
                $worker_object->role_id = $role_id;
                if (!$worker_object->save()) {                                                                            //Сохранить модель
                    $errors[] = $worker_object->errors;
                    throw new Exception("actionAddWorker. Не удалось сохранить привязку worker_object WorkerObject");
                }
                $object_id = $worker_object->object_id;
                $worker_object_id = $worker_object->id;
                $response = $this->actionCopyTypicalParametersToWorker($object_id, $worker_object_id);//копирование типового объекта в конкретного работника
                if ($response['status'] == 0) {
                    $errors[] = $response['errors'];
                    $warnings[] = $response['warnings'];
                    throw new Exception("actionAddWorker. Не удалось скопировать типовые параметры работника");
                }//сохраняем значения параметров из базовой таблицы в параметры базового объекта
                $worker_parameter_id = $this->actionAddWorkerParameter($worker_object_id, 1, 1);//параметр рост
                if ($height != '') {
                    $worker_parameter_value = $this->actionAddWorkerParameterHandbookValue($worker_parameter_id, $height, 1, date('Y-m-d H:i:s'));//сохранение значения параметра
                    if ($worker_parameter_value == -1)
                        $errors[] = 'actionAddWorker. Ошибка сохранения значения параметров базового справочника в параметрах: 1';
                }
                $worker_parameter_id = $this->actionAddWorkerParameter($worker_object_id, 2, 1);//параметр номер пропуска
                if (!empty($pass_number)) {
                    $worker_parameter_value = $this->actionAddWorkerParameterHandbookValue($worker_parameter_id, $pass_number, 1, date('Y-m-d H:i:s'));//сохранение значения параметра
                    if ($worker_parameter_value == -1)
                        $errors[] = 'actionAddWorker. Ошибка сохранения значения параметров базового справочника в параметрах: 2';
                }
                $worker_parameter_id = $this->actionAddWorkerParameter($worker_object_id, 392, 1);//параметр Табельный номер
                if (!empty($staff_number)) {
                    $worker_parameter_value = $this->actionAddWorkerParameterHandbookValue($worker_parameter_id, $staff_number, 1, date('Y-m-d H:i:s'));//сохранение значения параметра
                    if ($worker_parameter_value == -1)
                        $errors[] = 'actionAddWorker. Ошибка сохранения значения параметров базового справочника в параметрах: 392';
                }//                        $worker_parameter_id = $this->actionAddWorkerParameter($worker_object_id, 3, 1); //параметр фото
                //                        if ($post['photo'] != "") {
                //                            $worker_parameter_value = $this->actionAddWorkerParameterHandbookValue($worker_parameter_id, $post['photo'], 1, 1);//сохранение значения параметра
                //                            if ($worker_parameter_value == -1) $errors[] = "Ошибка сохранения значения параметров базового справочника в параметрах: 3";
                //                        }
                $worker_parameter_id = $this->actionAddWorkerParameter($worker_object_id, 274, 1);//параметр типовой объект
                $worker_parameter_value = $this->actionAddWorkerParameterHandbookValue($worker_parameter_id, $object_id, 1, date('Y-m-d H:i:s.U'));//сохранение значения параметра
                if ($worker_parameter_value == -1)
                    $errors[] = 'actionAddWorker. Ошибка сохранения значения параметров базового справочника в параметрах: 274';
                if (isset($file)) {
                    $uploaded_photo = $this->uploadPhoto($worker_object_id, $file, $full_name, $file_extension);
                    $errors = array_merge($errors, $uploaded_photo['errors']);
                    $photo_url = $uploaded_photo['url'];
                    $saving_data['photo_url'] = $photo_url;
                }
                $saving_data['arrWorkers'] = $this->getWorkersFromView($company_id, is_array($company_department) ? (int)$company_department['id'] : $company_department->id);
                $saving_data['arrMines'] = self::GetCompanyDepartmentForHandbook()['model'];
//                $full_name = $employee->last_name . ' ' . mb_substr($employee->first_name ,0,1). (isset($employee->patronymic) ? (' ' . mb_substr($employee->patronymic,0,1)) : '');
                $name = mb_substr($employee->first_name, 0, 1);
                if (!empty($employee->patronymic)) {
                    $patronymic = mb_substr($employee->patronymic, 0, 1);
                    $full_name = "$employee->last_name $name. $patronymic.";
                } else {
                    $full_name = "$employee->last_name $name.";
                }
//                $patronymic = mb_substr($employee->patronymic,0,1);
//                $full_name = "{$employee->last_name} {$name}. {$patronymic}.";
                $saving_data['contracting_company'] = [
                    'worker_id' => $worker_id ? $worker_id : null,
                    'stuff_number' => $worker->tabel_number,
                    'full_name' => $full_name,
                    'position_title' => $worker->position->title,
                    'role_id' => $role_id,
                    'role_title' => $worker_object->role->title,
                    'role_type' => $worker_object->role->type,
                    'check_knowledge' => array()];
            }
            HandbookCachedController::clearWorkerCache();
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $saving_data;

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Название метода: GetWorkersWithCompany() - Метод получения списка людей с их подразделениями
     * Метод получения списка людей с их подразделениями
     * http://127.0.0.1/read-manager-amicum?controller=handbooks\HandbookEmployee&method=GetWorkersWithCompany&subscribe=&data=
     * @param
     * @return array
     * @package frontend\controllers\handbooks
     * @example
     *
     * Документация на портале:
     * @author Некрасов Е.П.
     * Created date: on 01.07.2019 17:35
     * @since ver
     */
    public static function GetWorkersWithCompany($data_post): array
    {
        $result = (object)array();                                                                                      // промежуточный результирующий массив
        $errors = array();                                                                                              // массив ошибок
        $warnings = array();                                                                                            // массив предупреждений
        $status = 1;

        $warnings[] = 'GetWorkersWithCompany. Зашел в метод';
        try {

            $workers = Worker::find()
                ->select('
                        worker.id as worker_id,
                        company_department.id as company_department_id,
                        company.title as company_title
                ')
                ->innerJoin('company_department', 'worker.company_department_id = company_department.id')
                ->innerJoin('company', 'company_department.company_id = company.id')
                ->asArray();

            if ($workers->all()) {
                foreach ($workers->each(1000) as $worker) {
                    $list_worker[$worker['worker_id']]['worker_id'] = $worker['worker_id'];
                    $list_worker[$worker['worker_id']]['company_department_id'] = $worker['company_department_id'];
                    $list_worker[$worker['worker_id']]['company_title'] = $worker['company_title'];
                }
                $result = $list_worker;
            } else {
                throw new Exception('GetWorkersWithCompany . список работников пуст');
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = "GetWorkersWithCompany . Исключение ";                                               // Добавляем в массив ошибок, полученную ошибку
            $errors[] = $e->getMessage();                                                                                // Добавляем в массив ошибок, полученную ошибку
            $errors[] = $e->getLine();                                                                                   // Добавляем в массив ошибок, полученную ошибку
        }

        $warnings[] = "GetWorkersWithCompany . Вышел с метода";
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Название метода: GetWorkersWithCompanyForMo() - Метод получения списка людей с их подразделениями для медицинских осмотров
     * Метод получения списка людей с их подразделениями
     * http://127.0.0.1/read-manager-amicum?controller=handbooks\HandbookEmployee&method=GetWorkersWithCompanyForMo&subscribe=&data=
     * @param
     * @return array
     * @package frontend\controllers\handbooks
     * @example
     *
     * Документация на портале:
     * @author Якимов М.Н.
     * Created date: on 15.05.2020 17:35
     * @since ver
     */
    public static function GetWorkersWithCompanyForMo($data_post): array
    {
        $workers = array();                                                                                              // объекты работников
        $errors = array();                                                                                              // массив ошибок
        $warnings = array();                                                                                            // массив предупреждений
        $status = 1;

        $warnings[] = 'GetWorkersWithCompanyForMo. Зашел в метод';
        try {

            $workers = (new Query())
                ->select('
                        worker.id as worker_id,
                        worker.company_department_id as company_department_id
                ')
                ->from('worker')
                ->indexBy('worker_id')
                ->all();

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = "GetWorkersWithCompanyForMo . Исключение ";                                               // Добавляем в массив ошибок, полученную ошибку
            $errors[] = $e->getMessage();                                                                                // Добавляем в массив ошибок, полученную ошибку
            $errors[] = $e->getLine();                                                                                   // Добавляем в массив ошибок, полученную ошибку
        }

        if ($workers) {
            $result = $workers;
        } else {
            $result = (object)array();
        }

        $warnings[] = "GetWorkersWithCompanyForMo . Вышел с метода";

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    // GetWorkerType()      - Получение справочника типов работников
    // SaveWorkerType()     - Сохранение справочника типов работников
    // DeleteWorkerType()   - Удаление справочника типов работников

    /**
     * Метод GetWorkerType() - Получение справочника типов работников
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
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=GetWorkerType&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetWorkerType(): array
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetWorkerType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_worker_type = WorkerType::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_worker_type)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник типов работников пуст';
            } else {
                $result = $handbook_worker_type;
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
     * Метод SaveWorkerType() - Сохранение справочника типов работников
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "worker_type":
     *  {
     *      "worker_type_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "worker_type_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=SaveWorkerType&subscribe=&data={"worker_type":{"worker_type_id":-1,"title":"ACTION"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveWorkerType($data_post = NULL): array
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveWorkerType';
        $handbook_worker_type_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'worker_type'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_worker_type_id = $post_dec->worker_type->worker_type_id;
            $title = $post_dec->worker_type->title;
            $new_handbook_worker_type_id = WorkerType::findOne(['id' => $handbook_worker_type_id]);
            if (empty($new_handbook_worker_type_id)) {
                $new_handbook_worker_type_id = new WorkerType();
            }
            $new_handbook_worker_type_id->id = $handbook_worker_type_id;
            $new_handbook_worker_type_id->title = $title;
            if ($new_handbook_worker_type_id->save()) {
                $new_handbook_worker_type_id->refresh();
                $handbook_worker_type_data['worker_type_id'] = $new_handbook_worker_type_id->id;
                $handbook_worker_type_data['title'] = $new_handbook_worker_type_id->title;
            } else {
                $errors[] = $new_handbook_worker_type_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника типов работников');
            }
            unset($new_handbook_worker_type_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_worker_type_data;

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод DeleteWorkerType() - Удаление справочника типов работников
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "worker_type_id": 98             // идентификатор справочника типов работников
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=DeleteWorkerType&subscribe=&data={"worker_type_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteWorkerType($data_post = NULL): array
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = (object)array();
        $method_name = 'DeleteWorkerType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $result = $post_dec;
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'worker_type_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_worker_type_id = $post_dec->worker_type_id;
            WorkerType::deleteAll(['id' => $handbook_worker_type_id]);

        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }
}
