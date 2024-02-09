<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers;

use backend\controllers\Assistant;
use backend\controllers\cachemanagers\EdgeCacheController;
use backend\controllers\cachemanagers\EventCacheController;
use backend\controllers\cachemanagers\SensorCacheController;
use backend\controllers\cachemanagers\ServiceCache;
use backend\controllers\cachemanagers\SituationCacheController;
use backend\controllers\const_amicum\ParamEnum;
use backend\controllers\const_amicum\ParameterTypeEnumController;
use backend\controllers\EdgeMainController;
use backend\controllers\OpcController;
use backend\controllers\rpc\RpcClient;
use backend\controllers\SensorBasicController;
use backend\controllers\SensorMainController;
use backend\controllers\serviceamicum\PredExamController;
use backend\controllers\serviceamicum\SynchronizationController;
use backend\controllers\serviceamicum\ToroController;
use backend\controllers\StrataJobController;
use backend\controllers\WorkerMainController;
use backend\models\UserActionLog;
use Exception;
use frontend\controllers\handbooks\DepartmentController;
use frontend\controllers\handbooks\HandbookDepartmentController;
use frontend\controllers\handbooks\HandbookTypicalObjectController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\AuditPlace;
use frontend\models\CheckingPlace;
use frontend\models\CheckingPlan;
use frontend\models\Edge;
use frontend\models\EdgeParameter;
use frontend\models\Injunction;
use frontend\models\InjunctionAttachment;
use frontend\models\InjunctionStatus;
use frontend\models\InjunctionViolation;
use frontend\models\OrderPermit;
use frontend\models\OrderPlace;
use frontend\models\OrderPlaceVtbAb;
use frontend\models\OrderTemplatePlace;
use frontend\models\Passport;
use frontend\models\Place;
use frontend\models\PlaceCompanyDepartment;
use frontend\models\PlaceOperation;
use frontend\models\PlaceRoute;
use frontend\models\SensorParameterHandbookValue;
use frontend\models\SensorParameterValue;
use frontend\models\Shift;
use frontend\models\StopPb;
use frontend\models\TypeObjectParameter;
use frontend\models\TypeObjectParameterHandbookValue;
use frontend\models\Worker;
use frontend\models\WorkingPlace;
use Throwable;
use Yii;
use yii\db\Query;
use yii\web\Controller;
use yii\web\Response;

class SuperTestController extends Controller
{
    // actionBuildTypicalObjectArray        -   метод получения списка типовых объектов системы
    // actionFindDepartment                 -   Метод для тестирования FindDepartment метода, суть метода - найти все нижележащие участки
    // actionUpdateEdgeLength               -   метод обновления длины выработок по их координатам (так же восстанавливает параметр 151 для эджей)
    // actionTestPost                       -   проверка работоспособности и связи с сервером ЭСМО
    // actionRemoveDoubleEmployeeWorker     -   метод по удалению дубликатов после синхронизации сап и АМИКУМ ( у кого табельный номер не равен worker_id)
    // actionTestCancelByEditForSensor      -   удаляет последнее справочное значение у группы сенсоров по парамтрам - 83, 122, 269, 346 (местонахождение, шахта, координаты, выработка)
    // actionGetListToPathModel             -   метод получения списка путей до 3д моделей по всем типовым объектам/по конкретному типовому объекту
    // actionUploadModelTest                -   Метод загрузки 3д изображения на сервер
    // actionSaveMessageRead                -   сохранение статуса прочтения сообщения для конкретного network_id
    // actionSendSafetyEmail                -   Рассылка email сообщений о произошедшем событии
    // GetFullNameAndRoles                  -   получение ФИО и ролей сотрудников
    // GetShifts                            -   получение таблицы названия смен и их краткого наименования
    // actionGettingCountWorker             -   найти количество сутрудников все нижележащие участки
    // actionTestJournalAbWebSocket         -   отправка пакета на вебсокет в журнал оператора АБ дублирующего контроля газов
    // actionGetEdgesRelation               -   проверка метода расчета зоны попадания эджа
    // actionGetEsmo                        -   получить данные ЭСМО за период
    // actionConvertFromJson                -   метод конвертирует json в читаемом формате
    // actionBindWorkerToEnterprise         -   разделение людей по группам на схеме (Воркутинская, Заполярная)
    // actionAddSensor                      -   тестирование метода добавления сенсоров
    // actionShowSlaveStatus                -   получить текущий статус репликации
    // actionStartSlave                     -   метод запуска репликации по каналу
    // actionStopSlave                      -   метод остановки репликации по каналу
    // actionSkipError                      -   метод пропуска ошибки при репликации
    // actionSensorParameterRecovery        -   метод восстановления недостоющего параметра у сенсоров
    // actionSensorParameterUpdate          -   метод обновления параметров сенсора
    // actionSensorParameterFailedDelete    -   метод удаления не верных параметров Справочные в измеренных и наоборот
    // actionGetConnectionStringYii         -   получение строк подключения из проекта Yii2
    // actionTestDbConnection               -   тестовый метод проверки работы подключения к БД
    // actionTestFindObject                 -   тестовый метод проверки поиска части в другой части
    // actionTestSortObject                 -   тестовый метод для отладки метода сортировки массива объектов
    // actionTestStringInteger              -   тестовый метод проверки поведения is_string и is_integer
    // actionTestCod                        -   тестовый метод проверки текущего состояния переменной ЦОД или шахта
    // actionTestDuplicateUpdate            -   тестовый метод проверки массовой вставки с учетом дубликатов
    // actionSensorRestorePlaceByEdge       -   метод восстановелния параметра места у сенсора по его выработке
    // actionUpdateEdgeCh                   -   метод обновления уставки CH4 выработок (ставит 1%)
    // actionUpdateEdgeCo                   -   метод обновления уставки CO выработок (ставит 0.0017)
    // actionUpdateEdgeSechenie             -   метод обновления сечения выработок (ставит 16)
    // actionCompareEdgeShemaDbCache        -   метод сравнения схемы шахты в БД и в КЕШЕ
    // actionFindAndDeleteDublicatePlace    -   метод поиска, замены и удаления дубликатов места генерируемых при синхронизации ППК ПАБ
    // UpdateProject                        -   метод для обновления проекта
    // actionDeleteOldSituations            -   метод тестирования очистки старых ситуаций
    // actionDeleteOldEvents                -   метод тестирования очистки старых событий

    // ChangeAllDcsStatus                   -   метод изминения статусов  всех служб в кэш
    // CheckDcsState                        -   Метод поиска последнего параметра по конкретной службе для определения статуса работы служб
    // CheckDcsStatus                       -   метод проверки статуса разрешения на запись конкретной службе
    // ChangeDcsStatus                      -   метод запрещает/разришает запись передаваемого ССД, что означает отключает/включает
    // actionGetNetworkIdByExternalId       -   метод тестирования результатов преобразования внешнего ключа страты в сетевой адрес метки
    // actionGetAllParentsCompanies         -   метод тестирования метода получения списка вложенных компаний
    // actionGetAllParentsCompaniesWithAttachment - метод тестирования метода получения списка вложенных компаний c самими вложениями
    // actionXmlRpc                         -   Метод вызова удаленной процедуры на исполнение с возвратом данных через очередь

    /**
     * ВОССТАНОВЛЕНИЕ ДАННЫХ
     */
    // actionRecoveryData                   -   метод восстановления данных из json лога

    /**
     * ТОРО
     */
    // actionMainToro       - главный метод синхронизации ТОРО
    // actionCopyToro       - главный копирования ТОРО
    // actionSynchToro      - метод синхронизации ТОРО

    /**
     * ПРЕДСМЕННЫЙ ЭКЗАМЕНАТОР
     */
    // actionMainPredExam   - главный метод синхронизации предсменного экзаменатора
    // actionSynchPredExam  - метод синхронизации Предсменного экзаменатора
    // actionCopyPredExam   - главный метод синхронизации Предсменного экзаменатора

    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод GetShifts() - получение таблицы названия смен и их краткого наименования
     * @param null $data_post - ничего не получает
     * @return array (список названий смен)
     *
     * @package frontend\controllers
     *
     * @example http://localhost/read-manager-amicum?controller=SuperTest&method=GetShifts&subscribe=login&data=
     *
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 04.07.2019 16:48
     */
    public static function GetShifts($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $result_one = array();                                                                                            // Промежуточный результирующий массив
        if ($data_post !== NULL && $data_post !== '') {
            $warnings[] = '. Данные успешно переданы';
            $warnings[] = '. Входной массив данных' . $data_post;
            try {
                $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
                $warnings[] = '. Декодировал входные параметры';
                if (
                    property_exists($post_dec, '')
                )                                                                                                    // Проверяем наличие в нем нужных нам полей
                {
                    $warnings[] = '.Данные с фронта получены';
                    // Для получения параметра - $post_dec->имя параметра;

                } else {
                    $errors[] = '. Переданы некорректные входные параметры';
                    $status *= 0;
                }
            } catch (Throwable $exception) {
                $errors[] = $exception->getMessage();
                $errors[] = $exception->getLine();
                $status *= 0;
            }
        } else {
            $result_one = Shift::find()//Обращаемся к модели
            ->all();                                                                                                    //Берем все содержащиеся данные в таблице

            $errors[] = '. Данные с фронта не получены';
            $status *= 0;
        }
        $result = $result_one;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Метод GetFullNameAndRoles() - получение ФИО и ролей сотрудников
     * @param null $data_post - идентификатор участка
     * @return array (ФИО и роли)
     *
     * @package frontend\controllers
     *
     * @example http://localhost/read-manager-amicum?controller=SuperTest&method=GetQuery&subscribe=&data={%22company_department_id%22:%224029865%22}
     *
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 05.07.2019
     */

    public static function GetFullNameAndRoles($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $worker_full_name_and_roles = array();                                                                                                 // Промежуточный результирующий массив
        if ($data_post !== NULL && $data_post !== '') {
            $warnings[] = 'GetQuery. Данные успешно переданы';
            $warnings[] = 'GetQuery. Входной массив данных' . $data_post;
            try {
                $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
                $warnings[] = 'GetQuery. Декодировал входные параметры';
                if (
                    property_exists($post_dec, 'company_department_id')
                )                                                                                                    // Проверяем наличие в нем нужных нам полей
                {
                    $warnings[] = 'GetQuery.Данные с фронта получены';
                    // Для получения параметра - $post_dec->имя параметра;

                    $company_department_id = $post_dec->company_department_id;                                                                                 //Подключаемся к модели и выбираем поля ФИО и роль
                    $worker_full_name_and_roles = Worker::find()
                        ->select(['employee.last_name', 'employee.first_name', 'employee.patronymic', 'role.title AS role_title'])//Выбираем поля
                        ->leftJoin('employee', 'worker.employee_id=employee.id')//Связываем таблицы
                        ->leftJoin('worker_object', 'worker.id=worker_object.worker_id')
                        ->leftJoin('role', 'worker_object.role_id=role.id')
                        ->where(['company_department_id' => $company_department_id])
                        ->asArray()
                        ->limit(50)
                        ->all();
                } else {
                    $errors[] = 'GetQuery. Переданы некорректные входные параметры';
                    $status = 0;
                }
            } catch (Throwable $exception) {
                $errors[] = $exception->getMessage();
                $errors[] = $exception->getLine();
                $status = 0;
            }
        } else {
            $errors[] = 'GetQuery. Данные с фронта не получены';
            $status = 0;
        }
        $result = $worker_full_name_and_roles;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // actionSendSafetyEmail -  Рассылка email сообщений о произошедшем событии
    // 127.0.0.1/super-test/send-safety-email?text="ответь пжл при получении в вотсап"
    public function actionSendSafetyEmail()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {
            $post = Assistant::GetServerMethod();
            $text = $post['text'];
            //$numbers = XmlController::SendSafetyEmail("опа опа", ['lv.kanev1@severstal.com']);
//            $numbers = XmlController::SendSafetyEmail("Дублирующий контроль газов!" . $text, ['mn.yakimov@pfsz.ru']);
            $result = XmlController::SendSafetyEmail("Тестовое сообщение от AMICUM 1", ['IshkovVS@uk.mechel.com']);
            $result = XmlController::SendSafetyEmail("Тестовое сообщение от AMICUM 2", ['mn.yakimov@pfsz.ru']);
            $result = XmlController::SendSafetyEmail("Тестовое сообщение от AMICUM 3", ['IshkovVS@uk.mechel.com', 'mn.yakimov@pfsz.ru']);
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }


    // actionSaveMessageRead - сохранение статуса прочтения сообщения для конкретного network_id
    public function actionSaveMessageRead()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {

            $result[] = StrataJobController::SaveMessageRead(date("Y-m-d H:i:s"), "59", "661025");
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;

    }

    /**
     * actionUploadModelTest    -   Метод загрузки 3д изображения на сервер
     * обязательные входные параметры:
     * file    -   имя файла
     * title    -   наименование объекта
     * type  -   тип картинки
     * object_id   -   id объекта
     *
     * выходные параметры:
     * Items    -   $result  -   результаты
     * url      -   $file_path     -   путь к файлу на сервере
     * parameters   -   $type_obj_parameters -   массив типовых параметров объекта
     * status   -   $status  -   статус выполнения метода (1|0)
     * errors   -   $errors  -   ошибки
     * warnings -   $warnings    -   отладочная информация
     *
     * Пример выплонения: localhost/super-test/upload-model-test?object_id=11
     *  Кендялова М.И.
     */
    public function actionUploadModelTest()
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();

        $url = null;
        $file_path = null;
        $upload_dir = 'img/3d_models/equipment/';   //директория, куда перемещать? файл
        $type_obj_parameters = array();

        try {
            $warnings[] = "actionUploadModelTest. Начал выполнять метод";

            //Блок проверки входных данных
            $post = Assistant::GetServerMethod();
            if (isset($_FILES['file'])
                and isset($post['title']) and $post['title'] != ""
                and isset($post['type']) and $post['type'] != ""
                and isset($post['object_id']) and $post['object_id'] != "") {
                $file = $_FILES['file'];                            //массив с информацией о загруженных файлах https://www.php.net/manual/ru/features.file-upload.post-method.php
                $object_title = $post['title'];                     //название объекта
                $image_type = $post['type'];                        //тип объекта
                $object_id = $post['object_id'];                    //id объекта
            } else {
                throw  new Exception("actionUploadModelTest. Не все параметры переданы");
            }

            //Блок формирования, проверки пути и перемещение файла
            $file_path = Assistant::UploadPicture($file, $upload_dir, $object_title, $image_type);                         //формируется путь к файлу
            if ($file_path == -1) {
                throw  new Exception("actionUploadModelTest. Имя файла является недопустимым");
            }
            $warnings[] = "actionUploadModelTest. имя файла сформировано верно";
            //поиск параметра типового объекта
            $type_obj_picture_parameter = TypeObjectParameter::findOne(['object' => $object_id, 'parameter_id' => 169, 'parameter_type_id' => 1]);
            //добавление параметра типовому объекту, если такого параметра у объекта не существует
            if (!$type_obj_picture_parameter) {
                $type_obj_picture_parameter = new TypeObjectParameter();
                $type_obj_picture_parameter->object_id = $object_id;
                $type_obj_picture_parameter->parameter_id = 169;
                $type_obj_picture_parameter->parameter_type_id = 1;
                if ($type_obj_picture_parameter->save()) {
                    $type_obj_picture_parameter->refresh();
                    $warnings[] = "actionUploadModelTest. обавлен 169 параметр типовому объекту";
                } else {
                    $errors[] = $type_obj_picture_parameter->errors;
                    throw  new Exception("actionUploadModelTest. Не удалось сохранить параметр типового объекта");
                }
            }
            //запись справочного значения параметра типового объекта
            $type_obj_handbook_value = new TypeObjectParameterHandbookValue();
            $type_obj_handbook_value->type_object_parameter_id = $type_obj_picture_parameter->id;
            $type_obj_handbook_value->date_time = Assistant::GetDateNow();
            $type_obj_handbook_value->value = $file_path;
            $type_obj_handbook_value->status_id = 1;
            if (!$type_obj_handbook_value->save()) {
                $type_obj_handbook_value->errors;
                throw  new Exception("actionUploadModelTest. Не удалось сохранить путь в справочное значение объекта");
            } else {
                $warnings[] = "actionUploadModelTest. сохранено справочное значение типового объекта";
            }
            // как $object_id может быть ===null? Ведь не была бы пройдена проверка выше
            // Оператор идентичности === проверяет, являются ли переменные равные и при этом имеют одинаковый тип.
            // $type_obj_parameters = $object_id === null ? array() : $this->buildTypeObjectParametersArray($object_id);
            if ($object_id === null) {
                $type_obj_parameters = array();
            } else {
                $type_obj_parameters = new HandbookTypicalObjectController();
                $type_obj_parameters->buildTypeObjectParametersArray($object_id);
            }

        } catch (\Throwable $e) {
            $errors[] = "actionUploadModelTest. Исключение";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
            $status = 0;
        }
        $warnings[] = "actionUploadModelTest. Закончил выполнять метод";
        $result_main = array('Items' => $result,
            "url" => $file_path,
            'parameters' => $type_obj_parameters,
            'status' => $status,
            'errors' => $errors,
            'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * actionGetListToPathModel - метод получения списка путей до 3д моделей по всем типовым объектам/по конкретному типовому объекту
     * входные данные:
     * object_id - id Типового объекта
     *
     * выходные даные:
     * object_id
     * value    -   путь до 3д модели
     * Пример выполнения: localhost/super-test/get-list-to-path-model?object_id=121
     * Выполнила: Кендялова М.И.
     */

    public function actionGetListToPathModel()
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();

        $object_id = null;
        try {
            $warnings[] = "actionGetListToPathModel. Начал выполнять метод";

            $post = Assistant::GetServerMethod();                                                                       //получение и проверки входных значений
            if (isset($post['object_id']) and $post['object_id'] != 0) {
                $object_id = $post['object_id'];
                $string_to_path_model = (new Query())//получение данных из БД если есть конкретный $object_id
                ->select(['object_id', 'value'])
                    ->from(['view_Get3DModelTypicalObjectLast'])
                    ->where(['object_id' => $object_id])
                    ->indexBy(['object_id'])
                    ->one();
                $result = $string_to_path_model;
            } else {
                $string_to_path_model = (new Query())// получение данных из БД по всем $object_id
                ->select(['object_id', 'value'])
                    ->from(['view_Get3DModelTypicalObjectLast'])
                    ->indexBy(['object_id'])
                    ->all();
                $result = $string_to_path_model;
            }
        } catch (\Throwable $e) {
            $errors[] = "actionGetListToPathModel. Исключение";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
            $status = 0;
        }
        $warnings[] = "actionGetListToPathModel. Закончил выполнять метод";
        $result_main = array('Items' => $result,
            'status' => $status,
            'errors' => $errors,
            'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }



    /** Метод actionTestCancelByEditForSensor() -  удаляет последнее справочное значение у группы сенсоров по парамтрам - 83, 122, 269, 346 (местонахождение, шахта, координаты, выработка)
     * Входные параметры: -
     * Тестирование с помощью метода actionTestCancelByEditForSensor - http://localhost/read-manager-amicum?controller=SuperTest&method=actionTestCancelByEditForSensor&subscribe=&data={}
     * Выходные данные по результату - количество удаленных записей
     *
     *
     * @author Митяева Лидия <mla@pfsz.ru>
     * Сreated date: on 27.08.2019 9:00
     *
     */
    // 127.0.0.1/super-test/test-cancel-by-edit-for-sensor
    public function actionTestCancelByEditForSensor()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {
            $sensors_ids = json_encode(array("sensors_ids" => [140048, 140050]));                                       //Входные данные - id сенсоров
            $response = SensorMainController::CancelByEditForSensor($sensors_ids);                                      //вызов метода
            /****************** Формирование ответа ******************/
            if ($response['status'] == 1) {
                $result = $response['Items'];
                $status = $response['status'];
                $errors = $response['errors'];
                $warnings = $response['warnings'];
            } else {
                $errors = $response['errors'];
                $warnings = $response['warnings'];
                throw new Exception('actionTestCancelByEditForSensor. метод CancelByEditForSensor завершлся с ошибкой');
            }
        } catch (Throwable $exception) {
            $errors[] = "actionTestCancelByEditForSensor. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;

    }


    /**
     * Метод actionGettingCountWorker() - Метод для тестирования GettingCountWorker метода, суть метода - найти количество сутрудников все нижележащие участки
     * @return array
     *
     *Выходные параметры:
     *
     * @package frontend\controllers
     *Входные обязательные параметры: JSON со структрой:
     * @example http://localhost/read-manager-amicum?controller=SuperTest&method=actionGettingCountWorker&subscribe=&data={}
     *
     * @author Митяева Лидия <mla@pfsz.ru>
     * Created date: on 27.09.2019 9:46
     */
    public function actionGettingCountWorker()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {
            $company_department_id = json_encode(4029926);//Входные данные - id сенсоров
            $response = DepartmentController::GettingCountWorker($company_department_id);

            /****************** Формирование ответа ******************/
            if ($response['status'] == 1) {
                $result = $response['Items'];
                $status = $response['status'];
                $errors = $response['errors'];
                $warnings = $response['warnings'];
            } else {
                $errors = $response['errors'];
                $warnings = $response['warnings'];
                throw new Exception('actionGettingCountWorker. метод GettingCountWorker завершлся с ошибкой');
            }
        } catch (Throwable $exception) {
            $errors[] = "actionGettingCountWorker. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;

    }

    /**
     * Метод actionFindDepartment() - Метод для тестирования FindDepartment метода, суть метода - найти все нижележащие участки
     * @return array
     *
     *Выходные параметры:
     *
     * @package frontend\controllers
     *Входные обязательные параметры: JSON со структрой:
     * @example http://localhost/read-manager-amicum?controller=SuperTest&method=actionFindDepartment&subscribe=&data=4029926
     *
     * @author Митяева Лидия <mla@pfsz.ru>
     * Created date: on 27.09.2019 9:46
     */
    public static function actionFindDepartment($company_department_id = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {
            $response = DepartmentController::FindDepartment($company_department_id);

            /****************** Формирование ответа ******************/
            if ($response['status'] == 1) {
                $result = $response['Items'];
                $status = $response['status'];
                $errors = $response['errors'];
                $warnings = $response['warnings'];
            } else {
                $errors = $response['errors'];
                $warnings = $response['warnings'];
                throw new Exception('actionFindDepartment. метод FindDepartment завершлся с ошибкой');
            }
        } catch (Throwable $exception) {
            $errors[] = "actionFindDepartment. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;

    }


    public function actionTestEcho()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {
            $result_item['IP'] = "192.168.0.1";
            $result_item['portSNMP'] = "2101";
            $result_item['sensor_id'] = 1;
            $result_item['sensor_parameter_id'] = 2;
            $result[] = $result_item;
        } catch (Throwable $exception) {
            $errors[] = "actionTestEcho. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;

    }

    /**
     * Метод actionRemoveDoubleEmployeeWorker() - метод по удалению дубликатов после синхронизации сап и АМИКУМ ( у кого табельный номер не равен worker_id)
     * @package frontend\controllers
     * @example 127.0.0.1/super-test/remove-double-employee-worker
     *
     * @author Коренева К.А. <kka@pfsz.ru>
     * Created date: on 13.08.2019 9:59
     */
    public function actionRemoveDoubleEmployeeWorker()
    {
        $result = SynchronizationController::RemoveDoubleEmployee()();
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * Метод actionTestPost() - проверка работоспособности и связи с сервером ЭСМО
     * пример: http://127.0.0.1/super-test/test-post-esmo
     */
    public function actionTestPostEsmo()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {

            $arrContextOptions = array(
                "ssl" => array(
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ),
            );
            $response = file_get_contents('https://' . ESMO . '/services/vorkuta/get/get_mo_data/json/', false, stream_context_create($arrContextOptions));
            $response = json_decode($response, true);
            $result = $response['Items'];
            $status = $response['status'];
            $errors = $response['errors'];
            $warnings = $response['warnings'];

        } catch (Throwable $exception) {
            $errors[] = "actionTestPostEsmo. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод actionTestJournalAbWebSocket() - отправка пакета на вебсокет в журнал оператора АБ дублирующего контроля газов
     * пример: http://127.0.0.1/super-test/test-journal-ab-web-socket?situation_journal_id=
     */
    public function actionTestJournalAbWebSocket($situation_journal_id)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $method_name = "actionTestJournalAbWebSocket";                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {
            $date_time = Assistant::GetDateNow();
            $sub_pub_list = "addNewSituationJournal";
            $situation_current_to_ws = array(
                'situation_journal_id' => $situation_journal_id,                                                        // ключ журнала ситуации
                'situation_id' => 1,                                                                                    // ключ ситуации
                'situation_title' => '',                                                                                // название ситуации
                'mine_id' => 1,                                                                                         // ключ шахты
                'mine_title' => "Тестовая шахта",                                                                       // название шахты
                'status_checked' => 0,                                                                                  // статус проверки
                'situation_date_time' => date('d.m.Y H:i:s', strtotime($date_time)),                            // время создания ситуации
                'situation_date_time_format' => date('d.m.Y H:i:s', strtotime($date_time)),                     // время создания ситуации форматированное
                'object_id' => null,                                                                                    // ключ работника
                'object_title' => '',                                                                                   // ФИО работника
                'edge_id' => null,                                                                                      // выработка в которой произошла ситуация
                'place_id' => 0,                                                                                        // место ситуации
                'status_id' => null,                                                                                    // статус значения (нормальное/ аварийное)
                'sensor_value' => 5,                                                                                    // значение концентрации газа
                'kind_reason_id' => null,                                                                               // вид причины опасного действия
                'status_date_time' => date('d.m.Y H:i:s', strtotime($date_time)),                               // время изменения статуса ситуации
                'situation_status_id' => '',                                                                            // текущий статус ситуации (принята в работу, устранена и т.д.)
                'duration' => null,                                                                                     // продолжительность ситуации
                'statuses' => [],                                                                                       // список статусов (история изменения ситуации)
                'gilties' => [],                                                                                        // список виновных
                'operations' => [],                                                                                     // список принятых мер
                'event_journals' => (object)array(),                                                                    // список журнала событий ситуации
                'object_table' => ""                                                                                    // таблица в котрой лежит объект (сенсор, воркер)
            );
            $response = WebsocketController::SendMessageToWebSocket($sub_pub_list, $situation_current_to_ws);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . '. Ошибка отправки данных на вебсокет. Подписка: ' . $sub_pub_list);
            }

            $status = $response['status'];
            $errors = $response['errors'];
            $warnings = $response['warnings'];

        } catch (Throwable $exception) {
            $errors[] = $method_name . ". Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод actionGetEdgesRelation() - проверка метода расчета зоны попадания эджа
     * пример: http://127.0.0.1/super-test/get-edges-relation?edge_id=26317
     */
    public function actionGetEdgesRelation($edge_id)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $method_name = "actionGetEdgesRelation";                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $debug = array();
        $result = array();
        try {

            $response = EdgeMainController::GetEdgesRelation($edge_id, 290, 50, "11529.2,-482.16,-11336.11");
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $result = $response['Items'];
                $debug = $response['debug'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $debug = $response['debug'];
                throw new Exception($method_name . '. Ошибка получения зоны выработки: ' . $edge_id);
            }


        } catch (Throwable $exception) {
            $errors[] = $method_name . ". Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод actionBindWorkerToEnterprise() - разделение людей по группам на схеме (Воркутинская, Заполярная)
     * пример: http://127.0.0.1/super-test/bind-worker-to-enterprise
     */
    public function actionBindWorkerToEnterprise()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $method_name = "actionBindWorkerToEnterprise";                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $debug = array();
        $result = array();
        try {

            $response = WorkerMainController::bindWorkerToEnterprise(4029926, 4029860);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $result = $response['Items'];
                $debug = $response['debug'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . '. Ошибка разделения людей по департаментам: ');
            }


        } catch (Throwable $exception) {
            $errors[] = $method_name . ". Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод actionUpdateEdgeLength() - метод обновления длины выработок по их координатам (так же восстанавливает параметр 151 для эджей) расчет протяженности горных выработок
     * алгоритм:
     *      1. Получить список эджей и их сопряжений с координатам
     *      2. получить для списка эджей параметр длины и если его нет, то создать
     *      3. вычислить новую длину эджа
     *      4. Положить новую длину в БД
     * пример: http://127.0.0.1/super-test/update-edge-length
     */
    public function actionUpdateEdgeLength()
    {
        // Стартовая отладочная информация
        $method_name = 'actionUpdateEdgeLength';                                                                             // название логируемого метода
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

            $result = self::UpdateEdgeLength();

        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
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

        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    public static function UpdateEdgeLength($mine_id = null, $date_time = null)
    {
        // Стартовая отладочная информация
        $method_name = 'UpdateEdgeLength';                                                                             // название логируемого метода
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
        $count_edge = 0;                    // общее количество эджей
        $count_new_parameter_length = 0;    // количество созданных параметров
        $count_edge_save = 0;               // количество вставленных записей в БД
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

            $filter = null;
            if ($mine_id) {
                $filter = ['place.mine_id' => $mine_id];
            }
            $edges = (new Query())
                ->select('
                    edge.id as edge_id,
                    conjStart.x as xStart,
                    conjStart.y as yStart,
                    conjStart.z as zStart,
                    conjEnd.x as xEnd,
                    conjEnd.y as yEnd,
                    conjEnd.z as zEnd,
                ')
                ->from('edge')
                ->innerJoin('place', 'place.id=edge.place_id')
                ->innerJoin('conjunction conjStart', 'edge.conjunction_start_id=conjStart.id')
                ->innerJoin('conjunction conjEnd', 'edge.conjunction_end_id=conjEnd.id')
                ->where($filter)
                ->all();
            if (!$edges) {
                throw new Exception('UpdateEdgeLength. Список выработок пуст');
            }
            $count_edge = count($edges);
            $edges_parameter_length = (new Query())
                ->select('
                    id,
                    edge_id
                ')
                ->from('edge_parameter')
                ->where(['parameter_type_id' => 1, 'parameter_id' => 151])
                ->indexBy('edge_id')
                ->all();
            if (!$date_time) {
                $date_time = Assistant::GetDateNow();
            }

            foreach ($edges as $edge) {
                if (!isset($edges_parameter_length[$edge['edge_id']])) {
                    $new_edge_parameter = new EdgeParameter();
                    $new_edge_parameter->edge_id = $edge['edge_id'];
                    $new_edge_parameter->parameter_id = ParamEnum::LENGTH;
                    $new_edge_parameter->parameter_type_id = ParameterTypeEnumController::REFERENCE;
                    if (!$new_edge_parameter->save()) {
                        throw new Exception('UpdateEdgeLength. Не смог сохранить параметр 151/1 в EdgeParameter');
                    }
                    $new_edge_parameter->refresh();
                    $edges_parameter_length[$edge['edge_id']]['id'] = $new_edge_parameter->id;
                    $edges_parameter_length[$edge['edge_id']]['edge_id'] = $edge['edge_id'];
                    $count_new_parameter_length++;
                }

                $lenght = OpcController::calcDistance(
                    $edge['xStart'], $edge['yStart'], $edge['zStart'],
                    $edge['xEnd'], $edge['yEnd'], $edge['zEnd']
                );

                $length_to_db[] = array(
                    'edge_parameter_id' => $edges_parameter_length[$edge['edge_id']]['id'],
                    'date_time' => $date_time,
                    'value' => $lenght,
                    'status_id' => 1
                );
            }
            if (isset($length_to_db)) {
                $count_edge_save = Yii::$app->db->createCommand()->batchInsert('edge_parameter_handbook_value', ['edge_parameter_id', 'date_time', 'value', 'status_id'], $length_to_db)->execute();
                $warnings[] = $method_name . '. Количество вставленных записей в edge_parameter_handbook_value' . $count_edge_save;
            }
//            $result=$edges;


        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        // запись в БД начала выполнения скрипта
        $warnings[] = array(
            '$count_edge. Общее количество эджей' => $count_edge,
            '$count_new_parameter_length. Количество созданных парамтеров длины эджей' => $count_new_parameter_length,
            '$count_edge_save. Количество вставленных данных в БД' => $count_edge_save,
        );

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

        return array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
    }


    /**
     * Метод actionGetEsmo() - получить данные ЭСМО за период
     * входные параметры:
     * $date_start_synch_format - дата начала выборки
     * $date_end - дата окончания выборки
     * выходные параметры:
     * стандартный набор
     *
     * http://10.36.51.8/super-test/get-esmo?date_start_synch_format=16.03.2020&date_end=18.03.2020
     */
    public static function actionGetEsmo($date_start_synch_format, $date_end)
    {
        $arrContextOptions = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            ),
        );
        $response = file_get_contents('https://' . ESMO . '/services/vorkuta/get/get_mo_data/json/?data_from=' . rawurlencode($date_start_synch_format) . '&data_to=' . rawurlencode($date_end), false, stream_context_create($arrContextOptions));
        $esmos_without_group = json_decode($response);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $esmos_without_group;
    }

    // actionConvertFromJson - метод конвертирует json в читаемом формате
    // входные параметры:
    //      json            - декодируемая строка
    // 127.0.0.1/super-test/convert-from-json?json=
    public function actionConvertFromJson()
    {
        $post = Assistant::GetServerMethod();
        $response = json_decode($post['json']);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $response;
    }

    // actionAddSensor - тестирование метода добавления сенсоров
    // 127.0.0.1/super-test/add-sensor
    public function actionAddSensor($sensor_title, $object_id, $asmtp_id, $sensor_type_id, $mine_id)
    {
//        $response = SensorBasicController::addSensor("V)", 91, 1, 1, 290);
        $response = SensorBasicController::addSensor($sensor_title, $object_id, $asmtp_id, $sensor_type_id, $mine_id);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $response;
    }

    // actionShowSlaveStatus - получить текущий статус репликации
    // 127.0.0.1/super-test/show-slave-status
    public function actionShowSlaveStatus()
    {
        $result = array();
        $slave_statuses = Yii::$app->db_replication->createCommand('SHOW SLAVE STATUS')->queryAll();
        foreach ($slave_statuses as $slave_status) {
            $slave_status['Replicate_Ignore_Table'] = "";                                                               // игнорируется поле, т.к. не может распарсится на фронте из за спец символов
            $result[] = $slave_status;
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    // actionStartSlave - метод запуска репликации по каналу
    // 127.0.0.1/super-test/start-slave?channel=zapolyar2
    public function actionStartSlave()
    {
        $post = Assistant::GetServerMethod();
        $channel = $post['channel'];
        $slave_status = Yii::$app->db_replication->createCommand("START SLAVE FOR CHANNEL '" . $channel . "'")->execute();
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $slave_status;
    }

    // actionStopSlave - метод остановки репликации по каналу
    // 127.0.0.1/super-test/stop-slave?channel=zapolyar2
    public function actionStopSlave()
    {
        $post = Assistant::GetServerMethod();
        $channel = $post['channel'];
        $slave_status = Yii::$app->db_replication->createCommand("STOP SLAVE FOR CHANNEL '" . $channel . "'")->execute();
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $slave_status;
    }

    // actionSkipError - метод пропуска ошибки при репликации
    // 127.0.0.1/super-test/skip-error?channel=zapolyar2&next_step=hhh
    public function actionSkipError()
    {
        $post = Assistant::GetServerMethod();

        $channel = $post['channel'];                                                                                    // канал репликации
        $next_step = $post['next_step'];                                                                                // следующий шаг репликации

//        $slave_status['post'] = $post;
//        $slave_status['остановил репликацию'] = Yii::$app->db_replication->createCommand("STOP SLAVE FOR CHANNEL '" . $channel . "'")->execute();
        $slave_status = Yii::$app->db_replication->createCommand("SET GTID_NEXT='$next_step'; begin; commit; set GTID_NEXT='AUTOMATIC';")->execute();
        Yii::$app->db_replication->createCommand("START SLAVE FOR CHANNEL '" . $channel . "'")->execute();

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $slave_status;
    }

    // actionTestSortObject - тестовый метод для отладки метода сортировки массива объектов
    // 127.0.0.1/super-test/test-sort-object
    public function actionTestSortObject()
    {
        $source = array();
        $source[22410] = 22410;
        $source[7130] = 7130;
        $source[7150] = 7150;
        $source[7120] = 7120;
        asort($source);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $source;
    }

    // actionTestFindObject - тестовый метод проверки поиска части в другой части
    // 127.0.0.1/super-test/test-find-object
    public function actionTestFindObject()
    {
        $source = strripos("71302240", "713022409");

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $source;
    }

    // actionTestDbConnection - тестовый метод проверки работы подключения к БД
    // 127.0.0.1/super-test/test-db-connection
    public function actionTestDbConnection()
    {
        $db = "db_source";
        $result = Yii::$app->$db->createCommand("SELECT id FROM briefing_reason limit 25000")->queryAll();

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    // actionSensorParameterRecovery - метод восстановления недостающего параметра у сенсоров
    // находит все сенсоры у которых нет данного параметра, создает его, добавляет ему значение и записывает в кеш
    // на вход принимает parameter_id и parameter_type_id и value
    // для базовых параметров значения берутся из таблицы sensor
    // 127.0.0.1/super-test/sensor-parameter-recovery?parameter_type_id=1&parameter_id=274&value=-1
    public function actionSensorParameterRecovery()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // массив ошибок
        $result = array();                                                                                              // результирующий массив
        $count = 0;                                                                                                       // количество добавленных записей
        $count_all = 0;                                                                                                   // количество записей всего
        try {
            $warnings[] = "actionSensorParameterRecovery. Начал выполнять метод";
            $post = Assistant::GetServerMethod();
            if ($post['parameter_id'] == "" or $post['parameter_type_id'] == "" or $post['value'] == "") {
                throw new Exception("actionSensorParameterRecovery. Входные параметры не найдены");
            }

            $parameter_id = $post['parameter_id'];                                                                      // ключ параметра
            $parameter_type_id = $post['parameter_type_id'];                                                            // ключ типа параметра
            $value = $post['value'];                                                            // ключ типа параметра

            $sensors = (new Query())
                ->select(
                    'sensor.id as sensor_id,
                        sensor.object_id,
                        sensor.asmtp_id,
                        sensor.sensor_type_id,
                        sensor.title,
                        ')
                ->from('sensor')
                ->leftJoin('(select id, sensor_id from sensor_parameter where parameter_id=' . $parameter_id . ' and parameter_type_id=' . $parameter_type_id . ') sensor_parameter1', 'sensor_parameter1.sensor_id=sensor.id')
                ->where(['is', 'sensor_parameter1.id', null])
                ->all();
            $datetime = Assistant::GetDateNow();
            $status_id = 1;
            foreach ($sensors as $sensor) {
                $sensor_id = $sensor['sensor_id'];
                if ($parameter_id == 274) {
                    $value = $sensor['object_id'];
                } else if ($parameter_id == 162) {
                    $value = $sensor['title'];
                } else if ($parameter_id == 337) {
                    $value = $sensor['asmtp_id'];
                } else if ($parameter_id == 338) {
                    $value = $sensor['sensor_type_id'];
                }
                // создаем конкретный параметр в базе данных
                $response = SensorBasicController::addSensorParameter($sensor_id, $parameter_id, $parameter_type_id);
                if ($response['status'] == 1) {
                    $sensor_parameter_id = $response['sensor_parameter_id'];
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception("actionSensorParameterRecovery. Для сенсора $sensor_id не существует привязки к нему параметра $parameter_id и типа параметра $parameter_type_id");
                }

                /**
                 * Сохранение значения параметра в БД
                 */
                if ($parameter_type_id == 2 or $parameter_type_id == 3) {
                    $response = SensorBasicController::addSensorParameterValue($sensor_parameter_id, $value, $status_id, $datetime);
                    if ($response['status'] == 1) {
                        $value_database_id = $response['sensor_parameter_value_id'];
                    } else {
                        $errors[] = $response['errors'];
                        //$warnings[] = $response['warnings'];
                        throw new Exception("actionSensorParameterRecovery. Ошибка при сохранении значения в БД параметра $parameter_id-$parameter_type_id сенсора $sensor_id");
                    }
                } else if ($parameter_type_id == 1) {
                    $response = SensorBasicController::addSensorParameterHandbookValue($sensor_parameter_id, $value, $status_id, $datetime);
                    if ($response['status'] == 1) {
                        $value_database_id = $response['sensor_parameter_value_id'];
                    } else {
                        $errors[] = $response['errors'];
                        //$warnings[] = $response['warnings'];
                        throw new Exception("actionSensorParameterRecovery. Ошибка при сохранении значения в БД параметра $parameter_id-$parameter_type_id сенсора $sensor_id");
                    }
                } else {
                    throw new Exception("actionSensorParameterRecovery. Не известный тип параметра");
                }

                /**
                 * Сохранение значения параметра в кеш
                 */
                $response = (new SensorCacheController())->setSensorParameterValueHash(
                    $sensor_id,
                    $sensor_parameter_id,
                    $value,
                    $parameter_id,
                    $parameter_type_id,
                    $status_id,
                    $datetime
                );
                $count++;
            }
            $count_all = count($sensors);
            $result = $sensors;

        } catch (\Throwable $exception) {
            $errors[] = "actionSensorParameterRecovery. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = "actionSensorParameterRecovery. $count шт. Количество обработанных записей";
        $warnings[] = "actionSensorParameterRecovery. $count_all шт. Количество всего записей";

        $warnings[] = "actionSensorParameterRecovery. Закончил выполнять метод";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // actionSensorParameterUpdate - метод обновления параметров сенсора
    // находит все сенсоры у которых есть данный параметр, добавляет ему значение и записывает в кеш
    // на вход принимает parameter_id и parameter_type_id и value
    // для базовых параметров значения берутся из таблицы sensor
    // 127.0.0.1/super-test/sensor-parameter-update?parameter_type_id=1&parameter_id=274&value=-1
    public function actionSensorParameterUpdate()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // массив ошибок
        $result = array();                                                                                              // результирующий массив
        $count = 0;                                                                                                       // количество добавленных записей
        $count_all = 0;                                                                                                   // количество записей всего
        try {
            $warnings[] = "actionSensorParameterUpdate. Начал выполнять метод";
            $post = Assistant::GetServerMethod();
            if ($post['parameter_id'] == "" or $post['parameter_type_id'] == "" or $post['value'] == "") {
                throw new Exception("actionSensorParameterUpdate. Входные параметры не найдены");
            }

            $parameter_id = $post['parameter_id'];                                                                      // ключ параметра
            $parameter_type_id = $post['parameter_type_id'];                                                            // ключ типа параметра
            $value = $post['value'];                                                            // ключ типа параметра

            $sensors = (new Query())
                ->select(
                    'sensor.id as sensor_id,
                        sensor.object_id,
                        sensor.asmtp_id,
                        sensor.sensor_type_id,
                        sensor.title,
                        sensor_parameter.id as sensor_parameter_id
                        ')
                ->from('sensor')
                ->innerJoin('sensor_parameter', 'sensor_parameter.sensor_id=sensor.id')
                ->where(['parameter_id' => $parameter_id, 'parameter_type_id' => $parameter_type_id])
                ->all();
            $datetime = Assistant::GetDateNow();
            $status_id = 1;
            foreach ($sensors as $sensor) {
                $sensor_id = $sensor['sensor_id'];
                $sensor_parameter_id = $sensor['sensor_parameter_id'];
                if ($parameter_id == 274) {
                    $value = $sensor['object_id'];
                } else if ($parameter_id == 162) {
                    $value = $sensor['title'];
                } else if ($parameter_id == 337) {
                    $value = $sensor['asmtp_id'];
                } else if ($parameter_id == 338) {
                    $value = $sensor['sensor_type_id'];
                }

                /**
                 * Сохранение значения параметра в БД
                 */
                if ($parameter_type_id == 2 or $parameter_type_id == 3) {
                    $response = SensorBasicController::addSensorParameterValue($sensor_parameter_id, $value, $status_id, $datetime);
                    if ($response['status'] == 1) {
                        $value_database_id = $response['sensor_parameter_value_id'];
                    } else {
                        $errors[] = $response['errors'];
                        //$warnings[] = $response['warnings'];
                        throw new Exception("actionSensorParameterUpdate. Ошибка при сохранении значения в БД параметра $parameter_id-$parameter_type_id сенсора $sensor_id");
                    }
                } else if ($parameter_type_id == 1) {
                    $response = SensorBasicController::addSensorParameterHandbookValue($sensor_parameter_id, $value, $status_id, $datetime);
                    if ($response['status'] == 1) {
                        $value_database_id = $response['sensor_parameter_value_id'];
                    } else {
                        $errors[] = $response['errors'];
                        //$warnings[] = $response['warnings'];
                        throw new Exception("actionSensorParameterUpdate. Ошибка при сохранении значения в БД параметра $parameter_id-$parameter_type_id сенсора $sensor_id");
                    }
                } else {
                    throw new Exception("actionSensorParameterUpdate. Не известный тип параметра");
                }

                /**
                 * Сохранение значения параметра в кеш
                 */
                $response = (new SensorCacheController())->setSensorParameterValueHash(
                    $sensor_id,
                    $sensor_parameter_id,
                    $value,
                    $parameter_id,
                    $parameter_type_id,
                    $status_id,
                    $datetime
                );
                $count++;
            }
            $count_all = count($sensors);
            $result = $sensors;

        } catch (\Throwable $exception) {
            $errors[] = "actionSensorParameterUpdate. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = "actionSensorParameterUpdate. $count шт. Количество обработанных записей";
        $warnings[] = "actionSensorParameterUpdate. $count_all шт. Количество всего записей";

        $warnings[] = "actionSensorParameterUpdate. Закончил выполнять метод";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // actionSensorParameterFailedDelete - метод удаления не верных параметров
    // находит все сенсоры у которых значения параметров лежат не в тех таблица и удаляет их от туда
    // пример: справочные параметры лежат в измеренных и наоборот
    // на вход принимает parameter_id и parameter_type_id и value
    // для базовых параметров значения берутся из таблицы sensor
    // 127.0.0.1/super-test/sensor-parameter-failed-delete?parameter_id=274
    public function actionSensorParameterFailedDelete()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // массив ошибок
        $result = array();                                                                                              // результирующий массив
        $count['SensorParameterValue'] = 0;                                                                             // количество удаленных измеренных записей
        $count['SensorParameterHandbookValue'] = 0;                                                                     // количество удаленных справочных записей
        $count_all = 0;                                                                                                 // количество записей всего
        try {
            $warnings[] = "actionSensorParameterFailedDelete. Начал выполнять метод";
            $post = Assistant::GetServerMethod();
            if ($post['parameter_id'] == "") {
                throw new Exception("actionSensorParameterFailedDelete. Входные параметры не найдены");
            }

            $parameter_id = $post['parameter_id'];                                                                      // ключ параметра
            if ($parameter_id == "*") {
                $parameter_id = null;
            }

            // проверяем справочные и удаляем из измеренных
            $failed_values = (new Query())
                ->select('
                        sensor_parameter_value.id as sensor_parameter_value_id
                        ')
                ->from('sensor_parameter')
                ->innerJoin('sensor_parameter_value', 'sensor_parameter_value.sensor_parameter_id=sensor_parameter.id')
                ->where(['parameter_type_id' => 1])
                ->andFilterWhere(['parameter_id' => $parameter_id])
                ->all();

            foreach ($failed_values as $failed_value) {
                SensorParameterValue::deleteAll(['id' => $failed_value['sensor_parameter_value_id']]);
                $count['SensorParameterValue']++;
            }
            $count_all = count($failed_values);

            unset($failed_values);
            unset($failed_value);

            // проверяем измеренные и удаляем из справочных
            $failed_values = (new Query())
                ->select('
                        sensor_parameter_handbook_value.id as sensor_parameter_handbook_value_id
                        ')
                ->from('sensor_parameter')
                ->innerJoin('sensor_parameter_handbook_value', 'sensor_parameter_handbook_value.sensor_parameter_id=sensor_parameter.id')
                ->where(['parameter_type_id' => 2])
                ->andFilterWhere(['parameter_id' => $parameter_id])
                ->all();

            foreach ($failed_values as $failed_value) {
                SensorParameterHandbookValue::deleteAll(['id' => $failed_value['sensor_parameter_handbook_value_id']]);
                $count['SensorParameterHandbookValue']++;
            }

            $count_all += count($failed_values);

            unset($failed_values);
            unset($failed_value);

        } catch (\Throwable $exception) {
            $errors[] = "actionSensorParameterFailedDelete. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = $count;
        $warnings[] = "actionSensorParameterFailedDelete. $count_all шт. Количество всего записей";

        $warnings[] = "actionSensorParameterFailedDelete. Закончил выполнять метод";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }


    // actionGetConnectionStringYii - получение строк подключения из проекта Yii2
    // {connect_string}
    //      ip               -   адрес базы данных
    //      dbname           -   имя БД
    //      connect_string   -   строка подключения в Yii2
    // 127.0.0.1/super-test/get-connection-string-yii
    public function actionGetConnectionStringYii()
    {
        $result = array();
        $connect_strings = Yii::$app->components;
        foreach ($connect_strings as $key => $connect_string) {
            if (isset($connect_string['dsn'])) {
                $ip = Assistant::getDsnAttribute('host', $connect_string['dsn']);
                $dbname = Assistant::getDsnAttribute('dbname', $connect_string['dsn']);
                $result[$key] = array('ip' => $ip, 'dbname' => $dbname, 'connect_string' => $key);
            }
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    // actionTestStringInteger - тестовый метод проверки поведения is_string и is_integer
    // 127.0.0.1/super-test/test-string-integer
    public function actionTestStringInteger()
    {
        $source['is_string - строка'] = is_string("286");
        $source['is_string - число'] = is_string(286);

        $source['is_integer - строка'] = is_integer((int)"286");
        $source['is_integer - число'] = is_integer(286);
        if (!COD) {
            $source['false'] = 'mine';
        } else {
            $source['true'] = 'cod';
        }


        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $source;
    }

    // actionTestCod - тестовый метод проверки текущего состояния переменной ЦОД или шахта
    // 127.0.0.1/super-test/test-cod
    public function actionTestCod()
    {
        if (!COD) {
            $source['false'] = 'mine';
        } else {
            $source['true'] = 'cod';
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $source;
    }

    // actionTestDuplicateUpdate - тестовый метод проверки массовой вставки с учетом дубликатов
    // 127.0.0.1/super-test/test-duplicate-update
    public function actionTestDuplicateUpdate()
    {
        $method_name = "actionTestDuplicateUpdate";
        $data_to_db[] = array(
            'worker_parameter_id' => 40416,
            'sensor_id' => 26492,
            'date_time' => "2019-09-11 16:02:13",
            'type_relation_sensor' => 1,
        );

        if (!empty($data_to_db)) {
            $warnings[] = $data_to_db;

            $builder_data_to_db = Yii::$app->db_target->queryBuilder->batchInsert('worker_parameter_sensor', ['worker_parameter_id', 'sensor_id', 'date_time', 'type_relation_sensor'], $data_to_db);
            $insert_result_to_MySQL = Yii::$app->db_target->createCommand($builder_data_to_db . " ON DUPLICATE KEY UPDATE `type_relation_sensor` = VALUES (`type_relation_sensor`)")->execute();


        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $insert_result_to_MySQL;
    }

    // actionSensorRestorePlaceByEdge - метод восстановелния параметра места у сенсора по его выработке
    // 127.0.0.1/super-test/sensor-restore-place-by-edge
    public function actionSensorRestorePlaceByEdge()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // массив ошибок
        $result = array();                                                                                              // результирующий массив
        $count = 0;                                                                                                       // количество добавленных записей
        $count_all = 0;                                                                                                   // количество записей всего
        try {
            $warnings[] = "actionSensorRestorePlaceByEdge. Начал выполнять метод";

            // ключ типа параметра

            $handbook_values = (new Query())
                ->select(
                    '
                    sp269_value,
                    sp122_value,
                    sp269_sensor_parameter_id,
                    sp122_sensor_parameter_id,
                    sensor_id
                        ')
                ->from('view_restore_place_by_edge')
                ->all();
            $datetime = Assistant::GetDateNow();
            if (!$handbook_values) {
                throw new Exception("actionSensorRestorePlaceByEdge. Нет параметров для восстановления");
            }

            $edges = Edge::find()
                ->indexBy('id')
                ->asArray()
                ->all();

            if (!$edges) {
                throw new Exception("actionSensorRestorePlaceByEdge. Справочник выработок пустой");
            }

            foreach ($handbook_values as $handbook_value) {
                $edge_id = $handbook_value['sp269_value'];
                if (isset($edges[$edge_id])) {
                    //вставляем параметр edge
                    $sphv[] = array(
                        'sensor_parameter_id' => $handbook_value['sp269_sensor_parameter_id'],
                        'date_time' => $datetime,
                        'value' => $edge_id,
                        'status_id' => 1
                    );

                    //вставляем параметр edge
                    $sphv[] = array(
                        'sensor_parameter_id' => $handbook_value['sp122_sensor_parameter_id'],
                        'date_time' => $datetime,
                        'value' => $edges[$edge_id]['place_id'],
                        'status_id' => 1
                    );
                }
            }

            if (isset($sphv)) {
                $warnings[] = "actionSensorRestorePlaceByEdge. Вставка данных в sensor_parameter_handbook_value";
                $insert_result_to_MySQL = Yii::$app->db_amicum2->createCommand()->batchInsert('sensor_parameter_handbook_value', ['sensor_parameter_id', 'date_time', 'value', 'status_id'], $sphv)->execute();
                $warnings[] = "actionSensorRestorePlaceByEdge. закончил вставку данных в sensor_parameter_handbook_value: " . $insert_result_to_MySQL;
                if (!$insert_result_to_MySQL) {
                    throw new Exception('actionSensorRestorePlaceByEdge. Ошибка массовой вставки конкретных работников в БД (sensor_parameter_handbook_value) ' . $insert_result_to_MySQL);
                }
            } else {
                $warnings[] = "actionSensorRestorePlaceByEdge.  нечего вставлять";
            }


        } catch (\Throwable $exception) {
            $errors[] = "actionSensorRestorePlaceByEdge. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = "actionSensorRestorePlaceByEdge. $count шт. Количество обработанных записей";
        $warnings[] = "actionSensorRestorePlaceByEdge. $count_all шт. Количество всего записей";

        $warnings[] = "actionSensorRestorePlaceByEdge. Закончил выполнять метод";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод actionUpdateEdgeCh() - метод обновления уставки CH4 выработок (ставит 1%)
     * алгоритм:
     *      1. Получить список эджей и их сопряжений с координатам
     *      2. получить для списка эджей параметр длины и если его нет, то создать
     *      3. вычислить новую длину эджа
     *      4. Положить новую длину в БД
     * пример: http://127.0.0.1/super-test/update-edge-ch?mine_id=250
     */
    public function actionUpdateEdgeCh()
    {
        // Стартовая отладочная информация
        $method_name = 'actionUpdateEdgeCh';                                                                             // название логируемого метода
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
        $count_edge = 0;                    // общее количество эджей
        $count_new_parameter_ch = 0;    // количество созданных параметров
        $count_edge_save = 0;               // количество вставленных записей в БД
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
            $post = Assistant::GetServerMethod();
            $mine_id = $post['mine_id'];
            $edges = (new Query())
                ->select('
                    edge.id as edge_id,
                ')
                ->from('edge')
                ->innerJoin('place', 'place.id=edge.place_id')
                ->where(['place.mine_id' => $mine_id])
                ->all();
            if (!$edges) {
                throw new Exception('actionUpdateEdgeCh. Список выработок пуст');
            }
            $count_edge = count($edges);
            $edges_parameter_ch = (new Query())
                ->select('
                    id,
                    edge_id
                ')
                ->from('edge_parameter')
                ->where(['parameter_type_id' => 1, 'parameter_id' => 263])
                ->indexBy('edge_id')
                ->all();
            $date_time = Assistant::GetDateNow();
            foreach ($edges as $edge) {
                if (!isset($edges_parameter_ch[$edge['edge_id']])) {
                    $new_edge_parameter = new EdgeParameter();
                    $new_edge_parameter->edge_id = $edge['edge_id'];
                    $new_edge_parameter->parameter_id = 263;
                    $new_edge_parameter->parameter_type_id = 1;
                    if (!$new_edge_parameter->save()) {
                        throw new Exception('actionUpdateEdgeCh. Не смог сохранить параметр 263/1 в EdgeParameter');
                    }
                    $new_edge_parameter->refresh();
                    $edges_parameter_ch[$edge['edge_id']]['id'] = $new_edge_parameter->id;
                    $edges_parameter_ch[$edge['edge_id']]['edge_id'] = $edge['edge_id'];
                    $count_new_parameter_ch++;
                }

                $length_to_db[] = array(
                    'edge_parameter_id' => $edges_parameter_ch[$edge['edge_id']]['id'],
                    'date_time' => $date_time,
                    'value' => 1,
                    'status_id' => 1
                );
            }
            if (isset($length_to_db)) {
                $count_edge_save = Yii::$app->db->createCommand()->batchInsert('edge_parameter_handbook_value', ['edge_parameter_id', 'date_time', 'value', 'status_id'], $length_to_db)->execute();
                $warnings[] = $method_name . '. Количество вставленных записей в edge_parameter_handbook_value' . $count_edge_save;
            }
//            $result=$edges;


        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        // запись в БД начала выполнения скрипта
        $warnings[] = array(
            '$count_edge. Общее количество эджей' => $count_edge,
            '$count_new_parameter_ch. Количество созданных парамтеров ch4 эджей' => $count_new_parameter_ch,
            '$count_edge_save. Количество вставленных данных в БД' => $count_edge_save,
        );

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

        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }


    /**
     * Метод actionUpdateEdgeCo() - метод обновления уставки CO выработок (ставит 0.0017)
     * алгоритм:
     *      1. Получить список эджей и их сопряжений с координатам
     *      2. получить для списка эджей параметр длины и если его нет, то создать
     *      3. вычислить новую длину эджа
     *      4. Положить новую длину в БД
     * пример: http://127.0.0.1/super-test/update-edge-co?mine_id=250
     */
    public function actionUpdateEdgeCo()
    {
        // Стартовая отладочная информация
        $method_name = 'actionUpdateEdgeCo';                                                                             // название логируемого метода
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
        $count_edge = 0;                    // общее количество эджей
        $count_new_parameter_co = 0;    // количество созданных параметров
        $count_edge_save = 0;               // количество вставленных записей в БД
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
            $post = Assistant::GetServerMethod();
            $mine_id = $post['mine_id'];
            $edges = (new Query())
                ->select('
                    edge.id as edge_id,
                ')
                ->from('edge')
                ->innerJoin('place', 'place.id=edge.place_id')
                ->where(['place.mine_id' => $mine_id])
                ->all();
            if (!$edges) {
                throw new Exception('actionUpdateEdgeCo. Список выработок пуст');
            }
            $count_edge = count($edges);
            $edges_parameter_ch = (new Query())
                ->select('
                    id,
                    edge_id
                ')
                ->from('edge_parameter')
                ->where(['parameter_type_id' => 1, 'parameter_id' => 264])
                ->indexBy('edge_id')
                ->all();
            $date_time = Assistant::GetDateNow();
            foreach ($edges as $edge) {
                if (!isset($edges_parameter_ch[$edge['edge_id']])) {
                    $new_edge_parameter = new EdgeParameter();
                    $new_edge_parameter->edge_id = $edge['edge_id'];
                    $new_edge_parameter->parameter_id = 264;
                    $new_edge_parameter->parameter_type_id = 1;
                    if (!$new_edge_parameter->save()) {
                        throw new Exception('actionUpdateEdgeCo. Не смог сохранить параметр 264/1 в EdgeParameter');
                    }
                    $new_edge_parameter->refresh();
                    $edges_parameter_ch[$edge['edge_id']]['id'] = $new_edge_parameter->id;
                    $edges_parameter_ch[$edge['edge_id']]['edge_id'] = $edge['edge_id'];
                    $count_new_parameter_co++;
                }

                $length_to_db[] = array(
                    'edge_parameter_id' => $edges_parameter_ch[$edge['edge_id']]['id'],
                    'date_time' => $date_time,
                    'value' => '0.0017',
                    'status_id' => 1
                );
            }
            if (isset($length_to_db)) {
                $count_edge_save = Yii::$app->db->createCommand()->batchInsert('edge_parameter_handbook_value', ['edge_parameter_id', 'date_time', 'value', 'status_id'], $length_to_db)->execute();
                $warnings[] = $method_name . '. Количество вставленных записей в edge_parameter_handbook_value' . $count_edge_save;
            }
//            $result=$edges;


        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        // запись в БД начала выполнения скрипта
        $warnings[] = array(
            '$count_edge. Общее количество эджей' => $count_edge,
            '$count_new_parameter_co. Количество созданных парамтеров co эджей' => $count_new_parameter_co,
            '$count_edge_save. Количество вставленных данных в БД' => $count_edge_save,
        );

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

        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }


    /**
     * actionCompareEdgeShemaDbCache() - метод сравнения схемы шахты в БД и в КЕШЕ
     * dif_array            - содержимое записей не совпадает
     *     []
     *      source              - запись в источнике
     *          {}
     *      target              - запись в назначении
     *          {}
     * target_errors        - массив нет ключей в источнике
     * source_errors        - массив нет ключей в назначении
     * пример: 127.0.0.1/super-test/compare-edge-shema-db-cache
     *
     */
    public function actionCompareEdgeShemaDbCache()
    {

        // Стартовая отладочная информация
        $method_name = 'actionCompareEdgeShemaDbCache';                                                                         // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество вставленных записей
        $count_break_all = 0;                                                                                           // количество не совпадающих записей
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
        $result = array();                                                                                                 // результирующий массив (если требуется)
        $source_errors = array();                                                                                       // нет айди в назначении
        $target_errors = array();                                                                                       // нет айди в источнике
        $dif_array = array();                                                                                           // различные записи
        $status = 1;                                                                                                    // статус выполнения скрипта


//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', "10500M");

        try {

            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                                               // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */


            $target_ids = (new EdgeCacheController())->multiGetEdgeScheme(290);
            foreach ($target_ids as $target_id) {
                $targets_obj[$target_id['edge_id']] = $target_id;
            }

            $source_ids = Yii::$app->db_amicum2->createCommand('SELECT edge_id, place_id, place_title, conjunction_start_id, conjunction_end_id, xStart, yStart, zStart, xEnd, yEnd, zEnd, place_object_id, danger_zona, color_edge, color_edge_rus, mine_id, conveyor, conveyor_tag, value_ch, value_co, date_time FROM view_initEdgeScheme where mine_id=290')->queryAll();
            foreach ($source_ids as $source_id) {
                $source_obj[$source_id['edge_id']] = $source_id;
            }

            $source_column = array('edge_id', 'place_id', 'place_title', 'conjunction_start_id', 'conjunction_end_id', 'xStart', 'yStart', 'zStart', 'xEnd', 'yEnd', 'zEnd', 'place_object_id', 'danger_zona', 'color_edge', 'color_edge_rus', 'mine_id', 'conveyor', 'conveyor_tag', 'value_ch', 'value_co', 'date_time');

            foreach ($source_ids as $source_id) {
                if (!isset($targets_obj[$source_id['edge_id']])) {
                    $target_errors[] = $source_id['edge_id'];
                } else {
                    $flag = 0;
                    foreach ($source_column as $column) {
                        if ($targets_obj[$source_id['edge_id']][$column] != $source_id[$column]) {
                            $flag = 1;
                        }
                    }
                    if ($flag) {
                        $count_break_all++;
                        $dif_array[] = array("source" => $source_id, "target" => $targets_obj[$source_id['edge_id']]);
                    }
                }
                $count_all++;
            }

            foreach ($target_ids as $target_id) {
                if (!isset($source_obj[$target_id['edge_id']])) {
                    $source_errors[] = $target_id['edge_id'];
                }
            }

            $result['source_errors'] = $source_errors;
            $result['target_errors'] = $target_errors;
            $result['dif_array'] = $dif_array;

            $warnings[] = $method_name . '. Количество всех записей в источнике ' . $count_all;
            $warnings[] = $method_name . '. Количество Разных записей в источнике ' . $count_break_all;

            /** Отладка */
            $description = ' Закончил с edge_new_ids';                                                                 // описание текущей отладочной точки
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

        } catch (Throwable $throwable) {

            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $throwable->getMessage();
            $errors[] = $throwable->getLine();

        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * actionPhpInfo() - метод получения сведений о php
     * пример: 127.0.0.1/super-test/php-info
     *
     */
    public function actionPhpInfo()
    {
//        ob_start();
        echo phpinfo();
    }

    // actionFindAndDeleteDublicatePlace - метод поиска, замены и удаления дубликатов места генерируемых при синхронизации ППК ПАБ
    // пример: 127.0.0.1/super-test/find-and-delete-duplicate-place?$mine_id=1
    public function actionFindAndDeleteDuplicatePlace($mine_id = 1)
    {
        $method_name = "actionFindAndDeleteDuplicatePlace";
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
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

//            ini_set('max_execution_time', -1);
//            ini_set('mysqlnd.connect_timeout', 1440000);
//            ini_set('default_socket_timeout', 1440000);
//            ini_set('mysqlnd.net_read_timeout', 1440000);
//            ini_set('mysqlnd.net_write_timeout', 1440000);
//            ini_set('memory_limit', "10500M");

            $places = Place::find()
                ->where(['mine_id' => $mine_id])
                ->all();

            foreach ($places as $place) {
                $place_title = trim($place['title']);
                if (!isset($place_hand[$place_title]['place_count'])) {
                    $place_hand[$place_title]['place_count'] = 0;
                }
                $place_hand[$place_title]['id'] = $place['id'];
                $place_hand[$place_title]['place_title'] = $place_title;
                $place_hand[$place_title]['place_count']++;

                if ($place_hand[$place_title]['place_count'] > 1) {
                    if (!isset($place_duplicate[$place_title])) {
                        $place_duplicate[$place_title] = $place_duplicate_temp[$place_title];
                    }
                    $place_duplicate[$place_title][] = $place_hand[$place_title];
                } else {
                    $place_duplicate_temp[$place_title][] = $place_hand[$place_title];
                }
            }
            if (isset($place_duplicate)) {
                $warnings[] = "actionFindAndDeleteDuplicatePlace. Есть дубли " . count($place_duplicate);
//                $warnings[] = $place_duplicate;

                foreach ($place_duplicate as $place_titles) {
                    $count_all++;
                    foreach ($place_titles as $place) {
                        if (!isset($first_place)) {
                            $first_place = $place['id'];
                        } else {
                            $checking_places = CheckingPlace::findAll(['place_id' => $place['id']]);
                            foreach ($checking_places as $checking_place) {
                                $checking_place->place_id = $first_place;
                                if (!$checking_place->save()) {
                                    $errors[] = $checking_place->errors;
                                    throw new Exception('actionFindAndDeleteDuplicatePlace. Не смог сохранить CheckingPlace');
                                }
                            }

                            $injunction_places = Injunction::findAll(['place_id' => $place['id']]);
                            foreach ($injunction_places as $injunction_place) {
                                $injunction_places_for_replace = Injunction::findOne([
                                    'place_id' => $first_place,
                                    'worker_id' => $injunction_place['worker_id'],
                                    'kind_document_id' => $injunction_place['kind_document_id'],
                                    'checking_id' => $injunction_place['checking_id'],
                                    'observation_number' => $injunction_place['observation_number']
                                ]);
                                if ($injunction_places_for_replace) {
                                    $injunction_id = $injunction_places_for_replace['id'];
                                    $injunction_to_delete_id = $injunction_place['id'];
                                    $warnings[] = "actionFindAndDeleteDuplicatePlace. Меняем на этот ключ предписания" . $injunction_id;
                                    $warnings[] = "actionFindAndDeleteDuplicatePlace. Меняем этот ключ предписания" . $injunction_to_delete_id;
                                    $injunction_violations = InjunctionViolation::findAll(['injunction_id' => $injunction_to_delete_id]);
                                    $warnings[] = "actionFindAndDeleteDuplicatePlace. Нашел для обновления InjunctionViolation";
                                    $warnings[] = $injunction_violations;
                                    foreach ($injunction_violations as $injunction_violation) {
                                        $injunction_violation->injunction_id = $injunction_id;
                                        if (!$injunction_violation->save()) {
                                            $errors[] = $injunction_violation->errors;
                                            throw new Exception('actionFindAndDeleteDuplicatePlace. Не смог сохранить InjunctionViolation');
                                        }
                                    }
                                    $injunction_statuses = InjunctionStatus::findAll(['injunction_id' => $injunction_to_delete_id]);
                                    $warnings[] = "actionFindAndDeleteDuplicatePlace. Нашел для обновления InjunctionStatus";
                                    $warnings[] = $injunction_statuses;
                                    foreach ($injunction_statuses as $injunction_status) {
                                        $injunction_status->injunction_id = $injunction_id;
                                        if (!$injunction_status->save()) {
                                            $errors[] = $injunction_status->errors;
                                            throw new Exception('actionFindAndDeleteDuplicatePlace. Не смог сохранить InjunctionStatus');
                                        }
                                    }
                                    $injunction_attachments = InjunctionAttachment::findAll(['injunction_id' => $injunction_to_delete_id]);
                                    $warnings[] = "actionFindAndDeleteDuplicatePlace. Нашел для обновления InjunctionAttachment ";
                                    $warnings[] = $injunction_attachments;
                                    foreach ($injunction_attachments as $injunction_attachment) {
                                        $injunction_attachment->injunction_id = $injunction_id;
                                        if (!$injunction_attachment->save()) {
                                            $errors[] = $injunction_attachment->errors;
                                            throw new Exception('actionFindAndDeleteDuplicatePlace. Не смог сохранить InjunctionAttachment');
                                        }
                                    }
                                    $injunction_deleted = Injunction::deleteAll(['id' => $injunction_to_delete_id]);
                                    $warnings[] = "actionFindAndDeleteDuplicatePlace. Удалил предписание " . $injunction_deleted;
                                } else {
                                    $injunction_place->place_id = $first_place;
                                    if (!$injunction_place->save()) {
                                        $errors[] = $injunction_place->errors;
                                        throw new Exception('actionFindAndDeleteDuplicatePlace. Не смог сохранить Injunction');
                                    }
                                }
                            }

                            $injunction_violation_places = InjunctionViolation::findAll(['place_id' => $place['id']]);
                            foreach ($injunction_violation_places as $injunction_violation_place) {
                                $injunction_violation_place->place_id = $first_place;
                                if (!$injunction_violation_place->save()) {
                                    $errors[] = $injunction_violation_place->errors;
                                    throw new Exception('actionFindAndDeleteDuplicatePlace. Не смог сохранить InjunctionViolation');
                                }
                            }

                            $edge_places = Edge::findAll(['place_id' => $place['id']]);
                            foreach ($edge_places as $edge_place) {
                                $edge_place->place_id = $first_place;
                                if (!$edge_place->save()) {
                                    $errors[] = $edge_place->errors;
                                    throw new Exception('actionFindAndDeleteDuplicatePlace. Не смог сохранить Edge');
                                }
                            }

                            $order_places = OrderPlace::findAll(['place_id' => $place['id']]);
                            foreach ($order_places as $order_place) {
                                $order_place->place_id = $first_place;
                                if (!$order_place->save()) {
                                    $errors[] = $order_place->errors;
                                    throw new Exception('actionFindAndDeleteDuplicatePlace. Не смог сохранить OrderPlace');
                                }
                            }

                            $order_template_places = OrderTemplatePlace::findAll(['place_id' => $place['id']]);
                            foreach ($order_template_places as $order_template_place) {
                                $order_template_place->place_id = $first_place;
                                if (!$order_template_place->save()) {
                                    $errors[] = $order_template_place->errors;
                                    throw new Exception('actionFindAndDeleteDuplicatePlace. Не смог сохранить OrderTemplatePlace');
                                }
                            }

                            $order_places_vtb_ab = OrderPlaceVtbAb::findAll(['place_id' => $place['id']]);
                            foreach ($order_places_vtb_ab as $order_place_vtb_ab) {
                                $order_place_vtb_ab->place_id = $first_place;
                                if (!$order_place_vtb_ab->save()) {
                                    $errors[] = $order_place_vtb_ab->errors;
                                    throw new Exception('actionFindAndDeleteDuplicatePlace. Не смог сохранить OrderPlaceVtbAb');
                                }
                            }

                            $company_department_places = PlaceCompanyDepartment::findAll(['place_id' => $place['id']]);
                            foreach ($company_department_places as $company_department_place) {
                                $company_department_place->place_id = $first_place;
                                if (!$company_department_place->save()) {
                                    $errors[] = $company_department_place->errors;
                                    throw new Exception('actionFindAndDeleteDuplicatePlace. Не смог сохранить PlaceCompanyDepartment');
                                }
                            }

                            $order_permits = OrderPermit::findAll(['place_id' => $place['id']]);
                            foreach ($order_permits as $order_permit) {
                                $order_permit->place_id = $first_place;
                                if (!$order_permit->save()) {
                                    $errors[] = $order_permit->errors;
                                    throw new Exception('actionFindAndDeleteDuplicatePlace. Не смог сохранить OrderPermit');
                                }
                            }

                            $place_routes = PlaceRoute::findAll(['place_id' => $place['id']]);
                            foreach ($place_routes as $place_route) {
                                $place_route->place_id = $first_place;
                                if (!$place_route->save()) {
                                    $errors[] = $place_route->errors;
                                    throw new Exception('actionFindAndDeleteDuplicatePlace. Не смог сохранить PlaceCompanyDepartment');
                                }
                            }

                            $audit_places = AuditPlace::findAll(['place_id' => $place['id']]);
                            foreach ($audit_places as $audit_place) {
                                $audit_place->place_id = $first_place;
                                if (!$audit_place->save()) {
                                    $errors[] = $audit_place->errors;
                                    throw new Exception('actionFindAndDeleteDuplicatePlace. Не смог сохранить AuditPlace');
                                }
                            }

                            $checking_plans = CheckingPlan::findAll(['place_id' => $place['id']]);
                            foreach ($checking_plans as $checking_plan) {
                                $checking_plan->place_id = $first_place;
                                if (!$checking_plan->save()) {
                                    $errors[] = $checking_plan->errors;
                                    throw new Exception('actionFindAndDeleteDuplicatePlace. Не смог сохранить CheckingPlan');
                                }
                            }

                            $passports = Passport::findAll(['place_id' => $place['id']]);
                            foreach ($passports as $passport) {
                                $passport->place_id = $first_place;
                                if (!$passport->save()) {
                                    $errors[] = $passport->errors;
                                    throw new Exception('actionFindAndDeleteDuplicatePlace. Не смог сохранить Passport');
                                }
                            }

                            $place_operations = PlaceOperation::findAll(['place_id' => $place['id']]);
                            foreach ($place_operations as $place_operation) {
                                $place_operation->place_id = $first_place;
                                if (!$place_operation->save()) {
                                    $errors[] = $place_operation->errors;
                                    throw new Exception('actionFindAndDeleteDuplicatePlace. Не смог сохранить PlaceOperation');
                                }
                            }

                            $working_places = WorkingPlace::findAll(['place_id' => $place['id']]);
                            foreach ($working_places as $working_place) {
                                $working_place->place_id = $first_place;
                                if (!$working_place->save()) {
                                    $errors[] = $working_place->errors;
                                    throw new Exception('actionFindAndDeleteDuplicatePlace. Не смог сохранить WorkingPlace');
                                }
                            }

                            $stop_pbs = StopPb::findAll(['place_id' => $place['id']]);
                            foreach ($stop_pbs as $stop_pb) {
                                $stop_pb->place_id = $first_place;
                                if (!$stop_pb->save()) {
                                    $errors[] = $stop_pb->errors;
                                    throw new Exception('actionFindAndDeleteDuplicatePlace. Не смог сохранить StopPb');
                                }
                            }

                            $place_deleted = Place::deleteAll(['id' => $place['id']]);
//                            throw new Exception('actionFindAndDeleteDuplicatePlace. Отладочный стоп');
                        }
                    }
                    unset($first_place);
                }
            } else {
                $warnings[] = "actionFindAndDeleteDuplicatePlace. Нет дублей ";
            }

            $warnings[] = "actionFindAndDeleteDuplicatePlace. Количество обработанных записей: " . $count_all;

        } catch (\Throwable $exception) {
            $errors[] = $method_name . ". Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
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
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,


        $result_m = array('items' => $result, 'status' => $status, 'warnings' => $warnings, 'debug' => $debug, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_m;
    }

    /**
     * в папке /var/log должен присутствовать файл amicum_update.log ему выдать права на запись apatch
     * Команда на создание файла в консоле:
     *      > /var/log/amicum_update.log
     *      chmod -R 777 /var/log/amicum_update.log
     * UpdateProject - метод для обновления проекта
     * -back
     * -ekvn
     * -sour
     * -full
     * пример вызова http://192.168.1.5/admin/serviceamicum/amicum-service/update-project
     * sh /var/www/html/amicum/script_amicum/pull_1_5.sh -back
     * sh /var/www/html/amicum/script_amicum/pull_1_5.sh -ekvn
     * sh /var/www/html/amicum/script_amicum/pull_1_5.sh -full
     * sh /var/www/html/amicum/script_amicum/pull_1_5.sh -sour
     * 192.168.1.5/read-manager-amicum?controller=SuperTest&method=UpdateProject&subscribe=&data={"project":"-back"}
     * */
    public static function UpdateProject($data_post = null)
    {
        $log = new LogAmicumFront("UpdateProject");
        try {
            $log->addLog("Начал выполнять метод");
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }

            $post_dec = json_decode($data_post);

            if (!property_exists($post_dec, 'project') ||
                !isset($post_dec->project) || $post_dec->project == ""
            ) {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $project = $post_dec->project;

            switch ($project) {
                case "-back":
                    $log->addLog("Обновление только back-end");
                    break;
                case "-ekvn":
                    $log->addLog("Обновление ЭКВН. Обновляю front-end");
                    break;
                case "-sour":
                    $log->addLog("Обновление СОУР. Обновляю front-end");
                    break;
                case "-unity":
                    $log->addLog("Обновление Unity. Обновляю front-end");
                    break;
                case "-dash":
                    $log->addLog("Обновление DashBoard. Обновляю front-end");
                    break;
                case "-full":
                    $log->addLog("Обновление всего проекта");
                    break;
                case "-admin":
                    $log->addLog("Обновление модуля Авторизации");
                    break;
                case "-exam":
                    $log->addLog("Обновление Предсменного экзаменатора");
                    break;
                default:
                    throw new Exception("Получена неверная команда на обновление");
            }

            $host = gethostname();
            $ip = gethostbyname($host);
            if ($ip == "192.168.1.5" or $ip == "87.103.211.83") {
                $response = shell_exec("sh /var/www/html/amicum/script_amicum/pull_1_5.sh $project");
            } else {
                $response = shell_exec("sh /var/www/html/amicum/script_amicum/pull.sh $project");
            }

            $log->addData($response, '$response', __LINE__);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog('Окончил выполнение метода');

        return array_merge(['Items' => []], $log->getLogAll());
    }

    //ChangeDcsStatus - метод запрещает/разрешает запись передаваемого ССД, что означает отключает/включает
    //$dcsKey = ключ ССД которого надо отключить
    //Список ключей:
    //1) strataStatus
    //2) opcMikonStatus
    //3) bpdStatus
    //4) snmpStatus
    //5) opcEquipmentStatus
    //$status = команда которую должен выполнить метод (1/0 - откл/вкл)
    //пример вызова: 127.0.0.1/read-manager-amicum?controller=SuperTest&method=CheckDcsStatus&subscribe=&data={"dcs_name":"strataStatus","status_dcs":"0","mine_id":"290"}
    public static function ChangeDcsStatus($data_post = null)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $command = null;
        $method_name = "ChangeDcsStatus. ";
        try {

            $warnings[] = $method_name . 'Начало';
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . 'Не переданы входные параметры');
            }
            $warnings[] = $method_name . 'Данные успешно переданы';
            $warnings[] = $method_name . 'Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . 'Декодировал входные параметры';
            if (!property_exists($post_dec, 'dcs_name'))                                                        // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . 'Переданы некорректные входные параметры');
            }

            $dcs_name = $post_dec->dcs_name;
            $mine_id = $post_dec->mine_id;
            $status_dcs = $post_dec->status_dcs;
            if ($status_dcs == "0") {
                $command = "Ставлю запреть на запись";
            } else {
                $command = "Снемаю запреть на запись";
            }
            switch ($dcs_name) {
                case "strataStatus":
                    $warnings[] = "Ключ $dcs_name это ССД Strata. $command";
                    break;
                case "opcMikonStatus":
                    $warnings[] = "Ключ $dcs_name это ССД OPC МИКОН (стац датчики). $command";
                    break;
                case "bpdStatus":
                    $warnings[] = "Ключ $dcs_name это ССД БПД-3. $command";
                    break;
                case "snmpStatus":
                    $warnings[] = "Ключ $dcs_name это ССД SNMP (комутаторы). $command";
                    break;
                case "opcEquipmentStatus":
                    $warnings[] = "Ключ $dcs_name это ССД OPC АСУТП (оборудование). $command";
                    break;
                default:
                    $errors[] = "Не передан ключ ССД";

            }
            $dcs_key_with_mine_id = ServiceCache::buildDcsKey($mine_id);
            $result[] = (new ServiceCache())->amicum_rSetHash($dcs_key_with_mine_id, $dcs_name, $status_dcs);
            $warnings[] = $method_name . 'конец';


        } catch (Exception $e) {
            $status = 0;
            $errors[] = $method_name . "Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = $result_main;
        return $result_main;
    }

    //ChangeAllDcsStatus - метод изменения статусов  всех служб в кэш
    //$status_dcses = команда которую должен выполнить метод (1/0 - откл/вкл)
    //пример вызова: 127.0.0.1/read-manager-amicum?controller=SuperTest&method=ChangeAllDcsStatus&subscribe=&data={"status_dcses":"0","mine_id":"290"}
    public static function ChangeAllDcsStatus($data_post = null)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $command = null;
        try {
            $warnings[] = "ChangeAllDcsStatus начало";
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('ChangeAllDcsStatus' . '. Не переданы входные параметры');
            }
            $warnings[] = 'ChangeAllDcsStatus' . '. Данные успешно переданы';
            $warnings[] = 'ChangeAllDcsStatus' . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'ChangeAllDcsStatus' . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'status_dcses'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('ChangeAllDcsStatus' . '. Переданы некорректные входные параметры (status_dcses)');
            }
            if (!property_exists($post_dec, 'mine_id'))                                                         // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('ChangeAllDcsStatus' . '. Переданы некорректные входные параметры (status_dcses)');
            }

            $status_dcses = $post_dec->status_dcses;
            $mine_id = $post_dec->mine_id;
            $result = (new ServiceCache())->ChangeDcsStatus($status_dcses, $mine_id);
            $warnings[] = 'ChangeAllDcsStatus конец';
        } catch (Exception $e) {
            $status = 0;
            $errors[] = "ChangeAllDcsStatus. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = $result_main;
        return $result_main;
    }

    //CheckDcsStatus - метод проверки статуса разрешения на запись конкретной службе
    //dcs_key = ключ по которым нужно произвести поиск по кэшу
    // пример вызова: 127.0.0.1/read-manager-amicum?controller=SuperTest&method=CheckDcsStatus&subscribe=&data={"dcs_name":"strataStatus","mine_id":"290"}
    public static function CheckDcsStatus($data_post = null)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $command = null;
        try {

            $warnings[] = 'CheckDcsStatus начало';
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('CheckDcsStatus' . '. Не переданы входные параметры');
            }
            $warnings[] = 'CheckDcsStatus' . '. Данные успешно переданы';
            $warnings[] = 'CheckDcsStatus' . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'CheckDcsStatus' . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'dcs_name'))                                                        // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('CheckDcsStatus' . '. Переданы некорректные входные параметры');
            }

            if (!isset($post_dec->dcs_name) || $post_dec->dcs_name == "") {
                throw new Exception("Не передан входной параметер dcs_name");

            }
            if (!isset($post_dec->mine_id) || $post_dec->mine_id == "") {
                throw new Exception("Не передан входной параметер main_id");

            }
            $dcs_name = $post_dec->dcs_name;
            $mine_id = $post_dec->mine_id;

            if (!isset($dcs_name) || $dcs_name == "") {
                throw new \yii\console\Exception("Не передан входной параметер dcs_name");

            }
            if (!isset($mine_id) || $mine_id == "") {
                throw new Exception("Не передан входной параметер main_id");

            }
            switch ($dcs_name) {
                case "strataStatus":
                    $warnings[] = "Ключ $dcs_name это ССД Strata";
                    break;
                case "opcMikonStatus":
                    $warnings[] = "Ключ $dcs_name это ССД OPC МИКОН (стац датчики)";
                    break;
                case "bpdStatus":
                    $warnings[] = "Ключ $dcs_name это ССД БПД-3";
                    break;
                case "snmpStatus":
                    $warnings[] = "Ключ $dcs_name это ССД SNMP (комутаторы)";
                    break;
                case "opcEquipmentStatus":
                    $warnings[] = "Ключ $dcs_name это ССД OPC АСУТП (оборудование)";
                    break;
                default:
                    $errors[] = "Не передан ключ ССД";

            }
            $result['dcs_status'] = (new ServiceCache())->CheckDcsStatus($mine_id, $dcs_name);
            if ($result['dcs_status'] == "1") {
                $warnings[] = "Запись разрешен";
            } elseif ($result['dcs_status'] == "0") {
                $warnings[] = "Запись запрещен";
            } else {
                $warnings[] = "В кэше нет записи по ключу $dcs_name";
            }

            $warnings[] = 'CheckDcsStatus конец';


        } catch (Exception $e) {
            $status = 0;
            $errors[] = "CheckDcsStatus. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = $result_main;
        return $result_main;
    }

    /**
     * CheckDcsState - Метод поиска последнего параметра по конкретной службе для определения статуса работы служб
     * @param $dcs_name
     * @param $mine_id
     * @example 127.0.0.1/read-manager-amicum?controller=SuperTest&method=CheckDcsState&subscribe=&data={"dcs_name":"strataStatus","mine_id":"290"}
     *
     */
    public static function CheckDcsState($data_post = null)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $command = null;
        $object_id = null;
        $table_name_for_sensors_get_sensors = null;
        $table_name_for_get_value = null;
        $parameter_id = 83;
        $parameter_type_id = 2;
        $method_name = "CheckDcsState";
        try {

            $warnings[] = 'actionCheckDcsState начало';

            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'dcs_name'))                                                        // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }

            if (!isset($post_dec->dcs_name) || $post_dec->dcs_name == "") {
                throw new Exception("Не передан входной параметер dcs_name");

            }
            if (!isset($post_dec->mine_id) || $post_dec->mine_id == "") {
                throw new Exception("Не передан входной параметер main_id");

            }
            $dcs_name = $post_dec->dcs_name;
            $mine_id = $post_dec->mine_id;
            switch ($dcs_name) {
                case "strataStatus":
                    $warnings[] = "Ключ $dcs_name это ССД Strata";
                    $object_id = 47;
                    $table_name_for_get_value = 'view_initSensorParameterValue';
                    $table_name_for_sensors_get_sensors = 'view_initSensorParameterValue';
                    $parameter_id = 83;
                    $parameter_type_id = 2;
                    break;
                case "opcMikonStatus":
                    $warnings[] = "Ключ $dcs_name это ССД OPC МИКОН (стац датчики)";
                    $object_id = 28;
                    $table_name_for_get_value = 'view_initSensorParameterValue';
                    $table_name_for_sensors_get_sensors = 'view_initSensorParameterHandbookValue';

                    $parameter_id = 164;
                    $parameter_type_id = 3;
                    break;
                case "bpdStatus":
                    $warnings[] = "Ключ $dcs_name это ССД БПД-3";
                    $object_id = 49;
                    $table_name_for_get_value = 'view_initSensorParameterValue';
                    $table_name_for_sensors_get_sensors = 'view_initSensorParameterHandbookValue';
                    $parameter_id = 164;
                    $parameter_type_id = 3;
                    break;
                case "snmpStatus":
                    $warnings[] = "Ключ $dcs_name это ССД SNMP (комутаторы)";
                    $object_id = 156;
                    $table_name_for_get_value = 'view_initSensorParameterValue';
                    $table_name_for_sensors_get_sensors = 'view_initSensorParameterHandbookValue';
                    $parameter_id = 164;
                    $parameter_type_id = 3;
                    break;
                case "opcEquipmentStatus":
                    $warnings[] = "Ключ $dcs_name это ССД OPC АСУТП (оборудование)";
                    $object_id = 200;
                    $table_name_for_get_value = 'view_initSensorParameterValue';
                    $table_name_for_sensors_get_sensors = 'view_initSensorParameterHandbookValue';
                    $parameter_id = 164;
                    $parameter_type_id = 3;
                    break;
                default:
                    $errors[] = "Не передан ключ ССД";

            }
            //получаем список всех сенсоров
            $sensors = (new Query())
                ->select(['sensor.id as sensor_id'])
                ->from('sensor')
                ->innerJoin('object', 'sensor.object_id = object.id')
                ->innerJoin($table_name_for_sensors_get_sensors, $table_name_for_sensors_get_sensors . '.sensor_id  = sensor.id')
                ->where('object_id = ' . $object_id . ' and ' . $table_name_for_sensors_get_sensors . '.parameter_id = 346 and ' . $table_name_for_sensors_get_sensors . '.value = ' . $mine_id)
                ->all();
            if (!$sensors) {
                $errors[] = "Не смог получит сенсоров. Это значит в данной таблице $table_name_for_sensors_get_sensors отсуствуют значение по объекту $object_id";
            }

            foreach ($sensors as $sensor) {
                $hand_sensors[$sensor['sensor_id']] = $sensor;
            }
            //           $warnings['$sensors'] = $sensors;
            unset($sensors);
            //получаем последного значения для каждого сенсора по конкретным параметрам
            $max_date_of_sensors = (new Query())
                ->select(' sensor_id, date_time ')
                ->from($table_name_for_get_value)
                ->where('parameter_id = ' . $parameter_id . ' and parameter_type_id =' . $parameter_type_id)
                ->all();
            $last_date = "0";
            $last_sensor = NULL;
            foreach ($max_date_of_sensors as $max_date_of_sensor) {
                if (isset($hand_sensors[$max_date_of_sensor['sensor_id']])) {

                    if ($last_date < $max_date_of_sensor['date_time']) {
                        $last_date = $max_date_of_sensor['date_time'];
                        $last_sensor = $max_date_of_sensor['sensor_id'];
                    }
                }
            }
            $result['sensor_id'] = $last_sensor;
            $result['date_time'] = $last_date;
            $result['date_time_now'] = Assistant::GetDateTimeNow();
            $date_time_now = strtotime(Assistant::GetDateTimeNow());
            $date_time_dcs = strtotime($last_date);
            $result['diff'] = ($date_time_now - $date_time_dcs);
            if ($result['diff'] > 60) {
                $result['dcs_state'] = 0;
                $warnings[] = "Последняя дата не актуальна. Это значит ССД не работает";
            } else {
                $result['dcs_state'] = 1;
            }

            unset($max_date_of_sensors);
            $warnings[] = 'actionChangeAllDcsStatus конец';
        } catch (Exception $e) {
            $status = 0;
            $errors[] = "actionChangeAllDcsStatus. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = $result_main;
        return $result_main;
    }

    // actionGetNetworkIdByExternalId - метод тестирования результатов преобразования внешнего ключа страты в сетевой адрес метки
    // пример вызова: 127.0.0.1/super-test/get-network-id-by-external-id?net_id=709411&external_id=9049803
    public function actionGetNetworkIdByExternalId($external_id, $net_id)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $command = null;
        try {

            $warnings[] = 'actionGetNetworkIdByExternalId начало';


            $warnings[] = StrataJobController::getNetworkId($external_id, 0);     // вернет сам себя
            $warnings[] = StrataJobController::getNetworkId($external_id, 1);     // вернет net_id для сенсоров Strata
            $warnings[] = StrataJobController::getNetworkId($external_id, 2);     // вернет net_id для меток и шахтеров

            $warnings[] = StrataJobController::getExternalId($net_id, 0);         // вернет сам себя
            $warnings[] = StrataJobController::getExternalId($net_id, 1);         // вернет внешний ключ для сенсоров Strata
            $warnings[] = StrataJobController::getExternalId($net_id, 2);         // вернет внешний ключ для меток и шахтеров

            $warnings[] = 'тупой метод';
            $warnings[] = $external_id & 8388607;
            $warnings[] = $net_id & 8388607;

            $warnings[] = 'actionGetNetworkIdByExternalId конец';


        } catch (Exception $e) {
            $status = 0;
            $errors[] = "actionGetNetworkIdByExternalId. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // actionDeleteOldSituations - метод тестирования очистки старых ситуаций
    // пример вызова: 127.0.0.1/super-test/delete-old-situations
    public function actionDeleteOldSituations()
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $command = null;
        try {

            $warnings[] = 'actionDeleteOldSituations начало';


            $response = (new SituationCacheController())->deleteOldSituations();
            $result = $response['Items'];
            $errors = $response['errors'];
            $warnings = $response['warnings'];
            $status = $response['status'];
            $debug = $response['debug'];

            $warnings[] = 'actionDeleteOldSituations конец';


        } catch (Exception $e) {
            $status = 0;
            $errors[] = "actionDeleteOldSituations. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // actionDeleteOldEvents - метод тестирования очистки старых событий
    // пример вызова: 127.0.0.1/super-test/delete-old-events
    public function actionDeleteOldEvents()
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $command = null;
        try {

            $warnings[] = 'actionDeleteOldEvents начало';


            $response = (new EventCacheController())->deleteOldEvents();
            $result = $response['Items'];
            $errors = $response['errors'];
            $warnings = $response['warnings'];
            $status = $response['status'];
            $debug = $response['debug'];

            $warnings[] = 'actionDeleteOldEvents конец';


        } catch (Exception $e) {
            $status = 0;
            $errors[] = "actionDeleteOldEvents. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // actionGetAllParentsCompanies - метод тестирования метода получения списка вложенных компаний
    // пример вызова:   http://127.0.0.1/super-test/get-all-parents-companies?company_id=1 - нет вложения
    //                  http://127.0.0.1/super-test/get-all-parents-companies?company_id=2 - есть вложение
    public function actionGetAllParentsCompanies($company_id)
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $command = null;
        try {

            $warnings[] = 'actionGetAllParentsCompanies начало';


            $response = HandbookDepartmentController::GetAllParentsCompanies($company_id);
            $result[] = $response['Items'];
            $errors = $response['errors'];
            $warnings = $response['warnings'];
            $status = $response['status'];

            $warnings[] = 'actionGetAllParentsCompanies конец';


        } catch (Exception $e) {
            $status = 0;
            $errors[] = "actionGetAllParentsCompanies. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // actionGetAllParentsCompaniesWithAttachment - метод тестирования метода получения списка вложенных компаний c самими вложениями
    // пример вызова:   http://127.0.0.1/super-test/get-all-parents-companies-with-attachment
    public function actionGetAllParentsCompaniesWithAttachment()
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $command = null;
        try {

            $warnings[] = 'actionGetAllParentsCompaniesWithAttachment начало';


            $response = HandbookDepartmentController::getCompanyListInLine();
            $result = $response['Items'];
            $errors = $response['errors'];
            $warnings = $response['warnings'];
            $status = $response['status'];

            $warnings[] = 'actionGetAllParentsCompaniesWithAttachment конец';


        } catch (Exception $e) {
            $status = 0;
            $errors[] = "actionGetAllParentsCompaniesWithAttachment. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * actionRecoveryData - метод восстановления данных из json лога
     * входные параметры:
     *      user_action_log_id - ключ лога который надо восстановить
     * пример: http://127.0.0.1/super-test/recovery-data?user_action_log_id=233341
     * @return mixed
     */
    public function actionRecoveryData()
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $debug = array();
        $command = null;
        try {
            $post = Assistant::GetServerMethod();
            $user_action_log_id = $post['user_action_log_id'];

            $grafic_post = UserActionLog::findOne(['id' => $user_action_log_id]);

            if ($grafic_post) {
                $post_method = $grafic_post->post;
//                $warnings[]=$post_method;
                $post_method = Assistant::jsonRecoveryByPhp($post_method);

//                var_dump($post_method);
                $warnings[] = $post_method;

                $post_method = json_decode($post_method);
                $warnings[] = $post_method;

                switch (json_last_error()) {
                    case JSON_ERROR_NONE:
                        $errors[] = 'Ошибок нет';
                        break;
                    case JSON_ERROR_DEPTH:
                        $errors[] = 'Достигнута максимальная глубина стека';
                        break;
                    case JSON_ERROR_STATE_MISMATCH:
                        $errors[] = 'Некорректные разряды или несоответствие режимов';
                        break;
                    case JSON_ERROR_CTRL_CHAR:
                        $errors[] = 'Некорректный управляющий символ';
                        break;
                    case JSON_ERROR_SYNTAX:
                        $errors[] = 'Синтаксическая ошибка, некорректный JSON';
                        break;
                    case JSON_ERROR_UTF8:
                        $errors[] = 'Некорректные символы UTF-8, возможно неверно закодирован';
                        break;
                    default:
                        $errors[] = 'Неизвестная ошибка';
                        break;
                }

                $errors[] = json_last_error_msg();
                $namespace = 'frontend\controllers\\';
                $data = $post_method->data;
                $data = json_encode($data);                                                                             // входные парамтеры метода
                $controller = $post_method->controller;                                                                 // название вызываемого контроллера со стороны фронтенда
                $method = $post_method->method;                                                                         // название вызываемого метода контроллера со стороны фронтенда
                $subscribe = $post_method->subscribe;                                                                   // на какой канал оповещать

                $controller .= 'Controller';
                $controller = $namespace . $controller;

                $response = $controller::$method($data);

                $result = $response['Items'];
                $errors = array_merge($response['errors'], $errors);
                $warnings = array_merge($response['warnings'], $warnings);
                $status = $response['status'];
            } else {
                $warnings[] = "actionRecoveryData. Нет такого ключа лога";
            }
        } catch (Exception $e) {
            $status = 0;
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
    }

    // actionMainToro - главный метод синхронизации ТОРО
    // пример вызова:   http://127.0.0.1/super-test/main-toro
    public function actionMainToro()
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $debug = array();
        $command = null;
        try {

            $warnings[] = 'actionMainToro начало';


            $response = (new ToroController())->MainToro();
            $result = $response['Items'];
            $errors = $response['errors'];
            $debug = $response['debug'];
            $warnings = $response['warnings'];
            $status = $response['status'];

            $warnings[] = 'actionMainToro конец';


        } catch (Exception $e) {
            $status = 0;
            $errors[] = "actionMainToro. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // actionCopyToro - главный метод синхронизации ТОРО
    // пример вызова:   http://127.0.0.1/super-test/copy-toro
    public function actionCopyToro()
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $debug = array();
        $command = null;
        try {

            $warnings[] = 'actionCopyToro начало';

            $response = (new ToroController())->CopyToro();
            $result = $response['Items'];
            $errors = $response['errors'];
            $debug = $response['debug'];
            $warnings = $response['warnings'];
            $status = $response['status'];

            $warnings[] = 'actionCopyToro конец';


        } catch (Exception $e) {
            $status = 0;
            $errors[] = "actionCopyToro. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // actionSynchToro - метод синхронизации ТОРО
    // пример вызова:   http://127.0.0.1/super-test/synch-toro
    public function actionSynchToro()
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $debug = array();
        $command = null;
        try {

            $warnings[] = 'actionSynchToro начало';

            $response = ToroController::SynchToro();
            $result = $response['Items'];
            $errors = $response['errors'];
            $debug = $response['debug'];
            $warnings = $response['warnings'];
            $status = $response['status'];

            $warnings[] = 'actionSynchToro конец';


        } catch (Exception $e) {
            $status = 0;
            $errors[] = "actionSynchToro. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }


    // actionBuildTypicalObjectArray - метод получения списка типовых объектов системы
    // пример вызова:   http://127.0.0.1/super-test/build-typical-object-array
    public function actionBuildTypicalObjectArray()
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $debug = array();
        $command = null;
        try {

            $warnings[] = 'actionBuildTypicalObjectArray начало';

            $result = (new HandbookTypicalObjectController(1, "1"))->buildTypicalObjectArray();

        } catch (Exception $e) {
            $status = 0;
            $errors[] = "actionBuildTypicalObjectArray. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // actionCopyPredExam - главный метод синхронизации Предсменного экзаменатора
    // пример вызова:   http://127.0.0.1/super-test/copy-pred-exam
    public function actionCopyPredExam()
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $debug = array();
        $command = null;
        try {

            $warnings[] = 'actionCopyPredExam начало';

            $response = (new PredExamController())->CopyPredExam();
            $result = $response['Items'];
            $errors = $response['errors'];
            $debug = $response['debug'];
            $warnings = $response['warnings'];
            $status = $response['status'];

            $warnings[] = 'actionCopyPredExam конец';


        } catch (Exception $e) {
            $status = 0;
            $errors[] = "actionCopyPredExam. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // actionSynchPredExam - метод синхронизации Предсменного экзаменатора
    // пример вызова:   http://127.0.0.1/super-test/synch-pred-exam
    public function actionSynchPredExam()
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $debug = array();
        $command = null;
        try {

            $warnings[] = 'actionSynchPredExam начало';

            $response = PredExamController::SynchPredExam();
            $result = $response['Items'];
            $errors = $response['errors'];
            $debug = $response['debug'];
            $warnings = $response['warnings'];
            $status = $response['status'];

            $warnings[] = 'actionSynchPredExam конец';


        } catch (Exception $e) {
            $status = 0;
            $errors[] = "actionSynchPredExam. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // actionMainPredExam - главный метод синхронизации предсменного экзаменатора
    // пример вызова:   http://127.0.0.1/super-test/main-pred-exam
    public function actionMainPredExam()
    {
        $status = 1;
        $warnings = array();
        $errors = array();
        $result = array();
        $debug = array();
        $command = null;
        try {

            $warnings[] = 'actionMainPredExam начало';


            $response = (new PredExamController())->MainPredExam();
            $result = $response['Items'];
            $errors = $response['errors'];
            $debug = $response['debug'];
            $warnings = $response['warnings'];
            $status = $response['status'];

            $warnings[] = 'actionMainPredExam конец';


        } catch (Exception $e) {
            $status = 0;
            $errors[] = "actionMainPredExam. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * Метод actionUpdateEdgeSechenie() - метод обновления сечения выработок (ставит 16)
     * пример: http://127.0.0.1/super-test/update-edge-sechenie?mine_id=250
     */
    public function actionUpdateEdgeSechenie()
    {
        // Стартовая отладочная информация
        $method_name = 'actionUpdateEdgeSechenie';                                                                             // название логируемого метода
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
        $count_edge = 0;                    // общее количество эджей
        $count_new_parameter_co = 0;    // количество созданных параметров
        $count_edge_save = 0;               // количество вставленных записей в БД
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
            $post = Assistant::GetServerMethod();
            $mine_id = $post['mine_id'];
            $edges = (new Query())
                ->select('
                    edge.id as edge_id,
                ')
                ->from('edge')
                ->innerJoin('place', 'place.id=edge.place_id')
                ->where(['place.mine_id' => $mine_id])
                ->all();
            if (!$edges) {
                throw new Exception('actionUpdateEdgeSechenie. Список выработок пуст');
            }
            $count_edge = count($edges);
            $edges_parameter_ch = (new Query())
                ->select('
                    id,
                    edge_id
                ')
                ->from('edge_parameter')
                ->where(['parameter_type_id' => 1, 'parameter_id' => 130])
                ->indexBy('edge_id')
                ->all();
            $date_time = Assistant::GetDateNow();
            foreach ($edges as $edge) {
                if (!isset($edges_parameter_ch[$edge['edge_id']])) {
                    $new_edge_parameter = new EdgeParameter();
                    $new_edge_parameter->edge_id = $edge['edge_id'];
                    $new_edge_parameter->parameter_id = 130;
                    $new_edge_parameter->parameter_type_id = 1;
                    if (!$new_edge_parameter->save()) {
                        throw new Exception('actionUpdateEdgeSechenie. Не смог сохранить параметр 130/1 в EdgeParameter');
                    }
                    $new_edge_parameter->refresh();
                    $edges_parameter_ch[$edge['edge_id']]['id'] = $new_edge_parameter->id;
                    $edges_parameter_ch[$edge['edge_id']]['edge_id'] = $edge['edge_id'];
                    $count_new_parameter_co++;
                }

                $length_to_db[] = array(
                    'edge_parameter_id' => $edges_parameter_ch[$edge['edge_id']]['id'],
                    'date_time' => $date_time,
                    'value' => '16',
                    'status_id' => 1
                );
            }
            if (isset($length_to_db)) {
                $count_edge_save = Yii::$app->db->createCommand()->batchInsert('edge_parameter_handbook_value', ['edge_parameter_id', 'date_time', 'value', 'status_id'], $length_to_db)->execute();
                $warnings[] = $method_name . '. Количество вставленных записей в edge_parameter_handbook_value' . $count_edge_save;
            }
//            $result=$edges;


        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        // запись в БД начала выполнения скрипта
        $warnings[] = array(
            '$count_edge. Общее количество эджей' => $count_edge,
            '$count_new_parameter_co. Количество созданных параметров сечения эджей' => $count_new_parameter_co,
            '$count_edge_save. Количество вставленных данных в БД' => $count_edge_save,
        );

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

        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    /**
     * actionXmlRpc - Метод вызова удаленной процедуры на исполнение с возвратом данных через очередь
     * НЕ ОТЛАЖЕНО, НО ВЫЗЫВАЕТСЯ
     * Пример: 127.0.0.1/super-test/xml-rpc
     */
    public function actionXmlRpc()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей
        $log = new LogAmicumFront("actionXmlRpc");

        try {
            $log->addLog("Начало выполнения метода");

            $rpc = new RpcClient();
            $result = $rpc->call(30);

            $log->addLog("Окончил выполнение метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * actionClearCacheHandbook - Метод очистки кеша справочников
     * Пример: 127.0.0.1/super-test/clear-cache-handbook
     */
    public function actionClearCacheHandbook()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("actionClearCache");

        try {
            $log->addLog("Начало выполнения метода");

            HandbookCachedController::clearWorkerCache();
            $log->addLog("КЕШ работников очищен");
            HandbookCachedController::clearConveyorEquipmentsCache();
            $log->addLog("КЕШ оборудования очищен");
            HandbookCachedController::clearPlaceCache();
            $log->addLog("КЕШ мест очищен");
            HandbookCachedController::clearDepartmentCache();
            $log->addLog("КЕШ департаментов очищен");
            HandbookCachedController::clearOperationCache();
            $log->addLog("КЕШ операций очищен");

            $log->addLog("Окончил выполнение метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * actionRunInitMine - Метод инициализации кеша схемы шахты
     * Пример: 127.0.0.1/super-test/run-init-scheme-mine?mine_id=290&edge_id=348176
     */
    public function actionRunInitSchemeMine()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("actionRunInitSchemeMine");

        try {
            $log->addLog("Начало выполнения метода");

            $post = Assistant::GetServerMethod();
            $mine_id = $post['mine_id'];
            $edge_id = $post['edge_id'];

            $response = (new EdgeCacheController)->runInit($mine_id, $edge_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка выполнеия метода runInit");

            }
            $result = $response['warnings']['initEdgeScheme'];


            $log->addLog("Окончил выполнение метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

}

