<?php
/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\controllers;

use backend\controllers\Assistant as BackendAssistant;
use Exception;
use frontend\controllers\handbooks\DepartmentController;
use frontend\models\Attachment;
use frontend\models\ClassifierDiseases;
use frontend\models\Company;
use frontend\models\CompanyDepartmentAttachment;
use frontend\models\Contingent;
use frontend\models\ContingentFromSout;
use frontend\models\Diseases;
use frontend\models\DocumentEventPb;
use frontend\models\DocumentEventPbAttachment;
use frontend\models\DocumentPhysical;
use frontend\models\FactorsOfContingent;
use frontend\models\GroupMedReportResult;
use frontend\models\HarmfulFactors;
use frontend\models\MedReport;
use frontend\models\MedReportResult;
use frontend\models\Physical;
use frontend\models\PhysicalAttachment;
use frontend\models\PhysicalKind;
use frontend\models\PhysicalSchedule;
use frontend\models\PhysicalScheduleAttachment;
use frontend\models\PhysicalWorker;
use frontend\models\Role;
use frontend\models\Worker;
use Throwable;
use Yii;
use yii\db\Query;


//  Number()                        - медот подсчета численности, вынесенный отдельно, чтобы его вызывали в других методах
//  GetPhysical                     - графика по году, типу графика и структурному подразделению                                       /* :3 */
//  GetContingent()                 - метод получает справочник контингентов
//  SaveContingent()                - метод по сохранению контингентов.                                                                /* :3 */
//  DelContingent()                 - метод удаления из справочника контингентов
//  SavePhysicalWorker()            - Метод сохранения запланированного списка сотрудников (таблица physical_worker)                   /* :3 */
//  GetWorker()                     - метод получения списка персонала по подразделению с учетом списка контингентов
//  StatikResultMedReport()         - считает статистику результатов мед.обследований.
//  StaticGender()                  - метод, который считает количество мужчин и женщин на заданном участке                            /* :3 */
//  GetListPhysical()               - Метод получения списка графиков, которые были ранее созданы сотрудником                          /* :3 */
//  GetHandbookDieases              - Справочник профзаболеваний
//  GetHandbookMedReportResult      - Справочник заключений медицинской комисии
//  GetAccountingMedReport          - Метод получения данных для учёта медицинских осмотров

//  GetHandbookClassifierDiseases   - Справочник "Классификатор заболеваний"
//  SavePhysical                    - Сохранение графика
//  GetPhysicalSchedule             - метод получения данных планового графика медосмотров
//  GetPhysicalSchedules            - метод получения данных всех плановых графиков медосмотров
//  GetFactorOfContingent           - Справочник вредных факоторов по участку по роли
//  SavePhysicalSchedule            - Сохранение планового графика МО на участки
//  GraficPlannedMO                 - Метод получения каленадря медосмотров
//  DeletePhysical                  - Метод удаления графика МО
//  SaveDocumentPhysical            - Метод сохранения приказа на участок
//  RemovePhysicalAttachment        - Удаление связки файла на СП
//  SavePhysicalAttachment          - Сохранение вложения на СП (Согласование/Приказ)

//  GetMedicalReport                - метод получения сведений о медицинских заключениях
//  DeleteMedicalReport             - метод удаления медицинскиого заключения
//  SaveMedicalReport               - метод сохранения медицинского заключения или отметки о прохождении
//  SavePassedMedicalExamination    - метод сохранения Отметки о прохождении медицинского осмотра
//  HarmfulFactorsByContingents     - Получение факторов сгруппированных по участку и роли (контингент = участок + роль)
//  GetHarmfulFactors               - Метод получения вредных факторов (одно и тоже с GetHandbookHarmolFactors)
//  GetHandbookHarmolFactors        - Справочник вредных факторов  (одно и тоже с GetHarmfulFactors)
//  SaveHarmfulFactor               - сохранить справочник вредных факторов

// GetGroupMedReportResult()      - Получение справочника групп медицинских заключений
// SaveGroupMedReportResult()     - Сохранение справочника групп медицинских заключений
// DeleteGroupMedReportResult()   - Удаление справочника групп медицинских заключений


class PhysicalScheduleController extends \yii\web\Controller
{


    const FIT = 1;  // результат заключения: нет противоп-ний
    const UNFIT = 3; // результат заключения: временные против-ния
    const UNFIT_TIME = 2; // результат заключения: Постоянные против-ния
    const CHECKUP = 4;   // результат заключения:  Заключение не получено, надо дообследование
    const WOMEN = 2;   //определение пола для статистики, женщины
    const MEN = 1;   //определение пола для статистики, мужчины

    public function actionIndex()
    {
        return $this->render('index');
    }





    // CountPeople - метод подсчета численности
    //  http://localhost/read-manager-amicum?controller=positioningsystem\ForbiddenZone&method=CountPeople&subscribe=&data={%22company_id%22:4029294}
    //  подходит для тестирования, что найдены все нижестоящие подразделения
    //  http://localhost/read-manager-amicum?controller=positioningsystem\ForbiddenZone&method=CountPeople&subscribe=&data={%22company_id%22:20017252}
    //  подходит для поиска людей
    //  02/09


    /**
     * Метод Number() - медот подсчета численности, вынесенный отдельно, чтобы его вызывали в других методах
     * ЭТОТ ПИЗДЕЦ НЕЛЬЗЯ ИСПОЛЬЗОВАТЬ!!! ЯКИМОВ М.Н.
     * @param $company_id
     * @return array
     *
     *Выходные параметры: 4 стандартных массива Items, errors, status, warnings
     *
     * @package frontend\controllers
     * @example
     *
     * @author Митяева Лидия <mla@pfsz.ru>
     * Created date: on 05.09.2019 8:38
     */
    public static function Number($company_id)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $count_data = array();                                                                               // Промежуточный результирующий массив
        $warnings[] = 'Number. Начало метода';

        $count_data[] = $company_id;
        $new_upper = $company_id;                                                                                   // список компаний по которым будем исскать нижестоящие
        /****************** Ищем все нижестоящие подразделения ******************/
        while ($new_upper) {
            $upper_company_id = Company::find()//Список найденных нижестоящих
            ->where(['in', 'company.upper_company_id', $new_upper])
                ->asArray()
                ->all();
            $new_upper = array();
            foreach ($upper_company_id as $upper) {
                $count_data[] = $upper['id'];                                                                       //результирующий список всех подразделений
                $new_upper[] = $upper['id'];
            }
        }
        /****************** НАходим количество людей по всем нужным подразделениям ******************/
        $worker_count = Worker::find()
            ->where(['in', 'worker.company_department_id', $count_data])
            ->asArray()
            ->all();

        $count_data = count($worker_count);
        if (!($count_data)) {
            $warnings[] = 'Number. По заданному подразделению и всем нижестоящих людей не найдено';
        }
        $warnings[] = 'Number. Конец метода';
        $warnings[] = $count_data;
        $result = $count_data;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    const ACTIVE = 1;

    /**
     * Метод GetPhysical() - Получение графика по году, типу графика и структурному подразделению  //Или принимает id physical?
     * @param null $data_post
     * @return array
     *
     *Выходные параметры:
     *                          |----id                              - ключ мед.осмотра
     *                          |----title                           - название мед. осмотра
     *                          |----year                            - год, за который создан мед. осмотр
     *                          |----worker_id                       - ключ сотрудника, который создает мед. осмотр
     *                          |----physical_kind_id                - тип мед. осмотра
     *                          [                                    - набор графиков проведения в данном мед.осмотре
     *                              |----company_department_id       - ключ подразделения
     *                                      |----data_start          - дата начала мед. осмотра для такого подразделения
     *                                      |----data_end            = дата окончанния мед. осмотра для такого подразделения
     *                                      |----count_worker        - количество людей в подразделении и в ниже лежащих
     *                           ]
     *
     *
     * @package frontend\controllers
     * Входные обязательные параметры: title, year, physical_kind_id
     * @example  http://localhost/read-manager-amicum?controller=PhysicalSchedule&method=GetPhysical&subscribe=&data={%22title%22:%22%D0%93%D1%80%D0%B0%D1%84%D0%B8%D0%BA%20%D0%BF%D0%BE%202017%20%D0%B3%D0%BE%D0%B4%D1%83%22,%22physical_kind_id%22:2,%22year%22:2017}
     *
     * @author Митяева Лидия <mla@pfsz.ru>
     * Created date: on 05.09.2019 10:40
     */
    public static function GetPhysical($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $schedule = array();                                                                               // Промежуточный результирующий массив
        $warnings[] = 'GetPhysical. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('GetPhysical. Данные с фронта не получены');
            }
            $warnings[] = 'GetPhysical. Данные успешно переданы';
            $warnings[] = 'GetPhysical. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'GetPhysical. Декодировал входные параметры';

            if (
                !(property_exists($post_dec, 'title') ||
                    property_exists($post_dec, 'physical_kind_id') ||
                    property_exists($post_dec, 'year'))
            ) {
                throw new Exception('GetPhysical. Переданы некорректные входные параметры');
            }                                                                                                        // Проверяем наличие в нем нужных нам полей
            $warnings[] = 'GetPhysical. Данные с фронта получены';

            $title = $post_dec->title;
            $physical_kind_id = $post_dec->physical_kind_id;
            $year = $post_dec->year;
            /****************** Находим мед.осмотр, по году, вышестоящему подразделению и типу ******************/

            $physicales_schedule = Physical::find()
                ->joinWith('physicalSchedules')
                ->where(['physical.year' => $year, 'physical.physical_kind_id' => $physical_kind_id, 'physical.title' => $title])
                ->asArray()
                ->all();
            //Assistant::PrintR($physicales_schedule); die;

            $warnings[] = 'GetPhysical. График мед.осмотра найден';
            if (!($physicales_schedule)) {
                throw new Exception('GetPhysical. График мед.осмотра, по году, вышестоящему подразделению и типу не найден');
            }
            $physicales = $physicales_schedule['0'];
            //Assistant::PrintR($physicales); die;
            $warnings[] = 'GetPhysical. Данные графика мед.осмотра найдены в БД';
            /****************** Заполняем поля ******************/
            $schedule['id'] = $physicales['id'];
            $schedule['title'] = $physicales['title'];
            $schedule['year'] = $physicales['year'];
            $schedule['physical_kind_id'] = $physicales['physical_kind_id'];
            $schedule['worker_id'] = $physicales['worker_id'];

            foreach ($physicales['physicalSchedules'] as $physicale) {
                $schedule[$physicale['company_department_id']]['date_start'] = $physicale['date_start'];
                $schedule[$physicale['company_department_id']]['date_end'] = $physicale['date_end'];
                /****************** Считаем численность подразделения ******************/
                $company_id = $physicale['company_department_id'];
                $response = PhysicalScheduleController::Number($company_id);  //стучится внутри постоянно в бд. Мне это не нравится
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
                $schedule[$physicale['company_department_id']]['Count_worker'] = $result;
                //$schedule[$physical_schedule['id']]['company_department_id']= $physical_schedule['company_department_id'];
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetPhysical. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetPhysical. Конец метода';
        $result = $schedule;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetContingent() - метод получает справочник контингентов
     * @param null $data_post
     *      company_department_id    -   департамент/подразделение на которое получаем список контингента
     *      year                     -   год на который получаем список контингента
     * @return array
     *      contingents:
     *          {contingent_id}
     *                  contingent_id:                      // ключ контингента
     *                  company_department_id:              // ключ подразделения департамента
     *                  year_contingent:                    // год списка контингентов
     *                  role_id:                            // ключ роли
     *                  surface_underground:                // подземный/поверхностный
     *                  harmful_factors:                    // список вредных факторов
     *                      {harmful_factors_id}
     *                          harmful_factors_id:                     // вредный фактор ключ
     *                          harmful_factors_title:                  // вредный фактор название
     *Выходные параметры: Список контингентов (+профессия, подразделение, период и ключ данного контингента)
     *
     * @package frontend\controllers
     *Входные обязательные параметры: номер подразделения и год графика мед.осмотра
     * @example  http://127.0.0.1/read-manager-amicum?controller=PhysicalSchedule&method=GetContingent&subscribe=&data={"company_department_id":4029931,"year":2020}
     *
     * @author Якимов М.Н.
     */
    public static function GetContingent($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $method_name = "GetContingent";
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                     // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception($method_name . '. Данные с фронта не получены');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'year') ||
                !property_exists($post_dec, 'company_department_id')
            ) {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }                                                                                                           // Проверяем наличие в нем нужных нам полей
            $warnings[] = $method_name . ' Данные с фронта получены';

            $year = $post_dec->year;
            $company_department_id = $post_dec->company_department_id;

            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $warnings[] = $response['warnings'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . '. Ошибка при получении списка вложенных участков');
            }


            /**
             *          {contingent_id}
             *                  contingent_id:                      // ключ контингента
             *                  company_department_id:              // ключ подразделения департамента
             *                  role_id:                            // ключ роли
             *                  surface_underground:                // подземный/поверхностный
             *                  year_contingent:                    // год списка контингентов
             *                  harmful_factors:                    // список вредных факторов
             *                      {harmful_factors_id}
             *                          harmful_factors_id:                     // вредный фактор ключ
             *                          harmful_factors_title:                  // вредный фактор название
             */

            $contingents = Contingent::find()
                ->select('contingent.id as contingent_id,
                contingent.company_department_id,
                contingent.status,
                contingent.role_id,
                role.title as role_title,
                role.surface_underground,
                contingent.period, 
                contingent.year_contingent, 
                harmful_factors.id as harmful_factors_id,
                 harmful_factors.title as harmful_factors_title')
                ->innerJoin('role', 'contingent.role_id = role.id')
                ->innerJoin('factors_of_contingent', 'factors_of_contingent.contingent_id = contingent.id')
                ->innerJoin('harmful_factors', 'harmful_factors.id = factors_of_contingent.harmful_factors_id')
                ->where(['contingent.year_contingent' => $year])
                ->andWhere(['IN', 'contingent.company_department_id', $company_departments])
                ->asArray()
                ->all();


            foreach ($contingents as $contingent) {
//                $contingents_result[]=$contingent;
                $contingents_result[$contingent['contingent_id']]['contingent_id'] = $contingent['contingent_id'];
                $contingents_result[$contingent['contingent_id']]['company_department_id'] = $contingent['company_department_id'];
                $contingents_result[$contingent['contingent_id']]['role_id'] = $contingent['role_id'];
                $contingents_result[$contingent['contingent_id']]['role_title'] = $contingent['role_title'];

                if ($contingent['surface_underground'] == 1) {
                    $contingents_result[$contingent['contingent_id']]['surface_underground'] = 'Подземный';
                } else {
                    $contingents_result[$contingent['contingent_id']]['surface_underground'] = 'Поверхностный';
                }

                $contingents_result[$contingent['contingent_id']]['period'] = $contingent['period'];
                $contingents_result[$contingent['contingent_id']]['year_contingent'] = $contingent['year_contingent'];
                $contingents_result[$contingent['contingent_id']]['status'] = $contingent['status'];

                $contingents_result[$contingent['contingent_id']]['harmful_factors'][$contingent['harmful_factors_id']]['harmful_factors_id'] = $contingent['harmful_factors_id'];
                $contingents_result[$contingent['contingent_id']]['harmful_factors'][$contingent['harmful_factors_id']]['harmful_factors_title'] = $contingent['harmful_factors_title'];
            }

            if (!isset($contingents_result)) {
                $result = (object)array();
            } else {
                $result = $contingents_result;
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
     * Метод DelContingent() - метод удаления из справочника контингентов
     * @param null $data_post
     *      contingent_id    -   департамент/подразделение на которое получаем список контингента
     * @return array
     * @package frontend\controllers
     * @example  http://127.0.0.1/read-manager-amicum?controller=PhysicalSchedule&method=DelContingent&subscribe=&data={"contingent_id":4029931}
     *
     * @author Якимов М.Н.
     */
    public static function DelContingent($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $method_name = "DelContingent";
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                     // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception($method_name . '. Данные с фронта не получены');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'contingent_id')
            ) {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }                                                                                                           // Проверяем наличие в нем нужных нам полей
            $warnings[] = $method_name . ' Данные с фронта получены';

            $contingent_id = $post_dec->contingent_id;

            $result = Contingent::deleteAll(['id' => $contingent_id]);

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
     * Метод SaveContingent() - метод по сохранению контингентов.
     * Вопрос. как загружаем список вредных факторов? Пусть пока это уже типо есть в бд.
     * Пусть пока это будет что на вход номер подразделения и профессии, если такого еще нет (контингента) - то записываем.
     *
     * @param null $data_post
     * @return array
     *
     *Выходные параметры:
     *
     * @package frontend\controllers
     *Входные обязательные параметры:
     *              contingent:
     *                  contingent_id:                      // ключ контингента
     *                  company_department_id:              // ключ подразделения департамента
     *                  role_id:                            // ключ роли
     *                  year_contingent:                    // год списка контингентов
     *                  harmful_factors:                    // список вредных факторов
     *                      {harmful_factors_id}
     *                          harmful_factors_id:                     // вредный фактор ключ
     *                          harmful_factors_title:                  // вредный фактор название
     *
     *
     *                                       |-----company_department_id       - ключ подразделения
     *                                       |-----position_id                 - ключ профессии
     *                                       |-----period                      - период, через который для данного контингента проходит медосмотр
     *                                       |-----harmful_factors             - вредные факторы (список)
     *                                            |-----[]
     *
     * @example  http://127.0.0.1/read-manager-amicum?controller=PhysicalSchedule&method=SaveContingent&subscribe=&data={}
     *
     * @author Митяева Лидия <mla@pfsz.ru>
     * Created date: on 05.09.2019 15:21
     */
    public static function SaveContingent($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $result = array();                                                                               // Промежуточный результирующий массив
        $method_name = "SaveContingent";
        $warnings[] = $method_name . '. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception($method_name . '. Данные с фронта не получены');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;

            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            //Assistant::PrintR($post_dec);die;
            if (
                !(property_exists($post_dec, 'contingent'))
            ) {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }                                                                                                        // Проверяем наличие в нем нужных нам полей

            $warnings[] = $method_name . '. Данные с фронта получены';
            $contingent = $post_dec->contingent;

            $contingent_id = $contingent->contingent_id;

            //Если не существует, до добавляем нового контингента и вредные факторы для него
            $save_contingent = Contingent::findOne(['id' => $contingent_id]);
            if (!$save_contingent) {
                $save_contingent = new Contingent();
            }

            $save_contingent->company_department_id = $contingent->company_department_id;
            $save_contingent->role_id = $contingent->role_id;
            $save_contingent->status = $contingent->status;
            $save_contingent->period = $contingent->period;
            $save_contingent->year_contingent = $contingent->year_contingent;

            $warnings[] = $method_name . '. Таблица с информацией о контингенте заполнена';
            if ($save_contingent->save()) {                                                                                  //сохранение запрета
                $warnings[] = $method_name . '. Успешное сохранение  данных о контингенте';
                $save_contingent->refresh();
                $contingent->contingent_id = $save_contingent->id;
                $contingent_id = $save_contingent->id;
            } else {
                $errors[] = $save_contingent->errors;
                throw new Exception($method_name . '. Ошибка при сохранении контингента');
            }

            //добвление вредных факторов
            FactorsOfContingent::deleteAll(['contingent_id' => $contingent_id]);
            foreach ($contingent->harmful_factors as $harmful_factor) {
                $factors_of_contingent[] = [$harmful_factor->harmful_factors_id, $contingent_id];
            }

            if (isset($factors_of_contingent)) {
                $result_factors_of_contingent = Yii::$app->db->createCommand()
                    ->batchInsert('factors_of_contingent', ['harmful_factors_id', 'contingent_id'], $factors_of_contingent)//массовая вставка в БД
                    ->execute();
                if ($result_factors_of_contingent != 0) {
                    $role = Role::findOne(["id" => $contingent->role_id]);
                    if (!$role) {
                        throw new Exception($method_name . '. Ошибка при поиске роли контингента');
                    }
                    $warnings[] = $method_name . '. Успешное сохранение вредных факторов для контингента';
                    $warnings[] = $role;
                    if ($role->surface_underground == 1) {
                        $contingent->surface_underground = 'Подземный';
                    } else {
                        $contingent->surface_underground = 'Поверхностный';
                    }
                } else {
                    throw new Exception($method_name . '. Ошибка при добавлении вредных факторов для контингента');
                }
            }
            $result = $contingent;
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $errors[] = $exception->getFile();
            $status *= 0;
        }

        $warnings[] = $method_name . '. Конец метода';


        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    /**
     * Метод SaveSoutContingents() - метод по сохранению массива контингентов.
     *
     * @param null $data_post
     * @return array
     *
     *Выходные параметры:
     *
     * @package frontend\controllers
     *  Входные обязательные параметры:
     * [
     *              contingent:
     *                  contingent_id:                      // ключ контингента
     *                  company_department_id:              // ключ подразделения департамента
     *                  role_id:                            // ключ роли
     *                  year_contingent:                    // год списка контингентов
     *                  harmful_factors:                    // список вредных факторов
     *                      {harmful_factors_id}
     *                          harmful_factors_id:                     // вредный фактор ключ
     *                          harmful_factors_title:                  // вредный фактор название
     *
     *
     *                                       |-----company_department_id       - ключ подразделения
     *                                       |-----position_id                 - ключ профессии
     *                                       |-----period                      - период, через который для данного контингента проходит медосмотр
     *                                       |-----harmful_factors             - вредные факторы (список)
     *                                            |-----[]
     * ]
     *
     * @example  http://127.0.0.1/read-manager-amicum?controller=PhysicalSchedule&method=SaveSoutContingents&subscribe=&data={}
     *
     * @author Митяева Лидия <mla@pfsz.ru>
     * Created date: on 05.09.2019 15:21
     */
    public static function SaveSoutContingents($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $result = array();                                                                               // Промежуточный результирующий массив
        $method_name = "SaveSoutContingents";
//        $data_post = '{"contingents":[{"company_department_id":4029721,"contingent_id":-2,"role_id":295,"role_title":"Механик участка (пов)","year_contingent":2020,"surface_underground":0,"period":"","harmful_factors":{"2":{"harmful_factors_id":2,"harmful_factors_title":"Производственный шум"},"4":{"harmful_factors_id":4,"harmful_factors_title":"Физические перегрузки"},"157":{"harmful_factors_id":157,"harmful_factors_title":"Пр.1 п.3.4.2. Общая вибрация"}},"company_department_title":"Сервисное предприятие ВМЗ"},{"company_department_id":20039280,"contingent_id":-3,"role_id":188,"role_title":"ГРП","year_contingent":2020,"surface_underground":1,"period":"","harmful_factors":{"1":{"harmful_factors_id":1,"harmful_factors_title":"Подземные работы"},"2":{"harmful_factors_id":2,"harmful_factors_title":"Производственный шум"},"4":{"harmful_factors_id":4,"harmful_factors_title":"Физические перегрузки"},"156":{"harmful_factors_id":156,"harmful_factors_title":"Пр.1 п.3.4.1. Локальная вибрация"},"157":{"harmful_factors_id":157,"harmful_factors_title":"Пр.1 п.3.4.2. Общая вибрация"},"160":{"harmful_factors_id":160,"harmful_factors_title":"Пр.1 п.3.7. Инфразвук"},"166":{"harmful_factors_id":166,"harmful_factors_title":"Пр.1 п.4.1. Физические перегрузки (физическая динамическая нагрузка, масса поднимаемого и перемещаемого груза вручную, стереотипные рабочие движения, статическая нагрузка, рабочая поза, наклоны корпуса, перемещение в пространстве) (при отнесении условий труда по данным факторам по результатам аттестации рабочих мест по условиям труда к подклассу вредности 3.1 и выше)"}},"company_department_title":"Служба ОТ и ПБ"},{"company_department_id":20039280,"contingent_id":-5,"role_id":295,"role_title":"Механик участка (пов)","year_contingent":2020,"surface_underground":0,"period":"","harmful_factors":{"2":{"harmful_factors_id":2,"harmful_factors_title":"Производственный шум"},"4":{"harmful_factors_id":4,"harmful_factors_title":"Физические перегрузки"},"157":{"harmful_factors_id":157,"harmful_factors_title":"Пр.1 п.3.4.2. Общая вибрация"}},"company_department_title":"Служба ОТ и ПБ"},{"company_department_id":20039280,"contingent_id":-4,"role_id":202,"role_title":"Токарь","year_contingent":2020,"surface_underground":0,"period":"","harmful_factors":{},"company_department_title":"Служба ОТ и ПБ"}]}';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception($method_name . '. Данные с фронта не получены');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;

            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            //Assistant::PrintR($post_dec);die;
            if (
                !(property_exists($post_dec, 'contingents'))
            ) {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }                                                                                                        // Проверяем наличие в нем нужных нам полей

            $warnings[] = $method_name . '. Данные с фронта получены';
            $contingents = $post_dec->contingents;

            $counter = 0;
            $roles = Role::find()
                ->select(['id', 'surface_underground'])
                ->asArray()
                ->indexBy('id')
                ->all();
            if (!empty($contingents)) {
                foreach ($contingents as $key => $contingent) {
                    $contingent_id = $contingent->contingent_id;//Если не существует, до добавляем нового контингента и вредные факторы для него
                    $save_contingent = Contingent::findOne(['id' => $contingent_id]);
                    if (!$save_contingent) {
                        $save_contingent = new Contingent();
                    }
                    $save_contingent->company_department_id = $contingent->company_department_id;
                    $save_contingent->role_id = $contingent->role_id;
                    $save_contingent->period = $contingent->period;
                    $save_contingent->year_contingent = $contingent->year_contingent;
                    $warnings[] = $method_name . '. Таблица с информацией о контингенте заполнена';
                    if ($save_contingent->save()) {                                                                                  //сохранение запрета
                        $warnings[] = $method_name . '. Успешное сохранение  данных о контингенте';
                        $save_contingent->refresh();
                        $contingents[$key]->contingent_id = $save_contingent->id;
                        $contingent_id = $save_contingent->id;
                    } else {
                        $errors[] = $save_contingent->errors;
                        throw new Exception($method_name . '. Ошибка при сохранении контингента');
                    }
                    //добвление вредных факторов
                    FactorsOfContingent::deleteAll(['contingent_id' => $contingent_id]);
                    foreach ($contingent->harmful_factors as $harmful_factor) {
                        $factors_of_contingent[] = [$harmful_factor->harmful_factors_id, $contingent_id];
                        $counter++;
                    }
                    if ($counter >= 2000) {
                        $result_factors_of_contingent = Yii::$app->db->createCommand()
                            ->batchInsert('factors_of_contingent', ['harmful_factors_id', 'contingent_id'], $factors_of_contingent)//массовая вставка в БД
                            ->execute();
                        if ($result_factors_of_contingent == 0) {
                            throw new Exception($method_name . '. Ошибка при сохранении факторов контингента');
                        }
                        if (isset($roles[$contingent->role_id]['id']) && !empty($roles[$contingent->role_id]['id'])) {
                            $surface = $roles[$contingent->role_id]['surface_underground'];
                            if ($surface == 1) {
                                $contingents[$key]->surface_underground = 'Подземный';
                            } else {
                                $contingents[$key]->surface_underground = 'Поверхностный';
                            }
                        }
                        $factors_of_contingent = array();
                        $counter = 0;
                    }
                }
            }
            unset($roles, $contingent, $key);

            if (isset($factors_of_contingent) && !empty($factors_of_contingent)) {
                $result_factors_of_contingent = Yii::$app->db->createCommand()
                    ->batchInsert('factors_of_contingent', ['harmful_factors_id', 'contingent_id'], $factors_of_contingent)//массовая вставка в БД
                    ->execute();
                if ($result_factors_of_contingent == 0) {
                    throw new Exception($method_name . '. Ошибка при добавлении вредных факторов для контингента');
                }
            }
            $result = $contingents;
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $errors[] = $exception->getFile();
            $status *= 0;
        }

        $warnings[] = $method_name . '. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод SavePhysicalWorker() - Метод сохранения запланированного списка сотрудников (таблица physical_worker)/
     * PS/ сохраняем всех сотрудников из подразделения, а выборочные сотрудники - только для печати
     * @param null $data_post
     * @return array
     *
     *Выходные параметры:
     *
     * @package frontend\controllers
     *Входные обязательные параметры: ключ графика мед.осмотра, для которого сохраняем список сотруудников
     *
     *                                      ------ physical_schedule_id
     *
     * @example http://localhost/read-manager-amicum?controller=PhysicalSchedule&method=SavePhysicalWorker&subscribe=&data={%22physical_schedule_id%22:8}
     *
     * @author Митяева Лидия <mla@pfsz.ru>
     * Created date: on 11.09.2019 14:47
     */
    public static function SavePhysicalWorker($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $physical_worker = array();                                                                               // Промежуточный результирующий массив
        $warnings[] = 'SavePhysicalWorker. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('SavePhysicalWorker. Данные с фронта не получены');
            }
            $warnings[] = 'SavePhysicalWorker. Данные успешно переданы';
            $warnings[] = 'SavePhysicalWorker. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'SavePhysicalWorker. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'physical_schedule_id'))
            ) {
                throw new Exception('SavePhysicalWorker. Переданы некорректные входные параметры');
            }                                                                                                        // Проверяем наличие в нем нужных нам полей
            $warnings[] = 'SavePhysicalWorker. Данные с фронта получены';
            $physical_schedule_id = $post_dec->physical_schedule_id; //для таблицы (поле) + из него возьмем company_department

            $physical_schedule = PhysicalSchedule::findOne(['id' => $physical_schedule_id]);
            if (!($physical_schedule)) {                   //проверка, что такого есть
                throw new Exception('SavePhysicalWorker. Не найден такой график.');
            }
            $company_department_id = $physical_schedule['company_department_id'];

            $today = date('Y-m-d', strtotime(BackendAssistant::GetDateNow()));                                   // текущая дата
            /****************** Находит нижележащих людей ******************/
            //находим нижележащие участки
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("SavePhysicalWorker. Не смог получить список вложенных подразделений");
            }
            /****************** Находим сотрудников, которые на этих участках и НЕ уволены ******************/


            $workers = Worker::find()
                ->select('contingent.id as contingent_id, worker.id as worker_id')
                ->innerJoin('contingent', 'contingent.company_department_id = worker.company_department_id and contingent.position_id = worker.position_id')
                ->where(['in', 'company_department_id', $company_departments])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $today],
                    ['is', 'worker.date_end', null]
                ])
                ->asArray()
                ->all();

            foreach ($workers as $worker) {
                $physical_worker[] = [$worker['worker_id'], $worker['contingent_id'], $physical_schedule_id];
            }
            if (!$physical_worker) {
                $warnings[] = 'SavePhysicalWorker. Сотрудники не выбраны';
            }

            /****************** Вставка в бд ******************/
            if (isset($physical_worker)) {
                $result_physical_worker = Yii::$app->db->createCommand()
                    ->batchInsert('physical_worker', ['worker_id', 'contingent_id', 'physical_schedule_id'], $physical_worker)//массовая вставка в БД
                    ->execute();
                if ($result_physical_worker != 0) {
                    $warnings[] = 'SavePhysicalWorker. Успешное сохранение списка сотрудников для запланированного мед.осмотра';
                } else {
                    throw new Exception('SavePhysicalWorker. Ошибка при сохранении списка сотрудников для запланированного мед.осмотра');
                }
            }
            $warnings[] = 'SavePhysicalWorker. Данные сохранены в таблицу';

        } catch (Throwable $exception) {
            $errors[] = 'SavePhysicalWorker. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'SavePhysicalWorker. Конец метода';
        $result = $physical_worker;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetWorker() - метод получения списка персонала по подразделению с учетом списка контингентов
     * //todo вот это мне кажется бред, думаю он будет лишний
     *
     * Еализовано, что узнаем номер профессии и какой участок. И уже чисто в worker_id делаем поиск по этим двум параметрам.
     * После реализации метода про планированный график, думаю можно через него сделать
     * @param null $data_post
     * @return array
     *
     *Выходные параметры: список worker_id ( c учетом контингентов)
     *
     * @package frontend\controllers
     *Входные обязательные параметры: номер подразделения - [company_department_id]
     *                                список контигентов - [contingent_id]
     * JSON:
     *                                 |----company_department_id
     *                                 |----contingents
     *                                           |-----[
     *                                             contingent_id
     *                                                  ]
     * @example
     * http://localhost/read-manager-amicum?controller=PhysicalSchedule&method=GetWorker&subscribe=&data={%22company_department_id%22:801,%22contingent%22:[6,7,9]}
     *
     *
     * @author Митяева Лидия <mla@pfsz.ru>
     * Created date: on 06.09.2019 8:14
     */
    public static function GetWorker($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $worker_list = array();                                                                               // Промежуточный результирующий массив
        $warnings[] = 'GetWorker. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('GetWorker. Данные с фронта не получены');
            }
            $warnings[] = 'GetWorker. Данные успешно переданы';
            $warnings[] = 'GetWorker. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'GetWorker. Декодировал входные параметры';

            if (
                !(property_exists($post_dec, 'company_department_id'))
            ) {
                throw new Exception('GetWorker. Переданы некорректные входные параметры');
            }                                                                                                        // Проверяем наличие в нем нужных нам полей

            $warnings[] = 'GetWorker. Данные с фронта получены';

            $company_department_id = $post_dec->company_department_id;
            $contingents = $post_dec->contingent;


            $positions = Contingent::find()
                ->where(['in', 'id', $contingents])
                ->andWhere(['company_department_id' => $company_department_id])
                ->all();

            if (!($positions)) {
                throw new Exception('GetWorker. По данному подразделению контингент не найден');
            }
            $warnings[] = 'GetWorker. В данном подразделении найдены заданные контингенты';

            $position_id = array();
            foreach ($positions as $position) {
                $position_id[] = (int)$position['position_id'];
            }

            $warnings[] = 'GetWorker. Выбрали Профессии из подходящих контингентов';
            $workers = Worker::find()
                ->where(['in', 'position_id', $position_id])
                ->andWhere(['company_department_id' => $company_department_id])
                ->asArray()
                ->all();
            if (!($workers)) {
                throw new Exception('GetWorker. По контингентам и выбранному участку персонал не найден');
            }
            $warnings[] = 'GetWorker. Выбрали персонал по контингенту и подразделению';
            foreach ($workers as $worker) {
                $worker_list['id'][] = $worker['id'];
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetWorker. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'GetWorker. Конец метода';
        $result = $worker_list;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    //необходимо изметь таблицы и тогда можно реализовывать
    // Входные номер подразделения и год
    // http://localhost/read-manager-amicum?controller=PhysicalSchedule&method=StatikResultMedReport&subscribe=&data={"company_department_id":"801"}
    /**
     * Метод StatikResultMedReport() - считает статистику результатов мед.обследований.
     * @param null $data_post
     * @return array
     *
     *Выходные параметры: четыре значения (в процентах) результатов мед. заключений для какого-то подразделения
     *
     * @package frontend\controllers
     *Входные обязательные параметры: номер подразделения
     * @example
     *  http://localhost/read-manager-amicum?controller=PhysicalSchedule&method=StatikResultMedReport&subscribe=&data={"company_department_id":"801"}
     *
     * @author Митяева Лидия <mla@pfsz.ru>
     * Created date: on 11.09.2019 8:14
     */
    public static function StatikResultMedReport($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $static_results = array();                                                                               // Промежуточный результирующий массив
        $warnings[] = 'StatikResultMedReport. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('StatikResultMedReport. Данные с фронта не получены');
            }
            $warnings[] = 'StatikResultMedReport. Данные успешно переданы';
            $warnings[] = 'StatikResultMedReport. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'StatikResultMedReport. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'company_department_id'))
            ) {
                throw new Exception('StatikResultMedReport. Переданы некорректные входные параметры');
            }                                                                                                        // Проверяем наличие в нем нужных нам полей
            $warnings[] = 'StatikResultMedReport. Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;
            /****************** Выбираем работников по участкам и их результаты обследования ******************/
            $med_result = Worker::find()
                ->select('med_report_result_id')
                ->innerJoin('med_report', 'med_report.worker_id = worker.id')
                ->where(['worker.company_department_id' => $company_department_id])
                ->asArray()
                ->all();
            $warnings[] = 'StatikResultMedReport. Найдены все работники';
            /****************** Подсчитываем результаты обследования в процентах ******************/
            $all_workers = count($med_result);
            $fit = 0;
            $unfit_time = 0;
            $unfit = 0;
            $checkup = 0;

            foreach ($med_result as $result) {
                //Assistant::PrintR($result);
                if ($result['med_report_result_id'] == self::FIT) {
                    $fit = $fit + 1;
                } else if ($result['med_report_result_id'] == self::UNFIT_TIME) {
                    $unfit_time = $unfit_time + 1;
                } else if ($result['med_report_result_id'] == self::UNFIT) {
                    $unfit = $unfit + 1;
                } else if ($result['med_report_result_id'] == self::CHECKUP) {
                    $checkup = $checkup + 1;
                }
            }
            $warnings[] = 'StatikResultMedReport. Результаты подсчитаны в процентах';
            $static_results[] = $fit * 100 / $all_workers;
            $static_results[] = $unfit_time * 100 / $all_workers;
            $static_results[] = $unfit * 100 / $all_workers;
            $static_results[] = $checkup * 100 / $all_workers;

        } catch (Throwable $exception) {
            $errors[] = 'StatikResultMedReport. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'StatikResultMedReport. Конец метода';
        $result = $static_results;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод StaticGender() - метод, который считает количество мужчин и женщин на заданном участке
     * @param null $data_post
     * @return array
     *
     *Выходные параметры:   3 числа - количество мужчин, количество женщин, количество всего людей
     *
     * @package frontend\controllers
     *Входные обязательные параметры: company_department_-ключ участка
     * @example  http://localhost/read-manager-amicum?controller=PhysicalSchedule&method=StaticGender&subscribe=&data={%22company_department_id%22:%2220028766%22}
     *
     * @author Митяева Лидия <mla@pfsz.ru>
     * Created date: on 23.09.2019 13:36
     */
    public static function StaticGender($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $static_gender = array();                                                                               // Промежуточный результирующий массив
        $warnings[] = 'StaticGender. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('StaticGender. Данные с фронта не получены');
            }
            $warnings[] = 'StaticGender. Данные успешно переданы';
            $warnings[] = 'StaticGender. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'StaticGender. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'company_department_id'))
            ) {
                throw new Exception('StaticGender. Переданы некорректные входные параметры');
            }                                                                                                        // Проверяем наличие в нем нужных нам полей

            $warnings[] = 'StaticGender. Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;

            $today = date('Y-m-d', strtotime(BackendAssistant::GetDateNow()));                                   // текущая дата
            /****************** Находит нижележащих людей ******************/
            //находим нижележащие участки
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception("StaticGender. Не смог получить список вложенных подразделений");
            }
            /****************** Находим сотрудников, которые на этих участках и НЕ уволены ******************/

            $workers = Worker::find()
                ->select('worker.id, worker.company_department_id, employee.gender')
                ->innerJoin('employee', 'worker.employee_id = employee.id')
                ->where(['in', 'company_department_id', $company_departments])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $today],
                    ['is', 'worker.date_end', null]
                ])
                ->asArray()
                ->all();
            $warnings[] = 'StaticGender. Список сотрудников и данные найдены';

            $man = 0;
            $woman = 0;
            foreach ($workers as $worker) {
                if ($worker['gender'] == self::WOMEN) {
                    $woman = $woman + 1;
                } else if ($worker['gender'] == self::MEN) {
                    $man = $man + 1;
                }
            }
            $static_gender[] = ['women ' => $woman, 'man   ' => $man, 'all   ' => count($workers)];


        } catch (Throwable $exception) {
            $errors[] = 'StaticGender. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'StaticGender. Конец метода';
        $result = $static_gender;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetNextPhysicalSchedule() - Метод получения списка ближайших дат и для каких подразделений в этот день мед.осмотр
     * @param null $data_post
     * @return array
     *
     *Выходные параметры:      [date_start                        - дата начала мед. осмотрв
     *                               [company_department_id[]     - ключ подразделения для которого в этот день мед. осмотр
     *                          ]
     *
     * @package frontend\controllers
     *Входные обязательные параметры: -
     * @example  http://localhost/read-manager-amicum?controller=PhysicalSchedule&method=GetNextPhysicalSchedule&subscribe=&data={}
     *
     * @author Митяева Лидия <mla@pfsz.ru>
     * Created date: on 17.09.2019 14:40
     */
    public static function GetNextPhysicalSchedule($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $list_next_physical = array();                                                                               // Промежуточный результирующий массив
        $warnings[] = 'GetNextPhysicalSchedule. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('GetNextPhysicalSchedule. Данные с фронта не получены');
            }
            $warnings[] = 'GetNextPhysicalSchedule. Данные успешно переданы';
            $warnings[] = 'GetNextPhysicalSchedule. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'GetNextPhysicalSchedule. Декодировал входные параметры';


            if (
                (property_exists($post_dec, ''))
            ) {
                throw new Exception('GetNextPhysicalSchedule. Переданы некорректные входные параметры');
            }                                                                                                        // Проверяем наличие в нем нужных нам полей
            $warnings[] = 'GetNextPhysicalSchedule. Данные с фронта получены';

            /**
             * Получение текущих: месяца, дня, года
             */
            $today = date('Y-m-d', strtotime(BackendAssistant::GetDateNow()));
            $date_next = date('Y-m-d', strtotime($today . "+ 14 day"));
            $physical_shedules = PhysicalSchedule::find()
                ->where(['>', 'date_start', $today])
                ->andWhere(['<', 'date_start', $date_next])
                ->asArray()
                ->all();
            /****************** Формируется итоговый вывод. Дата и список подразделений на эту дату ******************/
            foreach ($physical_shedules as $physical_shedule) {
                $list_next_physical[$physical_shedule['date_start']][] = $physical_shedule['company_department_id'];

            }
        } catch (Throwable $exception) {
            $errors[] = 'GetNextPhysicalSchedule. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'GetNextPhysicalSchedule. Конец метода';
        $result = $list_next_physical;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetListPhysical() - Метод получения списка графиков, которые были ранее созданы сотрудником
     * @param null $data_post
     * @return array
     *
     *Выходные параметры:  Массив (список) названий графиков и по какому году график
     *                 [id]
     *                  |---- year   - год, по которому создается график
     *                  |---- title  - название графика
     * @package frontend\controllers
     *Входные обязательные параметры:  worker_id - ключ сотружника, который хочет создать график  JSON со структрой:
     * @example http://localhost/read-manager-amicum?controller=PhysicalSchedule&method=GetListPhysical&subscribe=&data={%22worker_id%22:70003553,%20%22physical_kind_id%22:2}
     *
     * @author Митяева Лидия <mla@pfsz.ru>
     * Created date: on 20.09.2019 14:02
     */
    public static function GetListPhysical($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $list_physical = array();                                                                               // Промежуточный результирующий массив
        $warnings[] = 'GetListPhysical. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('GetListPhysical. Данные с фронта не получены');
            }
            $warnings[] = 'GetListPhysical. Данные успешно переданы';
            $warnings[] = 'GetListPhysical. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'GetListPhysical. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'worker_id') ||
                    property_exists($post_dec, 'physical_kind_id'))
            ) {
                throw new Exception('GetListPhysical. Переданы некорректные входные параметры');
            }                                                                                                        // Проверяем наличие в нем нужных нам полей
            $warnings[] = 'GetListPhysical. Данные с фронта получены';

            $worker_id = $post_dec->worker_id;
            $physical_kind_id = $post_dec->physical_kind_id;

            $physicals = Physical::find()
                ->where(['worker_id' => $worker_id, 'physical_kind_id' => $physical_kind_id])
                ->asArray()
                ->all();

            if (!$physicals) {
                $warnings[] = 'GetListPhysical. Сотрудник еще не создавал график';
            }
            foreach ($physicals as $physical) {
                $list_physical[$physical['id']] = ['year' => $physical['year'], 'title' => $physical['title']];
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetListPhysical. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'GetListPhysical. Конец метода';
        $result = $list_physical;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    /**
     * Метод GetMedReportRead() - получает мед. заключение, читает (без редактирования)
     * @param null $data_post
     * @return array
     *
     *Выходные параметры:
     *                                      |----med_report_id          - ключ мед.заключения
     *                                     |----worker_id              - ключ сотрудника, кто сделал данное заключение
     *                                     |----med_report_result_id   - ключ, какое заключение было сделано по итогу
     *                                     |----date_next              - ата след. мед. осмотра
     *                                     |----comment_result         - комментарий к результату заключения
     *                                     |----comment_disease        - комментарий к проф. заболеванию
     *                                     |----med_report_date        - дата выдачи мед. заключения
     *                                     |----physical_date          - дата прохождения мед. осмотра
     *                                     |----attachment_id          - документ
     *                                     |----disease_id             - ключ профюзаболевания
     *
     * @package frontend\controllers
     *Входные обязательные параметры: JSON со структрой:
     * @example  http://localhost/read-manager-amicum?controller=PhysicalSchedule&method=GetMedReportRead&subscribe=&data={"med_report_id":"13"}
     *
     * @author Митяева Лидия <mla@pfsz.ru>
     * Created date: on 24.09.2019 15:48
     */
    public static function GetMedReportRead($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $med_report = array();                                                                               // Промежуточный результирующий массив
        $warnings[] = 'GetMedReportRead. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('GetMedReportRead. Данные с фронта не получены');
            }
            $warnings[] = 'GetMedReportRead. Данные успешно переданы';
            $warnings[] = 'GetMedReportRead. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'GetMedReportRead. Декодировал входные параметры';
            // Assistant::PrintR($post_dec);die;
            if (
                !(property_exists($post_dec, 'med_report_id'))
            ) {
                throw new Exception('GetMedReportRead. Переданы некорректные входные параметры');
            }                                                                                                        // Проверяем наличие в нем нужных нам полей

            $warnings[] = 'GetMedReportRead. Данные с фронта получены';
            $med_report_id = $post_dec->med_report_id;

            $med_report = MedReport::findOne(['id' => $med_report_id]);
            if (!$med_report) {
                throw new Exception('GetMedReportRead. Ошибка при выборе мед.заключенияю Не существует');
            }

        } catch (Throwable $exception) {
            $errors[] = 'GetMedReportRead. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'GetMedReportRead. Конец метода';
        $result = $med_report;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetHandbookDieases() - Справочник профзаболеваний
     * @return array
     *
     * @package frontend\controllers
     *
     * @example http://amicum/read-manager-amicum?controller=PhysicalSchedule&method=GetHandbookDieases&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 22.10.2019 16:26
     */
    public static function GetHandbookDieases()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetDieases. Начало метода';
        try {
            $result = Diseases::find()
                ->select(['id', 'title'])
                ->asArray()
                ->indexBy('id')
                ->all();
        } catch (Throwable $exception) {
            $errors[] = 'GetDieases. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetDieases. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetHandbookMedReportResult() - Справочник заключений медицинской комисии
     * @return array
     *
     * @package frontend\controllers
     *
     * @example http://amicum/read-manager-amicum?controller=PhysicalSchedule&method=GetHandbookMedReportResult&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 22.10.2019 16:29
     */
    public static function GetHandbookMedReportResult()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetHandbookMedReportResult. Начало метода';
        try {
            $result = MedReportResult::find()
                ->select(['id', 'title'])
                ->asArray()
                ->indexBy('id')
                ->all();
        } catch (Throwable $exception) {
            $errors[] = 'GetHandbookMedReportResult. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetHandbookMedReportResult. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetHandbookKindPhysical() - Справочник видов медосмотров
     * @return array - [physical_kind_id]
     *                             id:
     *                             title:
     *
     * @package frontend\controllers
     *
     * @example http://amicum/read-manager-amicum?controller=PhysicalSchedule&method=GetHandbookKindPhysical&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 23.10.2019 8:30
     */
    public static function GetHandbookKindPhysical()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetHandbookKindPhysical. Начало метода';
        try {
            $result = PhysicalKind::find()
                ->select(['id', 'title'])
                ->indexBy('id')
                ->asArray()
                ->all();
        } catch (Throwable $exception) {
            $errors[] = 'GetHandbookKindPhysical. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetHandbookKindPhysical. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetAccountingMedReport() - метод получения данных для учёта медицинских осмотров
     * @param null $data_post - JSON с идентификатором участка
     * @return array - массив со следующей структурой: [company_department_id]
     *                                                              company_department_id:
     *                                                              company_department_title:
     *                                                              physical_schedule_id:
     *                                                              title:
     *                                                              date_start:
     *                                                              date_start_format:
     *                                                              date_end:
     *                                                              date_end_format:
     *                                                              [schedule_workers]
     *                                                                         [worker_id]
     *                                                                                physical_worker_id:
     *                                                                                worker_id:
     *                                                                                full_name:
     *                                                                                stuff_number:
     *                                                                                gender:
     *                                                                                birthdate:
     *                                                                                position:
     *                                                                                date_pass_MO:
     *                                                                                date_pass_MO_format:
     *                                                                                               [med_reports]
     *                                                                                                      [med_report_id]
     *                                                                                                              med_report_id:
     *                                                                                                              med_report_result:
     *                                                                                                              med_report_date:
     *                                                                                                              med_report_date_format:
     *                                                                                                              comment_result:
     *                                                                                                              attachment_path:
     *                                                                                                              attachment_title:
     *                                                                                                              attachment_type:
     *                                                                                                              disease_id:
     *                                                                                                              disease_comment:
     *                                                                                                              disease_title:
     *
     * @package frontend\controllers
     *
     * @example http://amicum/read-manager-amicum?controller=PhysicalSchedule&method=GetAccountingMedReport&subscribe=&data={"company_department_id":20028766}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 24.10.2019 10:14
     */
    public static function GetAccountingMedReport($data_post = NULL)
    {
        $status = 1; // Флаг успешного выполнения метода
        $warnings = array(); // Массив предупреждений
        $errors = array(); // Массив ошибок
        $accounting_med_report = array();    // Промежуточный результирующий массив
        $company_departments = array();
        $get_current_year = date('Y', strtotime(BackendAssistant::GetDateNow()));
        $start_current_year = $get_current_year . '-01-01';
        $end_current_year = $get_current_year . '-12-31';
        $warnings[] = 'GetAccountingMedReport. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('GetAccountingMedReport. Не переданы входные параметры');
            }
            $warnings[] = 'GetAccountingMedReport. Данные успешно переданы';
            $warnings[] = 'GetAccountingMedReport. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post); // Декодируем входной массив данных
            $warnings[] = 'GetAccountingMedReport. Декодировал входные параметры';
            if (!property_exists($post_dec, 'company_department_id')) // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('GetAccountingMedReport. Переданы некорректные входные параметры');
            }
            $warnings[] = 'GetAccountingMedReport. Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            }
            $found_physical_workers = PhysicalWorker::find()
                ->select('physical_worker.id as phys_worker_id,
                                physical_worker.worker_id as worker_id,
                                company_department.id as company_department_id,
                                company.title as company_title,
                                physical.title as physical_title,
                                physical_schedule.id as physical_schedule_id,
                                physical_schedule.date_start as schedule_date_start,
                                physical_schedule.date_end as schedule_date_end,
                                employee.first_name as first_name,
                                employee.last_name as last_name,
                                employee.patronymic as patronymic,
                                worker.tabel_number as tabel_number,
                                employee.gender as gender,
                                employee.birthdate as birthdate,
                                position.title as position_title,
                                physical_worker_date.id as physical_worker_date_id,
                                physical_worker_date.date as physical_worker_date_date,
                                med_report.id as med_report_id,
                                med_report.comment_result as comment_result,
                                med_report_result.title as med_result_title,
                                med_report.med_report_date as med_report_date,
                                attachment.id as attachment_id,
                                attachment.path as attachment_path,
                                attachment.title as attachment_title,
                                attachment.attachment_type as attachment_type,
                                diseases.id as diseases_id,
                                diseases.title as diseases_title,
                                physical_worker_date.id as physical_worker_date_id,
                                med_report_disease.comment_disease as comment_diseases')
                ->leftJoin('physical_schedule', 'physical_worker.physical_schedule_id = physical_schedule.id')
                ->leftJoin('company_department', 'physical_schedule.company_department_id = company_department.id')
                ->leftJoin('company', 'company_department.company_id = company.id')
                ->leftJoin('physical', 'physical_schedule.physical_id = physical.id')
                ->leftJoin('physical_worker_date', 'physical_worker.id = physical_worker_date.physical_worker_id')
                ->leftJoin('med_report', 'physical_worker_date.id = med_report.physical_worker_date_id')
//                ->leftJoin('classifier_diseases', 'classifier_diseases.id = med_report.classifier_diseases_id')
                ->leftJoin('attachment', 'med_report.attachment_id = attachment.id')
                ->leftJoin('med_report_disease', 'med_report.id = med_report_disease.med_report_id')
                ->leftJoin('diseases', 'med_report_disease.disease_id = diseases.id')
                ->leftJoin('med_report_result', 'med_report.med_report_result_id = med_report_result.id')
                ->leftJoin('worker', 'physical_worker.worker_id = worker.id')
                ->leftJoin('employee', 'worker.employee_id = employee.id')
                ->leftJoin('position', 'worker.position_id = position.id')
                ->where(['in', 'physical_schedule.company_department_id', $company_departments])
                ->andWhere(['>=', 'physical_schedule.date_start', $start_current_year])
                ->andWhere(['<=', 'physical_schedule.date_end', $end_current_year])
                ->andWhere(['worker.company_department_id' => $company_department_id])
                ->asArray()
                ->all();
            $count_all_worker = 0;
            $count_worker_with_PMO = 0;
            $count_worker_with_PMO_man = 0;
            $count_worker_with_PMO_woman = 0;
            $count_all_worker_man = 0;
            $count_all_worker_wooman = 0;
            if (!empty($found_physical_workers)) {
                foreach ($found_physical_workers as $physical_worker) {
                    $company_id = $physical_worker['company_department_id'];
                    $phys_shedule_id = $physical_worker['physical_schedule_id'];
                    $worker_id = $physical_worker['worker_id'];
                    $med_report_id = $physical_worker['med_report_id'];
                    $accounting_med_report['companies'][$company_id]['company_department_id'] = $physical_worker['company_department_id'];
                    $accounting_med_report['companies'][$company_id]['company_title'] = $physical_worker['company_title'];
                    $accounting_med_report['companies'][$company_id]['schedule_id'] = $phys_shedule_id;
                    $accounting_med_report['companies'][$company_id]['title'] = $physical_worker['physical_title'];
                    $accounting_med_report['companies'][$company_id]['date_start'] = $physical_worker['schedule_date_start'];
                    $accounting_med_report['companies'][$company_id]['date_start_format'] = date('d.m.Y', strtotime($physical_worker['schedule_date_start']));
                    $accounting_med_report['companies'][$company_id]['date_end'] = $physical_worker['schedule_date_end'];
                    $accounting_med_report['companies'][$company_id]['date_end_format'] = date('d.m.Y', strtotime($physical_worker['schedule_date_end']));
                    $accounting_med_report['companies'][$company_id]['schedule_workers'][$worker_id]['physical_worker_id'] = $physical_worker['phys_worker_id'];
                    $accounting_med_report['companies'][$company_id]['schedule_workers'][$worker_id]['worker_id'] = $physical_worker['worker_id'];
                    $name = mb_substr($physical_worker['first_name'], 0, 1);
                    $patronymic = mb_substr($physical_worker['patronymic'], 0, 1);
                    $accounting_med_report['companies'][$company_id]['schedule_workers'][$worker_id]['full_name'] = "{$physical_worker['last_name']} {$name}. {$patronymic}.";
                    $accounting_med_report['companies'][$company_id]['schedule_workers'][$worker_id]['stuff_number'] = $physical_worker['tabel_number'];
                    $accounting_med_report['companies'][$company_id]['schedule_workers'][$worker_id]['gender'] = $physical_worker['gender'];
                    if ($physical_worker['gender'] == 'М') {
                        $count_all_worker_man++;
                    } else {
                        $count_all_worker_wooman++;
                    }
                    $accounting_med_report['companies'][$company_id]['schedule_workers'][$worker_id]['birthdate'] = $physical_worker['birthdate'];
                    $accounting_med_report['companies'][$company_id]['schedule_workers'][$worker_id]['position'] = $physical_worker['position_title'];
                    $accounting_med_report['companies'][$company_id]['schedule_workers'][$worker_id]['date_pass_MO'] = $physical_worker['physical_worker_date_date'];
                    $accounting_med_report['companies'][$company_id]['schedule_workers'][$worker_id]['physical_worker_date_id'] = $physical_worker['physical_worker_date_id'];
                    if ($physical_worker['physical_worker_date_date'] != null) {
                        if ($physical_worker['gender'] == 'М') {
                            $count_worker_with_PMO_man++;
                        } else {
                            $count_worker_with_PMO_woman++;
                        }
                        $count_worker_with_PMO++;
                    }
                    $accounting_med_report['companies'][$company_id]['schedule_workers'][$worker_id]['date_pass_MO_format'] = date('d.m.Y', strtotime($physical_worker['physical_worker_date_date']));
                    $accounting_med_report['companies'][$company_id]['schedule_workers'][$worker_id]['med_reports'] = array();
                    $accounting_med_report['companies'][$company_id]['schedule_workers'][$worker_id]['med_reports'][$med_report_id]['med_report_id'] = $med_report_id;
                    $accounting_med_report['companies'][$company_id]['schedule_workers'][$worker_id]['med_reports'][$med_report_id]['med_report_result'] = $physical_worker['med_result_title'];
                    $accounting_med_report['companies'][$company_id]['schedule_workers'][$worker_id]['med_reports'][$med_report_id]['med_report_date'] = $physical_worker['med_report_date'];
                    $accounting_med_report['companies'][$company_id]['schedule_workers'][$worker_id]['med_reports'][$med_report_id]['med_report_date_format'] = date('d.m.Y', strtotime($physical_worker['med_report_date']));
                    $accounting_med_report['companies'][$company_id]['schedule_workers'][$worker_id]['med_reports'][$med_report_id]['comment_result'] = $physical_worker['comment_result'];
                    $accounting_med_report['companies'][$company_id]['schedule_workers'][$worker_id]['med_reports'][$med_report_id]['attachment_id'] = $physical_worker['attachment_id'];
                    $accounting_med_report['companies'][$company_id]['schedule_workers'][$worker_id]['med_reports'][$med_report_id]['attachment_path'] = $physical_worker['attachment_path'];
                    $accounting_med_report['companies'][$company_id]['schedule_workers'][$worker_id]['med_reports'][$med_report_id]['attachment_title'] = $physical_worker['attachment_title'];
                    $accounting_med_report['companies'][$company_id]['schedule_workers'][$worker_id]['med_reports'][$med_report_id]['attachment_type'] = $physical_worker['attachment_type'];
                    $accounting_med_report['companies'][$company_id]['schedule_workers'][$worker_id]['med_reports'][$med_report_id]['diseases_id'] = $physical_worker['diseases_id'];
                    $accounting_med_report['companies'][$company_id]['schedule_workers'][$worker_id]['med_reports'][$med_report_id]['diseases_title'] = $physical_worker['diseases_title'];
                    $accounting_med_report['companies'][$company_id]['schedule_workers'][$worker_id]['med_reports'][$med_report_id]['comment_disease'] = $physical_worker['comment_diseases'];
                    if ($med_report_id == null) {
                        $accounting_med_report['companies'][$company_id]['schedule_workers'][$worker_id]['med_reports'] = (object)array();
                    }
                    $count_all_worker++;
                    if (isset($accounting_med_report['companies'][$company_id]['count_people'])) {
                        $accounting_med_report['companies'][$company_id]['count_people']++;
                    } else {
                        $accounting_med_report['companies'][$company_id]['count_people'] = 1;
                    }
                }
            } else {
                $accounting_med_report['companies'] = (object)array();
            }
            $accounting_med_report['statistic']['count_all_worker'] = $count_all_worker;
            $accounting_med_report['statistic']['count_all_worker_man'] = $count_all_worker_man;
            $accounting_med_report['statistic']['count_all_worker_wooman'] = $count_all_worker_wooman;
            $accounting_med_report['statistic']['count_worker_with_PMO'] = $count_worker_with_PMO;
            $accounting_med_report['statistic']['count_worker_with_PMO_man'] = $count_worker_with_PMO_man;
            $accounting_med_report['statistic']['count_worker_with_PMO_wooman'] = $count_worker_with_PMO_woman;
            $med_report_result_statistic = MedReport::find()
                ->select(['count(med_report_result_id) as count_med_report_result',
                    'mrr.title as med_report_result_title',
                    'mrr.id as med_report_result_id',
                    'physical_schedule.company_department_id'])
                ->innerJoin('med_report_result mrr', 'med_report.med_report_result_id = mrr.id')
                ->leftJoin('physical_worker_date', 'med_report.physical_worker_date_id = physical_worker_date.id')
                ->leftJoin('physical_worker pw', 'physical_worker_date.physical_worker_id = pw.id')
                ->leftJoin('physical_schedule', 'pw.physical_schedule_id = physical_schedule.id')
                ->where(['in', 'physical_schedule.company_department_id', $company_departments])
                ->andWhere(['>=', 'physical_schedule.date_start', $start_current_year])
                ->andWhere(['<=', 'physical_schedule.date_end', $end_current_year])
                ->groupBy('med_report_result_title,med_report_result_id,physical_schedule.company_department_id')
                ->asArray()
                ->all();
            foreach ($med_report_result_statistic as $statistic_item) {
                $accounting_med_report['statistic']['med_report_results'][$statistic_item['med_report_result_id']]['med_report_result_id'] = (int)$statistic_item['med_report_result_id'];
                $accounting_med_report['statistic']['med_report_results'][$statistic_item['med_report_result_id']]['med_report_result_title'] = $statistic_item['med_report_result_title'];
                $accounting_med_report['statistic']['med_report_results'][$statistic_item['med_report_result_id']]['count_med_report_result'] = (int)$statistic_item['count_med_report_result'];
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetAccountingMedReport. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetAccountingMedReport. Конец метода';
        $result = $accounting_med_report;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetHandbookHarmolFactors() - Справочник вредных факторов
     * @return array - [harmful_factors_id]
     *                                  id:
     *                                  title:
     *
     * @package frontend\controllers
     *
     * @example http://amicum/read-manager-amicum?controller=PhysicalSchedule&method=GetHandbookHarmolFactors&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 28.10.2019 9:33
     */
    public static function GetHandbookHarmolFactors()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetHandbookHarmolFactors. Начало метода';
        try {
            $result = HarmfulFactors::find()
                ->select(['id', 'title', 'period'])
                ->indexBy('id')
                ->asArray()
                ->all();
        } catch (Throwable $exception) {
            $errors[] = 'GetHandbookHarmolFactors. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetHandbookHarmolFactors. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetHandbookClassifierDiseases() - Справочник "Классификатор заболеваний"
     * @return array - массив со структурой: [classifier_diseases_id]
     *                                                      id:
     *                                                      disease_code:
     *                                                      title:
     *
     * @package frontend\controllers
     *
     * @example http://amicum/read-manager-amicum?controller=PhysicalSchedule&method=GetHandbookClassifierDiseases&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 31.10.2019 16:29
     */
    public static function GetHandbookClassifierDiseases()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetHandbookClassifierDiseases. Начало метода';
        try {
            $classifiers = ClassifierDiseases::find()
                ->innerJoinWith('classifierDiseasesType.classifierDiseasesKind')
                ->all();
            foreach ($classifiers as $classifier) {
                $classifier_kind_id = $classifier->classifierDiseasesType->classifierDiseasesKind->id;
                $classifier_type_id = $classifier->classifierDiseasesType->id;
                $classifier_id = $classifier->id;
                $result['tree'][$classifier_kind_id]['classifier_id'] = $classifier_kind_id;
                $result['tree'][$classifier_kind_id]['classifier_title'] = $classifier->classifierDiseasesType->classifierDiseasesKind->title;
                $result['tree'][$classifier_kind_id]['classifier_types'][$classifier_type_id]['classifier_id'] = $classifier_kind_id;
                $result['tree'][$classifier_kind_id]['classifier_types'][$classifier_type_id]['classifier_title'] = $classifier->classifierDiseasesType->title;
                $result['tree'][$classifier_kind_id]['classifier_types'][$classifier_type_id]['classifier_types'][$classifier_id]['classifier_id'] = $classifier_id;
                $result['tree'][$classifier_kind_id]['classifier_types'][$classifier_type_id]['classifier_types'][$classifier_id]['classifier_disease_code'] = $classifier->disease_code;
                $result['tree'][$classifier_kind_id]['classifier_types'][$classifier_type_id]['classifier_types'][$classifier_id]['classifier_title'] = $classifier->title;

                $result['handbook'][$classifier_id]['classifier_id'] = $classifier_id;
                $result['handbook'][$classifier_id]['classifier_title'] = $classifier->title;


            }
        } catch (Throwable $exception) {
            $errors[] = 'GetClassifierDiseases. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetHandbookClassifierDiseases. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод SavePhysical() - Сохранение графика
     * @param null $data_post - JSON со структурой графика
     * @return array
     *
     * @package frontend\controllers
     *
     * @example http://amicum/read-manager-amicum?controller=PhysicalSchedule&method=SavePhysical&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 06.11.2019 16:06
     */
    public static function SavePhysical($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $post_dec = (object)array();                                                                                              // Массив ошибок
//        $data_post = '{"physical_id":-1,"title":"плановый график на 2020","year":2020}';
        $session = Yii::$app->session;
        $warnings[] = 'SavePhysical. Начало метода';
        try {
            $transaction = Yii::$app->db->beginTransaction();
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('SavePhysical. Не переданы входные параметры');
            }
            $warnings[] = 'SavePhysical. Данные успешно переданы';
            $warnings[] = 'SavePhysical. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'SavePhysical. Декодировал входные параметры';
            if (!property_exists($post_dec, 'physical_id') ||
                !property_exists($post_dec, 'title') ||
                !property_exists($post_dec, 'year'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('SavePhysical. Переданы некорректные входные параметры');
            }
            $warnings[] = 'SavePhysical. Данные с фронта получены';
            $year = $post_dec->year;
            $title = $post_dec->title;
            $physical_id = $post_dec->physical_id;
            $physical = Physical::findOne(['id' => $physical_id]);
            if ($physical == null) {
                $physical = new Physical();
            }
            $physical->title = $title;
            $physical->year = $year;
            $physical->worker_id = $session['worker_id'];
            if ($physical->save()) {
                $warnings[] = 'SavePhysical. Сохранение графика медосмотра прошло успешно';
                $post_dec->physical_id = $physical->id;
            } else {
                $errors[] = $physical->errors;
                throw new Exception('SavePhysical. Произошла ошибка при сохранении графика медосмотра');
            }
            $transaction->commit();
        } catch (Throwable $exception) {
            $errors[] = 'SavePhysical. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
            $transaction->rollBack();
        }
        $warnings[] = 'SavePhysical. Конец метода';

        return array('Items' => $post_dec, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetPhysicalSchedule() - метод получения данных планового графика медосмотров
     * @param null $data_post - JSON с идентификатором графика
     * @return array - массив со структурой: physical_id:
     *                                       title:
     *                                       year:
     *                                       [attachment]
     *                                             id:
     *                                             title:
     *                                             type:
     *                                             date:
     *                                             date_format:
     *                                             src:
     *                                       [physical_schedule]
     *                                              [company_department_id]
     *                                                          company_department_id:
     *                                                          date_start:
     *                                                          date_start_format:
     *                                                          date_end:
     *                                                          date_end_format:
     *                                                          kind_id:
     *                                                          kind_title:
     *                                                          attachment:
     *                                                          [physical_workers]
     *                                                                  [worker_id]
     *                                                                          worker_id:
     *                                                                          [harmol_factors]
     *                                                                                  [factor_title]
     *
     * @package frontend\controllers
     *
     * Входные обязательные параметры:
     * @example http://amicum/read-manager-amicum?controller=PhysicalSchedule&method=GetPhysicalSchedule&subscribe=&data={"physical_id":3}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 11.11.2019 17:48
     */
    public static function GetPhysicalSchedule($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $physical_schedules = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = 'GetPhysicalSchedule. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('GetPhysicalSchedule. Не переданы входные параметры');
            }
            $warnings[] = 'GetPhysicalSchedule. Данные успешно переданы';
            $warnings[] = 'GetPhysicalSchedule. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GetPhysicalSchedule. Декодировал входные параметры';
            if (!property_exists($post_dec, 'physical_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('GetPhysicalSchedule. Переданы некорректные входные параметры');
            }
            $warnings[] = 'GetPhysicalSchedule. Данные с фронта получены';
            $physical_id = $post_dec->physical_id;
            $schedules = Physical::find()
                ->joinWith('physicalAttachment')
                ->joinWith('physicalSchedules.physicalKind')
                ->joinWith('physicalSchedules.physicalScheduleAttachment.attachment')
                ->joinWith('physicalSchedules.physicalWorkers.worker.employee')
                ->joinWith('physicalSchedules.physicalWorkers.worker.position')
                ->joinWith('physicalSchedules.physicalWorkers.worker.workerObjects')
                ->joinWith('physicalSchedules.physicalWorkers.contingent.factorsOfContingents.harmfulFactors')
                ->where(['physical.id' => $physical_id])
                ->all();
            /*
             * Если график не пуст тогда формируем результирующий массив
             */
            if (!empty($schedules)) {
                foreach ($schedules as $schedule) {
                    $physical_schedules['physical_id'] = $physical_id;
                    $physical_schedules['title'] = $schedule->title;
                    $physical_schedules['year'] = $schedule->year;
                    if ($schedule->physicalAttachment != null) {
                        $physical_schedules['attachment']['id'] = $schedule->physicalAttachment->attachment->id;
                        $physical_schedules['attachment']['title'] = $schedule->physicalAttachment->attachment->title;
                        $physical_schedules['attachment']['type'] = $schedule->physicalAttachment->attachment->attachment_type;
                        $physical_schedules['attachment']['date'] = $schedule->physicalAttachment->date;
                        $physical_schedules['attachment']['date_format'] = date('d.m.Y', strtotime($schedule->physicalAttachment->date));
                        $physical_schedules['attachment']['src'] = $schedule->physicalAttachment->attachment->path;
                    } else {
                        $physical_schedules['attachment'] = (object)array();
                    }
                    /******************** ПЕРЕБОР ГРАФИКОВ ********************/
                    $physical_schedules['physical_schedule'] = array();
                    $counter_schedules = 0;
                    foreach ($schedule->physicalSchedules as $physicalSchedule) {
                        $physical_schedule_comp_dep_id = $physicalSchedule->company_department_id;
                        $physical_schedules['physical_schedule'][$counter_schedules]['company_department_id'] = $physical_schedule_comp_dep_id;
                        $physical_schedules['physical_schedule'][$counter_schedules]['date_start'] = date('d.m.Y', strtotime($physicalSchedule->date_start));
                        $physical_schedules['physical_schedule'][$counter_schedules]['date_end'] = date('d.m.Y', strtotime($physicalSchedule->date_end));
                        $physical_schedules['physical_schedule'][$counter_schedules]['day_start'] = $physicalSchedule->day_start;
                        $physical_schedules['physical_schedule'][$counter_schedules]['day_end'] = $physicalSchedule->day_end;
                        $physical_schedules['physical_schedule'][$counter_schedules]['kind_id'] = $physicalSchedule->physicalKind->id;
                        $physical_schedules['physical_schedule'][$counter_schedules]['kind_title'] = $physicalSchedule->physicalKind->title;
                        if ($physicalSchedule->physicalScheduleAttachment != null) {
                            $physical_schedules['physical_schedule'][$counter_schedules]['attachment']['id'] = $physicalSchedule->physicalScheduleAttachment->attachment->id;
                            $physical_schedules['physical_schedule'][$counter_schedules]['attachment']['title'] = $physicalSchedule->physicalScheduleAttachment->attachment->title;
                            $physical_schedules['physical_schedule'][$counter_schedules]['attachment']['type'] = $physicalSchedule->physicalScheduleAttachment->attachment->attachment_type;
                            $physical_schedules['physical_schedule'][$counter_schedules]['attachment']['src'] = $physicalSchedule->physicalScheduleAttachment->attachment->path;
                        } else {
                            $physical_schedules['physical_schedule'][$counter_schedules]['attachment'] = (object)array();
                        }
                        /******************** ПЕРЕБОР РАБОТНИКОВ В ГРАФИКЕ ********************/
                        $physical_schedules['physical_schedule'][$counter_schedules]['physical_workers'] = array();
                        foreach ($physicalSchedule->physicalWorkers as $physicalWorker) {
                            $worker_id = $physicalWorker->worker_id;
                            $physical_schedules['physical_schedule'][$counter_schedules]['physical_workers'][$worker_id]['worker_id'] = $worker_id;
                            if (!empty($physicalWorker->worker->workerObjects)) {
                                $role_id = $physicalWorker->worker->workerObjects[0]->role_id;
                            } else {
                                $role_id = '-';
                            }
                            $physical_schedules['physical_schedule'][$counter_schedules]['physical_workers'][$worker_id]['role_id'] = $role_id;
                            $physical_schedules['physical_schedule'][$counter_schedules]['physical_workers'][$worker_id]['harmol_factors'] = array();
                            /******************** ПЕРЕБОР ВРЕДНЫХ ФАКТОРОВ ********************/
                            foreach ($physicalWorker->contingent->factorsOfContingents as $factorsOfContingent) {
                                $physical_schedules['physical_schedule'][$counter_schedules]['physical_workers'][$worker_id]['harmol_factors'][] = $factorsOfContingent->harmfulFactors->title;
                            }
                        }
                        if (empty($physical_schedules['physical_schedule'][$counter_schedules]['physical_workers'])) {
                            $physical_schedules['physical_schedule'][$counter_schedules]['physical_workers'] = (object)array();
                        }
                        $counter_schedules++;
                    }
                    if (empty($physical_schedules['physical_schedule'])) {
                        $physical_schedules['physical_schedule'] = array();
                    }
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetPhysicalSchedule. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetPhysicalSchedule. Конец метода';
        $result = $physical_schedules;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetPhysicalSchedules() - метод получения данных всех плановых графиков медосмотров
     * @return array -  структура описана в методе GetPhysicalSchedule
     *
     * @package frontend\controllers
     *
     * @example http://amicum/read-manager-amicum?controller=PhysicalSchedule&method=GetPhysicalSchedules&subscribe=&data=
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 12.11.2019 14:48
     */
    public static function GetPhysicalSchedules()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $physical_schedules = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = 'GetPhysicalSchedule. Начало метода';
        try {
            $warnings[] = 'GetPhysicalSchedule. Данные успешно переданы';
//            $warnings[] = 'GetPhysicalSchedule. Входной массив данных' . $data_post;
//            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GetPhysicalSchedule. Декодировал входные параметры';
            $schedules = Physical::find()
                ->joinWith('physicalAttachments')
                ->joinWith('documentPhysicals.documentEventPb')
                ->joinWith('documentPhysicals.documentEventPb.documentEventPbAttachments')
                ->joinWith('documentPhysicals.documentEventPb.vidDocument')
                ->joinWith('physicalSchedules.physicalKind')
                ->joinWith('physicalSchedules.companyDepartment.company')
                ->joinWith('physicalSchedules.physicalScheduleAttachment.attachment')
                ->joinWith('physicalSchedules.physicalWorkers.worker.employee')
                ->joinWith('physicalSchedules.physicalWorkers.worker.position')
                ->joinWith('physicalSchedules.physicalWorkers.worker.workerObjects')
                ->joinWith('physicalSchedules.physicalWorkers.contingent.factorsOfContingents.harmfulFactors')
                ->all();
            /*
             * Если график не пуст тогда формируем результирующий массив
             */
            if (!empty($schedules)) {
                foreach ($schedules as $schedule) {
                    $physical_id = $schedule->id;
                    $physical_schedules[$physical_id]['physical_id'] = $physical_id;
                    $physical_schedules[$physical_id]['title'] = $schedule->title;
                    $physical_schedules[$physical_id]['year'] = $schedule->year;
                    $physical_schedules[$physical_id]['physical_attachments'] = array();
                    $physical_schedules[$physical_id]['physical_documents'] = array();
                    foreach ($schedule->physicalAttachments as $physicalAttachment) {
                        $phys_attachment_id = $physicalAttachment->id;
                        $comp_dep_attachment_id = $physicalAttachment->company_department_id;
                        $physical_schedules[$physical_id]['physical_attachments'][$comp_dep_attachment_id]['physical_attachment_id'] = $phys_attachment_id;
                        $physical_schedules[$physical_id]['physical_attachments'][$comp_dep_attachment_id]['company_department_id'] = $comp_dep_attachment_id;
                        $physical_schedules[$physical_id]['physical_attachments'][$comp_dep_attachment_id]['attachment_physical_document_id'] = $physical_id;
                        $physical_schedules[$physical_id]['physical_attachments'][$comp_dep_attachment_id]['attachment_id'] = $physicalAttachment->attachment_id;
                        $physical_schedules[$physical_id]['physical_attachments'][$comp_dep_attachment_id]['attachment_filename'] = $physicalAttachment->attachment->title;
                        $physical_schedules[$physical_id]['physical_attachments'][$comp_dep_attachment_id]['attachment_type'] = $physicalAttachment->attachment->attachment_type;
                        $physical_schedules[$physical_id]['physical_attachments'][$comp_dep_attachment_id]['attachment_path'] = $physicalAttachment->attachment->path;
                        $physical_schedules[$physical_id]['physical_attachments'][$comp_dep_attachment_id]['attachment_date'] = date('d.m.Y', strtotime($physicalAttachment->date));
                        $physical_schedules[$physical_id]['physical_attachments'][$comp_dep_attachment_id]['attachment_title'] = $physicalAttachment->title;
                    }
                    if (isset($physical_schedules[$physical_id]['physical_attachments']) && empty($physical_schedules[$physical_id]['physical_attachments'])) {
                        $physical_schedules[$physical_id]['physical_attachments'] = (object)array();
                    }
                    /******************** ПЕРЕБОР ГРАФИКОВ ********************/
                    $physical_schedules[$physical_id]['physical_schedule'] = array();
                    $counter_schedules = 0;
                    foreach ($schedule->physicalSchedules as $physicalSchedule) {
                        $physical_schedule_comp_dep_id = $physicalSchedule->company_department_id;
                        $physical_schedules[$physical_id]['physical_schedule'][$counter_schedules]['plan_id'] = $physicalSchedule->id;
                        $physical_schedules[$physical_id]['physical_schedule'][$counter_schedules]['physical_schedule_id'] = $physicalSchedule->id;
                        $physical_schedules[$physical_id]['physical_schedule'][$counter_schedules]['company_department_id'] = $physical_schedule_comp_dep_id;
                        $physical_schedules[$physical_id]['physical_schedule'][$counter_schedules]['company_department_title'] = $physicalSchedule->companyDepartment->company->title;
                        $physical_schedules[$physical_id]['physical_schedule'][$counter_schedules]['date_start'] = date('d.m.Y', strtotime($physicalSchedule->date_start));
                        $physical_schedules[$physical_id]['physical_schedule'][$counter_schedules]['date_end'] = date('d.m.Y', strtotime($physicalSchedule->date_end));
                        $physical_schedules[$physical_id]['physical_schedule'][$counter_schedules]['day_start'] = $physicalSchedule->day_start;
                        $physical_schedules[$physical_id]['physical_schedule'][$counter_schedules]['day_end'] = $physicalSchedule->day_end;
                        $physical_schedules[$physical_id]['physical_schedule'][$counter_schedules]['kind_id'] = $physicalSchedule->physicalKind->id;
                        $physical_schedules[$physical_id]['physical_schedule'][$counter_schedules]['kind_title'] = $physicalSchedule->physicalKind->title;
                        if ($physicalSchedule->physicalScheduleAttachment != null) {
                            $physical_schedules[$physical_id]['physical_schedule'][$counter_schedules]['attachment']['id'] = $physicalSchedule->physicalScheduleAttachment->attachment->id;
                            $physical_schedules[$physical_id]['physical_schedule'][$counter_schedules]['attachment']['title'] = $physicalSchedule->physicalScheduleAttachment->attachment->title;
                            $physical_schedules[$physical_id]['physical_schedule'][$counter_schedules]['attachment']['type'] = $physicalSchedule->physicalScheduleAttachment->attachment->attachment_type;
                            $physical_schedules[$physical_id]['physical_schedule'][$counter_schedules]['attachment']['src'] = $physicalSchedule->physicalScheduleAttachment->attachment->path;
                        } else {
                            $physical_schedules[$physical_id]['physical_schedule'][$counter_schedules]['attachment'] = (object)array();
                        }
                        /******************** ПЕРЕБОР РАБОТНИКОВ В ГРАФИКЕ ********************/
                        $physical_schedules[$physical_id]['physical_schedule'][$counter_schedules]['physical_workers'] = array();
                        $counter_workers = 0;
                        foreach ($physicalSchedule->physicalWorkers as $physicalWorker) {
                            $worker_id = $physicalWorker->worker_id;
                            $physical_schedules[$physical_id]['physical_schedule'][$counter_schedules]['physical_workers'][$counter_workers]['worker_id'] = $worker_id;
                            if (!empty($physicalWorker->worker->workerObjects)) {
                                $role_id = $physicalWorker->worker->workerObjects[0]->role_id;
                            } else {
                                $role_id = '-';
                            }
                            $physical_schedules[$physical_id]['physical_schedule'][$counter_schedules]['physical_workers'][$counter_workers]['role_id'] = $role_id;
                            $physical_schedules[$physical_id]['physical_schedule'][$counter_schedules]['physical_workers'][$counter_workers]['harmol_factors'] = array();
                            /******************** ПЕРЕБОР ВРЕДНЫХ ФАКТОРОВ ********************/
                            foreach ($physicalWorker->contingent->factorsOfContingents as $factorsOfContingent) {
                                $physical_schedules[$physical_id]['physical_schedule'][$counter_schedules]['physical_workers'][$counter_workers]['harmol_factors'][] = $factorsOfContingent->harmfulFactors->title;
                            }
                            $counter_workers++;
                        }
                        if (empty($physical_schedules[$physical_id]['physical_schedule'][$counter_schedules]['physical_workers'])) {
                            $physical_schedules[$physical_id]['physical_schedule'][$counter_schedules]['physical_workers'] = array();
                        }
                        $counter_schedules++;
                    }
                    if (empty($physical_schedules[$physical_id]['physical_schedule'])) {
                        $physical_schedules[$physical_id]['physical_schedule'] = array();
                    }
                    /******************** ПЕРЕБОР ДОКУМЕНТОВ ********************/
                    $physical_schedules[$physical_id]['physical_documents'] = array();
                    foreach ($schedule->documentPhysicals as $documentPhysical) {
                        $comp_dep_id = $documentPhysical->company_department_id;
                        $physical_schedules[$physical_id]['physical_documents'][$comp_dep_id]['company_department_id'] = $comp_dep_id;
                        $physical_schedules[$physical_id]['physical_documents'][$comp_dep_id]['company_title'] = $documentPhysical->companyDepartment->company->title;
                        $physical_schedules[$physical_id]['physical_documents'][$comp_dep_id]['document_event_pb_id'] = $documentPhysical->document_event_pb_id;
                        $physical_schedules[$physical_id]['physical_documents'][$comp_dep_id]['physical_document_id'] = $documentPhysical->id;
                        $physical_schedules[$physical_id]['physical_documents'][$comp_dep_id]['title'] = $documentPhysical->documentEventPb->title;
//                        $physical_schedules[$physical_id]['physical_documents'][$comp_dep_id]['vid_document'] = $documentPhysical->vidDocument->title;
                        $physical_schedules[$physical_id]['physical_documents'][$comp_dep_id]['vid_document_id'] = $documentPhysical->documentEventPb->vid_document_id;
                        $physical_schedules[$physical_id]['physical_documents'][$comp_dep_id]['date_start'] = $documentPhysical->documentEventPb->date_start;
                        $physical_schedules[$physical_id]['physical_documents'][$comp_dep_id]['date_start_format'] = date('d.m.Y', strtotime($documentPhysical->documentEventPb->date_start));
                        $physical_schedules[$physical_id]['physical_documents'][$comp_dep_id]['date_end'] = $documentPhysical->documentEventPb->date_end;
                        $physical_schedules[$physical_id]['physical_documents'][$comp_dep_id]['date_end_format'] = date('d.m.Y', strtotime($documentPhysical->documentEventPb->date_end));
                        $physical_schedules[$physical_id]['physical_documents'][$comp_dep_id]['worker_id'] = $documentPhysical->documentEventPb->worker_id;
                        $physical_schedules[$physical_id]['physical_documents'][$comp_dep_id]['jsonDoc'] = $documentPhysical->documentEventPb->jsondoc;
                        $physical_schedules[$physical_id]['physical_documents'][$comp_dep_id]['attachments'] = array();
                        foreach ($documentPhysical->documentEventPb->documentEventPbAttachments as $documentPhysicalAttachment) {
                            $physical_schedules[$physical_id]['physical_documents'][$comp_dep_id]['attachments']['document_physical_document_id'] = $documentPhysicalAttachment->id;
                            $physical_schedules[$physical_id]['physical_documents'][$comp_dep_id]['attachments']['id'] = $documentPhysicalAttachment->attachment_id;
                            $physical_schedules[$physical_id]['physical_documents'][$comp_dep_id]['attachments']['title'] = $documentPhysicalAttachment->attachment->title;
                            $physical_schedules[$physical_id]['physical_documents'][$comp_dep_id]['attachments']['type'] = $documentPhysicalAttachment->attachment->attachment_type;
                            $physical_schedules[$physical_id]['physical_documents'][$comp_dep_id]['attachments']['src'] = $documentPhysicalAttachment->attachment->path;
                            $physical_schedules[$physical_id]['physical_documents'][$comp_dep_id]['attachments']['status'] = null;
                        }
                        if (empty($physical_schedules[$physical_id]['physical_documents'][$comp_dep_id]['attachments'])) {
                            $physical_schedules[$physical_id]['physical_documents'][$comp_dep_id]['attachments'] = (object)array();
                        }
                    }
                    if (empty($physical_schedules[$physical_id]['physical_documents'])) {
                        $physical_schedules[$physical_id]['physical_documents'] = (object)array();
                    }
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetPhysicalSchedule. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetPhysicalSchedule. Конец метода';
        $result = $physical_schedules;

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetFactorOfContingent() - Справочник вредных факоторов по участку по роли
     * @return array - массив со структурой: [company_department_id]
     *                                                     company_department_id:
     *                                                     company_title:
     *                                                     [roles]
     *                                                          role_id:
     *                                                          role_title:
     *                                                          [harmful_factors]
     *                                                                      [factors_of_contingent_id]
     *                                                                                      factors_of_contingent_id:
     *                                                                                      factors_of_contingent_title:
     *
     * @package frontend\controllers
     *
     * @example http://amicum/read-manager-amicum?controller=PhysicalSchedule&method=GetFactorOfContingent&subscribe=&data=
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 12.11.2019 14:57
     */
    public static function GetFactorOfContingent()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetFactorOfContingent. Начало метода';
        try {
            $factors_contingents = Contingent::find()
                ->joinWith('companyDepartment.company')
                ->joinWith('role')
                ->joinWith('factorsOfContingents.harmfulFactors')
                ->all();
            if (isset($factors_contingents)) {
                /******************** ПЕРЕБОР КОНТИНГЕНТА ********************/
                foreach ($factors_contingents as $factors_contingent) {
                    $comp_dep_id = $factors_contingent->company_department_id;
                    $role_id = $factors_contingent->role_id;
                    $result[$comp_dep_id]['company_department_id'] = $comp_dep_id;
                    $result[$comp_dep_id]['company_title'] = $factors_contingent->companyDepartment->company->title;
                    $result[$comp_dep_id]['roles'][$role_id]['role_id'] = $factors_contingent->role_id;
                    $result[$comp_dep_id]['roles'][$role_id]['role_title'] = $factors_contingent->role->title;
                    $result[$comp_dep_id]['roles'][$role_id]['harmful_factors'] = array();
                    /******************** ПЕРЕБОР ВРЕДНЫХ ФАКТОРОВ НА КОНТИНГЕНТЕ ********************/
                    foreach ($factors_contingent->factorsOfContingents as $factorsOfContingent) {
                        $factors_of_contingent_id = $factorsOfContingent->id;
                        $result[$comp_dep_id]['roles'][$role_id]['harmful_factors'][$factors_of_contingent_id]['factors_of_contingent_id'] = $factors_of_contingent_id;
                        $result[$comp_dep_id]['roles'][$role_id]['harmful_factors'][$factors_of_contingent_id]['factors_of_contingent_title'] = $factorsOfContingent->harmfulFactors->title;
                    }
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetFactorOfContingent. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetFactorOfContingent. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод SavePhysicalSchedule() - Сохранение планового графика МО на участки
     * @param null $data_post - JSON с идентификатором графика МО, участков и людей на которых сохрянаятеся график
     * @return array
     *
     * @package frontend\controllers
     *
     * @example http://amicum/read-manager-amicum?controller=PhysicalSchedule&method=SavePhysicalSchedule&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 12.11.2019 17:29
     */
    public static function SavePhysicalSchedule($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                              // Массив ошибок
        $inserted_phys_worker = array();
        $contingent_factors = array();
        $harmful_factors = array();
        $session = Yii::$app->session;
//        $data_post = '{"physical_id":7,"title":"тестовый график","year":2019,"physical_schedule":{"20028766":{"company_department_id":20028766,"date_start":"2019-11-11","date_end":"2019-11-16","kind_id":1,"attachment":{"id":null,"title":null,"type":null,"date":null,"src":null},"physical_workers":{}}}}';
        $warnings[] = 'SavePhysicalSchedule. Начало метода';
        try {
//            $transaction = Yii::$app->db->beginTransaction();
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('SavePhysicalSchedule. Не переданы входные параметры');
            }
            $warnings[] = 'SavePhysicalSchedule. Данные успешно переданы';
            $warnings[] = 'SavePhysicalSchedule. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'SavePhysicalSchedule. Декодировал входные параметры';
            if (!property_exists($post_dec, 'physical_id') ||
                !property_exists($post_dec, 'physical_schedule') ||
                !property_exists($post_dec, 'physical_attachments') ||
                !property_exists($post_dec, 'physical_documents') ||
                !property_exists($post_dec, 'title') ||
                !property_exists($post_dec, 'status') ||
                !property_exists($post_dec, 'year'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('SavePhysicalSchedule. Переданы некорректные входные параметры');
            }
            $warnings[] = 'SavePhysicalSchedule. Данные с фронта получены';
            $phys_id = $post_dec->physical_id;
            $phys_year = $post_dec->year;
            $physical_schedules = $post_dec->physical_schedule;
            $contingents = Contingent::find()
                ->joinWith('factorsOfContingents.harmfulFactors')
                ->where(['year_contingent' => $phys_year])
                ->all();
            foreach ($contingents as $contingent) {
                $cont_comp_dep[$contingent->company_department_id][$contingent->role_id] = $contingent->id;
                $contingent_factors[$contingent->id] = array();
                foreach ($contingent->factorsOfContingents as $factorsOfContingent) {
                    $contingent_factors[$contingent->id][] = $factorsOfContingent->harmfulFactors->title;
                }
            }

            PhysicalSchedule::DeleteAll(['physical_id' => $phys_id]);
            foreach ($physical_schedules as $key => $physical_schedule) {

                $add_phys_schedule = PhysicalSchedule::findOne(['id' => $physical_schedule->physical_schedule_id]);
                if (empty($add_phys_schedule)) {
                    $add_phys_schedule = new PhysicalSchedule();
                }

                $add_phys_schedule->physical_id = $phys_id;
                $add_phys_schedule->company_department_id = $physical_schedule->company_department_id;
                $add_phys_schedule->date_start = date('Y-m-d', strtotime($physical_schedule->date_start));
                $add_phys_schedule->date_end = date('Y-m-d', strtotime($physical_schedule->date_end));
                $add_phys_schedule->day_start = $physical_schedule->day_start;
                $add_phys_schedule->day_end = $physical_schedule->day_end;
                $add_phys_schedule->physical_kind_id = $physical_schedule->kind_id;

                if ($add_phys_schedule->save()) {
                    $warnings[] = 'SavePhysicalSchedule. График успешно сохранён';
                    $physical_schedule_id = $add_phys_schedule->id;
                    $post_dec->{"physical_schedule"}[$key]->{"physical_schedule_id"} = $physical_schedule_id;
                    $post_dec->{"physical_schedule"}[$key]->{"plan_id"} = $physical_schedule_id;
                } else {
                    $errors[] = $add_phys_schedule->errors;
                    throw new Exception('SavePhysicalSchedule. Ошибка при сохранении графика медосмотров');
                }
                unset($add_phys_schedule);

                if (isset($physical_schedule->attachment->src) and $physical_schedule->attachment->src != null) {
                    $normalize_path = Assistant::UploadFile($physical_schedule->attachment->src, $physical_schedule->attachment->title, 'attachment', $physical_schedule->attachment->type);
                    $add_attachment = new Attachment();
                    $add_attachment->date = BackendAssistant::GetDateFormatYMD();
                    $add_attachment->worker_id = $session['worker_id'];
                    $add_attachment->section_title = 'ОТ и ПБ/Медосмотры';
                    $add_attachment->title = $physical_schedule->attachment->title;
                    $add_attachment->attachment_type = $physical_schedule->attachment->type;
                    $add_attachment->path = $normalize_path;
                    if ($add_attachment->save()) {
                        $warnings[] = 'SavePhysicalSchedule. Вложение успешно сохранено';
                        $add_attachment->refresh();
                        $add_phys_schedule_attachment = new PhysicalScheduleAttachment();
                        $add_phys_schedule_attachment->physical_schedule_id = $physical_schedule_id;
                        $add_phys_schedule_attachment->attachment_id = $add_attachment->id;
                        if ($add_phys_schedule_attachment->save()) {
                            $warnings[] = 'SavePhysicalSchedule. Вложение для графика успешно сохранено';
                        } else {
                            $errors[] = $add_phys_schedule_attachment->errors;
                            throw new Exception('SavePhysicalSchedule. Ошибка при сохранении связки вложения и графика');
                        }
                    } else {
                        $errors[] = $add_attachment->errors;
                        throw new Exception('SavePhysicalSchedule. Ошибка при сохранении вложения');
                    }
                    unset($add_attachment);
                }
                if (!empty($physical_schedule->physical_workers)) {
                    PhysicalWorker::deleteAll(['physical_schedule_id' => $physical_schedule_id]);
                    foreach ($physical_schedule->physical_workers as $key_worker => $physical_worker) {
                        if (!isset($physical_worker->worker_role_id) || empty($physical_worker->worker_role_id)) {
                            $role_id = 9;
                        } else {
                            $role_id = $physical_worker->worker_role_id;
                        }
                        if (isset($cont_comp_dep[$physical_schedule->company_department_id][$role_id])) {
                            $contingent_id = $cont_comp_dep[$physical_schedule->company_department_id][$role_id];
                            $harmful_factors = $contingent_factors[$contingent_id];
                            $inserted_phys_worker[] = [$physical_worker->worker_id, $contingent_id, $physical_schedule_id];
                        } else {
                            $add_contingent = Contingent::findOne(['company_department_id' => $physical_schedule->company_department_id, 'role_id' => $role_id]);
                            if (empty($add_contingent)) {
                                $add_contingent = new Contingent();
                                $add_contingent->company_department_id = $physical_schedule->company_department_id;
                                $add_contingent->role_id = $role_id;
                                $add_contingent->year_contingent = $phys_year;
                                $add_contingent->period = 12;
                                $add_contingent->status = 1;
                                if ($add_contingent->save()) {
                                    $warnings[] = 'SavePhysicalSchedule. Контингент успешно сохранён';
                                } else {
                                    $errors[] = $add_contingent->errors;
                                    throw new Exception('SavePhysicalSchedule. Ошибка при сохранении контингента. Участок: ' . $physical_schedule->company_department_id . '; Роль: ' . $role_id);
                                }
                            }
                            $contingent_id = $add_contingent->id;
                            unset($add_contingent);
                            $add_factor_of_contingent = FactorsOfContingent::findOne(['contingent_id' => $contingent_id, 'harmful_factors_id' => 1]);
                            if (empty($add_factor_of_contingent)) {
                                $add_factor_of_contingent = new FactorsOfContingent();
                                $add_factor_of_contingent->contingent_id = $contingent_id;
                                $add_factor_of_contingent->harmful_factors_id = 1;
                                if ($add_factor_of_contingent->save()) {
                                    $warnings[] = 'SavePhysicalSchedule. Связка конитнгента и вреного фактора прошла успешно';
                                } else {
                                    $errors[] = $add_factor_of_contingent->errors;
                                    throw new Exception('SavePhysicalSchedule. Ошибка при сохранении Связка конитнгента и вреного фактора');
                                }
                            }
                            unset($add_factor_of_contingent);
                            $factor = HarmfulFactors::findOne(['id' => 1])->title;
                            if (!in_array($factor, $harmful_factors)) {
                                $harmful_factors[] = $factor;
                            }
                            unset($factor);
//                            $harmful_factors[] = HarmfulFactors::findOne(['id' => 1])->title;
                            $inserted_phys_worker[] = [$physical_worker->worker_id, $contingent_id, $physical_schedule_id];
//                            throw new Exception('SavePhysicalSchedule. Не существует контингента. Участок: ' . $physical_schedule->company_department_id . '; Роль: ' . $role_id);
                        }
                        $physical_schedules[$key]->physical_workers[$key_worker]->harmol_factors = $harmful_factors;
                    }
                }
            }

            if (!empty($inserted_phys_worker)) {
                $result_inserted_worker = Yii::$app->db
                    ->createCommand()
                    ->batchInsert('physical_worker', [
                        'worker_id',
                        'contingent_id',
                        'physical_schedule_id'
                    ], $inserted_phys_worker)
                    ->execute();
                if ($result_inserted_worker != 0) {
                    $warnings[] = 'SavePhysicalSchedule. Работники в графике МО успешно сохранены';
                } else {
                    throw new Exception('SavePhysicalSchedule. Ошибка при сохранении работников в графике МО');
                }
            }
            unset($inserted_phys_worker);
            $result = array(
                'physical_schedule' => $physical_schedules,
                'physical_id' => $phys_id,
                'title' => $post_dec->title,
                'year' => $post_dec->year,
                'status' => $post_dec->status,
                'physical_documents' => $post_dec->physical_documents,
                'physical_attachments' => $post_dec->physical_attachments
            );

//            $transaction->commit();
        } catch (Throwable $exception) {
//            $transaction->rollBack();
            $errors[] = 'SavePhysicalSchedule. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'SavePhysicalSchedule. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод DeletePhysical() - Метод удаления графика МО
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers
     *
     * @example http://amicum/read-manager-amicum?controller=PhysicalSchedule&method=DeletePhysical&subscribe=&data={"physical_id":10}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 22.11.2019 8:30
     */
    public static function DeletePhysical($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = 'DeletePhysical. Начало метода';
        try {
            $transaction = Yii::$app->db->beginTransaction();
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('DeletePhysical. Не переданы входные параметры');
            }
            $warnings[] = 'DeletePhysical. Данные успешно переданы';
            $warnings[] = 'DeletePhysical. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'DeletePhysical. Декодировал входные параметры';
            if (!property_exists($post_dec, 'physical_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('DeletePhysical. Переданы некорректные входные параметры');
            }
            $warnings[] = 'DeletePhysical. Данные с фронта получены';
            $physical_id = $post_dec->physical_id;
            Physical::deleteAll(['id' => $physical_id]);
            $transaction->commit();
        } catch (Throwable $exception) {
            $transaction->rollBack();
            $errors[] = 'DeletePhysical. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'DeletePhysical. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GraficPlannedMO() - Метод получения каленадря медосмотров
     * @param null $data_post - JSON с идентификатором учаска и год
     * входные параметры:
     * company_department_id    - ключ подразделения
     * year                     - год
     * @return array - массив со следующей структурой данных
     * {month}              - месяц
     *      month:                  - месяц
     *      days:                   - список дней
     *         {day}                        - день
     *              day:                            - день
     *              workers:                        - список работников
     *                  {worker_id}                     - ключ работника
     *                      worker_id                       - ключ работника
     *                      worker_full_name                - полное Имя работника
     *                      tabel_number                    - табельный номер работника
     *                      company_department_id           - ключ подразделенеия работника
     *                      company_title                   - наименование подразделения работника
     *                      position_id                     - ключ должности работника
     *                      position_title                  - наименование должности работника
     *                      role_id                         - ключ роли работника
     *                      role_title                      - роль работника
     *                      date_start                      - планова дата начала МО
     *                      date_end                        - плановая дата окончания МО
     *                      med_report_result_id            - ключ заключения МО
     *                      med_report_result_title         - наименование заключения МО
     *                      physical_schedule_id            - ключ графика в котором находтся хапланированны МО
     *                      physical_worker_id              - ключ работника для быстрого нахождения его контингента (может потребоваться в будующем)
     *                      contingent_id                   - ключ контингента работника
     * @package frontend\controllers
     * алгоритм:
     *      1. получаю список вложенных департаметов
     *      2. получаю справочник заключений МО
     *      3. строю пустой объект - месяц - день
     *      4. получаю графики МО по искомым подразедлениям и всю инфу что нужна за раз
     *      5. перестариваю графики как группу график - человек
     *      6. заполняю дни людьми
     * @example http://http://127.0.0.1/read-manager-amicum?controller=PhysicalSchedule&method=GraficPlannedMO&subscribe=&data={"company_department_id":4029720,"year":2019}
     *
     * @author Якимов М.Н.
     * Created date: on 22.11.2019 11:34
     */
    public static function GraficPlannedMO($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'GraficPlannedMO';                                                                             // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                              // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                       // время начала выполнения метода
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
                throw new Exception('GraficPlannedMO. Не переданы входные параметры');
            }
            $warnings[] = 'GraficPlannedMO. Данные успешно переданы';
            $warnings[] = 'GraficPlannedMO. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GraficPlannedMO. Декодировал входные параметры';
            if (!property_exists($post_dec, 'year') ||
                !property_exists($post_dec, 'company_department_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('GraficPlannedMO. Переданы некорректные входные параметры');
            }
            $warnings[] = 'GraficPlannedMO. Данные с фронта получены';

            $year = (int)$post_dec->year;

            $company_department_id = $post_dec->company_department_id;
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $comp_departments = $response['Items'];
                $warnings[] = $response['warnings'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            }
            $planned_mo = array();
            for ($i = 1; $i <= 12; $i++) {
                $planned_mo[$i]['month'] = $i;
                for ($j = 1; $j <= cal_days_in_month(CAL_GREGORIAN, $i, $year); $j++) {
                    $planned_mo[$i]['days'][$j]['day'] = $j;
                    $planned_mo[$i]['days'][$j]['workers'] = array();

                }
            }


            // получаем список людей прошедших медосмотр и их заключения итоговые
            $med_report_workers = (new Query())
                ->select('
                    med_report.worker_id as worker_id,
                    med_report.med_report_result_id as med_report_result_id,
                    med_report_result.title as med_report_result_title
                ')
                ->from('med_report')
                ->innerJoin('med_report_result', 'med_report_result.id=med_report.med_report_result_id')
//                ->where(['in', 'company_department_id', $comp_departments])
                ->where('YEAR(med_report.med_report_date)=' . $year)
                ->orderBy(['med_report.med_report_date' => SORT_ASC])
                ->indexBy('worker_id')
                ->all();
            /** Отладка */
            $description = 'Получил заключения МО';                                                                      // описание текущей отладочной точки
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
//            $warnings[]=$med_report_workers;


            // получить все графики по запрашиваемому подразделению
            // пробежаться от начала до конца с шагом в один день
            // и в каждый день записать работников по графику в результирующий массив
            // если есть человек в списке прошедших МО, то поставить ему отметку
            $phys_schedules_grafic = (new Query())
                ->select('
                    physical_schedule.id as physical_schedule_id,
                    physical_schedule.date_start as date_start,
                    physical_schedule.date_end as date_end,
                    physical_schedule.company_department_id as company_department_id,
                    company.title as company_title,
                    physical_worker.worker_id as worker_id,
                    physical_worker.contingent_id as contingent_id,
                    physical_worker.id as physical_worker_id,
                    worker.position_id as position_id,
                    position.title as position_title,
                    worker_object.role_id as role_id,
                    role.title as role_title,
                    worker.tabel_number as tabel_number,
                    employee.last_name as last_name,
                    employee.first_name as first_name,
                    employee.patronymic as patronymic,
                    ')
                ->from('physical_schedule')
                ->innerJoin('company_department', 'company_department.id=physical_schedule.company_department_id')
                ->innerJoin('company', 'company_department.company_id=company.id')
                ->innerJoin('physical', 'physical.id=physical_schedule.physical_id')
                ->innerJoin('physical_worker', 'physical_schedule.id=physical_worker.physical_schedule_id')
                ->innerJoin('worker', 'worker.id=physical_worker.worker_id')
                ->leftJoin('worker_object', 'worker.id=worker_object.worker_id')
                ->leftJoin('role', 'role.id=worker_object.role_id')
                ->innerJoin('position', 'position.id=worker.position_id')
                ->innerJoin('employee', 'worker.employee_id=employee.id')
                ->where(['physical.year' => $year])
                ->andWhere(['in', 'physical_schedule.company_department_id', $comp_departments])
                ->all();
//            $warnings[]=$phys_schedules_grafic;
            /** Отладка */
            $description = 'Получил график МО';                                                                      // описание текущей отладочной точки
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

            $group_by_grafic = array();
            // группирую людей в графики для ускорения выполнения метода
            foreach ($phys_schedules_grafic as $grafic) {
                $group_by_grafic[$grafic['physical_schedule_id']]['date_start'] = $grafic['date_start'];
                $group_by_grafic[$grafic['physical_schedule_id']]['date_end'] = $grafic['date_end'];
                $group_by_grafic[$grafic['physical_schedule_id']]['workers'][$grafic['worker_id']] = $grafic;
            }
            unset($phys_schedules_grafic);
            unset($grafic);


            // раскидываю людей по дням
            foreach ($group_by_grafic as $grafic) {
                $count = 0;
                for ($date = $grafic['date_start']; $date <= $grafic['date_end']; $date = date("Y-m-d", strtotime($date . '+1day'))) {
                    $count++;
                    if ($count > 366) {
                        $warnings[] = $date;
                        $warnings[] = $grafic['date_start'];
                        $warnings[] = $grafic['date_end'];

                        throw new Exception("счетчик больше 366");
                    }
                    $grafic_day = (int)date('d', strtotime($date));
                    $grafic_month = (int)date('m', strtotime($date));

                    /**
                     * {month}              - месяц
                     *      month:                  - месяц
                     *      days:                   - список дней
                     *         {day}                        - день
                     *              day:                            - день
                     *              workers:                        - список работников
                     *                  {worker_id}                     - ключ работника
                     *                      worker_id                       - ключ работника
                     *                      worker_full_name                - полное Имя работника
                     *                      tabel_number                    - табельный номер работника
                     *                      company_department_id           - ключ подразделенеия работника
                     *                      company_title                   - наименование подразделения работника
                     *                      position_id                     - ключ должности работника
                     *                      position_title                  - наименование должности работника
                     *                      role_id                         - ключ роли работника
                     *                      role_title                      - роль работника
                     *                      date_start                      - планова дата начала МО
                     *                      date_end                        - плановая дата окончания МО
                     *                      med_report_result_id            - ключ заключения МО
                     *                      med_report_result_title         - наименование заключения МО
                     *                      physical_schedule_id            - ключ графика в котором находтся хапланированны МО
                     *                      physical_worker_id              - ключ работника для быстрого нахождения его контингента (может потребоваться в будующем)
                     *                      contingent_id                   - ключ контингента работника
                     */

                    if (isset($planned_mo[$grafic_month]['days'][$grafic_day])) {
                        foreach ($grafic['workers'] as $worker) {
                            $planned_mo[$grafic_month]['days'][$grafic_day]['workers'][$worker['worker_id']]['worker_id'] = $worker['worker_id'];
                            $planned_mo[$grafic_month]['days'][$grafic_day]['workers'][$worker['worker_id']]['worker_full_name'] = $worker['last_name'] . ' ' . $worker['first_name'] . ' ' . $worker['patronymic'];
                            $planned_mo[$grafic_month]['days'][$grafic_day]['workers'][$worker['worker_id']]['tabel_number'] = $worker['tabel_number'];
                            $planned_mo[$grafic_month]['days'][$grafic_day]['workers'][$worker['worker_id']]['company_department_id'] = $worker['company_department_id'];
                            $planned_mo[$grafic_month]['days'][$grafic_day]['workers'][$worker['worker_id']]['company_title'] = $worker['company_title'];
                            $planned_mo[$grafic_month]['days'][$grafic_day]['workers'][$worker['worker_id']]['position_id'] = $worker['position_id'];
                            $planned_mo[$grafic_month]['days'][$grafic_day]['workers'][$worker['worker_id']]['position_title'] = $worker['position_title'];
                            $planned_mo[$grafic_month]['days'][$grafic_day]['workers'][$worker['worker_id']]['role_id'] = $worker['role_id'];
                            $planned_mo[$grafic_month]['days'][$grafic_day]['workers'][$worker['worker_id']]['role_title'] = $worker['role_title'];
                            $planned_mo[$grafic_month]['days'][$grafic_day]['workers'][$worker['worker_id']]['date_start'] = $worker['date_start'];
                            $planned_mo[$grafic_month]['days'][$grafic_day]['workers'][$worker['worker_id']]['date_end'] = $worker['date_end'];
                            $planned_mo[$grafic_month]['days'][$grafic_day]['workers'][$worker['worker_id']]['physical_schedule_id'] = $worker['physical_schedule_id'];
                            $planned_mo[$grafic_month]['days'][$grafic_day]['workers'][$worker['worker_id']]['physical_worker_id'] = $worker['physical_worker_id'];
                            $planned_mo[$grafic_month]['days'][$grafic_day]['workers'][$worker['worker_id']]['contingent_id'] = $worker['contingent_id'];
                            if (isset($med_report_workers[$worker['worker_id']])) {
                                $planned_mo[$grafic_month]['days'][$grafic_day]['workers'][$worker['worker_id']]['med_report_result_id'] = $med_report_workers[$worker['worker_id']]['med_report_result_id'];
                                $planned_mo[$grafic_month]['days'][$grafic_day]['workers'][$worker['worker_id']]['med_report_result_title'] = $med_report_workers[$worker['worker_id']]['med_report_result_title'];
                            } else {
                                $planned_mo[$grafic_month]['days'][$grafic_day]['workers'][$worker['worker_id']]['med_report_result_id'] = null;
                                $planned_mo[$grafic_month]['days'][$grafic_day]['workers'][$worker['worker_id']]['med_report_result_title'] = "";
                            }
                        }
                    }

                }
            }

            foreach ($planned_mo as $month) {
                foreach ($month['days'] as $day) {
                    if (empty($day['workers'])) {
                        $planned_mo[$month['month']]['days'][$day['day']]['workers'] = (object)array();
                    }
                }
            }

            if (isset($planned_mo) and $planned_mo) {
                $result = $planned_mo;
            } else {
                $result = (object)array();
            }
            /** Метод окончание */

        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
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
//        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));                       // время окончания выполнения метода
//        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//            $date_time_debug_start, $date_time_debug_end, $log_id,
//            $duration_summary, $max_memory_peak, $count_all);


        return $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
    }

    /**
     * Метод SaveDocumentPhysical() - Метод сохранения приказа на участок
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers
     *
     * @example http://amicum/read-manager-amicum?controller=PhysicalSchedule&method=SaveDocumentPhysical&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 13.12.2019 9:13
     */
    public static function SaveDocumentPhysical($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $post_dec = (object)array();                                                                                              // Массив ошибок
        $method_name = 'SaveDocumentPhysical';
        $session = Yii::$app->session;
//        $data_post = '{"company_department_id":20028766,"document_physical_id":null,"physical_id":3,"title":"Тест_сохранение","date_start":"20.12.2019","date_end":"31.12.2019","status_id":70,"vid_document_id":10,"jsondoc":"ТЕСТ","worker_id":2050735,"attachment":{"document_physical_document_id":null,"id":null,"type":null,"title":null,"src":null,"status":null}}';
        $warnings[] = 'SaveDocumentPhysical. Начало метода';
        try {
//            $transaction = Yii::$app->db->beginTransaction();
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('SaveDocumentPhysical. Не переданы входные параметры');
            }
            $warnings[] = 'SaveDocumentPhysical. Данные успешно переданы';
            $warnings[] = 'SaveDocumentPhysical. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'SaveDocumentPhysical. Декодировал входные параметры';
            if (!property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'document_event_pb_id') ||
                !property_exists($post_dec, 'physical_id') ||
                !property_exists($post_dec, 'title') ||
                !property_exists($post_dec, 'date_start') ||
                !property_exists($post_dec, 'date_end') ||
                !property_exists($post_dec, 'status_id') ||
                !property_exists($post_dec, 'vid_document_id') ||
                !property_exists($post_dec, 'jsondoc') ||
                !property_exists($post_dec, 'worker_id') ||
                !property_exists($post_dec, 'attachments')
            ) {
                throw new Exception('SaveDocumentPhysical. Переданы некорректные входные параметры');
            }
            $warnings[] = 'SaveDocumentPhysical. Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;
            $document_event_pb_id = $post_dec->document_event_pb_id;
            $physical_id = $post_dec->physical_id;
            $title = $post_dec->title;
            $date_start = date('Y-m-d', strtotime($post_dec->date_start));
            $date_end = date('Y-m-d', strtotime($post_dec->date_end));
            $status_id = $post_dec->status_id;
            $vid_document_id = $post_dec->vid_document_id;
            $jsondoc = $post_dec->jsondoc;
//            $worker_id = $post_dec->worker_id;
            $attachment = $post_dec->attachments;

            $new_document_event_pb = DocumentEventPb::findOne(['id' => $document_event_pb_id]);
            if (!$new_document_event_pb) {
                $new_document_event_pb = new DocumentEventPb();
            } else {
                $warnings[] = "SaveJournalInquiry. Документ уже был ";
            }
            $new_document_event_pb->title = $title;
            $new_document_event_pb->number_document = null;
            $new_document_event_pb->date_start = $date_start;
            $new_document_event_pb->date_end = $date_end;
            $new_document_event_pb->last_status_id = $status_id;
            $new_document_event_pb->vid_document_id = $vid_document_id;
            $new_document_event_pb->jsondoc = $jsondoc;
            $new_document_event_pb->worker_id = $session['worker_id'];
            $new_document_event_pb->parent_document_id = null;
            if ($new_document_event_pb->save()) {
                $new_document_event_pb->refresh();
                $document_event_pb_id = $new_document_event_pb->id;
                $post_dec->document_event_pb_id = $document_event_pb_id;
                $warnings[] = 'SaveJournalInquiry. Данные успешно сохранены в модель DocumentEventPb';
            } else {
                $errors[] = $new_document_event_pb->errors;
                throw new Exception('SaveJournalInquiry. Ошибка сохранения модели DocumentEventPb');
            }
            $document_physical = DocumentPhysical::findOne(['document_event_pb_id' => $document_event_pb_id, 'physical_id' => $physical_id, 'company_department_id' => $company_department_id]);
            if (empty($document_physical)) {
                $document_physical = new DocumentPhysical();
            }
            $document_physical->document_event_pb_id = $document_event_pb_id;
            $document_physical->physical_id = $physical_id;
            $document_physical->company_department_id = $company_department_id;
            if ($document_physical->save()) {
                $warnings[] = $method_name . '. Связка документа и графика прошла успешно';
//                $document_physical_id = $document_physical->id;
            } else {
                $errors[] = $document_physical->errors;
                throw new Exception($method_name . '. Ошибка при сохранении связи документа и графика');
            }

            if ($attachment->status == 'new') {
                $normalize_path = Assistant::UploadFile($attachment->src, $attachment->title, 'attachment', $attachment->type);
                $add_attachment = new Attachment();
                $add_attachment->path = $normalize_path;
                $add_attachment->date = BackendAssistant::GetDateFormatYMD();
                $add_attachment->worker_id = $session['worker_id'];
                $add_attachment->section_title = 'ОТ и ПБ/Медосмотры';
                $add_attachment->title = $attachment->title;
                $add_attachment->attachment_type = $attachment->type;
                if ($add_attachment->save()) {
                    $warnings[] = 'SaveDocumentPhysical. Вложение в документ успешно сохранено';
                    $add_attachment->refresh();
                    $attachment_id = $add_attachment->id;
                    $post_dec->attachments->src = $normalize_path;
                    $post_dec->attachments->id = $attachment_id;
                    $post_dec->attachments->status = null;
                } else {
                    $errors[] = $add_attachment->errors;
                    throw new Exception('SaveDocumentPhysical. Ошибка при сохранении вложения в документ');
                }
                $add_doc_attachment = new DocumentEventPbAttachment();
                $add_doc_attachment->document_event_pb_id = $document_event_pb_id;
                $add_doc_attachment->attachment_id = $attachment_id;
                if ($add_doc_attachment->save()) {
                    $warnings[] = 'SaveDocumentPhysical. Связка документа и вложения успешно установлена';
                    $add_doc_attachment->refresh();
                    $post_dec->attachments->document_physical_document_id = $add_doc_attachment->id;
                } else {
                    $errors[] = $add_doc_attachment->errors;
                    throw new Exception('SaveDocumentPhysical. Ошибка при сохранении связки документа и вложений');
                }
//                $inserted_doc_attachment[] = [$new_document_event_pb_id, $attachment_id];
            } elseif ($attachment->status == 'del') {
                DocumentEventPbAttachment::deleteAll(['document_event_pb_id' => $document_event_pb_id, 'attachment_id' => $attachment->id]);
            }
//            $transaction->commit();
        } catch (Throwable $exception) {
//            $transaction->rollBack();
            $errors[] = 'SaveDocumentPhysical. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'SaveDocumentPhysical. Конец метода';

        return array('Items' => $post_dec, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод SavePhysicalAttachment() - Сохранение вложения на СП (Согласование/Приказ)
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers
     *
     * Входные обязательные параметры:
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.01.2020 13:46
     */
    public static function SavePhysicalAttachment($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $post_dec = (object)array();                                                                                              // Массив ошибок
        $method_name = 'SavePhysicalAttachment';
        $warnings[] = $method_name . '. Начало метода';
        $session = Yii::$app->session;
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'attachment_physical_document_id') ||
                !property_exists($post_dec, 'attachment_title') ||
                !property_exists($post_dec, 'attachment_type') ||
                !property_exists($post_dec, 'attachment_id') ||
                !property_exists($post_dec, 'attachment_path') ||
                !property_exists($post_dec, 'attachment_date') ||
                !property_exists($post_dec, 'attachment_filename') ||
                !property_exists($post_dec, 'physical_attachment_id')
            )                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;
            $attachment_physical_document_id = $post_dec->attachment_physical_document_id;
            $attachment_title = $post_dec->attachment_title;
            $attachment_type = $post_dec->attachment_type;
            $attachment_id = $post_dec->attachment_id;
            $attachment_path = $post_dec->attachment_path;
            $attachment_date = $post_dec->attachment_date;
            $attachment_filename = $post_dec->attachment_filename;
            $physical_attachment_id = $post_dec->physical_attachment_id;

            if (!empty($physical_attachment_id)) {
                PhysicalAttachment::deleteAll(['id' => $physical_attachment_id]);
            }

            $physical_attachment = new PhysicalAttachment();
            if (empty($attachment_id)) {
                $normalize_path = Assistant::UploadFile($attachment_path, $attachment_filename, 'attachment', $attachment_type);
                $add_attachment = new Attachment();
                $add_attachment->title = $attachment_filename;
                $add_attachment->path = $normalize_path;
                $add_attachment->attachment_type = $attachment_type;
                $add_attachment->section_title = 'ОТ и ПБ/Согласование медосмотров';
                $add_attachment->worker_id = $session['worker_id'];
                $add_attachment->date = BackendAssistant::GetDateFormatYMD();
                if ($add_attachment->save()) {
                    $warnings[] = $method_name . '. Вложение успешно сохранено';
                    $attachment_id = $add_attachment->id;
                    $post_dec->attachment_path = $normalize_path;
                    $post_dec->attachment_id = $attachment_id;
                } else {
                    $errors[] = $add_attachment->errors;
                    throw new Exception($method_name . '. Ошибка при сохранении вложения');
                }
            }
            $physical_attachment->attachment_id = $attachment_id;
            $physical_attachment->company_department_id = $company_department_id;
            if (empty($attachment_title)) {
                $attachment_title = $attachment_filename;
            }
            $physical_attachment->title = $attachment_title;
            if (empty($attachment_date)) {
                $attachment_date = date('Y-m-d', strtotime(BackendAssistant::GetDateFormatYMD()));
            } else {
                $attachment_date = date('Y-m-d', strtotime($attachment_date));
            }
            $physical_attachment->date = $attachment_date;
            $physical_attachment->physical_id = $attachment_physical_document_id;
            if ($physical_attachment->save()) {
                $warnings[] = $method_name . '. Сохранили вложение на СП';
                $post_dec->physical_attachment_id = $physical_attachment->id;
            } else {
                $errors[] = $physical_attachment->errors;
                throw new Exception($method_name . '. Ошибка при сохранении связки вложения и привязки его к СП');
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        return array('Items' => $post_dec, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод RemovePhysicalAttachment() - Удаление связки файла на СП
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.01.2020 13:44
     */
    public static function RemovePhysicalAttachment($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'RemovePhysicalAttachment';
        $result = array();
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'physical_attachment_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $physical_attachment_id = $post_dec->physical_attachment_id;
            CompanyDepartmentAttachment::deleteAll(['id' => $physical_attachment_id]);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    // GetMedicalReport - метод получения сведений о медицинских заключениях
    // входные данные:
    //      year                             - год за который строим статистику
    //      month                            - месяц за который строим статистику
    //      company_department_id            - подразделение с учетом вложений для которого строим статистику
    //      period                           - период за который строим статистику год/месяц 'month/year'
    // выходные данные:
    //      medical_examinations:
    //          [company_department_id]         -   ключ департамента/подразделения
    //                  company_department_id       -   ключ департамента, в котором числился работник на момент прохождения медицинского осмотра
    //                  examinations:               -   список заключений медицинских осмотров за период
    //                      [medical_examination_id]       -   ключ медицинского заключения
    //                              medical_examination_id          -   ключ медицинского заключения
    //                              worker_id                       -   ключ работника, прошедшего медицинский осмотр
    //                              position_id                     -   должность работника, прошедшего медицинский осмотр
    //                              date_examination                -   дата выдачи заключения
    //                              date_examination_format         -   дата выдачи заключения форматированная
    //                              date_worker_examination         -   дата прохождения медицинского осмотра
    //                              date_worker_examination_format  -   дата прохождения медицинского осмотра форматированная
    //                              medical_report_result_id        -   Ключ результата заключения мед. осмотра
    //                              attachment_id                   -   ключ вложения (скан заключения)
    //                              attachment_path                 -   путь к вложению
    //                              comment_result                  -   Комментарий к заключительному результату мед.осмотра
    //                              date_next                       -   Дата следующего мед.осмотра
    //                              date_next_format                -   Дата следующего мед.осмотра форматированная
    //                              disease_id                      -   Ключ проф. заболевания
    //                              classifier_diseases_id          -   Комментарий дополнительное поле для внесение комментария (напр. для указания класса заболевания)
    //      count_all_worker                 - всего сотрудников
    //      count_all_worker_man             - всего сотрудников мужчин
    //      count_all_worker_woman           - всего сотрудников женщин
    //      count_worker_with_medical        - Всего сотрудников с прошедших медосмотр
    //      count_worker_with_medical_man    - Всего сотрудников с прошедших медосмотр мужчин
    //      count_worker_with_medical_woman  - Всего сотрудников с прошедших медосмотр женщин
    //      med_report_percentage:           - круговая диаграмма статистика заключений медицинской комиссии
    //          [id]                               -   ключ заключения
    //              id                                      -   ключ заключения
    //              title                                   -   название заключения (годен/нет и т.д.)
    //              value                                   -   количество человек в группе заключения
    //              percent                                 -   процен людей в группе заключений
    // алгоритм:
    // 1. делаем проверку входных данных
    // 2. Ищем все вложенные департаменты
    // 3. обрабатываем входные данные с целью унификации метода по году/по месяцу
    // 4. получаем данные о медицинских осмотрах из БД
    // 5. формируем выходной объект
    // 6. отправляем его на фронт
    // Разработал: Якимов М.Н.
    // дата разработки: 30.01.2020
    // прмер использования:
    // http://127.0.0.1/read-manager-amicum?controller=PhysicalSchedule&method=getMedicalReport&subscribe=&data={%22company_department_id%22:4029720,%22year%22:%222020%22,%22month%22:%2201%22,%22period%22:%22month%22}
    public static function GetMedicalReport($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'getMedicalReport';                                                                              // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = null;                                                                                              // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                       // время начала выполнения метода
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

            // запись в БД начала выполнения скрипта
            // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//                $date_time_debug_start, $date_time_debug_end, $log_id,
//                $duration_summary, $max_memory_peak, $count_all);
//            if ($response['status'] === 1) {
//                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
//            } else {
//                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
//            }

            /** Метод начало */
            $warnings[] = 'getMedicalReport. Начало метода';
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('getMedicalReport. Не переданы входные параметры');
            }
            $warnings[] = 'getMedicalReport. Данные успешно переданы';
            $warnings[] = 'getMedicalReport. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'getMedicalReport. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'company_department_id') ||                                       // ключ департамента
                !property_exists($post_dec, 'year') ||                                                         // год
                !property_exists($post_dec, 'month') ||                                                        // месяц
                !property_exists($post_dec, 'period'))                                                         // период 'month/year'
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = 'getMedicalReport. Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;                                                  // подразделение по которому нужно получить историю
            $period = $post_dec->period;                                                                                // период за который строится статистика
            $year = $post_dec->year;                                                                                    // год за который строится статистика
            $month = $post_dec->month;                                                                                  // месяц за который строится статистика
            if ($period === 'month') {
                $count_day = cal_days_in_month(CAL_GREGORIAN, $month, $year);                                  // количество дней в месяце
                $date = date('Y-m-d', strtotime($year . '-' . $month . '-' . $count_day));                 // период за месяц до конца месяца
//                $filterMonth = "MONTH(med_report.physical_worker_date)='" . $month . "'";                                    // задаем фильтрацию по месяцу
            } elseif ($period === 'year') {
                $date = date('Y-m-d', strtotime($year . '-12-31'));                                        // период за год до конца года
//                $filterMonth = null;                                                                                    // принудительно обнуляем переменную месяц для исключения ее из фильтров запросов (Null)
                $month = null;                                                                                          // принудительно обнуляем переменную месяц для исключения ее из фильтров запросов (Null)
            }
            $warnings[] = $date;

            // ищем вложенные подразделения
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception($method_name . '. Ошибка получения вложенных департаментов' . $company_department_id);
            }
            /** Отладка */
            $description = 'Получил список вложенных департаментов';                                                                      // описание текущей отладочной точки
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

            // получаем историю медицинских осмотров
            $med_reports = (new Query())
                ->select('
                med_report.company_department_id,
                med_report.id as id,
                med_report.worker_id,
                med_report.position_id,
                med_report.med_report_date,
                med_report.physical_worker_date,
                med_report.med_report_result_id,
                med_report.attachment_id as attachment_id,
                attachment.path,
                med_report.comment_result,
                med_report.date_next,
                med_report.disease_id,
                med_report.classifier_diseases_id,
                ')
                ->from('med_report')
                ->leftJoin('attachment', 'med_report.attachment_id = attachment.id')
                ->where(['in', 'med_report.company_department_id', $company_departments])
//                ->andWhere(['<', 'med_report.med_report_date', $date])
                ->andWhere("YEAR(med_report.physical_worker_date)='" . $year . "'")
                ->andFilterWhere(['MONTH(med_report.physical_worker_date)' => $month])
                ->all();

            // формируем выходной массив
            foreach ($med_reports as $med_report) {
                $medical_examinations[$med_report['company_department_id']]['company_department_id'] = $med_report['company_department_id'];
                $medical_examinations[$med_report['company_department_id']]['examinations'][$med_report['id']]['medical_examination_id'] = $med_report['id'];
                $medical_examinations[$med_report['company_department_id']]['examinations'][$med_report['id']]['worker_id'] = $med_report['worker_id'];
                $medical_examinations[$med_report['company_department_id']]['examinations'][$med_report['id']]['position_id'] = $med_report['position_id'];
                $medical_examinations[$med_report['company_department_id']]['examinations'][$med_report['id']]['date_examination'] = $med_report['med_report_date'];
                if ($med_report['med_report_date']) {
                    $medical_examinations[$med_report['company_department_id']]['examinations'][$med_report['id']]['date_examination_format'] = date('d.m.Y', strtotime($med_report['med_report_date']));
                } else {
                    $medical_examinations[$med_report['company_department_id']]['examinations'][$med_report['id']]['date_examination_format'] = "";
                }
                $medical_examinations[$med_report['company_department_id']]['examinations'][$med_report['id']]['date_worker_examination'] = $med_report['physical_worker_date'];
                if ($med_report['physical_worker_date']) {
                    $medical_examinations[$med_report['company_department_id']]['examinations'][$med_report['id']]['date_worker_examination_format'] = date('d.m.Y', strtotime($med_report['physical_worker_date']));
                } else {
                    $medical_examinations[$med_report['company_department_id']]['examinations'][$med_report['id']]['date_worker_examination_format'] = "";
                }
                $medical_examinations[$med_report['company_department_id']]['examinations'][$med_report['id']]['medical_report_result_id'] = $med_report['med_report_result_id'];
                $medical_examinations[$med_report['company_department_id']]['examinations'][$med_report['id']]['attachment_id'] = $med_report['attachment_id'];
                $medical_examinations[$med_report['company_department_id']]['examinations'][$med_report['id']]['attachment_path'] = $med_report['path'];
                $medical_examinations[$med_report['company_department_id']]['examinations'][$med_report['id']]['comment_result'] = $med_report['comment_result'];
                $medical_examinations[$med_report['company_department_id']]['examinations'][$med_report['id']]['date_next'] = $med_report['date_next'];

                if ($med_report['date_next']) {
                    $medical_examinations[$med_report['company_department_id']]['examinations'][$med_report['id']]['date_next_format'] = date('d.m.Y', strtotime($med_report['date_next']));
                } else {
                    $medical_examinations[$med_report['company_department_id']]['examinations'][$med_report['id']]['date_next_format'] = "";
                }
                $medical_examinations[$med_report['company_department_id']]['examinations'][$med_report['id']]['disease_id'] = $med_report['disease_id'];
                $medical_examinations[$med_report['company_department_id']]['examinations'][$med_report['id']]['classifier_diseases_id'] = $med_report['classifier_diseases_id'];
            }

            if (!isset($medical_examinations)) {
                $result['medical_examinations'] = (object)array();
            } else {
                $result['medical_examinations'] = $medical_examinations;
            }
            unset($found_worker);

            // получаем количество работающих работников на заданную дату сгруппировнных по гентерному признаку
            $found_worker = (new Query())
                ->select('count(worker.id) as count_worker, employee.gender')
                ->from('worker')
                ->innerJoin('employee', 'employee.id=worker.employee_id')
                ->where(['in', 'worker.company_department_id', $company_departments])
                ->andWhere(['<=', 'worker.date_start', $date])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $date],
                    ['is', 'worker.date_end', null]
                ])
                ->groupBy('employee.gender')
                ->indexBy('gender')
                ->all();
            /** Отладка */
            $description = 'Получил список работающих работников из БД';                                                                      // описание текущей отладочной точки
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

            // считаю статистику работников по гендерному признаку
            $result['count_all_worker_woman'] = 0;                                                                      // количество работников мужчин в подразделении
            $result['count_all_worker_man'] = 0;                                                                        // количество работников женщин в подразделении
            if (isset($found_worker['М'])) {
                $result['count_all_worker_man'] = (int)$found_worker['М']['count_worker'];
            }
            if (isset($found_worker['Ж'])) {
                $result['count_all_worker_woman'] = (int)$found_worker['Ж']['count_worker'];
            }
            $result['count_all_worker'] = $result['count_all_worker_woman'] + $result['count_all_worker_man'];        // всего работников в подразделении
            unset($found_worker);

            // Считаем сотрудников прошедших медосмотр
            $found_worker = (new Query())
                ->select('worker.id, employee.gender')
                ->from('med_report')
                ->innerJoin('worker', 'med_report.worker_id = worker.id')
                ->innerJoin('employee', 'employee.id=worker.employee_id')
                ->where(['in', 'med_report.company_department_id', $company_departments])
//                ->andWhere(['<', 'occupational_illness.date_act', $date])
                ->andWhere("YEAR(med_report.med_report_date)='" . $year . "'")
                ->andFilterWhere(['MONTH(med_report.med_report_date)' => $month])
                ->groupBy('worker.id, employee.gender')
                ->all();
            $result['count_worker_with_medical'] = 0;
            $result['count_worker_with_medical_man'] = 0;
            $result['count_worker_with_medical_woman'] = 0;
            foreach ($found_worker as $item) {
                if ($item['gender'] == 'М') {
                    $result['count_worker_with_medical_man']++;
                } elseif ($item['gender'] == 'Ж') {
                    $result['count_worker_with_medical_woman']++;
                }
            }
            $result['count_worker_with_medical'] = $result['count_worker_with_medical_man'] + $result['count_worker_with_medical_woman'];
            unset($found_worker);
            /** Отладка */
            $description = 'Посчитали сотрудников прошедших медосмотр';                                                 // описание текущей отладочной точки
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

            // Круговая диаграмма Статистика заключений медицинской комиссии
            $found_worker = (new Query())
                ->select('group_med_report_result_id, group_med_report_result.title as group_med_report_result_title, count(med_report.id) as sum_med_report_value')
                ->from('med_report')
                ->innerJoin('worker', 'med_report.worker_id = worker.id')
                ->innerJoin('med_report_result', 'med_report.med_report_result_id = med_report_result.id')
                ->innerJoin('group_med_report_result', 'med_report_result.group_med_report_result_id = group_med_report_result.id')
                ->where(['in', 'med_report.company_department_id', $company_departments])
                ->andWhere("YEAR(med_report.med_report_date)='" . $year . "'")
                ->andFilterWhere(['MONTH(med_report.med_report_date)' => $month])
                ->groupBy('group_med_report_result_id, group_med_report_result_title')
                ->indexBy('group_med_report_result_id')
                ->all();
            $count_worker = 0;
            foreach ($found_worker as $item) {
                $count_worker += (int)$item['sum_med_report_value'];
            }
            foreach ($found_worker as $item) {
                $found_worker_percent[$item['group_med_report_result_id']]['id'] = $item['group_med_report_result_id'];
                $found_worker_percent[$item['group_med_report_result_id']]['title'] = $item['group_med_report_result_title'];
                $found_worker_percent[$item['group_med_report_result_id']]['value'] = (int)$item['sum_med_report_value'];
                if ($result['count_worker_with_medical'] == 0) {
                    $found_worker_percent[$item['group_med_report_result_id']]['percent'] = 0;
                } else {
                    $found_worker_percent[$item['group_med_report_result_id']]['percent'] = round(($item['sum_med_report_value'] / $count_worker) * 100, 1);
                }
            }
            if (!isset($found_worker_percent)) {
                $result['med_report_percentage'] = (object)array();
            } else {
                $result['med_report_percentage'] = $found_worker_percent;
            }
            unset($found_worker);

            /** Отладка */
            $description = 'Круговая диаграмма Статистика Заключений медицинской комиссии';                                                                      // описание текущей отладочной точки
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

            /** Метод окончание */


        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
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
//        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                       // время окончания выполнения метода
//        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//            $date_time_debug_start, $date_time_debug_end, $log_id,
//            $duration_summary, $max_memory_peak, $count_all);

        return array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
    }


    // DeleteMedicalReport - метод удаления медицинскиого заключения
    // входные данные:
    //      medical_report_id               - ключ заключения медицинского осмотра
    // выходные данные:
    //      стандартный набор
    // алгоритм:
    // 1. делаем проверку входных данных
    // 2. удаляем объект
    // 6. отправляем статус на фронт
    // Разработал: Якимов М.Н.
    // дата разработки: 30.01.2020
    // прмер использования:
    // http://127.0.0.1/read-manager-amicum?controller=PhysicalSchedule&method=DeleteMedicalReport&subscribe=&data={%22medical_report_id%22:1}
    public static function DeleteMedicalReport($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'DeleteMedicalReport';                                                                           // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = null;                                                                                              // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                       // время начала выполнения метода
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

            // запись в БД начала выполнения скрипта
            // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//                $date_time_debug_start, $date_time_debug_end, $log_id,
//                $duration_summary, $max_memory_peak, $count_all);
//            if ($response['status'] === 1) {
//                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
//            } else {
//                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
//            }

            /** Метод начало */
            $warnings[] = 'DeleteMedicalReport. Начало метода';
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('DeleteMedicalReport. Не переданы входные параметры');
            }
            $warnings[] = 'DeleteMedicalReport. Данные успешно переданы';
            $warnings[] = 'DeleteMedicalReport. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'DeleteMedicalReport. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'medical_report_id')                                               // ключ заключения медицинского осмотра
            ) {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = 'DeleteMedicalReport. Данные с фронта получены';
            $medical_report_id = $post_dec->medical_report_id;                                                          // ключ заключения медицинского осмотра

            $result = MedReport::deleteAll(['id' => $medical_report_id]);


            /** Метод окончание */


        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
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
//        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                       // время окончания выполнения метода
//        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//            $date_time_debug_start, $date_time_debug_end, $log_id,
//            $duration_summary, $max_memory_peak, $count_all);

        return array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
    }

    // SaveMedicalReport - метод сохранения медицинского заключения или отметки о прохождении
    // входные данные:
    //      medical_examination:
    //                  company_department_id       -   ключ департамента, в котором числился работник на момент прохождения медицинского осмотра
    //                  medical_examination_id          -   ключ медицинского заключения
    //                  worker_id                       -   ключ работника, прошедшего медицинский осмотр
    //                  position_id                     -   должность работника, прошедшего медицинский осмотр
    //                  date_examination                -   дата выдачи заключения
    //                  date_examination_format         -   дата выдачи заключения форматированная
    //                  date_next                       -   Дата следующего мед.осмотра
    //                  date_next_format                -   Дата следующего мед.осмотра форматированная
    //                  comment_result                  -   Комментарий к заключительному результату мед.осмотра
    //                  date_worker_examination         -   дата прохождения медицинского осмотра
    //                  date_worker_examination_format  -   дата прохождения медицинского осмотра форматированная
    //                  disease_id                      -   Ключ проф. заболевания
    //                  medical_report_result_id        -   Ключ результата заключения мед. осмотра
    //                  attachment_id                   -   ключ вложения (скан заключения)
    //                  attachment_path                 -   путь к вложению
    //                  classifier_diseases_id          -   Комментарий дополнительное поле для внесение комментария (напр. для указания класса заболевания)
    //                  attachment:                     -   блок вложения
    //                      attachment_blob                 -   blob вложение
    //                      attachment_type                 -   тип вложения
    //                      title                           -   название вложения
    //                      sketch                          -   эскиз вложения
    //                      attachment_path                 -   путь вложения
    //
    // выходные данные:
    //      тот же объект, только с правильными айдишниками
    // алгоритм:
    // 1. делаем проверку входных данных
    // 2. ищем медицинское заключение, если оно есть, то обновляем в нем данные, иначе создаем новое
    // 3. ищем вложение, если вложения нет, то проверяем на наличе во входном массиве нового вложения, если его нет, то идем дальше, иначе создаем вложение
    // 4. меняем айдишник вложения, если вложение новое, то обнуляем объект attachment
    // 5. Сохраняем медицинское заключение
    // 6. меняем айдишники во входном объекте на актуальные
    // 7. отправляем статус на фронт
    // Разработал: Якимов М.Н.
    // дата разработки: 30.01.2020
    // прмер использования:
    // http://127.0.0.1/read-manager-amicum?controller=PhysicalSchedule&method=SaveMedicalReport&subscribe=&data={%22medical_report_id%22:1}
    public static function SaveMedicalReport($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'saveMedicalReport';                                                                             // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = null;                                                                                              // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        $session = Yii::$app->session;                                                                                  // текущая сессия

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

            // запись в БД начала выполнения скрипта
            // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//                $date_time_debug_start, $date_time_debug_end, $log_id,
//                $duration_summary, $max_memory_peak, $count_all);
//            if ($response['status'] === 1) {
//                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
//            } else {
//                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
//            }

            /** Метод начало */
            $warnings[] = 'SaveMedicalReport. Начало метода';
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('SaveMedicalReport. Не переданы входные параметры');
            }
            $warnings[] = 'SaveMedicalReport. Данные успешно переданы';
            $warnings[] = 'SaveMedicalReport. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'SaveMedicalReport. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'medical_examination')                                               // ключ заключения медицинского осмотра
            ) {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = 'SaveMedicalReport. Данные с фронта получены';
            $medical_examination = $post_dec->medical_examination;                                                          // ключ заключения медицинского осмотра

            // ищем медицинское заключение
            $med_report = MedReport::findOne(['id' => $medical_examination->medical_examination_id]);

            // если его нет, то создаем (Хотел сделать поиск на уже существующую проверку МО, что бы в нее дописывать)
            if (!$med_report) {
//                $med_report = MedReport::findOne(['worker_id' => $medical_examination->worker_id, 'physical_worker_date' => $medical_examination->date_worker_examination]);
//                if (!$med_report) {
                $med_report = new MedReport();
//                }
            }

            $med_report->worker_id = $medical_examination->worker_id;
            $med_report->position_id = $medical_examination->position_id;
            if ($medical_examination->date_examination) {
                $med_report->med_report_date = date('Y-m-d', strtotime($medical_examination->date_examination));
            }
            if ($medical_examination->date_worker_examination) {
                $med_report->physical_worker_date = date('Y-m-d', strtotime($medical_examination->date_worker_examination));
            }
            // дата следующего медосмотра считается +1 год от выдачи заключения
            if ($medical_examination->date_examination) {
                if ($medical_examination->date_next) {
                    $med_report->date_next = date('Y-m-d', strtotime($medical_examination->date_next));
                } else {
                    $med_report->date_next = date('Y-m-d', strtotime($medical_examination->date_examination . '+1 year'));
                }
                $medical_examination->date_next = $med_report->date_next;
                $medical_examination->date_next_format = date('d.m.Y', strtotime($medical_examination->date_next));
            }

            $med_report->comment_result = $medical_examination->comment_result;
            $med_report->disease_id = $medical_examination->disease_id;
            $med_report->med_report_result_id = $medical_examination->medical_report_result_id;
            if ($medical_examination->classifier_diseases_id) {
                $med_report->classifier_diseases_id = $medical_examination->classifier_diseases_id;
            }
            $med_report->company_department_id = $medical_examination->company_department_id;

            // блок обработки вложения
            // если есть новое вложение, то создаем его, иначе пролетаем дальше
            $attachment_id = $medical_examination->attachment_id;
            $attachment = Attachment::findOne(['id' => $attachment_id]);
            if (!$attachment and !empty($medical_examination->attachment) and ($medical_examination->attachment->attachment_blob)) {
                $medical_examination_attachment = $medical_examination->attachment;
                $attachment = new Attachment();
                $path1 = Assistant::UploadFile($medical_examination_attachment->attachment_blob, $medical_examination_attachment->title, 'medical_report', $medical_examination_attachment->attachment_type);
                $attachment->path = $path1;
                $attachment->date = BackendAssistant::GetDateFormatYMD();
                $attachment->worker_id = $session['worker_id'];
                $attachment->section_title = 'Учет медицинских осмотров';
                $attachment->title = $medical_examination_attachment->title;
                $attachment->attachment_type = $medical_examination_attachment->attachment_type;
                $attachment->sketch = $medical_examination_attachment->sketch;
                if ($attachment->save()) {
                    $attachment->refresh();
                    $attachment_id = $attachment->id;
                    $medical_examination->attachment_id = $attachment_id;
                    $medical_examination->attachment_path = $path1;
                    $medical_examination->attachment = (object)array();
                    $warnings[] = 'SaveMedicalReport. Данные успешно сохранены в модель Attachment';
                } else {
                    $errors[] = $attachment->errors;
                    throw new Exception('SaveMedicalReport. Ошибка сохранения модели Attachment');
                }
            } else {
                $warnings[] = "SaveMedicalReport. вложение уже было или нет файла который надо сохранить";
            }
            $med_report->attachment_id = $attachment_id;

            /** Отладка */
            $description = 'Обработал вложение';                                                                      // описание текущей отладочной точки
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

            if ($med_report->save()) {
                $med_report->refresh();
                $medical_examination->medical_examination_id = $med_report->id;
                $medical_examination->date_next = $med_report->date_next;
                $warnings[] = 'SaveMedicalReport. Данные успешно сохранены в модель MedReport';
            } else {
                $errors[] = $med_report->errors;
                throw new Exception('SaveMedicalReport. Ошибка сохранения модели MedReport');
            }
            $result = $medical_examination;
            /** Отладка */
            $description = 'Закончил основной метод метода';                                                                      // описание текущей отладочной точки
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

            /** Метод окончание */
        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
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
//        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                       // время окончания выполнения метода
//        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//            $date_time_debug_start, $date_time_debug_end, $log_id,
//            $duration_summary, $max_memory_peak, $count_all);

        return array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
    }

    // SaveHarmfulFactor - метод сохранения справочника вредных факторов
    // входные данные:
    //      harmfulFactor:
    //                  title           -   наименование вредного фаткора
    //                  period          -   период прохождения МО по вредному фактору
    //
    // выходные данные:
    //      тот же объект, только с правильными айдишниками
    // Разработал: Якимов М.Н.
    // дата разработки: 07.03.2020
    // прмер использования:
    // http://127.0.0.1/read-manager-amicum?controller=PhysicalSchedule&method=SaveHarmfulFactor&subscribe=&data={%harmfulFactor%22:1}
    public static function SaveHarmfulFactor($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'SaveHarmfulFactor';                                                                             // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = null;                                                                                              // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                       // время начала выполнения метода
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

            // запись в БД начала выполнения скрипта
            // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//                $date_time_debug_start, $date_time_debug_end, $log_id,
//                $duration_summary, $max_memory_peak, $count_all);
//            if ($response['status'] === 1) {
//                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
//            } else {
//                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
//            }

            /** Метод начало */
            $warnings[] = 'SaveHarmfulFactor. Начало метода';
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('SaveHarmfulFactor. Не переданы входные параметры');
            }
            $warnings[] = 'SaveHarmfulFactor. Данные успешно переданы';
            $warnings[] = 'SaveHarmfulFactor. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'SaveHarmfulFactor. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'harmfulFactor')
            ) {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = 'SaveHarmfulFactor. Данные с фронта получены';
            $harmfulFactor = $post_dec->harmfulFactor;                                                          // ключ заключения медицинского осмотра

            // ищем медицинское заключение
            $save_harmful_factor = HarmfulFactors::findOne(['id' => $harmfulFactor->id]);

            // если его нет, то создаем (Хотел сделать поиск на уже существующую проверку МО, что бы в нее дописывать)
            if (!$save_harmful_factor) {
                $save_harmful_factor = new HarmfulFactors();

            }

            $save_harmful_factor->title = $harmfulFactor->title;
            $save_harmful_factor->period = $harmfulFactor->period;
            if ($save_harmful_factor->save()) {
                $save_harmful_factor->refresh();
                $harmfulFactor->id = $save_harmful_factor->id;

                $warnings[] = 'SaveHarmfulFactor. Данные успешно сохранены в модель HarmfulFactors';
            } else {
                $errors[] = $save_harmful_factor->errors;
                throw new Exception('SaveHarmfulFactor. Ошибка сохранения модели HarmfulFactors');
            }
            $result = $harmfulFactor;
            /** Отладка */
            $description = 'Закончил основной метод метода';                                                                      // описание текущей отладочной точки
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

            /** Метод окончание */
        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
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
//        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                       // время окончания выполнения метода
//        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//            $date_time_debug_start, $date_time_debug_end, $log_id,
//            $duration_summary, $max_memory_peak, $count_all);

        return array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
    }

    // SavePassedMedicalExamination - метод сохранения Отметки о прохождении медицинского осмотра
    // входные данные:
    //      workers                     -   массив работников
    //          []
    //              worker_id               -   ключ работника
    //              company_department_id   -   ключ подразделения в котором работает работник
    //              position_id             -   должность работника текущая
    //      date_worker_examination:    - дата прохождения медицинского осмотра
    // выходные данные:
    //      [medical_examination_id]:   -   ключ медицинского заключения
    //                  company_department_id       -   ключ департамента, в котором числился работник на момент прохождения медицинского осмотра
    //                  medical_examination_id          -   ключ медицинского заключения
    //                  worker_id                       -   ключ работника, прошедшего медицинский осмотр
    //                  position_id                     -   должность работника, прошедшего медицинский осмотр
    //                  date_examination                -   дата выдачи заключения
    //                  date_examination_format         -   дата выдачи заключения форматированная
    //                  date_next                       -   Дата следующего мед.осмотра
    //                  comment_result                  -   Комментарий к заключительному результату мед.осмотра
    //                  date_worker_examination         -   дата прохождения медицинского осмотра
    //                  date_worker_examination_format  -   дата прохождения медицинского осмотра форматированная
    //                  disease_id                      -   Ключ проф. заболевания
    //                  medical_report_result_id        -   Ключ результата заключения мед. осмотра
    //                  attachment_id                   -   ключ вложения (скан заключения)
    //                  attachment_path                 -   путь к вложению
    //                  classifier_diseases_id          -   Комментарий дополнительное поле для внесение комментария (напр. для указания класса заболевания)
    //                  attachment:                     -   блок вложения
    // алгоритм:
    // 1. делаем проверку входных данных
    // 2. перебираем всех работников
    // 3. ищем отметку у этого рабоника на этот день, если ее нет, то создаем, иначе проходим мимо
    // 4. создаем отметку о прохождении медицинского осмотра
    // 5. возвращаем????
    // Разработал: Якимов М.Н.
    // дата разработки: 30.01.2020
    // прмер использования:
    // http://127.0.0.1/read-manager-amicum?controller=PhysicalSchedule&method=SavePassedMedicalExamination&subscribe=&data={%22medical_report_id%22:1}
    public static function SavePassedMedicalExamination($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'SavePassedMedicalExamination';                                                                             // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = null;                                                                                              // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                       // время начала выполнения метода
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

            // запись в БД начала выполнения скрипта
            // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
//            $response = LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//                $date_time_debug_start, $date_time_debug_end, $log_id,
//                $duration_summary, $max_memory_peak, $count_all);
//            if ($response['status'] === 1) {
//                $log_id = $response['Items'];                                                                                // сохраняем текущий ключ лога, для окончательной записи отметки о резултатах выполнения
//            } else {
//                throw new Exception($method_name . '. Не смог получить ключ лога для записи в БД');
//            }

            /** Метод начало */
            $warnings[] = $method_name . '. Начало метода';
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'workers') ||                                                      // список работников, которым делаем отметку о медицинском осмотре
                !property_exists($post_dec, 'date_worker_examination')                                         // дата прохождения медицинского осмотра
            ) {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $workers = $post_dec->workers;                                                                              // список работников получающих отметку
            $date_worker_examination = $post_dec->date_worker_examination;                                              // дата получения отметки

            foreach ($workers as $worker) {
                $med_report = MedReport::findOne(['worker_id' => $worker->worker_id, 'physical_worker_date' => $date_worker_examination]);

                if (!$med_report) {

                    $med_report = new MedReport();
                    $med_report->worker_id = $worker->worker_id;
                    $med_report->position_id = $worker->position_id;
                    $med_report->physical_worker_date = $date_worker_examination;
                    $med_report->company_department_id = $worker->company_department_id;
//                    $med_report->med_report_date = $date_worker_examination;

                    if ($med_report->save()) {
                        $med_report->refresh();
                        $med_report_id = $med_report->id;
                        $result[$med_report_id]['medical_examination_id'] = $med_report_id;
                        $result[$med_report_id]['company_department_id'] = $worker->company_department_id;
                        $result[$med_report_id]['worker_id'] = $worker->worker_id;
                        $result[$med_report_id]['position_id'] = $worker->position_id;
                        $result[$med_report_id]['date_examination'] = "";
                        $result[$med_report_id]['date_examination_format'] = "";
                        $result[$med_report_id]['date_next'] = "";
                        $result[$med_report_id]['comment_result'] = "";
                        $result[$med_report_id]['date_worker_examination'] = $date_worker_examination;
                        $result[$med_report_id]['date_worker_examination_format'] = date('d.m.Y', strtotime($date_worker_examination));
                        $result[$med_report_id]['disease_id'] = null;
                        $result[$med_report_id]['medical_report_result_id'] = null;
                        $result[$med_report_id]['attachment_id'] = null;
                        $result[$med_report_id]['attachment_path'] = "";
                        $result[$med_report_id]['classifier_diseases_id'] = null;
                        $result[$med_report_id]['attachment'] = (object)array();
                        $warnings[] = $method_name . '. Данные успешно сохранены в модель MedReport';
                    } else {
                        $errors[] = $med_report->errors;
                        throw new Exception($method_name . '. Ошибка сохранения модели MedReport');
                    }
                } else {
                    $warnings[] = $method_name . '. Отметка о прохождении медицинского осмотра для работника уже существовала';
                }
                unset($med_report);
            }


            /** Отладка */
            $description = 'Закончил основной метод метода';                                                                      // описание текущей отладочной точки
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

            /** Метод окончание */
        } catch (Throwable $ex) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
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
//        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));                       // время окончания выполнения метода
//        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//            $date_time_debug_start, $date_time_debug_end, $log_id,
//            $duration_summary, $max_memory_peak, $count_all);

        return array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
    }

    /**
     * Название метода: GetWorkerInfoPersonalCard()
     * Метод получения информации по конкретному сотруднику для личного кабинета на мобильном устройстве
     * http://127.0.0.1/read-manager-amicum?controller=PhysicalSchedule&method=GetWorkerInfoPersonalCard&subscribe=&data={%22worker_id%22:1801}
     * @param
     * @return array
     * @package frontend\controllers\handbooks
     * @example
     *
     * Документация на портале:
     * @author Якимов М.Н.
     * Created date: on 10.06.2019 17:35
     * @since ver
     *
     * @comment - перенесено из handbookEmployee для получения конкретных данных по сотруднику
     */
    public static function GetWorkerInfoPersonalCard($data_post)
    {
        $result = array();                                                                                              // промежуточный результирующий массив
        $errors = array();                                                                                              // массив ошибок
        $warnings = array();                                                                                            // массив предупреждений
        $status = 1;

        $warnings[] = 'GetWorkerInfoPersonalCard. Зашел в метод';
        try {
            $data_post = json_decode($data_post);
            if (
                !property_exists($data_post, 'worker_id')
            ) {
                throw new Exception("GetWorkerInfoPersonalCard. Не передан входной параметр worker_id");
            }

            $worker_id = $data_post->worker_id;
            $warnings[] = "GetWorkerInfoPersonalCard. Входные данные получены worker_id = $worker_id";

            $worker = Worker::find()
                ->with('position')
                ->with('department')
                ->with('employee')
                ->with('workerObjects')
                ->where('id=' . $worker_id)
                ->limit(1)
                ->one();
            if ($worker) {
                /**
                 * Блок обработки базовых свойств работника
                 */
//                $worker_object_id = $worker->workerObjects[0]->id;
                $worker_personal_card['worker_id'] = $worker_id;
                $worker_personal_card['stuff_number'] = $worker->tabel_number;
//                $worker_personal_card[$worker_id]['last_name'] = $worker->employee->last_name;
//                $worker_personal_card[$worker_id]['first_name'] = $worker->employee->first_name;
//                $worker_personal_card[$worker_id]['patronymic'] = $worker->employee->patronymic;
//                $worker_personal_card[$worker_id]['full_name'] = $worker_personal_card[$worker_id]['last_name'] . " " . $worker_personal_card[$worker_id]['first_name'] . " " . $worker_personal_card[$worker_id]['patronymic'];
                $worker_personal_card['gender'] = $worker->employee->gender;
                $worker_personal_card['birthdate'] = $worker->employee->birthdate;
                $worker_personal_card['position_title'] = $worker->position->title;
                $worker_personal_card['position_id'] = $worker->position->id;
//                $worker_personal_card[$worker_id]['department_title'] = $worker->department->title;
//                $worker_personal_card[$worker_id]['worker_object_id'] = $worker_object_id;

                $result = $worker_personal_card;
                unset($worker_personal_card);
            } else {
                throw new Exception("GetWorkerInfoPersonalCard. Искомого работника в БД не найдено. ключ: $worker_id");
            }
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = "GetWorkerInfoPersonalCard. Исключение ";                                                                                // Добавляем в массив ошибок, полученную ошибку
            $errors[] = $e->getMessage();                                                                                // Добавляем в массив ошибок, полученную ошибку
            $errors[] = $e->getLine();                                                                                // Добавляем в массив ошибок, полученную ошибку
        }
        $warnings[] = "GetWorkerInfoPersonalCard. Вышел с метода";
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод HarmfulFactorsByContingents() -Получение факторов сгруппированных по участку и роли (контингент = участок + роль)
     * @return array - структура выходного массива: [company_department_id]
     *                                                          [role_id]
     *                                                               [harmful_factor_id]
     *                                                                      harmful_factor_id:
     *                                                                      harmful_factor_title:
     *                                                                      harmful_factor_period:
     *
     * @package frontend\controllers
     *
     * @example http://amicum/read-manager-amicum?controller=PhysicalSchedule&method=HarmfulFactorsByContingents&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 13.02.2020 8:41
     */
    public static function HarmfulFactorsByContingents()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'HarmfulFactorsByContingents';
        $factors_comp_dep_roles = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            $year = date('Y', strtotime(BackendAssistant::GetDateNow()));
            $contigets_factor = Contingent::find()
                ->joinWith('factorsOfContingents.harmfulFactors')
                ->where(['year_contingent' => $year])
                ->all();
            if (!empty($contigets_factor)) {
                foreach ($contigets_factor as $contiget_factor) {
                    $comp_dep_id = $contiget_factor->company_department_id;
                    $role_id = $contiget_factor->role_id;
                    foreach ($contiget_factor->factorsOfContingents as $factorsOfContingent) {
                        $harmful_factor_id = $factorsOfContingent->harmful_factors_id;
                        $factors_comp_dep_roles[$comp_dep_id][$role_id][$harmful_factor_id]['harmful_factor_id'] = $harmful_factor_id;
                        $factors_comp_dep_roles[$comp_dep_id][$role_id][$harmful_factor_id]['harmful_factor_title'] = $factorsOfContingent->harmfulFactors->title;
                        $factors_comp_dep_roles[$comp_dep_id][$role_id][$harmful_factor_id]['harmful_factor_period'] = $factorsOfContingent->harmfulFactors->period;
                    }
                }
                unset($contigets_factor);
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $factors_comp_dep_roles;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    /**
     * Метод GetHarmfulFactors() - Метод получения вредных факторов
     * @return array - массив вида:
     *                              [Items]
     *                                  [harmful_factor_id]                                                             - идентификатор вредного фактора
     *                                              id:                                                                 - идентификатор вредного фактора
     *                                              title:                                                              - наименование вредного фактора
     *                              status:                                                                             - статус выполнения метода
     *                              [warnings]                                                                          - массив предупреждений (ход выполнения метода)
     *                              [errors]                                                                            - массив ошибок
     *
     * @package frontend\controllers
     *
     * @example http://amicum/read-manager-amicum?controller=PhysicalSchedule&method=GetHarmfulFactors&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 06.03.2020 14:40
     */
    public static function GetHarmfulFactors()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetHarmfulFactors';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $result = HarmfulFactors::find()
                ->indexBy('id')
                ->all();
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
     * Метод PreviewContingentFromContingetSout() - Превью данных который надо заполнить в контингенте на основе контингента из СОУТ
     * @param null $data_post - JSON c идентификатором участка
     * @return array
     * ПРИМЕР ВХОДНЫЪ ДАННЫХ
     * {
     *    "company_department_id":20028766
     * }
     *
     *
     *
     * @package frontend\controllers
     *
     * @example http://amicum/read-manager-amicum?controller=PhysicalSchedule&method=PreviewContingentFromContingetSout&subscribe=&data={"company_department_id":20028766}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 06.03.2020 15:23
     */
    public static function PreviewContingentFromContingetSout($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'PreviewContingentFromContingetSout';
        $preview_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'date'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;
            $date = date('Y-m-d', strtotime($post_dec->date));                                                   // форматируем входную дату

            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $warnings[] = $response['warnings'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . '. Ошибка при получении вложенных участков');
            }
            $contingents_sout = ContingentFromSout::find()
                ->joinWith('companyDepartment.company')
                ->joinWith('role')
                ->joinWith('sout')
                ->joinWith('contingentHarmfulFactorSouts.harmfulFactors')
                ->where(['in', 'contingent_from_sout.company_department_id', $company_departments])
                ->andWhere(['<=', 'sout.date', $date])
                ->all();
            if (!empty($contingents_sout)) {
                foreach ($contingents_sout as $contingent_sout) {
                    $role_id = $contingent_sout->role_id;
                    $role_title = $contingent_sout->role->title;
                    $cont_comp_dep_id = $contingent_sout->company_department_id;
                    $cont_comp_dep_title = $contingent_sout->companyDepartment->company->title;
                    $preview_data[$cont_comp_dep_id]['company_department_id'] = $cont_comp_dep_id;
                    $preview_data[$cont_comp_dep_id]['company_title'] = $cont_comp_dep_title;
                    $preview_data[$cont_comp_dep_id]['roles'][$role_id]['role_id'] = $role_id;
                    $preview_data[$cont_comp_dep_id]['roles'][$role_id]['surface_underground'] = $contingent_sout->role->surface_underground;
                    $preview_data[$cont_comp_dep_id]['roles'][$role_id]['role_title'] = $role_title;
                    $preview_data[$cont_comp_dep_id]['roles'][$role_id]['harmful_factors'] = array();
                    foreach ($contingent_sout->contingentHarmfulFactorSouts as $contingentHarmfulFactorSout) {
                        $factor_id = $contingentHarmfulFactorSout->harmfulFactors->id;
                        $factor_title = $contingentHarmfulFactorSout->harmfulFactors->title;
                        $preview_data[$cont_comp_dep_id]['roles'][$role_id]['harmful_factors'][$factor_id]['harmful_factors_id'] = $factor_id;
                        $preview_data[$cont_comp_dep_id]['roles'][$role_id]['harmful_factors'][$factor_id]['harmful_factors_title'] = $factor_title;
                    }
                    if (empty($preview_data[$cont_comp_dep_id]['roles'][$role_id]['harmful_factors'])) {
                        $preview_data[$cont_comp_dep_id]['roles'][$role_id]['harmful_factors'] = (object)array();
                    }
                }
            } else {
                $errors[] = 'Нечего показывать контингент СОУТа пустой';
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $preview_data;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод FillContingentFromContingetSout() - Заполнение контингента МО из континтингента СОУТ
     * @param null $data_post
     * @return array
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * {
     *      "company_department_id": 20028748,                                                                          - участок для которого нужно заменить контингент из контингента СОУТ (выбираются этот участок и все низлежащие)
     *      "date": "2020-03-31"                                                                                        - до какой даты получать список СОУТ
     * }
     *
     * ВЫХОДНЫЙ ПАРАМЕТРЫ:
     * {
     *      "Items": {
     *          "4": {                                                                                                  - идентификатор контингента СОУТ
     *              "company_department_id": 20028748,                                                                  - идентификатор участка
     *              "role_id": 193,                                                                                     - идентификатор роли
     *              "factors": [5, 3]                                                                                   - массив вредных факторов для этой роли
     *          }
     *      },
     *      "status": 1,                                                                                                - статус выполнения метода
     *      "errors": [],                                                                                               - массив ошибок
     *      "warnings": {}                                                                                              - массив предупреждений (ход выполнения метода)
     *      "debug": []                                                                                                 - отладочная информация
     *  }
     *
     * @package frontend\controllers
     *
     * @example http://amicum/read-manager-amicum?controller=PhysicalSchedule&method=FillContingentFromContingetSout&subscribe=&data={"company_department_id":20028748,"date":"2020-05-06"}
     * АЛГОРИТМ МЕТОДА:
     * 1. получить идентификатор учатка
     * 2. получить все вложенные участки
     * 3. найти все контингенты СОУТ по всем найденным участкам
     * 4. сгруппировать факторы по контингенту, по следующему виду
     *                                                              [контингент]
     *                                                                      идентификатор участка
     *                                                                      идентификатор роли
     *                                                                      [
     *                                                                       фактор1,
     *                                                                       фактор2,
     *                                                                       фактор3
     *                                                                      ]
     * 5. перебор полученного массива
     *      5.1 Обновление статуса у всех контингентов по условию:
     *          учасок = участку из контингента СОУТ
     *          роль =  роли из контингента СОУТ
     *      5.2 Создание нового контингента с данными:
     *              роль = роли из контингента СОУТ
     *              участок =  участку из контингента СОУТ
     *              год =  году из переданной даты
     *              период =  12
     *              статус =  1 (АКТУАЛЬНО)
     *      5.3 Перебор массива факторов
     *              5.2.1           Добавляем в массив по идентификатору контингента фактор
     *              5.2.2           Увеличиваем счётчик на 1
     *      5.4 Конец перебора факторов
     *      5.5 Счтётчик больше либо равен 2 000?
     *              да?             Массово добавить факторы контингента
     *                              Очистить массив на добавление
     *                              Очистить счётчик
     *              нет?            Пропустить
     * 6. Конец перебора
     * 7. В массиве на добавление есть данные?
     *      да?     Массово добавить факторы контингента
     *      нет?    Пропустить
     *
     *
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 10.03.2020 11:19
     */
    public static function FillContingentFromContingetSout($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'FillContingentFromContingetSout';
        $contingents_from_sout = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        $count_add = 0;
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'date'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;
            $date = date('Y-m-d', strtotime($post_dec->date));                                                   // форматируем входную дату
            /**
             * Получаем все вложенные участки
             */
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $warnings[] = $response['warnings'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . '. Ошибка при получении вложенных участков');
            }
            /**
             * Получаем все контингенты СОУТ по условиям:
             *  1. Идентификатор участка входит в массив вложенных участков
             *  2. Дата прохождения СОУТ больше либо равна переданной
             */
            $sout_contingents = ContingentFromSout::find()
                ->joinWith('contingentHarmfulFactorSouts')
                ->joinWith('sout')
                ->where(['in', 'contingent_from_sout.company_department_id', $company_departments])
                ->andWhere(['<=', 'sout.date', $date])
                ->all();
            if (!empty($sout_contingents)) {
                /**
                 * Перебираем найденный массив формируя структуру:
                 *  [контингент]
                 *      идентификатор участка
                 *      идентификатор роли
                 *      [
                 *           фактор1,
                 *           фактор2,
                 *           фактор3
                 *      ]
                 */
                foreach ($sout_contingents as $sout_contingent) {
                    $sout_contingent_id = $sout_contingent->id;
                    $contingents_from_sout[$sout_contingent_id]['company_department_id'] = $sout_contingent->company_department_id;
                    $contingents_from_sout[$sout_contingent_id]['role_id'] = $sout_contingent->role_id;
                    $contingents_from_sout[$sout_contingent_id]['factors'] = array();
                    foreach ($sout_contingent->contingentHarmfulFactorSouts as $contingentHarmfulFactorSout) {
                        $contingents_from_sout[$sout_contingent_id]['factors'][] = $contingentHarmfulFactorSout->harmful_factors_id;
                    }
                }
            }
            if (!empty($contingents_from_sout)) {
                /**
                 * Перебираем сформированный массив
                 */
                foreach ($contingents_from_sout as $contingent_sout) {
                    /**
                     * Обновление всех статусов контингентов по условию:
                     *      учасок = участку из контингента СОУТ
                     *      роль =  роли из контингента СОУТ
                     */
                    $contingent = Contingent::updateAll(['status' => 0], ['company_department_id' => $contingent_sout['company_department_id'], 'role_id' => $contingent_sout['role_id']]);
                    unset($contingent);
                    /**
                     * Создание нового контингента с статусом АКТУАЛЬНО (1)
                     */
                    $contingent_new = new Contingent();
                    $contingent_new->company_department_id = $contingent_sout['company_department_id'];
                    $contingent_new->role_id = $contingent_sout['role_id'];
                    $contingent_new->period = 12;
                    $contingent_new->year_contingent = date('Y', strtotime($date));
                    $contingent_new->status = 1;
                    if ($contingent_new->save()) {
                        $warnings[] = $method_name . '. Контингент успешно сохранён';
                    } else {
                        $errors[] = $contingent_new->errors;
                        throw new Exception($method_name . '. Ошибка  при сохранении контигента');
                    }
                    $contingent_id = $contingent_new->id;
                    unset($contingent_new);
                    /**
                     * Очищаем вредные факторы контингента
                     */
                    FactorsOfContingent::deleteAll(['contingent_id' => $contingent_id]);
                    /**
                     * Перебираем массив факторов контингента СОУТ и формируем массив на добавление
                     */
                    foreach ($contingent_sout['factors'] as $factor) {
                        $harmful_factor_contingents[] = [$contingent_id, $factor];
                        $count_add++;
                    }
                    /**
                     * Записей больше либо равно 2 000
                     *      да?     Массово добавить факторы для контингента
                     *              Обнулить массив факторов контингента
                     *              Обнулить счётчик
                     *      нет?    Пропустить
                     */
                    if ($count_add >= 2000) {
                        $batch_harmful_factors = Yii::$app->db->createCommand()->batchInsert('factors_of_contingent',
                            ['contingent_id', 'harmful_factors_id'], $harmful_factor_contingents)
                            ->execute();
                        if ($batch_harmful_factors == 0) {
                            throw new Exception($method_name . '. Ошибка при сохранении факторов контингента');
                        }
                        $harmful_factor_contingents = array();
                        $count_add = 0;
                    }
                }
                /**
                 * Массив факторов контингента пуст?
                 *      да?     Пропустить
                 *      нет?    Массово добавить факторы для контингента
                 */
                if (!empty($harmful_factor_contingents)) {
                    $batch_harmful_factors = Yii::$app->db->createCommand()->batchInsert('factors_of_contingent',
                        ['contingent_id', 'harmful_factors_id'], $harmful_factor_contingents)
                        ->execute();
                    if ($batch_harmful_factors == 0) {
                        throw new Exception($method_name . '. Ошибка при сохранении остатка факторов контингента');
                    }
                }
                unset($harmful_factor_contingents);
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $contingents_from_sout;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    // GetGroupMedReportResult()      - Получение справочника групп медицинских заключений
    // SaveGroupMedReportResult()     - Сохранение справочника групп медицинских заключений
    // DeleteGroupMedReportResult()   - Удаление справочника групп медицинских заключений

    /**
     * Метод GetGroupMedReportResult() - Получение справочника групп медицинских заключений
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                        // ключ справочника групп медицинских заключений
     *      "title":"ACTION",               // название группы медицинских заключений
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=PhysicalSchedule&method=GetGroupMedReportResult&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetGroupMedReportResult()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetGroupMedReportResult';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_group_med_report_result = GroupMedReportResult::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_group_med_report_result)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник групп медицинских заключений пуст';
            } else {
                $result = $handbook_group_med_report_result;
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
     * Метод SaveGroupMedReportResult() - Сохранение справочника групп медицинских заключений
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "group_med_report_result":
     *  {
     *      "group_med_report_result_id":-1,            // ключ справочника групп медицинских заключений
     *      "title":"ACTION",                           // название группы медицинских заключений
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "group_med_report_result_id":-1,            // ключ справочника групп медицинских заключений
     *      "title":"ACTION",                           // название группы медицинских заключений
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=PhysicalSchedule&method=SaveGroupMedReportResult&subscribe=&data={"group_med_report_result":{"group_med_report_result_id":-1,"title":"ACTION"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveGroupMedReportResult($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveGroupMedReportResult';
        $handbook_group_med_report_result_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'group_med_report_result'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_group_med_report_result_id = $post_dec->group_med_report_result->group_med_report_result_id;
            $title = $post_dec->group_med_report_result->title;
            $new_handbook_group_med_report_result_id = GroupMedReportResult::findOne(['id' => $handbook_group_med_report_result_id]);
            if (empty($new_handbook_group_med_report_result_id)) {
                $new_handbook_group_med_report_result_id = new GroupMedReportResult();
            }
            $new_handbook_group_med_report_result_id->title = $title;
            if ($new_handbook_group_med_report_result_id->save()) {
                $new_handbook_group_med_report_result_id->refresh();
                $handbook_group_med_report_result_data['group_med_report_result_id'] = $new_handbook_group_med_report_result_id->id;
                $handbook_group_med_report_result_data['title'] = $new_handbook_group_med_report_result_id->title;
            } else {
                $errors[] = $new_handbook_group_med_report_result_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника групп медицинских заключений');
            }
            unset($new_handbook_group_med_report_result_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_group_med_report_result_data;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод DeleteGroupMedReportResult() - Удаление справочника групп медицинских заключений
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "group_med_report_result_id": 98             // идентификатор справочника групп медицинских заключений
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=PhysicalSchedule&method=DeleteGroupMedReportResult&subscribe=&data={"group_med_report_result_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteGroupMedReportResult($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $post_dec = (object)array();                                                                                              // Массив ошибок
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteGroupMedReportResult';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'group_med_report_result_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_group_med_report_result_id = $post_dec->group_med_report_result_id;
            GroupMedReportResult::deleteAll(['id' => $handbook_group_med_report_result_id]);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';

        return array('Items' => $post_dec, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }
}
