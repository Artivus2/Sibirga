<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\handbooks;


use backend\controllers\Assistant as AssistantBackend;
use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\HandbookCachedController;
use frontend\controllers\industrial_safety\CheckingController;
use frontend\controllers\PhysicalScheduleController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Audit;
use frontend\models\Briefing;
use frontend\models\CheckKnowledge;
use frontend\models\Company;
use frontend\models\CompanyDepartment;
use frontend\models\Department;
use frontend\models\DepartmentParameter;
use frontend\models\DepartmentParameterSummary;
use frontend\models\DepartmentParameterSummaryWorkerSettings;
use frontend\models\DepartmentParameterValue;
use frontend\models\EventPb;
use frontend\models\Expertise;
use frontend\models\Injunction;
use frontend\models\MedReport;
use frontend\models\PhysicalSchedule;
use frontend\models\Worker;
use frontend\models\WorkerSiz;
use Throwable;
use Yii;
use yii\db\Query;
use yii\web\Controller;
use yii\web\Response;

//use frontend\controllers\Assistant;
require_once __DIR__ . '/../../controllers/../../DebugCode.php';

//ToDo: переименовать в соответствии с остальными справочниками

class DepartmentController extends Controller
{
    // настоящий контроллер содержит методы работы с параметрами департамента, в т.ч. сводными, добавлению их в кеш, чтению из кеша
    // и записью уведомлений и настроек в БД
    // уведомления находятся на центральной странице системы АМИКУМ и выводят показатели работы подразделения на экран.
    // настоящий контроллер опирается на следующие модели БД:

    // summary_department_parameter                 - применяется для хранения сводных параметров по подразделениям.
    //                                          Используется на главной странице для вывода уведомлений, сообщений и т.д.
    //
    // summary_department_parameter_worker_settings - таблица содержит текущие настройки для конкретного пользователя.
    //                                          Если у пользователя не задано свойство и он является сотрудником данного подразделения,
    //                                          то ему отображаются все параметры. Если параметр задан, то в зависимости от статуса ему
    //                                          будет или не будет отображаться этот параметр.Нужно если выводить параметр
    //                                          руководителю находящемусяв другом подразделении. или если наоборот, что бы не выводить
    //                                          текущему пользователю какой либо параметр.
    // список разработанных методов:
    // AddCompanyWithDepartmentDB                       - метод добавления компании и департамента в БД
    // AddDepartmentDB                                  - метод добавления департамента в БД и создание связки с компанией
    // AddCompanyDB                                     - метод добавления компании в БД
    // WriteDepDB                                       - центральный метод записи и обновления значения параметра в БД
    // WriteDepParamDB                                  - запись в базу конкретного параметра департамента БД
    // WriteDepParamValueDB                             - запись в базу сводного значения параметра департамента БД
    // WriteDepParamSummaryDB                           - запись в базу сводного значения параметра департамента БД
    // WriteDepParamSummaryWorkerSettingDB              - запись в базу настройки сводного значения параметра департамента БД
    //                                                    сохраняет нужно или нет отображать данному пользователю данное уведомление
    // UpdateStatusDepParamSummaryWorkerSettingDB       - обновить статус отображения/скрытия уведомлений на главном окне для конкретного пользователя
    // publicUpdateStatusDepParamSummaryWorkerSettingDB - обновить статус отображения/скрытия уведомлений на главном окне для конкретного пользователя
    /* UpdateReadMessageDepParamSummaryWorkerSettingDB  - обновить состояние последнего просмотра уведомлений, а так же
     *                                                  установить отметку просмотрены уведомления или нет
     *                                                  логика такая - когда пользователь открыл главную страницу и нажимает на уведомления прочтены, то у него менятеся статус на прочтены уведомления
     *                                                  и в последующем у вас новые уведомления оно не появляется. в случае появления новых уведомлений, мы сравниваем с датой последнего
     *                                                  открытия/прочтения формы и по разному отправляем данные на фронт. если пользователь хочет, что бы было отображение, что есть новые уведомления,
     *                                                  то он ставит $read_message_status равным 28, что означет, что не прочтено и так до следующего открытия у авс есть новые уведомления.
     *
     */
    // UpdateUnReadMessageDepParamSummaryWorkerSettingDB    - установить статус сообщение не прочтено
    // ReadDepParamSummaryDB                                - Чтение из БД всех или по конкретному company_department_id параметров департаментов и их значений
    // ReadDepParamSummarySettingsDB                        - Чтение из БД всех или по конкретному employee_id его настроек
    // ReadDepParamSummarySettingsDBWithParam               - Чтение из БД всех или по конкретному employee_id его настроек с его параметрами
    // GetDepParameter                                      - Чтение из БД всех параметров и настроек по конкретному employee_id
    // CountNewInjunction                                   - Метод смены сводного значения параметра по подраздалениям
    // CountPab                                             - Расчёт количества паб для показателей в блоке уведомлений
    // CountCheckingPlan                                    - Расчёт количества запланированных аудитов для показателей в блоке уведомлений
    // CountExpertise                                       - Расчёт количества экспертиз промышленной безопасности для показателей в блоке уведомлений
    // CountReplacementPPE                                  - Расчёт количества СИЗов необходимых к замене для показателей в блоке уведомлений
    // CountCheckup                                         - Расчёт количества медосмотров для показателей в блоке уведомлений
    // CountInstructionNotifications                        - Расчёт количественных показателей для блока уведомлений по "Запланирован инструктаж"
    // SetDepartmentParameterSettings                       - Добавление настроек только что созданному аккаунту
    // ParameterAddOrUpdate                                 - обновления/добавление параметров, сводных параметров
    // Find и  FindDepartment                               - два метода для получения списка нижележащих участков для участка
    // CountCheckKnowledge                                  - Метод расчёта показателей для блока уведомлений "Запланировано обучение"
    // CountInquiry                                         - Метод расчёта показателей для блока уведомлений "Происшествие"
    // CountCheckCertifiaction                              - Метод расчёта показателей для блока уведомлений "Назначена аттестация"
    // GetDepartmentsWithWorkersContingent                  - Получение участков с людьми у которых есть контингент и которые работают
    // SetDefaultParametersForUser                          - создание параметров по умолчанию для пользователя в личном кабинете

    // GetDepartmentListWithWorkers                         - метод получения списка департаментов и работников в них
    // GetDepartmentListRecursiv                            - метод получения вложенного списка департаментов без работников

    // FindDepartment()                                     - метода для получения списка нижележащих участков для участка
    // FindDepartmentRecursiv                               - метод рекурсивного поиска вложений департаментов/компаний
    // Find()                                               - метода для получения списка нижележащих участков для участка (вызывается в FindDepartment)
    // GetAttachDeprtmentByUpper()                          - Метод получения вложенных департаментов на основе вышестоящего департамента


    // разработать следующие методы:
    // получение списка уведомлений из кеша по подразделению
    // обновление в кеше значения сводного параметра подразделения

    // чтение из БД значений сводного параметра подразделения
    // одновременная запись в кеш и бд - центральный метод работы с уведомлениями
    // одновременное обновление кеша и бд уведомлений
    // рассылка обновления по подпискам по пользователям через вебсокет
    // настройка видимости пунктов уведомлений
    /**@var int Параметр: Предписание */
    const PARAMETER_INJUNCTION = 513;
    /**@var int Статус: Актуально */
    const STATUS_ACTUAL = 1;
    /**@var int Параметр: ПАБ */
    const PARAMETER_PAB = 516;
    /**@var int Параметр: Запланирован аудит */
    const PARAMETER_CHECKING_PLAN = 515;
    /**@var int Параметр: Запланирован ЭПБ */
    const PARAMETER_PLANNDED_ISE = 518;
    /**@var int Параметр: Замена СИЗ */
    const PARAMETER_REPLACEMENT_PPE = 522;
    /**@var int Параметр: Медосмотр */
    const PARAMETER_CHECKUP = 514;
    /**@var int Тип дня: нормальный рабочий день */
    const WORKING_TIME_NORMAL = 1;
    /**@var int Тип инструктажа: Повторный */
    const TYPE_BRIEFING_TWO = 2;
    /**@var int Параметр: Запланирован инструктаж */
    const PARAMETER_BRIEFING = 520;
    /**@var int Параметр тип: Вычисляемый параметр */
    const PARAMETER_CALCULATED = 3;
    const STATUS_ACTIVE = 1;
    /**@var int тип инструктажа. 2 - значит он повторный */
    const DAY_TYPE_ONE = 76;
    /**@var int количество дней для первого типа инструктажа */
    const DAY_TYPE_TWO = 90;
    /**@var int Тип проверки знаний: Проверка знаний (ИТР) */
    const TYPE_CHECK_KNOWLEDGE_ITR = 1;
    /**@var int Тип проверки знаний: Проверка знаний (работинки) */
    const TYPE_CHECK_KNOWLEDGE_WORKERS = 2;
    /**@var int Тип проверки знаний: Аттестация (ИТР) */
    const TYPE_CHECK_KNOWLEDGE_ATT = 3;
    /**@var int Параметр: Запланировано обучение */
    const PLANNED_CHECK_KNOWLEDGE = 519;
    /**@var int Параметр: Назначена аттестация */
    const PLANNED_CHECK_KNOWLEDGE_ATT = 517;
    /**@var int Параметр: Происшествие */
    const PARAMETER_ACCIDENT = 694;
    const NOW_CHECKUP = 2;
    const PLANNED_CHECKUP = 1;

    public function actionTestWrite()
    {
        //$post = Assistant::GetServerMethod();
        $status = 1;                                                                                                      //флаг успешного выполнения метода
        $warnings = array();                                                                                              // массив предупреждений
        $errors = array();                                                                                                // массив ошибок
        $result = array();                                                                                                // промежуточный результирующий массив
        //DepartmentParameter::deleteAll();
        //метод сохранения сводного параметра и конкретного параметра департамена и их значения в БД
        $response_amicum_method = self::WriteDepDB(
            802,
            6,
            3,
            17,
            17,
            null,
            'pr',
            null,
            1
        );
        $result[] = json_encode($response_amicum_method['Items']);
        $status *= $response_amicum_method['status'];
        $warnings[] = $response_amicum_method['warnings'];
        $errors[] = $response_amicum_method['errors'];
        $department_parameter = $response_amicum_method['id'];

        $response_amicum_method = self::WriteDepDB(
            802,
            513,
            3,
            7,
            7,
            null,
            'pr',
            null,
            1
        );
        $result[] = json_encode($response_amicum_method['Items']);
        $status *= $response_amicum_method['status'];
        $warnings[] = $response_amicum_method['warnings'];
        $errors[] = $response_amicum_method['errors'];
        $department_parameter = $response_amicum_method['id'];

        //сохранение настройки конкретного сводного параметра департамента в БД
        $response_amicum_method = self::WriteDepParamSummaryWorkerSettingDB(
            $response_amicum_method['id'],
            1301,
            16,
            null,
            0
        );

        $result[] = json_encode($response_amicum_method['Items']);
        $status *= $response_amicum_method['status'];
        $warnings[] = $response_amicum_method['warnings'];
        $errors[] = $response_amicum_method['errors'];

        //меняем настройку уведомления на отключить отображать 16 /неотображать 15
        $response_amicum_method = $this->UpdateStatusDepParamSummaryWorkerSettingDB(
            $department_parameter,
            1301,
            15
        );
        $result[] = json_encode($response_amicum_method['Items']);
        $status *= $response_amicum_method['status'];
        $warnings[] = $response_amicum_method['warnings'];
        $errors[] = $response_amicum_method['errors'];

        //меняем дату доставки уведомления пользователю
        $response_amicum_method = $this->UpdateReadMessageDepParamSummaryWorkerSettingDB(
            $department_parameter,
            1301,
            null,
            30
        );
        $result[] = json_encode($response_amicum_method['Items']);
        $status *= $response_amicum_method['status'];
        $warnings[] = $response_amicum_method['warnings'];
        $errors[] = $response_amicum_method['errors'];


        //меняем отметку у пользователя на не прочтено
        $response_amicum_method = $this->UpdateUnReadMessageDepParamSummaryWorkerSettingDB(
            $department_parameter,
            1301,
            28
        );
        $result[] = json_encode($response_amicum_method['Items']);
        $status *= $response_amicum_method['status'];
        $warnings[] = $response_amicum_method['warnings'];
        $errors[] = $response_amicum_method['errors'];

        //читаем все параметры департаментов и их значения

        $result[] = json_encode($response_amicum_method['Items']);
        $status *= $response_amicum_method['status'];
        $warnings[] = $response_amicum_method['warnings'];
        $errors[] = $response_amicum_method['errors'];

        //метод сохранения сводного параметра и конкретного параметра департамена и их значения в БД
        $response_amicum_method = self::WriteDepDB(
            802,
            514,
            3,
            70,
            70,
            null,
            'pr',
            null,
            1
        );
        $result[] = json_encode($response_amicum_method['Items']);
        $status *= $response_amicum_method['status'];
        $warnings[] = $response_amicum_method['warnings'];
        $errors[] = $response_amicum_method['errors'];
        $department_parameter = $response_amicum_method['id'];

        //сохранение настройки конкретного сводного параметра департамента в БД
        $response_amicum_method = self::WriteDepParamSummaryWorkerSettingDB(
            $response_amicum_method['id'],
            1301,
            16,
            null,
            0
        );

        //метод сохранения сводного параметра и конкретного параметра департамена и их значения в БД
        $response_amicum_method = self::WriteDepDB(
            802,
            515,
            3,
            71,
            71,
            null,
            'pr',
            null,
            1
        );
        $result[] = json_encode($response_amicum_method['Items']);
        $status *= $response_amicum_method['status'];
        $warnings[] = $response_amicum_method['warnings'];
        $errors[] = $response_amicum_method['errors'];
        $department_parameter = $response_amicum_method['id'];

        //сохранение настройки конкретного сводного параметра департамента в БД
        $response_amicum_method = self::WriteDepParamSummaryWorkerSettingDB(
            $response_amicum_method['id'],
            1301,
            16,
            null,
            0
        );

        //метод сохранения сводного параметра и конкретного параметра департамена и их значения в БД
        $response_amicum_method = self::WriteDepDB(
            802,
            516,
            3,
            72,
            72,
            null,
            'pr',
            null,
            1
        );
        $result[] = json_encode($response_amicum_method['Items']);
        $status *= $response_amicum_method['status'];
        $warnings[] = $response_amicum_method['warnings'];
        $errors[] = $response_amicum_method['errors'];
        $department_parameter = $response_amicum_method['id'];

        //сохранение настройки конкретного сводного параметра департамента в БД
        $response_amicum_method = self::WriteDepParamSummaryWorkerSettingDB(
            $response_amicum_method['id'],
            1301,
            16,
            null,
            0
        );

        //метод сохранения сводного параметра и конкретного параметра департамена и их значения в БД
        $response_amicum_method = self::WriteDepDB(
            802,
            347,
            3,
            72,
            72,
            null,
            'pr',
            null,
            1
        );
        $result[] = json_encode($response_amicum_method['Items']);
        $status *= $response_amicum_method['status'];
        $warnings[] = $response_amicum_method['warnings'];
        $errors[] = $response_amicum_method['errors'];
        $department_parameter = $response_amicum_method['id'];

        //сохранение настройки конкретного сводного параметра департамента в БД
        $response_amicum_method = self::WriteDepParamSummaryWorkerSettingDB(
            $response_amicum_method['id'],
            1011451,
            16,
            null,
            0
        );
        $response_amicum_method = $this->ReadDepParamSummaryDB(
            802

        );


        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    public function actionTestRead()
    {
        //$post = Assistant::GetServerMethod();
        $status = 1;                                                                                                      //флаг успешного выполнения метода
        $warnings = array();                                                                                              // массив предупреждений
        $errors = array();                                                                                                // массив ошибок
        $result = array();                                                                                                // промежуточный результирующий массив
        try {
            //DepartmentParameter::deleteAll();

            $response_amicum_method = $this->ReadDepParamSummaryDB(
                802

            );
            $result[] = json_encode($response_amicum_method['Items']);
            $status *= $response_amicum_method['status'];
            $warnings[] = $response_amicum_method['warnings'];
            $errors[] = $response_amicum_method['errors'];

            $response_amicum_method = $this->ReadDepParamSummarySettingsDB(
                1301

            );
            $result[] = json_encode($response_amicum_method['Items']);
            $status *= $response_amicum_method['status'];
            $warnings[] = $response_amicum_method['warnings'];
            $errors[] = $response_amicum_method['errors'];
        } catch (Exception $e) {
            $status = 0;
            $errors[] = $e->getMessage();
        }


        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // WriteDepDB - метод создания или обновления параметра для конкретного подразделения в базе данных
    // если параметра не было, то он его создает, в ином случае находит, то что передается
    // и записывает как есть передаваемое значение

    public static function WriteDepDB($company_department_id, $parameter_id, $parameter_type_id, $value, $value_sum = null, $date_time = null, $type_find = "pr", $dep_param_id = null, $status = 1)
    {
        $status = 1;                                                                                                      //флаг успешного выполнения метода
        $warnings = array();                                                                                              // массив предупреждений
        $errors = array();                                                                                                // массив ошибок
        $result = array();                                                                                                // промежуточный результирующий массив
        if (!is_null($company_department_id) and $company_department_id != "" and //проверка на наличие переданной привязки компании и департамента
            !is_null($parameter_id) and $parameter_id != "" and //проверка наличия переданного ключа параметра
            !is_null($parameter_type_id) and $parameter_type_id != "" and //проверка наличия переданного ключа типа параметра
            !is_null($value) and $value !== ""                                                                             //проверка на заданность записываемого значения в БД
        ) {
            $warnings[] = "WriteDepDB. данные успешно переданы";
            $warnings[] = "WriteDepDB. Входной массив данных ключ привязки ком деп =$company_department_id, 
            ключ параметра = $parameter_id, значение равно $value";
            try {
                $response_amicum_method = self::WriteDepParamDB($company_department_id, $parameter_id, $parameter_type_id, $dep_param_id, $type_find);
                $warnings[] = "WriteDepDB. распарсиваю возврат из метода WriteDepParamDB";
                $status *= $response_amicum_method['status'];
                $result[] = json_encode($response_amicum_method['Items']);
                $dep_param_id = $response_amicum_method['id'];
                $warnings[] = $response_amicum_method['warnings'];
                $errors = $response_amicum_method['errors'];
                $warnings[] = "WriteDepDB. распарсил возврат из метода WriteDepParamDB";
                if ($status == 1) {
                    $warnings[] = "WriteDepDB. Запись значений в БД";
                    $response_amicum_method = self::WriteDepParamValueDB($dep_param_id, $value, $date_time, $status);
                    $warnings[] = "WriteDepDB. распарсиваю возврат из метода WriteDepParamValueDB";
                    $result [] = json_encode($response_amicum_method['Items']);
                    $status *= $response_amicum_method['status'];
                    $warnings[] = $response_amicum_method['warnings'];
                    $errors[] = $response_amicum_method['errors'];
                    if (!is_null($value_sum)) {
                        $response_amicum_method = self::WriteDepParamSummaryDB($company_department_id, $parameter_id, $parameter_type_id, $value_sum, $dep_param_id, 'id', $date_time);
                        $result [] = json_encode($response_amicum_method['Items']);
                        $status *= $response_amicum_method['status'];
                        $warnings[] = $response_amicum_method['warnings'];
                        $errors[] = $response_amicum_method['errors'];
                    }

                }

            } catch (Exception $e) {
                $status = 0;
                $errors[] = $e->getMessage();
                $errors[] = $e->getLine();
            }
        } else {
            $errors[] = "WriteDepParamDB. Переданы не все входные параметры";
            $status = 0;
        }
        $result_main = array('Items' => $result, 'id' => $dep_param_id, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    //WriteDepParamSummaryDB - запись в базу сводного значения параметра департамента БД
    private static function WriteDepParamSummaryDB($company_department_id, $parameter_id, $parameter_type_id, $value_sum, $dep_param_sum_id, $type_find = "pr", $date_time = null)
    {
        $status = 1;                                                                                                      //флаг успешного выполнения метода
        $warnings = array();                                                                                              // массив предупреждений
        $errors = array();                                                                                                // массив ошибок
        $result = array();                                                                                                // промежуточный результирующий массив
        //эта часть создана для унификации метода и для повышения скорости работы в целом там, где это нужно
        if ($type_find == "pr") {                                                                                    //более долгий поиск по главному ключу таблицы, но более удобный в других методах
            $dep_param_summ = DepartmentParameterSummary::findOne([
                'company_department_id' => $company_department_id,
                'parameter_type_id' => $parameter_type_id,
                'parameter_id' => $parameter_id
            ]);
            $warnings[] = "WriteDepParamSummaryDB. поиск по первичному ключу";
        } elseif ($type_find == "id" and !is_null($dep_param_sum_id))                                                                               //более быстрий поиск по уникальному индексу id если он известен и получен из кеша
        {
            $dep_param_summ = DepartmentParameterSummary::findOne([
                'id' => $dep_param_sum_id
            ]);
            $warnings[] = "WriteDepParamSummaryDB. поиск по уникальному индексу id";
        } else {
            $errors[] = "WriteDepParamSummaryDB. Переданы не все входные параметры";
            $status = 0;
            $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
            return $result_main;
        }

        if (!$dep_param_summ) {
            $dep_param_summ = new DepartmentParameterSummary();                                                      //создаем  модель сводного параметра для записи
            $warnings[] = "WriteDepParamSummaryDB. Сводный параметр для подразделения создан с 0";
        } else {
            $dep_param_sum_id = $dep_param_summ->id;
            $warnings[] = "WriteDepParamSummaryDB. Сводный параметр был id: " . $dep_param_summ->id;
        }
        $warnings[] = "WriteDepParamSummaryDB. Сводный параметр id равен: " . $dep_param_sum_id;
        $dep_param_summ->id = (int)$dep_param_sum_id;
        $dep_param_summ->company_department_id = $company_department_id;
        $dep_param_summ->parameter_id = $parameter_id;
        $dep_param_summ->parameter_type_id = $parameter_type_id;
        $dep_param_summ->value = (int)$value_sum;
        if (is_null($date_time))
            $dep_param_summ->date_time = date('Y-m-d H:i:s.U');
        else
            $dep_param_summ->date_time = date('Y-m-d H:i:s.U', $date_time);

        if ($dep_param_summ->save())   // и проверяем наличие в нем нужных нам полей
        {
            $status *= 1;
            $warnings[] = "WriteDepParamSummaryDB. Значение успешно сохранено. Новое значение: " . $dep_param_summ->value;
        } else {
            $errors[] = "WriteDepParamSummaryDB. Ошибка сохранения модели DepartmentParameterSummary";
            $errors[] = $dep_param_summ->errors;
            $status = 0;
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    //WriteDepParamDB - запись в базу конкретного параметра департамента БД
    //чтобы искать по главному ключу нужно передать 3 основных параметра и type_find="pr"
    //чтобы искать по уникальному айди нужно дополнительно передать id и type_find="id"
    private static function WriteDepParamDB($company_department_id, $parameter_id, $parameter_type_id, $dep_param_id = null, $type_find = "pr")
    {
        $status = 1;                                                                                                      //флаг успешного выполнения метода
        $warnings = array();                                                                                              // массив предупреждений
        $errors = array();                                                                                                // массив ошибок
        $result = array();                                                                                                // промежуточный результирующий массив
        //эта часть создана для унификации метода и для повышения скорости работы в целом там, где это нужно
        $warnings[] = "WriteDepParamDB. Начал выполнять метод";
        if ($type_find == "pr") {                                                                                    //более долгий поиск по главному ключу таблицы, но более удобный в других методах
            $dep_param = DepartmentParameter::findOne([
                'company_department_id' => $company_department_id,
                'parameter_type_id' => $parameter_type_id,
                'parameter_id' => $parameter_id
            ]);
            $warnings[] = "WriteDepParamDB. поиск конкретного параметра по первичному ключу";
        } elseif ($type_find == "id" and !is_null($dep_param_id))                                                                               //более быстрий поиск по уникальному индексу id если он известен и получен из кеша
        {
            $dep_param = DepartmentParameter::findOne([
                'id' => $dep_param_id
            ]);
            $warnings[] = "WriteDepParamDB. поиск конкретного параметра по уникальному индексу id";
        } else {
            $errors[] = "WriteDepParamDB. Переданы не все входные параметры";
            $status = 0;
            $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
            return $result_main;
        }

        if (!$dep_param) {
            $dep_param = new DepartmentParameter();                                                      //создаем  модель сводного параметра для записи
            $warnings[] = "WriteDepParamDB. Конкретный параметр для подразделения создан с 0";
            $dep_param->company_department_id = $company_department_id;
            $dep_param->parameter_id = $parameter_id;
            $dep_param->parameter_type_id = $parameter_type_id;

            if ($dep_param->save())   // и проверяем наличие в нем нужных нам полей
            {
                $status *= 1;
                $dep_param->refresh();
                $dep_param_id = $dep_param->id;
                $warnings[] = "WriteDepParamDB. Значение успешно сохранено. Новое значение id: " . $dep_param_id;
            } else {
                $errors[] = "WriteDepParamDB. Ошибка сохранения модели DepartmentParameterSummary";
                $errors[] = $dep_param->errors;
                $status = 0;
            }
        } else {
            $dep_param_id = $dep_param->id;
            $warnings[] = "WriteDepParamDB. Уникальный ключ конкретного параметра уже существовал и id: " . $dep_param_id;
        }

        $warnings[] = "WriteDepParamDB. Закончил выполнять метод";
        $result_main = array('Items' => $result, 'id' => $dep_param_id, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    //WriteDepParamValueDB - запись в базу сводного значения параметра департамента БД
    private static function WriteDepParamValueDB($dep_param_id, $value, $date_time = null, $status_id = 1)
    {
        $status = 1;                                                                                                      //флаг успешного выполнения метода
        $warnings = array();                                                                                              // массив предупреждений
        $errors = array();                                                                                                // массив ошибок
        $result = array();                                                                                                // промежуточный результирующий массив
        $warnings[] = "WriteDepParamValueDB. Начал выполнять метод";
        $dep_param_value_id = 0;                                                                                          //клюя возвращаемого значения
        $dep_param_value = new DepartmentParameterValue();                                                      //создаем  модель сводного параметра для записи

        $dep_param_value->department_parameter_id = $dep_param_id;
        $dep_param_value->status_id = $status_id;
        $dep_param_value->value = (string)$value;
        if (is_null($date_time))
            $dep_param_value->date_time = date('Y-m-d H:i:s.U');
        else
            $dep_param_value->date_time = date('Y-m-d H:i:s.U', $date_time);
        $warnings[] = "WriteDepParamValueDB. Начал сохранять метод";
        if ($dep_param_value->save())   // и проверяем наличие в нем нужных нам полей
        {
            $status *= 1;
            $dep_param_value->refresh();
            $dep_param_value_id = $dep_param_value->id;
            $warnings[] = "WriteDepParamValueDB. Значение успешно сохранено. Новый id значение: " . $dep_param_value_id;
        } else {
            $errors[] = "WriteDepParamValueDB. Ошибка сохранения модели DepartmentParameterSummary";
            $errors[] = $dep_param_value->errors;
            $status = 0;
        }
        $result_main = array('Items' => $result, 'id' => $dep_param_value_id, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /* WriteDepParamSummaryWorkerSettingDB - запись в базу настройки сводного значения параметра департамента БД
    * сохраняет нужно или нет отображать данному пользователю данное уведомление,
     * а так же сохраняет инормацию, когда он просмотрел последнее уведомление и пометил его как прочтенное.
     * $department_parameter_id - конкретный параметр департамента
    * $employee_id - ключ сотрудника, для которого делается проверка сохранение настройки отображения уведомления
    * $status_id - ключ статуса настройки отображать 16 /неотображать 15
    * $date_time - дата и время последнего прочтения уведомлений
    * $read_message_status - статус прочтения сообщений 30 - прочтено/ 28 доставлено
    */
    private static function WriteDepParamSummaryWorkerSettingDB($department_parameter_id, $employee_id, $status_id = 16, $date_time = null, $read_message_status = null)
    {
        $status = 1;                                                                                                      //флаг успешного выполнения метода
        $warnings = array();                                                                                              // массив предупреждений
        $errors = array();                                                                                                // массив ошибок
        $result = array();                                                                                                // промежуточный результирующий массив
        $warnings[] = "WriteDepParamSummaryWorkerSettingDB. Начал выполнять метод";
        $dep_param_value_id = 0;                                                                                          //клюя возвращаемого значения
        if ($status_id == 15 or $status_id == 16) {
            $dep_param_sum_wor_set_value = new DepartmentParameterSummaryWorkerSettings();

            $dep_param_sum_wor_set_value->department_parameter_id = $department_parameter_id;
            $dep_param_sum_wor_set_value->status_id = $status_id;
            $dep_param_sum_wor_set_value->employee_id = $employee_id;
            $dep_param_sum_wor_set_value->read_message_status = $read_message_status;
            if (is_null($date_time))
                $dep_param_sum_wor_set_value->date_time = date('Y-m-d H:i:s.U');
            else
                $dep_param_sum_wor_set_value->date_time = date('Y-m-d H:i:s.U', $date_time);
            $warnings[] = "WriteDepParamSummaryWorkerSettingDB. Начал сохранять метод";
            if ($dep_param_sum_wor_set_value->save())   // и проверяем наличие в нем нужных нам полей
            {
                $status *= 1;
                $dep_param_sum_wor_set_value->refresh();
                $dep_param_sum_wor_set_value_id = $dep_param_sum_wor_set_value->id;
                $warnings[] = "WriteDepParamSummaryWorkerSettingDB. Значение успешно сохранено. Новый id значение: " . $dep_param_sum_wor_set_value_id;
            } else {
                $errors[] = "WriteDepParamSummaryWorkerSettingDB. Ошибка сохранения модели DepartmentParameterSummaryWorkerSettings";
                $errors[] = $dep_param_sum_wor_set_value->errors;
                $status = 0;
            }
        } else {
            $errors[] = "UpdateDepParamSummaryWorkerSettingDB. Не верно передан статус отображения уведомления, должен быть 15/16";
            $status = 0;
        }
        $result_main = array('Items' => $result, 'id' => $dep_param_value_id, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /* Метод добавления подразделений и связывания их с компанией.
         * Входные параметры:
         * - $post['department_title'] - (string) название нового подразделения - обязательный параметр
         * - $post['company_id'] - (int) идентификатор предприятия - обязательный параметр
         * - $post['type_department'] - (int) идентификатор типа подразделения - обязательный параметр
         * - $post['work_mode'] – (int) идентификатор режима работы (план смен)    !!!!!! этот параметр больше не нужен
         * Выходные параметры:
         * стандартный набор данных
     *      id - company_department_id - ключевое поле связки департамента и компании
    */
    //http://127.0.0.1/read-manager-amicum?controller=handbooks\Department&method&method=AddDepartmentDB&subscribe=login&data={%22department_title%22:%22%D0%A3%D0%BF%D1%80%D0%B0%D0%B2%D0%BB%D0%B5%D0%BD%D0%B8%D0%B5%22,%22company_id%22:%224029254%22,%22type_department%22:%225%22}

    public static function AddDepartmentDB($data_post)
    {
        $result = array();                                                                                                // промежуточный результирующий массив
        $errors = array();                                                                                              // массив ошибок
        $warnings = array();                                                                                              // массив предупреждений
        $status = 1;                                                                                                      //  состояния выполнения скрипта
        $company_department_id = 0;
        if (!is_null($data_post) and $data_post != "") {
            $warnings[] = "AddDepartmentDB. данные успешно переданы";
            $warnings[] = "AddDepartmentDB. Входной массив данных" . $data_post;
            try {
                $department = json_decode($data_post);                                                                      //декодируем входной массив данных
                $warnings[] = "AddDepartmentDB. декодировал входные параметры";
                if (property_exists($department, 'department_title') and
                    property_exists($department, 'company_id') and
                    property_exists($department, 'type_department')
                )                                                                                                       // и проверяем наличие в нем нужных нам полей
                {
                    $warnings[] = "AddDepartmentDB. Проверил входные данные";
                    //запись нового департамента в БД
                    if ($status != 0) {
                        $department_value = Department::find()->where(['title' => $department->department_title])->one();                                     //найти модель с таким названием
                        if (!$department_value) {                                                                                               //если модели нет
                            $department_value = new Department();                                                                              //создается новая модель
                            $department_value->title = $department->department_title;                                                                         //сохраняется название подразделения
                            if (!$department_value->save()) {
                                $status = 0;
                                $errors[] = "AddDepartmentDB. Ошибка сохранения модели Department";
                                $errors[] = $department_value->errors;
                            } else {
                                $department_value->refresh();
                                $warnings[] = "AddDepartmentDB. Новое подразделение сохранено";
                            }
                        } else {
                            $warnings[] = "AddDepartmentDB. Подразделение с таким названием уже существовало, изменений в БД не было сделано";
                        }
                        $department_value_id = $department_value->id;

                        $warnings[] = "AddDepartmentDB. Начал сохранять привязку департамента и компании";
                        $company_department = CompanyDepartment::find()->where([
                            'company_id' => $department->company_id,
                            'department_id' => $department_value_id,
                            'department_type_id' => $department->type_department
                        ])->one();
                        if (!$company_department) {
                            $company_department = new CompanyDepartment();
                            $company_department->company_id = $department->company_id;                                                 //сохранить id предприятия
                            $company_department->department_id = $department_value_id;                                                    //сохранить id подразделения
                            $company_department->department_type_id = $department->type_department;                                            //сохранить тип подразделения

                            if ($company_department->save())   // и проверяем наличие в нем нужных нам полей
                            {
                                $status *= 1;
                                $company_department_id = $company_department->id;
                                $warnings[] = "AddDepartmentDB. Значение успешно сохранено. Новый id значение: " . $company_department_id;
                            } else {
                                $errors[] = "AddDepartmentDB. Ошибка сохранения модели CompanyDepartment";
                                $errors[] = $company_department->errors;
                                $status = 0;
                            }
                        } else {
                            $status *= 1;
                            $company_department_id = $company_department->id;
                            $warnings[] = "AddDepartmentDB. Такая связки компании и департамента уже существовало. Сохранение не осуществлялось: " . $company_department_id;
                        }
                    }
                } else {
                    $errors[] = "AddDepartmentDB. Ошибка в наименование параметра во входных данных";
                    $status = 0;
                }
                HandbookCachedController::clearDepartmentCache();
            } catch (Exception $e) {
                $status = 0;
                $errors[] = $e->getMessage();
            }
        } else {
            $errors[] = "AddDepartmentDB. Входной массив обязательных данных пуст. Имя пользователя не передано.";
            $status = 0;
        }
        $result_main = array('Items' => $result, 'id' => $company_department_id, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    // метод сохранения компании в базу данных
    // внедрена проверка на наличие вышестоящей компании, если айди вышестоящей компании передан, но его не в базе, то
    // будет прекращено сохранение новой компании в базу данных
    // входные параметры:
    //      company_title - название компании
    //      company_upper - айди вышестоящей компании
    // выходные параметры:
    //      стандартный набор
    // id - ключ вновь созданной компании
    //127.0.0.1/read-manager-amicum?controller=handbooks\Department&method=AddCompanyDB&subscribe=&data={%22company_title%22:%22%D1%82%D0%B5%D1%81%D1%82%22,%22company_upper%22:%2260002517%22}
    public static function AddCompanyDB($data_post)
    {
        $result = array();                                                                                                // промежуточный результирующий массив
        $errors = array();                                                                                              // массив ошибок
        $warnings = array();                                                                                              // массив предупреждений
        $status = 1;                                                                                                      //  состояния выполнения скрипта
        $company_id = 0;                                                                                                  //ключ возвращаемого значения
        if (!is_null($data_post) and $data_post != "") {
            $warnings[] = "AddCompany. данные успешно переданы";
            $warnings[] = "AddCompany. Входной массив данных" . $data_post;
            try {
                $company = json_decode($data_post);                                                                      //декодируем входной массив данных
                $warnings[] = "AddCompany. декодировал входные параметры";
                if (property_exists($company, 'company_title') and
                    property_exists($company, 'company_upper')
                )                                                                                                       // и проверяем наличие в нем нужных нам полей
                {
                    $warnings[] = "AddCompany. Проверил входные данные";
                    //проверка наличия вышестоящей компании в базе данных
                    if ($company->company_upper and !is_null($company->company_upper)) {

                        $company_upper_id = Company::findOne([
                            'id' => $company->company_upper
                        ]);

                        if (!$company_upper_id) {
                            $errors[] = "AddCompany. уникальный ключ вышестоящей компании не существует в основной таблице. Сохранение прервано";
                            $status = 0;
                        }
                    }

                    //запись новой компании в БД
                    if ($status != 0) {
                        $company_value = new Company();                                                      //создаем  модель сводного параметра для записи
                        $company_value->title = $company->company_title;
                        $company_value->upper_company_id = $company->company_upper;

                        $warnings[] = "AddCompany. Начал сохранять метод";
                        if ($company_value->save())   // и проверяем наличие в нем нужных нам полей
                        {
                            $status *= 1;
                            $company_value->refresh();
                            $company_id = $company_value->id;
                            $warnings[] = "AddCompany. Значение успешно сохранено. Новый id значение: " . $company_id;
                        } else {
                            $errors[] = "AddCompany. Ошибка сохранения модели DepartmentParameterSummary";
                            $errors[] = $company_value->errors;
                            $status = 0;
                        }
                    }
                } else {
                    $errors[] = "AddCompany. Ошибка в наименование параметра во входных данных company_title или company_upper";
                    $status = 0;
                }
            } catch (Exception $e) {
                $status = 0;
                $errors[] = "AddCompany. Исключение";
                $errors[] = $e->getMessage();
            }
        } else {
            $errors[] = "AddCompany. Входной массив обязательных данных пуст: data_post is null";
            $status = 0;
        }
        return array('Items' => $result, 'id' => $company_id, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    /* UpdateStatusDepParamSummaryWorkerSettingDB - обновить статус отображения/скрытия уведомлений на главном окне для конкретного пользователя
     * входные параметры:
     *      $department_parameter_id - конкретный параметр департамента
     *      $employee_id - ключ сотрудника, для которого делается проверка сохранение настройки отображения уведомления
     *      $status_id - ключ статуса настройки отображать 16 /неотображать 15
     * выходные параметры:
     *      стандартный набор
     */
    private function UpdateStatusDepParamSummaryWorkerSettingDB($department_parameter_id, $employee_id, $status_id = null)
    {
        $status = 1;                                                                                                      //флаг успешного выполнения метода
        $warnings = array();                                                                                              // массив предупреждений
        $errors = array();                                                                                                // массив ошибок
        $result = array();                                                                                                // промежуточный результирующий массив
        $warnings[] = "UpdateStatusDepParamSummaryWorkerSettingDB. Начал выполнять метод";
        if ($status_id == 15 or $status_id == 16) {
            $dep_param_sum_wor_set_value = DepartmentParameterSummaryWorkerSettings::findOne([
                'department_parameter_id' => $department_parameter_id,
                'employee_id' => $employee_id
            ]);

            if (!$dep_param_sum_wor_set_value) {
                $dep_param_sum_wor_set_value = new DepartmentParameterSummaryWorkerSettings();
                $warnings[] = "UpdateStatusDepParamSummaryWorkerSettingDB. настройка уведомления создана с 0 в бд";
            }
            $dep_param_sum_wor_set_value->department_parameter_id = $department_parameter_id;
            $dep_param_sum_wor_set_value->status_id = $status_id;
            $dep_param_sum_wor_set_value->employee_id = $employee_id;

            $warnings[] = "UpdateStatusDepParamSummaryWorkerSettingDB. Начал сохранять метод";
            if ($dep_param_sum_wor_set_value->save())   // и проверяем наличие в нем нужных нам полей
            {
                $status *= 1;
                $warnings[] = "UpdateStatusDepParamSummaryWorkerSettingDB. Значение успешно обновлено. status_id значения: " . $status_id;
            } else {
                $errors[] = "UpdateStatusDepParamSummaryWorkerSettingDB. Ошибка сохранения модели DepartmentParameterSummaryWorkerSettings";
                $errors[] = $dep_param_sum_wor_set_value->errors;
                $status = 0;
            }
        } else {
            $errors[] = "UpdateStatusDepParamSummaryWorkerSettingDB. Не верно передан статус отображения уведомления, должен быть 15/16: $status_id";
            $status = 0;
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /* publicUpdateStatusDepParamSummaryWorkerSettingDB - обновить статус отображения/скрытия уведомлений на главном окне для конкретного пользователя
     * входные параметры:
     *      $department_parameter_id - конкретный параметр департамента
     *      $employee_id - ключ сотрудника, для которого делается проверка сохранение настройки отображения уведомления
     *      $status_id - ключ статуса настройки отображать 16 /неотображать 15
     * выходные параметры:
     *      стандартный набор
     */
    public static function publicUpdateStatusDepParamSummaryWorkerSettingDB($department_parameter_id, $employee_id, $status_id = null)
    {
        $status = 1;                                                                                                      //флаг успешного выполнения метода
        $warnings = array();                                                                                              // массив предупреждений
        $errors = array();                                                                                                // массив ошибок
        $result = array();                                                                                                // промежуточный результирующий массив
        $warnings[] = "publicUpdateStatusDepParamSummaryWorkerSettingDB. Начал выполнять метод";
        if ($status_id == 15 or $status_id == 16) {
            $dep_param_sum_wor_set_value = DepartmentParameterSummaryWorkerSettings::findOne([
                'department_parameter_id' => $department_parameter_id,
                'employee_id' => $employee_id
            ]);

            if (!$dep_param_sum_wor_set_value) {
                $dep_param_sum_wor_set_value = new DepartmentParameterSummaryWorkerSettings();
                $warnings[] = "publicUpdateStatusDepParamSummaryWorkerSettingDB. настройка уведомления создана с 0 в бд";
            }
            $dep_param_sum_wor_set_value->department_parameter_id = $department_parameter_id;
            $dep_param_sum_wor_set_value->status_id = $status_id;
            $dep_param_sum_wor_set_value->employee_id = $employee_id;

            $warnings[] = "publicUpdateStatusDepParamSummaryWorkerSettingDB. Начал сохранять метод";
            if ($dep_param_sum_wor_set_value->save())   // и проверяем наличие в нем нужных нам полей
            {
                $status *= 1;
                $warnings[] = "publicUpdateStatusDepParamSummaryWorkerSettingDB. Значение успешно обновлено. status_id значения: " . $status_id;
            } else {
                $errors[] = "publicUpdateStatusDepParamSummaryWorkerSettingDB. Ошибка сохранения модели DepartmentParameterSummaryWorkerSettings";
                $errors[] = $dep_param_sum_wor_set_value->errors;
                $status = 0;
            }
        } else {
            $errors[] = "publicUpdateStatusDepParamSummaryWorkerSettingDB. Не верно передан статус отображения уведомления, должен быть 15/16: $status_id";
            $status = 0;
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /* UpdateReadMessageDepParamSummaryWorkerSettingDB - обновить состояние последнего просмотра уведомлений, а так же
    *   установить отметку просмотрены уведомления или нет
     * логика такая - когда пользователь открыл главную страницу и нажимает на уведомления прочтены, то у него менятеся статус на прочтены уведомления
     * и в последующем у вас новые уведомления оно не появляется. в случае появления новых уведомлений, мы сравниваем с датой последнего
     * открытия/прочтения формы и по разному отправляем данные на фронт. если пользователь хочет, что бы было отображение, что есть новые уведомления,
     * то он ставит $read_message_status равным 28, что означет, что не прочтено и так до следующего открытия у авс есть новые уведомления.
     * входные параметры:
     *      $department_parameter_id - конкретный параметр департамента
     *      $employee_id - ключ сотрудника, для которого делается проверка сохранение настройки отображения уведомления
     *      $date_time - дата и время последнего прочтения уведомлений
     *      $read_message_status - статус прочтения сообщений 30 - прочтено/ 28 доставлено
     * выходные параметры:
     *      стандартный набор
     */
    private function UpdateReadMessageDepParamSummaryWorkerSettingDB($department_parameter_id, $employee_id, $date_time = null, $read_message_status = 30)
    {
        $status = 1;                                                                                                      //флаг успешного выполнения метода
        $warnings = array();                                                                                              // массив предупреждений
        $errors = array();                                                                                                // массив ошибок
        $result = array();                                                                                                // промежуточный результирующий массив
        $warnings[] = "UpdateReadMessageDepParamSummaryWorkerSettingDB. Начал выполнять метод";
        if ($read_message_status == 30 or $read_message_status == 28) {
            $dep_param_sum_wor_set_value = DepartmentParameterSummaryWorkerSettings::findOne([
                'department_parameter_id' => $department_parameter_id,
                'employee_id' => $employee_id
            ]);

            if (!$dep_param_sum_wor_set_value) {
                $dep_param_sum_wor_set_value = new DepartmentParameterSummaryWorkerSettings();
                $warnings[] = "UpdateReadMessageDepParamSummaryWorkerSettingDB. настройка уведомления создана с 0 в бд";
            }
            $dep_param_sum_wor_set_value->department_parameter_id = $department_parameter_id;
            $dep_param_sum_wor_set_value->read_message_status = $read_message_status;
            if (is_null($date_time))
                $dep_param_sum_wor_set_value->date_time = date('Y-m-d H:i:s.U');
            else
                $dep_param_sum_wor_set_value->date_time = date('Y-m-d H:i:s.U', $date_time);
            $dep_param_sum_wor_set_value->employee_id = $employee_id;

            $warnings[] = "UpdateReadMessageDepParamSummaryWorkerSettingDB. Начал сохранять метод";
            if ($dep_param_sum_wor_set_value->save())   // и проверяем наличие в нем нужных нам полей
            {
                $status *= 1;
                $warnings[] = "UpdateReadMessageDepParamSummaryWorkerSettingDB. Значение успешно обновлено. read_message_status значения: " . $read_message_status;
            } else {
                $errors[] = "UpdateReadMessageDepParamSummaryWorkerSettingDB. Ошибка сохранения модели DepartmentParameterSummaryWorkerSettings";
                $errors[] = $dep_param_sum_wor_set_value->errors;
                $status = 0;
            }
        } else {
            $errors[] = "UpdateReadMessageDepParamSummaryWorkerSettingDB. Не верно передан статус отображения уведомления, должен быть 30/28: $read_message_status";
            $status = 0;
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /* UpdateUnReadMessageDepParamSummaryWorkerSettingDB - установить статус сообщение не прочтено
     * логика такая - когда пользователь открыл главную страницу и нажимает на уведомления прочтены, то у него менятеся статус на прочтены уведомления
     * и в последующем у вас новые уведомления оно не появляется. в случае появления новых уведомлений, мы сравниваем с датой последнего
     * открытия/прочтения формы и по разному отправляем данные на фронт. если пользователь хочет, что бы было отображение, что есть новые уведомления,
     * то он ставит $read_message_status равным 28, что означет, что не прочтено и так до следующего открытия у Вас есть новые уведомления.
     * входные параметры
     *      $department_parameter_id - конкретный параметр департамента
     *      $employee_id - ключ сотрудника, для которого делается проверка сохранение настройки отображения уведомления
     *      $date_time - дата и время последнего прочтения уведомлений
     *      $read_message_status - статус прочтения сообщений 30 - прочтено/ 28 доставлено
     * выходные параметры:
     *      стандартный набор
     */
    private function UpdateUnReadMessageDepParamSummaryWorkerSettingDB($department_parameter_id, $employee_id, $read_message_status = 28)
    {
        $status = 1;                                                                                                      //флаг успешного выполнения метода
        $warnings = array();                                                                                              // массив предупреждений
        $errors = array();                                                                                                // массив ошибок
        $result = array();                                                                                                // промежуточный результирующий массив
        $warnings[] = "UpdateUnReadMessageDepParamSummaryWorkerSettingDB. Начал выполнять метод";
        if ($read_message_status == 30 or $read_message_status == 28) {
            $dep_param_sum_wor_set_value = DepartmentParameterSummaryWorkerSettings::findOne([
                'department_parameter_id' => $department_parameter_id,
                'employee_id' => $employee_id
            ]);

            if (!$dep_param_sum_wor_set_value) {
                $dep_param_sum_wor_set_value = new DepartmentParameterSummaryWorkerSettings();
                $warnings[] = "UpdateUnReadMessageDepParamSummaryWorkerSettingDB. настройка уведомления создана с 0 в бд";
            }
            $dep_param_sum_wor_set_value->department_parameter_id = $department_parameter_id;
            $dep_param_sum_wor_set_value->read_message_status = $read_message_status;
            $dep_param_sum_wor_set_value->employee_id = $employee_id;

            $warnings[] = "UpdateUnReadMessageDepParamSummaryWorkerSettingDB. Начал сохранять метод";
            if ($dep_param_sum_wor_set_value->save())   // и проверяем наличие в нем нужных нам полей
            {
                $status *= 1;
                $warnings[] = "UpdateUnReadMessageDepParamSummaryWorkerSettingDB. Значение успешно обновлено. read_message_status значения: " . $read_message_status;
            } else {
                $errors[] = "UpdateUnReadMessageDepParamSummaryWorkerSettingDB. Ошибка сохранения модели DepartmentParameterSummaryWorkerSettings";
                $errors[] = $dep_param_sum_wor_set_value->errors;
                $status = 0;
            }
        } else {
            $errors[] = "UpdateUnReadMessageDepParamSummaryWorkerSettingDB. Не верно передан статус отображения уведомления, должен быть 30/28: $read_message_status";
            $status = 0;
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /* ReadDepParamSummaryDB - Чтение из БД всех или по конкретному company_department_id параметров департаментов и их значений
     * входные парамеры:
     *      $company_deprtment_id - ключ конкретного департамента
     * выходные параметры:
     *      стандартный набор
     *      массив параметров департамента и их значений.
     *
     * @exapmple http://amicum/read-manager-amicum?controller=handbooks\Department&method=ReadDepParamSummaryDB&subscribe=&data={%22company_department_id%22:4029825}
     */
    private static function ReadDepParamSummaryDB($company_department_id = "")
    {
        $status = 1;                                                                                                      //флаг успешного выполнения метода
        $warnings = array();                                                                                              // массив предупреждений
        $errors = array();                                                                                                // массив ошибок
        $result = array();                                                                                                // промежуточный результирующий массив
        $warnings[] = "ReadDepParamSummaryDB. Начал выполнять метод";

        $company_department_summary = DepartmentParameterSummary::find()
            ->where(['department_parameter_summary.company_department_id' => $company_department_id])
            ->andWhere(['!=', 'value', 0])
            ->asArray()
            ->indexBy('id')
            ->limit(50000)
            ->all();

        if (!$company_department_summary) {
            $warnings[] = "ReadDepParamSummaryDB. Данные в БД по запрашиваемому департаменту/всем департаментам не найдены";
        } else {
            foreach ($company_department_summary as $company_department) {
                if (!isset($result[$company_department['parameter_id'] . " " . $company_department['parameter_type_id']])) {
                    $result[$company_department['parameter_id'] . " " . $company_department['parameter_type_id']] = array(
                        'id' => $company_department['id'],
                        'parameter_id' => $company_department['parameter_id'],
                        'parameter_type_id' => $company_department['parameter_type_id'],
                        'date_time' => $company_department['date_time'],
                        'value' => $company_department['value'],
                    );
                } else {
                    $result[$company_department['parameter_id'] . " " . $company_department['parameter_type_id']]['value'] += $company_department['value'];
                }
            }
            $warnings[] = "ReadDepParamSummaryDB. Полученный набор данных";
        }


        $warnings[] = "ReadDepParamSummaryDB. Окончил выполнять метод";

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    /* ReadDepParamSummarySettingsDB - Чтение из БД всех или по конкретному employee_id его настроек
         * входные парамеры:
         *      employee_id - ключ конкретного человека
         * выходные параметры:
         *      стандартный набор
         *      массив настроек параметров департамента.
         */
    private static function ReadDepParamSummarySettingsDB($employee_id = "")
    {
        $status = 1;                                                                                                      //флаг успешного выполнения метода
        $warnings = array();                                                                                              // массив предупреждений
        $errors = array();                                                                                                // массив ошибок
        $result = array();                                                                                                // промежуточный результирующий массив
        $warnings[] = "ReadDepParamSummarySettingsDB. Начал выполнять метод";

        $company_department_summary_settings = DepartmentParameterSummaryWorkerSettings::find()
            ->where(['department_parameter_summary_worker_settings.employee_id' => $employee_id])
            ->asArray()
            ->indexBy('department_parameter_id')
            ->limit(50000)
            ->all();

        if (!$company_department_summary_settings) {
            $warnings[] = "ReadDepParamSummarySettingsDB. Данные в БД по запрашиваемому департаменту/всем департаментам не найдены";
        } else {
            $result = $company_department_summary_settings;
            $warnings[] = "ReadDepParamSummarySettingsDB. Полученный набор данных";
            //$warnings[] = $result;
        }
        $warnings[] = "ReadDepParamSummarySettingsDB. Окончил выполнять метод";

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /* ReadDepParamSummarySettingsDBWithParam - Чтение из БД всех или по конкретному employee_id его настроек с его параметрами
         * входные парамеры:
         *      employee_id - ключ конкретного человека
         * выходные параметры:
         *      стандартный набор
         *      массив настроек параметров департамента.
         */
    private static function ReadDepParamSummarySettingsDBWithParam($employee_id = "")
    {
        $status = 1;                                                                                                      //флаг успешного выполнения метода
        $warnings = array();                                                                                              // массив предупреждений
        $errors = array();                                                                                                // массив ошибок
        $result = array();                                                                                                // промежуточный результирующий массив
        $warnings[] = "ReadDepParamSummarySettingsDBWithParam. Начал выполнять метод";

        $company_department_summary_settings = (new Query())
            ->select('
                department_parameter_summary_worker_settings.department_parameter_id as department_parameter_id,
                department_parameter_summary_worker_settings.employee_id as employee_id,
                department_parameter_summary_worker_settings.status_id as status_id,
                department_parameter_summary_worker_settings.date_time as date_time,
                department_parameter_summary_worker_settings.read_message_status as read_message_status,
                department_parameter_summary_worker_settings.id as id,
                parameter.id as parameter_id,
                parameter.title as parameter_title
            ')
            ->from("department_parameter_summary_worker_settings")
            ->innerJoin('department_parameter', 'department_parameter_summary_worker_settings.department_parameter_id=department_parameter.id')
            ->innerJoin('parameter', 'parameter.id=department_parameter.parameter_id')
            ->where(['department_parameter_summary_worker_settings.employee_id' => $employee_id])
            ->indexBy('department_parameter_id')
            ->limit(50000)
            ->all();

        if (!$company_department_summary_settings) {
            $warnings[] = "ReadDepParamSummarySettingsDBWithParam. Данные в БД по запрашиваемому департаменту/всем департаментам не найдены";
        } else {
            $result = $company_department_summary_settings;
            $warnings[] = "ReadDepParamSummarySettingsDBWithParam. Полученный набор данных";
            //$warnings[] = $result;
        }
        $warnings[] = "ReadDepParamSummarySettingsDBWithParam. Окончил выполнять метод";

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    // actionGetListWorkers - получить список работников по конкретному департаменту
    //127.0.0.1/handbooks/department/get-list-workers?company_department_id=802
    // метод не СДЕЛАН!!!!!!
    //TODO разработать метод до конца
    public function actionGetListWorkers()
    {
        $post = Assistant::GetServerMethod();
        $status = 1;                                                                                                      //флаг успешного выполнения метода
        $filter = array();                                                                                                //массив фильтр
        $warnings = array();                                                                                              // массив предупреждений
        $errors = array();                                                                                                // массив ошибок
        $result = array();
        if (isset($post['company_department_id']) and $post['company_department_id'] != "") {
            $company_department_id = $post['company_department_id'];
            $filter = array('company_department_id' => $company_department_id);
        }
        $worker_list = Worker::find()
            ->joinWith('employee')
            ->joinWith('companyDepartment')
            ->where($filter)
            ->limit(50000)
            ->all();

        foreach ($worker_list as $worker) {
            $warnings[$worker->company_department_id][] = $worker->employee->last_name;
        }

        $result = $worker_list;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // GetDepartmentList - метод получения списка департаментов
    // http://127.0.0.1/read-manager-amicum?controller=handbooks\Department&method=GetDepartmentList&subscribe=login&data={"type_func":"1","search_query":""}
    public static function GetDepartmentList($data_post)
    {
        $log = new LogAmicumFront("GetDepartmentList");
        $result = [];
        try {
            if (is_null($data_post) and $data_post === "") {
                throw new Exception("Входной массив обязательных данных пуст. Имя пользователя не передано.");
            }

            $log->addLog("Данные успешно переданы");
            $log->addLog("Входной массив данных" . $data_post);

            $department = json_decode($data_post);
            $log->addLog("Декодировал входные параметры");

            if (
                !property_exists($department, 'type_func') or
                !property_exists($department, 'search_query')
            ) {
                throw new Exception("Ошибка в наименование параметра во входных данных");
            }
            $department_type_func = $department->type_func;
            $log->addLog("Проверил входные данные");
            $cache = Yii::$app->cache;
            $key = "GetDepartmentList";
            $keyHash = "GetDepartmentListHash";
            $department_List_data = $cache->get($key);
            if (!$department_List_data) {
                $response = HandbookEmployeeController::GetCompanyDepartment($department_type_func);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception("Ошибка получения справочника подразделений");
                }
                $department_List_data = $response['model'];
                $hash = md5(json_encode($department_List_data));
                $cache->set($keyHash, $hash, 60 * 60 * 24);
                $cache->set($key, $response['model'], 60 * 60 * 24);   // 60 * 60 * 24 = сутки
            } else {
                $log->addLog("Кеш был");
                $hash = $cache->get($keyHash);
            }

            if (empty($department_List_data)) {
                $result = array();
            } else {
                $result['handbook'] = $department_List_data;
                $result['hash'] = $hash;
            }

        } catch (Exception $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Вышел с метода");
        return array_merge(['Items' => $result], $log->getLogAll());
    }

    // GetDepartmentListWithWorkers - метод получения списка департаментов и работников в них
    // http://127.0.0.1/read-manager-amicum?controller=handbooks\Department&method=GetDepartmentListWithWorkers&subscribe=login&data={"search_query":""}
    public static function GetDepartmentListWithWorkers()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("GetDepartmentListWithWorkers");

        try {
            $log->addLog("Начало выполнения метода");

            $cache = Yii::$app->cache;
            $key = "GetDepartmentListWithWorkers";
            $keyHash = "GetDepartmentListWithWorkersHash";
            $list_companys = $cache->get($key);
            if (!$list_companys) {
                $log->addLog("Кеша не было, получаю данные из БД");

                // получаем список всех 0 департаментов/компаний
                $companies = Company::find()
                    ->where('upper_company_id is null')
                    ->asArray()
                    ->all();
                if ($companies === false) {
                    throw new Exception("GetDepartmentListWithWorkers. Список компаний пуст");
                }

                // получаем список людей и их депратаментов со всей нужной служебной информации
                $all_list_departments_with_workers = (new Query())
                    ->select('*')
                    ->from('view_getworkerswithdepartments')
                    ->all();

                if ($all_list_departments_with_workers === false) {
                    throw new Exception("GetDepartmentListWithWorkers. Список работников пуст");
                }
                // группируем работников в департаменты
                $worker_group_by_company_id = [];
                foreach ($all_list_departments_with_workers as $worker) {
                    $worker_group_by_company_id[$worker['company_department_id']][] = $worker;
                }
                unset($worker);
                unset($all_list_departments_with_workers);

                // получаем список вложенных компаний
                $attachment_companies = Company::find()
                    ->select(
                        'company.id as id,
                    company.title as title,
                    upper_company_id'
                    )
                    ->where('upper_company_id is not null')
                    ->asArray()
                    ->all();

                // группируем работников в департаменты
                $company_by_upper_company_id = [];
                foreach ($attachment_companies as $attachment_company) {
                    $company_by_upper_company_id[$attachment_company['upper_company_id']][] = $attachment_company;
                }
                unset($attachment_company);
                unset($attachment_companies);
                $list_companys = [];
                foreach ($companies as $company) {
                    $list_companys[] = self::getCompanyAttachment($company, $worker_group_by_company_id, $company_by_upper_company_id);
                }
                $hash = md5(json_encode($list_companys));
                $cache->set($keyHash, $hash, 60 * 60 * 24);
                $cache->set($key, $list_companys, 60 * 60 * 24);   // 60 * 60 * 24 = сутки
            } else {
                $log->addLog("Кеш был");
                $hash = $cache->get($keyHash);
            }

            $result['handbook']['id'] = 1;
            $result['handbook']['title'] = "Список работников";
            $result['handbook']['state'] = array('expanded' => true);
            $result['handbook']['children'] = $list_companys;
            $result['handbook']['is_chosen'] = 0;
            $result['hash'] = $hash;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    public static function getCompanyAttachment($company, $worker_group_by_company_id, $company_by_upper_company_id)
    {
        $list_company['id'] = $company['id'];
        $list_company['title'] = str_replace("  ", " ", $company['title']); // эт
        $list_company['parent'] = $company['upper_company_id'];
        $list_company['is_chosen'] = 2;
        /**
         * блок проверки работников внутри подразделения
         */
        $count_worker_in_dep = 0;
        $date_time_now = strtotime(Assistant::GetDateTimeNow());
        if (isset($worker_group_by_company_id[$company['id']])) {
            foreach ($worker_group_by_company_id[$company['id']] as $worker) {
                $worker_groups_temp['id'] = $worker['worker_id'];
                $worker_groups_temp['worker_id'] = $worker['worker_id'];
                $worker_groups_temp['title'] = $worker['last_name'] . ' ' . $worker['first_name'] . ' ' . $worker['patronymic'];
                $worker_groups_temp['worker_full_name'] = $worker['last_name'] . ' ' . $worker['first_name'] . ' ' . $worker['patronymic'];
                $worker_groups_temp['worker_position_id'] = $worker['position_id'];
                $worker_groups_temp['last_name'] = $worker['last_name'];
                $worker_groups_temp['first_name'] = $worker['first_name'];
                $worker_groups_temp['patronymic'] = $worker['patronymic'];
                $worker_groups_temp['position_title'] = $worker['position_title'];
                $worker_groups_temp['qualification'] = $worker['qualification'];
                $worker_groups_temp['stuff_number'] = $worker['stuff_number'];
                $worker_groups_temp['worker_tabel_number'] = $worker['stuff_number'];
                $worker_groups_temp['worker_role_id'] = $worker['role_id'];
                $worker_groups_temp['input'] = array('type' => 'radio', 'name' => 'rbGroup1', 'value' => 1);
                $worker_groups_temp['is_chosen'] = 1;
                if (!$worker['worker_date_end'] or strtotime($worker['worker_date_end']) > $date_time_now) {
                    $worker_groups_temp['worker_worked'] = 1;    // работает
                } else {
                    $worker_groups_temp['worker_worked'] = 0;    // или не работает сейчас сотрудник
                }
                $list_company['children'][] = $worker_groups_temp;
                unset($worker_groups_temp);
                $count_worker_in_dep++;
            }
        }
        $list_company['count_worker'] = $count_worker_in_dep;
        /**
         * блок проверки подразделений внутри подразделения
         */
        if (isset($company_by_upper_company_id[$company['id']])) {
            foreach ($company_by_upper_company_id[$company['id']] as $child_company) {
                $response = self::getCompanyAttachment($child_company, $worker_group_by_company_id, $company_by_upper_company_id);
                $list_company['children'][] = $response;
                $list_company['count_worker'] += $response['count_worker'];
            }
        }

        return $list_company;
    }

    // GetDepartmentListRecursiv - метод получения вложенного списка департаментов без работников
    // http://127.0.0.1/read-manager-amicum?controller=handbooks\Department&method=GetDepartmentListRecursiv&subscribe=login&data={"search_query":""}
    public static function GetDepartmentListRecursiv()
    {
        $result = array();                                                                                                // промежуточный результирующий массив
        $errors = array();                                                                                              // массив ошибок
        $warnings = array();                                                                                              // массив предупреждений
        $status = 1;                                                                                                      //  состояния выполнения скрипта
        $company_department_id = 0;
        try {
            $warnings[] = "GetDepartmentListRecursiv. Проверил входные данные";

            // получаем список всех 0 департаментов/компаний
            $companies = Company::find()
                ->where('upper_company_id is null')
                ->asArray()
                ->all();
            if ($companies === false) {
                throw new Exception("GetDepartmentListRecursiv. Список компаний пуст");
            }


            // получаем список вложенных компаний
            $attachment_companies = Company::find()
                ->select(
                    'company.id as id,
                    company.title as title,
                    upper_company_id'
                )
                ->where('upper_company_id is not null')
                ->asArray()
                ->all();
            if ($attachment_companies === false) {
                $warnings[] = "GetDepartmentListRecursiv. Список вложенных компаний пуст";
            }
            $company_by_upper_company_id = [];
            // группируем работников в департаменты
            foreach ($attachment_companies as $attachment_company) {
                $company_by_upper_company_id[$attachment_company['upper_company_id']][] = $attachment_company;
            }
            unset($attachment_company);
            unset($attachment_companies);

            foreach ($companies as $company) {
                $list_companys[] = self::getCompanyAttachmentWithOutWorkers($company, $company_by_upper_company_id);
            }
            $result['id'] = 1;
            $result['title'] = "Список подразделений";
            $result['state'] = array('expanded' => true);
            $result['children'] = $list_companys;
            $result['is_chosen'] = 0;

        } catch (Throwable $exception) {
            $errors[] = "GetDepartmentListRecursiv. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = "GetDepartmentListRecursiv. Вышел с метода";
        $result_main = array(
            'Items' => $result,
            'status' => $status,
            'errors' => $errors,
            'warnings' => $warnings
        );
        return $result_main;
    }

    public static function getCompanyAttachmentWithOutWorkers($company, $company_by_upper_company_id)
    {
        $list_company['id'] = $company['id'];
        $list_company['title'] = str_replace("  ", " ", $company['title']);
        $list_company['is_chosen'] = 2;

        /**
         * блок проверки подразделений внутри подразделения
         */
        if (isset($company_by_upper_company_id[$company['id']])) {
            foreach ($company_by_upper_company_id[$company['id']] as $child_company) {
                $list_company['children'][] = self::getCompanyAttachmentWithOutWorkers($child_company, $company_by_upper_company_id);

            }
        }

        return $list_company;
    }

    public function GetServerMethod()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') return Yii::$app->request->post();
        else if ($_SERVER['REQUEST_METHOD'] == 'GET') return Yii::$app->request->get();
    }

    // actionGetDepartmentList - метод получения списка департаментов
    // http://127.0.0.1/read-manager-amicum?controller=handbooks\Department&method=GetDepartmentList&subscribe=login&data={"type_func":"1","search_query":""}
    public function actionGetDepartmentList()
    {


        $result = HandbookEmployeeController::GetCompanyDepartment(1);

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * Метод GetDepParameter() - вызов метода получения данных по конкретному департаменту
     * @param null $data_post - JSON c идентификатором участка
     * @return array - выходной массив: [parameters]
     *                                          [department_parameter_symmary_id]
     *                                                  id:
     *                                                  company_department_id:
     *                                                  parameter_id:
     *                                                  parameter_type_id:
     *                                                  date_time:
     *                                                  value:
     *                                  [settings]
     *                                      [department_parameter_summary_worker_settings_id]
     *                                                  department_parameter_id:
     *                                                  employee_id:
     *                                                  status_id:
     *                                                  date_time:
     *                                                  read_message_status:
     *                                                  id:
     *
     * @package frontend\controllers\handbooks
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\Department&method=GetDepParameter&subscribe=login&data={%22company_department_id%22:802}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 07.08.2019 15:16
     */
    public static function GetDepParameter($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $dep_param = array();                                                                                           // Промежуточный результирующий массив
        $session = Yii::$app->session;
        try {
            if ($data_post !== NULL && $data_post !== '') {
                $warnings[] = 'GetDepParameter. Данные успешно переданы';
                $warnings[] = 'GetDepParameter. Входной массив данных' . $data_post;
            } else {

                throw new Exception('GetDepParameter. Данные с фронта не получены');
            }

            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'GetDepParameter. Декодировал входные параметры';

            if (
                !property_exists($post_dec, 'company_department_id')
            ) {                                                                                                         // Проверяем наличие в нем нужных нам полей
                throw new Exception('GetDepParameter. Переданы некорректные входные параметры');
            }

            $warnings[] = 'GetDepParameter.Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;

            $response = DepartmentController::GetAttachDeprtmentByUpper($company_department_id);
            $warnings[] = $response['warnings'];
            $errors[] = $response['errors'];
            if ($response['status'] != 1) {
                throw new Exception('Ошибка получения вложенных департаментов');
            }
            $company_departments = $response['Items'];

            $result_param_dep = self::ReadDepParamSummaryDB($company_departments);                                    //вызываем метод получения параметров
            $dep_param['parameters'] = $result_param_dep['Items'];                                                      //укладываем результат в массив parameters
            $warnings[] = $result_param_dep['warnings'];
            $errors[] = $result_param_dep['errors'];

            $employee_id = $session['employee_id'];                                                                     //ищем employee_id работника из сессии по идентификатору работника
            if ($employee_id !== null)                                                                                  //если запись найдена
            {
                $result_symmary_settings = self::ReadDepParamSummarySettingsDBWithParam($employee_id);                  //находим все настройки для этого работника
                $dep_param['settings'] = $result_symmary_settings['Items'];                                             //укладываем результат в массив settings
            } else {
                throw new Exception('GetDepParameter. Отсутствует в сессии employee_id');
            }

            if (!isset($dep_param['parameters'])) {
                $dep_param['parameters'] = (object)array();
            }
            if (!isset($dep_param['settings'])) {
                $dep_param['settings'] = (object)array();
            }

        } catch (Throwable $exception) {
            $errors[] = 'GetDepParameter. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = "GetDepParameter. Окончил выполнять метод";
        $result = $dep_param;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Метод CountNewInjunction() - Метод смены сводного значения параметра по подраздалениям
     * @return array - стандартный массив данных
     *
     * @package frontend\controllers\handbooks
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\Department&method=CountNewInjunction&subscribe=login&data=
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 20.08.2019 9:43
     */
    public static function CountNewInjunction()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $on_add = array();
        $on_update = array();
        $warnings[] = 'CountNewInjunction. Начало метода';
        try {
            /**
             * блок получения предписаний по всем участкам
             */
            $injunctions = Injunction::find()
                ->select([
                    'injunction.company_department_id as comp_dep_id',
                    'count(injunction.id) as count_inj'])
                ->where(['status_id' => InjunctionController::STATUS_NEW,
                    'kind_document_id' => CheckingController::KIND_INJUNCTION])
                ->groupBy(['comp_dep_id'])
                ->orderBy('comp_dep_id')
                ->indexBy('comp_dep_id')
                ->asArray()
                ->all();
//            Assistant::PrintR($injunctions);die;
            /**
             * блок получения списка сводных параметров конкретных департаментов
             */
            $update_department_symmary = DepartmentParameterSummary::find()
                ->where(['parameter_id' => self::PARAMETER_INJUNCTION])
                ->indexBy('company_department_id')
//                ->asArray()
                ->all();

            /**
             * Блок перебора сводных параметров с целью формирования массива на обновление записей в БД
             */
            foreach ($update_department_symmary as $department_summary) {
                if (isset($injunctions[$department_summary->company_department_id]['count_inj']) &&
                    $injunctions[$department_summary->company_department_id]['count_inj'] != $department_summary->value) {
                    $on_update[$department_summary->company_department_id] = $injunctions[$department_summary->company_department_id]['count_inj'];
                } elseif (!isset($injunctions[$department_summary->company_department_id]['count_inj'])) {
                    $on_update[$department_summary->company_department_id] = 0;
                }
            }
            /**
             * Если массив на обновление записей в БД не пусто тогда вызываем метод с флагом на обновление
             */
            if (!empty($on_update)) {
                $update_value = self::ParameterUpdate(self::PARAMETER_INJUNCTION, $on_update);
                if ($update_value['status'] == 1) {
                    $warnings[] = 'CountInjunction. Данные успешно обновлены';
                    $warnings[] = $update_value['warnings'];
                } else {
                    $warnings[] = 'CountInjunction. Ошибка при обновлении';
                    $errors[] = $update_value['errors'];
                    $warnings[] = $update_value['warnings'];
                }
            }
//            Assistant::PrintR($on_update);die;
            /**
             * Перебор предписаний если по предписанию нет сводного параметры добавляется в массив на добавление
             * в таблицы (department_parameter,department_parameter_value,department_parameter_symmary)
             */
            foreach ($injunctions as $injunction) {
                if (!isset($update_department_symmary[$injunction['comp_dep_id']])) {
                    $on_add[$injunction['comp_dep_id']] = $injunction['count_inj'];
                }
            }

            /**
             * Если массив на добалвение записей в БД не пусто тогда вызываем местод с флагом на добавление
             */
            if (!empty($on_add)) {
                $add_values_parameters = self::ParameterAdd(self::PARAMETER_INJUNCTION, $on_add);
                if ($add_values_parameters['status'] == 1) {
                    $warnings[] = 'CountInjunction. Данные успешно обновлены';
                    $warnings[] = $add_values_parameters['warnings'];
                } else {
                    $warnings[] = 'CountInjunction. Ошибка при обновлении';
                    $errors[] = $add_values_parameters['errors'];
                    $warnings[] = $add_values_parameters['warnings'];
                }
            }

        } catch (Throwable $exception) {
            $errors[] = 'CountNewInjunction. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'CountNewInjunction. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод CountPab() - Расчёт количества паб для показателей в блоке уведомлений
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\Department&method=CountPab&subscribe=login&data=
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 03.09.2019 10:29
     */
    public static function CountPab()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $on_update = array();
        $on_add = array();
        $warnings[] = 'CountPab. Начало метода';
        try {
            /**
             * блок получения ПАБов по всем участкам
             */
            $count_pabs = Injunction::find()
                ->select([
                    'injunction.company_department_id as comp_dep_id',
                    'count(injunction.id) as count_inj'
                ])
                ->where([
                    'kind_document_id' => CheckingController::KIND_PAB])
                ->andWhere(['injunction.status_id' => 57])
                ->groupBy(['comp_dep_id'])
                ->orderBy('comp_dep_id')
                ->indexBy('comp_dep_id')
                ->asArray()
                ->all();
            foreach ($count_pabs as $count_pab) {
                $counter_for_pabs[$count_pab['comp_dep_id']] = $count_pab['count_inj'];
            }
            /**
             * блок получения списка сводных параметров конкретных департаментов
             */
            $update_department_symmary = DepartmentParameterSummary::find()
                ->where(['parameter_id' => self::PARAMETER_PAB])
                ->indexBy('company_department_id')
                ->all();
            foreach ($update_department_symmary as $department_summary) {
                if (isset($counter_for_pabs[$department_summary->company_department_id]) &&
                    $counter_for_pabs[$department_summary->company_department_id] != $department_summary->value) {
                    $on_update[$department_summary->company_department_id] = $counter_for_pabs[$department_summary->company_department_id];
                } elseif (!isset($counter_for_pabs[$department_summary->company_department_id])) {
                    $on_update[$department_summary->company_department_id] = 0;
                }
            }

            /**
             * Если массив на обновление записей в БД не пусто тогда вызываем метод с флагом на обновление
             */
            if (!empty($on_update)) {
                $update_value = self::ParameterUpdate(self::PARAMETER_PAB, $on_update);
                if ($update_value['status'] == 1) {
                    $warnings[] = 'CountPab. Данные успешно обновлены';
                    $warnings[] = $update_value['warnings'];
                } else {
                    $warnings[] = 'CountPab. Ошибка при обновлении';
                    $errors[] = $update_value['errors'];
                    $warnings[] = $update_value['warnings'];
                }
            }

            foreach ($counter_for_pabs as $comp_dep_id => $pab) {
                if (!isset($update_department_symmary[$comp_dep_id])) {
                    $on_add[$comp_dep_id] = $pab;
                }
            }
            /**
             * Если массив на добалвение записей в БД не пусто тогда вызываем местод с флагом на добавление
             */
            if (!empty($on_add)) {
                $add_values_parameters = self::ParameterAdd(self::PARAMETER_PAB, $on_add);
                if ($add_values_parameters['status'] == 1) {
                    $warnings[] = 'CountPab. Данные успешно обновлены';
                    $warnings[] = $add_values_parameters['warnings'];
                } else {
                    $warnings[] = 'CountPab. Ошибка при обновлении';
                    $errors[] = $add_values_parameters['errors'];
                    $warnings[] = $add_values_parameters['warnings'];
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'CountPab. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'CountPab. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод CountCheckingPlan() - Расчёт количества запланированных аудитов для показателей в блоке уведомлений
     * @return array - стандартный массив данных
     *
     * @package frontend\controllers\handbooks
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\Department&method=CountCheckingPlan&subscribe=login&data=
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 13.09.2019 14:42
     */
    public static function CountCheckingPlan()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $checking_plan = array();                                                                                        // Промежуточный результирующий массив
        $audits = array();                                                                                    // Промежуточный результирующий массив
        $on_add = array();                                                                                    // Промежуточный результирующий массив
        $on_update = array();                                                                                    // Промежуточный результирующий массив
        $update_department_parameter_handbook_value = array();                                                                                    // Промежуточный результирующий массив
        $current_comp_dep = null;
        $warnings[] = 'CountCheckingPlan. Начало метода';
        try {
            $date = date('Y-m-d', strtotime(AssistantBackend::GetDateNow()));
            $first_audits = Audit::find()
                ->select('min(audit.date_time) as min_date,audit.company_department_id as audit_company_department_id')
                ->innerJoin('audit_place', 'audit_place.audit_id = audit.id')
                ->andWhere(['>=', 'date_time', $date])
                ->orderBy('min_date ')
                ->indexBy('audit_company_department_id')
                ->groupBy('audit_company_department_id')
                ->asArray()
                ->all();
            foreach ($first_audits as $first_audit) {
                $found_audits = Audit::find()
                    ->select([
                        'count(audit_place.id) as count_audit'
                    ])
                    ->innerJoin('audit_place', 'audit_place.audit_id = audit.id')
                    ->where(['audit.company_department_id' => $first_audit['audit_company_department_id']])
                    ->andWhere(['audit.date_time' => $first_audit['min_date']])
                    ->limit(1)
                    ->asArray()
                    ->scalar();
                $audits[$first_audit['audit_company_department_id']] = $found_audits;
            }
            $update_department_symmary = DepartmentParameterSummary::find()
                ->where(['parameter_id' => self::PARAMETER_CHECKING_PLAN])
                ->indexBy('company_department_id')
                ->all();
            $date_time = AssistantBackend::GetDateNow();
            /**
             * Блоку получение Аудитов по участку по дате первого аудита
             */
            foreach ($audits as $comp_dep_id => $first_autit_date) {
                $count_checking_plan_by_comp_dep[$comp_dep_id] = $audits[$comp_dep_id];
                if (!isset($update_department_symmary[$comp_dep_id]['id'])) {
                    $on_add[$comp_dep_id] = $count_checking_plan_by_comp_dep[$comp_dep_id];
                }
            }

            /**
             * Если массив на добалвение записей в БД не пусто тогда вызываем местод с флагом на добавление
             */
            if (!empty($on_add)) {
                $add_values_parameters = self::ParameterAdd(self::PARAMETER_CHECKING_PLAN, $on_add);
                if ($add_values_parameters['status'] == 1) {
                    $warnings[] = 'CountCheckingPlan. Данные успешно обновлены';
                    $warnings[] = $add_values_parameters['warnings'];
                } else {
                    $warnings[] = 'CountCheckingPlan. Ошибка при обновлении';
                    $errors[] = $add_values_parameters['errors'];
                    $warnings[] = $add_values_parameters['warnings'];
                }
            }

            foreach ($update_department_symmary as $department_summary) {
                if (isset($audits[$department_summary->company_department_id]) &&
                    $audits[$department_summary->company_department_id] != $department_summary->value) {
                    $on_update[$department_summary->company_department_id] = $audits[$department_summary->company_department_id];
                } elseif (!isset($audits[$department_summary->company_department_id])) {
                    $on_update[$department_summary->company_department_id] = 0;
                }
            }
            /**
             * Если массив на обновление записей в БД не пусто тогда вызываем метод с флагом на обновление
             */
            if (!empty($on_update)) {
                $update_value = self::ParameterUpdate(self::PARAMETER_CHECKING_PLAN, $on_update);
                if ($update_value['status'] == 1) {
                    $warnings[] = 'CountCheckingPlan. Данные успешно обновлены';
                    $warnings[] = $update_value['warnings'];
                } else {
                    $warnings[] = 'CountCheckingPlan. Ошибка при обновлении';
                    $errors[] = $update_value['errors'];
                    $warnings[] = $update_value['warnings'];
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'CountCheckingPlan. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'CountCheckingPlan. Конец метода';
        $result = $checking_plan;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод CountExpertise() - Расчёт количества экспертиз промшленной безопасности для показателей в блоке уведомлений
     * @return array - стандартный массив выходных данных
     *
     * @package frontend\controllers\handbooks
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\Department&method=CountExpertise&subscribe=login&data=
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 13.09.2019 17:03
     */
    public static function CountExpertise()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $on_add = array();
        $on_update = array();
        $count_expertise_by_company_department_id = array();
        $current_company_department_id = array();
        $warnings[] = 'CountExpertise. Начало метода';
        try {
            $update_department_symmary = DepartmentParameterSummary::find()
                ->where(['parameter_id' => self::PARAMETER_PLANNDED_ISE])
                ->indexBy('company_department_id')
                ->all();

            $all_expertises = Expertise::find()
                ->select([
                    'count(expertise.id) as count',
                    'TIMESTAMPDIFF(MONTH,curdate(),expertise.date_next_expertise) as diff',
                    'expertise.company_department_id as company_department_id'
                ])
                ->where(['in', 'expertise.status_id', [62, 63]])
                ->groupBy('company_department_id,diff')
                ->having(['<=', 'diff', 6])
                ->asArray()
                ->limit(10000)
                ->all();

            if (!empty($all_expertises)) {
                foreach ($all_expertises as $expertise) {
                    if (in_array($expertise['company_department_id'], $current_company_department_id)) {
                        $count_expertise_by_company_department_id[$expertise['company_department_id']] += $expertise['count'];
                    } else {
                        $count_expertise_by_company_department_id[$expertise['company_department_id']] = $expertise['count'];
                        $current_company_department_id[] = $expertise['company_department_id'];
                    }
                }
            }

            /**
             * Если массив на добалвение записей в БД не пусто тогда вызываем местод с флагом на добавление
             */
            if (!empty($on_add)) {
                $add_values_parameters = self::ParameterAdd(self::PARAMETER_PLANNDED_ISE, $on_add);
                if ($add_values_parameters['status'] == 1) {
                    $warnings[] = 'CountExpertise. Данные успешно обновлены';
                    $warnings[] = $add_values_parameters['warnings'];
                } else {
                    $warnings[] = 'CountExpertise. Ошибка при обновлении';
                    $errors[] = $add_values_parameters['errors'];
                    $warnings[] = $add_values_parameters['warnings'];
                }
            }

            foreach ($update_department_symmary as $department_summary) {
                if (isset($count_expertise_by_company_department_id[$department_summary->company_department_id]) &&
                    $count_expertise_by_company_department_id[$department_summary->company_department_id] != $department_summary->value) {
                    $on_update[$department_summary->company_department_id] = $count_expertise_by_company_department_id[$department_summary->company_department_id];
                } elseif (!isset($count_expertise_by_company_department_id[$department_summary->company_department_id])) {
                    $on_update[$department_summary->company_department_id] = 0;
                }
            }

            /**
             * Если массив на обновление записей в БД не пусто тогда вызываем метод с флагом на обновление
             */
            if (!empty($on_update)) {
                $update_value = self::ParameterUpdate(self::PARAMETER_PLANNDED_ISE, $on_update);
                if ($update_value['status'] == 1) {
                    $warnings[] = 'CountExpertise. Данные успешно обновлены';
                    $warnings[] = $update_value['warnings'];
                } else {
                    $warnings[] = 'CountExpertise. Ошибка при обновлении';
                    $errors[] = $update_value['errors'];
                    $warnings[] = $update_value['warnings'];
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'CountExpertise. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'CountExpertise. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод CountReplacementPPE() - Расчёт количества СИЗов необходимых к замене
     * @return array - стандартный массив выходных данных
     *
     * @package frontend\controllers\handbooks
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Department&method=CountReplacementPPE&subscribe=login&data=
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.09.2019 16:14
     */
    public static function CountReplacementPPE()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $result_worker_siz_count = array();
        $update_values = array();
        $add_values = array();
        $date_now = date('Y-m-d', strtotime(AssistantBackend::GetDateNow()));
        $have_been_company_dep = array();
        $warnings[] = 'CountReplacementPPE. Начало метода';
        try {
            $found_worker_siz = WorkerSiz::find()
                ->select([
                    "datediff(worker_siz.date_write_off,'{$date_now}') as diff_date",
                    'worker.company_department_id as company_department_id',
                    'count(worker_siz.id) as count_worker_siz'
                ])
                ->innerJoin('worker', 'worker_siz.worker_id = worker.id')
                ->andWhere(['IN', 'worker_siz.status_id', [64, 65]])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $date_now],
                    ['is', 'worker.date_end', null]
                ])
                ->groupBy(['company_department_id', 'worker_siz.date_write_off'])
                ->having(['<=', 'diff_date', 3])
//                ->indexBy('company_department_id')
                ->asArray()
//                ->limit(80000)
                ->all();
            foreach ($found_worker_siz as $worker_siz_count) {

                if (in_array($worker_siz_count['company_department_id'], $have_been_company_dep)) {
                    $result_worker_siz_count[$worker_siz_count['company_department_id']] += (int)$worker_siz_count['count_worker_siz'];
                } else {
                    $result_worker_siz_count[$worker_siz_count['company_department_id']] = (int)$worker_siz_count['count_worker_siz'];
                    $have_been_company_dep[] = $worker_siz_count['company_department_id'];
                }
            }
            $update_department_symmary = DepartmentParameterSummary::find()
                ->where(['parameter_id' => self::PARAMETER_REPLACEMENT_PPE])
                ->indexBy('company_department_id')
                ->all();
//            Assistant::PrintR($found_worker_siz);die;
            foreach ($result_worker_siz_count as $comp_dep_id => $worker_siz) {
                if (!isset($update_department_symmary[$comp_dep_id]['id'])) {
                    $add_values[$comp_dep_id] = $worker_siz;
                }
            }
            if (!empty($add_values)) {
                $add_values_parameters = self::ParameterAdd(self::PARAMETER_REPLACEMENT_PPE, $add_values);
                if ($add_values_parameters['status'] == 1) {
                    $warnings[] = 'CountReplacementPPE. Данные успешно добавлены';
                    $warnings[] = $add_values_parameters['warnings'];
                } else {
                    $warnings[] = 'CountReplacementPPE. Ошибка при добавлении новых параметров';
                    $errors[] = $add_values_parameters['errors'];
                    $warnings[] = $add_values_parameters['warnings'];
                }
            }
            foreach ($update_department_symmary as $department_summary) {
                if (isset($result_worker_siz_count[$department_summary->company_department_id]) &&
                    $result_worker_siz_count[$department_summary->company_department_id] != $department_summary->value) {
                    $update_values[$department_summary->company_department_id] = $result_worker_siz_count[$department_summary->company_department_id];
                } elseif (!isset($result_worker_siz_count[$department_summary->company_department_id])) {
                    $update_values[$department_summary->company_department_id] = 0;
                }
            }
//            Assistant::PrintR($on_update);die;
            if (!empty($update_values)) {
                $update_value_parameters = self::ParameterUpdate(self::PARAMETER_REPLACEMENT_PPE, $update_values);
                if ($update_value_parameters['status'] == 1) {
                    $warnings[] = 'CountReplacementPPE. Данные успешно обновлены';
                    $warnings[] = $update_value_parameters['warnings'];
                } else {
                    $warnings[] = 'CountReplacementPPE. Ошибка при обновлении';
                    $errors[] = $update_value_parameters['errors'];
                    $warnings[] = $update_value_parameters['warnings'];
                }
            }


        } catch (Throwable $exception) {
            $errors[] = 'CountReplacementPPE. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'CountReplacementPPE. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Метод CountCheckup() - Расчёт количества медосмотров для показателей в блоке уведомлений
     * @return array - стандартный массив выходных данных
     *
     * @package frontend\controllers\handbooks
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\Department&method=CountCheckup&subscribe=login&data=
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.09.2019 18:22
     */
    public static function CountCheckup()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $checkup = array();
        $workers_on_comp_dep = array();
        $on_add = array();
        $on_update = array();
//        $update_department_symmary = array();
        $count_checkup_by_company_department = array();
        $current_company_department_id = array();
        $warnings[] = 'CountCheckup. Начало метода';
        try {
            $date_now = date('Y-m-d', strtotime(AssistantBackend::GetDateNow()));

            /**
             * Отнимаем от текущей даты 14 дней для показа уведомлений
             */
            $mk_date_plan = date('Y-m-d', strtotime($date_now . "- 14 days"));
            $physical_schedules = PhysicalSchedule::find()
                ->select([
                    'physical_worker.worker_id as worker_id',
                    'physical_schedule.company_department_id as company_department_id',
                    'physical_schedule.date_start as date_start',
                    'physical_schedule.date_end as date_end',
                    'physical_worker_date.id as physical_worker_date_id'
                ])
                ->leftJoin('physical_worker', 'physical_worker.physical_schedule_id = physical_schedule.id')
                ->leftJoin('worker', 'physical_worker.worker_id = worker.id')
                ->leftJoin('physical_worker_date', 'physical_worker.id = physical_worker_date.physical_worker_id')
                ->where([
                    'in', 'physical_schedule.physical_kind_id', [self::PLANNED_CHECKUP, self::NOW_CHECKUP]])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $date_now],
                    ['is', 'worker.date_end', null]
                ])
                ->asArray()
                ->all();
            $med_report = MedReport::find()
                ->innerJoin('physical_worker_date', 'physical_worker_date.id = med_report.physical_worker_date')
                ->indexBy(function ($report) {
                    return $report['worker_id'] . '_' . $report['physical_worker_date']['date'];
                })
                ->all();
            $mk_date_plan = date('Y-m-d', strtotime($date_now . "- 14 days"));

            /******************** Перебор графика медосмотров ********************/
            foreach ($physical_schedules as $physical_schedule) {
                if (!empty($physical_schedule['worker_id'])) {
                    /******************** Перебор работников на которых назначен график медосмотров (по плану) ********************/
                    if (!in_array($physical_schedule['company_department_id'], $current_company_department_id)) {
                        $current_company_department_id[] = $physical_schedule['company_department_id'];
                    }
                    if ($physical_schedule['date_start'] >= $mk_date_plan || !isset($med_report[$physical_schedule['worker_id'] . '_' . $physical_schedule['date_end']])) {
                        $worker_id = (int)$physical_schedule['worker_id'];
                        $workers_on_comp_dep[$physical_schedule['company_department_id']][$worker_id] = $worker_id;
                    }
                }
            }
            foreach ($workers_on_comp_dep as $comp_dep_id => $item) {
                $checkup[$comp_dep_id] = count($item);
            }
            $update_department_symmary = DepartmentParameterSummary::find()
                ->where(['parameter_id' => self::PARAMETER_CHECKUP])
                ->indexBy('company_department_id')
                ->all();
//                Assistant::PrintR($count_checkup_by_company_department);die;
            foreach ($checkup as $comp_dep_id => $value) {
                if (!isset($update_department_symmary[$comp_dep_id]['id'])) {
                    $on_add[$comp_dep_id] = $value;
                }
            }
            /**
             * Если массив на добалвение записей в БД не пусто тогда вызываем местод с флагом на добавление
             */
            if (!empty($on_add)) {
                $add_values_parameters = self::ParameterAdd(self::PARAMETER_CHECKUP, $on_add);
                if ($add_values_parameters['status'] == 1) {
                    $warnings[] = 'CountCheckup. Данные успешно обновлены';
                    $warnings[] = $add_values_parameters['warnings'];
                } else {
                    $warnings[] = 'CountCheckup. Ошибка при обновлении';
                    $errors[] = $add_values_parameters['errors'];
                    $warnings[] = $add_values_parameters['warnings'];
                }
            }

            foreach ($update_department_symmary as $department_summary) {
                if (isset($checkup[$department_summary->company_department_id]) &&
                    $checkup[$department_summary->company_department_id] != $department_summary->value) {
                    $on_update[$department_summary->company_department_id] = $checkup[$department_summary->company_department_id];
                } elseif (!isset($checkup[$department_summary->company_department_id])) {
                    $on_update[$department_summary->company_department_id] = 0;
                }
            }
//                Assistant::PrintR($phys_worker);die;
            /**
             * Если массив на обновление записей в БД не пусто тогда вызываем метод с флагом на обновление
             */
            if (!empty($on_update)) {
                $update_value = self::ParameterUpdate(self::PARAMETER_CHECKUP, $on_update);
                if ($update_value['status'] == 1) {
                    $warnings[] = 'CountCheckup. Данные успешно обновлены';
                    $warnings[] = $update_value['warnings'];
                } else {
                    $warnings[] = 'CountCheckup. Ошибка при обновлении';
                    $errors[] = $update_value['errors'];
                    $warnings[] = $update_value['warnings'];
                }
            }

        } catch (Throwable $exception) {
            $errors[] = 'CountCheckup. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'CountCheckup. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод CountInstructionNotifications() - Расчёт количественных показателей для блока уведомлений по "Запланирован инструктаж"
     * @return array - стандартный массив выходных данных
     *
     * @package frontend\controllers\handbooks
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\Department&method=CountInstructionNotifications&subscribe=login&data=
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 19.09.2019 15:31
     */
    public static function CountInstructionNotifications()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $worker_dep = array();
        $on_add = array();
        $on_update = array();
        $current_company_department_id = null;
        $count_instruction_by_company_department = null;
        $count_briefing = 0;
        $briefing_count = array();
        $date_now = date('Y-m-d', strtotime(AssistantBackend::GetDateNow()));
        $warnings[] = 'CountInstructionNotifications. Начало метода';
        try {
            $update_department_symmary = DepartmentParameterSummary::find()
                ->where(['parameter_id' => self::PARAMETER_BRIEFING])
                ->indexBy('company_department_id')
                ->all();

            $workers = Worker::find()
                ->innerJoin('grafic_tabel_date_plan', 'grafic_tabel_date_plan.worker_id = worker.id')
                ->innerJoin('grafic_tabel_main', 'grafic_tabel_date_plan.grafic_tabel_main_id = grafic_tabel_main.id')
//                ->where(['grafic_tabel_main.company_department_id' => $company_department_id])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $date_now],
                    ['is', 'worker.date_end', null]
                ])
                ->andWhere(['grafic_tabel_date_plan.date_time' => $date_now])
                ->andWhere(['grafic_tabel_date_plan.working_time_id' => self::WORKING_TIME_NORMAL])
                ->asArray()
                ->all();

            if (!empty($workers)) {
                foreach ($workers as $worker) {
                    $worker_dep[] = $worker['id'];
                }
                $briefings = Briefing::find()
                    ->select(' briefer.worker_id as briefer_worker_id, max(briefing.date_time) as max_date_briefing, worker.company_department_id as worker_company_department')
                    ->andWhere(['in', 'briefer.worker_id', $worker_dep])
                    ->andWhere(['briefing.type_briefing_id' => self::TYPE_BRIEFING_TWO])
                    ->innerJoin('briefer', 'briefer.briefing_id = briefing.id')
                    ->innerJoin('worker', 'briefer.worker_id = worker.id')
                    ->indexBy('briefer_worker_id')
                    ->groupBy('briefer_worker_id')
                    ->asArray()
                    ->all();
                foreach ($briefings as $briefing) {
                    $between_date = (strtotime($date_now) - strtotime($briefing['max_date_briefing'])) / (60 * 60 * 24);

                    if (($between_date < self::DAY_TYPE_TWO && $between_date > self::DAY_TYPE_ONE) || ($between_date > self::DAY_TYPE_TWO)) {
                        if ($current_company_department_id == $briefing['worker_company_department']) {
                            $briefing_count[$briefing['worker_company_department']]++;
                        } else {
                            $current_company_department_id = $briefing['worker_company_department'];
                            $briefing_count[$briefing['worker_company_department']] = 1;
                        }
                    }
                }
                foreach ($workers as $worker) {
                    if (!isset($briefings[$worker['id']])) {
                        if (isset($briefing_count[$worker['company_department_id']]) && !empty($briefing_count[$worker['company_department_id']])) {
                            $briefing_count[$worker['company_department_id']]++;
                        } else {
                            $briefing_count[$worker['company_department_id']] = 1;
                        }
                    }
                }
                foreach ($briefing_count as $comp_dep_id => $value) {
                    if (!isset($update_department_symmary[$comp_dep_id]['id'])) {
                        $on_add[$comp_dep_id] = $value;
                    }
                }
            } else {
                foreach ($update_department_symmary as $department_summary) {
                    $briefing_count[$department_summary->company_department_id] = 0;
                }
            }

            /**
             * Если массив на добалвение записей в БД не пусто тогда вызываем местод с флагом на добавление
             */
            if (!empty($on_add)) {
                $add_values_parameters = self::ParameterAdd(self::PARAMETER_BRIEFING, $on_add);
                if ($add_values_parameters['status'] == 1) {
                    $warnings[] = 'CountInstructionNotifications. Данные успешно обновлены';
                    $warnings[] = $add_values_parameters['warnings'];
                } else {
                    $warnings[] = 'CountInstructionNotifications. Ошибка при обновлении';
                    $errors[] = $add_values_parameters['errors'];
                    $warnings[] = $add_values_parameters['warnings'];
                }
            }

            foreach ($update_department_symmary as $department_summary) {
                if (isset($briefing_count[$department_summary->company_department_id]) &&
                    $briefing_count[$department_summary->company_department_id] != $department_summary->value) {
                    $on_update[$department_summary->company_department_id] = $briefing_count[$department_summary->company_department_id];
                } elseif (!isset($briefing_count[$department_summary->company_department_id])) {
                    $on_update[$department_summary->company_department_id] = 0;
                }
            }
//            Assistant::PrintR($on_update);die;
            /**
             * Если массив на обновление записей в БД не пусто тогда вызываем метод с флагом на обновление
             */
            if (!empty($on_update)) {
                $update_value = self::ParameterUpdate(self::PARAMETER_BRIEFING, $on_update);
                if ($update_value['status'] == 1) {
                    $warnings[] = 'CountInstructionNotifications. Данные успешно обновлены';
                    $warnings[] = $update_value['warnings'];
                } else {
                    $warnings[] = 'CountInstructionNotifications. Ошибка при обновлении';
                    $errors[] = $update_value['errors'];
                    $warnings[] = $update_value['warnings'];
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'CountInstructionNotifications. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'CountInstructionNotifications. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод SetDepartmentParameterSettings() - Добавление настроек только что созданному аккаунту
     * @param $worker_id - идентификатор (созданного) работника
     * @return array - стандартный массив двыннх
     *
     * @package frontend\controllers\handbooks
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 20.09.2019 13:10
     */
    public static function SetDepartmentParameterSettings($worker_id)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $arr_key = array();
        $department_parameter_id = false;
        $department_parameters_ids = array();
        $company_department_id = false;
        $needle = array(513, 514, 515, 516, 517, 518, 519, 520, 522);
        $warnings[] = 'SetDepartmentParameterSettings. Начало метода';
        try {
            $company_department_id = Worker::find()
                ->select('company_department_id')
                ->where(['id' => $worker_id])
                ->scalar();
            if ($company_department_id != false) {
                $department_parameters_id = DepartmentParameter::find()
                    ->select(['id', 'parameter_id'])
                    ->where(['company_department_id' => $company_department_id])
                    ->asArray()
                    ->all();
                if (!empty($department_parameters_id)) {
                    foreach ($department_parameters_id as $department_parameter_id) {
                        $department_parameters_ids[$department_parameter_id['parameter_id']] = $department_parameter_id['id'];
                    }
                }
            }
            if (!empty($department_parameters_ids)) {
                $warnings[] = 'SetDepartmentParameterSettings. Проверяю параметры на полноту';
                $arr_key = array_keys($department_parameters_ids);
                if (count(array_intersect($arr_key, $needle)) == count($needle)) {
                    $arr_intersect = true;
                    $warnings[] = 'SetDepartmentParameterSettings. Все параметры есть';
                } else {
                    $arr_intersect = false;
//                    $warnings[] = 'SetDepartmentParameterSettings. Не хватает части параметров';
//                    $warnings[] = 'SetDepartmentParameterSettings. Требуется параметров: '.count($needle);
//                    $warnings[] = 'SetDepartmentParameterSettings. Имеется параметров: '.count(array_intersect($arr_key, $needle));
//                    $warnings[] = 'SetDepartmentParameterSettings. Исходный массив';
//                    $warnings[] = $arr_key;
                }
//                $arr_intersect = (count(array_intersect($arr_key, $needle))) ? true : false;
//                $warnings['$arr_intersect'] = $arr_intersect;
//                $warnings['count(array_intersect($arr_key, $needle))'] = count(array_intersect($arr_key, $needle));
                if ($arr_intersect == true) {
                    foreach ($department_parameters_ids as $dep_paramater_id) {
                        $response_amicum_method = self::WriteDepParamSummaryWorkerSettingDB(
                            $dep_paramater_id,
                            $worker_id,
                            16,
                            null,
                            0
                        );
                        $result[] = json_encode($response_amicum_method['Items']);
                    }
                } else {
                    $differenece_arrays = array_diff($needle, $arr_key);
                    foreach ($differenece_arrays as $diff_param) {
                        $response_amicum_method = self::WriteDepDB(
                            $company_department_id,
                            $diff_param,
                            3,
                            0,
                            0,
                            null,
                            'pr',
                            null,
                            1
                        );
                        $response_amicum_method = self::WriteDepParamSummaryWorkerSettingDB(
                            $response_amicum_method['id'],
                            $worker_id,
                            16,
                            null,
                            0
                        );
                        $result[] = json_encode($response_amicum_method['Items']);
                    }
                }
            } else {
                foreach ($needle as $parameter_id) {
                    $response_amicum_method = self::WriteDepDB(
                        $company_department_id,
                        $parameter_id,
                        3,
                        0,
                        0,
                        null,
                        'pr',
                        null,
                        1
                    );
                    $response_amicum_method = self::WriteDepParamSummaryWorkerSettingDB(
                        $response_amicum_method['id'],
                        $worker_id,
                        16,
                        null,
                        0
                    );
                    $result[] = json_encode($response_amicum_method['Items']);
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'SetDepartmentParameterSettings. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'SetDepartmentParameterSettings. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GettingCountWorker() - метод подсчета численности ( с вызовом другого метода)
     * @param null $data_post
     * @return array число, количество людей в подразделении( + ниже лежащие подразделения)
     *
     *
     * @package frontend\controllers
     * @example
     *
     *  http://localhost/read-manager-amicum?controller=PhysicalSchedule&method=GettingCountWorker&subscribe=&data={%22company_id%22:4029294}
     * //  подходит для тестирования, что найдены все нижестоящие подразделения
     * //  http://localhost/read-manager-amicum?controller=PhysicalSchedule&method=GettingCountWorker&subscribe=&data={%22company_id%22:20017252}
     * //  подходит для поиска людей
     *
     * @author Митяева Лидия <mla@pfsz.ru>
     * Created date: on 03.09.2019 11:29
     */
    public static function GettingCountWorker($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $result = array();                                                                               // Промежуточный результирующий массив
        $warnings[] = 'GettingCountWorker. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('GettingCountWorker. Данные с фронта не получены');
            }
            $warnings[] = 'GettingCountWorker. Данные успешно переданы';
            $warnings[] = 'GettingCountWorker. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'GettingCountWorker. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'company_id'))
            ) {
                throw new Exception('GettingCountWorker. Переданы некорректные входные параметры');
            }                                                                                                        // Проверяем наличие в нем нужных нам полей

            $warnings[] = 'GettingCountWorker. Данные с фронта получены';
            $company_id = $post_dec->company_id;

            $response = PhysicalScheduleController::Number($company_id);                                      //вызов метода
            /****************** Формирование ответа ******************/
            if ($response['status'] == 1) {
                $result = $response['Items'];
                $status = $response['status'];
                $errors = $response['errors'];
                $warnings = $response['warnings'];
            } else {
                $errors = $response['errors'];
                $warnings = $response['warnings'];
                throw new \yii\db\Exception('Number. метод Number завершлся с ошибкой');
            }

        } catch (Throwable $exception) {
            $errors[] = 'GettingCountWorker. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'GettingCountWorker. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод ParameterUpdate() - обновление параметров, сводных параметров
     * @param $flag_update - флаг обновление данных или редактирование
     * @param $parameter_id - параметр
     * @param array $on_update - массив на обновление
     * @param array $on_add - массив на редактирование
     * @return array - стандартный массив данных
     *
     * @package frontend\controllers\handbooks
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 24.09.2019 9:43
     */
    public static function ParameterUpdate($parameter_id, $on_update = array())
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $dep_par_value = array();
        $dep_par_symmary = array();
        $batch_dep_parameters = array();
        $add_dep_parameters = array();
        $add_dep_parameters_values = array();
        $add_dep_parameters_symmary = array();
        $dep_parameter_id_exist = null;
        $last_oper_id = null;
        $parameter = null;
        $date_time = AssistantBackend::GetDateNow();
        $warnings[] = 'ParameterUpdate. Начало метода';
        try {
            $dep_parameter_id_exist = DepartmentParameter::find()
                ->select(['id'])
                ->where(['parameter_id' => $parameter_id, 'parameter_type_id' => 3])
                ->indexBy('id')
                ->all();

            $update_department_symmary = DepartmentParameterSummary::find()
                ->where(['parameter_id' => $parameter_id])
                ->indexBy(function ($func) {
                    return $func['company_department_id'] . '_' . $func['parameter_id'];
                })
                ->asArray()
                ->all();

            foreach ($dep_parameter_id_exist as $dep_parameter) {
                foreach ($on_update as $comp_dep_id => $item) {
                    $parameter = $comp_dep_id . '_' . $parameter_id;
                    if ($dep_parameter->id == $update_department_symmary[$parameter]['id']) {
                        $dep_par_value[] = [$dep_parameter->id, $date_time, (int)$item, self::STATUS_ACTUAL];
                        $dep_par_symmary[] = [$dep_parameter->id, $comp_dep_id, $parameter_id, self::PARAMETER_CALCULATED, $date_time, (int)$item];
                    }
                }
            }
            if (!empty($dep_par_value)) {
                $result_dep_parameter_vlaue = Yii::$app->db->createCommand()->batchInsert('department_parameter_value',
                    ['department_parameter_id', 'date_time', 'value', 'status_id'],
                    $dep_par_value)
                    ->execute();
                if ($result_dep_parameter_vlaue != 0) {
                    $warnings[] = 'ParameterUpdate. Добавление в таблицу department_parameter_value успешно';
                } else {
                    throw new Exception('ParameterUpdate. Ошибка при добавлении в таблицу department_parameter_value');
                }
            }

            if (!empty($dep_par_symmary)) {
                $sql = Yii::$app->db->queryBuilder->batchInsert('department_parameter_summary',
                    ['id', 'company_department_id', 'parameter_id', 'parameter_type_id', 'date_time', 'value'],
                    $dep_par_symmary);
                $result_query = Yii::$app->db->createCommand($sql . "ON DUPLICATE KEY UPDATE `value` = VALUES (`value`),`date_time` = VALUES (`date_time`)")->execute();
                if ($result_query !== 0) {
                    $warnings[] = 'CountNewInjunction. Добавление/Обновление данных выполнено успешно в таблицу department_parameter_summary';
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'ParameterUpdate. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'ParameterUpdate. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод ParameterAdd() - добавление параметров, сводных параметров
     * @param $parameter_id
     * @param array $on_add
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 03.10.2019 11:58
     */
    public static function ParameterAdd($parameter_id, $on_add = array())
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $date_time = AssistantBackend::GetDateNow();
        $warnings[] = 'ParameterAdd. Начало метода';
        try {
            if (!empty($on_add)) {
                $last_dep_parameter = DepartmentParameter::find()
                    ->select('id')
                    ->orderBy('id DESC')
                    ->limit(1)
                    ->scalar();
                $last_oper_id = (int)$last_dep_parameter;
                foreach ($on_add as $comp_dep_id => $item) {
                    $last_oper_id++;
                    $add_dep_parameters[] = [$last_oper_id, $comp_dep_id, $parameter_id, self::PARAMETER_CALCULATED];
                    $add_dep_parameters_values[] = [$last_oper_id, $date_time, (int)$item, self::STATUS_ACTUAL];
                    $add_dep_parameters_symmary[] = [$last_oper_id, $comp_dep_id,
                        $parameter_id,
                        self::PARAMETER_CALCULATED,
                        $date_time,
                        (int)$item];
                }

//                    $warnings['Массив перед добавленем параметров'] = $add_dep_parameters;
//                    $warnings['Массив перед добавленем параметров значений'] = $add_dep_parameters_values;
//                    $warnings['Массив перед добавленем суммарных значений параметров'] = $add_dep_parameters_symmary;
                $batch_dep_parameters = Yii::$app->db->createCommand()->batchInsert('department_parameter',
                    ['id',
                        'company_department_id',
                        'parameter_id',
                        'parameter_type_id'],
                    $add_dep_parameters)
                    ->execute();
                if ($batch_dep_parameters != 0) {
                    $warnings[] = 'ParameterAdd. Параметры добавлены успешно';
                } else {
                    throw new Exception('ParameterAdd. Ошибка при добавлении параметров');
                }

                $batch_dep_parameters_value = Yii::$app->db->createCommand()->batchInsert('department_parameter_value',
                    ['department_parameter_id',
                        'date_time',
                        'value',
                        'status_id'], $add_dep_parameters_values)
                    ->execute();
                if ($batch_dep_parameters_value != 0) {
                    $warnings[] = 'ParameterAdd. Параметры добавлены успешно';
                } else {
                    throw new Exception('ParameterAdd. Ошибка при добавлении параметров');
                }

                $batch_dep_parameters_summary = Yii::$app->db->createCommand()->batchInsert('department_parameter_summary',
                    ['id',
                        'company_department_id',
                        'parameter_id',
                        'parameter_type_id',
                        'date_time',
                        'value'], $add_dep_parameters_symmary)->execute();
                if ($batch_dep_parameters_summary != 0) {
                    $warnings[] = 'ParameterAdd. Параметры добавлены успешно';
                } else {
                    throw new Exception('ParameterAdd. Ошибка при добавлении параметров');
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'ParameterAdd. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'ParameterAdd. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


//    /** ЭТО НЕ ПРАВИЛЬНЫЙ МЕТОД, ВОЗВРАЩАЕТ НЕ ВЕСЬ СПИСОК ДЕПАРТАМЕНТОВ */
//     * Метод Find() - метода для получения списка нижележащих участков для участка (вызывается в FindDepartment)
//     * @param $company_id - список вышестоящего,$all_company все участки
//     * @param $all_company
//     * @return array
//     *
//     *Выходные параметры: Список нижележащих участков $list_department
//     *
//     * @package frontend\controllers\handbooks
//     *Входные обязательные параметры: JSON со структрой:
//     * @example
//     *
//     * @author Митяева Лидия <mla@pfsz.ru>
//     * Created date: on 24.09.2019 14:16
//     */
//    public static function Find($company_id, $all_company)
//    {
//        $new_upper = array();
//        $list_company[] = $company_id;
//        foreach ($all_company as $company) {
//            if (in_array($company['upper_company_id'], $list_company)) {
//                $new_upper[] = $company['id'];
//                $list_company[] = $company['id'];
//            }
//        }
//        if ($new_upper) {
//            $list_department[] = self::Find($new_upper, $all_company);
//        } else {
//            $list_department[] = $list_company;
//            //Assistant::PrintR($list_department); die;
//        }
//        // Assistant::PrintR($company_id);
//        return $list_department;
//    }


    /**
     * Метод FindDepartment() - метода для получения списка нижележащих участков для участка
     * @param null $company_id
     * @return array
     *
     *Выходные параметры: Список нижележащих участков $list_department
     *
     * @package frontend\controllers\handbooks
     *Входные обязательные параметры: $company_department_id
     * @example    http://localhost/read-manager-amicum?controller=SuperTest&method=actionFindDepartment&subscribe=&data=4029926
     * Тестируется через метод в controller SuperTest, метод actionFindDepartment
     * @author Митяева Лидия <mla@pfsz.ru>
     * Created date: on 24.09.2019 14:45
     *
     * полный рефакторинг и переделка, т.к. метод написан полностью не правильно
     * алгоритм:
     * 1. получить список компаний(департаментов) и проиндексировать его по id
     * 2. создать список первого уровня нижележащих компаний на основе компаний, у который upper_company not null
     * 3. найти для искомой компании список нижележащий компаний и если он не пуст,
     * то запустить для каждой из этих компаний рекурсивный метод, который вернет список нижележащих компаний в ней
     * в этот рекурсивный метод передать список первого уровня нижележащих компаний
     * 4. по цепочке все собрать обратно и вернуть пользователю
     * Разработал: Якимов М.Н.
     * Дата 23.01.2020
     * Пример использования: 127.0.0.1/read-manager-amicum?controller=SuperTest&method=actionFindDepartment&subscribe=&data=4029926
     */
    public static function FindDepartment($company_id = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $all_company = array();                                                                                         // Массив компаний
        $result = array();                                                                                              // Промежуточный результирующий массив
        $microtime_start = microtime(true);
        $warnings[] = 'FindDepartment. Начало метода';
        try {
            // проверяем входные параметры метода
            if ($company_id == NULL || $company_id == '') {
                throw new Exception("FindDepartment. Передан пустой/нулевой параметр company_id/company_department_id");
            }

            // берем из БД все компании что есть и индексируем их по id
            $all_company = Company::find()
                ->asArray()
                ->indexBy('id')
                ->all();

            $warnings[] = 'FindDepartment получили из БД все компании что есть и индексируем их по id ' . $duration_method = round(microtime(true) - $microtime_start, 6);

            // создаем справочник нижележащих компаний
            foreach ($all_company as $company) {
                if ($company['upper_company_id']) {
                    $spr_upper_company[$company['upper_company_id']][] = $company['id'];
                }
            }
            $warnings[] = 'FindDepartment создали список нижележащих компаний ' . $duration_method = round(microtime(true) - $microtime_start, 6);

            // вызываем рекурсивный метод
            $result = self::FindDepartmentRecursiv($company_id, $spr_upper_company);
            $warnings[] = 'FindDepartment. Количество найденных';

        } catch (Throwable $exception) {
            $errors[] = 'FindDepartment. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'FindDepartment. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, "all_company" => $all_company);
    }

    // FindDepartmentRecursiv - метод рекурсивного поиска вложений департаментов/компаний
    // $company_id          - искомая компания
    // $spr_upper_company   - справочник вложенных компаний
    // $count_recursiv      - количество вызовов рекурсии < 20
    public static function FindDepartmentRecursiv($company_id, $spr_upper_company, $count_recursiv = 0)
    {
        $count_recursiv++;
        $list_department = array();
        if ($count_recursiv > 20) {
            throw new Exception("FindDepartmentRecursiv. Зацикливание рекурсии. Больше 20 шагов в глубь");
        }
        // проверяем на наличие первого вхождения
        // если есть, то начинаем искать все, что ниже, иначе выходим с метода
        if (isset($spr_upper_company[$company_id])) {
            // перебираем найденные компании вложенные
            foreach ($spr_upper_company[$company_id] as $company) {
                //вызов рекурсии
                $list_department = array_merge($list_department, self::FindDepartmentRecursiv($company, $spr_upper_company, $count_recursiv));
            }
        }
        // докидываем в результирующий список
        $list_department[] = $company_id;
        // оптравляем назад
        return $list_department;
    }

    /**
     * Метод CountCheckKnowledge() - Метод расчёта показателей для блока уведомлений "Запланированно обучение"
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\Department&method=CountCheckKnowledge&subscribe=login&data=
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 22.10.2019 8:14
     */
    public static function CountCheckKnowledge()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $on_add = array();
        $on_update = array();
        $count_check_knowledge = array();
        $date_now = date('Y-m-d', strtotime(AssistantBackend::GetDateNow()));
        $warnings[] = 'CountCheckKnowledge. Начало метода';
        try {
            $check_knowledge = CheckKnowledge::find()
                ->select([
                    'count(ckw.id)                                                        as count_check_knowledge_id',
                    'check_knowledge.company_department_id                                as check_company_department_id',
                    'DATE_ADD(check_knowledge.date,INTERVAL 3 YEAR)                      as next_date',
                    'if (check_knowledge.type_check_knowledge_id = 2,datediff(DATE_ADD(check_knowledge.date,INTERVAL 3 YEAR),curdate()),datediff(DATE_ADD(`check_knowledge`.date,INTERVAL 1 YEAR),curdate()))  as diff_date',
                    'check_knowledge.date',
                    'ckw.worker_id as worker_id'
                ])
                ->innerJoin('check_knowledge_worker ckw', 'check_knowledge.id = ckw.check_knowledge_id')
                ->innerJoin('worker w', 'ckw.worker_id = w.id')
                ->where(['in', 'check_knowledge.type_check_knowledge_id', [1, 2]])
                ->andWhere(['or',
                    ['>', 'w.date_end', $date_now],
                    ['is', 'w.date_end', null]
                ])
                ->groupBy('check_company_department_id, next_date,diff_date,date,worker_id')
                ->indexBy('worker_id')
                ->orderBy('check_knowledge.date asc')
                ->asArray()
                ->all();

            if (isset($check_knowledge)) {
                foreach ($check_knowledge as $check_knowledge) {
                    if (($check_knowledge['diff_date'] <= 14 && $check_knowledge['diff_date'] >= 0) || ($check_knowledge['diff_date'] < 0))
                        if (isset($count_check_knowledge[$check_knowledge['check_company_department_id']])) {
                            $count_check_knowledge[$check_knowledge['check_company_department_id']]++;
                        } else {
                            $count_check_knowledge[$check_knowledge['check_company_department_id']] = 1;
                        }
                }
            }
            $update_department_symmary = DepartmentParameterSummary::find()
                ->where(['parameter_id' => self::PLANNED_CHECK_KNOWLEDGE])
                ->indexBy('company_department_id')
                ->all();

            foreach ($count_check_knowledge as $comp_dep_id => $value) {
                if (!isset($update_department_symmary[$comp_dep_id]['id'])) {
                    $on_add[$comp_dep_id] = $value;
                }
            }
            /**
             * Если массив на добалвение записей в БД не пусто тогда вызываем местод с флагом на добавление
             */
            if (!empty($on_add)) {
                $add_values_parameters = self::ParameterAdd(self::PLANNED_CHECK_KNOWLEDGE, $on_add);
                if ($add_values_parameters['status'] == 1) {
                    $warnings[] = 'CountCheckKnowledge. Данные успешно обновлены';
                    $warnings[] = $add_values_parameters['warnings'];
                } else {
                    $warnings[] = 'CountCheckKnowledge. Ошибка при обновлении';
                    $errors[] = $add_values_parameters['errors'];
                    $warnings[] = $add_values_parameters['warnings'];
                }
            }


            foreach ($update_department_symmary as $department_summary) {
                if (isset($count_check_knowledge[$department_summary->company_department_id]) &&
                    $count_check_knowledge[$department_summary->company_department_id] != $department_summary->value) {
                    $on_update[$department_summary->company_department_id] = $count_check_knowledge[$department_summary->company_department_id];
                } elseif (!isset($count_check_knowledge[$department_summary->company_department_id])) {
                    $on_update[$department_summary->company_department_id] = 0;
                }
            }
            /**
             * Если массив на обновление записей в БД не пусто тогда вызываем метод с флагом на обновление
             */
            if (!empty($on_update)) {
                $update_value = self::ParameterUpdate(self::PLANNED_CHECK_KNOWLEDGE, $on_update);
                if ($update_value['status'] == 1) {
                    $warnings[] = 'CountCheckKnowledge. Данные успешно обновлены';
                    $warnings[] = $update_value['warnings'];
                } else {
                    $warnings[] = 'CountCheckKnowledge. Ошибка при обновлении';
                    $errors[] = $update_value['errors'];
                    $warnings[] = $update_value['warnings'];
                }
            }


        } catch (Throwable $exception) {
            $errors[] = 'CountCheckKnowledge. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'CountCheckKnowledge. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод CountCheckCertifiaction() - Метод расчёта показателей для блока уведомлений "Назначена аттестация"
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\Department&method=CountCheckCertifiaction&subscribe=login&data=
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 15.11.2019 9:31
     */
    public static function CountCheckCertifiaction()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $on_add = array();
        $on_update = array();
        $count_check_knowledge = array();
        $date_now = date('Y-m-d', strtotime(AssistantBackend::GetDateNow()));
        $warnings[] = 'CountCheckCertifiaction. Начало метода';
        try {
            $check_knowledge_ITR = CheckKnowledge::find()
                ->select([
                    'count(ckw.id)                                                              as count_check_knowledge_id',
                    'check_knowledge.company_department_id                                      as check_company_department_id',
                    'DATE_ADD(check_knowledge.date,INTERVAL 3 YEAR)                             as next_date',
                    'datediff(DATE_ADD(max(check_knowledge.date),INTERVAL 3 YEAR),curdate())    as diff_date',
                    'ckw.worker_id                                                              as worker_id'
                ])
                ->innerJoin('check_knowledge_worker ckw', 'check_knowledge.id = ckw.check_knowledge_id')
                ->innerJoin('worker w', 'ckw.worker_id = w.id')
                ->where(['check_knowledge.type_check_knowledge_id' => self::TYPE_CHECK_KNOWLEDGE_ATT])
                ->andWhere(['or',
                    ['>', 'w.date_end', $date_now],
                    ['is', 'w.date_end', null]
                ])
                ->groupBy('check_company_department_id, next_date, worker_id')
                ->indexBy('worker_id')
                ->asArray()
                ->all();
            if (isset($check_knowledge_ITR)) {
                foreach ($check_knowledge_ITR as $check_knowledge) {
                    if (($check_knowledge['diff_date'] <= 14 && $check_knowledge['diff_date'] >= 0) || ($check_knowledge['diff_date'] < 0))
                        if (isset($count_check_knowledge[$check_knowledge['check_company_department_id']])) {
                            $count_check_knowledge[$check_knowledge['check_company_department_id']] += $check_knowledge['count_check_knowledge_id'];
                        } else {
                            $count_check_knowledge[$check_knowledge['check_company_department_id']] = $check_knowledge['count_check_knowledge_id'];
                        }
                }
            }
            $update_department_symmary = DepartmentParameterSummary::find()
                ->where(['parameter_id' => self::PLANNED_CHECK_KNOWLEDGE_ATT])
                ->indexBy('company_department_id')
                ->all();

            foreach ($count_check_knowledge as $comp_dep_id => $value) {
                if (!isset($update_department_symmary[$comp_dep_id]['id'])) {
                    $on_add[$comp_dep_id] = $value;
                }
            }
            /**
             * Если массив на добалвение записей в БД не пусто тогда вызываем местод с флагом на добавление
             */
            if (!empty($on_add)) {
                $add_values_parameters = self::ParameterAdd(self::PLANNED_CHECK_KNOWLEDGE_ATT, $on_add);
                if ($add_values_parameters['status'] == 1) {
                    $warnings[] = 'CountCheckCertifiaction. Данные успешно обновлены';
                    $warnings[] = $add_values_parameters['warnings'];
                } else {
                    $warnings[] = 'CountCheckCertifiaction. Ошибка при обновлении';
                    $errors[] = $add_values_parameters['errors'];
                    $warnings[] = $add_values_parameters['warnings'];
                }
            }


            foreach ($update_department_symmary as $department_summary) {
                if (isset($count_check_knowledge[$department_summary->company_department_id]) &&
                    $count_check_knowledge[$department_summary->company_department_id] != $department_summary->value) {
                    $on_update[$department_summary->company_department_id] = $count_check_knowledge[$department_summary->company_department_id];
                } elseif (!isset($count_check_knowledge[$department_summary->company_department_id])) {
                    $on_update[$department_summary->company_department_id] = 0;
                }
            }
            /**
             * Если массив на обновление записей в БД не пусто тогда вызываем метод с флагом на обновление
             */
            if (!empty($on_update)) {
                $update_value = self::ParameterUpdate(self::PLANNED_CHECK_KNOWLEDGE_ATT, $on_update);
                if ($update_value['status'] == 1) {
                    $warnings[] = 'CountCheckCertifiaction. Данные успешно обновлены';
                    $warnings[] = $update_value['warnings'];
                } else {
                    $warnings[] = 'CountCheckCertifiaction. Ошибка при обновлении';
                    $errors[] = $update_value['errors'];
                    $warnings[] = $update_value['warnings'];
                }
            }

        } catch (Throwable $exception) {
            $errors[] = 'CountCheckCertifiaction. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'CountCheckCertifiaction. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод CountInquiry() - Метод расчёта показателей для блока уведомлений "Происшествие"
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\Department&method=CountInquiry&subscribe=login&data=
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 30.10.2019 15:42
     */
    public static function CountInquiry()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $on_update = array();
        $on_add = array();
        $count_inquiry = array();
        $count_inquirys = array();
        $date_now = AssistantBackend::GetDateFormatYMD();
        $warnings[] = 'CountInquiry. Начало метода';
        try {
            $date_now_start = $date_now . ' 00:00:00';
            $date_now_end = $date_now . ' 23:59:59';
            $count_events = EventPb::find()
                ->select(['count(event_pb.id) count_event_pb', 'event_pb.company_department_id', 'event_pb.case_pb_id'])
                ->andWhere(['>=', 'event_pb.date_time_event', $date_now_start])
                ->andWhere(['<=', 'event_pb.date_time_event', $date_now_end])
                ->andWhere(['in', 'event_pb.case_pb_id', [1, 2, 3]])
                ->groupBy('company_department_id,case_pb_id')
                ->indexBy('company_department_id')
                ->asArray()
                ->all();
            if (!empty($count_events)) {
                foreach ($count_events as $count_event) {
                    $count_inquiry[$count_event['company_department_id']] = $count_event['count_event_pb'];
                }
            }
            $update_department_symmary = DepartmentParameterSummary::find()
                ->where(['parameter_id' => self::PARAMETER_ACCIDENT])
                ->indexBy('company_department_id')
                ->all();

            foreach ($count_inquiry as $comp_dep_id => $value) {
                if (!isset($update_department_symmary[$comp_dep_id]['id'])) {
                    $on_add[$comp_dep_id] = $value;
                }
            }
            /**
             * Если массив на добалвение записей в БД не пусто тогда вызываем местод с флагом на добавление
             */
            if (!empty($on_add)) {
                $add_values_parameters = self::ParameterAdd(self::PARAMETER_ACCIDENT, $on_add);
                if ($add_values_parameters['status'] == 1) {
                    $warnings[] = 'CountInquiry. Данные успешно обновлены';
                    $warnings[] = $add_values_parameters['warnings'];
                } else {
                    $warnings[] = 'CountInquiry. Ошибка при обновлении';
                    $errors[] = $add_values_parameters['errors'];
                    $warnings[] = $add_values_parameters['warnings'];
                }
            }


            foreach ($update_department_symmary as $department_summary) {
                if (isset($count_inquiry[$department_summary->company_department_id]) &&
                    $count_inquiry[$department_summary->company_department_id] != $department_summary->value) {
                    $on_update[$department_summary->company_department_id] = $count_inquiry[$department_summary->company_department_id];
                } elseif (!isset($count_inquiry[$department_summary->company_department_id])) {
                    $on_update[$department_summary->company_department_id] = 0;
                }
            }
            /**
             * Если массив на обновление записей в БД не пусто тогда вызываем метод с флагом на обновление
             */
            if (!empty($on_update)) {
                $update_value = self::ParameterUpdate(self::PARAMETER_ACCIDENT, $on_update);
                if ($update_value['status'] == 1) {
                    $warnings[] = 'CountInquiry. Данные успешно обновлены';
                    $warnings[] = $update_value['warnings'];
                } else {
                    $warnings[] = 'CountInquiry. Ошибка при обновлении';
                    $errors[] = $update_value['errors'];
                    $warnings[] = $update_value['warnings'];
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'CountInquiry. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'CountInquiry. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetUpperCompanies() - Метод возвращает идентификатор второго уровня компании
     * @param $companies - массив участков у которых необходимо получить компании
     * @return array  - массив со структурой: [переданные company_id] => company_id (второго уровня (пример: Для УКТ вернёт идентификатор ш.Заполярная))
     *
     * ПРИМЕР ВЫЗОВА:
     *          $compaines = [20028766,20019171,20019150];
     *          $result = DepartmentController::GetUpperCompanies($compaines);
     *
     * @package frontend\controllers\handbooks
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 21.01.2020 14:22
     */
    public static function GetUpperCompanies($companies)
    {
        $result = array();
        $lowerest_comp_dep = array();
        $all_company = Company::find()
            ->asArray()
            ->all();
        foreach ($all_company as $company) {
            if ($company['upper_company_id']) {
                $spr_upper_company[$company['upper_company_id']][] = $company['id'];
            }
        }
        $company_upperest = Company::find()
            ->select('id')
            ->where(['is', 'upper_company_id', null])
            ->asArray()
            ->all();
        foreach ($company_upperest as $upperest_company) {
            $upper_comp[] = $upperest_company['id'];
        }
        $all_companies = Company::find()
            ->select('id,upper_company_id')
            ->where(['in', 'upper_company_id', $upper_comp])
            ->asArray()
            ->all();
        foreach ($all_companies as $item) {
            $lowerest_comp_dep[$item['id']] = self::FindDepartmentRecursiv($item['id'], $spr_upper_company);
        }
        foreach ($companies as $comp) {
            foreach ($lowerest_comp_dep as $second_level_comp_dep => $item_lowerest_comp_dep) {
                if (in_array($comp, $item_lowerest_comp_dep)) {
                    $result[$comp] = $second_level_comp_dep;
                }
            }
        }
        return $result;
    }

    /**
     * Метод GetFirstLevelCompanies() - Получение первого уровня компании
     * @param $companies - массив компаний
     * @return array - ключами переданныу участки, а значениями идентификатор участка первого уровня
     *
     * @throws Exception
     * @example
     *
     * @package frontend\controllers\handbooks
     *
     * Входные обязательные параметры:
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 27.01.2020 17:07
     */
    public static function GetFirstLevelCompanies($companies)
    {
        $result = array();
        $lowerest_comp_dep = array();
        $all_company = Company::find()
            ->asArray()
            ->all();
        foreach ($all_company as $company) {
            if ($company['upper_company_id']) {
                $spr_upper_company[$company['upper_company_id']][] = $company['id'];
            }
        }
        $company_upperest = Company::find()
            ->select('id')
            ->where(['is', 'upper_company_id', null])
            ->asArray()
            ->all();
        foreach ($company_upperest as $upperest_company) {
            $lowerest_comp_dep[$upperest_company['id']] = self::FindDepartmentRecursiv($upperest_company['id'], $spr_upper_company);
        }
        foreach ($companies as $comp) {
            foreach ($lowerest_comp_dep as $second_level_comp_dep => $item_lowerest_comp_dep) {
                if (in_array($comp, $item_lowerest_comp_dep)) {
                    $result[$comp] = $second_level_comp_dep;
                }
            }
        }
        return $result;
    }

    /**
     * Метод GetDepartmentsWithWorkersContingent() - Получение участков с людьми у которых есть контингент и которые работают
     * @return array - Стандартыный массив выходных данных: Items       - участки с людьми у которых есть контингент и которые работают
     *                                                      status      - статус работы метода (0,1)
     *                                                      warnings    - массив предупреждений (ход выполнения метода)
     *                                                      errors      - массив ошибок
     *
     * @package frontend\controllers\handbooks
     * АЛГОРИТМ РАБОТЫ МЕТОДА:
     * 1. Получить список вышестоящих участков (upper_company_id пусто)
     * 2. Получить работающих людей у которых есть контингенты
     * 3. Группируем людей по участкам
     * 4. Выгружаем список вложенных компани (upper_company_id не пусто)
     * 5. Группируем в вышестоящиее
     * 6. Перебираем вышестоящие участки
     *      6.1 Передаём в функцию идентификатор вышестоящей компании, сгруппированных по участкам людей и массив влооженных компаний для рекурсивного построения участков с людьми
     * 7. Конец перебора
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\Department&method=GetDepartmentsWithWorkersContingent&subscribe=&data={"year":2020}
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 05.03.2020 8:54
     */
    public static function GetDepartmentsWithWorkersContingent($data_post = null)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $worker_group_by_company_id = array();
        $method_name = 'GetDepartmentsWithWorkersContingent';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'year'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . ". Проверил входные данные";
            $year = $post_dec->year;
            if (empty($year)) {
                throw new Exception($method_name . '. Ошибка не был передан год');
            }
            $date_year = "{$year}-12-31";

            // получаем список всех 0 департаментов/компаний
            $companies = Company::find()
                ->where('upper_company_id is null')
                ->asArray()
                ->all();
            if ($companies === false) {
                throw new Exception($method_name . ". Список компаний пуст");
            }
            /**
             * Получаем всех работающих людей у которых есть контингенты
             */
            $date_now = date('Y-m-d', strtotime(AssistantBackend::GetDateNow()));
            $year = date('Y', strtotime(AssistantBackend::GetDateNow()));
            $list_workers_with_departments = Worker::find()
                ->select(['worker.id as worker_id',
                    'worker.date_end as worker_date_end',
                    'employee.first_name as first_name',
                    'employee.last_name as last_name',
                    'employee.patronymic as patronymic',
                    'worker_object.role_id',
                    'worker.tabel_number as stuff_number',
                    'position.id as position_id',
                    'position.title as position_title',
                    'position.qualification as qualification',
                    'company_department.id as company_department_id',
                    'department.title as department_title',
                    'department.id as department_id',
                    'company.id as company_id',
                    'company.title as company_title',
                    'company.upper_company_id as upper_company_id',
                    'contingent.id as contingent_id'
                ])
                ->innerJoin('worker_object', 'worker_object.worker_id = worker.id')
                ->innerJoin('employee', 'employee.id = worker.employee_id')
                ->innerJoin('position', 'position.id = worker.position_id')
                ->innerJoin('company_department', 'company_department.id = worker.company_department_id')
                ->innerJoin('company', 'company.id = company_department.company_id')
                ->innerJoin('department', 'department.id = company_department.department_id')
                ->innerJoin('contingent', 'contingent.company_department_id = worker.company_department_id and contingent.role_id = worker_object.role_id')
                ->where(['or',
                    ['>', 'worker.date_end', $date_year],
                    ['is', 'worker.date_end', null]
                ])
                ->andWhere(['<=', 'worker.date_start', $date_year])
                ->andWhere(['contingent.year_contingent' => $year])
                ->asArray()
                ->all();
//            if (empty($list_workers_with_departments)) {
//                throw new \Exception($method_name . '. Нет работников с заполнеными контингентами');
//            }
            /**
             * Группируем работников в участки
             */
            if (!empty($list_workers_with_departments)) {
                foreach ($list_workers_with_departments as $worker) {
                    $worker_group_by_company_id[$worker['company_department_id']][] = $worker;
                }
            }
            unset($list_workers_with_departments, $worker);

            /**
             * Получаем список вложенных компаний
             */
            $attachment_companies = Company::find()
                ->select(
                    'company.id as id,
                    company.title as title,
                    upper_company_id'
                )
                ->where('upper_company_id is not null')
                ->asArray()
                ->all();
            if ($attachment_companies === false) {
                $warnings[] = "GetDepartmentListWithWorkers. Список вложенных компаний пуст";
            }
            /**
             * группируем участки в вышестоящие
             */
            foreach ($attachment_companies as $attachment_company) {
                $company_by_upper_company_id[$attachment_company['upper_company_id']][] = $attachment_company;
            }
            unset($attachment_company);
            unset($attachment_companies);
            foreach ($companies as $company) {
                $list_companys[] = self::getCompanyAttachment($company, $worker_group_by_company_id, $company_by_upper_company_id);
            }
            $result['id'] = 1;
            $result['title'] = "Список работников";
            $result['state'] = array('expanded' => true);
            $result['children'] = $list_companys;
            $result['is_chosen'] = 0;
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
     * Метод GetDepartmentsWithWorkers() - Получение участков с людьми которые работают на данный момент
     * @return array - Стандартыный массив выходных данных: Items       - участки с людьми которые работают на данный момент
     *                                                      status      - статус работы метода (0,1)
     *                                                      warnings    - массив предупреждений (ход выполнения метода)
     *                                                      errors      - массив ошибок
     *
     * @package frontend\controllers\handbooks
     *
     * АЛГОРИТМ РАБОТЫ МЕТОДА:
     * 1. Получить список вышестоящих участков (upper_company_id пусто)
     * 2. Получить работающих людей
     * 3. Группируем людей по участкам
     * 4. Выгружаем список вложенных компани (upper_company_id не пусто)
     * 5. Группируем в вышестоящиее
     * 6. Перебираем вышестоящие участки
     *      6.1 Передаём в функцию идентификатор вышестоящей компании, сгруппированных по участкам людей и массив влооженных компаний для рекурсивного построения участков с людьми
     * 7. Конец перебора
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 10.03.2020 10:50
     */
    public static function GetDepartmentsWithWorkers()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetDepartmentsWithWorkersContingent';
        $warnings[] = $method_name . '. Начало метода';
        try {

            // получаем список всех 0 департаментов/компаний
            $companies = Company::find()
                ->where('upper_company_id is null')
                ->asArray()
                ->all();
            if ($companies === false) {
                throw new Exception($method_name . ". Список компаний пуст");
            }
            /**
             * Получаем всех работающих людей, которые работают на данный момент
             */
            $date_now = date('Y-m-d', strtotime(AssistantBackend::GetDateNow()));
            $list_workers_with_departments = Worker::find()
                ->select(['worker.id as worker_id',
                    'employee.first_name as first_name',
                    'employee.last_name as last_name',
                    'employee.patronymic as patronymic',
                    'worker_object.role_id',
                    'worker.tabel_number as stuff_number',
                    'worker.date_end as worker_date_end',
                    'position.id as position_id',
                    'position.title as position_title',
                    'position.qualification as qualification',
                    'company_department.id as company_department_id',
                    'department.title as department_title',
                    'department.id as department_id',
                    'company.id as company_id',
                    'company.title as company_title',
                    'company.upper_company_id as upper_company_id'
                ])
                ->innerJoin('worker_object', 'worker_object.worker_id = worker.id')
                ->innerJoin('employee', 'employee.id = worker.employee_id')
                ->innerJoin('position', 'position.id = worker.position_id')
                ->innerJoin('company_department', 'company_department.id = worker.company_department_id')
                ->innerJoin('company', 'company.id = company_department.company_id')
                ->innerJoin('department', 'department.id = company_department.department_id')
                ->where(['or',
                    ['>', 'worker.date_end', $date_now],
                    ['is', 'worker.date_end', null]
                ])
                ->asArray()
                ->all();
            if (empty($list_workers_with_departments)) {
                throw new Exception($method_name . '. Нет работников с заполнеными контингентами');
            }
            /**
             * Группируем работников в участки
             */
            foreach ($list_workers_with_departments as $worker) {
                $worker_group_by_company_id[$worker['company_department_id']][] = $worker;
            }
            unset($list_workers_with_departments, $worker);

            /**
             * Получаем список вложенных компаний
             */
            $attachment_companies = Company::find()
                ->select(
                    'company.id as id,
                    company.title as title,
                    upper_company_id'
                )
                ->where('upper_company_id is not null')
                ->asArray()
                ->all();
            if ($attachment_companies === false) {
                $warnings[] = "GetDepartmentListWithWorkers. Список вложенных компаний пуст";
            }
            /**
             * группируем участки в вышестоящие
             */
            foreach ($attachment_companies as $attachment_company) {
                $company_by_upper_company_id[$attachment_company['upper_company_id']][] = $attachment_company;
            }
            unset($attachment_company);
            unset($attachment_companies);
            foreach ($companies as $company) {
                $list_companys[] = self::getCompanyAttachment($company, $worker_group_by_company_id, $company_by_upper_company_id);
            }
            $result['id'] = 1;
            $result['title'] = "Список работников";
            $result['state'] = array('expanded' => true);
            $result['children'] = $list_companys;
            $result['is_chosen'] = 0;
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

    // SetDefaultParametersForUser - создание параметров по умолчанию для пользователя в личном кабинете
    // http://127.0.0.1/read-manager-amicum?controller=handbooks\Department&method=SetDefaultParametersForUser&subscribe=&data={"company_department_id":4029938}
    public static function SetDefaultParametersForUser($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'SetDefaultParametersForUser';
        $order_places_reason = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'company_department_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';

            $company_department_id = $post_dec->company_department_id;
            $session = Yii::$app->session;
            $worker_id = $session['worker_id'];
            $response = self::SetDepartmentParameterSettings($worker_id);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $warnings[] = 'SetDefaultParametersForUser. Данные успешно обновлены';
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception($method_name . '. Ошибка при создании параметров');
            }
            $json_to_restrict = json_encode(array('company_department_id' => $company_department_id));
            $response = self::GetDepParameter($json_to_restrict);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $result = $response['Items'];
                $warnings[] = 'SetDefaultParametersForUser. Данные успешно получены';
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception($method_name . '. Ошибка получении параметров');
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

    // AddCompanyWithDepartmentDB - метод добавления компании и департамента в БД
    // Входные параметры:
    //      company_title       - название создаваемого департамента/компании
    //      upper_company_id    - ключ вышестоящей компании
    //      work_mode           - режим работы предприятия/департамента
    // http://127.0.0.1/read-manager-amicum?controller=handbooks\Department&method=AddCompanyWithDepartmentDB&subscribe=&data={"company_title":"Прочее11", "upper_company_id":"101","work_mode":"5"}
    public static function AddCompanyWithDepartmentDB($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array('company_id' => -1);
        $method_name = 'AddCompanyWithDepartmentDB. ';
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
                !property_exists($post_dec, 'company_title') or
                !property_exists($post_dec, 'upper_company_id') or
                !property_exists($post_dec, 'work_mode')
            )                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';

            $company_title = $post_dec->company_title;
            $upper_company_id = $post_dec->upper_company_id;
            $work_mode = $post_dec->work_mode;

            $response = HandbookEmployeeController::AddDepartment($upper_company_id, $company_title, 5, $work_mode);
            if ($response['status'] == 1) {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                $result['company_id'] = $response['company_id'];
                $result['company_title'] = $company_title;
                $result['work_mode'] = $work_mode;
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception($method_name . "Не удлалось сохранить новое подразделение");
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
     * GetAttachDeprtmentByUpper - Метод получения вложенных департаментов на основе вышестоящего департамента
     * @param $company_department_id - ключ департамента, у которого ищется вышестоящий и по нему вложенные
     * @return array|mixed[]|object[]
     */
    public static function GetAttachDeprtmentByUpper($company_department_id)
    {
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("GetAttachDeprtmentByUpper");
        $result = (object)array();
        try {
            $log->addLog("Начало выполнения метода");

            $company = Company::findOne(['id' => $company_department_id]);
            if ($company and $company['upper_company_id']) {
                $upper_company = $company['upper_company_id'];
            } else {
                $upper_company = $company_department_id;
            }
            $response = DepartmentController::FindDepartment($upper_company);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка получения вложенных департаментов');
            }
            $result = $response['Items'];

            $log->addLog("Окончание выполнения метода", $count_record);
        } catch
        (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $result], $log->getLogAll());
    }
}
