<?php

namespace frontend\controllers\ordersystem;

use frontend\controllers\Assistant;
use frontend\models\Brigade;
use frontend\models\Chane;
use frontend\models\OperationType;
use frontend\models\OrderCyclegrammLava;
use frontend\models\Place;
use frontend\models\TypeOperation;
use Yii;
use frontend\models\GraficTabelDateFact;
use frontend\models\GraficTabelDatePlan;
use frontend\models\GraficTabelMain;
use frontend\models\OrderItrDepartment;
use frontend\models\OrderOperationWorker;
use frontend\models\Role;
use frontend\models\ViewOrderGiveFormRoles;
use frontend\models\Worker;
use yii\helpers\ArrayHelper;
use yii\web\Controller;


class OrderGiveFormController extends Controller
{

    //GetAllShiftAndWorkersPlanFact         - Возвращает данные из графиков выходов план/факт по заданым входным параметрам

    /**@var  STATUS_ACTIVE Статус работы 1 - обычный рабочий день*/
    const STATUS_ACTIVE = 1;
    /**@var  STATUS_ACTUAL Актуальный статус*/
    const STATUS_ACTUAL = 1;                                                                                            //актуальный статус

    public function actionIndex()
    {
        return $this->render('index');
    }

    /**Мето
     * Назначение: Метод получает список людей и их роли по участку
     * Название метода: GetITR()
     * @param null $data_post
     * @return array
     *
     * Входные необязательные параметры
     *
     * @package frontend\controllers\ordersystem
     *
     * Входные обязательные параметры:
     * @see
     * @example http://amicum/read-manager-amicum?controller=ordersystem\OrderGiveForm&method=GetITR&subscribe=worker_list&data={"company_department_id":"801"}
     *
     * Документация на портале:
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 29.05.2019 9:54
     * @since ver
     */
    public static function GetITR($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();                                                                                                // Промежуточный результирующий массив
        if ($data_post !== NULL && $data_post !== '') {
            try {
                $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
                if (property_exists($post_dec, 'company_department_id')) {
                    $warnings[] = 'GetITR. Данные успешно получены. Принятые данные: ' . $data_post;
                    $company_department_id = $post_dec->company_department_id;
                    $warnings[] = 'GetITR. Ищем данные в плановом графике';
                    $worker_list = Worker::find()
                        ->joinWith('workerObjects')
                        ->where(['company_department_id' => $company_department_id]);                                   //ищем работников по заданому фильтру
                    foreach ($worker_list->each(500) as $worker)                                                           //перебор найденных работников
                    {

                        if (!empty($worker->workerObjects)) {
                            $worker_find = OrderItrDepartment::find()
                                ->where(['worker_object_id' => $worker->workerObjects[0]->id])
                                ->limit(1)
                                ->one();
                            if ($worker_find) {
                                $result_view = ViewOrderGiveFormRoles::find()
                                    ->where(['worker_object_id' => $worker->workerObjects[0]])
                                    ->all();//                        $result['workers'][$result_view->worker_object_id];
                                foreach ($result_view as $result_item) {
                                    $result[$worker->id]['fullname'] = $result_item->FIO;
                                    $result[$worker->id]['worker_id'] = $worker->id;
                                    $result[$worker->id]['worker_object_id'] = $result_item->worker_object_id;
                                    $result[$worker->id]['role'][] = $result_item->role_title;
                                }
                            }

                        }
                    }
                } else {
                    $warnings[] = 'GetITR. Данные не получены, не хвататет каких-либо данных. Были полученны данные: ' . $data_post;
                }

            } catch (\Throwable $exception) {
                $errors[] = $exception->getMessage();
                $errors[] = $exception->getLine();
                $status = 0;
            }
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    //TODO метод написан не правильно. выгребаются все графики выходов, а должен браться последний. Якимов М.Н.

//    /**
//     * Назначение: Метод получает всех людей по смене, дню, году, месяцу. И иформацию о них.
//     * Название метода: GetWorkerListForShift()
//     * @param null $data_post
//     * @return array
//     *
//     * Входные необязательные параметры
//     *
//     * @package frontend\controllers\ordersystem
//     *
//     * Входные обязательные параметры:
//     * @see
//     * @example http://amicum/read-manager-amicum?controller=ordersystem\OrderGiveForm&method=GetWorkerListForShift&subscribe=worker_list&data={"shift_id":"1","year":"2019","month":"5","day":"1","brigade_id":"84"}
//     *
//     * Документация на портале:
//     * @author Рудов Михаил <rms@pfsz.ru>
//     * Created date: on 29.05.2019 11:02
//     * @since ver
//     */
//    public static function GetWorkerListForShift($data_post = NULL)
//    {
//        $status = 1;                                                                                                 // Флаг успешного выполнения метода
//        $warnings = array();                                                                                              // Массив предупреждений
//        $errors = array();                                                                                                // Массив ошибок
//        $result = array();                                                                                                // Промежуточный результирующий массив
//        if ($data_post !== NULL && $data_post !== '') {
//            try {
//                $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
////                $warnings[] = 'GetWorkerListForShift. Декодированные данные: '.$post_dec;
////                Assistant::PrintR($post_dec);
//                if (
//                    property_exists($post_dec, 'shift_id') &&
//                    property_exists($post_dec, 'year') &&
//                    property_exists($post_dec, 'month') &&
//                    property_exists($post_dec, 'day') &&
//                    property_exists($post_dec, 'brigade_id')) {
//                    $warnings[] = 'GetWorkerListForShift. Данные успешно получены.';
//                    $shift_id = $post_dec->shift_id;
//                    $year = $post_dec->year;
//                    $month = $post_dec->month;
//                    $day = $post_dec->day;
//                    $brigade_id = $post_dec->brigade_id;
//
//                    $filter = array('grafic_tabel_date_plan.year' => $year, 'grafic_tabel_date_plan.month' => $month,
//                        'grafic_tabel_date_plan.day' => $day, 'shift_id' => $shift_id);                                 //фильтр поиска по графику плановому
//                    $warnings[] = 'GetWorkerListForShift. Ищем данные в плановом графике';
//                    $grafic_in_filter = GraficTabelDatePlan::find()
//                        ->joinWith('role')
//                        ->where($filter);                                                                               //ищем график по заданому фильтру
//                    foreach ($grafic_in_filter->each() as $grafic_result)                                               //перебор найденных графиков
//                    {
//                        $warnings[] = 'GetWorkerListForShift. Ищем      людей по графику';
//                        $found_worker = Worker::find()
//                            ->joinWith('employee')
//                            ->joinWith('role')
//                            ->joinWith('position')
//                            ->joinWith('brigadeWorkers')
//                            ->joinWith('brigade')
//                            ->where(['worker.id' => $grafic_result->worker_id])
//                            ->andWhere(['brigade_id' => $brigade_id]);
//                        foreach ($found_worker->each() as $worker) {
//                            $warnings[] = 'GetWorkerListForShift. Перебор графиков';
//                            $result['brigade'][$worker->brigade[0]->id]['brigade_id'] = $worker->brigade[0]->id;
//                            $result['brigade'][$worker->brigade[0]->id]['description'] = $worker->brigade[0]->description;
//                            $result['brigade'][$worker->brigade[0]->id]['brigader_id'] = $worker->brigade[0]->brigader_id;
//                            $result['workers'][$worker->id]['worker_id'] = $worker->id;
//                            $name = mb_substr($worker->employee->first_name, 0, 1);
//                            $patronymic = mb_substr($worker->employee->patronymic, 0, 1);
//                            $result['workers'][$worker->id]['fullname'] = "{$worker->employee->last_name} {$name}. {$patronymic}.";
//                            $result['workers'][$worker->id]['role_in_shift'] = $grafic_result->role->title;
//                            $result['workers'][$worker->id]['qualification'] = $worker->position->qualification;
//                        }
//                    }
//                } else {
//                    $warnings[] = 'GetWorkerListForShift. Входной массив данных пуст, или нехватает каких-либо данных.';
//                }
//            } catch (\Throwable $exception) {
//                $errors[] = $exception->getMessage();
//                $errors[] = $exception->getLine();
//                $status = 0;
//            }
//        }
//        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
//        return $result_main;
//    }

    /**
     * Метод GetAllShiftAndWorkersPlanFact() - Возвращает данные из графиков выходов план/факт по заданым входным параметрам
     * @param null $data_post - JSON с данными: год, месяц, день, идентификатор участка
     * @return array - выходной массив с данными: date:
     *                                                  [brigade_id]                                                 -наименование бригады
     *                                                          brigade_description:
     *                                                          brigade_id:
     *                                                          [shift_id]                                           -наименование смены (Без смены - выходной/прогул/неявка/больничный)
     *                                                                  shift_id:
     *                                                                  shift_title:
     *                                                                  chaner_fullname:                                -ФИО звеневого (null - если это выходной/прогул/неявка/больничный)
     *                                                                  workers:                                       -список работников на этой смене
     *                                                                       [worker_id]                                -идентификатор работника
     *                                                                              worker_fullname:                    -ФИО работника
     *                                                                              worker_role:                        -роль работника из графика
     *                                                                              worker_tabelnumber:                 -табельный номер работника
     *                                                                              worker_qualification:               -разряд работника
     *                                                                              worker_id:                          -идентификатор работника
     *                                                                              isOutgoingInFact:                   -есть ли разница план/факт (true - если по плану и факту одно и тоже, false во всех остальных случаях)
     *                                                                              worker_description:                 -описание (причина не выхода работника)
     *
     * @package frontend\controllers\ordersystem
     *
     * @example http://amicum/read-manager-amicum?controller=ordersystem\OrderGiveForm&method=GetAllShiftAndWorkersPlanFact&subscribe=worker_list&data={%22year%22:%222019%22,%22month%22:%228%22,%22day%22:%2222%22,%22company_department_id%22:%22802%22}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.08.2019 14:45
     */
    public static function GetAllShiftAndWorkersPlanFact($data_post = NULL)
    {
        $status = 1;                                                                                                 // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();
        $description = null;
        $worker_description = null;
        $shift_workers_plan = array();
        if ($data_post !== NULL && $data_post !== '') {
            try {
                $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
                if (property_exists($post_dec, 'year') &&
                    property_exists($post_dec, 'month') &&
                    property_exists($post_dec, 'day') &&
                    property_exists($post_dec,'company_department_id')) {
                    $warnings[] = 'GetAllShiftAndWorkersPlanFact. Данные успешно получены.';
                    $year = $post_dec->year;
                    $month = $post_dec->month;
                    $day = $post_dec->day;
                    $company_department_id = $post_dec->company_department_id;

                }
                $warnings[] = 'GetAllShiftAndWorkersPlanFact. Ищем данные в фактическом графике';
                $grafics_fact = GraficTabelMain::find()
                    ->select([
                        'brigade.brigader_id as brigader_id',
                        'brigade.id as brigade_id',
                        'brigade.status_id as brigade_status_id',
                        'chane_grafic.id as chane_id',
                        'worker.id as worker_id',
                        'employee.last_name as worker_last_name',
                        'employee.first_name as worker_first_name',
                        'employee.patronymic as worker_patronymic',
                        'chaner_emoployee.last_name as chaner_last_name',
                        'chaner_emoployee.first_name as chaner_first_name',
                        'chaner_emoployee.patronymic as chaner_patronymic',
                        'shift.id as shift_id',
                        'shift.title as shift_title',
                        'role.title as role_title',
                        'position.qualification as qualification',
                        'worker.tabel_number as tabel_number',
                        'grafic_tabel_date_fact.description as description',
                        'brigade.description as brigade_description'])
                    ->innerJoin('grafic_tabel_date_fact',
                        'grafic_tabel_main.id = grafic_tabel_date_fact.grafic_tabel_main_id AND grafic_tabel_date_fact.day=' . $day)
                    ->innerJoin('worker', 'grafic_tabel_date_fact.worker_id = worker.id')
                    ->innerJoin('brigade_worker', 'brigade_worker.worker_id = worker.id')
                    ->innerJoin('brigade', 'brigade_worker.brigade_id = brigade.id and brigade.status_id=' . self::STATUS_ACTUAL)
                    ->innerJoin('worker AS brigader_worker', 'brigader_worker.id = brigade.brigader_id')
                    ->leftJoin('chane as chane_grafic', 'chane_grafic.id = grafic_tabel_date_fact.chane_id')
                    ->leftJoin('worker AS chane_worker', 'chane_worker.id = chane_grafic.chaner_id')
                    ->leftJoin('employee AS chaner_emoployee', 'chaner_emoployee.id = chane_worker.employee_id')
                    ->innerJoin('shift', 'grafic_tabel_date_fact.shift_id = shift.id')
                    ->innerJoin('role', 'grafic_tabel_date_fact.role_id = role.id')
                    ->innerJoin('employee', 'worker.employee_id = employee.id')
                    ->innerJoin('position', 'worker.position_id = position.id')
                    ->where(['grafic_tabel_main.year' => $year, 'grafic_tabel_main.month' => $month,
                        'grafic_tabel_main.status_id' => self::STATUS_ACTUAL,
                        'grafic_tabel_main.company_department_id'=>$company_department_id])
                    ->asArray()
                    ->all();
                $grafics_plan = GraficTabelMain::find()
                    ->select([
                        'brigade.brigader_id as brigader_id',                                                           //идентификатор бригадира
                        'brigader_employee.last_name as brigader_last_name',
                        'brigader_employee.first_name as brigader_first_name',
                        'brigader_employee.patronymic as brigader_patronymic',
                        'brigade.id as brigade_id',                                                                     //идентификатор бригады
                        'brigade.status_id as brigade_status_id',
                        'chane_grafic.id as chane_id',
                        'worker.id as worker_id',
                        'employee.last_name as worker_last_name',
                        'employee.first_name as worker_first_name',
                        'employee.patronymic as worker_patronymic',
                        'chaner_emoployee.last_name as chaner_last_name',
                        'chaner_emoployee.first_name as chaner_first_name',
                        'chaner_emoployee.patronymic as chaner_patronymic',
                        'shift.id as shift_id',
                        'shift.title as shift_title',
                        'role.title as role_title',
                        'position.qualification as qualification',
                        'worker.tabel_number as tabel_number',
                        'grafic_tabel_date_plan.description as description',
                        'brigade.description as brigade_description'])
                    ->innerJoin('grafic_tabel_date_plan',
                        'grafic_tabel_main.id = grafic_tabel_date_plan.grafic_tabel_main_id AND grafic_tabel_date_plan.day=' . $day)
                    ->innerJoin('worker', 'grafic_tabel_date_plan.worker_id = worker.id')
                    ->innerJoin('brigade_worker', 'brigade_worker.worker_id = worker.id')
                    ->innerJoin('brigade', 'brigade_worker.brigade_id = brigade.id and brigade.status_id=' . self::STATUS_ACTUAL)
                    ->innerJoin('worker AS brigader_worker', 'brigader_worker.id = brigade.brigader_id')
                    ->innerJoin('employee AS brigader_employee', 'brigader_employee.id = brigader_worker.employee_id')
                    ->leftJoin('chane as chane_grafic', 'chane_grafic.id = grafic_tabel_date_plan.chane_id')
                    ->leftJoin('worker AS chane_worker', 'chane_worker.id = chane_grafic.chaner_id')
                    ->leftJoin('employee AS chaner_emoployee', 'chaner_emoployee.id = chane_worker.employee_id')
                    ->innerJoin('shift', 'grafic_tabel_date_plan.shift_id = shift.id')
                    ->innerJoin('role', 'grafic_tabel_date_plan.role_id = role.id')
                    ->innerJoin('employee', 'worker.employee_id = employee.id')
                    ->innerJoin('position', 'worker.position_id = position.id')
                    ->where(['grafic_tabel_main.year' => $year, 'grafic_tabel_main.month' => $month,
                        'grafic_tabel_main.status_id' => self::STATUS_ACTUAL,
                        'grafic_tabel_main.company_department_id'=>$company_department_id])
                    ->asArray()
                    ->all();
                foreach ($grafics_plan as $plan_grafic_data) {
                    /**
                     * Если такого человека нет в плановом графике выходов и его смены различаются
                     * И он находиться в одном и том же звене по плану и факту  тогда "false"
                     */
                    $shift_id = $plan_grafic_data['shift_id'];
                    $shift_title = $plan_grafic_data['shift_title'];
                    $description_worker = $plan_grafic_data['description'];
                    $isOutgoingInFact = true;
                    $search_worker = array_search($plan_grafic_data['worker_id'],array_column($grafics_fact,'worker_id'));
                    if (false !== $search_worker){
                        if ($plan_grafic_data['shift_id'] !==  $grafics_fact[$search_worker]['shift_id']){
                            if($plan_grafic_data['chane_id'] ==  $grafics_fact[$search_worker]['chane_id'])
                            {
                                $shift_id = $grafics_fact[$search_worker]['shift_id'];
                                $shift_title = $grafics_fact[$search_worker]['shift_title'];
                                $description_worker = $grafics_fact[$search_worker]['description'];
                                $isOutgoingInFact = false;
                            }
                        }
                    }else{
                        $isOutgoingInFact = false;
                        $description_worker = 'Этого человека нет в фактическом графике выходов';
                    }

                    $shift_workers_plan['date'] = "{$day}-{$month}-{$year}";

                    /**
                     * Если звеневой не пуст тогда записываем в переменную фамилию человека, иначе нулл
                     */
                    if ($plan_grafic_data['chaner_last_name'] !== null) {
                        $full_name_chaner = "{$plan_grafic_data['chaner_last_name']} {$plan_grafic_data['chaner_first_name']} {$plan_grafic_data['chaner_patronymic']}";
                    }else{
                        $full_name_chaner = null;
                    }

                    /**
                     * Формируем результирующий массив
                     */
                    $shift_workers_plan['brigades'][$plan_grafic_data['brigade_id']]['brigade_id'] = $plan_grafic_data['brigade_id'];
                    $shift_workers_plan['brigades'][$plan_grafic_data['brigade_id']]['brigade_full_name'] = "{$plan_grafic_data['brigader_last_name']} {$plan_grafic_data['brigader_first_name']} {$plan_grafic_data['brigader_patronymic']}";
                    $shift_workers_plan['brigades'][$plan_grafic_data['brigade_id']]['brigade_description'] = $plan_grafic_data['brigade_description'];
                    $shift_workers_plan['brigades'][$plan_grafic_data['brigade_id']]['shifts'][$plan_grafic_data['shift_id']]['shift_id'] = $plan_grafic_data['shift_id'];
                    $shift_workers_plan['brigades'][$plan_grafic_data['brigade_id']]['shifts'][$plan_grafic_data['shift_id']]['shift_title'] = $plan_grafic_data['shift_title'];
                    $shift_workers_plan['brigades'][$plan_grafic_data['brigade_id']]['shifts'][$plan_grafic_data['shift_id']]['chaner_fullname'] = $full_name_chaner;
                    $full_name_worker = "{$plan_grafic_data['worker_last_name']} {$plan_grafic_data['worker_first_name']} {$plan_grafic_data['worker_patronymic']}";
                    $shift_workers_plan['brigades'][$plan_grafic_data['brigade_id']]['shifts'][$plan_grafic_data['shift_id']]['workers'][$plan_grafic_data['worker_id']]['worker_fullname'] = $full_name_worker;

                    /**
                     * Если роль человека "Проходчик", обрезаем её до "Прох."
                     */
                    if ($plan_grafic_data['role_title'] === 'Проходчик')
                    {
                        $role_title = mb_strimwidth($plan_grafic_data['role_title'],0,5,'.');
                    }else{
                        $role_title = $plan_grafic_data['role_title'];
                    }
                    $shift_workers_plan['brigades'][$plan_grafic_data['brigade_id']]['shifts'][$plan_grafic_data['shift_id']]['workers'][$plan_grafic_data['worker_id']]['worker_role'] = $role_title;
                    $shift_workers_plan['brigades'][$plan_grafic_data['brigade_id']]['shifts'][$plan_grafic_data['shift_id']]['workers'][$plan_grafic_data['worker_id']]['worker_tabelnumber'] = $plan_grafic_data['tabel_number'];
                    $shift_workers_plan['brigades'][$plan_grafic_data['brigade_id']]['shifts'][$plan_grafic_data['shift_id']]['workers'][$plan_grafic_data['worker_id']]['worker_qualification'] = $plan_grafic_data['qualification'];
                    $shift_workers_plan['brigades'][$plan_grafic_data['brigade_id']]['shifts'][$plan_grafic_data['shift_id']]['workers'][$plan_grafic_data['worker_id']]['worker_id'] = $plan_grafic_data['worker_id'];
                    $shift_workers_plan['brigades'][$plan_grafic_data['brigade_id']]['shifts'][$plan_grafic_data['shift_id']]['workers'][$plan_grafic_data['worker_id']]['isOutgoingInFact'] = $isOutgoingInFact;

                    $shift_workers_plan['brigades'][$plan_grafic_data['brigade_id']]['shifts'][$plan_grafic_data['shift_id']]['workers'][$plan_grafic_data['worker_id']]['worker_description'] = $description_worker;
                }
                foreach ($grafics_fact as $fact_grafic_data) {
                    /**
                     * Если идентификатор работника не найден в плановом графике выходов тогда записываем его в результирующий массив
                     * А в поле "description" пишем информацию о том, что "Этого работника не существует в плановом графике выходов"
                     */
                    $search_in_plan = array_search($fact_grafic_data['worker_id'], array_column($grafics_plan, 'worker_id'), true);
                    if (false === $search_in_plan){
                        $shift_workers_plan['date'] = "{$day}-{$month}-{$year}";

                        /**
                         * Если звеневой не пуст тогда записываем в переменную фамилию человека, иначе нулл
                         */
                        if ($fact_grafic_data['chaner_last_name'] !== null) {
                            $full_name_chaner = "{$fact_grafic_data['chaner_last_name']} {$fact_grafic_data['chaner_first_name']} {$fact_grafic_data['chaner_patronymic']}";
                        }else{
                            $full_name_chaner = null;
                        }

                        /**
                         * Формируем результирующий массив
                         */
                        $shift_workers_plan['brigades'][$fact_grafic_data['brigade_id']]['brigade_id'] = $fact_grafic_data['brigade_id'];
                        $shift_workers_plan['brigades'][$fact_grafic_data['brigade_id']]['brigade_description'] = $fact_grafic_data['brigade_description'];
                        $shift_workers_plan['brigades'][$fact_grafic_data['brigade_id']]['shifts'][$fact_grafic_data['shift_id']]['shift_id'] = $fact_grafic_data['shift_id'];
                        $shift_workers_plan['brigades'][$fact_grafic_data['brigade_id']]['shifts'][$fact_grafic_data['shift_id']]['chaner_fullname'] = $full_name_chaner;
                        $full_name_worker = "{$fact_grafic_data['worker_last_name']} {$fact_grafic_data['worker_first_name']} {$fact_grafic_data['worker_patronymic']}";
                        $shift_workers_plan['brigades'][$fact_grafic_data['brigade_id']]['shifts'][$fact_grafic_data['shift_id']]['workers'][$fact_grafic_data['worker_id']]['worker_fullname'] = $full_name_worker;

                        /**
                         * Если роль человека "Проходчик", обрезаем её до "Прох."
                         */
                        if ($fact_grafic_data['role_title'] === 'Проходчик')
                        {
                            $role_title = mb_strimwidth($fact_grafic_data['role_title'],0,5,'.');
                        }else{
                            $role_title = $fact_grafic_data['role_title'];
                        }
                        $shift_workers_plan['brigades'][$fact_grafic_data['brigade_id']]['shifts'][$fact_grafic_data['shift_id']]['workers'][$fact_grafic_data['worker_id']]['worker_role'] = $role_title;
                        $shift_workers_plan['brigades'][$fact_grafic_data['brigade_id']]['shifts'][$fact_grafic_data['shift_id']]['workers'][$fact_grafic_data['worker_id']]['worker_tabelnumber'] = $fact_grafic_data['tabel_number'];
                        $shift_workers_plan['brigades'][$fact_grafic_data['brigade_id']]['shifts'][$fact_grafic_data['shift_id']]['workers'][$fact_grafic_data['worker_id']]['worker_qualification'] = $fact_grafic_data['qualification'];
                        $shift_workers_plan['brigades'][$fact_grafic_data['brigade_id']]['shifts'][$fact_grafic_data['shift_id']]['workers'][$fact_grafic_data['worker_id']]['worker_id'] = $fact_grafic_data['worker_id'];
                        $shift_workers_plan['brigades'][$fact_grafic_data['brigade_id']]['shifts'][$fact_grafic_data['shift_id']]['workers'][$fact_grafic_data['worker_id']]['isOutgoingInFact'] = true;
                        $shift_workers_plan['brigades'][$fact_grafic_data['brigade_id']]['shifts'][$fact_grafic_data['shift_id']]['workers'][$fact_grafic_data['worker_id']]['worker_description'] = 'Этого работника не существует в плановом графике выходов';
                    }
                }
            } catch (\Throwable $exception) {
                $warnings[] = 'GetAllShiftAndWorkersPlanFact. Исключение';
                $errors[] = $exception->getMessage();
                $errors[] = $exception->getLine();
                $status = 0;
            }
        }
        $result = $shift_workers_plan;
        $warnings[] = 'GetAllShiftAndWorkersPlanFact. Достигнут конец метда';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Назначение: Выхождаемость работников
     * Название метода: OutgoingPlanFactValue()
     * @param null $data_post - JSON массив данных (день, месяц, год, ид смены)
     * @return array - массив  с данными: [day]
     *                                        date:
     *                                        isPlanEqualFact: (0 - если план не равен факту, 1 - если план равен факту)
     *
     * Входные необязательные параметры
     *
     * @package frontend\controllers\ordersystem
     *
     * Входные обязательные параметры:$data_post - JSON  с данными
     *                                                        (год, месяц, идентификатор участка company_department_id)
     * @see
     * @example http://amicum/read-manager-amicum?controller=ordersystem\OrderGiveForm&method=OutgoingPlanFactValue&subscribe=worker_list&data={%22year%22:%222019%22,%22month%22:%227%22,%22company_department_id%22:%22802%22}
     *
     * Документация на портале:
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 03.06.2019 8:55
     * @since ver
     */
    public static function OutgoingPlanFactValue($data_post = NULL)
    {
        $status = 1;                                                                                                 // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();
        $outgoing_result = null;
        $shift_id = null;
        if ($data_post !== NULL && $data_post !== '') {
            try {
                $warnings[] = 'OutgoingPlanFactValue. Данные успешно получены.';
                $warnings[] = 'OutgoingPlanFactValue. Декодируем данные.';
                $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
                if (property_exists($post_dec, 'year') &&
                    property_exists($post_dec, 'month') &&
                    property_exists($post_dec, 'company_department_id')                                         //проверяем есть ли во входных данных (год, месяц, участок)
                ) {
                    $grafic_year = $post_dec->year;
                    $grafic_month = $post_dec->month;
                    $company_department_id = $post_dec->company_department_id;
                    if (property_exists($post_dec,'shift_id'))
                    {
                        $shift_id = $post_dec->shift_id;
                    }
                    $warnings[] = "OutgoingPlanFactValue. Входные параметры успешно получены. {$grafic_year}-{$grafic_month}-{$company_department_id}";
                } else {                                                                                                //если нет, тогда генерируем исключение
                    throw new \Exception("OutgoingPlanFactValue. Неверные входные параметры. Либо отсутствуют. year, month, company_department_id");
                }
                /*********************************** Формирую шаблон выходных данных *********************************/
                for ($i = 1; $i <= cal_days_in_month(CAL_GREGORIAN, $grafic_month, $grafic_year); $i++) {
                    $outgoing_result[$i]['grafic_day'] = "{$i}";
                    $outgoing_result[$i]['outgoing_plan'] = 0;
                    $outgoing_result[$i]['outgoing_fact'] = 0;
                }
                $warnings[] = "OutgoingPlanFactValue. Шаблон входных данных сформирован.";

                /************* Блок формирования данных по плановому графику выходов для расчёта выхождаемости********/
                $grafic_tabel_main_id_plans = GraficTabelMain::find()
                    ->select(['grafic_tabel_date_plan.day as grafic_day'])
                    ->innerJoin('grafic_tabel_date_plan', 'grafic_tabel_date_plan.grafic_tabel_main_id = grafic_tabel_main.id')
                    ->where([
                        'grafic_tabel_main.company_department_id' => $company_department_id,
                        'grafic_tabel_main.year' => $grafic_year,
                        'grafic_tabel_main.month' => $grafic_month,
                        'grafic_tabel_main.status_id' => self::STATUS_ACTUAL,
                        'grafic_tabel_date_plan.working_time_id'=>self::STATUS_ACTIVE
                    ])
                    ->andFilterWhere(['grafic_tabel_date_plan.shift_id'=>$shift_id])
                    ->asArray()
                    ->all();

                if (!empty($grafic_tabel_main_id_plans)) {
                    foreach ($grafic_tabel_main_id_plans as $grafic_tabel_main_id_plan) {
                        $outgoing_result[$grafic_tabel_main_id_plan['grafic_day']]['outgoing_plan']++;
                    }
                    $warnings[] = "OutgoingPlanFactValue. График по плану записан.";
                } else {
                    $warnings[] = 'OutgoingPlanFactValue.Данных по плановому графику не найдено.';
                }

                /*********** Блок формирования данных по фактическому графику выходов для расчёта выхождаемости********/
                $grafic_tabel_main_id_facts = GraficTabelMain::find()
                    ->select(['grafic_tabel_date_fact.day as grafic_day'])
                    ->innerJoin('grafic_tabel_date_fact', 'grafic_tabel_date_fact.grafic_tabel_main_id = grafic_tabel_main.id')
                    ->where([
                        'grafic_tabel_main.company_department_id' => $company_department_id,
                        'grafic_tabel_main.year' => $grafic_year,
                        'grafic_tabel_main.month' => $grafic_month,
                        'grafic_tabel_main.status_id' => self::STATUS_ACTUAL,
                        'grafic_tabel_date_fact.working_time_id'=>self::STATUS_ACTIVE
                    ])
                    ->andFilterWhere(['grafic_tabel_date_fact.shift_id'=>$shift_id])
                    ->asArray()
                    ->all();
                if (!empty($grafic_tabel_main_id_facts)) {
                    foreach ($grafic_tabel_main_id_facts as $grafic_tabel_main_id_fact) {
                        $outgoing_result[$grafic_tabel_main_id_fact['grafic_day']]['outgoing_fact']++;
                    }
                    $warnings[] = "OutgoingPlanFactValue. График по факту записан.";
                } else {
                    $warnings[] = 'OutgoingPlanFactValue.Данных по фактическому графику не найдено.';
                }
                foreach ($outgoing_result as $outgoing) {
                    if (!empty($outgoing['outgoing_plan']) || !empty($outgoing['outgoing_fact'])){
                        if ($outgoing['outgoing_plan'] !== $outgoing['outgoing_fact'])
                        {
                            $isPlanEqualFact = 0;
                        }else{
                            $isPlanEqualFact = 1;
                        }
                    }else{
                        $isPlanEqualFact = null;
                    }

                    $result[$outgoing['grafic_day']]['date'] = "{$outgoing['grafic_day']}-{$grafic_month}-{$grafic_year}";
                    $result[$outgoing['grafic_day']]['isPlanEqualFact'] = $isPlanEqualFact;

                }

            } catch (\Throwable $exception) {
                $errors[] = $exception->getMessage();
                $errors[] = $exception->getLine();
                $status = 0;
            }
        }
        $warnings[] = 'OutgoingPlanFactValue. Достигнут конец метда';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     *
     * Название метода: GetInfoAboutWorker() - Метод получает информацию о человеке по идентификатору типизированного работника (worker_object_id)
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\ordersystem
     *
     * @see
     * @example amicum/read-manager-amicum?controller=ordersystem\OrderGiveForm&method=GetInfoAboutWorker&subscribe=worker_list&data={"worker_object_id":"9773"}
     *
     * Документация на портале:
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 30.05.2019 11:14
     * @since ver
     */
    public static function GetInfoAboutWorker($data_post = NULL)
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
                        $result[$worker_object_id]['image'] = "";
                        $birthdate = strtotime($worker[0]['employee']['birthdate']);
                        $result[$worker_object_id]['birthdate'] = $birthdate * 1000;
                        $result[$worker_object_id]['gender'] = $worker[0]['employee']['gender'];
                        foreach ($worker[0]['workerObjectsRole'] as $worker_role) {
                            $role = Role::find()
                                ->where(['id' => $worker_role['role_id']])
                                ->limit(1)
                                ->one();
                            $result[$worker_object_id]['role'][] = $role['title'];
                        }
                        $result[$worker_object_id]['qualification'] = $worker[0]['position']['qualification'];
                        $date_start_work = strtotime($worker[0]['date_start']);
                        $result[$worker_object_id]['date_start_work'] = $date_start_work * 1000;
                        $date_end_work = strtotime($worker[0]['date_end']);
                        $result[$worker_object_id]['date_end_work'] = $date_end_work * 1000;
                        $result[$worker_object_id]['experience_on_role'] = '';
                    } else                                                                                                //иначе предупреждение о том что работник не найден
                    {
                        $warnings[] = 'GetInfoAboutWorker.Работник не найден. Переданный идентификатор типизированного работника. ' . $worker_object_id;
                    }
                } else                                                                                                    //иначе записываем информацию о том что не передан входной массив данных либо он не верен
                {
                    $warnings[] = 'GetInfoAboutWorker. Входной массив данных пуст либо содержит ошибки';
                }
            } catch (\Throwable $exception)                                                                               //ловим все ошибки и исключения
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
     * Название метода: ReportBrigadesByDate() - Метод получения списка бригад по конкретной дате
     *
     * @param null $data_post
     * @return array
     * @package frontend\controllers\ordersystem
     * @example http://web.amicum/read-manager-amicum?controller=ordersystem\OrderGiveForm&method=ReportBrigadesByDate&subscribe=worker_list&data={%22year%22:%222019%22,%22month%22:%226%22,%22day%22:%2218%22}
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 18.06.2019 9:31
     * @since ver
     */
    public static function ReportBrigadesByDate($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $brigades_list = array();                                                                                            // Промежуточный результирующий массив
        if ($data_post !== NULL && $data_post !== '') {
            $warnings[] = 'ReportBrigadesByDate. Данные успешно переданы';
            $warnings[] = 'ReportBrigadesByDate. Входной массив данных' . $data_post;
            try {
                $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
                $warnings[] = 'ReportBrigadesByDate. Декодировал входные параметры';

                if (
                    property_exists($post_dec, 'year') &&
                    property_exists($post_dec, 'month') &&
                    property_exists($post_dec, 'day')
                )                                                                                                       // Проверяем наличие в нем нужных нам полей
                {
                    $warnings[] = 'ReportBrigadesByDate.Данные с фронта получены';
                    $year = $post_dec->year;
                    $month = $post_dec->month;
                    $day = $post_dec->day;

                    $brigades = OrderOperationWorker::find()// Получение списка актуальных бригад работника согласно нарядам за конкретную дату
                    ->joinWith('worker.actualBrigade')
                        ->where(['>=', 'order_operation_worker.date_time', $year . '-' . $month . '-' . $day . ' 00:00:00'])
                        ->andWhere(['<=', 'order_operation_worker.date_time', $year . '-' . $month . '-' . $day . ' 23:59:59'])
                        ->asArray()
                        ->all();
                    foreach ($brigades AS $brigade) {
                        if (isset($brigade['worker']['actualBrigade'])) {
                            $brigades_list[$brigade['worker']['actualBrigade']['id']] = $brigade['worker']['actualBrigade']['description'];
                        }
                    }
                } else {
                    $errors[] = 'ReportBrigadesByDate. Переданы некорректные входные параметры';
                    $status *= 0;
                }
            } catch (\Throwable $exception) {
                $errors[] = $exception->getMessage();
                $errors[] = $exception->getLine();
                $status *= 0;
            }
        } else {
            $errors[] = 'ReportBrigadesByDate. Данные с фронта не получены';
            $status = 0;
        }
        $result = $brigades_list;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод возвращает тип операции, наименование операции, количество людей на операции, единицу измерения и значение
     * Название метода: GetOrderPlaceOperation()
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\ordersystem
     *
     * @see
     * @example http://amicum/read-manager-amicum?controller=ordersystem\OrderGiveForm&method=GetOrderPlaceOperation&subscribe=worker_list&data={"place_id":"6181"}
     *
     * Документация на портале:
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 21.06.2019 15:44
     * @since ver
     */
    public static function GetOrderPlaceOperation($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $place_operations = array();                                                                                         // Промежуточный результирующий массив
        if ($data_post !== NULL && $data_post !== '')
        {
            $warnings[] = 'GetOrderPlaceOperation. Данные успешно переданы';
            $warnings[] = 'GetOrderPlaceOperation. Входной массив данных' . $data_post;
            try
            {
                $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
                $warnings[] = 'GetOrderPlaceOperation. Декодировал входные параметры';

                if (
                    property_exists($post_dec, 'place_id')
                )                                                                                                       // Проверяем наличие в нем нужных нам полей
                {
                    $warnings[] = 'GetOrderPlaceOperation.Данные с фронта получены';
                    $place_id = $post_dec->place_id;
                    $warnings[] = 'GetOrderPlaceOperation.Поиск данных';
                    $found_place = Place::find()
                        ->joinWith('configurationFaces')
                        ->joinWith('configurationFaces.passport')
                        ->joinWith('configurationFaces.passport.passportOperations')
                        ->joinWith('configurationFaces.passport.passportOperations.operation')
                        ->joinWith('configurationFaces.passport.passportOperations.operation.operationType')
                        ->joinWith('configurationFaces.passport.passportOperations.operation.unit')
                        ->joinWith('configurationFaces.passport.passportOperations.operation.orderOperationWorkers')
                        ->where(['place.id' => $place_id]);
                    if ($found_place) {
                        $warnings[] = 'GetOrderPlaceOperation. Данные найдены';
                        foreach ($found_place->each() as $place) {
                            $place_operations['place_id'] = $place->id;
                            $place_operations['place_title'] = $place->title;
                            foreach ($place->configurationFaces as $conf_face) {
                                foreach ($conf_face->passport->passportOperations as $passportOperation) {
                                    $place_operations[$place->id]['type_operation'] = $passportOperation->operation->operationType->title;
                                    $place_operations[$place->id]['operation'] = $passportOperation->operation->title;
                                    $place_operations[$place->id]['value'] = $passportOperation->operation->value;
                                    $place_operations[$place->id]['unit'] = $passportOperation->operation->unit->short;
                                    $place_operations[$place->id]['count_worker'] = count($passportOperation->operation->orderOperationWorkers);
                                }
                            }
                        }
                    } else {
                        throw new  \Exception('Данных не найдено');
                    }
                } else {
                    $errors[] = 'GetOrderPlaceOperation. Переданы некорректные входные параметры';
                    $status *= 0;
                }
            } catch (\Throwable $exception) {
                $errors[] = $exception->getMessage();
                $errors[] = $exception->getLine();
                $status *= 0;
            }
        }
        else
        {
            $errors[] = 'GetOrderPlaceOperation. Данные с фронта не получены';
            $status *= 0;
        }
        $warnings[] = 'GetOrderPlaceOperation. Достигнут конец метода';
        $result = $place_operations;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Название метода: actionTest()
     * Метод для отладки, использовать в том случае если нужен отладчик yii2 например для просмотра отправленных запросов
     *
     * @param $data
     * @return string
     * @package frontend\controllers\ordersystem
     * @example http://web.amicum/order-system/order-give-form/test?data={}
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 18.06.2019 10:49
     * @since ver
     */
    public function actionTest($data)
    {
        self::ReportDataByShift($data);
        return $this->render('index');
    }


}
