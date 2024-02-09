<?php
/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\controllers\ordersystem;

use backend\controllers\Assistant;
use backend\controllers\Assistant as BackendAssistant;
use backend\controllers\cachemanagers\WorkerCacheController;
use backend\controllers\WorkerMainController;
use DateTime;
use Exception;
use frontend\controllers\handbooks\BrigadeController;
use frontend\controllers\industrial_safety\CheckingController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Briefer;
use frontend\models\Brigade;
use frontend\models\BrigadeWorker;
use frontend\models\Chane;
use frontend\models\ChaneWorker;
use frontend\models\Checking;
use frontend\models\CheckKnowledge;
use frontend\models\Contingent;
use frontend\models\Employee;
use frontend\models\EventPbWorker;
use frontend\models\GraficChaneTable;
use frontend\models\GraficTabelDateFact;
use frontend\models\GraficTabelDatePlan;
use frontend\models\GraficTabelMain;
use frontend\models\KindWorkingTime;
use frontend\models\MedReport;
use frontend\models\OccupationalIllness;
use frontend\models\Role;
use frontend\models\Shift;
use frontend\models\ViewEmployeeLastWorkerInfo;
use frontend\models\ViewGetSummaryWorkerByDayAndMonth;
use frontend\models\ViewInitWorkerParameterHandbookValue;
use frontend\models\Worker;
use frontend\models\WorkerObject;
use frontend\models\WorkerParameterCalcValue;
use frontend\models\WorkerSiz;
use frontend\models\WorkingTime;
use Throwable;
use Yii;
use yii\db\Query;
use yii\web\Controller;
use yii\web\Response;

class WorkScheduleController extends Controller
{
    /** Документация http://192.168.1.3/products/files/doceditor.aspx?fileid=20771 */
    // GetGraphicMain                   - получить график main
    // GetListCompanyDepartment         - Метод получения списка работников по участку
    // AddGraficTabelMain               - Метод добавления grafic_tabel_main, нового графика
    // GetGraphicTabel                  - Метод получения графика выходов, блока с выходами по дням
    // FindBrigade                      - Метод получения списка бригад по конкретному департаменту
    // GetTemplate                      - Метод получения шаблона построения графика по месяцу, бригаде, году
    // SaveGraphic                      - Метод сохранения графика выходов
    // SaveGraphicv2                    - Метод сохранения графика выходов v2
    // actionBrigadeChaneMain           - Метод получения подробной информации о звеньях в бригаде
    // GetMineDepartment                - Метод возвращает список участков по идентификатору шахты
    // GetListWorkerChaneForReadManager - Этот метод получает спиоск людей по заданной бригаде использвется при построении графика для распределения людей по вкладкам
    // ListWorkerCompanyDepartment      - Этот метод получает список работников по переданному идентификатору (company_department_id) применяется на вкладке персонала
    // funcListWorkerCompanyDepartment  - Этот метод получает список работников по переданному идентификатору (company_department_id) применяется как внутренний метод
    // GetSummaryWorkerByDayAndMonth    - Метод получения сводной информации по количеству работников на выход по дням и сменам по конкретному департаменту на конкретный год и месяц
    // GetListBrigade                   - Метод получения списка бригад по конкретному департаменту, если задан, то вернет весь список актуальных бригад
    // GetBrigades                      - ВНУТРЕННИЙ Метод получения списка бригад по конкретному департаменту, если задан, то вернет весь список актуальных бригад
    // GetWorkingTimeList               - метод получения списка рабочего времени (явка, больничный, отпуск)
    // GetKindWorkingTimeList           - метод получения списка рабочего времени (явка, больничный, отпуск)
    // GetShiftList                     - метод получения списка смен
    // GetRoleList                      - метод получения списка ролей
    // GetGraphicWorkerCard             - Получение графика выходов конкретного работника в карточке сотрудника за конкретный год, месяц
    // GetWorkerCard                    - Метод формирования карточки сотрудника, получения параметров
    // SaveRole                         - метод сохранения справочника ролей
    // ContinueGraphicToNextMonth       - Продолжение графика выходов на следующий месяц на основе последних 4 дней

    const PARAMETER_BREATHLAYZER = 685;
    const TYPE_BRIEFING_INTERSHIP = 8;
    const TYPE_BRIEFING_REPEATED_INSTUCTION = 2;


    /**
     * Название метода: actionIndex()
     * @return string
     *
     * Входные необязательные параметры
     *
     * Тестовые данные:
     * http://localhost/order-system/work-schedule?department_id=4029857
     *
     * @url
     *
     * @throws \yii\db\Exception
     * Документация на портале:
     * @package app\controllers\ordersystem
     *
     * Входные обязательные параметры:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 07.02.2019 16:54
     * @since ver 0.3
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Название метода: GetGraphic()
     *
     * @param null $data_post
     * @package frontend\controllers\ordersystem
     * @example http://web.amicum/read-manager-amicum?controller=ordersystem\WorkSchedule&method=GetGraphicMain&subscribe=login&data={"company_department_id":"801","year":"2019","month":"5","brigade_id":"46"}
     *
     * Входные параметры:
     *  - company_department_id - идентификатор компании
     *  - year - год
     *  - month - месяц
     *  - brigade_id - идентификатор бригады
     *
     * На выходе получаем варианты
     *
     * Алгоритм работы метода:
     *
     * 1. Получаем и проверяем входные параметры;
     * 2. Проверяем наличие графика по полученным полям: год, месяц, участок, бригада
     * 3. Если график такой уже имееся, получаем список работников в бригаде, выхода работников
     * 4. Иначе проверяем наличие графика за предыдущий месяц, получаем список работников в бригаде, выхода работников
     * 5. Если и за прошлый месяц данных не было, добавляем бригаду, новый график
     * 6. Получаем список работников на участке
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 25.05.2019 18:51
     * @since ver
     */
    public static function GetGraphicMain($data_post = NULL)
    {
        $log = new LogAmicumFront("GetGraphicMain");
        $result['ListBrigadeWithChane'] = (object)array();                                                              // Промежуточный результирующий объект СПИОК БРИГАД/ЗВЕНЬЕВ И РАБОТНИКОВ В НИХ в Т.Ч. бригадир и звеньевой
        $result['ListGrafic'] = (object)array();                                                                        // Промежуточный результирующий объект СПИСОК ГРАФИКОВ ВЫХОДОВ НА КОНКРЕТНОМ ДЕПАРТАМЕНТЕ
        $result['ListWorkers'] = (object)array();                                                                       // Промежуточный результирующий объект СПИСОК РАБОТНИКОВ НА КОНКРЕТНОМ УЧАСТКЕ
        $result['WorkerInChane'] = (object)array();                                                                     // Промежуточный результирующий объект СПИСОК РАБОТНИКОВ С УКАЗАНИЕМ ПРИНАДЛЕЖНОСТИ К ЗВЕНУ
        $result['WorkersVgk'] = (object)array();                                                                        // Промежуточный результирующий объект СПИСОК РАБОТНИКОВ С УКАЗАНИЕМ ПРИНАДЛЕЖНОСТИ К ЗВЕНУ

        $log->addLog("Начало выполнения метода");

        $log->addData($data_post, '$data_post', __LINE__);

        try {
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            // Проверяем переданы ли все параметры
            if (
                property_exists($post_dec, 'company_department_id') &&
                property_exists($post_dec, 'year') &&
                property_exists($post_dec, 'month')
            )                                                                                                       // Проверяем наличие в нем нужных нам полей
            {

                $company_department_id = $post_dec->company_department_id;
                $year = $post_dec->year;
                $month = $post_dec->month;

                $log->addLog("Обработал входные данные");
                /**
                 * получаем список всех работников ВГК на шахте
                 */
                $workers_vgk = Worker::find()->select(['id', 'vgk'])->where('vgk = 1')->asArray()->indexBy('id')->all();
                if ($workers_vgk) {
                    $result['WorkersVgk'] = $workers_vgk;
                }

                $log->addLog("Получил список ВГК");

                /**
                 * проверяем наличие графика выходов на запрашиваемый месяц и если не найден на запрашиваемый месяц
                 * то ищем график на предыдущий месяц дабы получить данные для построения текущего месяца
                 */
                $response = self::GetGraphicTabel($company_department_id, $year, $month);     // График на предыдущий месяц
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception("Ошибка получения графика выходов");
                }
                $result['ListGrafic'] = $response['Items'];
                $worker_list_in_grapfic = $response['worker_list_in_grapfic'];
//$log->addData($worker_list_in_grapfic,'$worker_list_in_grapfic',__LINE__);
                $log->addLog("Получил графики выходов");

                /**
                 * Блок поиска сведений о бригадах и звеньях и т.д.
                 * берем все бригады что есть у данного департамента
                 */
                $response = self::GetBrigades($company_department_id, 1);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception("Ошибка получения списка бригад со звеньями для выпадашки в графике выходов и для групп работников");
                }

                $result['ListBrigadeWithChane'] = $response['Items'];
                $result['WorkerInChane'] = $response['worker_in_chane'];
                $result['workers'] = $response['workers'];

                $worker_list_in_grapfic = array_merge($worker_list_in_grapfic, $response['worker_list_in_grapfic']);
//                $log->addData($worker_list_in_grapfic,'$worker_list_in_grapfic',__LINE__);
                $log->addLog("Получил список бригад и звеньев");
                /**
                 * Блок получения списка людей в конкретном выбранном департаменте
                 */
                $response = self::funcListWorkerCompanyDepartment($company_department_id, $worker_list_in_grapfic);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception("Ошибка получения списка работников конкретного департамента");
                }


                if ($response['no_worker'] == 0) {
                    $result['ListWorkers'] = $response['Items']['workers'];
                } else {
                    $result['ListWorkers'] = $response['Items'];
                }
//                foreach ($result['ListWorkers'] as $key=>$worker) {
//                    if(!isset($result['workers'][$key])) {
//                        $result['workers'][$key] = $worker;
//                    }
//                }

                $log->addLog("Получил справочник людей в графике выходов");

            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Закончил выполнение метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * Метод GetGraphicWorkerCard() - получение графика выходов конкретного работника в карточке сотрудника за конкретный год, месяц
     *
     * @param null $data_post
     * @package frontend\controllers\ordersystem
     * @example Данных по графику нет: /read-manager-amicum?controller=ordersystem\WorkSchedule&method=GetGraphicWorkerCard&subscribe=login&data={"year":"2019","worker_id":"2021940","month":"6"}
     *          Данные по графику есть: http://web.amicum/read-manager-amicum?controller=ordersystem\WorkSchedule&method=GetGraphicWorkerCard&subscribe=login&data={%22year%22:%222019%22,%22worker_id%22:%222045122%22,%22month%22:%226%22}
     *
     * Входные параметры:
     *  - year - год
     *  - month - месяц
     *  - worker_id - идентификатор работника для которого нужно построить график
     *
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 07.06.2019 10:05
     */
    public static function GetGraphicWorkerCard($data_post = NULL)
    {
        $status = 1;                                                                                                      // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();                                                                                                // Промежуточный результирующий массив
        if ($data_post !== NULL && $data_post !== '') {
            $warnings[] = 'GetGraphicWorkerCard. Данные успешно переданы';
            $warnings[] = 'GetGraphicWorkerCard. Входной массив данных' . $data_post;
            try {
                $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
                $warnings[] = 'GetGraphicWorkerCard. Декодировал входные параметры';
                if (                                                                                                    // Проверяем переданы ли все параметры
                    property_exists($post_dec, 'year') &&
                    property_exists($post_dec, 'month') &&
                    property_exists($post_dec, 'worker_id')
                )                                                                                                       // Проверяем наличие в нем нужных нам полей
                {
                    $warnings[] = 'GetGraphicWorkerCard. Данные с фронта получены';
                    $current_year = (int)$post_dec->year;                                                               // Год
                    $current_month = (int)$post_dec->month;                                                             // Месяц
                    $worker_id = (int)$post_dec->worker_id;                                                             // Идентификатор работника
                    $count_days_in_current_month = cal_days_in_month(CAL_GREGORIAN, $current_month, $current_year);

                    /******************** Заполняем пустыми данными дни текущего месяца ********************/
                    for ($i = 1; $i <= $count_days_in_current_month; $i++) {
                        $worker_card_grafic['plan_schedule'][$i]['day'] = $i;
                        $worker_card_grafic['fact_schedule'][$i]['day'] = $i;
                    }

                    $worker_card_grafic['worker_id'] = $worker_id;

                    /**
                     * формируем справочник Working_time  в удобном виде для последующего поиска
                     */
                    $working_time_list = WorkingTime::find()
                        ->asArray()
                        ->limit(200)
                        ->all();
                    foreach ($working_time_list as $working_times) {
                        $working_time_array[$working_times['id']] = $working_times['short_title'];
                    }

                    /**
                     * Формируем справочник Shift  в удобном виде для последующего поиска
                     */
                    $shift_list = Shift::find()
                        ->asArray()
                        ->limit(200)
                        ->all();
                    foreach ($shift_list as $shift) {
                        $shift_array[$shift['id']] = $shift['title'];
                    }


                    /******************** Находим график по плану ********************/

                    $graphics = (new Query())
                        ->select([
                            'day',
                            'worker_id',
                            'working_time_id',
                            'shift_id',
                            'hours_value',
                            'grafic_tabel_date_plan.month',
                            'grafic_tabel_date_plan.year',
                        ])
                        ->from('grafic_tabel_main')
                        ->innerJoin('grafic_tabel_date_plan', 'grafic_tabel_main.id=grafic_tabel_date_plan.grafic_tabel_main_id')
                        ->Where(['grafic_tabel_date_plan.worker_id' => $worker_id])// Для конкретного работника
                        ->andWhere('grafic_tabel_date_plan.working_time_id != 25')
                        ->andWhere(['grafic_tabel_date_plan.month' => $current_month])
                        ->andWhere(['grafic_tabel_date_plan.year' => $current_year])
                        ->andWhere(['grafic_tabel_main.status_id' => 1])
                        ->limit(6000)
                        ->all();

                    $warnings[] = 'GetGraphicWorkerCard. Выполнение запроса в БД';                                      // Получаем данные из запроса
                    $worker_card_grafic['sum_plan_value'] = 0;
                    $worker_card_grafic['sum_fact_value'] = 0;
                    foreach ($graphics as $grafic)                                                                      // Перебор полученного графика
                    {
                        $worker_card_grafic['plan_schedule'][$grafic['day']]['day'] = $grafic['day'];
                        $worker_card_grafic['plan_schedule'][$grafic['day']]['working_time'][$grafic['working_time_id']]['working_time_id'] = $grafic['working_time_id'];
                        $worker_card_grafic['plan_schedule'][$grafic['day']]['working_time'][$grafic['working_time_id']]['working_time_title'] = $working_time_array[$grafic['working_time_id']];
                        if ($grafic['working_time_id'] == 1 and $grafic['shift_id'] != 5) {
                            $worker_card_grafic['plan_schedule'][$grafic['day']]['working_time'][$grafic['working_time_id']]['shift'][$grafic['shift_id']]['shift_id'] = $grafic['shift_id'];
                        }
                    }

                    /******************** Находим график выходов по факту ********************/

                    $graphics = (new Query())
                        ->select([
                            'day',
                            'worker_id',
                            'working_time_id',
                            'shift_id',
                            'hours_value',
                            'grafic_tabel_date_fact.month',
                            'grafic_tabel_date_fact.year',
                        ])
                        ->from('grafic_tabel_main')
                        ->innerJoin('grafic_tabel_date_fact', 'grafic_tabel_main.id=grafic_tabel_date_fact.grafic_tabel_main_id')
                        ->Where(['grafic_tabel_date_fact.worker_id' => $worker_id])// Для конкретного работника
                        ->andWhere('grafic_tabel_date_fact.working_time_id != 25')
                        ->andWhere(['grafic_tabel_date_fact.month' => $current_month])
                        ->andWhere(['grafic_tabel_date_fact.year' => $current_year])
                        ->andWhere(['grafic_tabel_main.status_id' => 1])
                        ->limit(6000)
                        ->all();
                    $warnings[] = 'GetGraphicWorkerCard. Выполнение запроса в БД';                                      // Получаем данные из запроса

                    foreach ($graphics as $grafic)                                                                      // Перебор полученного графика
                    {
                        $worker_card_grafic['fact_schedule'][$grafic['day']]['day'] = $grafic['day'];
                        $worker_card_grafic['fact_schedule'][$grafic['day']]['working_time'][$grafic['working_time_id']]['working_time_id'] = $grafic['working_time_id'];
                        $worker_card_grafic['fact_schedule'][$grafic['day']]['working_time'][$grafic['working_time_id']]['working_time_title'] = $working_time_array[$grafic['working_time_id']];
                        if ($grafic['working_time_id'] == 1 and $grafic['shift_id'] != 5) {
                            $worker_card_grafic['fact_schedule'][$grafic['day']]['working_time'][$grafic['working_time_id']]['shift'][$grafic['shift_id']]['shift_id'] = $grafic['shift_id'];
                            $worker_card_grafic['sum_fact_value'] += $grafic['hours_value'];
                        }
                    }

                    $result = $worker_card_grafic;
                    $warnings[] = 'GetGraphicWorkerCard. Данные работника сгруппированны';
                }
            } catch (Throwable $exception) {
                $status = 0;
                $errors[] = $exception->getMessage();
                $errors[] = $exception->getLine();
            }
            $warnings[] = 'GetGraphicWorkerCard. Метод завершил работу';
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Название метода: GetListCompanyDepartment()
     * Метод получения списка работников по участку
     *
     * @param null $data_post
     * @return array
     * @package frontend\controllers\ordersystem
     * @example http://web.amicum/read-manager-amicum?controller=ordersystem\WorkSchedule&method=GetListCompanyDepartment&subscribe=login&data={"company_department_id":801}
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 25.05.2019 19:46
     * @since ver
     */
    public static function GetListCompanyDepartment($data_post = NULL)
    {
        $status = 1;                                                                                                 // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();                                                                                                // Промежуточный результирующий массив
        if ($data_post !== NULL && $data_post !== '') {
            $warnings[] = 'GetListCompanyDepartment. Данные успешно переданы';
            $warnings[] = 'GetListCompanyDepartment. Входной массив данных' . $data_post;
            try {
                $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
                $warnings[] = 'GetListCompanyDepartment. Декодировал входные параметры';
                // Проверяем переданы ли все параметры
                if (
                    property_exists($post_dec, 'company_department_id')
                )                                                                                                       // Проверяем наличие в нем нужных нам полей
                {
                    $warnings[] = 'GetListCompanyDepartment. Данные получены вызываю метод';
                    // Получаем список людей по идентификатору company_department
                    $workers_company = self::funcListWorkerCompanyDepartment($post_dec->company_department_id);
                    if (!empty($workers_company['errors'])) {
                        $errors[] = $workers_company['errors'];
                    }
                    $warnings[] = $workers_company['warnings'];
                    $result['workers'] = $workers_company['Items']['workers'];
                    $status *= $workers_company['status'];

                    // Получаем список людей по идентификатору company_department
                    $workers_company = self::FindBrigade($post_dec->company_department_id);
                    if (!empty($workers_company['errors'])) {
                        $errors[] = $workers_company['errors'];
                    }
                    $warnings[] = $workers_company['warnings'];
                    $result['brigade'] = $workers_company['Items']['brigade'];
                    $status *= $workers_company['status'];
                }
            } catch (Throwable $ex) {
                $errors[] = $ex->getMessage();
                $status = 0;
            }
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Название метода: AddGraficTabelMain()
     * Метод добавления grafic_tabel_main, нового графика
     *
     * @param $year - год
     * @param $month - месяц
     * @param $company_department_id - участок
     * @param $title - наименование
     * @package frontend\controllers\ordersystem
     * @example
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 25.05.2019 19:48
     * @since ver
     */
    private static function AddGraficTabelMain($year, $month, $company_department_id, $title)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $gtm_id = -1;
        try {
            $warnings[] = "AddGraficTabelMain. Начал выполнять метод";
            $find_grafic = GraficTabelMain::findOne(['year' => $year, 'month' => $month, 'company_department_id' => $company_department_id]);
            if ($find_grafic) {
                throw new Exception("AddGraficTabelMain. Графика выходов на данный период уже существует");
            }
            $gtm = new GraficTabelMain();
            $gtm->date_time_create = date('Y-m-d H:i:s');
            $gtm->year = $year;
            $gtm->month = $month;
            $gtm->title = $title;
            $gtm->company_department_id = $company_department_id;
            $gtm->status_id = 1;
            if (!$gtm->save()) {
                $errors[] = $gtm->errors;
                throw new Exception("AddGraficTabelMain. Ошибка сохранения главной записи графика выходов");
            }
            $gtm->refresh();
            $gtm_id = $gtm->id;
            $warnings[] = "AddGraficTabelMain. график выходов главная запись создана";

        } catch (Throwable $exception) {
            $errors[] = "AddGraficTabelMain. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = "AddGraficTabelMain. Закончил выполнять метод";
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'id' => $gtm_id);
    }

    /**
     * Название метода: GetWorkerCard()
     * GetWorkerCard - Метод формирования карточки сотрудника, получения параметров
     *
     * @throws Exception
     * @example Работник у которого не заданы параметры некоторые: http://web.amicum/read-manager-amicum?controller=ordersystem\WorkSchedule&method=GetWorkerCard&subscribe=login&data={"worker_id":"2911855"}
     *          Вспомогательные данные: worker_object_id 9773
     *          Переданы пустые данные: http://web.amicum/read-manager-amicum?controller=ordersystem\WorkSchedule&method=GetWorkerCard&subscribe=login&data={"worker_id":""}
     *          Не переданы входные данные вообще: http://web.amicum/read-manager-amicum?controller=ordersystem\WorkSchedule&method=GetWorkerCard&subscribe=login&data={}
     *          Работник у которого не задана базовая информация, например отсутствует роль: http://web.amicum/read-manager-amicum?controller=ordersystem\WorkSchedule&method=GetWorkerCard&subscribe=login&data={"worker_id":"1011221"}
     *          Работник у которого заданы все параметры, и значений много(берутся с актуальным статусом): http://web.amicum/read-manager-amicum?controller=ordersystem\WorkSchedule&method=GetWorkerCard&subscribe=login&data={"worker_id":"2080508"}
     *          Работник у которого задано несколько должностей: http://web.amicum/read-manager-amicum?controller=ordersystem\WorkSchedule&method=GetWorkerCard&subscribe=login&data={"worker_id":"2914737"}
     *          worker_object для него 10051
     *          SQL для добавления:
     *              INSERT INTO `amicum2`.`worker_parameter_calc_value`
     * (`worker_parameter_id`, `date_time`, `value`, `status_id`, `shift`, `date_work`)
     * VALUES (37855, NOW(), 7, 1, 'Смена 3', '2019-06-14');
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 07.06.2019 17:10
     * @since ver
     * @package frontend\controllers\ordersystem
     */
    public static function GetWorkerCard($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $worker_data = array();                                                                                         // Промежуточный результирующий массив
        $new_date_now = new DateTime(Assistant::GetDateFormatYMD());
        $date_now = date('Y-m-d', strtotime(Assistant::GetDateNow()));
        try {
            if ($data_post !== NULL && $data_post !== '') {
                $warnings[] = 'GetWorkerCard. Данные успешно переданы';
                $warnings[] = 'GetWorkerCard. Входной массив данных' . $data_post;
            } else {
                throw new Exception('GetWorkerCard. Входная JSON строка не получена');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'GetWorkerCard. Декодировал входные параметры';
            if (property_exists($post_dec, 'worker_id'))                                                      // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'GetWorkerCard. Входные атрибуты получены';
            } else {
                throw new Exception('GetWorkerCard. Переданы не все входные атрибуты');
            }
            $worker_id = (int)$post_dec->worker_id;
//            $position_id = $post_dec->position_id;
            $worker_data = ViewEmployeeLastWorkerInfo::find()// Получаем базовую информацию о работнике
            ->select(['worker_id', 'worker_full_name', 'worker_birthdate', 'worker_date_start', 'worker_date_end',
                'employee_id', 'worker_position_qualification', 'role_main', 'worker_gender', 'worker_tabel_number'])
                ->where(['worker_id' => $worker_id])
                ->limit(1)
                ->asArray()
                ->one();
            if (!empty($worker_data)) {
                $warnings[] = 'GetWorkerCard. Базовая информация о работнике получена';
            } else {
                throw new Exception('GetWorkerCard. Базовая информация о работнике не получена');
            }
            $worker_data['worker_birthdate'] = strtotime($worker_data['worker_birthdate']) * 1000;                      // Дата рождения сотрудника

            $employee_opportunity = Employee::find()// Находим минимальную по дате запись о работнике, то есть когда его только приняли
            ->select(['worker.date_start AS worker_date_start', 'worker.date_end AS worker_date_end'])
                ->innerJoin('worker', 'worker.employee_id = employee.id')
                ->where(['employee.id' => $worker_data['employee_id']])
                ->orderBy('worker.date_start ASC')
                ->asArray()
                ->all();
            $worker_data['worker_date_start'] = strtotime($employee_opportunity[0]['worker_date_start']) * 1000;        // Начало работы в компании
            $worker_data['position_date_start'] = strtotime(end($employee_opportunity)['worker_date_start']) * 1000;// Начал работать в текущей должности, попросили вернуть в миллисекундах так что * на 1000
            $worker_data['position_date_end'] = strtotime(end($employee_opportunity)['worker_date_end']) * 1000; // Закончит работать в текущей должности, попросили вернуть в миллисекундах так что * на 1000
            unset($worker_data['worker_date_end']);                                                                     // Убираем лишние переменные

            $warnings[] = 'GetWorkerCard. Получение справочных данных - фотография, проф.заболевания';
            $handbook_value = ViewInitWorkerParameterHandbookValue::find()// Получение значений справочных параметров, фотография, проф.заболевание
            ->select([
                'value',
                'parameter_id'
            ])
                ->where(['worker_id' => $worker_id, 'status_id' => 1])
                ->andWhere('parameter_id = 3')
                ->limit(1)
                ->one();
            if ($handbook_value) {
                $worker_data['parameters'][$handbook_value->parameter_id] = $handbook_value->value;
            } else {
                $worker_data['parameters'][3] = "";
            }

            $company_department_id = Worker::find()
                ->select('company_department_id')
                ->where(['id' => $worker_id])
                ->limit(1)
                ->scalar();

            /** @var int Тип роли работника $role_type */
            $role_type = WorkerObject::find()
                ->select('role.type as role_type,role.id as role_id')
                ->innerJoin('role', 'role.id = worker_object.role_id')
                ->where(['worker_id' => $worker_id])
                ->asArray()
                ->limit(1)
                ->one();

            if (!$role_type) {
                $role_type = array(
                    'role_id' => 9,
                    'role_type' => 3,
                );
            }


            #region Обеспечение СИЗ
            $warnings[] = 'GetWorkerCard. Получение данных по СИЗам';
            /******************** БЛОК СИЗов У РАБОТНИКА ********************/
            $worker_sizs = WorkerSiz::find()
                ->joinWith('siz')
                ->where(['worker_siz.worker_id' => $worker_id])
                ->andWhere(['in', 'worker_siz.status_id', [64, 65]])
                ->asArray()
                ->all();
            $date_now_date_time_format = new DateTime($date_now);
            foreach ($worker_sizs as $worker_siz) {
                $date_write_off = new DateTime($worker_siz['date_write_off']);
                $diff_date = $date_now_date_time_format->diff($date_write_off);
                if ($worker_siz['date_write_off'] != null) {
                    $formated_date_write_off = date('d.m.Y', strtotime($worker_siz['date_write_off']));
                } else {
                    $formated_date_write_off = null;
                }
                if ($diff_date->format('%r%a') <= 14 and $diff_date->format('%r%a') > 0) {
                    $worker_data['worker_siz'][] = ['siz_id' => $worker_siz['siz_id'], 'date_to_replacement_siz' => $worker_siz['date_write_off'], 'date_to_replacement_siz_format' => $formated_date_write_off, 'siz_title' => $worker_siz['siz']['title'], 'flag' => 'yellow', 'diff_day' => $diff_date->format('%r%a')];
                } elseif ($diff_date->format('%r%a') <= 0) {
                    $worker_data['worker_siz'][] = ['siz_id' => $worker_siz['siz_id'], 'date_to_replacement_siz' => $worker_siz['date_write_off'], 'date_to_replacement_siz_format' => $formated_date_write_off, 'siz_title' => $worker_siz['siz']['title'], 'flag' => 'red', 'diff_day' => $diff_date->format('%r%a')];
                } else {

                    $worker_data['worker_siz'][] = [
                        'siz_id' => $worker_siz['siz_id'],
                        'date_to_replacement_siz' => $worker_siz['date_write_off'],
                        'date_to_replacement_siz_format' => $formated_date_write_off,
                        'siz_title' => $worker_siz['siz']['title'],
                        'flag' => 'green',
                        'diff_day' => $diff_date->format('%r%a')];
                }
            }
            if (empty($worker_data['worker_siz'])) {
                $worker_data['worker_siz'] = (object)array();
            }
            #endregion

            #region Медосмотры
            /******************** БЛОК МЕДОСМОТРОВ У РАБОТНИКА ********************/
            $worker_data['parameters'][761] = array();
            $checkup = MedReport::find()
                ->select(['med_report.med_report_date as date', 'med_report.date_next', 'med_report_result.title as result_title'])
                ->innerJoin('med_report_result', 'med_report_result.id = med_report.med_report_result_id')
                ->orderBy('med_report.med_report_date DESC')
                ->where(['med_report.worker_id' => $worker_id])
                ->asArray()
                ->limit(1)
                ->one();
            $period = Contingent::findOne(['role_id' => $role_type['role_id'], 'company_department_id' => $company_department_id]);
            if (empty($period)) {
                $period['period'] = 12;
            }
            if (!empty($checkup)) {
                $date_format = date('d.m.Y', strtotime($checkup['date']));
                if ($checkup['date_next'] == null) {
                    $date_next = date('d.m.Y', strtotime($checkup['date'] . '+' . $period['period'] . ' month'));
                } else {
                    $date_next = date('d.m.Y', strtotime($checkup['date_next']));
                }
                $object_date = new DateTime(Assistant::GetDateFormatYMD());
                $object_date_next = new DateTime($date_next);
                $diff_object_date = $object_date->diff($object_date_next);
                $worker_data['parameters'][761]['date'] = $checkup['date'];
                $worker_data['parameters'][761]['date_format'] = $date_format;
                $worker_data['parameters'][761]['med_report_result'] = $checkup['result_title'];
                $worker_data['parameters'][761]['date_next_med_report'] = $checkup['date_next'];
                $worker_data['parameters'][761]['date_next_med_report_format'] = $date_next;
                $worker_data['parameters'][761]['day_to_med_report'] = $diff_object_date->format('%r%a');

            } else {
                $worker_data['parameters'][761] = (object)array();
            }
            #endregion

            #region Профессиональные заболевания
            /******************** БЛОК ПРОФ ЗАБОЛЕВАНИЯ В РУЗУЛЬТАТЕ ПОСЛЕДНЕЙ МЕД ПРОВЕРКИ У работника ********************/
            $worker_data['parameters'][760] = false;
            $disease = MedReport::find()
                ->joinWith('medReportDiseases')
//                ->where(['med_report.worker_id'=>$worker_id,'med_report.position_id'=>$position_id]) //TODO 29.11.2019 rudov: раскоментить когда будет приходить идентификатор профессии
                ->where(['med_report.worker_id' => $worker_id])
                ->andWhere(['is not', 'med_report.disease_id', null])
                ->orderBy('med_report_date DESC')
                ->limit(1)
                ->one();
            $disease_med_report = true;
            $disease_proff_disease = true;
            if ($disease and property_exists($disease, "medReportDiseases")) {
                foreach ($disease->medReportDiseases as $medReportDisease) {
                    $disease_med_report = false;
                }
            }
            /**
             * тут будет ещё блок с профзаболеваниями
             */
            $occ_illness = array();
//            $occ_illness = OccupationalIllness::findOne(['worker_id'=>$worker_id,'position_id'=>$position_id]);
            $occ_illness = OccupationalIllness::findOne(['worker_id' => $worker_id]);
            if (!empty($occ_illness)) {
                $disease_proff_disease = false;
            }
            $worker_data['parameters'][760] = $disease_med_report * $disease_proff_disease;
            #endregion //

            #region Алкотестирование
            /******************** БЛОК ПРОХОЖДЕНИЯ АЛКОТЕСТИРОВАНИЯ У РАБОТНИКА ********************/
            /*
             * Заглушка
             */
            $worker_data['parameters'][684] = 0;
            #endregion

            #region Несчастные случаи
            /******************** БЛОК НАСЧАСТНЫХ СЛУЧАЕВ ********************/
            $worker_data['parameters'][764] = array();
            $event_accident = EventPbWorker::find()
                ->select('event_pb_worker.experience as experience,event_pb.date_time_event as date_time')
                ->innerJoin('event_pb', 'event_pb_worker.event_pb_id = event_pb.id')
                ->where(['event_pb_worker.worker_id' => $worker_id])
                ->andWhere(['event_pb.case_pb_id' => 2])
                ->asArray()
                ->all();
            if (!empty($event_accident)) {
                foreach ($event_accident as $item_accident) {
                    $worker_data['parameters'][764][] = [
                        'experience' => $item_accident['experience'],
                        'date_time' => $item_accident['date_time'],
                        'date_time_format' => date('d.m.Y', strtotime($item_accident['date_time'])),
                    ];
                }
//                $worker_data['parameters'][764] = $event_accident;
            } else {
                $worker_data['parameters'][764] = (object)array();
            }
            #endregion


            #region Обучение
            /******************** БЛОК ОБУЧЕНИЕ/СТАЖИРОВКА ********************/
            $certification = null;
            if ($role_type['role_type'] == 2 || $role_type['role_type'] == 1) {
                $check_knowledge_type = 2;
                $certification = 3;
                $is_ITR = true;
            } elseif ($role_type['role_type'] == 3) {
                $check_knowledge_type = 1;
                $certification = null;
                $is_ITR = false;
            } else {
                $check_knowledge_type = null;
                $certification = null;
                $is_ITR = false;
            }
            $worker_data['flag_ITR'] = $is_ITR;
            if ($check_knowledge_type != null) {
                $check_knowledges = CheckKnowledge::find()
                    ->joinWith('checkKnowledgeWorkers')
                    ->where(['check_knowledge.company_department_id' => $company_department_id])
                    ->andWhere(['check_knowledge.type_check_knowledge_id' => $check_knowledge_type])
                    ->andWhere(['check_knowledge_worker.worker_id' => $worker_id])
                    ->orderBy('check_knowledge.date DESC')
                    ->limit(1)
                    ->one();
                if (!empty($check_knowledges)) {
                    $worker_data['parameters'][763]['date_pass'] = date('d.m.Y', strtotime($check_knowledges->date));
                    $worker_data['parameters'][763]['number_certificate'] = $check_knowledges->checkKnowledgeWorkers[0]->number_certificate;
                    if ($check_knowledge_type == 1) {
                        $date_next = date('d.m.Y', strtotime($check_knowledges->date . "+1 year"));
                    } else {
                        $date_next = date('d.m.Y', strtotime($check_knowledges->date . "+3 year"));
                    }
                    $new_date_next = new DateTime($date_next);
                    $diff_check_knowledge = $new_date_now->diff($new_date_next);
                    $worker_data['parameters'][763]['date_next'] = $date_next;
                    $worker_data['parameters'][763]['day_to_check_knowledge'] = $diff_check_knowledge->format('%r%a');
                } else {
                    $worker_data['parameters'][763] = (object)array();
                }
            } else {
                $worker_data['parameters'][763] = (object)array();
            }

            #endregion

            #region Инструктажи
            /******************** БЛОК ИНСТРУКТАЖЕЙ ********************/
            /**
             * Логика: взять последний продейнный этим работинков повторный инструктаж и вывести данные 'дата прохождения инструктажа'
             *                                                                                          'тип инструктажа'
             *                                                                                          'ФИО и должность инструктирующего'
             */
            $get_last_briefing = Briefer::find()
                ->select([
                    'briefer.date_time',
                    'employee.first_name',
                    'employee.last_name',
                    'employee.patronymic',
                    'position.title as position_title',
                    'briefing.id as briefing_id',
                    'worker.id as worker_id'
                ])
                ->joinWith('briefing')
                ->joinWith('worker.employee')
                ->joinWith('worker.position')
                ->where(['briefer.worker_id' => $worker_id])
                ->andWhere(['briefing.type_briefing_id' => self::TYPE_BRIEFING_REPEATED_INSTUCTION])
                ->orderBy('briefer.date_time desc')
                ->asArray()
                ->limit(1)
                ->one();
            if (!empty($get_last_briefing)) {
                $date_next = date('d.m.Y', strtotime($get_last_briefing['date_time'] . "+3 month"));
                $date = date('d.m.Y', strtotime($get_last_briefing['date_time']));
                $new_date = new DateTime(BackendAssistant::GetDateNow());
                $new_date_next = new DateTime($date_next);
                $worker_data['parameters'][765]['flag'] = true;
                $worker_data['parameters'][765]['date_time'] = $date;
                $name_instructor = mb_substr($get_last_briefing['first_name'], 0, 1);
                $patronymic_instructor = mb_substr($get_last_briefing['patronymic'], 0, 1);
                $worker_data['parameters'][765]['full_name_instructor'] = "{$get_last_briefing['last_name']} {$name_instructor}. {$patronymic_instructor}.";
                $worker_data['parameters'][765]['position_title_instructor'] = $get_last_briefing['position_title'];
                $worker_data['parameters'][765]['date_next'] = $date_next;
                $diff_day = $new_date->diff($new_date_next);
                $worker_data['parameters'][765]['day_to_instructed'] = $diff_day->format('%r%a');
            } else {
                $worker_data['parameters'][765]['flag'] = false;
            }

            #endregion

            #region Аттестация
            /******************** БЛОК АТТЕСТАЦИИ ********************/
            $worker_data['parameters'][762] = array();
            $check_knowledges_certification = CheckKnowledge::find()
                ->joinWith('checkKnowledgeWorkers')
                ->where(['check_knowledge.company_department_id' => $company_department_id])
                ->andWhere(['check_knowledge.type_check_knowledge_id' => $certification])
                ->andWhere(['check_knowledge_worker.worker_id' => $worker_id])
                ->orderBy('check_knowledge.date DESC')
                ->limit(1)
                ->one();
            if ($certification) {
                if ($check_knowledges_certification) {
                    $check_knowledge_date = date('d.m.Y', strtotime($check_knowledges_certification->date));
                    $check_knowledge_date_next = date('d.m.Y', strtotime($check_knowledges_certification->date . "+3 year"));//                    $new_certifiaction_date = new DateTime($check_knowledge_date);
                    $new_certifiaction_next_date = new DateTime($check_knowledge_date_next);
                    $diff_certification = $new_date_now->diff($new_certifiaction_next_date);
                    $worker_data['parameters'][762]['date_pass'] = $check_knowledge_date;
                    $worker_data['parameters'][762]['date_next'] = $check_knowledge_date_next;
                    $worker_data['parameters'][762]['day_to_certification'] = $diff_certification->format('%r%a');
                } else {
                    $worker_data['parameters'][762] = (object)array();
                }
            } else {
                $worker_data['parameters'][762] = (object)array();
            }
            #endregion

            #region Предсменный экзаменатор
            /******************** БЛОК ПРЕДСМЕНЕНОГО ЭКЗАМЕНАТОРА ********************/
            /*
             * Заглушка
             */
            $worker_data['parameters'][766] = 0;
            #endregion

            #region Номер телефона
            /******************** БЛОК НОМЕРА ТЕЛЕФОНА ********************/
            $worker_cache_parameter = (new WorkerCacheController());
            $phone_number = $worker_cache_parameter->getParameterValueHash($worker_id, 7, 1);
            if (!empty($phone_number)) {
                $worker_data['phone_number'] = $phone_number['value'];
            } else {
                $worker_data['phone_number'] = null;
            }
            #endregion

            # region Нарушения у сотрудника
            /******************** БЛОК НАРУШЕНИЙ У СОТРУДНИКА ********************/
            $violation_for_worker = self::GetViolationForWorker($worker_id);
            if ($violation_for_worker['status'] == 1) {
                $worker_data['statistic_violation'] = $violation_for_worker['Items'];
                $warnings[] = $violation_for_worker['warnings'];
            } else {
                $warnings[] = $violation_for_worker['warnings'];
                $errors[] = $violation_for_worker['errors'];
            }
            #endregion

        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getFile();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $result = $worker_data;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetViolationForWorker() - метод получения нарушений у сотрудника для карточки сотрудника
     * @param $worker_id - идентификатор сотрудника
     * @return array - массив со следующей структурой: count_pab:
     *                                                 count_pab_by_year:
     *                                                 [violations_type]
     *                                                      [violation_type_id]
     *                                                                  [count_pab:]
     *                                                 [violations_type_by_year]
     *                                                          [year]
     *                                                              [violation_type_id]
     *                                                                  [count_pab:]
     *
     * @package frontend\controllers\ordersystem
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 04.10.2019 8:36
     */
    public static function GetViolationForWorker($worker_id)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetViolationForWorker. Начало метода';
        try {
            $count_pab_all_by_worker = Checking::find()
                ->select('count(injunction.id) as count_inj')
                ->innerJoin('injunction', 'checking.id = injunction.checking_id')
                ->where(['injunction.worker_id' => $worker_id])
                ->andWhere(['injunction.kind_document_id' => CheckingController::KIND_PAB])
                ->limit(1)
                ->scalar();
            $result['count_pab'] = $count_pab_all_by_worker;
            $count_pab_by_violation = Checking::find()
                ->select(['violation_type.title as violation_type_title', ' count(i.id) as count_inj_id', 'YEAR(checking.date_time_start) as year'])
                ->innerJoin('injunction i', 'checking.id = i.checking_id')
                ->innerJoin('injunction_violation iv', 'i.id = iv.injunction_id')
                ->innerJoin('violation', 'iv.violation_id = violation.id')
                ->innerJoin('violation_type', 'violation.violation_type_id = violation_type.id')
                ->where(['i.worker_id' => $worker_id])
                ->andWhere(['i.kind_document_id' => CheckingController::KIND_PAB])
                ->groupBy('violation_type_title,YEAR(checking.date_time_start)')
                ->asArray()
                ->all();
            if (!empty($count_pab_by_violation)) {
                foreach ($count_pab_by_violation as $pab_vioaltions) {
                    $result['violations_type'][$pab_vioaltions['violation_type_title']] = $pab_vioaltions['count_inj_id'];
                    $result['violations_type_by_year'][$pab_vioaltions['year']][$pab_vioaltions['violation_type_title']]['violation_type_title'] = $pab_vioaltions['violation_type_title'];
                    if (isset($result['violations_type_by_year'][$pab_vioaltions['year']]['count'])) {
                        $result['violations_type_by_year'][$pab_vioaltions['year']]['count'] += $pab_vioaltions['count_inj_id'];
                    } else {
                        $result['violations_type_by_year'][$pab_vioaltions['year']]['count'] = $pab_vioaltions['count_inj_id'];
                    }
                    $result['violations_type_by_year'][$pab_vioaltions['year']][$pab_vioaltions['violation_type_title']]['count_inj_id'] = $pab_vioaltions['count_inj_id'];
                }
            } else {
                $result['violations_type'] = (object)array();
                $result['violations_type_by_year'] = (object)array();
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetViolationForWorker. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetViolationForWorker. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Название метода: actionTest()
     * Тестовый action
     *
     * @package frontend\controllers\ordersystem
     * @example http://web.amicum/order-system/work-schedule/fill-test-calc-value?medinspection=83&internship=24&instruction=72&ot=30&pb=23&breathalyzer=79&education=83&worker_id=2080508
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 13.06.2019 13:34
     * @since ver
     */
    public function actionFillTestCalcValue($medinspection, $internship, $instruction, $ot, $pb, $breathalyzer, $education, $worker_id)
    {
        $worker_parameter_id = NULL;                                                                                    // Если нужно добавить параметр работнику, добавляем
        $parameter_id = NULL;
        $date_work = date('Y-m-d');
        $shift = 'Смена 1';
        /*
         *  - 468 Мед.осмотры
         *  - 469 Стажировки
         *  - 470 Инструктажи
         *  - 471 Дней после аттестации ОТ
         *  - 472 Дней после ПБ
         *  - 474 Алкотестер
         *  - 475 Уровень знаний
         */
        if (isset($medinspection, $internship, $instruction, $ot, $pb, $breathalyzer, $education, $worker_id)) {
            $errors = array();
            $parameters = [
                468 => $medinspection,
                469 => $internship,
                470 => $instruction,
                471 => $ot,
                472 => $pb,
                474 => $breathalyzer,
                475 => $education];
            // Получаем массив параметров
            // Добавляем эти параметры работникам
            foreach ($parameters as $parameter_id => $value) {
                $worker_parameter = WorkerMainController::createWorkerParameter($worker_id, $parameter_id, 3);         // Добавление конкретного параметра работнику
                $worker_parameter_id = $worker_parameter['worker_parameter_id'];
                $errors[] = self::addWorkerParameterCalcValue($worker_parameter_id, $value, $shift, $date_work); // Добавляем значение для параметра
            }
        }
        $result = array('errors' => $errors);                                         //формируем массив для фронта
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            // формат json
        Yii::$app->response->data = $result;
    }

    /**
     * Название метода: fillTestValues()
     * Метод заполнения таблицы worker тестовыми значениями
     *
     * @package frontend\controllers\ordersystem
     * @example
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 13.06.2019 11:39
     * @since ver
     */
    public static function fillTestCalcValues($values_array)
    {
        $errors = array();                                                                                                // Массив ошибок
        try {
            $added_rows_count = Yii::$app->db->createCommand()->batchInsert('worker_parameter_calc_value',            // Множественное добавление значений вычисляемым параметрам, на выходе количество добавленных строк
                ['worker_parameter_id', 'date_time', 'value', 'status_id', 'shift', 'date_work'],
                [$values_array])->execute();
            if ($added_rows_count === 0) {
                $errors[] = 'Не добавлено ни одного значения параметра';
            }
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }
        return $errors;
    }

    /**
     * Название метода: addWorkerParameterHandbookValue()
     * Назначение метода: метод добавления вычисляемых значений в таблицу worker_parameter_calc_value
     *
     * Входные обязательные параметры:
     * @param     $worker_parameter_id -идентификатор параметра работника
     * @param     $value - значение
     * @param     $status_id - ИД статуса
     * Входные необязательные параметры
     * @param int $date_time дата и время
     * @return int|array если данные успешно сохранились в БД, то возвращает id, иначе массив ошибок
     * @package backend\controllers
     *
     * @example $this->addWorkerParameterHandbookValue(45454, 'mot', 19);
     *
     * @author Некрасов Евгений <nep@pfsz.ru>
     * Created date: on 13.06.2019 11:38
     */
    public static function addWorkerParameterCalcValue($worker_parameter_id, $value, $shift, $date_work)
    {

        $w_p_c_v = new WorkerParameterCalcValue();                                                                      // Добавляем новую запись
        $w_p_c_v->worker_parameter_id = $worker_parameter_id;
        $w_p_c_v->value = (string)$value;
        $w_p_c_v->status_id = 1;
        $w_p_c_v->date_time = date('Y-m-d');
        $w_p_c_v->shift = $shift;
        $w_p_c_v->date_work = $date_work;
        if ($w_p_c_v->save()) {
            $w_p_c_v->refresh();
            return $w_p_c_v->id;
        }
        return $w_p_c_v->errors;
    }


    /**
     * Название метода: actionGetGraphic()
     * Метод получения графика выходов плановых и фактических выходов, блока с выходами по дням
     * пользователь запрашивает конкретный график выходов по конкретному департаменту на конкретный год и месяц
     * после чего формируется ему пустой массив сгруппированный по ключу работника и его роли по дням.
     * далее он заполняется конкретными видами рабочего времени (working_time) (Б, В, Я) и т.д.
     * если working_time равен Я, то заполняем смену и количество часов,
     * если working_time равен П (прочее), то заполняем Kind_working_time - Запасной выход, охрана труда и т.д.
     *
     * ищется конкретный АКТУАЛЬНЫЙ (tatus_id=1) график выходов.
     * Графиков выходов актуальных на конкретный департамент и год и месяц не может быть больше одного. ПРоверка выполняется при сохранении графика выходов
     * пример использования: 127.0.0.1/read-manager-amicum?controller=ordersystem\WorkSchedule&method=GetGraphicMain&subscribe=&data={"company_department_id":"4029841","year":"2019","month":"5","brigade_id":"46"}
     *
     * выходной массив:
     * [grafic_main_id] ключ конкретного графика выходов
     *      [grafic_main_id]    ключ графика выходов
     *      [worker_id]         ключ работника
     *          [role_id]       роль работника (стажер, ГРОЗ, ГРП, работник и т.д.
     *              [plan_schedule]     плановый график работника
     *                  [day]               день в графике
     *                      [working_time_id]      вид рабочего времени (Я,Б,О и т.д.)
     *                          [index]             группа или shift или kind_working_time_id
     *                              [shift]
     *                                  [shift_id]:         hours_value
     *                              [kind_working_time_id]: kind_working_time_id Запасной выход, охрана труда и т.д.
     * *            [fact_schedule]     фактический график работника
     *                  [day]               день в графике
     *                      [working_time_id]      вид рабочего времени (Я,Б,О и т.д.)
     *                          [index]             группа или shift или kind_working_time_id
     *                              [shift]
     *                                  [shift_id]:         hours_value
     *                              [kind_working_time_id]: kind_working_time_id Запасной выход, охрана труда и т.д.
     *
     * @param $company_department_id - ключ конкретного департамента
     * @param $grafic_year - год, запрашиваемого графика
     * @param $grafic_month - месяц запрашиваемого графика
     * @return array
     *
     * @package frontend\controllers\ordersystem
     * @example
     *
     * Документация на портале:
     * @author Якимов М.Н.
     * Created date: on 30.05.2019 12:34
     * @since ver
     */
    public static function GetGraphicTabel($company_department_id, $grafic_year, $grafic_month)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = (object)array();
        $grafic_main = array();
        $worker_list_in_grapfic = [];                                                                                   // список работников в графике выходов, для получения сведений в целом по людям

        try {
            // Проверяем наличие данных в кеше по ключу WorkerTimetable_CompanyDepartmentId_Year_Month
            $warnings[] = 'actionGetGraphic. Проверяем данные в кеше';

            $working_time_template["1"]["mine_id"] = "";
            $working_time_template["1"]["chane_id"] = "";
            $working_time_template["1"]["brigade_id"] = "";
            $working_time_template["1"]["working_time_id"] = 1;
            $working_time_template["1"]["index"]["shift"] = array();

            $warnings[] = 'actionGetGraphic. Получаем данные графка выходов из БД';
            $graphics = GraficTabelMain::find()
                ->with('graficTabelDateFacts')
                ->with('graficTabelDatePlans')
                ->where([
                    'year' => $grafic_year,
                    'month' => $grafic_month,
                    'company_department_id' => $company_department_id,
                    'status_id' => 1,
                ])
                ->limit(30000)
                ->all();

            if ($graphics) {
                $chane_handbook = Chane::find()
                    ->indexBy('id')
                    ->all();
                if (!$chane_handbook) {
                    throw new Exception("GetGraphicTabel. Справочник звеньев пуст");
                }


                // получаем данные с наряда
                $order_grafic = [];
                $orders = (new Query())
                    ->select('
                    company_department_id,
                    shift_id,
                    order.mine_id as mine_id,
                    chane_id,
                    brigade_id,
                    worker_id,
                    role_id,
                    role.title as role_title,
                    date_time_create
                    ')
                    ->from('order')
                    ->innerJoin('order_place', 'order_place.order_id=order.id')
                    ->innerJoin('order_operation', 'order_operation.order_place_id=order_place.id')
                    ->innerJoin('operation_worker', 'operation_worker.order_operation_id=order_operation.id')
                    ->innerJoin('role', 'operation_worker.role_id=role.id')
                    ->where(['company_department_id' => $company_department_id])
                    ->andWhere('MONTH(order.date_time_create)=' . $grafic_month)
                    ->andWhere('YEAR(order.date_time_create)=' . $grafic_year)
                    ->all();

                if ($orders) {
                    for ($i = 1; $i <= cal_days_in_month(CAL_GREGORIAN, $grafic_month, $grafic_year); $i++) {
                        foreach ($orders as $order) {
                            $worker_list_in_grapfic[$order['worker_id']] = $order['worker_id'];
                            $order_grafic['workers'][$order['worker_id']]['worker_id'] = $order['worker_id'];
                            $order_grafic['workers'][$order['worker_id']]['roles'][$order['role_id']]['role_id'] = $order['role_id'];
                            $order_grafic['workers'][$order['worker_id']]['roles'][$order['role_id']]['role_title'] = $order['role_title'];
                            $order_grafic['workers'][$order['worker_id']]['roles'][$order['role_id']]['plan_days'] = 0;
                            $order_grafic['workers'][$order['worker_id']]['roles'][$order['role_id']]['plan_hours'] = 0;
                            $order_grafic['workers'][$order['worker_id']]['roles'][$order['role_id']]['night_plan_hours'] = 0;
                            $order_grafic['workers'][$order['worker_id']]['roles'][$order['role_id']]['plan_schedule'][$i]['day'] = $i;
//                            $order_grafic['workers'][$order['worker_id']]['roles'][$order['role_id']]['plan_schedule'][$i]['chane_id'] = "";
//                            $order_grafic['workers'][$order['worker_id']]['roles'][$order['role_id']]['plan_schedule'][$i]['brigade_id'] = "";
                            $order_grafic['workers'][$order['worker_id']]['roles'][$order['role_id']]['plan_schedule'][$i]['working_time'] = $working_time_template;
                            $order_grafic['workers'][$order['worker_id']]['roles'][$order['role_id']]['fact_days'] = 0;
                            $order_grafic['workers'][$order['worker_id']]['roles'][$order['role_id']]['fact_hours'] = 0;
                            $order_grafic['workers'][$order['worker_id']]['roles'][$order['role_id']]['night_fact_hours'] = 0;
                            $order_grafic['workers'][$order['worker_id']]['roles'][$order['role_id']]['fact_schedule'][$i]['day'] = $i;
//                            $order_grafic['workers'][$order['worker_id']]['roles'][$order['role_id']]['fact_schedule'][$i]['chane_id'] = "";
//                            $order_grafic['workers'][$order['worker_id']]['roles'][$order['role_id']]['fact_schedule'][$i]['brigade_id'] = "";
                            $order_grafic['workers'][$order['worker_id']]['roles'][$order['role_id']]['fact_schedule'][$i]['working_time'] = $working_time_template;
                        }
                    }


                    foreach ($orders as $order) {
                        $worker_list_in_grapfic[$order['worker_id']] = $order['worker_id'];
                        $day = date("j", strtotime($order['date_time_create']));
                        $worker_id = $order['worker_id'];
                        $role_id = $order['role_id'];
                        $shift_id = $order['shift_id'];
                        $order_grafic['workers'][$worker_id]['worker_id'] = $worker_id;
                        $order_grafic['workers'][$worker_id]['roles'][$role_id]['role_id'] = $role_id;
                        $order_grafic['workers'][$worker_id]['roles'][$role_id]['role_title'] = $order['role_title'];
                        if (!isset($order_grafic['workers'][$worker_id]['roles'][$role_id]['fact_days'])) {
                            $order_grafic['workers'][$worker_id]['roles'][$role_id]['fact_days'] = 0;
                            $order_grafic['workers'][$worker_id]['roles'][$role_id]['fact_hours'] = 0;
                            $order_grafic['workers'][$worker_id]['roles'][$role_id]['night_fact_hours'] = 0;
                        }

                        $order_grafic['workers'][$worker_id]['roles'][$role_id]['fact_days'] += 1;
                        $order_grafic['workers'][$worker_id]['roles'][$role_id]['fact_hours'] += 6;
                        if ($shift_id == 3 or $shift_id == 4) {
                            $order_grafic['workers'][$worker_id]['roles'][$role_id]['night_fact_hours'] += 6;
                        }

                        $order_grafic['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['day'] = $day;
//                        $order_grafic['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['chane_id'] = $order['chane_id'];
//                        $order_grafic['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['brigade_id'] = $order['brigade_id'];
                        $order_grafic['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['working_time'][1]['index']['shift'][$shift_id]['chane_id'] = $order['chane_id'];
                        $order_grafic['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['working_time'][1]['index']['shift'][$shift_id]['brigade_id'] = $order['brigade_id'];
                        $order_grafic['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['working_time'][1]['index']['shift'][$shift_id]["mine_id"] = $order['mine_id'];
                        $order_grafic['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['working_time'][1]['working_time_id'] = '1';
                        $order_grafic['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['working_time'][1]['index']['shift'][$shift_id]['shift_id'] = $shift_id;
                        $order_grafic['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['working_time'][1]['index']['shift'][$shift_id]['hours_value'] = 6;
                        $order_grafic['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['working_time'][1]['index']['shift'][$shift_id]['description'] = "";
                    }

                    $warnings[] = 'actionGetGraphic. График выходов по наряду';
//                $warnings[] = $orders;
//                    $warnings[] = $order_grafic;
                }

                foreach ($graphics as $graphic) {
                    $grafic_main[$graphic->id]['grafic_main_id'] = $graphic->id;
                    $workers = (new Query())
                        ->select([
                            'grafic_tabel_main_id',
                            'worker_id',
                            'role_id',
                            'role.title as role_title'
                        ])
                        ->from('grafic_tabel_date_plan')
                        ->innerJoin('role', "role.id=grafic_tabel_date_plan.role_id")
                        ->where([
                            'grafic_tabel_main_id' => $graphic->id,
                        ])
                        ->groupBy(['grafic_tabel_main_id', 'role_id', 'worker_id'])
                        ->limit(6000)
                        ->all();
                    if (isset($order_grafic['workers'])) {
                        $grafic_main[$graphic->id]['workers'] = $order_grafic['workers'];
                    }
                    if ($workers) {

                        for ($i = 1; $i <= cal_days_in_month(CAL_GREGORIAN, $grafic_month, $grafic_year); $i++) {
                            foreach ($workers as $worker) {
                                $worker_list_in_grapfic[$worker['worker_id']] = $worker['worker_id'];
                                if (!isset($grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']])) {
                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['worker_id'] = $worker['worker_id'];
                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['role_id'] = $worker['role_id'];
                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['role_title'] = $worker['role_title'];
                                }

                                if (!isset($grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['plan_schedule'])) {
                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['plan_days'] = 0;
                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['plan_hours'] = 0;
                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['night_plan_hours'] = 0;
                                }
                                if (!isset($grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['plan_schedule'][$i])) {
                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['plan_schedule'][$i]['day'] = $i;
//                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['plan_schedule'][$i]['chane_id'] = "";
//                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['plan_schedule'][$i]['brigade_id'] = "";
                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['plan_schedule'][$i]['working_time'] = $working_time_template;
                                }

                                if (!isset($grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['fact_schedule'])) {
                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['fact_days'] = 0;
                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['fact_hours'] = 0;
                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['night_fact_hours'] = 0;
                                }

                                if (!isset($grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['fact_schedule'][$i])) {
                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['fact_schedule'][$i]['day'] = $i;
//                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['fact_schedule'][$i]['chane_id'] = "";
//                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['fact_schedule'][$i]['brigade_id'] = "";
                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['fact_schedule'][$i]['working_time'] = $working_time_template;
                                }
                            }
                        }
                    }
                }

//                $warnings[] = "Шаблон:";
//                $warnings[] = $grafic_main;

                foreach ($graphics as $graphic) {
                    $grafic_main[$graphic->id]['grafic_main_id'] = $graphic->id;
                    $workers = (new Query())
                        ->select([
                            'grafic_tabel_main_id',
                            'worker_id',
                            'role_id',
                            'role.title as role_title'
                        ])
                        ->from('grafic_tabel_date_fact')
                        ->innerJoin('role', "role.id=grafic_tabel_date_fact.role_id")
                        ->where([
                            'grafic_tabel_main_id' => $graphic->id,
                        ])
                        ->groupBy(['grafic_tabel_main_id', 'role_id', 'worker_id'])
                        ->limit(6000)
                        ->all();
                    if ($workers) {
                        for ($i = 1; $i <= cal_days_in_month(CAL_GREGORIAN, $grafic_month, $grafic_year); $i++) {
                            foreach ($workers as $worker) {
                                $worker_list_in_grapfic[$worker['worker_id']] = $worker['worker_id'];
                                if (!isset($grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']])) {
                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['worker_id'] = $worker['worker_id'];
                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['role_id'] = $worker['role_id'];
                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['role_title'] = $worker['role_title'];
                                }
                                if (!isset($grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['plan_schedule'])) {
                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['plan_days'] = 0;
                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['plan_hours'] = 0;
                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['night_plan_hours'] = 0;
                                }
                                if (!isset($grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['plan_schedule'][$i])) {
                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['plan_schedule'][$i]['day'] = $i;
//                                        $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['plan_schedule'][$i]['chane_id'] = "";
//                                        $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['plan_schedule'][$i]['brigade_id'] = "";
                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['plan_schedule'][$i]['working_time'] = $working_time_template;
                                }

                                if (!isset($grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['fact_schedule'])) {
                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['fact_days'] = 0;
                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['fact_hours'] = 0;
                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['night_fact_hours'] = 0;
                                }

                                if (!isset($grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['fact_schedule'][$i])) {
                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['fact_schedule'][$i]['day'] = $i;
//                                        $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['fact_schedule'][$i]['chane_id'] = "";
//                                        $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['fact_schedule'][$i]['brigade_id'] = "";
                                    $grafic_main[$graphic->id]['workers'][$worker['worker_id']]['roles'][$worker['role_id']]['fact_schedule'][$i]['working_time'] = $working_time_template;
                                }

                            }
                        }
                    }
                }

//                $warnings[] = "Шаблон:";
//                $warnings[] = $grafic_main;

                // заполнение графика
                foreach ($graphics as $graphic) {
                    // плановый график
                    foreach ($graphic->graficTabelDatePlans as $worker_grafics) {
                        $worker_list_in_grapfic[$worker_grafics['worker_id']] = $worker_grafics['worker_id'];
                        if (date("m", strtotime($worker_grafics['date_time'])) == (int)$grafic_month) {
                            $worker_id = $worker_grafics['worker_id'];
                            $working_time = (int)$worker_grafics['working_time_id'];
                            $day = (int)date("d", strtotime($worker_grafics['date_time']));
                            $role_id = $worker_grafics['role_id'];
                            $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['plan_schedule'][$day]['working_time'][$working_time]['working_time_id'] = $working_time;
//                            $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['plan_schedule'][$day]['chane_id'] = $worker_grafics['chane_id'];
//                            $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['plan_schedule'][$day]['brigade_id'] = $worker_grafics['chane_id'] ? $chane_handbook[$worker_grafics['chane_id']]['brigade_id'] : null;
                            $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['plan_schedule'][$day]['day'] = $day;

                            $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['plan_schedule'][$day]['working_time'][$working_time]['chane_id'] = $worker_grafics['chane_id'];
                            $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['plan_schedule'][$day]['working_time'][$working_time]['brigade_id'] = $worker_grafics['chane_id'] ? $chane_handbook[$worker_grafics['chane_id']]['brigade_id'] : null;

                            if ($working_time == 1) {
                                /**
                                 * рабочая смена
                                 */
                                $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['plan_schedule'][$day]['working_time'][$working_time]['index']['shift'][$worker_grafics['shift_id']]['chane_id'] = $worker_grafics['chane_id'];
                                $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['plan_schedule'][$day]['working_time'][$working_time]['index']['shift'][$worker_grafics['shift_id']]['brigade_id'] = $worker_grafics['chane_id'] ? $chane_handbook[$worker_grafics['chane_id']]['brigade_id'] : null;
                                $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['plan_schedule'][$day]['working_time'][$working_time]['index']['shift'][$worker_grafics['shift_id']]['mine_id'] = $worker_grafics['mine_id'];
                                $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['plan_schedule'][$day]['working_time'][$working_time]['index']['shift'][$worker_grafics['shift_id']]['shift_id'] = $worker_grafics['shift_id'];
                                $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['plan_schedule'][$day]['working_time'][$working_time]['index']['shift'][$worker_grafics['shift_id']]['hours_value'] = $worker_grafics['hours_value'];
                                $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['plan_hours'] += 6;
                                $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['plan_days'] += 1;
                                if ($worker_grafics['shift_id'] == 3 or $worker_grafics['shift_id'] == 4) {
                                    $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['night_plan_hours'] += 6;
                                }
                            } else if ($working_time == 23 || $working_time == 24) {
                                /**
                                 * Больничный или выходной
                                 **/
                                if (isset($grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['plan_schedule'][$day]['working_time']['1']['index']['shift']) and !$grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['plan_schedule'][$day]['working_time']['1']['index']['shift']) {
                                    unset($grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['plan_schedule'][$day]['working_time']["1"]);
                                }

                                $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['plan_schedule'][$day]['working_time'][$working_time]['mine_id'] = $worker_grafics['mine_id'];
                                $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['plan_schedule'][$day]['working_time'][$working_time]['index']['shift'][$worker_grafics['shift_id']]['mine_id'] = $worker_grafics['mine_id'];
                                $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['plan_schedule'][$day]['working_time'][$working_time]['index']['shift'][$worker_grafics['shift_id']]['shift_id'] = $worker_grafics['shift_id'];
                                $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['plan_schedule'][$day]['working_time'][$working_time]['index']['shift'][$worker_grafics['shift_id']]['hours_value'] = $worker_grafics['hours_value'];
                                $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['plan_schedule'][$day]['working_time'][$working_time]['index']['shift'][$worker_grafics['shift_id']]['description'] = $worker_grafics['description'];
                            } else {
                                if (isset($grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['plan_schedule'][$day]['working_time']['1']['index']['shift']) and !$grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['plan_schedule'][$day]['working_time']['1']['index']['shift']) {
                                    unset($grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['plan_schedule'][$day]['working_time']["1"]);
                                }
                                $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['plan_schedule'][$day]['working_time'][$working_time]['kind_working_time'] = $worker_grafics['kind_working_time_id'];
                                $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['plan_schedule'][$day]['working_time'][$working_time]['mine_id'] = $worker_grafics['mine_id'];
                            }
                        }
                    }
//                    $warnings[] = $grafic_main;

                    // фактический график
                    foreach ($graphic->graficTabelDateFacts as $worker_grafics) {
                        $worker_list_in_grapfic[$worker_grafics['worker_id']] = $worker_grafics['worker_id'];
                        if (date("m", strtotime($worker_grafics['date_time'])) == (int)$grafic_month) {
                            $worker_id = $worker_grafics['worker_id'];
                            $working_time = $worker_grafics['working_time_id'];
                            $day = (int)date("d", strtotime($worker_grafics['date_time']));
                            $role_id = $worker_grafics['role_id'];
                            $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['working_time'][$working_time]['working_time_id'] = $working_time;
//                            $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['chane_id'] = $worker_grafics['chane_id'];
//                            $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['brigade_id'] = $chane_handbook[$worker_grafics['chane_id']]['brigade_id'];
                            $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['day'] = $day;

                            $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['working_time'][$working_time]['chane_id'] = $worker_grafics['chane_id'];
                            $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['working_time'][$working_time]['brigade_id'] = $chane_handbook[$worker_grafics['chane_id']]['brigade_id'];

                            if ($working_time == 1) {
                                $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['working_time'][$working_time]['index']['shift'][$worker_grafics['shift_id']]['chane_id'] = $worker_grafics['chane_id'];
                                $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['working_time'][$working_time]['index']['shift'][$worker_grafics['shift_id']]['brigade_id'] = $worker_grafics['chane_id'] ? $chane_handbook[$worker_grafics['chane_id']]['brigade_id'] : null;
                                $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['working_time'][$working_time]['index']['shift'][$worker_grafics['shift_id']]['shift_id'] = $worker_grafics['shift_id'];
                                $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['working_time'][$working_time]['index']['shift'][$worker_grafics['shift_id']]['mine_id'] = $worker_grafics['mine_id'];
                                $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['working_time'][$working_time]['index']['shift'][$worker_grafics['shift_id']]['hours_value'] = $worker_grafics['hours_value'];
                                $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['working_time'][$working_time]['index']['shift'][$worker_grafics['shift_id']]['description'] = $worker_grafics['description'];
                                $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['fact_hours'] += 6;
                                $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['fact_days'] += 1;
                                if ($worker_grafics['shift_id'] == 3 or $worker_grafics['shift_id'] == 4) {
                                    $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['night_fact_hours'] += 6;
                                }
                            } else if ($working_time == 23 || $working_time == 24) {
                                /**
                                 * Больничный или выходной
                                 **/
                                if (isset($grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['working_time']['1']['index']['shift']) and !$grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['working_time']['1']['index']['shift']) {
                                    unset($grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['working_time']["1"]);
                                }

                                $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['working_time'][$working_time]['mine_id'] = $worker_grafics['mine_id'];
                                $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['working_time'][$working_time]['index']['shift'][$worker_grafics['shift_id']]['mine_id'] = $worker_grafics['mine_id'];
                                $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['working_time'][$working_time]['index']['shift'][$worker_grafics['shift_id']]['shift_id'] = $worker_grafics['shift_id'];
                                $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['working_time'][$working_time]['index']['shift'][$worker_grafics['shift_id']]['hours_value'] = $worker_grafics['hours_value'];
                                $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['working_time'][$working_time]['index']['shift'][$worker_grafics['shift_id']]['description'] = $worker_grafics['description'];
                            } else {
                                if (isset($grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['working_time']['1']['index']['shift']) and !$grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['working_time']['1']['index']['shift']) {
                                    unset($grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['working_time']["1"]);
                                }
                                $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['working_time'][$working_time]['kind_working_time'] = $worker_grafics['kind_working_time_id'];
                                $grafic_main[$graphic->id]['workers'][$worker_id]['roles'][$role_id]['fact_schedule'][$day]['working_time'][$working_time]['mine_id'] = $worker_grafics['mine_id'];
                            }
                        }
                    }
                }
                $status *= 1;
                $warnings[] = "GetGraphicTabel. Закончил перебор существующих графиков выходов";
                $result = $grafic_main;

            } else {
                $warnings[] = "GetGraphicTabel. По запрашиваемому набору данных нет графиков выходов";
                $status *= 1;
            }

            $warnings[] = 'actionGetGraphic. выполнение метода закончено';
            // Сохраняем данные в кеше

        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array(
            'Items' => $result,
            'status' => $status,
            'errors' => $errors,
            'warnings' => $warnings,
            'worker_list_in_grapfic' => $worker_list_in_grapfic,
        );
    }

    /**
     * Название метода: FindBrigade()
     * Метод получения списка бригад по конкретному департаменту
     *
     * @param null $data_post - параметры запроса в формате JSON
     * $status_actual - статус актуальности бригад
     * $company_department_id - ключ конкретного департамента
     * @return array - список бригад
     * @package frontend\controllers\ordersystem
     * @example
     *
     * Документация на портале:
     * @author Якимов М.Н.
     * Created date: on 23.05.2019 12:34
     * @since ver
     */
    private static function FindBrigade($company_department_id, $status_actual = 1)
    {
        $status = 1;                                                                                                      // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();
        try {
            $brigade_model = Brigade::find()
                ->where([
                    'company_department_id' => $company_department_id,
                    'status_id' => $status_actual
                ])
                ->asArray()
                ->orderBy('description')
                ->all();
            if ($brigade_model) {
                $warnings[] = 'FindBrigade. нашли бригаду ';
                foreach ($brigade_model as $brigade) {
                    $result['brigade'][$brigade['id']]['brigade_id'] = $brigade['id'];
                    $result['brigade'][$brigade['id']]['brigade_description'] = $brigade['description'];
                    $result['brigade'][$brigade['id']]['brigader_id'] = $brigade['brigader_id'];
                }
                $status *= 1;
            } else {
                $warnings[] = 'FindBrigade. У запрашиваемого конкретного департамента нет бригад';
                $result['brigade'] = "";
                $status *= 1;
            }
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = $exception->getMessage();
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Название метода: GetTemplate()
     * Метод получения шаблона построения графика по месяцу, бригаде, году
     *
     * @param null $data_post - параметры запроса в формате JSON
     * @return array
     * @package frontend\controllers\ordersystem
     * @example
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 23.05.2019 12:34
     * @since ver
     */
    public static function GetTemplate($data_post = NULL)
    {
        $status = 1;                                                                                                      // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();                                                                                                // Промежуточный результирующий массив
        if ($data_post !== NULL && $data_post !== '') {
            $warnings[] = 'actionGetTemplate. Данные успешно переданы';
            $warnings[] = 'actionGetTemplate. Входной массив данных' . $data_post;
            try {
                $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
                $warnings[] = 'actionGetTemplate. Декодировал входные параметры';
                // Проверяем переданы ли все параметры
                if (
                    property_exists($post_dec, 'grafic_tabel_main_id') &&
                    property_exists($post_dec, 'brigade_id') &&
                    property_exists($post_dec, 'year') &&
                    property_exists($post_dec, 'month')
                )                                                                                                       // Проверяем наличие в нем нужных нам полей
                {
                    $year = (property_exists($post_dec, 'year')) ? $post_dec->year : date('Y');           // Если не передана дата на которую строитсья график, берем текущую
                    $month = (property_exists($post_dec, 'month')) ? $post_dec->month : date('m');        // Если не передана дата на которую строитсья график, берем текущую

                    $warnings[] = 'actionGetTemplate. Получаем данные шаблона графика выходов';
                    $grafic_tabel_main_id = $post_dec->grafic_tabel_main_id;
                    $brigade_id = $post_dec->brigade_id;

                    $graphics_template = GraficChaneTable::find()// Получаем данные шаблона
                    ->select(['DATE_FORMAT({{grafic_chane_table}}.date_time, \'%d\') as day', 'chane_id', 'shift_id', 'working_time_id', 'grafic_tabel_main_id'])
                        ->joinWith('chane')
                        ->joinWith('graficTabelMain')
                        ->joinWith('workingTime')
                        ->where(['grafic_tabel_main.year' => $year])
                        ->andWhere(['grafic_tabel_main.month' => $month])
                        ->andWhere(['grafic_tabel_main.id' => $grafic_tabel_main_id])
                        ->andWhere(['chane.brigade_id' => $brigade_id])
                        ->orderBy('date_time')
                        ->asArray();

                    foreach ($graphics_template->each(500) as $template_grafic) {
                        $result[$grafic_tabel_main_id]['grafic_tabel_main_id'] = $grafic_tabel_main_id;
                        $result[$grafic_tabel_main_id]['chane'][$template_grafic['chane_id']]['chane_id'] = $template_grafic['chane_id'];
                        $result[$grafic_tabel_main_id]['chane'][$template_grafic['chane_id']]['day'][(int)$template_grafic['day']]['day'] = (int)$template_grafic['day'];
                        $result[$grafic_tabel_main_id]['chane'][$template_grafic['chane_id']]['day'][(int)$template_grafic['day']]['shift_id'] = $template_grafic['shift_id'];
                        $result[$grafic_tabel_main_id]['chane'][$template_grafic['chane_id']]['day'][(int)$template_grafic['day']]['working_time_id'] = $template_grafic['working_time_id'];
                        $result[$grafic_tabel_main_id]['chane'][$template_grafic['chane_id']]['day'][(int)$template_grafic['day']]['working_time_short_title'] = $template_grafic['workingTime']['short_title'];
                    }
                }
            } catch (Throwable $exception) {
                $status = 0;
                $errors[] = $exception->getMessage();
                $errors[] = $exception->getLine();
            }
        } else {
            $errors[] = "actionGetGraphic. Входной массив обязательных данных пуст";
            $status = 0;
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Название метода: SaveGraphic()
     * Метод сохранения графика выходов
     *
     * В сохранении достаточно получать department_id
     * @package frontend\controllers\ordersystem
     * @example http://amicum/read-manager-amicum?controller=ordersystem\WorkSchedule&method=SaveGraphic&subscribe=saveGraphic&data={}
     *
     *
     * Документация на портале:
     * @author Якимов М.Н.
     * Created date: on 31.05.2019 13:47
     * @since ver
     */
    public static function SaveGraphic($data_post = NULL)
    {
        $status = 1;                                                                                                      // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $new_brigade_id = NULL;
        $result = array();                                                                                                // Промежуточный результирующий массив
        $grahic_array_plan = array();
        $grahic_array_fact = array();
        $id_new_chane_for_template = array();
        $new_grafic_tabel_main = NULL;
//        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Проверяем есть ли данные графика в кеше
            if ($data_post !== NULL and $data_post !== "") {
                $warnings[] = 'SaveGraphic. Данные успешно переданы';
                $warnings[] = 'SaveGraphic. Входной массив данных';
            } else {
                throw new Exception("SaveGraphic. Входной массив не передан");
            }

            $graph_worker = json_decode($data_post);                                                                // Декодируем входной массив данных
            $warnings[] = 'SaveGraphic. Декодировал входные параметры';
            if (
                property_exists($graph_worker, 'graphic_info') &&
                property_exists($graph_worker, 'year') &&
                property_exists($graph_worker, 'month') &&
                property_exists($graph_worker, 'grafic_tabel_main_id') &&
                property_exists($graph_worker, 'company_department_id') &&
                property_exists($graph_worker, 'list_brigade') &&
                property_exists($graph_worker, 'list_vgk') &&
                property_exists($graph_worker, 'template')
            ) {
                /**
                 *  let graficMain = {
                 * grafic_tabel_main_id: -1,                                           // ключ главного графика выходов
                 * company_department_id: this.$store.state.currentDepartment.id,      // конкретный депратамент для которого создается график
                 * year: this.$store.state.chosenDate.year,                            // год в котором делаем график
                 * month: this.$store.state.chosenDate.numberMonth + 1,                // месяц в котором делаем график
                 * workers_info: {},                                                   // раньше использовалось для проверки необходимости пересохрнять бригаду
                 * graphic_info: {},                                                   // список графиков работника на сохранение
                 * template: {},                                                       // шаблон на сохранение
                 * list_brigade:{}                                                      // список бригад на сохранение
                 * };
                 */
                // Проверяем наличие нужных полей
                $year = $graph_worker->year;                        // год за который сохраняется график
                $month = $graph_worker->month;                      // месяц за который сохраняется график
                $gtm_id = $graph_worker->grafic_tabel_main_id;      // ключ графика выходов
                $list_brigade = $graph_worker->list_brigade;        // список бригад
                $graphic_info = $graph_worker->graphic_info;        // список графиков выходов
                $list_vgk = $graph_worker->list_vgk;                // списко работников ВГК
                $template = $graph_worker->template;                // шаблон графика выходов
                $company_department_id = $graph_worker->company_department_id->id;  // конкретный департамент в которы пишем график
                $department_title = $graph_worker->company_department_id->title;  // конкретный департамент в которы пишем график
                /**
                 * Создание бригад и звеньев, и добавление в них работников
                 */
                $chane_worker_arr = array();                                                                        // Массив для добавления в таблицу chane_worker
                $brigade_worker_arr = array();                                                                      // Массив для добавления в таблицу brigade_worker
            } else {
                throw new Exception("SaveGraphic. Параметры входного массива не корректны");
            }

            foreach ($list_brigade as $brigade_id => $brigade) {
                if ($brigade->brigade_id != 0) {
                    $response = BrigadeController::AddBrigade(
                        $brigade_id,
                        $brigade->brigade_description,
                        $brigade->brigader_id,
                        $company_department_id
                    );
                    if ($response['status'] == 0) {
                        $errors[] = $response['errors'];
                        $warnings[] = $response['warnings'];
                        throw new Exception("SaveGraphic. Ошибка сохранения бригады");
                    }

                    $errors[] = $response['errors'];
                    $warnings[] = $response['warnings'];
                    $new_brigade_id = $response['id'];
                    $warnings[] = 'SaveGraphic. Удаление старых бригад если были';
                    //$delete_insert = Yii::$app->db->createCommand()->delete('brigade_worker', 'brigade_id=' . $new_brigade_id)->execute();
                    $delete_insert = BrigadeWorker::deleteAll(['brigade_id' => $new_brigade_id]);
                    $warnings[] = "SaveGraphic. Удалено бригад  " . $delete_insert;
                    // создаем справочник бригад - старая новая/обновленная бригада
                    // нужно при создании новой бригады на фронте, что записался сам график
                    $brigade_arr[$brigade_id] = $new_brigade_id;
                    foreach ($brigade->chanes as $chane_id => $chane)                                        // Перебор звеньев
                    {
                        $response = BrigadeController::AddChane(                                                        // Добавляем новое звено прикрепленное к созданной бригаде
                            $new_brigade_id,
                            $chane->chaner_id,
                            $chane->chane_title,
                            $chane->chane_type,
                            $chane->chane_id
                        );
                        if ($response['status'] == 0) {
                            $errors[] = $response['errors'];
                            $warnings[] = $response['warnings'];
                            throw new Exception("SaveGraphic. Ошибка сохранения звена");
                        }

                        $errors[] = $response['errors'];
                        $warnings[] = $response['warnings'];
                        $id_new_chane = $response['id'];

                        $id_new_chane_for_template[$chane->chane_id] = $id_new_chane;
                        $warnings[] = 'SaveGraphic. Удаление старых звеньев если были';
                        //$delete_insert = Yii::$app->db->createCommand()->delete('chane_worker', 'chane_id=' . $id_new_chane)->execute();
                        $delete_insert = ChaneWorker::deleteAll(['chane_id' => $id_new_chane]);
                        $warnings[] = "SaveGraphic. Удалено звеньев  " . $delete_insert;
                        // создаем справочник звеньев - старое новое/обновленное звено
                        // нужно при создании нового звена на фронте, что записался сам график
                        $chane_arr[$chane->chane_id] = $id_new_chane;
                        if (property_exists($chane, 'workers')) {
                            foreach ((array)$chane->workers as $worker)                                                 // Перебор третьего слоя группировки - работники
                            {
                                $chane_worker_arr[] = [$id_new_chane, $worker->worker_id];                              // Формируем массив который в последующем добавим при помощи batchInsert
                                $brigade_worker_arr[] = [$new_brigade_id, $worker->worker_id];                          // Формируем массив который в последующем добавим при помощи batchInsert
                            }
                        }
                    }

                    $warnings[] = 'SaveGraphic. Формирование массивов для добавление закончил, множественное добавление';
                    $insert_result = Yii::$app->db->createCommand()->batchInsert(ChaneWorker::tableName(),
                        ['chane_id', 'worker_id'], $chane_worker_arr)->execute();                                       // Закрепляем сотрудников за бригадами и звеньями
                    if ($insert_result === 0) {
//                        throw new \Exception('SaveGraphic. Работники в звено не добавлены');
                    }
                    $insert_result = Yii::$app->db->createCommand()->batchInsert(BrigadeWorker::tableName(),
                        ['brigade_id', 'worker_id'], $brigade_worker_arr)->execute();
                    if ($insert_result === 0) {
//                        throw new \Exception('SaveGraphic. Работники в бригаду не добавлены');
                    }
                    $warnings[] = 'SaveGraphic. Добавление работников закончил';
                }
                $warnings[] = 'SaveGraphic. получил айди все бригады. Обновление закончил';
            }


            /**
             * Добавление графика выходов
             */
            if ($gtm_id == -1) {
                $response = self::AddGraficTabelMain($year, $month, $company_department_id, $department_title);
                if ($response['status'] == 0) {
                    $errors[] = $response['errors'];
                    $warnings[] = $response['warnings'];
                    throw new Exception("SaveGraphic. Ошибка сохранения главного графика вызодов");
                }

                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                $new_grafic_tabel_main = $response['id'];

                $warnings[] = "SaveGraphic.График заново созда";
            } else {
                $new_grafic_tabel_main = $gtm_id;
                $warnings[] = "SaveGraphic. Ключ главного графика выходов взял с фронта - есть такой график выходов";
            }
            //распарсивание результатов от фронта и подготовка их к массовой вставке
            $i = 0;
            $j = 0;
            foreach ($graphic_info as $grafic) {
                foreach ($grafic->roles as $role) {
                    $workers_role[] = array('worker_id' => $grafic->worker_id, 'role_id' => $role->role_id);
                    $worker_list_for_search[$grafic->worker_id] = $grafic->worker_id;
                    foreach ($role->plan_schedule as $day) {
                        if ($day->working_time !== NULL) {
                            foreach ($day->working_time as $working_time) {
                                if (property_exists($working_time, 'index')) {
                                    if (property_exists($working_time->index, 'shift')) {
                                        foreach ($working_time->index->shift as $shift) {
                                            if ($shift !== null and property_exists($shift, 'shift_id')) {
                                                $grahic_array_plan[$i]['grafic_tabel_main_id'] = $new_grafic_tabel_main;
                                                $grahic_array_plan[$i]['day'] = $day->day;
                                                $grahic_array_plan[$i]['chane_id'] = $chane_arr[$day->chane_id];
                                                $grahic_array_plan[$i]['shift_id'] = $shift->shift_id;
                                                $grahic_array_plan[$i]['worker_id'] = $grafic->worker_id;
                                                $grahic_array_plan[$i]['hours_value'] = $shift->hours_value;
                                                $grahic_array_plan[$i]['role_id'] = $role->role_id;
                                                $grahic_array_plan[$i]['date_time'] = date("Y-m-d", strtotime($year . '-' . $month . '-' . $day->day));
                                                $grahic_array_plan[$i]['month'] = $month;
                                                $grahic_array_plan[$i]['year'] = $year;
                                                $grahic_array_plan[$i]['kind_working_time_id'] = 1;                             //1 - значит рабочий день из справочника kind_working_time
                                                $grahic_array_plan[$i]['working_time_id'] = $working_time->working_time_id;
                                                $grahic_array_plan[$i]['description'] = "";
                                                $i++;
                                            }
                                        }
                                    } elseif (property_exists($working_time->index, 'kind_working_time')) {
                                        foreach ($working_time->index->kind_working_time as $kind_working_time) {
                                            $grahic_array_plan[$i]['grafic_tabel_main_id'] = $grafic->grafic_main_id->id;
                                            $grahic_array_plan[$i]['day'] = $day->day;
                                            $grahic_array_plan[$i]['chane_id'] = $chane_arr[$day->chane_id];
                                            $grahic_array_plan[$i]['shift_id'] = 5;                                         //5 значит без смены
                                            $grahic_array_plan[$i]['worker_id'] = $grafic->worker_id;
                                            $grahic_array_plan[$i]['hours_value'] = NULL;
                                            $grahic_array_plan[$i]['role_id'] = $role->role_id;
                                            $grahic_array_plan[$i]['date_time'] = date("Y-m-d", strtotime($year . '-' . $month . '-' . $day->day));
                                            $grahic_array_plan[$i]['month'] = $month;
                                            $grahic_array_plan[$i]['year'] = $year;
                                            $grahic_array_plan[$i]['kind_working_time_id'] = $kind_working_time->kind_working_time_id;
                                            $grahic_array_plan[$i]['working_time_id'] = $working_time->working_time_id;
                                            $grahic_array_plan[$i]['description'] = "";
                                            $i++;
                                        }
                                    }
                                } elseif (property_exists($working_time, 'kind_working_time')) {
                                    $grahic_array_plan[$i]['grafic_tabel_main_id'] = $new_grafic_tabel_main;
                                    $grahic_array_plan[$i]['day'] = $day->day;
                                    $grahic_array_plan[$i]['chane_id'] = $chane_arr[$day->chane_id];
                                    $grahic_array_plan[$i]['shift_id'] = 5;
                                    $grahic_array_plan[$i]['worker_id'] = $grafic->worker_id;
                                    $grahic_array_plan[$i]['hours_value'] = 0;
                                    $grahic_array_plan[$i]['role_id'] = $role->role_id;
                                    $grahic_array_plan[$i]['date_time'] = date("Y-m-d", strtotime($year . '-' . $month . '-' . $day->day));
                                    $grahic_array_plan[$i]['month'] = $month;
                                    $grahic_array_plan[$i]['year'] = $year;
                                    $grahic_array_plan[$i]['kind_working_time_id'] = $working_time->kind_working_time;
                                    $grahic_array_plan[$i]['working_time_id'] = $working_time->working_time_id;
                                    $grahic_array_plan[$i]['description'] = "";
                                    $i++;
                                } else {
                                    $grahic_array_plan[$i]['grafic_tabel_main_id'] = $new_grafic_tabel_main;
                                    $grahic_array_plan[$i]['day'] = $day->day;
                                    $grahic_array_plan[$i]['chane_id'] = $chane_arr[$day->chane_id];
                                    $grahic_array_plan[$i]['shift_id'] = 5;
                                    $grahic_array_plan[$i]['worker_id'] = $grafic->worker_id;
                                    $grahic_array_plan[$i]['hours_value'] = 0;
                                    $grahic_array_plan[$i]['role_id'] = $role->role_id;
                                    $grahic_array_plan[$i]['date_time'] = date("Y-m-d", strtotime($year . '-' . $month . '-' . $day->day));
                                    $grahic_array_plan[$i]['month'] = $month;
                                    $grahic_array_plan[$i]['year'] = $year;
                                    $grahic_array_plan[$i]['kind_working_time_id'] = 1;                             //1 - значит рабочий день из справочника kind_working_time
                                    $grahic_array_plan[$i]['working_time_id'] = $working_time->working_time_id;
                                    $grahic_array_plan[$i]['description'] = "";
                                    $i++;
                                }
                            }
                        }
                    }
                    foreach ($role->fact_schedule as $day) {
                        //$warnings[] = $day;
                        if (property_exists($day, "working_time") and $day->working_time !== NULL) {
                            foreach ($day->working_time as $working_time) {
                                if (property_exists($working_time, 'index')) {
                                    if (property_exists($working_time->index, 'shift')) {
                                        foreach ($working_time->index->shift as $shift) {
                                            if ($shift !== null and property_exists($shift, 'shift_id') and isset($chane_arr[$day->chane_id])) {
                                                $grahic_array_fact[$j]['grafic_tabel_main_id'] = $new_grafic_tabel_main;
                                                $grahic_array_fact[$j]['day'] = $day->day;
                                                $grahic_array_fact[$j]['chane_id'] = $chane_arr[$day->chane_id];
                                                $grahic_array_fact[$j]['shift_id'] = $shift->shift_id;
                                                $grahic_array_fact[$j]['worker_id'] = $grafic->worker_id;
                                                $grahic_array_fact[$j]['hours_value'] = $shift->hours_value;
                                                $grahic_array_fact[$j]['role_id'] = $role->role_id;
                                                $grahic_array_fact[$j]['date_time'] = date("Y-m-d", strtotime($year . '-' . $month . '-' . $day->day));
                                                $grahic_array_fact[$j]['month'] = $month;
                                                $grahic_array_fact[$j]['year'] = $year;
                                                $grahic_array_fact[$j]['kind_working_time_id'] = 1;                             //1 - значит рабочий день из справочника kind_working_time
                                                $grahic_array_fact[$j]['working_time_id'] = $working_time->working_time_id;
                                                $grahic_array_fact[$j]['description'] = "";
                                                $j++;
                                            }
                                        }
                                    } elseif (property_exists($working_time->index, 'kind_working_time')) {
                                        foreach ($working_time->index->kind_working_time as $kind_working_time) {
                                            if (isset($chane_arr[$day->chane_id])) {
                                                $grahic_array_fact[$j]['grafic_tabel_main_id'] = $grafic->grafic_main_id->id;
                                                $grahic_array_fact[$j]['day'] = $day->day;
                                                $grahic_array_fact[$j]['chane_id'] = $chane_arr[$day->chane_id];
                                                $grahic_array_fact[$j]['shift_id'] = 5;                                         //5 значит без смены
                                                $grahic_array_fact[$j]['worker_id'] = $grafic->worker_id;
                                                $grahic_array_fact[$j]['hours_value'] = NULL;
                                                $grahic_array_fact[$j]['role_id'] = $role->role_id;
                                                $grahic_array_fact[$j]['date_time'] = date("Y-m-d", strtotime($year . '-' . $month . '-' . $day->day));
                                                $grahic_array_fact[$j]['month'] = $month;
                                                $grahic_array_fact[$j]['year'] = $year;
                                                $grahic_array_fact[$j]['kind_working_time_id'] = $kind_working_time->kind_working_time_id;
                                                $grahic_array_fact[$j]['working_time_id'] = $working_time->working_time_id;
                                                $grahic_array_fact[$j]['description'] = "";
                                                $j++;
                                            }
                                        }
                                    }
                                } elseif (property_exists($working_time, 'kind_working_time')) {
                                    $grahic_array_fact[$j]['grafic_tabel_main_id'] = $new_grafic_tabel_main;
                                    $grahic_array_fact[$j]['day'] = $day->day;
                                    $grahic_array_fact[$j]['chane_id'] = $chane_arr[$day->chane_id];
                                    $grahic_array_fact[$j]['shift_id'] = 5;
                                    $grahic_array_fact[$j]['worker_id'] = $grafic->worker_id;
                                    $grahic_array_fact[$j]['hours_value'] = 0;
                                    $grahic_array_fact[$j]['role_id'] = $role->role_id;
                                    $grahic_array_fact[$j]['date_time'] = date("Y-m-d", strtotime($year . '-' . $month . '-' . $day->day));
                                    $grahic_array_fact[$j]['month'] = $month;
                                    $grahic_array_fact[$j]['year'] = $year;
                                    $grahic_array_fact[$j]['kind_working_time_id'] = $working_time->kind_working_time;                             //1 - значит рабочий день из справочника kind_working_time
                                    $grahic_array_fact[$j]['working_time_id'] = $working_time->working_time_id;
                                    $grahic_array_fact[$j]['description'] = "";
                                    $j++;
                                } else {
                                    $grahic_array_fact[$j]['grafic_tabel_main_id'] = $new_grafic_tabel_main;
                                    $grahic_array_fact[$j]['day'] = $day->day;
                                    $grahic_array_fact[$j]['chane_id'] = $chane_arr[$day->chane_id];
                                    $grahic_array_fact[$j]['shift_id'] = 5;
                                    $grahic_array_fact[$j]['worker_id'] = $grafic->worker_id;
                                    $grahic_array_fact[$j]['hours_value'] = 0;
                                    $grahic_array_fact[$j]['role_id'] = $role->role_id;
                                    $grahic_array_fact[$j]['date_time'] = date("Y-m-d", strtotime($year . '-' . $month . '-' . $day->day));
                                    $grahic_array_fact[$j]['month'] = $month;
                                    $grahic_array_fact[$j]['year'] = $year;
                                    $grahic_array_fact[$j]['kind_working_time_id'] = 1;                             //1 - значит рабочий день из справочника kind_working_time
                                    $grahic_array_fact[$j]['working_time_id'] = $working_time->working_time_id;
                                    $grahic_array_fact[$j]['description'] = "";
                                    $j++;
                                }
                            }
                        }
                    }
                }

            }
            /**
             * Блок записи статусов ВГК в свойства работника
             */

            foreach ($list_vgk as $worker_vgk)
                if ($worker_vgk->vgk == 1) {
                    $workers_vgk_true[] = (int)$worker_vgk->id;
                } else {
                    $workers_vgk_false[] = (int)$worker_vgk->id;
                }


            if (isset($workers_vgk_false)) {
                $workers_string = implode(',', $workers_vgk_false);
                $sql = "UPDATE worker SET worker.vgk=0  WHERE worker.id in ($workers_string)";
                $execute_result = Yii::$app->db->createCommand($sql)->execute();
                $warnings[] = "SaveGraphic. Количество обновленных записей Установлено 0: " . $execute_result;
                $warnings[] = $workers_vgk_false;
            }
            unset ($workers_string);
            if (isset($workers_vgk_true)) {
                $workers_string = implode(',', $workers_vgk_true);
                $sql = "UPDATE worker SET worker.vgk=1  WHERE worker.id in ($workers_string)";
                $execute_result = Yii::$app->db->createCommand($sql)->execute();
                $warnings[] = "SaveGraphic. Количество обновленных записей Установлено 1: " . $execute_result;
                $warnings[] = $workers_vgk_true;
            }
            unset ($workers_string);
            // Если массив плановых выходов не пуст, то заполняем массово grafic_tabel_date_plan

            if ($gtm_id != -1) {


                $warnings[] = "SaveGraphic. Ключ главного графика выходов взял с фронта - есть такой график выходов";
            }
            if (!empty($grahic_array_plan))                                                 // Если данные графика получены
            {
                $warnings[] = 'SaveGraphic. Удаление старого планового графика если он был';
                //$delete_insert = Yii::$app->db->createCommand()->delete('grafic_tabel_date_plan', 'grafic_tabel_main_id=' . $new_grafic_tabel_main)->execute();
                $delete_insert = GraficTabelDatePlan::deleteAll(['grafic_tabel_main_id' => $new_grafic_tabel_main]);
                $warnings[] = "SaveGraphic. Удалено план ";
                $warnings[] = $delete_insert;
                //                GraficTabelDatePlan::deleteAll(['grafic_tabel_main_id' => $new_grafic_tabel_main]);
                $warnings[] = 'SaveGraphic. Добавление данных нового графика';
                /**************************** Заполнение графика ****************************/
//                $dfgsdfg = (object) array();
//                $dfgsdfg['d1']=234;
//                $dfgsdfg[]=234;
                $insert_result = Yii::$app->db->createCommand()->batchInsert('grafic_tabel_date_plan',
                    ['grafic_tabel_main_id', 'day', 'chane_id', 'shift_id', 'worker_id', 'hours_value', 'role_id',
                        'date_time', 'month', 'year', 'kind_working_time_id', 'working_time_id', 'description'], $grahic_array_plan)
                    ->execute();

                $warnings[] = 'SaveGraphic. Добавление графика закончено';
                if ($insert_result === 0) {
                    $status = 0;
                    $errors[] = 'SaveGraphic. Во время сохранения графика возникла ошибка';
                } else {
                    $warnings[] = 'SaveGraphic. График плановый сохранен';
                    $status *= 1;
                }
            } else {
                $warnings[] = 'SaveGraphic. не заполнен ни один работник';
            }

            # region Обновление роли работника из графика выходов
            if (isset($worker_list_for_search) and isset($workers_role)) {
//                $warnings[]=$workers_role;
                $warnings['Массив на обновление ролей исходник'] = $workers_role;
//            $uniq_graf_workers = array_unique($grafic_workers);
                foreach ($worker_list_for_search as $worker_item) {
                    $uniq_graf_workers[] = $worker_item;
                }
                $worker_objects = WorkerObject::find()
                    ->select(['id', 'role_id', 'worker_id', 'object_id'])
                    ->where(['in', 'worker_id', $uniq_graf_workers])
                    ->indexBy('worker_id')
                    ->asArray()
                    ->all();

                foreach ($workers_role as $worker_item) {

                    if (isset($worker_objects[$worker_item['worker_id']]) and
                        ($worker_objects[$worker_item['worker_id']]['role_id'] !== $worker_item['role_id'] or $worker_objects[$worker_item['worker_id']]['role_id'] == null)
                    ) {
                        $update_w_o[] = [
                            $worker_objects[$worker_item['worker_id']]['id'],
                            $worker_item['worker_id'],
                            $worker_objects[$worker_item['worker_id']]['object_id'],
                            $worker_item['role_id']
                        ];
                    }
                }
                if (isset($update_w_o)) {
                    $warnings['Массив на обновление ролей Финал'] = $update_w_o;
                    $sql = Yii::$app->db->queryBuilder->batchInsert('worker_object',
                        ['id', 'worker_id', 'object_id', 'role_id'],
                        $update_w_o);
                    $result_query = Yii::$app->db->createCommand($sql . "ON DUPLICATE KEY UPDATE `role_id` = VALUES (`role_id`)")->execute();
                    if ($result_query !== 0) {
                        $warnings[] = 'SaveGraphic. Роли работников успешно изменены';
                    }
                }
            }
            #endregion

            // Если массив плановых выходов не пуст, то заполняем массово grafic_tabel_date_plan
            if (!empty($grahic_array_fact))                                                 // Если данные графика получены
            {
                $warnings[] = 'SaveGraphic. Удаление старого фактического графика если он был';
//                GraficTabelDateFact::deleteAll(['grafic_tabel_main_id' => $new_grafic_tabel_main]);
                //$delete_insert = Yii::$app->db->createCommand()->delete('grafic_tabel_date_fact', 'grafic_tabel_main_id=' . $new_grafic_tabel_main)->execute();
                $delete_insert = GraficTabelDateFact::deleteAll(['grafic_tabel_main_id' => $new_grafic_tabel_main]);
                $warnings[] = "SaveGraphic. Удалено факт ";
                $warnings[] = $delete_insert;
                $warnings[] = 'SaveGraphic. Добавление данных нового графика';
                /**************************** Заполнение графика ****************************/
                $insert_result = Yii::$app->db->createCommand()->batchInsert('grafic_tabel_date_fact',
                    ['grafic_tabel_main_id', 'day', 'chane_id', 'shift_id', 'worker_id', 'hours_value', 'role_id',
                        'date_time', 'month', 'year', 'kind_working_time_id', 'working_time_id', 'description'], $grahic_array_fact)
                    ->execute();
                $warnings[] = 'SaveGraphic. Добавление графика закончено';

                if ($insert_result === 0) {
                    $status = 0;
                    $errors[] = 'SaveGraphic. Во время сохранения графика возникла ошибка';
                } else {
                    $warnings[] = 'SaveGraphic. График фактический сохранен';
                    $status *= 1;
                }
            }


            /**************************** Добавление шаблона ****************************/
            if (property_exists($graph_worker, 'template')) {
                if (!empty($graph_worker->template))                                                 // Если данные графика получены
                {

                    $template_array = (array)$graph_worker->template;
                    if (isset($template_array) && !empty($template_array)) {
                        $gct_batch_insert_array = array();
                        $warnings[] = 'SaveGraphic. Добавление шаблона начато';
                        foreach ($template_array as $template_chanes)                                                            // Перебор списка звеньев, звенья - объекты
                        {
                            foreach ($template_chanes->chane as $item_chane) {
                                foreach ($item_chane->day as $graphic_day)                                                 // Перебор данных для каждого дня работника
                                {
                                    if (!empty($id_new_chane_for_template)) {
                                        $date_template = "{$year}-{$month}-{$graphic_day->day} 00:00:00";
                                        $gct_batch_insert_array[] = [$new_grafic_tabel_main, $graphic_day->shift_id, $id_new_chane_for_template[$item_chane->chane_id],
                                            $date_template, $graphic_day->working_time_id];
                                    } else {
                                        $date_template = "{$year}-{$month}-{$graphic_day->day} 00:00:00";
                                        $gct_batch_insert_array[] = [$new_grafic_tabel_main, $graphic_day->shift_id, $item_chane->chane_id,
                                            $date_template, $graphic_day->working_time_id];
                                    }

                                }
                            }

                        }
                        $warnings['Весь массив'] = $gct_batch_insert_array;
                        $insert_result = Yii::$app->db->createCommand()
                            ->batchInsert('grafic_chane_table',
                                ['grafic_tabel_main_id', 'shift_id', 'chane_id', 'date_time', 'working_time_id'],
                                $gct_batch_insert_array)
                            ->execute();
                        $warnings[] = 'SaveGraphic. Добавление шаблона закончено';
                        if ($insert_result === 0) {
                            $status = 0;
                            $errors[] = 'SaveGraphic. Во время сохранения шаблона возникла ошибка';
                        }
                    }


                }
            }

//            $transaction->commit();
            $warnings[] = 'SaveGraphic. Достигнут конец метода';

        } catch (Throwable $e) {
//            $transaction->rollBack();
            $status = 0;
            $errors[] = 'SaveGraphic. Исключение';
            $errors[] = $day;
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }


        $result['new_brigade_id'] = $new_brigade_id;
        $result['new_gtm_id'] = $new_grafic_tabel_main;

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Название метода: actionBrigadeChaneMain()
     * Метод получения подробной информации о звеньях в бригаде
     *
     * TODO: Переписать используя ActiveRecord
     * @param $datas [ идентификатор бригады, год на который нужно получить информацию о бригаде ]
     * @return array
     *
     * Входные необязательные параметры
     *
     * @url localhost/ordersystem/ordersystem/brigade-chane-main?brigade_id=236784&year=2019
     *
     * @throws \yii\db\Exception
     * Документация на портале:
     * @package app\controllers\ordersystem
     *
     * Входные обязательные параметры:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 28.03.2019 15:05
     * @since ver
     */
    public static function actionBrigadeChaneMain()
    {
        $post = array();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $post = Yii::$app->request->post();
            if (!$post) {
                $post = json_decode(file_get_contents("php://input"), TRUE);
            }
        } elseif ($_SERVER['REQUEST_METHOD'] == 'GET') $post = Yii::$app->request->get();
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                              // Массив результатов работы метода
        if (isset($post['brigade_id'], $post['year']) and !empty($post['brigade_id']) and !empty($post['year'])) {
            $brigade_id = $post['brigade_id'];
            $year = $post['year'];
            $params = [
                ':brigade_id' => $brigade_id,
                ':year' => $year
            ];
            $chosen_year = $post['year'];                                                                               // Количество работников по дням и сменам
            // Процент выхождаемости
            // Получить данные о том скольким людям было запланировано выйти на работу и они вышли. Группировка по звеньям
            $count_recoverability_execution = Yii::$app->db->createCommand('
                SELECT COUNT(working_time_fact_id)/COUNT(working_time_plan_id)*100 AS recoverbility_persent, 
                        COUNT(working_time_fact_id) AS recoverbility_fact, 
                        COUNT(working_time_plan_id) AS recoverbility_plan, 
                        chane_id,
                        chane_title,
                        month,
                        year,
                        brigade_id,
                        brigade_title,
                        COUNT(working_time_fact_id)/COUNT(working_time_plan_id)*100 AS execution_persent,
                        SUM(hours_fact_value) AS hours_fact_value,
                        SUM(hours_plan_value) AS hours_plan_value,
                        COUNT(injunction_id) AS injunction_count
                FROM view_worker_recoverbility 
                WHERE working_time_fact_id = 1 
                    OR working_time_plan_id = 1 
                    AND chane_id IS NOT NULL 
                    AND brigade_id = :brigade_id
                    AND (year = :year OR year = :year - 1)
                GROUP BY  chane_id, brigade_id, year, month')
                ->bindValues($params)
                ->queryAll();
            $index_i = 0;                                                                                              // Индекс звена
            $pab_counter = 0;                                                                                           // Количество предписаний
            $hours_fact_counter = 0;                                                                                    // Счетчик часов по факту
            $hours_plan_counter = 0;                                                                                    // Счетчик часов по плану
            $recoverbility_plan = 0;                                                                                    // Выхождаемость по плану
            $recoverbility_fact = 0;                                                                                    // Выхождаемость по факту
            $year_name = "";                                                                                            // Наименование года(предыдущий/текущий)
            if (isset($count_recoverability_execution) and !empty($count_recoverability_execution)) {
                $result['id'] = $count_recoverability_execution[0]['brigade_id'];                                       // Записываем идентификатор бригады в результирующий массив
                $result['title'] = $count_recoverability_execution[0]['brigade_title'];                                 // Записываем наименование в результирующий массив
                $result['chains'][0]['id'] = $count_recoverability_execution[0]['chane_id'];                            // Записываем информацию о первом звене в выборке
                $result['chains'][0]['title'] = $count_recoverability_execution[0]['chane_title'];
                foreach ($count_recoverability_execution as $chane_statistic) {
                    // Если звено текущей итерации отличается от ранее добавленного
                    if ($result['chains'][$index_i]['id'] != $chane_statistic['chane_id'])                              // Если идентификатор звена добавленного в результирующий массив на предыдущей итерации отличается от идентификатор звена текйщей итерации.
                    {
                        // Добавляем данные о процентах для звена предыдущей итерации
                        ($recoverbility_plan === 0) ? $recoverbility_persent = 0 : $recoverbility_persent = $recoverbility_fact / $recoverbility_plan * 100;    // Подсчитываем процент выхождаемости звена
                        ($hours_plan_counter === 0) ? $execution_persent = 0 : $execution_persent = $hours_fact_counter / $hours_plan_counter * 100;             // Подсчитываем процент процент выполнения плана
                        $result['chains'][$index_i]['outgoing'] = $recoverbility_persent;                               // Добавляем результаты расчетов в результирующий массив
                        $result['chains'][$index_i]['plan_fact_percent'] = $execution_persent;
                        $result['chains'][$index_i]['pab'] = $pab_counter;
                        $result['chains'][$index_i]['chosenYear'] = $chosen_year;
                        $index_i++;
                        $pab_counter = 0;                                                                               // Обнуляем счетчики
                        $hours_fact_counter = 0;
                        $hours_plan_counter = 0;
                        $recoverbility_plan = 0;
                        $recoverbility_fact = 0;
                        $result['chains'][$index_i]['id'] = $chane_statistic['chane_id'];
                        $result['chains'][$index_i]['title'] = $chane_statistic['chane_title'];
                    }
                    // Если год изменился(при группировке идет сортировка от меньшего к большему) следовательно перешли на выбранный год
                    // Для него нужен и факт и план
                    if ($chosen_year == $chane_statistic['year']) {
                        $year_name = "chosenYear";
                        $result['chains'][$index_i]['plan'][$year_name][$chane_statistic['month']] = $chane_statistic['hours_plan_value'];  // Добавляем в результирующий массив цель(план)
                    } else {
                        $year_name = "prevYear";
                    }
                    $result['chains'][$index_i]['fact'][$year_name][$chane_statistic['month']] = $chane_statistic['hours_fact_value'];      // Добавляем в результирующий массив выполнение плана по факту
                    $hours_fact_counter += $chane_statistic['hours_fact_value'];                                                            // Увеличиваем счетчик на количество часов по факту
                    $hours_plan_counter += $chane_statistic['hours_plan_value'];                                                            // Увеличиваем счетчик на количество часов по плану
                    $recoverbility_plan += $chane_statistic['recoverbility_plan'];                                                          // Увеличиваем счетчик на выхождаемость по плану
                    $recoverbility_fact += $chane_statistic['recoverbility_fact'];                                                          // Увеличиваем счетчик на выхождаемость по факту
                    $pab_counter += $chane_statistic['injunction_count'];                                                                   // Увеличиваем счетчик количества ПАБов
                }
            } else {
                $errors[] = "Ошибка получения данных из БД";
            }
        } else {
            $errors[] = "Не задан ИД бригады или год";
        }
        $result = array('errors' => $errors, 'brigadeInfo' => $result);                                                                   // Готовим результирующий массив который будем возвращать в JSON формате на фронт
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        \Yii::$app->response->data = $result;
    }


    /**
     * Этот метод получает спиоск людей по заданной бригаде использвется при построении графика для распределения людей по вкладкам
     *
     * Структура получаемых данных:
     *
     * Название метода: actionListWorkersChane()
     * @param null $data
     * @return array
     *
     * Входные данные: $data - JSON в котором есть идентификатор бригады
     *
     * @package frontend\controllers\ordersystem
     * @see
     * @example
     *
     * http://amicum/read-manager-amicum?controller=ordersystem\WorkSchedule&method=GetListWorkerChaneForReadManager&subscribe=worker_list&data={"brigade_id":"44"}
     *
     * Документация на портале:
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.05.2019 13:22
     * @since ver
     */
    public static function GetListWorkerChaneForReadManager($data_post = NULL)
    {
//        $post = Assistant::GetServerMethod();
        $status = 1;                                                                                                      //флаг успешного выполнения метода
        $warnings = array();                                                                                              // массив предупреждений
        $errors = array();                                                                                                // массив ошибок
        $result = array();
        $role_list = array();
        $id_worker_object = 0;
        if ($data_post !== NULL && $data_post !== '')                                                               // Проверяем передана ли входная JSON строка
        {
            try {
                $post_data = json_decode($data_post);
                if (property_exists($post_data, 'brigade_id'))                                                   //существет ли в полученом JSON ид бригады
                {
                    $brigade_id = $post_data->brigade_id;
                    $brigade = Brigade::find()->where(['id' => $brigade_id])->limit(1)->one();
                    if ($brigade) {
                        $worker_list = Worker::find()
                            ->joinWith('employee')
                            ->joinWith('chaneWorkers')
                            ->joinWith('workerObjects')
                            ->joinWith('brigades')
                            ->joinWith('workerObjectsRole')
                            ->joinWith('position')
                            ->joinWith('role')
                            ->joinWith('chane')
                            ->joinWith('chaneType')
                            ->where(['chane.brigade_id' => $brigade_id]);                                                                               //ищем бригаду по фильтру
                        if ($worker_list) {
                            foreach ($worker_list->each() as $worker) {
                                $result[$brigade_id]['brigader_id'] = $brigade->brigader_id;
                                $result[$brigade_id]['description'] = $brigade->description;
                                $result[$brigade_id]['date_time'] = $brigade->date_time;
                                $result[$brigade_id]['company_department_id'] = $brigade->company_department_id;
                                $result[$brigade_id]['status_id'] = $brigade->status_id;

                                foreach ($worker->chane as $chane_item) {
                                    foreach ($worker->workerObjectsRole as $role_item) {
                                        if ($id_worker_object == $role_item->worker_object_id) {
                                            $id_worker_object = $role_item->worker_object_id;
                                            $role_list[] = $role_item->role->title;
                                        } else {
                                            $role_list[0] = $role_item->role->title;
                                        }
                                    }
                                    $result[$brigade_id]['chane'][$chane_item->id]['chaner_id'] = $chane_item->chaner_id;
                                    $result[$brigade_id]['chane'][$chane_item->id]['chane_type'] = $chane_item->chaneType->title;
                                    $result[$brigade_id]['chane'][$chane_item->id]['chane_title'] = $chane_item->title;
                                    $result[$brigade_id]['chane'][$chane_item->id]['worker'][$worker->workerObjects[0]->id]['full_name'] = $worker->employee->last_name . ' ' . $worker->employee->first_name . ' ' . $worker->employee->patronymic;
                                    $result[$brigade_id]['chane'][$chane_item->id]['worker'][$worker->workerObjects[0]->id]['tabel_number'] = $worker->tabel_number;
                                    $result[$brigade_id]['chane'][$chane_item->id]['worker'][$worker->workerObjects[0]->id]['role'] = $role_list;
                                    $result[$brigade_id]['chane'][$chane_item->id]['worker'][$worker->workerObjects[0]->id]['qualification'] = $worker->position->qualification;
                                    $result[$brigade_id]['chane'][$chane_item->id]['worker'][$worker->workerObjects[0]->id]['time'] = 126;

                                }
                            }
                        } else {
                            $warnings[] = 'actionGetListWorkerChaneForReadManager. Не найден список работников.';
                            $status = 0;
                        }
                    } else {
                        $warnings[] = "actionGetListWorkerChaneForReadManager. Бригада не найдена. Переданный идентификатор бригады {$brigade_id}";
                        $status = 0;
                    }
                } else {
                    $warnings[] = 'GetListWorkerChaneForReadManager. Переданные данные не верны. ';
                }
            } catch (Exception $exception) {
                $status = 0;
                $errors[] = $exception->getMessage();
            }
        } else {
            $errors[] = "actionGetListWorkerChaneForReadManager. Входной массив обязательных данных пуст. Бригада не передана.";
            $status = 0;
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    public static function actionListWorkersChane($brigade_id)
    {
        $status = 1; //флаг успешного выполнения метода
        $filter = array(); //массив фильтр
        $warnings = array(); // массив предупреждений
        $errors = array(); // массив ошибок
        $result = array();

        $filter = array('chane.brigade_id' => $brigade_id);
        $worker_list = Worker::find()
            ->joinWith('employee')
            ->joinWith('chaneWorkers')
            ->joinWith('workerObjects')
            ->joinWith('brigades')
            ->joinWith('workerObjectsRole')
            ->joinWith('position')
            ->joinWith('role')
            ->joinWith('chane')
            ->joinWith('chaneType')
            ->where($filter);
        foreach ($worker_list->each() as $worker) {
            foreach ($worker->chane as $chane_item) {
                $result[$chane_item->id]['chaner_id'] = $worker->chane[0]->chaner_id;
                $result[$chane_item->id]['chane_type'] = $chane_item->chaneType->id;
                $result[$chane_item->id]['chane_title'] = $chane_item->title;
                $result[$chane_item->id]['workers'][$worker->id]['worker_id'] = $worker->id;
                if ($worker->id == $worker->chane[0]->chaner_id) {
                    $result[$chane_item->id]['workers'][$worker->id]['chaner_id'] = 1;
                } else {
                    $result[$chane_item->id]['workers'][$worker->id]['chaner_id'] = 0;
                }
            }
            return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        }
    }

    // funcListWorkerCompanyDepartment - метод получения списка людей в конкретном департаменте с указанием бригад и звеньев в которых они состоят
    // входной параметр:
    //      $company_department_id  - ключ конкретного департамента
    // выходные параметры:
    //      стандартный набор
    // разработал: хз кто. Якимов М.Н. сделал описание и потимизировал работу метода
    // редактирование метода:   Якимов М.Н. 16.07.2019  -   удалил из метода получение бригад и звеньев - т.к. было выполнено не верно
    // дата: 08.06.2019
    //
    private static function funcListWorkerCompanyDepartment($company_department_id, $filter_array_worker = [])
    {
        $status = 1;                                                                                                    //флаг успешного выполнения метода
        $warnings = array();                                                                                            // массив предупреждений
        $errors = array();                                                                                              // массив ошибок
        $result = array();
        $no_worker = 0;
        try {
            $warnings[] = "funcListWorkerCompanyDepartment. Начал выполнять метод";
            $date_time = Assistant::GetDateNow();
            $worker_list = Worker::find()
                ->joinWith('employee')
                ->joinWith('companyDepartment')
                ->joinWith('workerObjects')
                ->joinWith('position')
                ->joinWith('workerObjects.role')
                ->andWhere([
                    'worker.company_department_id' => $company_department_id
                ])
                ->andWhere(
                    'worker.date_end>"' . $date_time . '"'
                )
                ->orWhere(["worker.id" => $filter_array_worker]);


            if (!$worker_list) {
                new Exception('funcListWorkerCompanyDepartment. Работников нет в БД по заданному департаменту');
            }
            foreach ($worker_list->each(300) as $worker) {
                foreach ($worker->workerObjects as $worker_object_item) {
                    $result['workers'][$worker->id]['worker_id'] = $worker->id;
                    $result['workers'][$worker->id]['worker_full_name'] = $worker->employee->last_name . ' ' . $worker->employee->first_name . ' ' . $worker->employee->patronymic;
                    $result['workers'][$worker->id]['worker_tabel_number'] = $worker->tabel_number;
                    if ($worker_object_item->role_id == NULL) {
                        $result['workers'][$worker->id]['role_id'] = 9;
                        $result['workers'][$worker->id]['role_title'] = 'Прочее';
                    } else {
                        $result['workers'][$worker->id]['role_id'] = $worker_object_item->role_id;
                        $result['workers'][$worker->id]['role_title'] = $worker_object_item->role->title;
                    }
                    $result['workers'][$worker->id]['worker_object_id'] = $worker_object_item->id;
                    $result['workers'][$worker->id]['worker_position_qualification'] = $worker->position->qualification;
                }
            }

            foreach ($filter_array_worker as $worker_id) {
                if (!isset($result['workers'][$worker_id])) {
                    $result['workers'][$worker_id] = array(
                        'worker_id' => $worker_id,
                        'worker_full_name' => "Удаленный сотрудник из БД",
                        'worker_tabel_number' => $worker_id,
                        'role_id' => "9",
                        'role_title' => "Прочее",
                        'worker_object_id' => "26",
                        'worker_position_qualification' => "1",
                    );
                }
            }

        } catch (Throwable $ex) {
            $errors[] = "funcListWorkerCompanyDepartment. Исключение: ";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $warnings[] = "funcListWorkerCompanyDepartment. Закончил выполнять метод";
        if ($result == null) {
            $result = (object)array();
            $no_worker = 1;
        }
        return array('Items' => $result, 'no_worker' => $no_worker, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }
    // GetOrderGraphic - метод получения графика выходов по наряду - метод не дописан
    // входной параметр:
    //      $company_department_id  - ключ конкретного департамента
    //      $year                   - год
    //      $month                  - месяц
    // выходные параметры:
    //      стандартный набор + graphic - object
    // Разработал: Якимов М.Н.
    // дата: 24.12.2020
    //
    private static function GetOrderGraphic($company_department_id, $year, $month)
    {
        $status = 1;                                                                                                    //флаг успешного выполнения метода
        $warnings = array();                                                                                            // массив предупреждений
        $errors = array();                                                                                              // массив ошибок
        $result = array();
        try {
            $warnings[] = "GetOrderGraphic. Начал выполнять метод";

            $cal_day = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            $graphic = array();
            for ($j = 1; $j <= cal_days_in_month(CAL_GREGORIAN, $month, $year); $j++) {
                $graphic['days'][$j]['day'] = $j;
                $graphic['days'][$j]['workers'] = array();
            }


        } catch (Throwable $ex) {
            $errors[] = "funcListWorkerCompanyDepartment. Исключение: ";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $warnings[] = "funcListWorkerCompanyDepartment. Закончил выполнять метод";
        if ($result == null) {
            $result = (object)array();
        }
        return array('Items' => $result, 'graphic' => $graphic, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Этот метод получает список работников по переданному идентификатору (company_department_id) применяется на вкладке персонала
     *
     * Структура получаемых данных:Работники
     *                              идентификатор работника
     *                              ФИО
     *                              табельный номер
     *                              главная роль
     * Название метода: ListWorkerCompanyDepartment()
     * @param null $data
     * @return array
     *
     * Входные параметры: $data - JSON содержащий идентификатор company_department
     *
     * @package frontend\controllers\ordersystem
     * @see
     * @example
     *
     * http://amicum/read-manager-amicum?controller=ordersystem\WorkSchedule&method=ListWorkerCompanyDepartment&subscribe=worker_list&data={"company_department_id":"801"}
     *
     * Документация на портале:
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.05.2019 18:55
     * @since ver
     */
    public static function ListWorkerCompanyDepartment($data_post = NULL)
    {
        //        $post = Assistant::GetServerMethod();
        $status = 1; //флаг успешного выполнения метода
        $warnings = array(); // массив предупреждений
        $errors = array(); // массив ошибок
        $result = array();
        if ($data_post !== NULL && $data_post !== '')                                                               // Проверяем передана ли входная JSON строка
        {
            try {
                $post_data = json_decode($data_post);
                if (property_exists($post_data, 'company_department_id'))                                        //есть ли в полученном JSON company_department_id
                {
                    $warnings[] = 'ListWorkerCompanyDepartment. Данные успешно получены. ';
                    $company_department_id = $post_data->company_department_id;
                    $worker_list = Worker::find()
                        ->joinWith('employee')
                        ->joinWith('companyDepartment')
                        ->joinWith('workerObjects')
                        ->joinWith('position')
                        ->joinWith('workerObjects.role')
                        ->joinWith('brigades')
                        ->where(['worker.company_department_id' => $company_department_id]);                            //ищем всех людей по copany_department
                    foreach ($worker_list->each() as $worker)                                                           //перебираем всех работников
                    {
                        //$warnings[] = 'ListWorkerCompanyDepartment. Перебор найденных работников.';
                        foreach ($worker->workerObjects as $worker_object_item)                                         //перебираем worker_object
                        {
                            //$warnings[] = 'ListWorkerCompanyDepartment. Формирую реузльтирующий массив.';
                            $result['workers'][$worker->id]['worker_id'] = $worker->id;
                            $result['workers'][$worker->id]['full_name'] = $worker->employee->last_name . ' ' . $worker->employee->first_name . ' ' . $worker->employee->patronymic;
                            $result['workers'][$worker->id]['tabel_number'] = $worker->tabel_number;
                            if ($worker_object_item->role_id == NULL) {
                                $result['workers'][$worker->id]['role_main'] = 'null';
                            } else {
                                $result['workers'][$worker->id]['role_main'] = $worker_object_item->role->title;
                            }
                            $result['workers'][$worker->id]['worker_object_id'] = $worker_object_item->id;
                            $result['workers'][$worker->id]['qualification'] = $worker->position->qualification;
                        }
                    }
                } else {
                    $warnings[] = 'ListWorkerCompanyDepartment. Переданы не верные данные. Проверьте передаваемые параметры';
                }
            } catch (Exception $exception) {
                $status = 0;
                $errors[] = $exception->getMessage();
            }
        } else {
            $errors[] = "ListWorkerCompanyDepartment. Входной массив обязательных данных пуст. Участок не передан.";
            $status = 0;
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Название метода: GetSummaryWorkerByDayAndMonth()
     * Метод получения сводной информации по количеству работников на выход по дням и сменам
     * по конкретному департаменту на конкретный год и месяц
     *
     * @param null $data_post
     * @return array
     * @package frontend\controllers\ordersystem
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\WorkSchedule&method=GetSummaryWorkerByDayAndMonth&subscribe=&data={"company_department_id":801,"year":2019,"month":5}
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 25.05.2019 19:46
     * @since ver
     */
    public static function GetSummaryWorkerByDayAndMonth($data_post = NULL)
    {
        $status = 1;                                                                                                 // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();                                                                                                // Промежуточный результирующий массив
        if ($data_post !== NULL && $data_post !== '') {
            $warnings[] = 'GetSummaryWorkerByDayAndMonth. Данные успешно переданы';
            $warnings[] = 'GetSummaryWorkerByDayAndMonth. Входной массив данных' . $data_post;
            try {
                $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
                // Проверяем переданы ли все параметры
                if (
                    property_exists($post_dec, 'company_department_id') and
                    property_exists($post_dec, 'year') and
                    property_exists($post_dec, 'month')
                )                                                                                                       // Проверяем наличие в нем нужных нам полей
                {
                    $company_department = $post_dec->company_department_id;
                    $grafic_month = $post_dec->month;
                    $grafic_year = $post_dec->year;
                    $warnings[] = 'GetSummaryWorkerByDayAndMonth. Данные получены вызываю метод';
                    // Получаем список людей по идентификатору company_department

                    $summary_worker_by_days = ViewGetSummaryWorkerByDayAndMonth::find()
                        ->where([
                            'company_department_id' => $company_department,
                            'grafic_tabel_main_month' => $grafic_month,
                            'grafic_tabel_main_year' => $grafic_year
                        ])
                        ->asArray()
                        ->all();

                    for ($i = 1; $i <= cal_days_in_month(CAL_GREGORIAN, $grafic_month, $grafic_year); $i++) {
                        $summary_worker_by_day[$i]['sumByDay'] = "0";

                        $dayOfWeek = date("w", strtotime($grafic_year . '-' . $grafic_month . '-' . $i));
                        if ($dayOfWeek == 0 || $dayOfWeek == 6) {
                            $summary_worker_by_day[$i]['weekendsFlag'] = "outputDay";
                        } else {
                            $summary_worker_by_day[$i]['weekendsFlag'] = "";
                        }
                        $summary_worker_by_day[$i]['shift']['1'] = "0";
                        $summary_worker_by_day[$i]['shift']['2'] = "0";
                        $summary_worker_by_day[$i]['shift']['3'] = "0";
                        $summary_worker_by_day[$i]['shift']['4'] = "0";
                    }
                    $result = $summary_worker_by_day;

                    if ($summary_worker_by_days) {
                        foreach ($summary_worker_by_days as $day) {
                            $summary_worker_by_day[$day['day']]['sumByDay'] = $day['sum_day'];
                            $summary_worker_by_day[$day['day']]['shift']['1'] = $day['shift_id1'];
                            $summary_worker_by_day[$day['day']]['shift']['2'] = $day['shift_id2'];
                            $summary_worker_by_day[$day['day']]['shift']['3'] = $day['shift_id3'];
                            $summary_worker_by_day[$day['day']]['shift']['4'] = $day['shift_id4'];
                        }
                        $result = $summary_worker_by_day;
                        $status *= 1;
                    }
                }
            } catch (Throwable $ex) {
                $errors[] = $ex->getMessage();
                $status = 0;
            }
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Название метода: GetListBrigade()
     * Метод получения списка бригад по конкретному департаменту, если задан, то вернет весь список актуальных бригад
     * флаг выборки только бригад или со звеньями. Если флаг = 1, то выбираются только одни бригады
     * если флаг равен 0, то выбираются бригады со звеньями
     *
     * @param null $data_post
     * @param $company_department_id -   ключ конкретного департамента
     * @param $only_brigades -   флаг только бригад
     * @return array
     * @package frontend\controllers\ordersystem
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\WorkSchedule&method=GetListBrigade&subscribe=&data={"company_department_id":801}
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\WorkSchedule&method=GetListBrigade&subscribe=&data={"company_department_id":801,"only_brigades":1}
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\WorkSchedule&method=GetListBrigade&subscribe=&data={"company_department_id":""}
     *
     * Документация на портале:
     * @author Якимов М.Н.
     * Created date: on 25.05.2019 19:46
     * @since ver
     */
    public static function GetListBrigade($data_post = NULL)
    {
        $status = 1;                                                                                                 // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = (object)array();                                                                                                // Промежуточный результирующий массив
        $workers = [];                                                                                                  // массив всех работников найденных в звеньях
        if ($data_post !== NULL && $data_post !== '') {
            $warnings[] = 'GetListBrigade. Данные успешно переданы';
            $warnings[] = 'GetListBrigade. Входной массив данных' . $data_post;
            try {
                $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
                // Проверяем переданы ли все параметры
                if (
                    property_exists($post_dec, 'mine_id')
                )                                                                                                       // Проверяем наличие в нем нужных нам полей
                {
                    $mine_id = $post_dec->mine_id;
                } else {
                    $mine_id = 1;
                }

                if (
                    property_exists($post_dec, 'company_department_id')
                )                                                                                                       // Проверяем наличие в нем нужных нам полей
                {
                    // флаг выборки только бригад или со звеньями. Если флаг = 1, то выбираются только одни бригады
                    // если флаг равен 0, то выбираются бригады со звеньями
                    if (property_exists($post_dec, 'only_brigades')) {
                        $flag_only_brigades = (int)$post_dec->only_brigades;                                            // только бригады
                    } else {
                        $flag_only_brigades = 0;                                                                        //вместе со звеньями
                    }

                    $company_department = $post_dec->company_department_id;


                    $warnings[] = 'GetListBrigade. Данные получены вызываю метод';
                    // Получаем список людей по идентификатору company_department

                    $brigade_model = Brigade::find()
                        ->with('chanes')
                        ->with('chanes.chaneWorkers.worker')// Получаем вложенные связи сразу
                        ->where(['like', 'company_department_id', $company_department])
                        ->all();
                    if ($brigade_model) {
                        foreach ($brigade_model as $brigade) {
                            $brigade_list[$brigade['id']]['brigade_id'] = $brigade['id'];
                            $brigade_list[$brigade['id']]['brigade_description'] = $brigade['description'];
                            $brigade_list[$brigade['id']]['brigader_id'] = $brigade['brigader_id'];
                            $brigade_list[$brigade['id']]['company_department_id'] = $brigade['company_department_id'];
                            $brigade_list[$brigade['id']]['flag_save'] = FALSE;
                            if ($flag_only_brigades == 0) {
                                $brigade_list[$brigade['id']]['chanes'] = array();
                                foreach ($brigade->chanes as $chane) {
                                    $brigade_list[$brigade['id']]['chanes'][$chane['id']]['brigade_id'] = $brigade['id'];
                                    $brigade_list[$brigade['id']]['chanes'][$chane['id']]['chane_id'] = $chane['id'];
                                    $brigade_list[$brigade['id']]['chanes'][$chane['id']]['chane_title'] = $chane['title'];
                                    $brigade_list[$brigade['id']]['chanes'][$chane['id']]['chaner_id'] = $chane['chaner_id'];
                                    $brigade_list[$brigade['id']]['chanes'][$chane['id']]['chane_type'] = $chane['chane_type_id'];
                                    $brigade_list[$brigade['id']]['chanes'][$chane['id']]['workers'] = array();
                                    foreach ($chane->chaneWorkers as $chane_worker) {
                                        if ($mine_id == $chane_worker->worker->mine_id) {
                                            $brigade_list[$brigade['id']]['chanes'][$chane['id']]['workers'][$chane_worker->worker->id]['worker_id'] = $chane_worker->worker->id;
                                            $workers[] = $chane_worker->worker->id;
                                        }
                                    }
                                }
                            }
                        }
                        foreach ($brigade_list as $brigade) {
                            foreach ($brigade['chanes'] as $chane) {
                                if (!$chane['workers']) {
                                    $brigade_list[$brigade['brigade_id']]['chanes'][$chane['chane_id']]['workers'] = (object)array();
//                                    $warnings[] = 'GetListBrigade. Пустая бригада';
                                }

                            }
                        }
                        $result = $brigade_list;
                        $status *= 1;
                        $warnings[] = 'GetListBrigade. Метод отработал все ок';
                    }
                }
            } catch (Throwable $ex) {
                $errors[] = $ex->getMessage();
                $errors[] = $ex->getLine();
                $status = 0;
            }
        }

        return array('Items' => $result, 'workers' => $workers, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Название метода: GetBrigades()
     * Внутренний метод получения списка бригад по конкретному департаменту, если задан, то вернет весь список актуальных бригад
     * флаг выборки только бригад или со звеньями. Если флаг = 0, то выбираются только одни бригады
     * если флаг равен 1, то выбираются бригады со звеньями
     *
     * @param $company_department_id -   ключ конкретного департамента
     * @param $flag_with_chane -   флаг только бригад
     * @return array
     * @package frontend\controllers\ordersystem
     *
     * @author Якимов М.Н.
     * Created date: on 16.07.2019 10:34
     * @since ver
     */
    public static function GetBrigades($company_department_id, $flag_with_chane = 1)
    {
        $log = new LogAmicumFront("GetBrigades");
        $result = (object)array();                                                                                      // Промежуточный результирующий массив
        $worker_list_in_grapfic = [];                                                                                   // список работников для получения в графике выходов сведений о работниках
        /**
         * переменные используются для определения принадлежности людей к конкретным звеньям - для определения
         * необходимости вывода на вкладке нераспределенные на графике выходов
         */
        $worker_in_chane = array();                                                                                       // привязка работников к звеньям
        $worker_chane_all = array();                                                                                       // привязка работников к звеньям

        $log->addLog("Начало выполнения метода");
        try {

            // Получаем список людей по идентификатору company_department
            $brigade_model = Brigade::find()
                ->joinWith('chanes')
                ->joinWith('chanes.chaneWorkers.worker.workerObjects.role')// Получаем вложенные связи сразу
                ->joinWith('chanes.chaneWorkers.worker.employee')// Получаем вложенные связи сразу
                ->joinWith('chanes.chaneWorkers.worker.position')// Получаем вложенные связи сразу
                ->where(['brigade.company_department_id' => $company_department_id])
                ->all();

            $log->addLog("Получил данные с БД");

            if ($brigade_model) {
                foreach ($brigade_model as $brigade) {
                    $worker_list_in_grapfic[$brigade['brigader_id']] = $brigade['brigader_id'];
                    $brigade_list[$brigade['id']]['brigade_id'] = $brigade['id'];
                    $brigade_list[$brigade['id']]['brigade_description'] = $brigade['description'];
                    $brigade_list[$brigade['id']]['brigader_id'] = $brigade['brigader_id'];
                    $brigade_list[$brigade['id']]['company_department_id'] = $brigade['company_department_id'];
                    if ($flag_with_chane == 1) {
                        $brigade_list[$brigade['id']]['chanes'] = array();
                        foreach ($brigade->chanes as $chane) {
                            $worker_list_in_grapfic[$chane['chaner_id']] = $chane['chaner_id'];
                            $brigade_list[$brigade['id']]['chanes'][$chane['id']]['brigade_id'] = $brigade['id'];
                            $brigade_list[$brigade['id']]['chanes'][$chane['id']]['chane_id'] = $chane['id'];
                            $brigade_list[$brigade['id']]['chanes'][$chane['id']]['chane_title'] = $chane['title'];
                            $brigade_list[$brigade['id']]['chanes'][$chane['id']]['chaner_id'] = $chane['chaner_id'];
                            $brigade_list[$brigade['id']]['chanes'][$chane['id']]['chane_type'] = $chane['chane_type_id'];

                            foreach ($chane->chaneWorkers as $chane_worker) {
                                $worker_id = $chane_worker->worker->id;
                                $worker_in_chane[$worker_id]['chane_id'] = $chane['id'];


                                if ($chane_worker->worker->workerObjects and $chane_worker->worker->workerObjects[0] and $chane_worker->worker->workerObjects[0]->role) {
                                    $role_id = $chane_worker->worker->workerObjects[0]->role_id;
                                    $role_title = $chane_worker->worker->workerObjects[0]->role->title;
                                    $worker_object_id = $chane_worker->worker->workerObjects[0]->id;
                                } else {
                                    $role_id = 9;
                                    $role_title = "Прочее";
                                    $worker_object_id = $worker_id;
                                }
                                $worker_full_name = $chane_worker->worker->employee->last_name . ' ' . $chane_worker->worker->employee->first_name . ' ' . $chane_worker->worker->employee->patronymic;
                                $worker_tabel_number = $chane_worker->worker->tabel_number;
                                $worker_position_qualification = $chane_worker->worker->position->qualification;

                                // инфа по работникам в бригадах -> звеньях
                                $brigade_list[$brigade['id']]['workers'][$worker_id]['worker_id'] = $worker_id;
                                $brigade_list[$brigade['id']]['workers'][$worker_id]['mine_id'] = $chane_worker->mine_id;

                                $brigade_list[$brigade['id']]['chanes'][$chane['id']]['workers'][$worker_id]['mine_id'] = $chane_worker->mine_id;
                                $brigade_list[$brigade['id']]['chanes'][$chane['id']]['workers'][$worker_id]['worker_id'] = $worker_id;
                                $brigade_list[$brigade['id']]['chanes'][$chane['id']]['workers'][$worker_id]['role_id'] = $role_id;
                                $brigade_list[$brigade['id']]['chanes'][$chane['id']]['workers'][$worker_id]['role_title'] = $role_title;
                                $brigade_list[$brigade['id']]['chanes'][$chane['id']]['workers'][$worker_id]['worker_object_id'] = $worker_object_id;
                                $brigade_list[$brigade['id']]['chanes'][$chane['id']]['workers'][$worker_id]['worker_full_name'] = $worker_full_name;
                                $brigade_list[$brigade['id']]['chanes'][$chane['id']]['workers'][$worker_id]['worker_tabel_number'] = $worker_tabel_number;
                                $brigade_list[$brigade['id']]['chanes'][$chane['id']]['workers'][$worker_id]['worker_position_qualification'] = $worker_position_qualification;

                                // инфа по всем работникам в звеньях
                                $worker_chane_all[$worker_id]['worker_id'] = $worker_id;
                                $worker_chane_all[$worker_id]['role_id'] = $role_id;
                                $worker_chane_all[$worker_id]['role_title'] = $role_title;
                                $worker_chane_all[$worker_id]['worker_object_id'] = $worker_object_id;
                                $worker_chane_all[$worker_id]['worker_full_name'] = $worker_full_name;
                                $worker_chane_all[$worker_id]['worker_tabel_number'] = $worker_tabel_number;
                                $worker_chane_all[$worker_id]['worker_position_qualification'] = $worker_position_qualification;

                                $worker_list_in_grapfic[$worker_id] = $worker_id;
                            }
                            if (!isset($brigade_list[$brigade['id']]['chanes'][$chane['id']]['workers'])) {
                                $chanes_without_workers_items['brigade_id'] = $brigade['id'];
                                $chanes_without_workers_items['chane_id'] = $chane['id'];
                                $chanes_without_workers_array[] = $chanes_without_workers_items;
                            }
                        }
                    }
                    if (!isset($brigade_list[$brigade['id']]['workers'])) {
                        $brigade_without_workers_array[] = $brigade['id'];
                    }
                }

                $log->addLog("Закончил первичную обрабтку");

                if (!$worker_in_chane) {
                    $worker_in_chane = (object)array();
                }
                if (!$worker_chane_all) {
                    $worker_chane_all = (object)array();
                }
                if (isset($brigade_without_workers_array)) {
                    foreach ($brigade_without_workers_array as $brigade_without_workers_item) {
                        $brigade_list[$brigade_without_workers_item]['workers'] = (object)array();
                    }
                }
                if (isset($chanes_without_workers_array)) {
                    foreach ($chanes_without_workers_array as $chanes_without_workers_item) {
                        $brigade_list[$chanes_without_workers_item['brigade_id']]['chanes'][$chanes_without_workers_item['chane_id']]['workers'] = (object)array();
                    }
                }
                foreach ($brigade_list as $key => $brigade) {
                    if (empty($brigade['chanes'])) {
                        $brigade_list[$key]['chanes'] = (object)array();
                    }
                }
                $result = $brigade_list;
                $log->addLog("Закончил восстановление структуры");
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Закончил выполнение метода");

        return array_merge(['Items' => $result, 'worker_in_chane' => $worker_in_chane, 'workers' => $worker_chane_all, "worker_list_in_grapfic" => $worker_list_in_grapfic], $log->getLogAll());
    }

    // GetWorkingTimeList - метод получения списка рабочего времени (явка, больничный, отпуск)
    // выходной массив:
    //      items
    //          working_time_id             -   ключ вида рабочего времени
    //              title                   -   название вида рабочего времени
    //              code                    -   спец код из государтвенного справочника
    //              short_title             -   сокращенное обозначение
    // http://127.0.0.1/read-manager-amicum?controller=ordersystem\WorkSchedule&method=GetWorkingTimeList&subscribe=&data=""
    public static function GetWorkingTimeList($data_post)
    {
        $result = array();                                                                                                // промежуточный результирующий массив
        $errors = array();                                                                                              // массив ошибок
        $warnings = array();                                                                                              // массив предупреждений
        $status = 1;                                                                                                      //  состояния выполнения скрипта
        $list_working = array();
        $warnings[] = "GetWorkingTimeList. Метод начал выполнение";
        try {
            $list_working_times = WorkingTime::find()
                ->limit(500)
                ->asArray()
                ->all();
            foreach ($list_working_times as $list_working_time) {
                $list_working[$list_working_time['id']]['id'] = $list_working_time['id'];
                $list_working[$list_working_time['id']]['title'] = $list_working_time['title'];
                $list_working[$list_working_time['id']]['code'] = $list_working_time['code'];
                $list_working[$list_working_time['id']]['short_title'] = $list_working_time['short_title'];
            }
        } catch (Exception $e) {
            $status = 0;
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $result = $list_working;
        $warnings[] = "GetWorkingTimeList. Вышел с метода";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetKindWorkingTimeList - метод получения  списка видов рабочего времени (охрана труда , выход по запасным выходам, обучение и т.д.)
    // выходной массив:
    //      items
    //          kind_working_time_id             -   ключ вида рабочего времени
    //              title                        -   название вида рабочего времени
    // http://127.0.0.1/read-manager-amicum?controller=ordersystem\WorkSchedule&method=GetKindWorkingTimeList&subscribe=&data=""
    public static function GetKindWorkingTimeList($data_post)
    {
        $result = array();                                                                                                // промежуточный результирующий массив
        $errors = array();                                                                                              // массив ошибок
        $warnings = array();                                                                                              // массив предупреждений
        $status = 1;                                                                                                      //  состояния выполнения скрипта
        $list_kind_working = array();
        $warnings[] = "GetKindWorkingTimeList. Метод начал выполнение";
        try {
            $list_kind_working_times = KindWorkingTime::find()
                ->limit(500)
                ->asArray()
                ->all();
            foreach ($list_kind_working_times as $list_kind_working_time) {
                $list_kind_working[$list_kind_working_time['id']]['id'] = $list_kind_working_time['id'];
                $list_kind_working[$list_kind_working_time['id']]['title'] = $list_kind_working_time['title'];
                $list_kind_working[$list_kind_working_time['id']]['short_title'] = $list_kind_working_time['short_title'];

            }
        } catch (Exception $e) {
            $status = 0;
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        $result = $list_kind_working;
        $warnings[] = "GetKindWorkingTimeList. Вышел с метода";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetShiftList - метод получения списка смен
    // выходной массив:
    //      items
    //          shift_id                    -   ключ смен
    //              title                   -   название смен
    // http://127.0.0.1/read-manager-amicum?controller=ordersystem\WorkSchedule&method=GetShiftList&subscribe=&data=""
    public static function GetShiftList($data_post)
    {
        $result = array();                                                                                                // промежуточный результирующий массив
        $errors = array();                                                                                              // массив ошибок
        $warnings = array();                                                                                              // массив предупреждений
        $status = 1;                                                                                                      //  состояния выполнения скрипта
        $list_shift_result = array();

        $warnings[] = "GetShiftList. Метод начал выполнение";
        try {
            $list_shifts = Shift::find()
                ->limit(500)
                ->asArray()
                ->all();
            foreach ($list_shifts as $list_shift) {
                $list_shift_result[$list_shift['id']]['id'] = $list_shift['id'];
                $list_shift_result[$list_shift['id']]['title'] = $list_shift['title'];
            }
        } catch (Exception $e) {
            $status = 0;
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result = $list_shift_result;
        $warnings[] = "GetShiftList. Вышел с метода";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetRoleList - метод получения списка ролей
    // выходной массив:
    //      items
    //          role_id                    -   ключ роли
    //              id                      -   ключ роли
    //              title                   -   название смен
    //              weight                  -   вес роли - нужен для сортировки ролей по значимости в списке
    // http://127.0.0.1/read-manager-amicum?controller=ordersystem\WorkSchedule&method=GetRoleList&subscribe=&data=""
    public static function GetRoleList($data_post)
    {
        $result = array();                                                                                                // промежуточный результирующий массив
        $errors = array();                                                                                              // массив ошибок
        $warnings = array();                                                                                              // массив предупреждений
        $status = 1;                                                                                                      //  состояния выполнения скрипта
        $list_shift_result = array();

        $warnings[] = "GetRoleList. Метод начал выполнение";
        try {
            $list_roles = Role::find()
                ->limit(500)
                ->asArray()
                ->all();
            foreach ($list_roles as $list_role) {
                $list_shift_result[$list_role['id']]['id'] = $list_role['id'];
                $list_shift_result[$list_role['id']]['title'] = $list_role['title'];
                $list_shift_result[$list_role['id']]['weight'] = $list_role['weight'];

            }
        } catch (Exception $e) {
            $status = 0;
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result = $list_shift_result;
        $warnings[] = "GetRoleList. Вышел с метода";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // SaveRole - метод сохранения справочника ролей
    // входные данные:
    //      role:
    //                  id                      -   ключ роли
    //                  title                   -   наименование роли
    //                  weight                  -   позиция при сортировке
    //                  type                    -   тип роли (ИТР, рабоие и т.д.)
    //                  surface_underground     -   подземный/поверхностный
    //
    // выходные данные:
    //      тот же объект, только с правильными айдишниками
    // Разработал: Якимов М.Н.
    // дата разработки: 07.03.2020
    // прмер использования:
    // http://127.0.0.1/read-manager-amicum?controller=ordersystem\WorkSchedule&method=SaveRole&subscribe=&data={%22role%22:1}
    public static function SaveRole($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'SaveRole';                                                                             // название логируемого метода
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
            $description = 'Начало выполнение метода';                                                                      // описание текущей отладочной точки
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
//                throw new \Exception($method_name . '. Не смог получить ключ лога для записи в БД');
//            }

            /** Метод начало */
            $warnings[] = 'SaveRole. Начало метода';
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('SaveRole. Не переданы входные параметры');
            }
            $warnings[] = 'SaveRole. Данные успешно переданы';
            $warnings[] = 'SaveRole. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'SaveRole. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'role')
            ) {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = 'SaveRole. Данные с фронта получены';
            $role = $post_dec->role;                                                          // ключ заключения медицинского осмотра

            // ищем медицинское заключение
            $save_role = Role::findOne(['id' => $role->id]);

            // если его нет, то создаем (Хотел сделать поиск на уже существующую проверку МО, что бы в нее дописывать)
            if (!$save_role) {
                $save_role = new Role();

            }

            $save_role->title = $role->title;
            $save_role->weight = $role->weight;
            $save_role->surface_underground = $role->surface_underground;
            $save_role->type = $role->type;
            if ($save_role->save()) {
                $save_role->refresh();
                $role->id = $save_role->id;

                $warnings[] = 'SaveRole. Данные успешно сохранены в модель Role';
            } else {
                $errors[] = $save_role->errors;
                throw new Exception('SaveRole. Ошибка сохранения модели Role');
            }
            $result = $role;
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

        $result_main = array(
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
        return $result_main;
    }

    /**
     * Назначение: Получение информации о работнике по идентификатору типизированного рабтника
     * Название метода: GetInfoAboutWorkerForTimeTable()
     * @param null $data_post
     * @return array
     *
     * Входные необязательные параметры
     *
     * @package frontend\controllers\ordersystem
     *
     * Входные обязательные параметры:
     * @see
     * @example http://amicum/read-manager-amicum?controller=ordersystem\WorkSchedule&method=GetInfoAboutWorkerForTimeTable&subscribe=&data={%22worker_object_id%22:%2213979%22}
     *
     * Документация на портале:
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 03.06.2019 12:06
     * @since ver
     */
    public static function GetInfoAboutWorkerForTimeTable($data_post = NULL)
    {
        $status = 1;                                                                                                 // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();
        if ($data_post !== NULL && $data_post !== '') {
            try {
                $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
                if (property_exists($post_dec, 'worker_object_id'))                                            //проверяем есть ли в полученных данных ид типизированного работника
                {
                    $warnings[] = 'GetInfoAboutWorker. Данные успешно получены.';
                    $worker_object_id = $post_dec->worker_object_id;
                    $filter = array('worker_object.id' => $worker_object_id);
                    $warnings[] = 'GetInfoAboutWorker. Поиск работника по идентификатору типизированного работника';
                    $worker = Worker::find()
                        ->joinWith('position')
                        ->joinWith('brigadeWorkers')
                        ->joinWith('brigade')
                        ->joinWith('chaneWorkers')
                        ->joinWith('workerObjects')
                        ->joinWith('workerObjectsRole')
                        ->joinWith('employee')
                        ->joinWith('role')
                        ->where($filter)
                        ->all();                                                                                        //поиск работника по фильтру
                    if ($worker)                                                                                        //если работник найден формируем результирующий массив
                    {
                        $warnings[] = 'GetInfoAboutWorker. Работник успешно найден.';
                        $warnings[] = 'GetInfoAboutWorker. Формирование данных о работнике.';
                        $result[$worker_object_id]['tabel_number'] = $worker[0]['tabel_number'];
                        $result[$worker_object_id]['FIO'] = "{$worker[0]['employee']['last_name']} {$worker[0]['employee']['first_name']} {$worker[0]['employee']['patronymic']}";
                        $result[$worker_object_id]['worker_id'] = $worker[0]['id'];
                        $result[$worker_object_id]['worker_object_id'] = $worker[0]['workerObjects'][0]['id'];
                        if (!empty($worker[0]['workerObjectsRole'])) {
                            foreach ($worker[0]['workerObjectsRole'] as $worker_role) {
                                $role = Role::find()
                                    ->where(['id' => $worker_role['role_id']])
                                    ->limit(1)
                                    ->one();
                                $result[$worker_object_id]['role'][] = $role['title'];
                            }
                        } else {
                            $found_worker_obj = WorkerObject::find()
                                ->where(['id' => $worker_object_id])
                                ->limit(1)
                                ->one();
                            $result[$worker_object_id]['role'][] = $found_worker_obj['role']['title'];
                        }
                        if (!empty($worker[0]['brigadeWorkers']) || !empty($worker[0]['chaneWorkers'])) {
                            $result[$worker_object_id]['brigade_id'] = $worker[0]['brigadeWorkers'][0]['brigade_id'];
                            $result[$worker_object_id]['chane_id'] = $worker[0]['chaneWorkers'][0]['chane_id'];
                        } else {
                            $result[$worker_object_id]['brigade_id'] = 'null';
                            $result[$worker_object_id]['chane_id'] = 'null';
                        }

                    } else                                                                                                //иначе предупреждение о том что работник не найден
                    {
                        $warnings[] = 'GetInfoAboutWorker.Работник не найден. Переданный идентификатор типизированного работника. ' . $worker_object_id;
                    }
                } else                                                                                                    //иначе записываем информацию о том что не передан входной массив данных либо он не верен
                {
                    $warnings[] = 'GetInfoAboutWorker. Входной массив данных пуст либо содержит ошибки';
                }
            } catch (Throwable $exception)                                                                               //ловим все ошибки и исключения
            {
                $errors[] = $exception->getMessage();
                $errors[] = $exception->getLine();
                $status = 0;
            }
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Метод ContinueGraphicToNextMonth() - Продолжение графика выходов на следующий месяц на основе последних 4 дней
     * @param null $data_post
     * @return array
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     *  {
     *      "year": 2020,                                                                                               - год
     *      "month": 4,                                                                                                 - месяц
     *      "company_department_id": 20028748,                                                                          - идентификатор участка
     *      "workers": {                                                                                                - массив работников по которым нужно простроить график
     *          "2911251": {                                                                                            - идентификатор работника
     *              "worker_id": 2911251,                                                                               - идентификатор работника
     *              "role_id": 9,                                                                                       - идентификатор роли в которой будет сохранён работник
     *              "chane_id": 868704                                                                                  - идентификатор звена на котором будет закреплён работник
     *          }
     *      }
     *  }
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * (стандартный массив данных)
     *
     * АЛГОРИТМ:
     * СЧЁТЧИК В ДАННОМ КОНТЕКСТЕ НЕОБХОДИМ ДЛЯ ОТЧЁТА КОЛИЧЕСТВА СМЕН
     * 1. Формируем массив людей из объекта
     * 2. Заполняем массив роли и звена по работнику
     * 3. Пришедший месяц равен 12
     *      да?     установить следующий месяц = 01
     *              увеличить год на 1
     *      нет?    Увеличить значение месяца которое пришло на 1
     *              Оставить не изменным год
     * 4. Полчить список всех ролей индексированный по идентификатору
     * 5. Считаем количество дней в месяце
     * 6. Считаем количество дней в следующем месяце
     * 7. Массивом получаем последние 4 дня переданного месяца
     * 8. Выбираем данные по плановому графику
     *      по переданным людям
     *      за последние 4 дня
     * 9. Формируем массив вида:
     *      [worker_id]
     *         flag:                    // true = значит больничный или отпуск
     *         working_time_id:        // проверятся только если флаг = true, ставиться вид рабочего времени для графика
     *         worker_id:                // идентификатор работника
     *         [shifts]                // список смен
     *             {day}: {shift_id}   // день: идентификатор смены, напирмер 30: 4 = 30 число 4 смена
     * 10. Перебор сформированного массива
     *    10.1    Получаем роль, если нет роли то ставиться роль по умаолчанию (role_id = 9 (Прочее))
     *    10.2    Получаем тип роли
     *    10.3    Звено пустое или не передано
     *                Да?        Запрос на получение первого звена работника
     *                            Найдено?        Взять идентификатор звена
     *                            Не найдено?        Идентификатор звена по умолчани.
     *                Нет?    Взять идентификатор звена
     *    10.4    Получаем флаг
     *                false?    идентификатор рабочего времени = 1
     *                        Получаем последнюю смену в месяце
     *                        Последняя смена в месяце не равна 5? (Последняя смена в месяце не выходной?)
     *                            Да?        Посчитать количество таких смен (далее: count_shift)
     *                                    Тип роли
     *                                            1 или 2?    Устанавливаем флаг статичной смены = true
     *                                                        Предпоследняя смена в прошедшем месяце равна последней смене в месяце?
     *                                                            Да?        Значение счётчика = 3 - количест смен (count_shift) (У ИТР график 5/2, так как шло 2 смены необходимо чтобы счётчик повторился минимум 2 раза)
     *                                                            Нет?    Значение счётчика = 2 (Если смены у последнего и предпоследнего дня различны, значит на предидущей 5 смена (Выходной), значит строим до пяти смен)
     *                                                        Установить значение смены = 1
     *                                                        Получить значение предпоследней смены
     *                                            иначе?        Количество смен(count_shift) равно 1?    Значение счётчика = 0 (смену нужно повторить 2 раза)
     *                                                        Количество смен(count_shift) равно 2?    Значение счётчика = -1 (смену нужно повторить 1 раз)
     *                                                        Количество смен(count_shift) равно 3?    Значение счётчика = -1 (поставить 1 выходной и после 3/1)
     *                                                                                                Значение смены = 5
     *                            Нет?    Тип роли
     *                                            1 или 2?    Устанавливаем флаг статичной смены = true
     *                                                        Предпоследняя смена в прошедшем месяце равна последней смене в месяце?
     *                                                            Да?        Значение счётчика = 3 (Так как последний день совпадает с предидущим, а последний день = выходной, знчит предидущий тоже выходной, следуя логике 5/2, нужно сделать пять смен)
     *                                                            Нет?    Значение счётчика = 0 (Так как последний день не совпадает с предидущим, а последний день = выходной, значит нужно проставить ещё 1 выходной)
     *                                                                    Значение смены = 5
     *                                                        Получаем значене смены
     *                                                        Получить значение предпоследней смены
     *                                            иначе?        Значение счётчика = 2 (так как был выходной, а схема работы не ИТР 3/1, значит сейчас 3 рабочие смены)
     *                                                        Получить значение смены
     *                                                        Получить значение предпоследней смены
     *                true?    Значение рабочей смены  = тому что стояло у человека (Отпуск, больничный и т.д.)
     * 11. Конец перебора данных работников
     * 12. Запрос на получение графика выходов
     * 13. Если такого графика нет, создать его
     * 14. Есть флаг и он не пуст (false = пустота, true = не пусто)
     *      да?        Заполнить массив по конкретному человеку на весь месяц тем видом рабочего времени который получили в working_time
     *      нет?        Перебор дней
     *                Формирование даты
     *                Статична ли смена?
     *                    да?     Пропустить
     *                    нет?    Проверка является ли смена меньше либо равна 1
     *                                да?     Установить значение смены = 4
     *                                нет?    Пропустить
     *                Смена равна 5
     *                    да?     часы работы = 0
     *                    нет?    часы работы = 6
     *                Добавляем в массив по человеку и дню данные
     *                Статична ли смена?
     *                    нет?    Счётчик меньше 0 и смена = 5
     *                                да?                                             Получаем предидущую смену и уменьшаем её на 1
     *                                                                                Счётчик = 2 (нужно устанновить 3 рабочих дня)
     *                                иначе если Счётчик меньше 0 и смена не = 5?     Устанавливаем значение смены = 5
     *                                                                                Счётчик = 0   (нужно установить 1 выходной)
     *                    да?     Счётчик меньше 0 и смена = 5
     *                                да?                                             Проверяем тип роли
     *                                                                                        1 или 2?    Счётчик = 4 (ИТР график 5/2, 5 рабочих дней)
     *                                                                                        инчае?      Счётчик = 1 (не ИТР график 3/1, 3 рабочих дня)
     *
     * ПРЕДУПРЕЖДЕНИЯ:
     * - Необходимо создать звено с идентификатором 1, как звено по умолчанию (Будет задано если небыло передано звено и человек не закреплён ни за 1 звеном)
     *
     * @package frontend\controllers\ordersystem
     *
     * @example http://amicum/read-manager-amicum?controller=ordersystem\WorkSchedule&method=ContinueGraphicToNextMonth&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 08.04.2020 21:18
     */
    public static function ContinueGraphicToNextMonth($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'ContinueGraphicToNextMonth';
        $cont_grafic = array();                                                                                            // Промежуточный результирующий массив
        $workers_grafic = array();
        $warnings[] = $method_name . '. Начало метода';
        $workers_to_select = array();
        $grafic_to_insert = array();
        $role_id = 9;
        $chane_id = 1;
        $role_type = 3;
        $last_date_in_next_month = null;
//        $data_post = '{"year":2020,"month":4,"company_department_id":4029938,"workers":{"1093815":{"worker_id":1093815,"role_id":202,"chane_id":774},"2020454":{"worker_id":2020454,"role_id":175,"chane_id":734},"2030581":{"worker_id":2030581,"role_id":178,"chane_id":734},"2036419":{"worker_id":2036419,"role_id":185,"chane_id":706},"2050178":{"worker_id":2050178,"role_id":185,"chane_id":706},"2050328":{"worker_id":2050328,"role_id":186,"chane_id":834},"2050363":{"worker_id":2050363,"role_id":186,"chane_id":706},"2050398":{"worker_id":2050398,"role_id":187,"chane_id":706},"2050468":{"worker_id":2050468,"role_id":187,"chane_id":834},"2050724":{"worker_id":2050724,"role_id":185,"chane_id":774},"2050755":{"worker_id":2050755,"role_id":185,"chane_id":706},"2050799":{"worker_id":2050799,"role_id":187,"chane_id":774},"2050985":{"worker_id":2050985,"role_id":185,"chane_id":774},"2051021":{"worker_id":2051021,"role_id":188,"chane_id":774},"2051056":{"worker_id":2051056,"role_id":187,"chane_id":774},"2051188":{"worker_id":2051188,"role_id":185,"chane_id":834},"2051543":{"worker_id":2051543,"role_id":185,"chane_id":834},"2051626":{"worker_id":2051626,"role_id":185,"chane_id":774},"2051772":{"worker_id":2051772,"role_id":185,"chane_id":706},"2051825":{"worker_id":2051825,"role_id":185,"chane_id":774},"2051877":{"worker_id":2051877,"role_id":181,"chane_id":766},"2051899":{"worker_id":2051899,"role_id":179,"chane_id":734},"2052043":{"worker_id":2052043,"role_id":185,"chane_id":706},"2052355":{"worker_id":2052355,"role_id":185,"chane_id":706},"2052380":{"worker_id":2052380,"role_id":185,"chane_id":706},"2052560":{"worker_id":2052560,"role_id":187,"chane_id":834},"2052614":{"worker_id":2052614,"role_id":185,"chane_id":706},"2052631":{"worker_id":2052631,"role_id":187,"chane_id":706},"2052762":{"worker_id":2052762,"role_id":237,"chane_id":766},"2053017":{"worker_id":2053017,"role_id":185,"chane_id":706},"2082540":{"worker_id":2082540,"role_id":187,"chane_id":834},"2084231":{"worker_id":2084231,"role_id":187,"chane_id":774},"2191057":{"worker_id":2191057,"role_id":177,"chane_id":734},"2904619":{"worker_id":2904619,"role_id":187,"chane_id":706},"2904945":{"worker_id":2904945,"role_id":185,"chane_id":834},"2909025":{"worker_id":2909025,"role_id":185,"chane_id":706},"2910303":{"worker_id":2910303,"role_id":187,"chane_id":706},"2910672":{"worker_id":2910672,"role_id":188,"chane_id":706},"2910682":{"worker_id":2910682,"role_id":185,"chane_id":834},"2911249":{"worker_id":2911249,"role_id":187,"chane_id":834},"2911917":{"worker_id":2911917,"role_id":185,"chane_id":834},"2911987":{"worker_id":2911987,"role_id":186,"chane_id":834},"2912216":{"worker_id":2912216,"role_id":187,"chane_id":834},"2912375":{"worker_id":2912375,"role_id":187,"chane_id":834},"2912789":{"worker_id":2912789,"role_id":188,"chane_id":834},"2912795":{"worker_id":2912795,"role_id":188,"chane_id":834},"2913085":{"worker_id":2913085,"role_id":186,"chane_id":834},"2913274":{"worker_id":2913274,"role_id":188,"chane_id":834},"2913576":{"worker_id":2913576,"role_id":176,"chane_id":734},"2913879":{"worker_id":2913879,"role_id":186,"chane_id":834},"2914051":{"worker_id":2914051,"role_id":186,"chane_id":706},"2914140":{"worker_id":2914140,"role_id":186,"chane_id":706},"2914178":{"worker_id":2914178,"role_id":185,"chane_id":774},"2916480":{"worker_id":2916480,"role_id":185,"chane_id":834},"2916639":{"worker_id":2916639,"role_id":185,"chane_id":706},"7":{"worker_id":7,"role_id":237,"chane_id":844},"2020460":{"worker_id":2020460,"role_id":185,"chane_id":842},"2020512":{"worker_id":2020512,"role_id":185,"chane_id":706},"2030210":{"worker_id":2030210,"role_id":186,"chane_id":708},"2043506":{"worker_id":2043506,"role_id":185,"chane_id":842},"2050195":{"worker_id":2050195,"role_id":181,"chane_id":766},"2050974":{"worker_id":2050974,"role_id":185,"chane_id":712},"2050996":{"worker_id":2050996,"role_id":185,"chane_id":836},"2051043":{"worker_id":2051043,"role_id":185,"chane_id":708},"2051143":{"worker_id":2051143,"role_id":185,"chane_id":844},"2051352":{"worker_id":2051352,"role_id":185,"chane_id":840},"2051734":{"worker_id":2051734,"role_id":185,"chane_id":836},"2052168":{"worker_id":2052168,"role_id":185,"chane_id":712},"2053413":{"worker_id":2053413,"role_id":181,"chane_id":766},"2057196":{"worker_id":2057196,"role_id":181,"chane_id":766},"2190909":{"worker_id":2190909,"role_id":186,"chane_id":708},"2191085":{"worker_id":2191085,"role_id":185,"chane_id":714},"2191105":{"worker_id":2191105,"role_id":9,"chane_id":844},"2223125":{"worker_id":2223125,"role_id":187,"chane_id":774},"2906021":{"worker_id":2906021,"role_id":187,"chane_id":774},"2908590":{"worker_id":2908590,"role_id":185,"chane_id":710},"2908730":{"worker_id":2908730,"role_id":185,"chane_id":842},"2909005":{"worker_id":2909005,"role_id":186,"chane_id":844},"2909019":{"worker_id":2909019,"role_id":185,"chane_id":712},"2909304":{"worker_id":2909304,"role_id":185,"chane_id":844},"2910498":{"worker_id":2910498,"role_id":186,"chane_id":840},"2910862":{"worker_id":2910862,"role_id":185,"chane_id":844},"2910876":{"worker_id":2910876,"role_id":185,"chane_id":836},"2912547":{"worker_id":2912547,"role_id":188,"chane_id":766},"2912963":{"worker_id":2912963,"role_id":187,"chane_id":834},"2913086":{"worker_id":2913086,"role_id":185,"chane_id":710},"2913174":{"worker_id":2913174,"role_id":185,"chane_id":714},"2913326":{"worker_id":2913326,"role_id":185,"chane_id":840},"2913496":{"worker_id":2913496,"role_id":186,"chane_id":710},"2913860":{"worker_id":2913860,"role_id":9,"chane_id":708},"2913981":{"worker_id":2913981,"role_id":185,"chane_id":714},"2914028":{"worker_id":2914028,"role_id":185,"chane_id":710},"2914097":{"worker_id":2914097,"role_id":186,"chane_id":840},"2914159":{"worker_id":2914159,"role_id":185,"chane_id":842},"2914194":{"worker_id":2914194,"role_id":181,"chane_id":766},"2914215":{"worker_id":2914215,"role_id":185,"chane_id":714},"2916481":{"worker_id":2916481,"role_id":186,"chane_id":836},"2050565":{"worker_id":2050565,"role_id":181,"chane_id":766},"2050967":{"worker_id":2050967,"role_id":187,"chane_id":774},"2051064":{"worker_id":2051064,"role_id":185,"chane_id":774},"2051089":{"worker_id":2051089,"role_id":185,"chane_id":846},"2051300":{"worker_id":2051300,"role_id":185,"chane_id":774},"2051871":{"worker_id":2051871,"role_id":185,"chane_id":706},"2084235":{"worker_id":2084235,"role_id":187,"chane_id":774},"2905691":{"worker_id":2905691,"role_id":187,"chane_id":834},"2912185":{"worker_id":2912185,"role_id":188,"chane_id":768},"2913595":{"worker_id":2913595,"role_id":186,"chane_id":846}}}';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'year') ||
                !property_exists($post_dec, 'month') ||
                !property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'workers'))                                                         // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $year = (int)$post_dec->year;
            $month = (int)$post_dec->month;
            $company_department_id = $post_dec->company_department_id;
            $workers = $post_dec->workers;
            /**
             * Формируем массив людей из объекта
             */
            if (!empty($workers)) {
                foreach ($workers as $worker) {
                    if (!in_array($worker->worker_id, $workers_to_select)) {
                        $workers_to_select[] = $worker->worker_id;
                        $workers_role[$worker->worker_id]['role_id'] = $worker->role_id;
                        $workers_role[$worker->worker_id]['chane_id'] = $worker->chane_id;
                    }
                }
            }
            unset($workers);
            /**
             * Пришедший месяц равен 12
             *      да?     установить следующий месяц = 01
             *              увеличить год на 1
             *      нет?    Увеличить значение месяца которое пришло на 1
             *              Оставить не изменным год
             */
            if ($month == 12) {
                $next_month = 01;
                $next_year = (int)$post_dec->year + 1;
            } else {
                $next_month = (int)$post_dec->month + 1;
                $next_year = (int)$post_dec->year;
            }
            /**
             * Полчить список всех ролей индексированный по идентификатору
             */
            $roles = Role::find()
                ->indexBy('id')
                ->asArray()
                ->all();

            $count_days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);                             // считаем количество дней в месяце
            $count_days_in_next_month = cal_days_in_month(CAL_GREGORIAN, $next_month, $next_year);              // считаем количество дней в следующем месяце
            $last_date_in_next_month = $count_days_in_month;
            $last_four_days = array();                                                                                  // массив дат последних 4 дней
            /**
             * Массивом получаем последние 4 дня переданного месяца
             */
            for ($i = 0; $i < 4; $i++) {
                $last_four_days[] = "{$year}-{$month}-{$count_days_in_month}";
                $count_days_in_month--;
            }
            /**
             * Выбираем данные по плановому графику
             *      по переданным людям
             *      за последние 4 дня
             */
            $grafic_data = GraficTabelDatePlan::find()
                ->where(['in', 'grafic_tabel_date_plan.worker_id', $workers_to_select])
                ->andWhere(['in', 'grafic_tabel_date_plan.date_time', $last_four_days])
                ->asArray()
                ->all();
            unset($workers_to_select);
            unset($last_four_days);
            if (!empty($grafic_data)) {
                $workers_data = array();
                /**
                 * Формируем массив вида:
                 * [worker_id]
                 *      flag:                    // true = значит больничный или отпуск
                 *      working_time_id:        // проверятся только если флаг = true, ставиться вид рабочего времени для графика
                 *      worker_id:                // идентификатор работника
                 *      [shifts]                // список смен
                 *          {day}: {shift_id}   // день: идентификатор смены, напирмер 30: 4 = 30 число 4 смена
                 */
                foreach ($grafic_data as $grafic_item) {
                    $day = date('d', strtotime($grafic_item['date_time']));
                    $workers_data[$grafic_item['worker_id']]['worker_id'] = $grafic_item['worker_id'];
                    if ($grafic_item['working_time_id'] == 1 || $grafic_item['working_time_id'] == 23) {
                        $workers_data[$grafic_item['worker_id']]['shifts'][$day] = $grafic_item['shift_id'];
                    } else {
                        if ($grafic_item['working_time_id'] == 6 || $grafic_item['working_time_id'] == 24) {
                            $workers_data[$grafic_item['worker_id']]['working_time_id'] = $grafic_item['working_time_id'];
                            $workers_data[$grafic_item['worker_id']]['flag'] = true;
                        } else {
                            $workers_data[$grafic_item['worker_id']]['flag'] = false;
                        }
                    }
                }
            }
            unset($grafic_data);
            unset($grafic_item);
            if (isset($workers_data) && !empty($workers_data)) {
                $worker_new_grafic = array();
                foreach ($workers_data as $wo_item) {
                    $static_shift_id = false;
                    $worker_id = $wo_item['worker_id'];
                    $worker_new_grafic[$worker_id]['worker_id'] = $worker_id;
                    /**
                     * Получить значение роли
                     * Если роль не задана, по умолчанию роль (role_id = 9 (Прочее))
                     */
                    if (isset($workers_role[$worker_id]['role_id']) && !empty($workers_role[$worker_id]['role_id'])) {
                        $role_id = $workers_role[$worker_id]['role_id'];
                    } else {
                        $role_id = 9;
                    }
                    /**
                     * Получить значение звена
                     * Звено передано
                     *      да?      Взять идентификатор звена
                     *      нет?     Найти первое звено работника
                     *               Звено найдено?
                     *                  да?     Получить идентификатор звена
                     *                  нет?    Звено по умолчанию
                     */
                    if (isset($workers_role[$worker_id]['chane_id']) && !empty($workers_role[$worker_id]['chane_id'])) {
                        $chane_id = $workers_role[$worker_id]['chane_id'];
                    } else {
                        $chane = ChaneWorker::findOne(['worker_id' => $wo_item['worker_id']]);
                        if (!empty($chane)) {
                            $chane_id = $chane->id;
                        } else {
                            throw new Exception($method_name . '. Работника нет ни в 1 звене');
                        }
                        unset($chane);
                    }
                    $role_type = $roles[$role_id]['type'];
                    $worker_new_grafic[$worker_id]['role_id'] = $role_id;
                    $worker_new_grafic[$worker_id]['shift_id'] = null;
                    $worker_new_grafic[$worker_id]['counter'] = null;
                    $worker_new_grafic[$worker_id]['role_type'] = $role_type;
                    $worker_new_grafic[$worker_id]['chane_id'] = $chane_id;
                    $worker_new_grafic[$worker_id]['flag'] = isset($wo_item['flag']) ? $wo_item['flag'] : false;
                    if (!isset($wo_item['flag']) || empty($wo_item['flag'])) {
                        $worker_new_grafic[$worker_id]['working_time_id'] = 1;
                        $shift_at_last_day = $wo_item['shifts'][$last_date_in_next_month];
                        /**
                         * Есть ли первая смена в массиве смен по дням
                         */
                        $search_last_shift = array_search(1, $wo_item['shifts']);
                        /**
                         * Последняя смена в месяце выходной
                         *      да?     Смотрим был ли выходной
                         *              Получаем количество смен по последнему дню
                         *                  тип роли 1 или 2?   Флаг ститичной смены = true
                         *                                      Последняя смена в месяце не равна предпоследней смене в месяце
                         *                                              Да?     Счётчик = 3 - количество смен по последнему дню (ИТР 5/2)
                         *                                              Нет?    Счётчик = 2
                         *                  тип роли 3?         Есть ли первая смена среди последних 4 дней?
                         *                                              Да?     Флаг статичной смены  = true
                         *                                              Нет?    Флаг статичной смены  = false
                         *                                      Количество смен по последней смене в месяце?
                         *                                              1?      Счётчик = 0 (простроить 2 дня с той же сменой)
                         *                                              2?      Счётчик = -1 (простроить 1 день с той же сменой)
                         *                                              3?      Есть первая смена?
                         *                                                        Да?        Если предпоследняя смена в месяце был выходной
                         *                                                                    Счётчик = 0 (простроть ещё 2 дня, график 3/1)
                         *                                                                Иначе: пред предпоследняя смена в месяце был выходным
                         *                                                                    Счётчик -1
                         *                                                                    Установить смену = 1
                         *                                                          Нет?    Счётчик -1
                         *                                                                  Установить смену = 5
                         *
                         *                                              Иначе?  Есть ли выходной среди полученных смен по дням
                         *                                                          Нет?    Счётчик -1  (простроить один выходной)
                         *                                                                  Установить смену = 5
                         *                                                          Да?     Счётчик - 0 ( простроить ещё 2 дня )
                         *
                         *      нет?    Тип роли?
                         *                1 или 2?    Флаг статичной смены = true
                         *                            Предпоследняя смена в месяце == последней смене в месце
                         *                                Да?        Счётчик = 3 (если сюда зашли значит было 2 выходных, значить простроить 5/2)
                         *                                Нет?    Счётчик = 0 (простроить 2 дня выходных)
                         *                3?            Счётчик = 2
                         *                            Есть первая смена?
                         *                                Да?        Взять значение смены из локальной переменной смены
                         *                                        Флаг статичной смены = true
                         *                                        Счётчик = 1
                         *                                Нет?    Пропустить
                         */
                        if ($shift_at_last_day != 5) {
                            /**
                             * Есть ли выходной среди массива смен по дням (ситуация: 1 1 1 1, в типе роли 3 - не ИТР 3/1)
                             */
                            $search_free_time = array_search(5, $wo_item['shifts']);
                            /**
                             * Получаем количество смен по последнему дню
                             */
                            $count_shifts = array_count_values($wo_item['shifts'])[$wo_item['shifts'][$last_date_in_next_month]];
                            if ($role_type == 1 || $role_type == 2) {
                                $static_shift_id = true;
                                if ($wo_item['shifts'][$last_date_in_next_month - 1] == $shift_at_last_day) {
                                    $counter = 3 - $count_shifts;
                                } else {
                                    $counter = 2;
                                }
                                $worker_new_grafic[$worker_id]['counter'] = $counter;
                                $worker_new_grafic[$worker_id]['shift_id'] = 1; // первая смена статично задана так как это ИТР с графиком 5/2
                                $worker_new_grafic[$worker_id]['last_shift_id'] = $wo_item['shifts'][$last_date_in_next_month - 1];
                            } else {
                                if (!empty($search_last_shift)) {
                                    $static_shift_id = true;
                                }

                                if ($count_shifts == 1) {
                                    $counter = 0;
                                } elseif ($count_shifts == 2) {
                                    $counter = -1;
                                    if ($static_shift_id) {
                                        if ($wo_item['shifts'][$last_date_in_next_month - 1] == 5) {
                                            $counter = 0;
                                        }
                                    }
                                } elseif ($count_shifts == 3) {
                                    if (!empty($search_last_shift)) {
                                        if ($wo_item['shifts'][$last_date_in_next_month - 1] == 5) {
                                            $counter = 0;
                                        } elseif ($wo_item['shifts'][$last_date_in_next_month - 2] == 5) {
                                            $counter = -1;
                                            $shift_at_last_day = 1;
                                        } else {
                                            $counter = -1;
                                            $shift_at_last_day = 5;
                                        }
                                    } else {
                                        $counter = -1;
                                        $shift_at_last_day = 5;
                                    }
                                } else {
                                    if (empty($search_free_time)) {
                                        $counter = -1;
                                        $shift_at_last_day = 5;
                                    } else {
                                        $counter = 0;
                                    }

                                }
                                $worker_new_grafic[$worker_id]['counter'] = $counter;
                                $worker_new_grafic[$worker_id]['shift_id'] = $shift_at_last_day;
                                $worker_new_grafic[$worker_id]['last_shift_id'] = $wo_item['shifts'][$last_date_in_next_month - 1];

                            }
//                            if ($worker_id == 2911987){
//                                Assistant::PrintR($counter);
//                                Assistant::PrintR($shift_at_last_day);
//                                Assistant::PrintR($wo_item['shifts'][$last_date_in_next_month - 1]);
//                                Assistant::PrintR($wo_item);
//                                die;
//                            }
                        } else {
                            $local_shift_id = 1;
                            if ($role_type == 1 || $role_type == 2) {
                                $static_shift_id = true;
                                if ($wo_item['shifts'][$last_date_in_next_month - 1] == $shift_at_last_day) {
                                    $counter = 3;
                                } else {
                                    $counter = 0;
                                    $local_shift_id = 5;
                                }
                                $worker_new_grafic[$worker_id]['shift_id'] = $local_shift_id;
                            } else {
                                $counter = 1;
                                if ($search_last_shift) {
                                    $shift_at_last_day = $local_shift_id;
                                    if (!empty($search_last_shift)) {
                                        $static_shift_id = true;
                                    }
                                } else {
                                    $shift_at_last_day = 4;
                                }
                                $worker_new_grafic[$worker_id]['shift_id'] = $shift_at_last_day;
                            }
                            $worker_new_grafic[$worker_id]['counter'] = $counter;
                            $worker_new_grafic[$worker_id]['last_shift_id'] = $wo_item['shifts'][$last_date_in_next_month - 1];
//                            if ($worker_id == 2914215) {
//                                Assistant::PrintR($wo_item['shifts'][$last_date_in_next_month - 1]);
//                                Assistant::PrintR('------------------------------------------');
//                                Assistant::PrintR($shift_at_last_day);
//                                Assistant::PrintR($static_shift_id);
//                                Assistant::PrintR($wo_item);
//                                die;
//                            }
                        }
                    } else {
                        $worker_new_grafic[$worker_id]['working_time_id'] = $wo_item['working_time_id'];
                    }
                    $worker_new_grafic[$worker_id]['static_shift_id'] = $static_shift_id;
                }
            }
//            Assistant::PrintR($worker_new_grafic);
//            die;
            unset($grafic_data);
            unset($grafic_item);

            /**
             * Запрос на получение графика выходов
             * Если такого графика нет, создать его
             */
            $grafic_tabel_main = GraficTabelMain::findOne(['year' => $next_year, 'month' => $next_month, 'company_department_id' => $company_department_id]);
            if (empty($grafic_tabel_main)) {
                $grafic_tabel_main = new GraficTabelMain();
                $grafic_tabel_main->year = $next_year;
                $grafic_tabel_main->month = $next_month;
                $grafic_tabel_main->company_department_id = $company_department_id;
                $grafic_tabel_main->title = 'Сгенерированный график';
                $grafic_tabel_main->date_time_create = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));
                $grafic_tabel_main->status_id = 1;
                if (!$grafic_tabel_main->save()) {
                    $errors[] = $grafic_tabel_main->errors;
                    throw new Exception($method_name . '. Ошибка при сохранении графика выходов');
                }
            }
            $grafic_tabel_main_id = $grafic_tabel_main->id;
            unset($grafic_tabel_main);
            if (!empty($worker_new_grafic)) {
                foreach ($worker_new_grafic as $worker_item) {
                    $static_shift_id = $worker_item['static_shift_id'];
                    $shift_id = $worker_item['shift_id'];
                    $counter = $worker_item['counter'];
                    $role_id = $worker_item['role_id'];
                    $chane_id = $worker_item['chane_id'];
                    $role_type = $worker_item['role_type'];
                    /**
                     * Есть флаг и он не пуст (false = пустота, true = не пусто)
                     *      да?     Заполнить массив по конкретному человеку на весь месяц тем видом рабочего времени который получили в working_time
                     *      нет?    Проверка есть ли массив смен
                     *              Получаем минимальный ключ смены
                     *              Минимальный ключ смены = 1
                     *                  да?     Устанавливаем флаг статичной смены = true
                     *                          Устанавливаем идентификатор смены = 1
                     *                          Получаем максимальное значение по сменам
                     *                          Проверяем тип роли (ИТР график 5/2, остальные 3/1)
                     *                              1 или 2?    Устанавливаем счётчик 4 - максимальное значение по смене (ИТР)
                     *                              иначе?      Устанавливаем счётчик 2 - максимальное значение по смене
                     *                                          Устанавливаем идентификатор смены = 5 (Выходной)
                     *                  нет?    Устанавливаем флаг статичной смены = false
                     *                          Получаем ключ смены в которой значение максимальное
                     *                          Получаем максимальное значение по смене
                     *                          Максимальное значение =?
                     *                              2?          Устанавливаем счётчик = -1
                     *                              3?          Декримент смены
                     *                                          Устанавливаем счётчик = 2
                     *                              иначе?      Устанавливаем смену = 4
                     *                                          Устанавливаем счётчик = 2
                     */
                    if (isset($worker_item['flag']) && !empty($worker_item['flag'])) {
                        for ($j = 1; $j < $count_days_in_next_month; $j++) {
                            $date = "{$year}-{$next_month}-{$j}";
                            $workers_grafic[$worker_item['worker_id']][$j] = [
                                'grafic_tabel_main_id' => $grafic_tabel_main_id,
                                'day' => $j,
                                'shift_id' => 5,
                                'worker_id' => $worker_item['worker_id'],
                                'hours_work' => 0,
                                'role_id' => $role_id,
                                'date_time' => $date,
                                'month' => $next_month,
                                'year' => $next_year,
                                'kind_working_time_id' => 1,
                                'working_time_id' => $worker_item['working_time_id'],
                                'description' => '',
                                'chane_id' => $chane_id
                            ];
                        }
                    } else {
                        /** СЧЁТЧИК В ДАННОМ КОНТЕКСТЕ ОЗНАЧЕТ КОЛЬКО РАЗ УСТАНОВИТЬ СМЕНУ
                         *
                         *  Перебор дней
                         *      Формирование даты
                         *      Статична ли смена?
                         *          да?     Пропустить
                         *          нет?    Проверка является ли смена меньше либо равна 1
                         *                      да?     Установить значение смены = 4
                         *                      нет?    Пропустить
                         *      Смена равна 5
                         *          да?     часы работы = 0
                         *          нет?    часы работы = 6
                         *      Добавляем в массив по человеку и дню данные
                         *      Статична ли смена?
                         *          нет?    Счётчик меньше 0 и смена = 5
                         *                      да?                                             Получаем предидущую смену и уменьшаем её на 1
                         *                                                                      Счётчик = 2 (нужно устанновить 3 рабочих дня)
                         *                      иначе если Счётчик меньше 0 и смена не = 5?     Устанавливаем значение смены = 5
                         *                                                                      Счётчик = 0   (нужно установить 1 выходной)
                         *          да?     Счётчик меньше 0 и смена = 5
                         *                      да?                                             Проверяем тип роли
                         *                                                                              1 или 2?    Счётчик = 4 (ИТР график 5/2, 5 рабочих дней)
                         *                                                                              инчае?      Счётчик = 2 (не ИТР график 3/1, 3 рабочих дня)
                         *                                                                      Устанавливаем значение смены = 1
                         *                      иначе если Счётчик меньше 0 и смена не = 5?     Проверяем тип роли
                         *                                                                              1 или 2?    Счётчик = 1 (ИТР, нужно поставить 2 выходных)
                         *                                                                              инчае?      Счётчик = 0 (не ИТР, значит 1 выходной)
                         *      Уменьшаем значение счётчика
                         */
                        for ($j = 1; $j <= $count_days_in_next_month; $j++) {
                            $date = "{$year}-{$next_month}-{$j}";
                            if (!$static_shift_id) {
                                if ($shift_id <= 1) {
                                    $shift_id = 4;
                                }
                            }
                            if ($shift_id == 5) {
                                $working_time = 23;
                                $hours_work = 0;
                            } else {
                                $working_time = 1;
                                $hours_work = 6;
                            }
                            $workers_grafic[$worker_item['worker_id']][$j] = [
                                'grafic_tabel_main_id' => $grafic_tabel_main_id,
                                'day' => $j,
                                'shift_id' => $shift_id,
                                'worker_id' => $worker_item['worker_id'],
                                'hours_work' => $hours_work,
                                'role_id' => $role_id,
                                'date_time' => $date,
                                'month' => $next_month,
                                'year' => $next_year,
                                'kind_working_time_id' => 1,
                                'working_time_id' => $working_time,
                                'description' => '',
                                'chane_id' => $chane_id
                            ];

                            if (!$static_shift_id) {
                                if ($counter < 0 && $shift_id == 5) {
                                    if (isset($workers_grafic[$worker_item['worker_id']][$j - 1]['shift_id'])) {
                                        $shift_id = $workers_grafic[$worker_item['worker_id']][$j - 1]['shift_id'] - 1;
                                    } else {
                                        $shift_id = $worker_item['last_shift_id'] - 1;
                                    }
                                    $counter = 2;
                                } elseif ($counter < 0 && $shift_id != 5) {
                                    $counter = 0;
                                    $shift_id = 5;
                                }
                            } else {
                                if ($counter < 0 && $shift_id == 5) {
                                    if ($role_type == 1 || $role_type == 2) {
                                        $counter = 4;
                                    } else {
                                        $counter = 2;
                                    }
                                    $shift_id = 1;
                                } elseif ($counter < 0 && $shift_id != 5) {
                                    if ($role_type == 1 || $role_type == 2) {
                                        $counter = 1;
                                    } else {
                                        $counter = 0;
                                    }
                                    $shift_id = 5;
                                }
                            }
                            $counter--;
                        }
                    }
                }
            }
//            Assistant::PrintR($workers_data);
            unset($counter);
            unset($workers_data);
            unset($worker_item);
//            Assistant::PrintR($workers_grafic);
//            die;
            if (isset($workers_grafic) && !empty($workers_grafic)) {
                foreach ($workers_grafic as $worker_grafic) {
                    foreach ($worker_grafic as $grafic_item) {
                        $grafic_to_insert[] = [
                            $grafic_item['grafic_tabel_main_id'],
                            $grafic_item['day'],
                            $grafic_item['shift_id'],
                            (int)$grafic_item['worker_id'],
                            $grafic_item['hours_work'],
                            $grafic_item['role_id'],
                            $grafic_item['date_time'],
                            $grafic_item['month'],
                            $grafic_item['year'],
                            (int)$grafic_item['kind_working_time_id'],
                            (int)$grafic_item['working_time_id'],
                            $grafic_item['description'],
                            $grafic_item['chane_id']
                        ];
                    }
                }
//                Assistant::PrintR();
//                die;
                $cont_grafic = $grafic_to_insert;
                unset($workers_grafic);
                unset($worker_grafic);
                unset($grafic_item);
                if (!empty($grafic_to_insert)) {
                    $inserted_data = Yii::$app->db->createCommand()->batchInsert('grafic_tabel_date_plan', [
                        'grafic_tabel_main_id',
                        'day',
                        'shift_id',
                        'worker_id',
                        'hours_value',
                        'role_id',
                        'date_time',
                        'month',
                        'year',
                        'kind_working_time_id',
                        'working_time_id',
                        'description',
                        'chane_id'
                    ], $grafic_to_insert)->execute();
                    if ($inserted_data == 0) {
                        throw new Exception($method_name . '. Ошибка при добавлении планового графика');
                    }
                }
                unset($grafic_to_insert);
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $cont_grafic;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Название метода: SaveGraphicv2 - Метод сохранения графика выходов
     * Входной объект:
     *      year                    - выбранный год в меню
     *      month                   - выбранный месяц в меню
     *      company_department      - выбранное подразделение в меню
     *          id                      - ключ подразделения
     *          title                   - название подразделения
     *      grafic_tabel_main_id    - ключ созраняемого графика выходов (если -1, то график новый)
     *      personal_tab            - список графиков выходов со вкладки персонал
     *          {worker_id}             - ключ работника
     *              worker_id               - ключ работника
     *              worker_full_name        - ФИО работника
     *              worker_tabel_number     - табельный номер работника
     *              worker_object_id        - ключ работника по конкретной профессии
     *              roles:                  - список ролей работника
     *                  {role_id}               - ключ роли
     *                      role_id                 - ключ роли
     *                      role_title              - название роли
     *                      plan_schedule           - плановый график
     *                          {day}                   -номер дня
     *                              day                     - номер дня
     *                              brigade_id              - ключ бригады
     *                              chane_id                - ключ звена
     *                              working_time:           - список рабочего времени
     *                      fact_schedule           - фактический график
     *                          {day}                   -номер дня
     *                              day                     - номер дня
     *                              brigade_id              - ключ бригады
     *                              chane_id                - ключ звена
     *                              working_time:           - список рабочего времени
     *      brigade_tab             - список графиков выходов по бригадам
     *          {brigade_id}            - ключ бригады
     *              brigade_id              - ключ бригады
     *              brigade_description     - название бригады
     *              brigader_id             - ключ бригадира
     *              chanes:                 - список звеньев
     *                  {chane_id}              - ключ звена
     *                      chane_id                - ключ звена
     *                      chane_title             - название звена
     *                      chane_type              - тип звена
     *                      chaner_id               - ключ звеньевого
     *                      workers:                - список работников в звене
     *                          {worker_id}             - ключ работника
     *                              worker_id               - ключ работника
     *                              worker_full_name        - ФИО работника
     *                              worker_tabel_number     - табельный номер работника
     *                              worker_object_id        - ключ работника по конкретной профессии
     *                              roles:                  - список ролей работника
     *                                  {role_id}               - ключ роли
     *                                      role_id                 - ключ роли
     *                                      role_title              - название роли
     *                                      plan_schedule           - плановый график
     *                                          {day}                   -номер дня
     *                                              day                     - номер дня
     *                                              brigade_id              - ключ бригады
     *                                              chane_id                - ключ звена
     *                                              working_time:           - список рабочего времени
     *                                      fact_schedule           - фактический график
     *                                          {day}                   -номер дня
     *                                              day                     - номер дня
     *                                              brigade_id              - ключ бригады
     *                                              chane_id                - ключ звена
     *                                              working_time:           - список рабочего времени
     *      department_list_vgk     - список работников ВГК
     *
     * @package frontend\controllers\ordersystem
     * @example http://amicum/read-manager-amicum?controller=ordersystem\WorkSchedule&method=SaveGraphicv2&subscribe=saveGraphic&data={}
     * Документация на портале:
     * @author Якимов М.Н.
     * Created date: on 31.05.2019 13:47
     * @since ver
     */
    public static function SaveGraphicv2($data_post = NULL)
    {
        $result = array();                                                                                                // Промежуточный результирующий массив
        $log = new LogAmicumFront("SaveGraphicv2");
        $grahic_array_plan = array();
        $grahic_array_fact = array();
        $id_new_chane_for_template = array();
        $new_grafic_tabel_main_id = NULL;
        try {
            // Проверяем есть ли данные графика в кеше
            if ($data_post == NULL or $data_post == "") {
                throw new Exception("Входной массив не передан");
            }
            $post = json_decode($data_post);                                                                // Декодируем входной массив данных
            if (
                property_exists($post, 'personal_tab') &&
                property_exists($post, 'year') &&
                property_exists($post, 'month') &&
                property_exists($post, 'grafic_tabel_main_id') &&
                property_exists($post, 'company_department') &&
                property_exists($post, 'list_brigade_chane') &&
                property_exists($post, 'brigade_tab') &&
                property_exists($post, 'department_list_vgk')
            ) {
                // Проверяем наличие нужных полей
                $year = $post->year;                                    // год за который сохраняется график
                $month = $post->month;                                  // месяц за который сохраняется график
                $company_department_id = $post->company_department->id; // конкретный департамент, в который пишем график
                $department_title = $post->company_department->title;   // конкретный департамент, в который пишем график
                $gtm_id = $post->grafic_tabel_main_id;                  // ключ графика выходов
                $list_brigade_chane = $post->list_brigade_chane;        // список работников по бригадам и звеньям
                $personal_tab = $post->personal_tab;                    // список графиков выходов
                $brigade_tab = $post->brigade_tab;                      // список бригад
                $department_list_vgk = $post->department_list_vgk;      // список работников ВГК подразделения
                /**
                 * Создание бригад и звеньев, и добавление в них работников
                 */
                $chane_worker_arr = array();                                                                            // Массив для добавления в таблицу chane_worker
                $brigade_worker_arr = array();                                                                          // Массив для добавления в таблицу brigade_worker
            } else {
                throw new Exception("Параметры входного массива не корректны");
            }

            foreach ($list_brigade_chane as $brigade_id => $brigade) {
                if ($brigade->brigade_id != 0) {
                    $response = BrigadeController::AddBrigade($brigade->brigade_id, $brigade->brigade_description, $brigade->brigader_id, $company_department_id);
                    $log->addLogAll($response);
                    if ($response['status'] == 0) {
                        throw new Exception("Ошибка сохранения бригады");
                    }

                    $new_brigade_id = $response['id'];
                    $post->brigade_tab->$brigade_id->brigade_id = $new_brigade_id;
                    BrigadeWorker::deleteAll(['brigade_id' => $new_brigade_id]);
                    // создаем справочник бригад - старая новая/обновленная бригада
                    // нужно при создании новой бригады на фронте, что записался сам график
                    $brigade_arr[$brigade_id] = $new_brigade_id;
                    foreach ($brigade->chanes as $chane_id => $chane)                                                   // Перебор звеньев
                    {
                        $response = BrigadeController::AddChane($new_brigade_id, $chane->chaner_id, $chane->chane_title, $chane->chane_type, $chane->chane_id);// Добавляем новое звено прикрепленное к созданной бригаде
                        $log->addLogAll($response);
                        if ($response['status'] == 0) {
                            throw new Exception("Ошибка сохранения звена");
                        }
                        $id_new_chane = $response['id'];
                        $post->brigade_tab->$brigade_id->chanes->$chane_id->chane_id = $id_new_chane;
                        ChaneWorker::deleteAll(['chane_id' => $id_new_chane]);
                        // создаем справочник звеньев - старое новое/обновленное звено
                        // нужно при создании нового звена на фронте, что записался сам график
                        $chane_arr[$chane_id] = array('chane_id' => $id_new_chane, 'brigade_id' => $new_brigade_id);
                        if (property_exists($chane, 'workers')) {
                            foreach ((array)$chane->workers as $worker)                                                 // Перебор третьего слоя группировки - работники
                            {
                                $chane_worker_arr[$id_new_chane . "-" . $worker->worker_id . "-" . $worker->mine_id] = [$id_new_chane, $worker->worker_id, $worker->mine_id];                              // Формируем массив который в последующем добавим при помощи batchInsert
                                $brigade_worker_arr[$new_brigade_id . "-" . $worker->worker_id . "-" . $worker->mine_id] = [$new_brigade_id, $worker->worker_id, $worker->mine_id];                          // Формируем массив который в последующем добавим при помощи batchInsert
                            }
                        }
                    }

//                    Yii::$app->db->createCommand()->batchInsert(ChaneWorker::tableName(), ['chane_id', 'worker_id', 'mine_id'], $chane_worker_arr)->execute();                                       // Закрепляем сотрудников за бригадами и звеньями
//                    Yii::$app->db->createCommand()->batchInsert(BrigadeWorker::tableName(), ['brigade_id', 'worker_id', 'mine_id'], $brigade_worker_arr)->execute();
                }
            }

            /**
             * Добавление графика выходов
             */
            if ($gtm_id == -1) {
                $response = self::AddGraficTabelMain($year, $month, $company_department_id, $department_title);
                $log->addLogAll($response);
                if ($response['status'] == 0) {
                    throw new Exception("Ошибка сохранения главного графика вызодов");
                }
                $new_grafic_tabel_main_id = $response['id'];
            } else {
                $new_grafic_tabel_main_id = $gtm_id;
            }
            //распарсивание результатов от фронта и подготовка их к массовой вставке
            $i = 0;
            $j = 0;

            foreach ($brigade_tab as $brigade_id => $brigade) {
                foreach ($brigade->chanes as $chane_id => $chane)                                                                // Перебор звеньев
                {
                    foreach ($chane->workers as $key_worker_id => $worker) {
                        foreach ($worker->roles as $key_role_id => $role) {
                            $workers_role[] = array('worker_id' => $worker->worker_id, 'role_id' => $role->role_id);
                            $worker_list_for_search[$worker->worker_id] = $worker->worker_id;
                            foreach ($role->plan_schedule as $key_day => $day) {
                                if ($day->chane_id and isset($chane_arr[$day->chane_id])) {
                                    $new_chane_id = $chane_arr[$day->chane_id]['chane_id'];
                                    $new_brigade_id = $chane_arr[$day->chane_id]['brigade_id'];
                                } else {
                                    $new_chane_id = null;
                                    $new_brigade_id = null;
                                }
//                                if ($day->brigade_id) {
//                                    $new_brigade_id = $brigade_arr[$day->brigade_id];
//                                } else {
//                                    $new_brigade_id = null;
//                                }
//                                $post->personal_tab->$key_worker_id->roles->$key_role_id->plan_schedule->$key_day->chane_id = $new_chane_id;
//                                $post->personal_tab->$key_worker_id->roles->$key_role_id->plan_schedule->$key_day->brigade_id = $new_brigade_id;
                                if ($day->working_time !== NULL) {
                                    foreach ($day->working_time as $working_time) {
                                        if (property_exists($working_time, 'index')) {
                                            if (property_exists($working_time->index, 'shift')) {
                                                foreach ($working_time->index->shift as $key_shift => $shift) {
                                                    if ($key_shift != 5 and $shift !== null and property_exists($shift, 'shift_id')) {
                                                        $grahic_array_plan[$i]['grafic_tabel_main_id'] = $new_grafic_tabel_main_id;
                                                        $grahic_array_plan[$i]['mine_id'] = $shift->mine_id ? $shift->mine_id : null;
                                                        $grahic_array_plan[$i]['day'] = $day->day;
                                                        $grahic_array_plan[$i]['chane_id'] = $new_chane_id;
                                                        $grahic_array_plan[$i]['shift_id'] = $shift->shift_id;
                                                        $grahic_array_plan[$i]['worker_id'] = $worker->worker_id;
                                                        $grahic_array_plan[$i]['hours_value'] = $shift->hours_value;
                                                        $grahic_array_plan[$i]['role_id'] = $role->role_id;
                                                        $grahic_array_plan[$i]['date_time'] = date("Y-m-d", strtotime($year . '-' . $month . '-' . $day->day));
                                                        $grahic_array_plan[$i]['month'] = $month;
                                                        $grahic_array_plan[$i]['year'] = $year;
                                                        $grahic_array_plan[$i]['kind_working_time_id'] = 1;                             //1 - значит рабочий день из справочника kind_working_time
                                                        $grahic_array_plan[$i]['working_time_id'] = $working_time->working_time_id;
                                                        $grahic_array_plan[$i]['description'] = "";
                                                        $i++;
                                                        if ($shift->mine_id and $shift->mine_id != -1 and $new_chane_id) {
                                                            $chane_worker_arr[$new_chane_id . "-" . $worker->worker_id . "-" . $shift->mine_id] = [$new_chane_id, $worker->worker_id, $shift->mine_id];                              // Формируем массив который в последующем добавим при помощи batchInsert
                                                        }
                                                    } else if ($new_chane_id and $key_shift == 5 and $shift !== null and property_exists($shift, 'shift_id') and $shift->mine_id and $shift->mine_id != -1 and isset($chane_arr[$new_chane_id])) {
                                                        $chane_worker_arr[$new_chane_id . "-" . $worker->worker_id . "-" . $shift->mine_id] = [$new_chane_id, $worker->worker_id, $shift->mine_id];                              // Формируем массив который в последующем добавим при помощи batchInsert
                                                        $brigade_worker_arr[$new_brigade_id . "-" . $worker->worker_id . "-" . $shift->mine_id] = [$new_brigade_id, $worker->worker_id, $shift->mine_id];                              // Формируем массив который в последующем добавим при помощи batchInsert
                                                    }
                                                }
                                            } elseif (property_exists($working_time->index, 'kind_working_time')) {
                                                foreach ($working_time->index->kind_working_time as $key_kind_working_time => $kind_working_time) {
                                                    $grahic_array_plan[$i]['grafic_tabel_main_id'] = $worker->grafic_main_id->id;
                                                    $grahic_array_plan[$i]['mine_id'] = $working_time->mine_id ? $working_time->mine_id : null;
                                                    $grahic_array_plan[$i]['day'] = $day->day;
                                                    $grahic_array_plan[$i]['chane_id'] = $new_chane_id;
                                                    $grahic_array_plan[$i]['shift_id'] = 5;                                         //5 значит без смены
                                                    $grahic_array_plan[$i]['worker_id'] = $worker->worker_id;
                                                    $grahic_array_plan[$i]['hours_value'] = NULL;
                                                    $grahic_array_plan[$i]['role_id'] = $role->role_id;
                                                    $grahic_array_plan[$i]['date_time'] = date("Y-m-d", strtotime($year . '-' . $month . '-' . $day->day));
                                                    $grahic_array_plan[$i]['month'] = $month;
                                                    $grahic_array_plan[$i]['year'] = $year;
                                                    $grahic_array_plan[$i]['kind_working_time_id'] = $kind_working_time->kind_working_time_id;
                                                    $grahic_array_plan[$i]['working_time_id'] = $working_time->working_time_id;
                                                    $grahic_array_plan[$i]['description'] = "";
                                                    $i++;
                                                }
                                            }
                                        } elseif (property_exists($working_time, 'kind_working_time')) {
                                            $grahic_array_plan[$i]['grafic_tabel_main_id'] = $new_grafic_tabel_main_id;
                                            $grahic_array_plan[$i]['mine_id'] = $working_time->mine_id ? $working_time->mine_id : null;
                                            $grahic_array_plan[$i]['day'] = $day->day;
                                            $grahic_array_plan[$i]['chane_id'] = $new_chane_id;
                                            $grahic_array_plan[$i]['shift_id'] = 5;
                                            $grahic_array_plan[$i]['worker_id'] = $worker->worker_id;
                                            $grahic_array_plan[$i]['hours_value'] = 0;
                                            $grahic_array_plan[$i]['role_id'] = $role->role_id;
                                            $grahic_array_plan[$i]['date_time'] = date("Y-m-d", strtotime($year . '-' . $month . '-' . $day->day));
                                            $grahic_array_plan[$i]['month'] = $month;
                                            $grahic_array_plan[$i]['year'] = $year;
                                            $grahic_array_plan[$i]['kind_working_time_id'] = $working_time->kind_working_time;
                                            $grahic_array_plan[$i]['working_time_id'] = $working_time->working_time_id;
                                            $grahic_array_plan[$i]['description'] = "";
                                            $i++;
                                        } else {
                                            $grahic_array_plan[$i]['grafic_tabel_main_id'] = $new_grafic_tabel_main_id;
                                            $grahic_array_plan[$i]['mine_id'] = $working_time->mine_id ? $working_time->mine_id : null;
                                            $grahic_array_plan[$i]['day'] = $day->day;
                                            $grahic_array_plan[$i]['chane_id'] = $new_chane_id;
                                            $grahic_array_plan[$i]['shift_id'] = 5;
                                            $grahic_array_plan[$i]['worker_id'] = $worker->worker_id;
                                            $grahic_array_plan[$i]['hours_value'] = 0;
                                            $grahic_array_plan[$i]['role_id'] = $role->role_id;
                                            $grahic_array_plan[$i]['date_time'] = date("Y-m-d", strtotime($year . '-' . $month . '-' . $day->day));
                                            $grahic_array_plan[$i]['month'] = $month;
                                            $grahic_array_plan[$i]['year'] = $year;
                                            $grahic_array_plan[$i]['kind_working_time_id'] = 1;                             //1 - значит рабочий день из справочника kind_working_time
                                            $grahic_array_plan[$i]['working_time_id'] = $working_time->working_time_id;
                                            $grahic_array_plan[$i]['description'] = "";
                                            $i++;
                                        }
                                    }
                                }
                            }
                            foreach ($role->fact_schedule as $key_day => $day) {
                                if ($day->chane_id and isset($chane_arr[$day->chane_id])) {
                                    $new_chane_id = $chane_arr[$day->chane_id]['chane_id'];
                                    $new_brigade_id = $chane_arr[$day->chane_id]['brigade_id'];
                                } else {
                                    $new_chane_id = null;
                                    $new_brigade_id = null;
                                }
//                                if ($day->brigade_id) {
//                                    $new_brigade_id = $brigade_arr[$day->brigade_id];
//                                } else {
//                                    $new_brigade_id = null;
//                                }
//                                $post->personal_tab->$key_worker_id->roles->$key_role_id->fact_schedule->$key_day->chane_id = $new_chane_id;
//                                $post->personal_tab->$key_worker_id->roles->$key_role_id->fact_schedule->$key_day->brigade_id = $new_brigade_id;
                                if (property_exists($day, "working_time") and $day->working_time !== NULL) {
                                    foreach ($day->working_time as $key_working_time => $working_time) {
                                        if (property_exists($working_time, 'index')) {
                                            if (property_exists($working_time->index, 'shift')) {
                                                foreach ($working_time->index->shift as $key_shift => $shift) {
                                                    if ($key_shift != 5 and $shift !== null and property_exists($shift, 'shift_id') and isset($chane_arr[$day->chane_id])) {
                                                        $grahic_array_fact[$j]['grafic_tabel_main_id'] = $new_grafic_tabel_main_id;
                                                        $grahic_array_fact[$j]['mine_id'] = $shift->mine_id ? $shift->mine_id : null;
                                                        $grahic_array_fact[$j]['day'] = $day->day;
                                                        $grahic_array_fact[$j]['chane_id'] = $new_chane_id;
                                                        $grahic_array_fact[$j]['shift_id'] = $shift->shift_id;
                                                        $grahic_array_fact[$j]['worker_id'] = $worker->worker_id;
                                                        $grahic_array_fact[$j]['hours_value'] = $shift->hours_value;
                                                        $grahic_array_fact[$j]['role_id'] = $role->role_id;
                                                        $grahic_array_fact[$j]['date_time'] = date("Y-m-d", strtotime($year . '-' . $month . '-' . $day->day));
                                                        $grahic_array_fact[$j]['month'] = $month;
                                                        $grahic_array_fact[$j]['year'] = $year;
                                                        $grahic_array_fact[$j]['kind_working_time_id'] = 1;                             //1 - значит рабочий день из справочника kind_working_time
                                                        $grahic_array_fact[$j]['working_time_id'] = $working_time->working_time_id;
                                                        $grahic_array_fact[$j]['description'] = "";
                                                        $j++;
                                                    }
                                                }
                                            } elseif (property_exists($working_time->index, 'kind_working_time')) {
                                                foreach ($working_time->index->kind_working_time as $key_kind_working_time => $kind_working_time) {
                                                    if (isset($chane_arr[$day->chane_id])) {
                                                        $grahic_array_fact[$j]['grafic_tabel_main_id'] = $worker->grafic_main_id->id;
                                                        $grahic_array_fact[$j]['mine_id'] = $working_time->mine_id ? $working_time->mine_id : null;
                                                        $grahic_array_fact[$j]['day'] = $day->day;
                                                        $grahic_array_fact[$j]['chane_id'] = $new_chane_id;
                                                        $grahic_array_fact[$j]['shift_id'] = 5;                                         //5 значит без смены
                                                        $grahic_array_fact[$j]['worker_id'] = $worker->worker_id;
                                                        $grahic_array_fact[$j]['hours_value'] = NULL;
                                                        $grahic_array_fact[$j]['role_id'] = $role->role_id;
                                                        $grahic_array_fact[$j]['date_time'] = date("Y-m-d", strtotime($year . '-' . $month . '-' . $day->day));
                                                        $grahic_array_fact[$j]['month'] = $month;
                                                        $grahic_array_fact[$j]['year'] = $year;
                                                        $grahic_array_fact[$j]['kind_working_time_id'] = $kind_working_time->kind_working_time_id;
                                                        $grahic_array_fact[$j]['working_time_id'] = $working_time->working_time_id;
                                                        $grahic_array_fact[$j]['description'] = "";
                                                        $j++;
                                                    }
                                                }
                                            }
                                        } elseif (property_exists($working_time, 'kind_working_time')) {
                                            $grahic_array_fact[$j]['grafic_tabel_main_id'] = $new_grafic_tabel_main_id;
                                            $grahic_array_fact[$j]['mine_id'] = $working_time->mine_id ? $working_time->mine_id : null;
                                            $grahic_array_fact[$j]['day'] = $day->day;
                                            $grahic_array_fact[$j]['chane_id'] = $new_chane_id;
                                            $grahic_array_fact[$j]['shift_id'] = 5;
                                            $grahic_array_fact[$j]['worker_id'] = $worker->worker_id;
                                            $grahic_array_fact[$j]['hours_value'] = 0;
                                            $grahic_array_fact[$j]['role_id'] = $role->role_id;
                                            $grahic_array_fact[$j]['date_time'] = date("Y-m-d", strtotime($year . '-' . $month . '-' . $day->day));
                                            $grahic_array_fact[$j]['month'] = $month;
                                            $grahic_array_fact[$j]['year'] = $year;
                                            $grahic_array_fact[$j]['kind_working_time_id'] = $working_time->kind_working_time;                             //1 - значит рабочий день из справочника kind_working_time
                                            $grahic_array_fact[$j]['working_time_id'] = $working_time->working_time_id;
                                            $grahic_array_fact[$j]['description'] = "";
                                            $j++;
                                        } else {
                                            $grahic_array_fact[$j]['grafic_tabel_main_id'] = $new_grafic_tabel_main_id;
                                            $grahic_array_fact[$j]['mine_id'] = $working_time->mine_id ? $working_time->mine_id : null;
                                            $grahic_array_fact[$j]['day'] = $day->day;
                                            $grahic_array_fact[$j]['chane_id'] = $new_chane_id;
                                            $grahic_array_fact[$j]['shift_id'] = 5;
                                            $grahic_array_fact[$j]['worker_id'] = $worker->worker_id;
                                            $grahic_array_fact[$j]['hours_value'] = 0;
                                            $grahic_array_fact[$j]['role_id'] = $role->role_id;
                                            $grahic_array_fact[$j]['date_time'] = date("Y-m-d", strtotime($year . '-' . $month . '-' . $day->day));
                                            $grahic_array_fact[$j]['month'] = $month;
                                            $grahic_array_fact[$j]['year'] = $year;
                                            $grahic_array_fact[$j]['kind_working_time_id'] = 1;                             //1 - значит рабочий день из справочника kind_working_time
                                            $grahic_array_fact[$j]['working_time_id'] = $working_time->working_time_id;
                                            $grahic_array_fact[$j]['description'] = "";
                                            $j++;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            Yii::$app->db->createCommand()->batchInsert(ChaneWorker::tableName(), ['chane_id', 'worker_id', 'mine_id'], $chane_worker_arr)->execute();                                       // Закрепляем сотрудников за бригадами и звеньями
            Yii::$app->db->createCommand()->batchInsert(BrigadeWorker::tableName(), ['brigade_id', 'worker_id', 'mine_id'], $brigade_worker_arr)->execute();                                       // Закрепляем сотрудников за бригадами и звеньями
//            $log->addData($chane_worker_arr, '$chane_worker_arr', __LINE__);
            /**
             * Блок записи статусов ВГК в свойства работника
             */

            foreach ($department_list_vgk as $worker_vgk)
                if ($worker_vgk->vgk == 1) {
                    $workers_vgk_true[] = (int)$worker_vgk->id;
                } else {
                    $workers_vgk_false[] = (int)$worker_vgk->id;
                }


            if (isset($workers_vgk_false)) {
                $workers_string = implode(',', $workers_vgk_false);
                $sql = "UPDATE worker SET worker.vgk=0  WHERE worker.id in ($workers_string)";
                Yii::$app->db->createCommand($sql)->execute();
            }
            unset ($workers_string);
            if (isset($workers_vgk_true)) {
                $workers_string = implode(',', $workers_vgk_true);
                $sql = "UPDATE worker SET worker.vgk=1  WHERE worker.id in ($workers_string)";
                Yii::$app->db->createCommand($sql)->execute();
            }
            unset ($workers_string);
            // Если массив плановых выходов не пуст, то заполняем массово grafic_tabel_date_plan
            GraficTabelDatePlan::deleteAll(['grafic_tabel_main_id' => $new_grafic_tabel_main_id]);
            if (!empty($grahic_array_plan))                                                 // Если данные графика получены
            {

                /**************************** Заполнение графика ****************************/

                $sql = Yii::$app->db->queryBuilder->batchInsert('grafic_tabel_date_plan',
                    ['grafic_tabel_main_id', 'mine_id', 'day', 'chane_id', 'shift_id', 'worker_id', 'hours_value', 'role_id',
                        'date_time', 'month', 'year', 'kind_working_time_id', 'working_time_id', 'description'], $grahic_array_plan);
                Yii::$app->db->createCommand($sql . "ON DUPLICATE KEY UPDATE 
                `grafic_tabel_main_id` = VALUES (`grafic_tabel_main_id`),  
                `working_time_id` = VALUES (`working_time_id`),  
                `kind_working_time_id` = VALUES (`kind_working_time_id`), 
                `date_time` = VALUES (`date_time`), 
                `role_id` = VALUES (`role_id`),  
                `shift_id` = VALUES (`shift_id`),  
                `worker_id` = VALUES (`worker_id`)")->execute();
            }

            # region Обновление роли работника из графика выходов
            if (isset($worker_list_for_search) and isset($workers_role)) {
                foreach ($worker_list_for_search as $worker_item) {
                    $uniq_graf_workers[] = $worker_item;
                }
                $worker_objects = WorkerObject::find()
                    ->select(['id', 'role_id', 'worker_id', 'object_id'])
                    ->where(['in', 'worker_id', $uniq_graf_workers])
                    ->indexBy('worker_id')
                    ->asArray()
                    ->all();

                foreach ($workers_role as $worker_item) {

                    if (isset($worker_objects[$worker_item['worker_id']]) and
                        ($worker_objects[$worker_item['worker_id']]['role_id'] !== $worker_item['role_id'] or $worker_objects[$worker_item['worker_id']]['role_id'] == null)
                    ) {
                        $update_w_o[] = [
                            $worker_objects[$worker_item['worker_id']]['id'],
                            $worker_item['worker_id'],
                            $worker_objects[$worker_item['worker_id']]['object_id'],
                            $worker_item['role_id']
                        ];
                    }
                }
                if (isset($update_w_o)) {
                    $sql = Yii::$app->db->queryBuilder->batchInsert('worker_object', ['id', 'worker_id', 'object_id', 'role_id'], $update_w_o);
                    Yii::$app->db->createCommand($sql . "ON DUPLICATE KEY UPDATE `role_id` = VALUES (`role_id`)")->execute();
                }
            }
            #endregion


            GraficTabelDateFact::deleteAll(['grafic_tabel_main_id' => $new_grafic_tabel_main_id]);
            // Если массив плановых выходов не пуст, то заполняем массово grafic_tabel_date_plan
            if (!empty($grahic_array_fact))                                                 // Если данные графика получены
            {

                /**************************** Заполнение графика ****************************/
                $sql = Yii::$app->db->queryBuilder->batchInsert('grafic_tabel_date_fact',
                    ['grafic_tabel_main_id', 'mine_id', 'day', 'chane_id', 'shift_id', 'worker_id', 'hours_value', 'role_id',
                        'date_time', 'month', 'year', 'kind_working_time_id', 'working_time_id', 'description'], $grahic_array_fact);
                Yii::$app->db->createCommand($sql . "ON DUPLICATE KEY UPDATE 
                `grafic_tabel_main_id` = VALUES (`grafic_tabel_main_id`),  
                `working_time_id` = VALUES (`working_time_id`),  
                `kind_working_time_id` = VALUES (`kind_working_time_id`), 
                `date_time` = VALUES (`date_time`), 
                `role_id` = VALUES (`role_id`),  
                `shift_id` = VALUES (`shift_id`),  
                `worker_id` = VALUES (`worker_id`)")->execute();
            }
            $result = $post;
            $result->grafic_tabel_main_id = $new_grafic_tabel_main_id;

        } catch (Throwable $ex) {
//            $log->addData($day,'$day',__LINE__);
            $log->addError($ex->getMessage(), $ex->getLine());
        }


        return array_merge(['Items' => $result], $log->getLogAll());
    }
}
