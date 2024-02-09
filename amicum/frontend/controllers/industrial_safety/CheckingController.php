<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\industrial_safety;

use backend\controllers\Assistant as AssistansBackend;
use backend\controllers\Assistant as BackendAssistant;
use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\handbooks\DepartmentController;
use frontend\controllers\handbooks\HandbookDepartmentController;
use frontend\controllers\handbooks\InjunctionController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Attachment;
use frontend\models\Audit;
use frontend\models\AuditPlace;
use frontend\models\AuditWorker;
use frontend\models\Checking;
use frontend\models\CheckingGratitude;
use frontend\models\CheckingGratitudeAttachment;
use frontend\models\CheckingGratitudeWorker;
use frontend\models\CheckingType;
use frontend\models\CheckingWorkerType;
use frontend\models\Company;
use frontend\models\CorrectMeasures;
use frontend\models\Document;
use frontend\models\Equipment;
use frontend\models\Injunction;
use frontend\models\InjunctionImg;
use frontend\models\InjunctionStatus;
use frontend\models\InjunctionViolation;
use frontend\models\KindStopPb;
use frontend\models\KindViolation;
use frontend\models\OrderOperation;
use frontend\models\Place;
use frontend\models\PlaceCompanyDepartment;
use frontend\models\ReasonDangerMotion;
use frontend\models\Unit;
use frontend\models\ViewInitWorkerParameterHandbookValue;
use frontend\models\ViolationType;
use frontend\models\Worker;
use Throwable;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\web\Controller;


class CheckingController extends Controller
{

    #region /************************************************** БЛОК КОНСТАНТ *****************************************/
    const WORKER_SCREEN_PARAM = 3;
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 19;
    const WORKER_TYPE_INSPECTOR = 1;
    const WORKER_TYPE_VIOLATOR = 3;
    const KIND_INJUNCTION = 1;
    const KIND_PAB = 2;
    const ROLE_CHIEF_DEPARTMENT = 175;
    const WORKER_TYPE_AUDITOR = 1;                                                                                      // ключ аудитора
    const WORKER_TYPE_RESPONSIBLE = 2;                                                                                  // ключ ответственного
    const WORKER_TYPE_PRESENT = 3;                                                                                      // ключ присутствующего
    const STATUS_YES = 55;
    const STATUS_NO = 56;
    /**@var int статус Просрочено */
    const STATUS_NEW = 57;                                                                                              // статус предписания новое
    const STATUS_IN_JOB = 58;                                                                                           // статус предписания в работе
    const STATUS_DONE = 59;                                                                                             // статус предписания устранено
    const STATUS_EXPIRED = 60;                                                                                          // статус просрочено
    #endregion
    #region /******************************************** БЛОК ОПИСАНИЯ МЕТОДОВ ***************************************/
    // GetCheckingData                   - Метод получает данные о проверке, предписании, корректирующих мероприятиях и остановке ПБ
    // ChaneStatusInjunction             - Метод меняет статус предписания
    // GetListViolationType              - Метод получения всех типов нарушений
    // GetNewCheckingId                  - Метод получения последнего идентификатор проверки, для формирования новой проверки
    // GetWorkerByCompanyDepartment      - Метод получения списка работников и данных о них
    // GetPlacesList                     - Получение списка мест
    // SaveChecking                      - Метод сохранения проверки
    // SaveInjunction                    - Метод сохранения проверки
    // GetArchiveInjunction              - Метод получение данных для архива предписаний
    // GetArchivePab                     - Метод получение данных для архива ПАБ
    // GetArchiveChecking                - Метод получения данных для архива проверок
    // ReasonDangerMotions               - Метод возвращает список причин опасных действий
    // AddResultCorrectMeasures          - Метод добавляет результат корректирующего мероприятия
    // ChangeStatuCorrectMeasures        - Метод смены статуса корректирущим мероприятиям
    // DeletedCheckingOrInjunction       - Метод удаления проверки или предписания/ПАБа по флагу delete_type (true - проверка, false - предписание/ПАБ)
    // ChangeCheckingType                - Метод смены типа проверки
    // GetInjunctionStatistic            - Метод получения статистики по предписаниям
    // GetPlannedAudit                   - Получение запланированных аудитов за год
    // SavePlannedAudit                  - Метод сохранения запланированного аудита
    // DeletedAudit                      - Удаление аудита по идентификатору
    // ChangeAudit                       - Метод изменения аудита
    // GetEquipmentGroup                 - Справочник оборудований сгрупиированный по типам объекта
    // GetArchiveCheckingForStatistic    - Проверки для таблицы в статистике "Статистика предписаний/ПАБ"
    // GetPabsInforamtion                - Метод получения данных на странице "Учёт нарушений"
    // MarkAboutCompletePab              - Отетка о выполнении ПАБа
    // KindStopPb                        - возвращает список типов простоев
    // SaveViolationDisconformity        - Сохранение нарушения несоответствия
    // GetInfoAboutPab                   - получение информации о ПАБ или список информации о ПАБ
    // GetInfoAboutInjunction            - Получение информации о предписании нарушения или список информации о предписании нарушения
    // GetMineInjunctions                - получение данных для архива предписаний по шахтам
    // SaveCheckingGratitude             - Сохранение благодарности

    // внешние методы:
    // ReportForPreviousPeriodController/GetEvents() - метод получения списка событий - причина простоя
    #endregion
    const WORKER_TYPE_NARUSHITEL = 4;


    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Название метода: GetCheckingData() - Метод получает данные о проверке, предписании, корректирующих мероприятиях и остановке ПБ
     *
     * @param string $data_post - JSON строка с идентификатором участка (company_department_id)
     *
     * @return array - возвращает данные по следующей струтктуре
     *
     * СТРУКТУРА: checking                                                                                              - проверка
     *              [checking_id]                                                                                       - идентификатор проверки
     *                      checking_id                                                                                 - идентификатор проверки
     *                      checking_title                                                                              - наименование проверки
     *                      checking_type                                                                               - тип проверки
     *                      date_time_start                                                                             - дата начала проверки
     *                      date_time_end                                                                               - дата окончания проверки
     *                      crew_member                                                                                 - все люди которые участвовали в проверки
     *                            [checking_worker_type_id] (crew_member_id)                                            - идентификатор списка работников участвующих в проверке
     *                                      crew_member_id                                                              - идентификатор списка работников участвующих в проверке
     *                                      worker_id                                                                   - идентификатор работника
     *                                      worker_type_id                                                              - тип работника
     *                      injunction                                                                                  - предписание
     *                              [injunction_id]                                                                     - идентификатор предписания
     *                                          attachments                                                             - вложения
     *                                                  [attachment_id]                                                 - идентификатор вложения
     *                                                              attachment_id                                       - идентификатор вложения
     *                                         injunction_id                                                            - идентификатор предписания
     *                                         place_id                                                                 - идентификатор места
     *                                         worker_id (аудитор, первый из всех аудиторов)                            - идентификатор аудитора первого из всех аудиторов
     *                                         kind_document_id                                                         - документ
     *                                         rtn_statistis_status                                                     - статус РТН
     *                                         injunction_violation                                                     - предписание нарушения
     *                                                [injunction_violation_id]                                         - идентификатор предписание нарушения
     *                                                     injunction_violation_id                                      - идентификатор предписания нарушения
     *                                                     reason_danger_motion_id                                      - причина опасного действия
     *                                                     probability                                                  - вероятность
     *                                                     dangerous                                                    - опасность
     *                                                     correct_peroiod                                              - срок устранения нарушения
     *                                                     violation_id                                                 - идентификатор нарушения
     *                                                     violation_type_id                                            - идентификатор типа нарушения (направления)
     *                                                     document_id                                                  - идентификатор документа ПБ
     *                                                     paragraph_pb_id                                              - идентификатор пункта документа ПБ
     *                                                     injunction_img                                               - изображения нарушения
     *                                                              [injunction_img]                                    - идентификатор изображения нарушения
     *                                                                          injunction_img_id                       - идентификатор изображения нарушения
     *                                                                          injunction_img_path                     - путь до изображения
     *                                                     injunction_description                                       - описание предписания
     *                                                     paragraph_injunction_description                             - описание пункта Персональной Безопасности
     *
     *                                                     injunction_violation_statuses:                               - статусы нарушений предписаний
     *                                                                  [injunction_violation_status_id]                - идентификатор таблицы статуса нарушения предписания
     *                                                                              status_id:                          - статус нарушения предписания
     *                                                     violators:                                                   - нарушители предписания
     *                                                                  [worker_id]                                     - идентификатор работника нарушевшего предписания
     *                                                                              worker_id:                          - идентификатор работника нарушевшего предписания
     *                                                     correct_measures                                             - корректирующие мероприятия
     *                                                              [correct_measures_id]                               - идентификатор корректирующего мероприятия
     *                                                                          correct_measures_id                     - идентификатор корректирующего мероприятия
     *                                                                          operation_id                            - идентификатор опреации
     *                                                                          operation_description                   - описание операции
     *                                                                          correct_measures_description            - описание корректирующего мероприятия
     *                                                                          operation_unit                          - единица измерения операции
     *                                                                          correct_measures_value                  - объём операции
     *                                                                          worker_id                               - идентификатор работника ответственного за операцию
     *                                                                          date_plan                               - планируемая дата выполнения корректирющего мероприятия
     *                                                                          result_correct_measures                 - результат корректирующего действия
     *                                                     stop_pb                                                      - Простои ПБ
     *                                                          [stop_pb_id]                                            - идентификатор простоя ПБ
     *                                                                  stop_pb_id                                      - идентификатор ПБ
     *                                                                  date_time_start                                 - дата и время начала простоя
     *                                                                  date_time_end                                   - дата и время окончания простоя
     *                                                                  equipment_id                                    - идентификатор оборудования
     *                                                                  place_id                                        - идентификатор места
     *                                                                  kind_stop_pb                                    - вид простоя
     *                                                                  kind_duration                                   - длительность простоя
     *                                          injunction_status                                                       - статус предписания
     *                                              [injunction_status_id]                                              - идентификатор статуса предписания
     *                                                  injunction_status_id                                            - идентификатор статуса предписания
     *                                                  worker_id                                                       - идентификатор работника (незнаю зачем он тут нужен)
     *                                                  date_time                                                       - дата и время смены статуса
     *                                                  status_id                                                       - статус
     *
     *
     * @package frontend\controllers\industrial_safety
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Checking&method=GetCheckingData&subscribe=&data={"company_department_id":"20000636","checking_id":9}
     *
     * @author Рудов Михаил <rms@pfsz.ru>, Евгений Некрасов <nep@pfsz.ru>
     * Created date: on 07.06.2019 16:21
     */
    public static function GetCheckingData($data_post = NULL)
    {
        $status = 1;                                                                                                    //флаг успешного выполнения метода
        $warnings = array();                                                                                            // массив предупреждений
        $errors = array();                                                                                              // массив ошибок
        $checking_info = array();
        try {

            if (is_null($data_post) or $data_post == "") {
                throw new Exception("GetCheckingData. Входной массив обязательных данных пуст");
            }

            $warnings[] = 'GetCheckingData. Данные успешно переданы';
            $warnings[] = 'GetCheckingData. Входной массив данных' . $data_post;
            $post_data = json_decode($data_post);
            $warnings[] = 'GetCheckingData. Декодировал входные параметры';
            if (property_exists($post_data, 'company_department_id') &&
                property_exists($post_data, 'checking_id')) {
                $warnings[] = 'GetCheckingData. Получение данных проверки из БД';
                $checking_id = $post_data->checking_id;
                $company_department_id = $post_data->company_department_id;
                $checking_data = Checking::find()
                    ->joinWith('injunctions.place1')
                    ->joinWith('injunctions.injunctionViolations.violators.workerEmployee')
                    ->joinWith('injunctions.injunctionViolations.injunctionViolationStatuses')
                    ->joinWith('injunctions.injunctionViolations.reasonDangerMotion')
                    ->joinWith('injunctions.injunctionViolations.violation.violationType')
                    ->joinWith('injunctions.injunctionViolations.document')
                    ->joinWith('injunctions.injunctionViolations.stopPbs.place')
                    ->joinWith('injunctions.injunctionViolations.stopPbs.stopPbEquipments.equipment')
                    ->joinWith('injunctions.injunctionViolations.stopPbs.kindStopPb')
                    ->joinWith('injunctions.injunctionViolations.stopPbs.kindDuration')
                    ->joinWith('injunctions.injunctionViolations.injunctionImg')
                    ->joinWith('injunctions.injunctionViolations.correctMeasures.operation.unit')
                    ->joinWith('injunctions.injunctionAttachment.attachment')
                    ->joinWith('checkingWorkerTypes.worker.employee')
                    ->where(['checking.company_department_id' => $company_department_id,
                        'checking.id' => $checking_id])
                    ->all();                                                                                        //ищем проверку со всеми связанными таблицами (предписание, нарушение, статус предписания, вложения предписания, пункт нарушения предписания, вид простоя, длительность простоя, картинка места простоя, корректирующие мероприятия, операции корректирующего мероприятия)
                $warnings[] = 'GetCheckingData. Данные получены из БД, перебор результата';
                if (!empty($checking_data)) {
                    foreach ($checking_data as $checking_item)                                                          //перебор всех проверок
                    {

                        $checking_info['checking'][$checking_item->id]['checking_id'] = $checking_item->id;
                        $checking_info['checking'][$checking_item->id]['checking_title'] = $checking_item->title;
                        $checking_info['checking'][$checking_item->id]['checking_type'] = $checking_item->checking_type_id;
                        $checking_info['checking'][$checking_item->id]['date_time_start'] = $checking_item->date_time_start;
                        $checking_info['checking'][$checking_item->id]['date_time_start_format'] = date('d.m.Y H:i', strtotime($checking_item->date_time_start));
                        $checking_info['checking'][$checking_item->id]['date_time_end'] = $checking_item->date_time_end;
                        $checking_info['checking'][$checking_item->id]['date_time_end_format'] = date('d.m.Y H:i', strtotime($checking_item->date_time_end));
                        $checking_info['checking'][$checking_item->id]['company_department_id'] = $checking_item->company_department_id;

                        foreach ($checking_item->checkingWorkerTypes as $checking_worker_type)                          //перебор всех работников по типам (инспектор, ответственный, присутствующий)
                        {
                            $checking_info['checking'][$checking_item->id]['crew_member'][$checking_worker_type->id]['crew_member_id'] = $checking_worker_type->id;
                            $checking_info['checking'][$checking_item->id]['crew_member'][$checking_worker_type->id]['worker_id'] = $checking_worker_type->worker_id;
                            $checking_info['checking'][$checking_item->id]['crew_member'][$checking_worker_type->id]['full_name'] = Assistant::GetFullName($checking_worker_type->worker->employee->first_name, $checking_worker_type->worker->employee->patronymic, $checking_worker_type->worker->employee->last_name);
                            $checking_info['checking'][$checking_item->id]['crew_member'][$checking_worker_type->id]['worker_type_id'] = $checking_worker_type->worker_type_id;
                        }

                        foreach ($checking_item->injunctions as $injunction_item)                                       //перебор всех предписаний
                        {
                            $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['attachments'] = array();
                            foreach ($injunction_item->injunctionAttachment as $injunction_attachment_item)             //перебор вложений предписания
                            {
                                $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['attachments'][$injunction_attachment_item->attachment_id]['attachment_id'] = $injunction_attachment_item->attachment_id;
                                $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['attachments'][$injunction_attachment_item->attachment_id]['attachment_src'] = $injunction_attachment_item->attachment->path;
                                $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['attachments'][$injunction_attachment_item->attachment_id]['attachment_flag'] = null;
                                $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['attachments'][$injunction_attachment_item->attachment_id]['attachment_type'] = null;
                                $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['attachments'][$injunction_attachment_item->attachment_id]['attachment_name'] = basename($injunction_attachment_item->attachment->path);
                            }
                            if (count($checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['attachments']) == 0) {
                                $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['attachments'] = (object)array();
                            }
                            $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_id'] = $injunction_item->id;
                            $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['observation_number'] = $injunction_item->observation_number;
                            $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['place_id'] = $injunction_item->place_id;
                            $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['place_title'] = $injunction_item->place1->title;
                            $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['worker_id'] = $injunction_item->worker_id;
                            $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['kind_document_id'] = $injunction_item->kind_document_id;
                            $checking_info['checking'][$checking_item->id]['kind_document_id'] = $injunction_item->kind_document_id;
                            $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['rtn_statistic_status'] = $injunction_item->rtn_statistic_status_id;

                            /******************************************** Последний статус проверки ********************************************/
                            $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_status'][$injunction_item->id]['injunction_status_id'] = $injunction_item->id;
                            $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_status'][$injunction_item->id]['worker_id'] = $injunction_item->worker_id;
                            $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_status'][$injunction_item->id]['status_id'] = $injunction_item->status_id;

                            /************************************ Все статусы проверок ****************************************/
                            foreach ($injunction_item->injunctionStatuses as $injunction_status) {
                                $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_status_all'][$injunction_status->id]['injunction_status_id'] = $injunction_status->id;
                                $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_status_all'][$injunction_status->id]['worker_id'] = $injunction_status->worker_id;
                                $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_status_all'][$injunction_status->id]['date_time'] = date('d.m.Y H:i', strtotime($injunction_status->date_time));
                                $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_status_all'][$injunction_status->id]['status_id'] = $injunction_status->status_id;
                            }

                            /******************************************** Нарушения ********************************************/
                            foreach ($injunction_item->injunctionViolations as $injunction_vioaltion_item) {
                                $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['injunction_violation_id'] = $injunction_vioaltion_item->id;
                                $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['reason_danger_motion_id'] = $injunction_vioaltion_item->reason_danger_motion_id;
                                if (isset($injunction_vioaltion_item->reasonDangerMotion->title)) {
                                    $reason_danger_motion_title = $injunction_vioaltion_item->reasonDangerMotion->title;
                                } else {
                                    $reason_danger_motion_title = null;
                                }
                                $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['reason_danger_motion_title'] = $reason_danger_motion_title;
                                $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['probability'] = $injunction_vioaltion_item->probability;
                                $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['dangerous'] = $injunction_vioaltion_item->gravity;
                                $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['correct_period'] = $injunction_vioaltion_item->correct_period;
                                $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['violation_id'] = $injunction_vioaltion_item->violation_id;
                                $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['violation_type_id'] = $injunction_vioaltion_item->violation->violation_type_id;
                                $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['violation_type_title'] = $injunction_vioaltion_item->violation->violationType->title;
                                if (empty($injunction_vioaltion_item->paragraphPb)) {
                                    $paragraph_pb = '';
                                    $paragraph_pb_text = '';
                                } else {
                                    $paragraph_pb = $injunction_vioaltion_item->paragraphPb->id;
                                    $paragraph_pb_text = $injunction_vioaltion_item->paragraphPb->text;

                                }
                                $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['document_id'] = $injunction_vioaltion_item->document_id;
                                $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['document_title'] = $injunction_vioaltion_item->document->title;
                                $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['paragraph_pb_id'] = $paragraph_pb;


                                /******************************************** Фотка нарушения ********************************************/
                                if ($injunction_vioaltion_item->injunctionImg !== null) {
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['injunction_img'][$injunction_vioaltion_item->injunctionImg->id]['injunction_img_id'] = $injunction_vioaltion_item->injunctionImg->id;
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['injunction_img'][$injunction_vioaltion_item->injunctionImg->id]['injunction_img_path'] = $injunction_vioaltion_item->injunctionImg->img_path;
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['injunction_img'][$injunction_vioaltion_item->injunctionImg->id]['injunction_img_name'] = basename($injunction_vioaltion_item->injunctionImg->img_path);
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['injunction_img'][$injunction_vioaltion_item->injunctionImg->id]['injunction_img_type'] = null;
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['injunction_img'][$injunction_vioaltion_item->injunctionImg->id]['injunction_img_flag_status'] = null;
                                } else {
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['injunction_img'] = (object)array();
                                }

                                $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['injunction_description'] = $injunction_vioaltion_item->violation->title;
                                $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['paragraph_injunction_description'] = $paragraph_pb_text;

                                /******************************************** Статусы нарушений ********************************************/
                                foreach ($injunction_vioaltion_item->injunctionViolationStatuses as $injunctionViolationStatus) {
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['injunction_violation_statuses'][$injunctionViolationStatus->id]['injunction_violation_status_id'] = $injunctionViolationStatus->status_id;
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['injunction_violation_statuses'][$injunctionViolationStatus->id]['injunction_violation_worker_id'] = $injunctionViolationStatus->worker_id;
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['injunction_violation_statuses'][$injunctionViolationStatus->id]['injunction_violation_date_time'] = $injunctionViolationStatus->date_time;
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['injunction_violation_statuses'][$injunctionViolationStatus->id]['injunction_violation_date_time_format'] = date('d.m.Y H:i', strtotime($injunctionViolationStatus->date_time));
                                }

                                /******************************************** Нарушители ********************************************/
                                foreach ($injunction_vioaltion_item->violators as $violator) {
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['violators'][$violator->worker_id]['worker_id'] = $violator->worker_id;
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['violators'][$violator->worker_id]['full_name'] = Assistant::GetFullName($violator->workerEmployee->first_name, $violator->workerEmployee->patronymic, $violator->workerEmployee->last_name);;
                                }


                                /******************************************** Корректирующие мероприятия ********************************************/
                                $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['correct_measures'] = array();
                                foreach ($injunction_vioaltion_item->correctMeasures as $correct_measures_item) {
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['correct_measures'][$correct_measures_item->id]['correct_measures_id'] = $correct_measures_item->id;
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['correct_measures'][$correct_measures_item->id]['operation_id'] = $correct_measures_item->operation_id;
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['correct_measures'][$correct_measures_item->id]['operation_description'] = $correct_measures_item->operation->title;
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['correct_measures'][$correct_measures_item->id]['correct_measures_description'] = $correct_measures_item->correct_measures_description;
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['correct_measures'][$correct_measures_item->id]['operation_unit'] = $correct_measures_item->operation->unit->short;
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['correct_measures'][$correct_measures_item->id]['operation_value'] = $correct_measures_item->correct_measures_value;
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['correct_measures'][$correct_measures_item->id]['worker_id'] = $correct_measures_item->worker_id;
                                    if ($correct_measures_item->date_time != null) {
                                        $date_time = $correct_measures_item->date_time;
                                        $date_time_format = date('d.m.Y H:i', strtotime($correct_measures_item->date_time));
                                    } else {
                                        $date_time = null;
                                        $date_time_format = null;
                                    }
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['correct_measures'][$correct_measures_item->id]['date_plan'] = $date_time;
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['correct_measures'][$correct_measures_item->id]['date_plan_format'] = $date_time_format;
                                }
                                if (count($checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['correct_measures']) == 0) {
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['correct_measures'] = (object)array();
                                }
                                $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['stop_pb'] = array();
                                /******************************************** Простои ********************************************/
                                foreach ($injunction_vioaltion_item->stopPbs as $stop_pb_item) {

                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['stop_pb'][$stop_pb_item->id]['active'] = true;
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['stop_pb'][$stop_pb_item->id]['stop_pb_id'] = $stop_pb_item->id;
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['stop_pb'][$stop_pb_item->id]['date_time_start'] = $stop_pb_item->date_time_start;
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['stop_pb'][$stop_pb_item->id]['date_time_start_format'] = date('d.m.Y H:i', strtotime($stop_pb_item->date_time_start));
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['stop_pb'][$stop_pb_item->id]['date_time_end'] = $stop_pb_item->date_time_end;
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['stop_pb'][$stop_pb_item->id]['date_time_end_format'] = date('d.m.Y H:i', strtotime($stop_pb_item->date_time_end));
                                    if ($stop_pb_item->date_time_end == null) {
                                        $until = true;
                                    } else {
                                        $until = false;
                                    }
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['stop_pb'][$stop_pb_item->id]['until_complete_flag'] = $until;
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['stop_pb'][$stop_pb_item->id]['equipment'] = array();
                                    foreach ($stop_pb_item->stopPbEquipments as $stopPbEquipment) {
                                        $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['stop_pb'][$stop_pb_item->id]['equipment'][$stopPbEquipment->equipment_id]['stop_pb_equipment_id'] = $stopPbEquipment->id;
                                        $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['stop_pb'][$stop_pb_item->id]['equipment'][$stopPbEquipment->equipment_id]['equipment_id'] = $stopPbEquipment->equipment_id;
                                        $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['stop_pb'][$stop_pb_item->id]['equipment'][$stopPbEquipment->equipment_id]['equipment_title'] = $stopPbEquipment->equipment->title;
                                        $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['stop_pb'][$stop_pb_item->id]['equipment'][$stopPbEquipment->equipment_id]['inventory_number'] = $stopPbEquipment->equipment->inventory_number;
                                    }
                                    if (empty($checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['stop_pb'][$stop_pb_item->id]['equipment'])) {
                                        $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['stop_pb'][$stop_pb_item->id]['equipment'] = (object)array();
                                    }
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['stop_pb'][$stop_pb_item->id]['place_id'] = $stop_pb_item->place_id;
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['stop_pb'][$stop_pb_item->id]['place_title'] = $stop_pb_item->place->title;
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['stop_pb'][$stop_pb_item->id]['kind_stop_pb'] = $stop_pb_item->kindStopPb->title;
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['stop_pb'][$stop_pb_item->id]['kind_duration'] = $stop_pb_item->kindDuration->title;
                                }
                                if (count($checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['stop_pb']) == 0) {
                                    $checking_info['checking'][$checking_item->id]['injunction'][$injunction_item->id]['injunction_violation'][$injunction_vioaltion_item->id]['stop_pb'] = (object)array();
                                }
                            }
                        }
                    }
                } else {
                    $warnings[] = 'GetCheckingData. Не найдено никаких данных';
                }
            } else {
                $status = 0;
                $errors[] = 'GetCheckingData. Данные получены не корректно';
            }
        } catch (Throwable $exception) {
            $warnings[] = 'GetCheckingData. Исключение';
            $status = 0;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'GetCheckingData. Метод завершил работу';
        $result = (object)$checking_info;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод ChaneStatusInjunction() - меняет статус предписания
     * @param null $data_post - JSON строк с идентификатором предписания, статуса на который необходимо сменить
     * @return array - стандартный массив выходных данных
     * @package frontend\controllers\industrial_safety
     *
     * @see
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Checking&method=ChangeStatusInjunction&subscribe=&data={"injunction_id":65,"status_id":60}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 21.06.2019 12:03
     */
    public static function ChangeStatusInjunction($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $session = Yii::$app->session;

        try {
            if ($data_post != NULL && $data_post != '') {
                $warnings[] = 'ChaneStatusInjunction. Данные успешно переданы';
                $warnings[] = 'ChaneStatusInjunction. Входной массив данных' . $data_post;
            } else {
                throw new Exception('ChaneStatusInjunction. Данные с фронта не получены');
            }
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'ChaneStatusInjunction. Декодировал входные параметры';

            if (
                property_exists($post_dec, 'injunction_id') &&
                property_exists($post_dec, 'status_id')
            )                                                                                                       // Проверяем наличие в нем нужных нам полей (идентификатор предписания)
            {
                $warnings[] = 'ChaneStatusInjunction.Данные с фронта получены';
                $injunction_id = $post_dec->injunction_id;
                $status_id = $post_dec->status_id;
            } else {
                throw new Exception('ChaneStatusInjunction. Переданы некорректные входные параметры');
            }
            $found_injunction = Injunction::findOne(['id' => $injunction_id]);                                                                                        //ищем предписание в базе данных
            if ($found_injunction)                                                                               //если предписание найдено
            {
                $found_injunction->status_id = $status_id;                                           //меняем статус предписания
                if ($found_injunction->save())                                                                   //если успешно сохранилось, тогда записать в предупреждения информацю о том что статус изменён
                {
                    $inj_status = new InjunctionStatus();
                    $inj_status->injunction_id = $injunction_id;
                    $inj_status->status_id = $status_id;
                    $inj_status->date_time = date('Y-m-d H:i:s', strtotime(AssistansBackend::GetDateNow()));
                    $inj_status->worker_id = $session['worker_id'];
                    if ($inj_status->save()) {
                        $found_injunction->refresh();
                        $warnings[] = 'ChaneStatusInjunction. Статус успешно изменён. Статуст предписания' . $found_injunction->id . ' = ' . $found_injunction->status_id;
                    } else {
                        $errors[] = $inj_status->errors;
                        throw new Exception('ChaneStatusInjunction. Ошибка при сохранении статуса предписания InjunctionStatus');
                    }
                } else {
                    $errors[] = $found_injunction->errors;
                    throw new Exception('ChaneStatusInjunction. Ошибка при сохранении нового статуса предписания');
                }
//                $inj_violation = InjunctionViolation::find()
//                    ->select(['id'])
//                    ->where(['injunction_id'=>$injunction_id])
//                    ->scalar();
//                if (!empty($inj_violation)) {
//                    $update_all_correct = CorrectMeasures::updateAll(['status_id' => $status_id], ['injunction_id' => $inj_violation]);
//                    if ($update_all_correct != 0) {
//                        $warnings[] = 'ChaneStatusInjunction. Статусы всех корректирующих мероприятий изменены';
//                    } else {
//                        throw new \Exception('ChaneStatusInjunction. Возникла ошибка при изменении статусов корректирующих меропритятий');
//                    }
//                }
            } else {
                throw new Exception('ChaneStatusInjunction. Предписание с таким идентификатором не найдено');
            }
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод ChangeStatuCorrectMeasures() - Метод смены статуса корректирущим мероприятиям
     * @param null $data_post - JSON c массиво корректирующих меропртиятий которым неоьходимо сменить статус и статус
     *                          который необходимо установить этим корректирующи меропиртиям
     * @return array - стандартный массив выходных данных
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Checking&method=ChangeStatuCorrectMeasures&subscribe=&data={"correct_measures":[114,115,116],"status_id":60}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.09.2019 11:14
     */
    public static function ChangeStatuCorrectMeasures($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $correct_measures = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = 'ChangeStatuCorrectMeasures. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('ChangeStatuCorrectMeasures. Не переданы входные параметры');
            }
            $warnings[] = 'ChangeStatuCorrectMeasures. Данные успешно переданы';
            $warnings[] = 'ChangeStatuCorrectMeasures. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'ChangeStatuCorrectMeasures. Декодировал входные параметры';
            if (!property_exists($post_dec, 'correct_measures') ||
                !property_exists($post_dec, 'status_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('ChangeStatuCorrectMeasures. Переданы некорректные входные параметры');
            }
            $warnings[] = 'ChangeStatuCorrectMeasures. Данные с фронта получены';
            $correct_measures = $post_dec->correct_measures;
            $status_id = $post_dec->status_id;
            $update_correct_measures_status = CorrectMeasures::updateAll(['status_id' => $status_id], ['in', 'id', $correct_measures]);
            if ($update_correct_measures_status != 0) {
                $warnings[] = 'ChangeStatuCorrectMeasures. Статусы корректирующих мероприятий успешно изменены';
            } else {
                throw new Exception('ChangeStatuCorrectMeasures. Ошибка при смене статусов корректирующихх мероприятий');
            }
            if (property_exists($post_dec, 'injunction_id')) {
                $injunction_id = $post_dec->injunction_id;
                $injunction = Injunction::findOne(['id' => $injunction_id]);
                if ($injunction) {
                    $injunction->status_id = $status_id;
                    if ($injunction->save()) {
                        $warnings[] = 'ChangeStatuCorrectMeasures. Статус наряда успешно изменён';
                    } else {
                        $errors[] = $injunction->errors;
                        throw new Exception('ChangeStatuCorrectMeasures. Ошибка при изменении статуса наряда');
                    }
                }
            }
        } catch (Throwable $exception) {
            $errors[] = 'ChangeStatuCorrectMeasures. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'ChangeStatuCorrectMeasures. Конец метода';
        $result = $correct_measures;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetListViolationType() - получает все типы нарушений
     * @return array - структура вида: [violation_type_id]
     *                                          violation_type_id:
     *                                          violation_type_title:
     *                                          kind_violation_title:
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Checking&method=GetListViolationType&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 21.06.2019 13:36
     */
    public static function GetListViolationType()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $violation_types = array();                                                                                              // Промежуточный результирующий массив
        try {
            $violation_types = ViolationType::find()
                ->select(['violation_type.id as violation_type_id',
                    'violation_type.title as violation_type_title',
                    'kind_violation.title as kind_violation_title'])
                ->innerjoin('kind_violation', 'violation_type.kind_violation_id = kind_violation.id')
                ->orderBy('violation_type.title')
                ->indexBy('violation_type_id')
                ->asArray()
                ->all();                                                                                                //получаем все типы нарушений
            if (!empty($violation_types))                                                                               //если массив с типами нарушений не пуст тогда записываем предупреждение о том что данные успешно получены
            {
                $warnings[] = 'ChaneStatusInjunction. Данные успешно получены.';
            } else                                                                                                        //иначе генерируем исключение
            {
                throw  new Exception('Данные не получены.');
            }

        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = 'ChaneStatusInjunction. Достигнут конец метода.';
        $result = $violation_types;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Название метода: GetCheckingType() - Метод получает список всех типов проверок
     * @return array - возращает структуру следующего вида:[checking_type_id]
     *                                                              checking_type_id:
     *                                                              checking_type_title:
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Checking&method=GetCheckingType&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.06.2019 15:02
     */
    public static function GetCheckingType()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $checking_types = array();                                                                                      //промежуточный массив
        try {
            $checking_types = CheckingType::find()
                ->select(['checking_type.id as checking_type_id', 'checking_type.title as checking_type_title'])
                ->orderBy('checking_type.title')
                ->indexBy('checking_type_id')
                ->asArray()
                ->all();                                                                                                //получаем все типы проверок идексированных по идентификатору типа проверки
            if (!empty($checking_types))                                                                                //если типы проверок не пусты тогда записываем предупреждение о том что данные успешно получены
            {
                $warnings[] = 'GetCheckingType. Данные успешно получены.';
            } else                                                                                                      //иначе генерируем исключение
            {
                throw  new Exception('Данные не получены.');
            }
        } catch (Throwable $exception) {
            $warnings[] = 'GetCheckingType. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $result = $checking_types;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Название метода: GetNewCheckingId()
     * Метод получения последнего идентификатор проверки, для формирования новой проверки
     *
     * @param null $data_post
     * @return array
     * @package frontend\controllers\industrial_safety
     * @example http://web.amicum/read-manager-amicum?controller=industrial_safety\Checking&method=GetNewCheckingId&subscribe=&data={}
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 19.06.2019 15:05
     * @since ver
     */
    public static function GetNewCheckingId($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $new_checking_id = -1;                                                                                         // Промежуточный результирующий массив
        try {
            $new_checking_id = Checking::find()->select('id AS checking_id')
                ->orderBy('id DESC')->limit(1)->asArray()->one();
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $result = $new_checking_id;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    // HandbookEmployeeController -> GetCompanyDepartment                 -   Метод получения списка участков

    /**
     * Название метода: GetWorkerByCompanyDepartment()
     * Метод получения списка работников и данных о них
     *
     * @param null $data_post
     * @return array
     * @package frontend\controllers\industrial_safety
     * @example http://web.amicum/read-manager-amicum?controller=industrial_safety\Checking&method=GetSortedByFullNameWorkers&subscribe=&data={"date":"2019-06-19"}
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 19.06.2019 16:20
     * @since ver
     */
    public static function GetSortedByFullNameWorkers($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $workers = array();                                                                                             // Промежуточный результирующий массив
        $workers_screens = array();                                                                                     // Промежуточный результирующий массив
        try {
            if ($data_post !== NULL && $data_post !== '') {
                $warnings[] = 'GetWorkerByCompanyDepartment. Данные успешно переданы';
                $warnings[] = 'GetWorkerByCompanyDepartment. Входной массив данных' . $data_post;
            } else {
                throw new Exception('GetWorkerByCompanyDepartment. Получена входная JSON строка');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'GetWorkerByCompanyDepartment. Декодировал входные параметры';
            if (
                property_exists($post_dec, 'date')                                                              // Дата на которую оформляется проверка
            )                                                                                                           // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'GetWorkerByCompanyDepartment.Данные с фронта получены';
            } else {
                throw new Exception('GetWorkerByCompanyDepartment. Переданы некорректные входные параметры');

            }
            $date = $post_dec->date;
            $warnings[] = 'GetWorkerByCompanyDepartment.Данные с фронта получены';
            $workers_info = Worker::find()                                                                              // Получаем основую информацию о работнике, ФИО и.т.д.
            ->select(["CONCAT(employee.last_name,' ',employee.first_name,' ',employee.patronymic) AS worker_fullname",
                'worker.id AS worker_id', 'worker.tabel_number AS worker_tabel_number',
                'position.title AS position_title'])
                ->joinWith('employee', false)
                ->joinWith('position', false)
                ->where(['<=', 'worker.date_start', $date])
                ->andWhere(['>=', 'worker.date_end', $date])
                ->orderBy('worker_fullname')
                ->asArray()
                ->all();
            $i = 1;
            foreach ($workers_info as $worker_info) {
                $workers[$i] = $worker_info;
                $i++;
            }

            $warnings[] = 'GetWorkerByCompanyDepartment. Фотографии работников получены';
            $workers_screens = ViewInitWorkerParameterHandbookValue::find()                                             // Делается через indexBy учитывая то, что актуальная фотография у сотрудника может быть только одна
            ->select(['worker_id', 'value AS path'])
                ->where(['parameter_id' => self::WORKER_SCREEN_PARAM,
                    'status_id' => self::STATUS_ACTIVE])
                ->asArray()
                ->indexBy('worker_id')
                ->all();
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $result = ['workers_info' => $workers, 'workers_screens' => $workers_screens];
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetPlacesList() - получение списка мест из справочника мест, всех кроме ППК ПАБ
     * @return array            - массив мест из справочника мест
     * @package frontend\controllers\industrial_safety
     * @example http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Checking&method=GetPlacesList&subscribe=&data=
     *
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 19.06.2019 18:04
     */
    public static function GetPlacesList()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("GetPlacesList");

        try {
            $log->addLog("Начало выполнения метода");

            $cache = Yii::$app->cache;
            $key = "GetPlacesList";
            $keyHash = "GetPlacesListHash";
            $places = $cache->get($key);
            if (!$places) {
                $log->addLog("Кеша не было, получаю данные из БД");
                $places = Place::find()
                    ->select(['place.id AS place_id', 'place.title AS place_title', 'place.mine_id as mine_id'])
                    ->indexBy('place_id')
                    ->asArray()
                    ->all();

                $hash = md5(json_encode($places));
                $cache->set($keyHash, $hash, 60 * 60 * 24);
                $cache->set($key, $places, 60 * 60 * 24);   // 60 * 60 * 24 = сутки
            } else {
                $log->addLog("Кеш был");
                $hash = $cache->get($keyHash);
            }
            $result['handbook'] = $places;
            $result['hash'] = $hash;
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * Название метода: SaveChecking()
     * Метод сохранения проверки.
     *
     * @param null $data_post - строка в формате JSON содержащая структуру для сохранения проверки. Структура описана выше
     * @return array                - выходной массив с данными сохраненной проверки в виду структуры описанной выше
     * @package frontend\controllers\industrial_safety
     * @example
     *              Входной массив со всеми коррктными данными: http://web.amicum/read-manager-amicum?controller=industrial_safety\Checking&method=SaveChecking&subscribe=&data={%22checking%22:{%22new_checking%22:{%22checking_id%22:%22new_checking%22,%22checking_title%22:%22%D0%9F%D1%80%D0%BE%D0%B2%D0%B5%D1%80%D0%BA%D0%B0%204%22,%22checking_type_id%22:1,%22date_time_start%22:%222019-06-10%2012:00:00%22,%22date_time_end%22:%222019-06-10%2015:00:00%22,%22company_department_id%22:20036912,%22crew_member%22:{%2214%22:{%22crew_member_id%22:14,%22worker_id%22:1093254,%22worker_type_id%22:1},%2215%22:{%22crew_member_id%22:15,%22worker_id%22:1093184,%22worker_type_id%22:2},%2216%22:{%22crew_member_id%22:16,%22worker_id%22:2034260,%22worker_type_id%22:2},%2217%22:{%22crew_member_id%22:17,%22worker_id%22:2034277,%22worker_type_id%22:3}},%22injunction%22:{%221%22:{%22attachments%22:{%221%22:{%22attachment_id%22:1}},%22injunction_id%22:1,%22place_id%22:6224,%22worker_id%22:1,%22kind_document_id%22:1,%22rtn_statistic_status%22:1}},%22injunction_status%22:{%221%22:{%22injunction_status_id%22:1,%22worker_id%22:2034277,%22date_time%22:%222019-06-15%2010:00:00%22,%22status_id%22:1}},%22injunction_status_all%22:{%221%22:{%22injunction_status_id%22:1,%22worker_id%22:2034277,%22date_time%22:%222019-06-15%2010:00:00%22,%22status_id%22:1},%222%22:{%22injunction_status_id%22:2,%22worker_id%22:2034277,%22date_time%22:%222019-06-10%2016:00:00%22,%22status_id%22:19},%223%22:{%22injunction_status_id%22:3,%22worker_id%22:2034277,%22date_time%22:%222019-06-10%2013:10:00%22,%22status_id%22:19},%224%22:{%22injunction_status_id%22:4,%22worker_id%22:2034277,%22date_time%22:%222019-06-10%2011:00:00%22,%22status_id%22:19}},%22injunction_violation%22:{%221%22:{%22injunction_violation_id%22:1,%22probability%22:3,%22gravity%22:3,%22correct_period%22:%222019-06-12%22,%22violation_id%22:1,%22paragraph_pb_id%22:1,%22injunction_img%22:{%221%22:{%22injunction_img_id%22:1,%22injunction_img_path%22:%22C:/Users/TestUser/Pictures/injunction%E2%84%9621.jpg%22},%222%22:{%22injunction_img_id%22:2,%22injunction_img_path%22:%22C:/img21.png%22}},%22injunction_description%22:%22%D0%9D%D0%B0%20%D0%BA%D0%BE%D0%BD%D1%86%D0%B5%20%D0%9F%D0%9E%D0%A2%20%D0%BD%D0%B5%20%D0%BF%D0%BE%D0%B4%D0%BA%D0%BB%D1%8E%D1%87%D1%91%D0%BD%20%D0%AD%D0%9A%D0%9C%22,%22paragraph_injunction_description%22:%22%D0%9F%D1%83%D0%BD%D0%BA%D1%82%20%E2%84%962%22,%22correct_measures%22:{%221%22:{%22correct_measures_id%22:1,%22operation_id%22:1,%22operation_description%22:%22%D0%92%D0%B5%D0%BD%D1%82%D0%B8%D0%BB%D1%8F%D1%86%D0%B8%D0%BE%D0%BD%D0%BD%D1%8B%D0%B9%20%D1%81%D1%82%D0%B0%D0%B2%22,%22operation_unit%22:4,%22operation_value%22:0,%22worker_id%22:2091763,%22date_plan%22:%222019-06-10%2010:00:00%22}},%22stop_pb%22:{%221%22:{%22stop_pb_id%22:1,%22date_time_start%22:%222019-06-10%2000:00:00%22,%22date_time_end%22:%222019-06-12%2000:00:00%22,%22equipment_id%22:2072,%22place_id%22:6224,%22kind_stop_pb%22:%22%D0%97%D0%B0%D0%B2%D0%B8%D1%81%D0%B8%D0%BC%D1%8B%D0%B9%22,%22kind_duration%22:%22%D0%94%D0%BE%20%D1%83%D1%81%D1%82%D1%80%D0%B0%D0%BD%D0%B5%D0%BD%D0%B8%D1%8F%20%D0%BD%D0%B0%20%D1%81%D1%80%D0%BE%D0%BA%20%D0%B4%D0%BE%202019-06-12%22}}}}}}}
     *              Массив у которого не переданы места: http://web.amicum/read-manager-amicum?controller=industrial_safety\Checking&method=SaveChecking&subscribe=&data={%22checking%22:{%22new_checking%22:{%22checking_id%22:%22new_checking%22,%22checking_title%22:%22%D0%9F%D1%80%D0%BE%D0%B2%D0%B5%D1%80%D0%BA%D0%B0%204%22,%22checking_type%22:1,%22date_time_start%22:%222019-06-10%2012:00:00%22,%22date_time_end%22:%222019-06-10%2015:00:00%22,%22company_department_id%22:20036912,%22crew_member%22:{%2214%22:{%22crew_member_id%22:14,%22worker_id%22:1093254,%22worker_type_id%22:1},%2215%22:{%22crew_member_id%22:15,%22worker_id%22:1093184,%22worker_type_id%22:2},%2216%22:{%22crew_member_id%22:16,%22worker_id%22:2034260,%22worker_type_id%22:2},%2217%22:{%22crew_member_id%22:17,%22worker_id%22:2034277,%22worker_type_id%22:3}},%22injunction%22:{},%22injunction_status%22:{%221%22:{%22injunction_status_id%22:1,%22worker_id%22:2034277,%22date_time%22:%222019-06-15%2010:00:00%22,%22status_id%22:1}},%22injunction_status_all%22:{%221%22:{%22injunction_status_id%22:1,%22worker_id%22:2034277,%22date_time%22:%222019-06-15%2010:00:00%22,%22status_id%22:1},%222%22:{%22injunction_status_id%22:2,%22worker_id%22:2034277,%22date_time%22:%222019-06-10%2016:00:00%22,%22status_id%22:19},%223%22:{%22injunction_status_id%22:3,%22worker_id%22:2034277,%22date_time%22:%222019-06-10%2013:10:00%22,%22status_id%22:19},%224%22:{%22injunction_status_id%22:4,%22worker_id%22:2034277,%22date_time%22:%222019-06-10%2011:00:00%22,%22status_id%22:19}},%22injunction_violation%22:{%221%22:{%22injunction_violation_id%22:1,%22probability%22:3,%22gravity%22:3,%22correct_period%22:%222019-06-12%22,%22violation_id%22:1,%22paragraph_pb_id%22:1,%22injunction_img%22:{%221%22:{%22injunction_img_id%22:1,%22injunction_img_path%22:%22C:/Users/TestUser/Pictures/injunction%E2%84%9621.jpg%22},%222%22:{%22injunction_img_id%22:2,%22injunction_img_path%22:%22C:/img21.png%22}},%22injunction_description%22:%22%D0%9D%D0%B0%20%D0%BA%D0%BE%D0%BD%D1%86%D0%B5%20%D0%9F%D0%9E%D0%A2%20%D0%BD%D0%B5%20%D0%BF%D0%BE%D0%B4%D0%BA%D0%BB%D1%8E%D1%87%D1%91%D0%BD%20%D0%AD%D0%9A%D0%9C%22,%22paragraph_injunction_description%22:%22%D0%9F%D1%83%D0%BD%D0%BA%D1%82%20%E2%84%962%22,%22correct_measures%22:{%221%22:{%22correct_measures_id%22:1,%22operation_id%22:1,%22operation_description%22:%22%D0%92%D0%B5%D0%BD%D1%82%D0%B8%D0%BB%D1%8F%D1%86%D0%B8%D0%BE%D0%BD%D0%BD%D1%8B%D0%B9%20%D1%81%D1%82%D0%B0%D0%B2%22,%22operation_unit%22:4,%22operation_value%22:0,%22worker_id%22:2091763,%22date_plan%22:%222019-06-10%2010:00:00%22}},%22stop_pb%22:{%221%22:{%22stop_pb_id%22:1,%22date_time_start%22:%222019-06-10%2000:00:00%22,%22date_time_end%22:%222019-06-12%2000:00:00%22,%22equipment_id%22:2072,%22place_id%22:6224,%22kind_stop_pb%22:%22%D0%97%D0%B0%D0%B2%D0%B8%D1%81%D0%B8%D0%BC%D1%8B%D0%B9%22,%22kind_duration%22:%22%D0%94%D0%BE%20%D1%83%D1%81%D1%82%D1%80%D0%B0%D0%BD%D0%B5%D0%BD%D0%B8%D1%8F%20%D0%BD%D0%B0%20%D1%81%D1%80%D0%BE%D0%BA%20%D0%B4%D0%BE%202019-06-12%22}}}}}}}
     *              Массив у которого участвующие: http://web.amicum/read-manager-amicum?controller=industrial_safety\Checking&method=SaveChecking&subscribe=&data={%22checking%22:{%22new_checking%22:{%22checking_id%22:%22new_checking%22,%22checking_title%22:%22%D0%9F%D1%80%D0%BE%D0%B2%D0%B5%D1%80%D0%BA%D0%B0%204%22,%22checking_type%22:1,%22date_time_start%22:%222019-06-10%2012:00:00%22,%22date_time_end%22:%222019-06-10%2015:00:00%22,%22company_department_id%22:20036912,%22crew_member%22:{},%22injunction%22:{%221%22:{%22attachments%22:{%221%22:{%22attachment_id%22:1}},%22injunction_id%22:1,%22place_id%22:6224,%22worker_id%22:1,%22kind_document_id%22:1,%22rtn_statistic_status%22:1}},%22injunction_status%22:{%221%22:{%22injunction_status_id%22:1,%22worker_id%22:2034277,%22date_time%22:%222019-06-15%2010:00:00%22,%22status_id%22:1}},%22injunction_status_all%22:{%221%22:{%22injunction_status_id%22:1,%22worker_id%22:2034277,%22date_time%22:%222019-06-15%2010:00:00%22,%22status_id%22:1},%222%22:{%22injunction_status_id%22:2,%22worker_id%22:2034277,%22date_time%22:%222019-06-10%2016:00:00%22,%22status_id%22:19},%223%22:{%22injunction_status_id%22:3,%22worker_id%22:2034277,%22date_time%22:%222019-06-10%2013:10:00%22,%22status_id%22:19},%224%22:{%22injunction_status_id%22:4,%22worker_id%22:2034277,%22date_time%22:%222019-06-10%2011:00:00%22,%22status_id%22:19}},%22injunction_violation%22:{%221%22:{%22injunction_violation_id%22:1,%22probability%22:3,%22gravity%22:3,%22correct_period%22:%222019-06-12%22,%22violation_id%22:1,%22paragraph_pb_id%22:1,%22injunction_img%22:{%221%22:{%22injunction_img_id%22:1,%22injunction_img_path%22:%22C:/Users/TestUser/Pictures/injunction%E2%84%9621.jpg%22},%222%22:{%22injunction_img_id%22:2,%22injunction_img_path%22:%22C:/img21.png%22}},%22injunction_description%22:%22%D0%9D%D0%B0%20%D0%BA%D0%BE%D0%BD%D1%86%D0%B5%20%D0%9F%D0%9E%D0%A2%20%D0%BD%D0%B5%20%D0%BF%D0%BE%D0%B4%D0%BA%D0%BB%D1%8E%D1%87%D1%91%D0%BD%20%D0%AD%D0%9A%D0%9C%22,%22paragraph_injunction_description%22:%22%D0%9F%D1%83%D0%BD%D0%BA%D1%82%20%E2%84%962%22,%22correct_measures%22:{%221%22:{%22correct_measures_id%22:1,%22operation_id%22:1,%22operation_description%22:%22%D0%92%D0%B5%D0%BD%D1%82%D0%B8%D0%BB%D1%8F%D1%86%D0%B8%D0%BE%D0%BD%D0%BD%D1%8B%D0%B9%20%D1%81%D1%82%D0%B0%D0%B2%22,%22operation_unit%22:4,%22operation_value%22:0,%22worker_id%22:2091763,%22date_plan%22:%222019-06-10%2010:00:00%22}},%22stop_pb%22:{%221%22:{%22stop_pb_id%22:1,%22date_time_start%22:%222019-06-10%2000:00:00%22,%22date_time_end%22:%222019-06-12%2000:00:00%22,%22equipment_id%22:2072,%22place_id%22:6224,%22kind_stop_pb%22:%22%D0%97%D0%B0%D0%B2%D0%B8%D1%81%D0%B8%D0%BC%D1%8B%D0%B9%22,%22kind_duration%22:%22%D0%94%D0%BE%20%D1%83%D1%81%D1%82%D1%80%D0%B0%D0%BD%D0%B5%D0%BD%D0%B8%D1%8F%20%D0%BD%D0%B0%20%D1%81%D1%80%D0%BE%D0%BA%20%D0%B4%D0%BE%202019-06-12%22}}}}}}}
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 20.06.2019 8:59
     * @since ver
     */
    public static function SaveChecking($data_post = NULL)
    {
        $log = new LogAmicumFront("SaveChecking", true);
        $created_checking = NULL;

        try {
            $log->addLog("Начал выполнять метод");

            if ($data_post == NULL or $data_post == '') {                                                               // Проверяем передана ли входная JSON строка
                throw new Exception('Не получена входная JSON строка');
            }
            $post_dec = json_decode($data_post);

            if (
                !property_exists($post_dec, 'checking') or
                !property_exists($post_dec->checking, 'new_checking') or
                !property_exists($post_dec->checking, 'kind_document_id')
            ) {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $log->addLog("Начал обработку");

            $post_array = ArrayHelper::toArray($post_dec->checking->new_checking);

            $log->addLog("Создаем новую проверку");
            /******************** СОХРАНЯЕМ НОВУЮ ПРОВЕРКУ ********************/
            $new_checking = new Checking();                                                                         // Создаем экземпляр новой проверки
            $new_checking->scenario = Checking::SCENARIO_DEFAULT;                                                   // Задаем конкретный сценарий
            $post_array['title'] = $post_array['checking_title'];

            $new_checking->attributes = $post_array;

            $new_checking->kind_document_id = $post_dec->checking->kind_document_id;

            if (!$new_checking->save()) {                                             // Проверяем данные по правилам описанным в модели и выполняем сохранение в случаи успешной валидации
                $log->addData($new_checking->errors, '$new_checking->errors', __LINE__);
                throw new Exception('Во время добавления проверки возникло исключение.');   // Проверка не удалась кидаем исключение
            }

            $new_checking_id = $new_checking->id;

            /******************** ДОБАВЛЯЕМ СПИСОК СОТРУДНИКОВ УЧАСТВУЮЩИХ В ПРОВЕРКИ ********************/
            foreach ($post_array['crew_member'] as $member) {                                                       // Формируем массив на добавление
                $crew_members[] = [$member['worker_id'], $member['worker_type_id'], $new_checking_id];
                if ($member['worker_type_id'] == 1) {
                    $auditors[] = $member['worker_id'];
                }
            }

            if (!isset($crew_members)) {
                throw new Exception('Не передан ни участвующий в оформлении проверки');
            }

            Yii::$app->db->createCommand()->batchInsert('checking_worker_type', ['worker_id', 'worker_type_id', 'checking_id'], $crew_members)->execute();

            /******************** ДОБАВЛЯЕМ СПИСОК МЕСТ ДЛЯ ПРОВЕРКИ ********************/
            foreach ($post_array['injunction'] as $injunction) {                                                    // Формируем массив на добавление
                $checking_places[] = [$new_checking_id, $injunction['place_id']];
                $places[] = $injunction['place_id'];
            }

            if (!isset($checking_places)) {
                throw new Exception('Не передан ни одно место проверки');
            }

            Yii::$app->db->createCommand()->batchInsert('checking_place', ['checking_id', 'place_id'], $checking_places)->execute();


            $post_array['checking_id'] = $new_checking_id;
            $post_array['date_time_start'] = date('d.m.Y H:i', strtotime($post_array['date_time_start']));
            $created_checking['checking'] = [$new_checking_id => $post_array];

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
            $post_array = NULL;
        }

        return array_merge(['Items' => $created_checking], $log->getLogAll());
    }

    /**
     * Метод GetWorkerScreen()          - метод получения фотографии сотрудника
     * @param null $data_post - идентификатор сотрудника
     * @return array                    - массив с данными работников
     * @package frontend\controllers\industrial_safety
     * @example Корректный данные: http://web.amicum/read-manager-amicum?controller=industrial_safety\Checking&method=GetWorkerScreen&subscribe=&data={%22worker_id%22:2080508}
     *          Данные не переданы: http://web.amicum/read-manager-amicum?controller=industrial_safety\Checking&method=GetWorkerScreen&subscribe=&data={}
     *
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 08.07.2019 9:43
     */
    public static function GetWorkerScreen($data_post = NULL)
    {
        $status = 1;                                                                                                        // Флаг успешного выполнения метода
        $warnings = array();                                                                                                // Массив предупреждений
        $errors = array();                                                                                                  // Массив ошибок
        $worker_screen = array();                                                                                   // Промежуточный результирующий массив
        try {
            if ($data_post !== NULL && $data_post !== '')                                                                   // Проверяем передана ли входная JSON строка
            {
                $warnings[] = 'GetWorkerScreen. Данные успешно переданы';
                $warnings[] = 'GetWorkerScreen. Входной массив данных' . $data_post;
            } else {
                throw new Exception('GetWorkerScreen. Не получена входная JSON строка');
            }
            $post_dec = json_decode($data_post);                                                                            // Декодируем входной массив данных
            $warnings[] = 'GetWorkerScreen. Декодировал входные параметры';
            if (property_exists($post_dec, 'worker_id'))                                                             // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'GetWorkerScreen. Данные с фронта получены';
            } else {
                throw new Exception('GetWorkerScreen. Переданы некорректные входные параметры');
            }

            /************************************************* ТЕЛО МЕТОДА *************************************************/

            $worker_id = $post_dec->worker_id;
            $handbook_values = ViewInitWorkerParameterHandbookValue::find()                                             // Получение значений справочных параметров, фотография, проф.заболевание
            ->select([
                'value',
                'parameter_id'
            ])
                ->where(['worker_id' => $worker_id, 'status_id' => 1])
                ->andWhere('parameter_id = 3')
                ->limit(1)
                ->one();
            if ($handbook_values) {
                $worker_screen = $handbook_values->value;
            } else {
                $worker_screen = null;
            }
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $result = $worker_screen;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод - SaveInjunction() - сохранение предписания
     *  checking: {
     *      [checking_id]: {
     *          checking_id: - обязательный параметр
     *          checking_title: - обязательный параметр
     *          checking_type:
     *          date_time_start:
     *          date_time_end:
     *          company_department_id: - обязательный параметр
     *          crew_member: {}
     *          injunction: {
     *              [injunction_id]: {
     *                  injunction_id:
     *                  place_id:
     *                  worker_id:
     *                  kind_document_id:
     *                  rtn_statistic_status:
     *                  injunction_description:
     *                  injunction_status: {
     *                      [injunction_status_id]: {
     *                          injunction_status_id:
     *                          worker_id:
     *                          date_time:
     *                          status_id:
     *                      }
     *                  }
     *                  injunction_status_all: {
     *                      [injunction_status_id]: {
     *                          injunction_status_id:
     *                          worker_id:
     *                          date_time:
     *                          status_id:
     *                      }
     *                      ...
     *                  }
     *                  injunction_violation: {
     *                      [injunction_violation_id]: {
     *                          injunction_violation_id:
     *                          probability:
     *                          gravity:
     *                          correct_period:
     *                          violation_id:
     *                          paragraph_pb:
     *                          document_id:
     *                          violation_type_id:
     *                          injunction_img: {
     *                              [injunction_img_id]: {
     *                                  injunction_img_id:
     *                                  injunction_img_path:
     *                              }
     *                              ...
     *                          }
     *                          injunction_description:
     *                          paragraph_injunction_description:
     *                          correct_measures: {
     *                              [correct_measures_id]: {
     *                                  correct_measures_id:
     *                                  correct_measures_description:
     *                                  operation_id:
     *                                  operation_description:
     *                                  operation_unit:
     *                                  operation_value:
     *                                  worker_id:
     *                                  date_plan:
     *                              }
     *                          }
     *                          stop_pb: {
     *                              [stop_pb_id]: {
     *                                  stop_pb_id:
     *                                  date_time_start:
     *                                  date_time_end:
     *                                  equipment_id:
     *                                  place_id:
     *                                  kind_stop_pb:
     *                                  kind_duration:
     *                              }
     *                          }
     *                      }
     *                  }
     *              }
     *          }
     *      }
     *  }
     * @param null $data_post - данные проверки согласно структуре описанной выше
     * @return array                - идентификатор нового предписания
     * @package frontend\controllers\industrial_safety
     * @example Корректные данные: http://web.amicum/read-manager-amicum?controller=industrial_safety\Checking&method=SaveInjunction&subscribe=&data={%22checking%22:{%229%22:{%22checking_id%22:%229%22,%22checking_title%22:%22%D0%9F%D1%80%D0%BE%D0%B2%D0%B5%D1%80%D0%BA%D0%B0%204%22,%22checking_type%22:1,%22date_time_start%22:%222019-06-10%2012:00:00%22,%22date_time_end%22:%222019-06-10%2015:00:00%22,%22company_department_id%22:20036912,%22crew_member%22:{},%22injunction%22:{%221%22:{%22attachments%22:{%221%22:{%22attachment_id%22:1}},%22injunction_id%22:1,%22place_id%22:6224,%22worker_id%22:1091021,%22kind_document_id%22:1,%22rtn_statistic_status%22:1,%22injunction_description%22:%22%D0%9D%D0%B0%20%D0%BA%D0%BE%D0%BD%D1%86%D0%B5%20%D0%9F%D0%9E%D0%A2%20%D0%BD%D0%B5%20%D0%BF%D0%BE%D0%B4%D0%BA%D0%BB%D1%8E%D1%87%D0%B5%D0%BD%20%D0%AD%D0%9A%D0%9D%22,%22injunction_status%22:{%221%22:{%22injunction_status_id%22:1,%22worker_id%22:2034277,%22date_time%22:%222019-06-15%2010:00:00%22,%22status_id%22:1}},%22injunction_status_all%22:{%221%22:{%22injunction_status_id%22:1,%22worker_id%22:2034277,%22date_time%22:%222019-06-15%2010:00:00%22,%22status_id%22:1},%222%22:{%22injunction_status_id%22:2,%22worker_id%22:2034277,%22date_time%22:%222019-06-10%2016:00:00%22,%22status_id%22:19},%223%22:{%22injunction_status_id%22:3,%22worker_id%22:2034277,%22date_time%22:%222019-06-10%2013:10:00%22,%22status_id%22:19},%224%22:{%22injunction_status_id%22:4,%22worker_id%22:2034277,%22date_time%22:%222019-06-10%2011:00:00%22,%22status_id%22:19}},%22injunction_violation%22:{%221%22:{%22injunction_violation_id%22:1,%22probability%22:3,%22gravity%22:3,%22correct_period%22:%222019-06-12%22,%22violation_id%22:1,%22paragraph_pb%22:%22%D0%9F%D1%83%D0%BD%D0%BA%D1%82%20%E2%84%961%22,%22document_id%22:70,%22violation_type_id%22:1,%22injunction_img%22:{%221%22:{%22injunction_img_id%22:1,%22injunction_img_path%22:%22C:/Users/TestUser/Pictures/injunction%E2%84%9621.jpg%22},%222%22:{%22injunction_img_id%22:2,%22injunction_img_path%22:%22C:/img21.png%22}},%22injunction_description%22:%22%D0%9D%D0%B0%20%D0%BA%D0%BE%D0%BD%D1%86%D0%B5%20%D0%9F%D0%9E%D0%A2%20%D0%BD%D0%B5%20%D0%BF%D0%BE%D0%B4%D0%BA%D0%BB%D1%8E%D1%87%D1%91%D0%BD%20%D0%AD%D0%9A%D0%9C%22,%22paragraph_injunction_description%22:%22%D0%9F%D1%83%D0%BD%D0%BA%D1%82%20%E2%84%962%22,%22correct_measures%22:{%221%22:{%22correct_measures_id%22:1,%22operation_id%22:1,%22operation_description%22:%22%D0%92%D0%B5%D0%BD%D1%82%D0%B8%D0%BB%D1%8F%D1%86%D0%B8%D0%BE%D0%BD%D0%BD%D1%8B%D0%B9%20%D1%81%D1%82%D0%B0%D0%B2%22,%22operation_unit%22:4,%22operation_value%22:0,%22worker_id%22:2091763,%22date_plan%22:%222019-06-10%2010:00:00%22}},%22stop_pb%22:{%221%22:{%22stop_pb_id%22:1,%22date_time_start%22:%222019-06-10%2000:00:00%22,%22date_time_end%22:%222019-06-12%2000:00:00%22,%22equipment_id%22:2072,%22place_id%22:6224,%22kind_stop_pb%22:%22%D0%97%D0%B0%D0%B2%D0%B8%D1%81%D0%B8%D0%BC%D1%8B%D0%B9%22,%22kind_duration%22:%22%D0%94%D0%BE%20%D1%83%D1%81%D1%82%D1%80%D0%B0%D0%BD%D0%B5%D0%BD%D0%B8%D1%8F%20%D0%BD%D0%B0%20%D1%81%D1%80%D0%BE%D0%BA%20%D0%B4%D0%BE%202019-06-12%22}}}}}}}}}
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 21.06.2019 13:15
     * @since ver
     */
    public static function SaveInjunction($data_post = NULL)
    {
        $log = new LogAmicumFront("SaveInjunction");
        $result = null;
//        $date_post = '{"checking":{"255":{"checking_id":255,"checking_title":"Акт проверки от 2019-09-10 17:45","checking_type":2,"date_time_start":"2019-09-10 15:29:00","date_time_end":"2019-09-10 15:29:00","company_department_id":802,"crew_member":{"536":{"crew_member_id":536,"worker_id":70012751,"worker_type_id":1},"537":{"crew_member_id":537,"worker_id":70011263,"worker_type_id":2}},"injunction":{"184":{"injunction_id":184,"place_id":139663,"worker_id":70012751,"kind_document_id":1,"rtn_statistic_status":56,"injunction_status":{"184":{"injunction_status_id":184,"worker_id":70012751,"status_id":57}},"injunction_status_all":{"187":{"injunction_status_id":187,"worker_id":1801,"date_time":"2019-09-10 15:33:09","status_id":57}},"injunction_violation":{"163":{"injunction_violation_id":163,"reason_danger_motion_id":null,"probability":4,"dangerous":5,"correct_period":null,"violation_id":176,"violation_type_id":39,"document_id":573,"document_title":"Приказ о не сертифицированном инструменте","paragraph_pb_id":168,"injunction_img":{"82":{"injunction_img_id":82,"injunction_img_path":"/img/injunction/what_10-09-2019 15-33.PNG"}},"injunction_description":null,"paragraph_injunction_description":"Пункт 20","correct_measures":{"122":{"correct_measures_id":122,"operation_id":122,"operation_description":"Подрывка почвы","operation_unit":81,"correct_measures_value":55555,"worker_id":null,"date_plan":"2019-09-10 20:00:00"}},"stop_pb":{"70":{"active":true,"stop_pb_id":70,"date_time_start":"2019-09-10 00:00:00","date_time_end":"2019-09-20 00:00:00","until_complete_flag":false,"equipments":{"139271":{"stop_pb_equipment_id":1,"equipment_id":139271},"139737":{"stop_pb_equipment_id":2,"equipment_id":139737},"139742":{"stop_pb_equipment_id":3,"equipment_id":139742}},"place_id":139663,"kind_stop_pb":"Административная приостановка работ","kind_duration":"До устранения"}}}}}}}}}';
        try {
            if ($data_post == NULL or $data_post == '') {
                throw new Exception('Не получена входная JSON строка');
            }

            $post_dec = json_decode($data_post);

            $log->addLog("Декодировал входные параметры'");

            if (!property_exists($post_dec, 'checking')) {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $post_checking_array = ArrayHelper::toArray($post_dec->checking);
            $post_checking = array_shift($post_checking_array);

            $company_department_id_post = $post_checking['company_department_id'];
            $checking_id = $post_checking['checking_id'];
            $checking_title = $post_checking['checking_title'];

            /******************** РЕДАКТИРУЕМ НАЗВАНИЕ ПРОВЕРКИ ********************/
            $checking = Checking::findOne(['id' => $checking_id]);
            if (!$checking) {
                throw new Exception('Данной проверки не существует');
            }

            $checking->title = $checking_title;
            if (!$checking->save()) {
                $log->addData($checking->errors, '$checking->errors', __LINE__);
                throw new Exception('Во время добавления наименования проверки произошла ошибка модель Checking');
            }

            $response = InjunctionController::AddInjunction($post_checking);                                            // Вызываем метод базового контроллера для добавления предписания
            $log->addLogAll($response);
            if (!$response['status']) {
                throw new Exception("Ошибка сохранения предписания");
            }

            $log->addLog("Все данные успешно добавлены, выполняется коммит изменений'");

            $json_get_checking = '{"company_department_id":"' . $company_department_id_post . '","checking_id":' . $checking_id . '}';

            $response = self::GetCheckingData($json_get_checking);
            $log->addLogAll($response);

            $result = $response['Items'];

        } catch (Exception $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод GetExcelData()             - метод выгрузки данных из Excel в MySQL
     * @param null $data_post - входные данные не требуются(универсальность не требуется), так как метод рассматривается для разового использования
     * @return array                    - стандартный массив с данными по предупреждениям, ошибкам и статусом
     * @package frontend\controllers\industrial_safety
     * @example http://web.amicum/read-manager-amicum?controller=industrial_safety\Checking&method=GetExcelData&subscribe=&data=
     *
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 11.07.2019 9:35
     */
    public static function GetExcelData($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $excel_data = array();                                                                                          // Промежуточный результирующий массив
        try {
            $file_name = "C:\local_webserver\Справочник направлений нарушений.ods";
            $excel_data = \PHPExcel_IOFactory::load($file_name);
            $start = 2;                                                                                                 // Данные начинаются со 2 строки
            $end = 103;                                                                                                 // Данные заканчиваются на 687 строке
            for ($i = $start; $i <= $end; $i++) {
                $parent_id = $excel_data->getActiveSheet()->getCell('C' . $i)->getValue();
                $title = $excel_data->getActiveSheet()->getCell('B' . $i)->getValue();
                $new_violation_type = new ViolationType();
                $new_violation_type->title = $title;
                $new_violation_type->kind_violation_id = $parent_id;
                $new_violation_type->save();
            }
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $result = $excel_data;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetDocumentsGroup()
     * @param null $data_post - входная информация не требуется
     * @return array                - массив со сгруппированной информацией по документам
     * @package frontend\controllers\industrial_safety
     * @example http://web.amicum/read-manager-amicum?controller=industrial_safety\Checking&method=GetDocumentsGroup&subscribe=&data=
     *
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 11.07.2019 14:42
     */
    public static function GetDocumentsGroup($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        try {
            $warnings[] = 'GetDocumentsGroup. Получение данных по документам из БД';
            $documents = Document::find()                                                                               // Получаем информацию по документам
            ->limit(1000)
                ->orderBy(['parent_document_id' => SORT_ASC])
                ->indexBy('id')
                ->asArray()
                ->all();

            /******************** Группируем документы, по вышестоящим ********************/
            $warnings[] = 'GetDocumentsGroup. Группируем документы';
            foreach ($documents as $document)                                                                           // Перебор документов, с целью их группировки
            {
                $current_document = $document;                                                                          // Запоминаем текущий документ
                while ($current_document['parent_document_id'] != 0)                                                     // Спускаемся по вложенностям до тех пор пока не дойдем до самого низа
                {
                    $documents[$current_document['parent_document_id']]['lower_documents'][$current_document['id']] = $current_document;    // Записываем текущий документ
                    $current_document = $documents[$current_document['parent_document_id']];                            // Уходим вниз по вложенности

                }
            }
            $warnings[] = 'GetDocumentsGroup. Группировка документов закончилась, удаляются лишние документы';

            /******************** Убираем лишние данные, у которых родительский документ задан ********************/

            foreach ($documents as $document)                                                                           // Убираем лишние данные, у которых родительский документ != 0
            {
                if ($document['parent_document_id'] != 0)                                                                // Если идентификатор родительского документа != 0
                {
                    unset($documents[$document['id']]);                                                                 // Удаляем из массива данный документ
                }
            }

            $warnings[] = 'GetDocumentsGroup. Лишние документы удалены из массива. Метод завершил работу';
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $result = $documents;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    /**
     * Метод GetViolationTypes()    - получение списка направлений нарущений сгруппированных по видам нарушений
     * @param null $data_post - не требуются входные параметры
     * @return array                - список направлений нарушений
     * @package frontend\controllers\industrial_safety
     * @example http://web.amicum/read-manager-amicum?controller=industrial_safety\Checking&method=GetViolationTypes&subscribe=&data=
     *
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 12.07.2019 10:16
     */
    public static function GetViolationTypes($data_post = NULL)
    {
        $status = 1;                                                                                                        // Флаг успешного выполнения метода
        $warnings = array();                                                                                                // Массив предупреждений
        $errors = array();                                                                                                  // Массив ошибок
        $violation_types = array();                                                                                   // Промежуточный результирующий массив
        try {
            $violation_types = KindViolation::find()
                ->innerJoinWith('violationTypes')
                ->asArray()
                ->all();
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $result = $violation_types;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetUnitList() - получение справочника едениц измерения
     *
     * @param null $data_post - массив пустой, но требуется в read manager
     * @return array            - массив едениц измерений из справочника Единицы измерения
     * @package frontend\controllers\industrial_safety
     * @example http://web.amicum/read-manager-amicum?controller=industrial_safety\Checking&method=GetUnitList&subscribe=&data=
     *
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * ///Created date: on 16.07.2019 16:00
     */
    public static function GetUnitList($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $unit = array();                                                                                              // Промежуточный результирующий массив
        try {
            $unit = Unit::find()                                                                                     //
            ->select(['id', 'title', 'short AS short_title'])
                ->orderBy('title')
                ->indexBy('id')
                ->asArray()
                ->all();
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $result = $unit;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetArchiveInjunction() - получение данных для архива предписаний
     * @param null $data_post - JSON с датой от начала (от какого числа хотим увидеть архив предписаний), дата окончания
     *                          (да какого числа хотим увидеть архив предписаний)
     * @return array - массив со следующей структурой: [inunction_violation_id]
     *                                                              checking_id:
     *                                                              company_department_id:
     *                                                              inunction_violation_id:
     *                                                              inunction_id:
     *                                                              date_first_status:
     *                                                              place_id:
     *                                                              responsible_worker_id:
     *                                                              direction:
     *                                                              [auditors]
     *                                                                    [worker_id]
     *                                                                          worker_id:
     *                                                               company_department_id:
     *                                                               rtn_statistic_status_id:
     *                                                               status_id:
     *
     * @throws Exception
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Checking&method=GetArchiveInjunction&subscribe=&data={"date_time_start":"2019-06-17","date_time_end":"2019-07-17"}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 22.07.2019 08:20
     * @package frontend\controllers\industrial_safety
     *
     */
    public static function GetArchiveInjunction($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $archive_injunction = array();                                                                                         // Промежуточный результирующий массив
//        $auditor = array();
        $directions = array();
//        $kind_documents = array();
        $method_name = 'GetArchiveInjunction';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('GetArchiveInjunction. Данные с фронта не получены');
            }
            $warnings[] = 'GetArchiveInjunction. Данные успешно переданы';
            $warnings[] = 'GetArchiveInjunction. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GetArchiveInjunction. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'date_time_start') ||
                !property_exists($post_dec, 'date_time_end') ||
                !property_exists($post_dec, 'company_department_id')
            )                                                                                                       // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('GetArchiveInjunction. Переданы некорректные входные параметры');
            }
            $warnings[] = 'GetArchiveInjunction.Данные с фронта получены';
            $date_time_start = date('Y-m-d', strtotime($post_dec->date_time_start));
            $date_time_end = date('Y-m-d', strtotime($post_dec->date_time_end));
            $company_department_id = $post_dec->company_department_id;

            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $warnings[] = $response['warnings'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . '. Ошибка при получении вложенных участков');
            }
            unset($response);

            $warnings[] = 'GetArchiveInjunction. Начало метода.';
            $warnings[] = 'GetArchiveInjunction. Поиск предписание нарушения.';
            /**
             * Виды документов которые необходимо выгрузить
             */
            $kind_documents = [1, 3];
            $found_data_injunction = Checking::find()
                ->joinWith('companyDepartment.company')
                ->joinWith('checkingWorkerTypes.worker.employee')
                ->joinWith('checkingWorkerTypes.worker.position')
                ->joinWith('injunctions.place')
                ->joinWith('injunctions.firstInjunctionStatuses')
                ->joinWith('injunctions.injunctionViolations.violation.violationType.kindViolation')
                ->where(['in', 'injunction.kind_document_id', $kind_documents])
                ->andWhere(['>=', 'checking.date_time_start', $date_time_start . ' 00:00:00'])
                ->andWhere(['<=', 'checking.date_time_start', $date_time_end . ' 23:59:59'])
                ->andWhere(['in', 'checking.company_department_id', $company_departments])
                ->all();
            $found_worker = PlaceCompanyDepartment::find()
                ->select(['worker.id', 'CONCAT(`employee`.`last_name`," ",
                                `employee`.`first_name`," ",`employee`.`patronymic`) as full_name',
                    'position.title as position_title', 'worker.tabel_number as worker_tabel_number', 'place_company_department.place_id as place_id'])
                ->leftJoin('worker', 'worker.company_department_id = place_company_department.company_department_id')
                ->leftJoin('employee', 'employee.id = worker.employee_id')
                ->leftJoin('position', 'position.id = worker.position_id')
                ->leftJoin('worker_object', 'worker.id = worker_object.worker_id')
                ->where(['worker_object.role_id' => self::ROLE_CHIEF_DEPARTMENT])
                ->limit(50000)
                ->indexBy('place_id')
                ->asArray()
                ->all();
            if ($found_data_injunction) {
                foreach ($found_data_injunction as $checking) {
                    $auditor = null;
                    $resposible = null;
                    $checking_id = $checking->id;
                    $company_department_id = $checking->company_department_id;
                    $company_title = $checking->companyDepartment->company->title;
                    foreach ($checking->checkingWorkerTypes as $checkingWorkerType) {
                        if ($checkingWorkerType->worker_type_id == self::WORKER_TYPE_AUDITOR) {
                            $auditor['worker_id'] = $checkingWorkerType->worker->id;
                            $full_name = "{$checkingWorkerType->worker->employee->last_name} {$checkingWorkerType->worker->employee->first_name} {$checkingWorkerType->worker->employee->patronymic}";
                            $auditor['worker_full_name'] = $full_name;
                            unset($full_name);
                            $auditor['worker_position_title'] = $checkingWorkerType->worker->position->title;
                            $auditor['worker_staff_number'] = $checkingWorkerType->worker->tabel_number;
                        } elseif ($checkingWorkerType->worker_type_id == self::WORKER_TYPE_RESPONSIBLE) {
                            $resposible['worker_id'] = $checkingWorkerType->worker->id;
                            $full_name = "{$checkingWorkerType->worker->employee->last_name} {$checkingWorkerType->worker->employee->first_name} {$checkingWorkerType->worker->employee->patronymic}";
                            $resposible['worker_full_name'] = $full_name;
                            unset($full_name);
                            $resposible['worker_position_title'] = $checkingWorkerType->worker->position->title;
                            $resposible['worker_staff_number'] = $checkingWorkerType->worker->tabel_number;
                        }
                    }
                    foreach ($checking->injunctions as $injunction) {
                        $injunction_id = $injunction->id;
                        $place_title = isset($injunction->place->title) ? $injunction->place->title : 'Нет места в справочнике';
                        $place_id = $injunction->place_id;
                        $inj_status_id = $injunction->status_id;
                        $inj_rtn_status_id = $injunction->rtn_statistic_status_id;
                        foreach ($injunction->injunctionViolations as $injunctionViolation) {
                            if (isset($injunction->firstInjunctionStatuses->date_time)) {
                                $date_first = date('d.m.Y H:i:s', strtotime($injunction->firstInjunctionStatuses->date_time));
                                $date_first_format = date('d.m.Y', strtotime($date_first));
                            } else {
                                $date_first = date('d.m.Y H:i:s', strtotime($checking->date_time_start));
                                $date_first_format = date('d.m.Y', strtotime($date_first));
                            }
                            $ppk_id_instruct = $checking->instruct_id;
                            if ($ppk_id_instruct) {
                                $archive_checking[$injunction_id]['ppk_id'] = $ppk_id_instruct;
                            } else {
                                $archive_checking[$injunction_id]['ppk_id'] = "";
                            }
                            $archive_injunction[$injunction_id]['checking_id'] = $checking_id;
                            $archive_injunction[$injunction_id]['company_department_id'] = $company_department_id;
                            $archive_injunction[$injunction_id]['injunction_violation_id'] = $injunctionViolation->id;
                            $archive_injunction[$injunction_id]['injunction_id'] = $injunction_id;
                            $archive_injunction[$injunction_id]['date_time'] = $date_first;
                            $archive_injunction[$injunction_id]['date_time_formated'] = $date_first_format;
                            $archive_injunction[$injunction_id]['place_title'] = $place_title;
                            if (empty($resposible)) {
                                if (!empty($found_worker[$place_id]['id'])) {
                                    $resposible ['worker_id'] = $found_worker[$place_id]['id'];
                                    $resposible ['worker_full_name'] = $found_worker[$place_id]['full_name'];
                                    $resposible ['worker_position_title'] = $found_worker[$place_id]['position_title'];
                                    $resposible ['worker_staff_number'] = $found_worker[$place_id]['worker_tabel_number'];
                                } else {
                                    $resposible ['worker_id'] = null;
                                    $resposible ['worker_full_name'] = null;
                                    $resposible ['worker_position_title'] = null;
                                    $resposible ['worker_staff_number'] = null;
                                }
                            }
                            $archive_injunction[$injunction_id]['responsible_worker'] = $resposible;
                            $directions[] = $injunctionViolation->violation->violationType->title;
                            $archive_injunction[$injunction_id]['direction'] = array_unique($directions);
                            $archive_injunction[$injunction_id]['auditor'] = $auditor;
                            $archive_injunction[$injunction_id]['department_title'] = $company_title;
                            $archive_injunction[$injunction_id]['rtn_statistic_status_id'] = $inj_rtn_status_id;
                            $archive_injunction[$injunction_id]['status_id'] = $inj_status_id;
                        }
                        unset($directions);
                    }
                }
                unset($found_data_injunction, $found_worker);
            } else {
                $errors[] = 'GetArchiveInjunction. Нет данных';
            }

        } catch (Throwable $exception) {
            $errors[] = 'GetArchiveInjunction. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetArchiveInjunction. Конец метода.';
        $result = $archive_injunction;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    /**
     * Метод GetArchivePab() - получение данных для архива ПАБ
     * @param null $data_post - JSON с датой начала (от какого числа хотим увидеть архив ПАБ), дата окончания
     *                          (да какого числа хотим увидеть архив ПАБ)
     * @return array - массив со следующей структурой: [c_{checking_id}_{observation_number}]
     *      *                                                       checking_id:
     *                                                              company_department_id:
     *                                                              injunction_id:
     *                                                              date_time:
     *                                                              [auditor]
     *                                                                    worker_id:
     *                                                                    worker_full_name:
     *                                                                    worker_position_title:
     *                                                                    worker_staff_number:
     *                                                              [violators]
     *                                                                    worker_id:
     *                                                                    worker_full_name:
     *                                                                    worker_position_title:
     *                                                                    worker_staff_number:
     *                                                              direction:
     *                                                              status_id:
     *
     *
     * @throws Exception
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Checking&method=GetArchivePab&subscribe=&data={"date_time_start":"2019-06-18","date_time_end":"2019-07-18"}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 22.07.2019 17:42
     * @package frontend\controllers\industrial_safety
     *
     */
    public static function GetArchivePab($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $archive_pab = array();                                                                                         // Промежуточный результирующий массив
        $auditor = array();
        $violator_info = array();
        $directions = array();
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('GetArchivePab. Данные с фронта не получены');
            }
            $warnings[] = 'GetArchivePab. Данные успешно переданы';
            $warnings[] = 'GetArchivePab. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GetArchivePab. Декодировал входные параметры';

            if (
                !property_exists($post_dec, 'date_time_start') ||
                !property_exists($post_dec, 'date_time_end') ||
                !property_exists($post_dec, 'company_department_id')
            )                                                                                                       // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('GetArchivePab. Переданы некорректные входные параметры');
            }
            $warnings[] = 'GetArchivePab.Данные с фронта получены';
            $date_time_start = date('Y-m-d', strtotime($post_dec->date_time_start));
            $date_time_end = date('Y-m-d', strtotime($post_dec->date_time_end));
            $company_department_id = $post_dec->company_department_id;

            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $warnings[] = $response['warnings'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception('GetArchivePab. Ошибка при получении вложенных участков');
            }
            unset($response);

            $warnings[] = 'GetArchivePab. Начало метода';
            $warnings[] = 'GetArchivePab. Поиск предписание нарушения.';
            $found_data_pab = Checking::find()
                ->joinWith(['checkingWorkerTypes checking_worker_type' => function ($check) {
                    $check->joinWith(['worker worker_checking_worker_type' => function ($worker) {
                        $worker->joinWith('employee employee_checking_worker_type');
                        $worker->joinWith('position position_checking_worker_type');
                    }]);
                }])
                ->joinWith('companyDepartment.company')
                ->joinWith('injunctions')
                ->joinWith('injunctions.firstInjunctionStatuses')
                ->joinWith('injunctions.injunctionViolations.violation.violationType.kindViolation')
                ->joinWith('injunctions.injunctionViolations.violators.workerEmployee')
                ->joinWith('injunctions.injunctionViolations.violators.workerPosition')
                ->where(['injunction.kind_document_id' => self::KIND_PAB])
                ->andWhere(['>=', 'checking.date_time_start', $date_time_start . ' 00:00:00'])
                ->andWhere(['<=', 'checking.date_time_start', $date_time_end . ' 23:59:59'])
                ->andWhere(['in', 'injunction.company_department_id', $company_departments])
                ->asArray()
                ->limit(50000)
                ->all();
            if ($found_data_pab) {
                $warnings[] = 'GetArchivePab. Предписания ПАБ найдено';
                $warnings[] = 'GetArchivePab. Перебор найденных данных';
                foreach ($found_data_pab as $checking_item) {
                    foreach ($checking_item['checkingWorkerTypes'] as $checkingWorkerType) {
                        if ($checkingWorkerType['worker_type_id'] == self::WORKER_TYPE_AUDITOR) {
                            $auditor['worker_id'] = $checkingWorkerType['worker']['id'];
                            $full_name_auditor = "{$checkingWorkerType['worker']['employee']['last_name']} {$checkingWorkerType['worker']['employee']['first_name']} {$checkingWorkerType['worker']['employee']['patronymic']}";
                            $auditor['worker_full_name'] = $full_name_auditor;
                            $auditor['worker_position_title'] = $checkingWorkerType['worker']['position']['title'];
                            $auditor['worker_staff_number'] = $checkingWorkerType['worker']['tabel_number'];
                        }
//                        elseif ($checkingWorkerType['worker_type_id'] == self::WORKER_TYPE_NARUSHITEL) {
//                            unset($violator_type);
//                            $violator_type['worker_id'] = $checkingWorkerType['worker']['id'];
//                            $full_name_auditor = "{$checkingWorkerType['worker']['employee']['last_name']} {$checkingWorkerType['worker']['employee']['first_name']} {$checkingWorkerType['worker']['employee']['patronymic']}";
//                            $violator_type['worker_full_name'] = $full_name_auditor;
//                            $violator_type['worker_position_title'] = $checkingWorkerType['worker']['position']['title'];
//                            $violator_type['worker_staff_number'] = $checkingWorkerType['worker']['tabel_number'];
//                        }
                    }
                    foreach ($checking_item['injunctions'] as $injunction) {
                        if (isset($injunction['firstInjunctionStatuses']['date_time'])) {
                            $date_time = date('d.m.Y H:i:s', strtotime($injunction['firstInjunctionStatuses']['date_time']));
                            $date_time_format = date('d.m.Y', strtotime($injunction['firstInjunctionStatuses']['date_time']));
                        } else {
                            $date_time = null;
                            $date_time_format = null;
                        }
                        $com = "c_{$checking_item['id']}_{$injunction['observation_number']}";
                        foreach ($injunction['injunctionViolations'] as $injunctionViolation) {
                            foreach ($injunctionViolation['violators'] as $violator) {
                                $violator_info['worker_id'] = $violator['worker']['id'];
                                $full_name_violator = "{$violator['workerEmployee']['last_name']} {$violator['workerEmployee']['first_name']} {$violator['workerEmployee']['patronymic']}";
                                $violator_info['worker_full_name'] = $full_name_violator;
                                $violator_info['worker_position_title'] = $violator['workerPosition']['title'];
                                $violator_info['worker_staff_number'] = $violator['worker']['tabel_number'];
                            }
                            $archive_pab[$com]['checking_id'] = $checking_item['id'];
                            $ppk_id = explode("_", $checking_item['pab_id']);
                            if (isset($ppk_id[1])) {
                                $archive_pab[$com]['ppk_id'] = $ppk_id[1];
                            } else {
                                $archive_pab[$com]['ppk_id'] = "";
                            }
                            $archive_pab[$com]['company_department_id'] = $checking_item['company_department_id'];
                            $archive_pab[$com]['department_title'] = $checking_item['companyDepartment']['company']['title'];
                            $archive_pab[$com]['injunction_id'] = $injunction['id'];
                            $archive_pab[$com]['date_time'] = $date_time;
                            $archive_pab[$com]['date_time_formated'] = $date_time_format;
                            $archive_pab[$com]['auditor'] = $auditor;//
                            $directions[] = $injunctionViolation['violation']['violationType']['title'];
                            $archive_pab[$com]['direction'] = array_unique($directions);
                            $archive_pab[$com]['status_id'] = $injunction['status_id'];
                            $archive_pab[$com]['violator'] = $violator_info;//
                        }
                        unset($directions);
                    }
                }
            } else {
                $errors[] = 'GetArchivePab. Нет данных';
            }
            $result = $archive_pab;
        } catch (Throwable $exception) {
            $errors[] = 'GetArchivePab. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetArchivePab. Конец метода.';
        if (!isset($result)) {
            $result = (object)array();
        }
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    /**
     * Метод GetInfoAboutInjunction() - Получение информации о предписании нарушения или список информации о предписании нарушения
     * @param null $data_post - JSON с идентификатор предписания(или checking_id) для которого нужна информация или
     * date_start, date_end, company_department_id,(injunction_kind_document_id - не обязателен)
     * @return array - массив с следующей структурой:
     * [injunction_id]                              - в случае выборки по дате
     *      injunction_id:                               -идентификатор предписания
     *      checking_id:                                 -идентификатор проверки
     *      inj_worker_id:
     *      ppk_id:
     *      injunction_status_id:                        -статус предписания
     *      kind_document_id:
     *      injunction_place_title:
     *      rtn_statistic_status_id:
     *      mine_title:                                  -наименование шахты
     *      base_checking:                               -основание проверки
     *      company_department_id:
     *      company_department_title:
     *      date_first_status:                           -дата и время оформления предписания
     *      date_first_status_format:
     *      status_id:
     *      auditors {                                   -все кто проводил проверку (аудитор)
     *           [worker_staff_number]                   -идентификатор работника (аудитора)
     *               worker_id:                          -идентификатор работника (аудитора)
     *               worker_full_name:
     *               worker_staff_number:
     *               worker_position:
     *               department_path:
     *      }
     *      violator {
     *           [worker_staff_number]                   -все кто присутствовал при проверке (присутствующий)
     *               worker_id:                          -идентификатор работника (присутствующий)
     *               worker_full_name:
     *               worker_staff_number:
     *               worker_position:
     *               department_path:
     *      }
     *      injunction_violations {                      -предписание нарушения
     *           [injunction_violation_id]               -идентификатор прдеписание нарушения
     *               violator{
     *                   [worker_staff_number]
     *                       worker_id:
     *                       worker_full_name:
     *                       worker_staff_number:
     *                       worker_position:
     *                       department_path:
     *               }
     *               injunction_violation_id:
     *               violation_id:                       -идентификатор нарушения
     *               inj_violation_place_id:             -идентификатор места нарушения
     *               inj_violation_place_title:          -название места нарушения
     *               injunction_violation_img:           -картинка предписания нарушения
     *               document_id:                        -идентификатор документа
     *               document_title:                     -наименование документа
     *               paragraph_pb_text:                  -пункт документа
     *               gravity:
     *               probability:                        -вероятность
     *               kind_violation_title:               -направление нарушения
     *               injunction_img: {
     *                  [injunction_img_id]: {
     *                      injunction_img_id:
     *                      injunction_img_path:
     *                  }
     *                  ...
     *               }
     *               correct_measures {                  -корректирующие мероприятия - (null)
     *                   [correct_measures_id]           -идентификатор корректирующего мероприятия
     *                       correct_measures_id:        -идентификатор корректирующего мероприятия
     *                       operation_title:
     *                       operation_id:               -идентификатор операции
     *                       operation_groups_obj {}
     *                       correct_measures_description
     *                       correct_measures_value:     -объём корректирующего мероприятия
     *                       unit_short_title:
     *                       date_time:                  -дата и время корректирующего мероприятия
     *                       date_time_format:
     *                       correct_measures_status_id: -статус корректирующего мероприятия
     *               }
     *               stop_pbs {                          -простои - (null)
     *                   [type]                          -тип "Приостановка работ" либо "Технологически вынужденный простой"
     *                   [stop_pb_id]                    -идентификатор прростоя
     *                       stop_pb_id:                 -идентификатор прростоя
     *                       place_id:                   -идентификатор места
     *                       place_title:                -название места
     *                       date_time_start:            -дата и время начала простоя
     *                       stop_date_time_start_formated:
     *                       date_time_end:              -дата и время окончания простоя
     *                       stop_date_time_end_formated:
     *                       until_complete_flag:
     *                       equipments: {
     *                          [equipment_id]
     *                              equipment_id:
     *                              equipment_title:
     *               }
     *      injunction_attachment {                      -вложения
     *           [injunction_attachment_id]              -идентификатор вложения предписания
     *               attachment_path:                    -путь вложения
     *      }
     *      presentors {
     *           [worker_staff_number]
     *               worker_id:
     *               worker_full_name:
     *               worker_staff_number:
     *               worker_position:
     *               department_path:
     *      }
     *      responsible {                                -ответственный
     *           worker_id:
     *           worker_staff_number
     *           worker_position
     *           department_path
     * }
     *
     *
     * @throws Exception
     * @example http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Checking&method=GetInfoAboutInjunction&subscribe=&data={%22injunction_id%22:39006}
     * @example http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Checking&method=GetInfoAboutInjunction&subscribe=&data={%22checking_id%22:39006}
     * @example http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Checking&method=GetInfoAboutInjunction&subscribe=&data={"date_start":"2019-07-05","date_end":"2023-08-13","company_department_id":60002522}
     * @example http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Checking&method=GetInfoAboutInjunction&subscribe=&data={"date_start":"2019-07-05","date_end":"2023-08-13","company_department_id":60002522,"injunction_kind_document_id":4}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 24.07.2019 13:39
     * @package frontend\controllers\industrial_safety
     *
     */
    public static function GetInfoAboutInjunction($data_post = NULL)
    {
        $log = new LogAmicumFront("GetInfoAboutInjunction");

        $info_about_inj = null;
        $flag_array = false;
        $result = null;
        $department_paths = [];

        try {
            $log->addLog("Начало выполнения метода");

            if (is_null($data_post) or $data_post == "") {
                throw new Exception("Входной массив данных post не передан");
            }

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];                                                                       // Декодируем входной массив данных

            if (property_exists($post, 'checking_id') && $post->checking_id) {
                $checking_id = $post->checking_id;
                $filter = ['checking.id' => $checking_id];
            } else if (property_exists($post, 'injunction_id') && $post->injunction_id) {
                $post_injunction_id = $post->injunction_id;
                $filter = ['injunction.id' => $post_injunction_id];
            } else if (
                property_exists($post, 'date_start') && $post->date_start &&
                property_exists($post, 'date_end') && $post->date_end &&
                property_exists($post, 'company_department_id') && $post->company_department_id
            ) {
                $flag_array = true;
                $date_start = date('Y-m-d', strtotime($post->date_start));
                $date_end = date('Y-m-d', strtotime($post->date_end));
                $company_department_id = $post->company_department_id;
                $filter = 'checking.company_department_id = ' . $company_department_id . ' AND checking.date_time_start >= "' . $date_start . ' 00:00:00" AND checking.date_time_start <= "' . $date_end . ' 23:59:59"';
                if (property_exists($post, 'injunction_kind_document_id') && $post->injunction_kind_document_id) {
                    $filter = $filter . "AND injunction.kind_document_id = $post->injunction_kind_document_id";
                } else {
                    $filter = $filter . "AND injunction.kind_document_id IN (1,3,5)";
                }
            } else {
                throw new Exception("Входные параметры не переданы");
            }


            /******************** ПОИСК ДАННЫХ В НУЖНЫХ ТАБЛИЦАХ ********************/
            $found_data_abot_injuinction = Checking::find()
                ->select([
                    'checking.id as checking_id',
                    'checking.date_time_start as checking_date_time_start',
                    'checking.instruct_id as instruct_id',
                    'checking.pab_id as pab_id',
                    'checking.rostex_number as rostex_number',
                    'checking.nn_id as nn_id',
                    'injunction.id as injunction_id',
                    'injunction.kind_document_id as kind_document_id',
                    'injunction.rtn_statistic_status_id as rtn_statistic_status_id',
                    'injunction.place_id as injunction_place_id',
                    'place.title as injunction_place_title',
                    'injunction.worker_id inj_worker_id',
                    'injunction.description as injunction_description',
                    'injunction.company_department_id as company_department_id',
                    'injunction.status_id as injunction_status_id',
                    'company.title as company_title',
                    'checking_worker_type.worker_id as worker_type_worker_id',
                    'checking_emp.first_name as checking_first_name',
                    'checking_emp.last_name as checking_last_name',
                    'checking_emp.patronymic as checking_patronymic',
                    'checking_worker.id as worker_id',
                    'checking_worker.tabel_number as tabel_number',
                    'checking_worker.company_department_id as checking_company_department_id',
                    'checking_pos.title as position_title',
                    'checking_worker_type.worker_type_id as worker_type_id',
                    'violator.worker_id as violator_worker_id',
                    'violator_employee.first_name as violator_first_name',
                    'violator_employee.last_name as violator_last_name',
                    'violator_employee.patronymic as violator_patronymic',
                    'violator_worker.id as violator_worker_id',
                    'violator_worker.tabel_number as violator_stuff_number',
                    'violator_worker.company_department_id as violator_company_department_id',
                    'violator_position.title as violator_position_title',
                    'injunction_violation.id as inj_viol_id',
                    'injunction_violation.place_id as inj_vio_place_id',
                    'injunction_violation.violation_id as inj_vio_violation_id',
                    'injunction_violation.gravity as gravity',
                    'injunction_violation.probability as probability',
                    'injunction_violation.reason_danger_motion_id as reason_danger_motion_id',
                    'place.title as inj_vio_place_title',
                    'injunction_img.img_path as inj_img',
                    'violation.title as violation_title',
                    'document.id as document_id',
                    'document.title as document_title',
                    'paragraph_pb.text as paragraph_pb_text',
                    'correct_measures.id as correct_measures_id',
                    'correct_measures.operation_id as operation_id',
                    'correct_measures.correct_measures_description as correct_measures_description',
                    'correct_measures.correct_measures_value as correct_measures_value',
                    'correct_measures.date_time as date_time_date_time',
                    'correct_measures.result_correct_measures as result_correct_measures',
                    'correct_measures.status_id as correct_measures_status_id',
                    'operation.title as operation_title',
                    'operation.unit_id as unit_id',
                    'unit.short as unit_short_title',
                    'kind_violation.title as kind_violation_title',
                    'violation_type.title as violation_type_title',
                    'stop_pb.id as stop_pb_id',
                    'stop_pb_equipment.equipment_id as equipment_id',
                    'stop_pb_equipment.id as stop_pb_equipment_id',
                    'stop_pb.place_id as stop_pb_place_id',
                    'stop_pb.date_time_start as stop_date_time_start',
                    'stop_pb.date_time_end as stop_date_time_end',
                    'stop_place.title as stop_place_title',
                    'stop_equipment.title as stop_equipment_title',
                    'injunction_attachment.id as injunction_attachment_id',
                    'attachment.path as attachment_path',
                    'injunction_attachment.injunction_id as attach_injunction_id',
                    'operation_group.group_operation_id as group_operation_id',
                    'mine.title as mine_title'
                ])
                ->innerJoin('injunction', 'checking.id = injunction.checking_id')
                ->leftjoin('checking_worker_type', 'checking.id = checking_worker_type.checking_id')
                ->leftJoin('worker as checking_worker', 'checking_worker.id = checking_worker_type.worker_id')
                ->leftJoin('employee as checking_emp', 'checking_emp.id = checking_worker.employee_id')
                ->leftJoin('position as checking_pos', 'checking_pos.id = checking_worker.position_id')
                ->leftjoin('company_department', 'company_department.id = injunction.company_department_id')
                ->leftjoin('company', 'company.id = company_department.company_id')
                ->leftjoin('injunction_violation', 'injunction.id = injunction_violation.injunction_id')
                ->leftJoin('place', 'place.id = injunction_violation.place_id')
                ->leftJoin('place p1', 'p1.id = injunction_violation.place_id')
                ->leftJoin('mine', 'place.mine_id = mine.id')
                ->leftjoin('injunction_img', 'injunction_violation.id = injunction_img.injunction_violation_id')
                ->leftjoin('violator', 'injunction_violation.id = violator.injunction_violation_id')
                ->leftjoin('worker violator_worker', 'violator.worker_id = violator_worker.id')
                ->leftjoin('employee violator_employee', 'violator_worker.employee_id = violator_employee.id')
                ->leftjoin('position violator_position', 'violator_worker.position_id = violator_position.id')
                ->leftjoin('paragraph_pb', 'injunction_violation.paragraph_pb_id = paragraph_pb.id')
                ->leftjoin('document', 'injunction_violation.document_id = document.id')
                ->leftjoin('injunction_violation_status', 'injunction_violation.id = injunction_violation_status.injunction_violation_id')
                ->leftjoin('violation', 'injunction_violation.violation_id = violation.id')
                ->leftjoin('violation_type', 'violation.violation_type_id = violation_type.id')
                ->leftjoin('kind_violation', 'violation_type.kind_violation_id = kind_violation.id')
                ->leftjoin('correct_measures', 'injunction_violation.id = correct_measures.injunction_violation_id')
                ->leftjoin('operation', 'correct_measures.operation_id = operation.id')
                ->leftjoin('operation_group', 'operation_group.operation_id = operation.id')
                ->leftjoin('unit', 'operation.unit_id = unit.id')
                ->leftJoin('stop_pb', 'stop_pb.injunction_violation_id = injunction_violation.id')
                ->leftJoin('stop_pb_equipment', 'stop_pb.id = stop_pb_equipment.stop_pb_id')
                ->leftJoin('place as stop_place', 'stop_place.id = stop_pb.place_id')
                ->leftJoin('equipment as stop_equipment', 'stop_equipment.id = stop_pb_equipment.equipment_id')
                ->leftJoin('injunction_attachment', 'injunction_attachment.injunction_id = injunction.id')
                ->leftJoin('attachment', 'attachment.id = injunction_attachment.attachment_id')
                ->where($filter)
                ->asArray()
                ->all();

            $all_companies = Company::find()->indexBy('id')->asArray()->all();

            $log->addLog("Данные найдены. Данные найдены. Начинаю перебор с целью формирования результирующего массива");

            foreach ($found_data_abot_injuinction as $injunction_item) {
                $injunction_id = $injunction_item['injunction_id'];
                $info_about_inj[$injunction_id]['injunction_id'] = $injunction_id;
                $info_about_inj[$injunction_id]['checking_id'] = $injunction_item['checking_id'];
                $info_about_inj[$injunction_id]['inj_worker_id'] = $injunction_item['inj_worker_id'];

                $ppk_id_rtn = $injunction_item['rostex_number'];
                $ppk_id_instruct = $injunction_item['instruct_id'];
                $ppk_id_nn = explode("_", $injunction_item['nn_id']);
                $ppk_id_pab = explode("_", $injunction_item['pab_id']);
                if ($ppk_id_rtn) {
                    $info_about_inj[$injunction_id]['ppk_id'] = $ppk_id_rtn;
                } else if ($ppk_id_instruct) {
                    $info_about_inj[$injunction_id]['ppk_id'] = $ppk_id_instruct;
                } else if ($ppk_id_nn and isset($ppk_id_nn[1])) {
                    $info_about_inj[$injunction_id]['ppk_id'] = $ppk_id_nn[1];
                } else if ($ppk_id_pab and isset($ppk_id_pab[1])) {
                    $info_about_inj[$injunction_id]['ppk_id'] = $ppk_id_pab[1];
                } else {
                    $info_about_inj[$injunction_id]['ppk_id'] = "";
                }

                $info_about_inj[$injunction_id]['injunction_place_id'] = $injunction_item['injunction_place_id'];
                $info_about_inj[$injunction_id]['checking_date_time_start'] = $injunction_item['checking_date_time_start'];
                $info_about_inj[$injunction_id]['kind_document_id'] = $injunction_item['kind_document_id'];
                $info_about_inj[$injunction_id]['injunction_place_title'] = $injunction_item['injunction_place_title'];
                $info_about_inj[$injunction_id]['rtn_statistic_status_id'] = $injunction_item['rtn_statistic_status_id'];
                $info_about_inj[$injunction_id]['mine_title'] = $injunction_item['mine_title'];
                $info_about_inj[$injunction_id]['base_checking'] = 'На основании Федерального закона от 21 июля 1997 года №116-ФЗ "О промышленной безопасности производственных объектов"';
                $info_about_inj[$injunction_id]['company_department_id'] = $injunction_item['company_department_id'];
                $info_about_inj[$injunction_id]['company_department_title'] = $injunction_item['company_title'];

                if (!isset($info_about_inj[$injunction_id]['date_first_status'])) {
                    $injunction_first_status = InjunctionStatus::find()->where(['injunction_id' => $injunction_id])->one();
                    if ($injunction_first_status and $injunction_first_status->date_time) {
                        $info_about_inj[$injunction_id]['date_first_status'] = $injunction_first_status->date_time;
                        $info_about_inj[$injunction_id]['date_first_status_format'] = date('d.m.Y', strtotime($injunction_first_status['date_time']));
                    } else {
                        $info_about_inj[$injunction_id]['date_first_status'] = null;
                        $info_about_inj[$injunction_id]['date_first_status_format'] = null;
                    }
                }

                $info_about_inj[$injunction_id]['status_id'] = $injunction_item['injunction_status_id'];
                /******************** ЗАПИСЫВАЕМ АУДИТОРОВ И ПРИСУТСТВУЮЩИХ ********************/
                if ($injunction_item['worker_type_id'] == self::WORKER_TYPE_AUDITOR)                                    // если тип работника аудитор записываем в массив
                {
                    $full_name = "{$injunction_item['checking_last_name']} {$injunction_item['checking_first_name']} {$injunction_item['checking_patronymic']}";
                    $info_about_inj[$injunction_id]['auditors'][$injunction_item['worker_type_worker_id']]['worker_id'] = $injunction_item['worker_id'];
                    $info_about_inj[$injunction_id]['auditors'][$injunction_item['worker_type_worker_id']]['worker_full_name'] = $full_name;
                    $info_about_inj[$injunction_id]['auditors'][$injunction_item['worker_type_worker_id']]['worker_staff_number'] = $injunction_item['tabel_number'];
                    $info_about_inj[$injunction_id]['auditors'][$injunction_item['worker_type_worker_id']]['worker_position'] = $injunction_item['position_title'];

                    $department_path = "";
                    if ($injunction_item['checking_company_department_id'] and !isset($department_paths[$injunction_item['checking_company_department_id']])) {
                        $response = HandbookDepartmentController::GetAllParentsCompaniesWithCompany($injunction_item['checking_company_department_id'], $all_companies);               // путь до департамента работника
                        if ($response['status'] == 1) {
                            $department_path = $response['Items'];                                                              // путь до департамента работника
                        }
                    }
                    $department_paths[$injunction_item['checking_company_department_id']] = $department_path;

                    $info_about_inj[$injunction_id]['auditors'][$injunction_item['worker_type_worker_id']]['department_path'] = $department_paths[$injunction_item['checking_company_department_id']];
                } else if ($injunction_item['worker_type_id'] == self::WORKER_TYPE_PRESENT)                                    // иначе если тип работника присутствующий записываем в массив
                {
                    $full_name = "{$injunction_item['checking_last_name']} {$injunction_item['checking_first_name']} {$injunction_item['checking_patronymic']}";
                    $info_about_inj[$injunction_id]['presentors'][$injunction_item['worker_type_worker_id']]['worker_id'] = $injunction_item['worker_id'];
                    $info_about_inj[$injunction_id]['presentors'][$injunction_item['worker_type_worker_id']]['worker_full_name'] = $full_name;
                    $info_about_inj[$injunction_id]['presentors'][$injunction_item['worker_type_worker_id']]['worker_staff_number'] = $injunction_item['tabel_number'];
                    $info_about_inj[$injunction_id]['presentors'][$injunction_item['worker_type_worker_id']]['worker_position'] = $injunction_item['position_title'];

                    $department_path = "";
                    if ($injunction_item['checking_company_department_id'] and !isset($department_paths[$injunction_item['checking_company_department_id']])) {
                        $response = HandbookDepartmentController::GetAllParentsCompaniesWithCompany($injunction_item['checking_company_department_id'], $all_companies);               // путь до департамента работника
                        if ($response['status'] == 1) {
                            $department_path = $response['Items'];                                                              // путь до департамента работника
                        }
                    }
                    $department_paths[$injunction_item['checking_company_department_id']] = $department_path;

                    $info_about_inj[$injunction_id]['presentors'][$injunction_item['worker_type_worker_id']]['department_path'] = $department_paths[$injunction_item['checking_company_department_id']];
                }

                if ($injunction_item['violator_worker_id'] != null)                                                     // иначе если тип работника присутствующий записываем в массив
                {
                    $full_name = "{$injunction_item['violator_last_name']} {$injunction_item['violator_first_name']} {$injunction_item['violator_patronymic']}";
                    $info_about_inj[$injunction_id]['violator'][$injunction_item['violator_worker_id']]['worker_id'] = $injunction_item['violator_worker_id'];
                    $info_about_inj[$injunction_id]['violator'][$injunction_item['violator_worker_id']]['worker_full_name'] = $full_name;
                    $info_about_inj[$injunction_id]['violator'][$injunction_item['violator_worker_id']]['worker_staff_number'] = $injunction_item['violator_stuff_number'];
                    $info_about_inj[$injunction_id]['violator'][$injunction_item['violator_worker_id']]['worker_position'] = $injunction_item['violator_position_title'];
                    $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['violator'][$injunction_item['violator_worker_id']]['worker_id'] = $injunction_item['violator_worker_id'];
                    $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['violator'][$injunction_item['violator_worker_id']]['worker_full_name'] = $full_name;
                    $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['violator'][$injunction_item['violator_worker_id']]['worker_staff_number'] = $injunction_item['violator_stuff_number'];
                    $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['violator'][$injunction_item['violator_worker_id']]['worker_position'] = $injunction_item['violator_position_title'];

                    $department_path = "";
                    if ($injunction_item['violator_company_department_id'] and !isset($department_paths[$injunction_item['violator_company_department_id']])) {
                        $response = HandbookDepartmentController::GetAllParentsCompaniesWithCompany($injunction_item['violator_company_department_id'], $all_companies);               // путь до департамента работника
                        if ($response['status'] == 1) {
                            $department_path = $response['Items'];                                                              // путь до департамента работника
                        }
                    }
                    $department_paths[$injunction_item['violator_company_department_id']] = $department_path;
                    $info_about_inj[$injunction_id]['violator'][$injunction_item['violator_worker_id']]['department_path'] = $department_paths[$injunction_item['violator_company_department_id']];
                    $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['violator'][$injunction_item['violator_worker_id']]['department_path'] = $department_paths[$injunction_item['violator_company_department_id']];

                }
                /******************** ЗАПИСЫВАЕМ ИНФОРМАЦИЮ О НАРУШЕНИЯХ ********************/
                $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['injunction_violation_id'] = $injunction_item['inj_viol_id'];
                $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['violation_id'] = $injunction_item['inj_vio_violation_id'];
                $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['inj_violation_place_id'] = $injunction_item['inj_vio_place_id'];
                $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['inj_violation_place_title'] = $injunction_item['inj_vio_place_title'];
                $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['injunction_violation_img'] = $injunction_item['inj_img'];
                $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['document_id'] = $injunction_item['document_id'];
                $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['document_title'] = $injunction_item['document_title'];
                $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['paragraph_pb_text'] = $injunction_item['paragraph_pb_text'];
                $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['gravity'] = $injunction_item['gravity'];
                $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['probability'] = $injunction_item['probability'];
                $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['injunction_description'] = $injunction_item['violation_title'];
                $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['kind_violation_title'] = $injunction_item['kind_violation_title'] . " / " . $injunction_item['violation_type_title'];
                /******************** ЗАПИСЫВАЕМ injunction_img ********************/
                $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['injunction_img'] = null;
                $injunction_imgs = InjunctionImg::findAll(['injunction_violation_id' => $injunction_item['inj_viol_id']]);
                foreach ($injunction_imgs as $model_injunction_img) {
                    $injunction_img['injunction_img_id'] = $model_injunction_img->id;
                    $injunction_img['injunction_img_path'] = $model_injunction_img->img_path;
                    $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['injunction_img'][$model_injunction_img->id] = $injunction_img;
                }
                /******************** ЗАПИСЫВАЕМ КОРРЕКТИРУЮЩИЕ МЕРОПРИЯТИЯ ********************/
                if ($injunction_item['correct_measures_id'] != null)                                                    // если есть идентификатор корректирующего мероприятия не пуст тогда записываем корректирующие мероприятия
                {
                    $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['correct_measures'][$injunction_item['correct_measures_id']]['correct_measures_id'] = $injunction_item['correct_measures_id'];
                    $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['correct_measures'][$injunction_item['correct_measures_id']]['operation_title'] = $injunction_item['operation_title'];
                    $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['correct_measures'][$injunction_item['correct_measures_id']]['operation_id'] = $injunction_item['operation_id'];

                    if ($injunction_item['group_operation_id'] and !isset($info_about_inj['injunction_violation'][$injunction_item['inj_viol_id']]['correct_measures'][$injunction_item['correct_measures_id']]['operation_groups_obj'][$injunction_item['group_operation_id']])) {
                        $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['correct_measures'][$injunction_item['correct_measures_id']]['operation_groups'][] = $injunction_item['group_operation_id'];
                    }
                    $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['correct_measures'][$injunction_item['correct_measures_id']]['operation_groups_obj'][$injunction_item['group_operation_id']] = $injunction_item['group_operation_id'];
                    $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['correct_measures'][$injunction_item['correct_measures_id']]['correct_measures_description'] = $injunction_item['correct_measures_description'];
                    $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['correct_measures'][$injunction_item['correct_measures_id']]['correct_measures_value'] = $injunction_item['correct_measures_value'];
                    $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['correct_measures'][$injunction_item['correct_measures_id']]['unit_short_title'] = $injunction_item['unit_short_title'];
                    if ($injunction_item['date_time_date_time'] != null) {
                        $date_time_correct_measures = $injunction_item['date_time_date_time'];
                        $date_time_correct_measures_format = date('d.m.Y H:i', strtotime($injunction_item['date_time_date_time']));
                    } else {
                        $date_time_correct_measures = null;
                        $date_time_correct_measures_format = null;
                    }
                    $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['correct_measures'][$injunction_item['correct_measures_id']]['date_time'] = $date_time_correct_measures;
                    $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['correct_measures'][$injunction_item['correct_measures_id']]['date_time_format'] = $date_time_correct_measures_format;
                    $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['correct_measures'][$injunction_item['correct_measures_id']]['correct_measures_status_id'] = $injunction_item['correct_measures_status_id'];
                } else {
                    $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['correct_measures'] = null;
                }
                /******************** ЗАПИСЫВАЕМ ПРОСТОИ ПБ ********************/
                if ($injunction_item['stop_pb_id'] !== null)                                                            // если есть идентификатор остановки не пуст тогда записываем остановки
                {
                    if ($injunction_item['inj_vio_place_id'] !== $injunction_item['stop_pb_place_id'])                  // если место остановки отличается от места на которое выдано предписание, то это 'Технологически вынужденная остановка'
                    {
                        $type_stop_pb = 'forced_stop_pb';
                    } else {                                                                                            // иначе это 'Приостановка работ'
                        $type_stop_pb = 'stop_pb';
                    }

                    if ($injunction_item['stop_date_time_end'] == null) {
                        $until = true;
                        $date_end_format = null;
                    } else {
                        $until = false;
                        $date_end_format = date('d.m.Y', strtotime($injunction_item['stop_date_time_end']));
                    }

                    $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['stop_pbs'][$type_stop_pb][$injunction_item['stop_pb_id']]['stop_pb_id'] = $injunction_item['stop_pb_id'];
                    $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['stop_pbs'][$type_stop_pb][$injunction_item['stop_pb_id']]['place_id'] = $injunction_item['stop_pb_place_id'];
                    $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['stop_pbs'][$type_stop_pb][$injunction_item['stop_pb_id']]['place_title'] = $injunction_item['stop_place_title'];
                    $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['stop_pbs'][$type_stop_pb][$injunction_item['stop_pb_id']]['stop_date_time_start'] = $injunction_item['stop_date_time_start'];
                    $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['stop_pbs'][$type_stop_pb][$injunction_item['stop_pb_id']]['stop_date_time_start_format'] = date('d.m.Y', strtotime($injunction_item['stop_date_time_start']));
                    $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['stop_pbs'][$type_stop_pb][$injunction_item['stop_pb_id']]['stop_date_time_end'] = $injunction_item['stop_date_time_end'];
                    $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['stop_pbs'][$type_stop_pb][$injunction_item['stop_pb_id']]['stop_date_time_end_format'] = $date_end_format;
                    $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['stop_pbs'][$type_stop_pb][$injunction_item['stop_pb_id']]['until_complete_flag'] = $until;
                    $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['stop_pbs'][$type_stop_pb][$injunction_item['stop_pb_id']]['equipments'][$injunction_item['stop_pb_equipment_id']]['equipment_id'] = $injunction_item['equipment_id'];
                    $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['stop_pbs'][$type_stop_pb][$injunction_item['stop_pb_id']]['equipments'][$injunction_item['stop_pb_equipment_id']]['equipment_title'] = $injunction_item['stop_equipment_title'];
                } else {
                    $info_about_inj[$injunction_id]['injunction_violation'][$injunction_item['inj_viol_id']]['stop_pbs'] = null;
                }
                if ($injunction_item['injunction_attachment_id'] !== null) {
                    $info_about_inj[$injunction_id]['injunction_attachment'][$injunction_item['injunction_attachment_id']]['attachment_path'] = $injunction_item['attachment_path'];
//                        $injunction_place_id = $injunction_item['injunction_place_id'];
                } else {
                    $info_about_inj[$injunction_id]['injunction_attachment'] = (object)array();
                }
                /******************** ИЩЕМ И ЗАПИСЫВАЕМ ОТВЕТСТВЕННОГО ********************/
                if ($injunction_item['worker_type_id'] == self::WORKER_TYPE_RESPONSIBLE)                                // если тип работника аудитор тогда записываем его
                {
                    $full_name = "{$injunction_item['checking_last_name']} {$injunction_item['checking_first_name']} {$injunction_item['checking_patronymic']}";
                    $info_about_inj[$injunction_id]['responsible']['worker_id'] = $injunction_item['worker_id'];
                    $info_about_inj[$injunction_id]['responsible']['full_name'] = $full_name;
                    $info_about_inj[$injunction_id]['responsible']['worker_staff_number'] = $injunction_item['tabel_number'];
                    $info_about_inj[$injunction_id]['responsible']['worker_position'] = $injunction_item['position_title'];

                    $department_path = "";
                    if ($injunction_item['checking_company_department_id'] and !isset($department_paths[$injunction_item['checking_company_department_id']])) {
                        $response = HandbookDepartmentController::GetAllParentsCompaniesWithCompany($injunction_item['checking_company_department_id'], $all_companies);               // путь до департамента работника
                        if ($response['status'] == 1) {
                            $department_path = $response['Items'];                                                              // путь до департамента работника
                        }
                    }
                    $department_paths[$injunction_item['checking_company_department_id']] = $department_path;
                    $info_about_inj[$injunction_id]['responsible']['department_path'] = $department_paths[$injunction_item['checking_company_department_id']];
                }
            }

            if ($info_about_inj) {
                foreach ($info_about_inj as $item) {
                    $injunction_id = $item['injunction_id'];
                    if (empty($item['auditors'])) {
                        $info_about_inj[$injunction_id]['auditors'] = (object)array();
                    }
                    if (empty($item['presentors'])) {
                        $info_about_inj[$injunction_id]['presentors'] = (object)array();
                    }
                    if (empty($item['responsible'])) {
                        $info_about_inj[$injunction_id]['responsible'] = (object)array();
                    }
                }
                if ($flag_array) {
                    $result = $info_about_inj;
                } else {
                    $result = $info_about_inj[$injunction_id];
                }
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Конец метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод GetInfoAboutPab() - получение информации о ПАБ или список информации о ПАБ
     * @param null $data_post - JSON с идентификатором предписания или
     * date_start, date_end, company_department_id
     * @return array - массив со следующей структурой:
     * [injunction_id] - в случае выборки по дате
     *  injunction_id:
     *  checking_id:
     *  inj_worker_id:
     *  ppk_id:
     *  company_department_id:
     *  company_department_title:
     *  date_first_status:
     *  date_first_status_formated:
     *  status_id:
     *  mine_title:
     *  base_checking:
     *  auditor_worker {
     *      worker_id:
     *      position_title:
     *      full_name
     *  }
     *  injunction_violation: {
     *      [injunction_violation_id] {
     *          injunction_violation_id:
     *          injunction_violation_place_title:
     *          violation_id:
     *          injunction_violation_img:
     *          document_id:
     *          document_title:
     *          paragraph_pb_text:
     *          dangerous:
     *          probability:
     *          reason_danger_motion_id:
     *          reason_danger_motion_title:
     *          injunction_description:
     *          violators: {
     *              worker_id:
     *              position_title:
     *              full_name:
     *          }
     *          injunction_img: {
     *              [injunction_img_id]: {
     *                  injunction_img_id:
     *                  injunction_img_path:
     *              }
     *              ...
     *          }
     *          correct_measures: { - null
     *              [correct_measures_id] {
     *                  correct_measures_id:
     *                  operation_id:
     *                  operation_title:
     *                  correct_measures_description:
     *                  correct_measures_value:
     *                  unit_id:
     *                  unit_sort_title:
     *                  date_time:
     *                  date_time_format:
     *                  result_correct_measures:
     *                  correct_measures_status_id:
     *              }
     *              ...
     *          }
     *          stop_pbs: { - null
     *              [type_stop_pb] {
     *                  [stop_pb_id] {
     *                      stop_pb_id:
     *                      place_id:
     *                      place_title:
     *                      stop_date_time_start:
     *                      stop_date_time_start_formated:
     *                      stop_date_time_end:
     *                      stop_date_time_end_formated:
     *                      until_complete_flag:
     *                      equipments: {
     *                          [stop_pb_equipment_id] {
     *                              equipment_id:
     *                              equipment_title:
     *                          }
     *                      }
     *                  }
     *              }
     *          }
     *          kind_violation_title:
     *      }
     *  }
     *  responsible_worker: {
     *      worker_id:
     *      position_title:
     *      full_name:
     *  }
     *
     * @throws Exception
     * @example http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Checking&method=GetInfoAboutPab&subscribe=&data={%22injunction_id%22:10579090}
     * @example http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Checking&method=GetInfoAboutPab&subscribe=&data={"date_start":"2019-07-05","date_end":"2023-08-13","company_department_id":60002522}
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 06.08.2019 9:53
     * @package frontend\controllers\industrial_safety
     *
     */
    public static function GetInfoAboutPab($data_post = NULL)
    {
        $log = new LogAmicumFront("GetInfoAboutPab");

        $current_date_time_status = Assistant::GetDateTimeNow();
        $pab_status_date_time = "1970-01-01 00:00:01";
        $result = null;
        $flag_array = false;

        try {

            $log->addLog("Начало выполнения метода");

            if (is_null($data_post) or $data_post == "") {
                throw new Exception("Входной массив данных post не передан");
            }

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];

            if (
                !property_exists($post, 'injunction_id') || $post->injunction_id == ''
            ) {
                if (
                    !property_exists($post, 'date_start') || $post->date_start == '' ||
                    !property_exists($post, 'date_end') || $post->date_end == '' ||
                    !property_exists($post, 'company_department_id') || $post->company_department_id == ''
                ) {
                    throw new Exception("Входные параметры не переданы");
                }
                $flag_array = true;
                $date_start = date('Y-m-d', strtotime($post->date_start));
                $date_end = date('Y-m-d', strtotime($post->date_end));
                $company_department_id = $post->company_department_id;
                $filter = "checking.company_department_id = $company_department_id AND checking.date_time_start >= '$date_start 00:00:00' AND checking.date_time_start <= '$date_end 23:59:59'";
            } else {
                $post_injunction_id = $post->injunction_id;
                $filter = ['injunction.id' => $post_injunction_id];
            }

            /******************** ПОИСК ДАННЫХ В НУЖНЫХ ТАБЛИЦАХ ********************/
            $found_data_abot_pab = Checking::find()
                ->select([
                    'checking.id as checking_id',
                    'injunction.id as injunction_id',
                    'checking.instruct_id as instruct_id',
                    'checking.pab_id as pab_id',
                    'checking.rostex_number as rostex_number',
                    'checking.nn_id as nn_id',
                    'injunction_violation.id as inj_viol_id',
                    'injunction.company_department_id as company_department_id',
                    'company.title as company_department_title',
                    'injunction.status_id as injunction_status_id',
                    'checking_worker_type.worker_id as worker_type_worker_id',
                    'checking_worker_type.worker_type_id as worker_type_id',
                    'checking_position_worker.title as auditor_position_title',
                    'checking_employee_worker.first_name as auditor_first_name',
                    'checking_employee_worker.last_name as auditor_last_name',
                    'checking_employee_worker.patronymic as auditor_patronymic',
                    'violator_position_worker.title as violator_position_title',
                    'violator_employee_worker.first_name as violator_first_name',
                    'violator_employee_worker.last_name as violator_last_name',
                    'violator_employee_worker.patronymic as violator_patronymic',
                    'operation.title as operation_title',
                    'violator.worker_id as violator_worker_id',
                    'injunction_violation.place_id as inj_vio_place_id',
                    'injunction_violation.violation_id as inj_vio_violation_id',
                    'violation.title as violation_title',
                    'injunction_img.img_path as inj_img',
                    'document.id as document_id',
                    'place.title as inj_vio_place_title',
                    'unit.short as unit_short_title',
                    'document.title as document_title',
                    'paragraph_pb.text as paragraph_pb_text',
                    'injunction_violation.gravity as dangerous',
                    'injunction_violation.probability as probability',
                    'correct_measures.id as correct_measures_id',
                    'correct_measures.correct_measures_description as correct_measures_description',
                    'correct_measures.operation_id as operation_id',
                    'correct_measures.correct_measures_value as correct_measures_value',
                    'operation.unit_id as unit_id',
                    'correct_measures.result_correct_measures as result_correct_measures',
                    'correct_measures.status_id as correct_measures_status_id',
                    'correct_measures.date_time as date_time_date_time',
                    'injunction.place_id as injunction_place_id',
                    'injunction.description as injunction_description',
                    'injunction_violation.reason_danger_motion_id as reason_danger_motion_id',
                    'reason_danger_motion.title as reason_danger_motion_title',
                    'kind_violation.title as kind_violation_title',
                    'stop_pb.id as stop_pb_id',
                    'stop_pb_equipment.equipment_id as equipment_id',
                    'stop_pb_equipment.id as stop_pb_equipment_id',
                    'stop_pb.place_id as stop_pb_place_id',
                    'stop_pb.date_time_start as stop_date_time_start',
                    'stop_pb.date_time_end as stop_date_time_end',
                    'stop_place.title as stop_place_title',
                    'stop_equipment.title as stop_equipment_title',
                    'injunction.worker_id as inj_worker_id',
                    'mine.title as mine_title'])
                ->leftjoin('checking_worker_type', 'checking.id = checking_worker_type.checking_id')
                ->leftJoin('worker as checking_worker', 'checking_worker.id = checking_worker_type.worker_id')
                ->leftJoin('position as checking_position_worker', 'checking_position_worker.id = checking_worker.position_id')
                ->leftJoin('employee as checking_employee_worker', 'checking_employee_worker.id = checking_worker.employee_id')
                ->innerJoin('injunction', 'checking.id = injunction.checking_id')
                ->leftjoin('company_department', 'company_department.id = injunction.company_department_id')
                ->leftjoin('company', 'company.id = company_department.company_id')
                ->leftjoin('injunction_violation', 'injunction.id = injunction_violation.injunction_id')
                ->leftjoin('reason_danger_motion', 'reason_danger_motion.id = injunction_violation.reason_danger_motion_id')
                ->leftjoin('place', 'place.id = injunction_violation.place_id')
                ->leftjoin('mine', 'place.mine_id = mine.id')
                ->leftjoin('injunction_img', 'injunction_violation.id = injunction_img.injunction_violation_id')
                ->leftjoin('violator', 'injunction_violation.id = violator.injunction_violation_id')
                ->leftJoin('worker as violator_worker', 'violator_worker.id = violator.worker_id')
                ->leftJoin('position as violator_position_worker', 'violator_position_worker.id = violator_worker.position_id')
                ->leftJoin('employee as violator_employee_worker', 'violator_employee_worker.id = violator_worker.employee_id')
                ->leftjoin('paragraph_pb', 'injunction_violation.paragraph_pb_id = paragraph_pb.id')
                ->leftjoin('document', 'paragraph_pb.document_id = document.id')
                ->leftjoin('injunction_violation_status', 'injunction_violation.id = injunction_violation_status.injunction_violation_id')
                ->leftjoin('violation', 'injunction_violation.violation_id = violation.id')
                ->leftjoin('violation_type', 'violation.violation_type_id = violation_type.id')
                ->leftjoin('kind_violation', 'violation_type.kind_violation_id = kind_violation.id')
                ->leftjoin('correct_measures', 'injunction_violation.id = correct_measures.injunction_violation_id')
                ->leftjoin('operation', 'correct_measures.operation_id = operation.id')
                ->leftjoin('unit', 'operation.unit_id = unit.id')
                ->leftJoin('stop_pb', 'stop_pb.injunction_violation_id = injunction_violation.id')
                ->leftJoin('stop_pb_equipment', 'stop_pb.id = stop_pb_equipment.stop_pb_id')
                ->leftJoin('place as stop_place', 'stop_place.id = stop_pb.place_id')
                ->leftJoin('equipment as stop_equipment', 'stop_equipment.id = stop_pb_equipment.equipment_id')
                ->where($filter)
                ->andWhere(['injunction.kind_document_id' => self::KIND_PAB])
                ->asArray()
                ->all();
            $found_worker = PlaceCompanyDepartment::find()
                ->select([
                    'worker.id',
                    'position.title as position_title',
                    'employee.first_name as first_name',
                    'employee.last_name as last_name',
                    'employee.patronymic as patronymic'])
                ->leftJoin('worker', 'worker.company_department_id = place_company_department.company_department_id')
                ->leftJoin('position', 'worker.position_id = position.id')
                ->leftJoin('employee', 'worker.employee_id = employee.id')
                ->where(['like', 'position.title', 'Начальник участка'])
                ->orderBy('worker.date_start DESC')
                ->indexBy('id')
                ->asArray()
                ->limit(1)
                ->one();

//            $log->addData($found_data_abot_pab);
//            $log->addData($found_worker);

            foreach ($found_data_abot_pab as $pab_item) {
                $injunction_id = $pab_item['injunction_id'];
                $info_about_pab[$injunction_id]['injunction_id'] = $injunction_id;
                $info_about_pab[$injunction_id]['checking_id'] = $pab_item['checking_id'];
                $info_about_pab[$injunction_id]['inj_worker_id'] = $pab_item['inj_worker_id'];

                $ppk_id_rtn = $pab_item['rostex_number'];
                $ppk_id_instruct = $pab_item['instruct_id'];
                $ppk_id_nn = explode("_", $pab_item['nn_id']);
                $ppk_id_pab = explode("_", $pab_item['pab_id']);
                if ($ppk_id_rtn) {
                    $info_about_pab[$injunction_id]['ppk_id'] = $ppk_id_rtn;
                } else if ($ppk_id_instruct) {
                    $info_about_pab[$injunction_id]['ppk_id'] = $ppk_id_instruct;
                } else if ($ppk_id_nn and isset($ppk_id_nn[1])) {
                    $info_about_pab[$injunction_id]['ppk_id'] = $ppk_id_nn[1];
                } else if ($ppk_id_pab and isset($ppk_id_pab[1])) {
                    $info_about_pab[$injunction_id]['ppk_id'] = $ppk_id_pab[1];
                } else {
                    $info_about_pab[$injunction_id]['ppk_id'] = "";
                }

                $info_about_pab[$injunction_id]['company_department_id'] = $pab_item['company_department_id'];
                $info_about_pab[$injunction_id]['company_department_title'] = $pab_item['company_department_title'];

                if (!isset($info_about_pab[$injunction_id]['date_first_status'])) {
                    $injunction_first_status = InjunctionStatus::find()->where(['injunction_id' => $injunction_id])->one();
                    if ($injunction_first_status and $injunction_first_status->date_time) {
                        $info_about_pab[$injunction_id]['date_first_status'] = $injunction_first_status->date_time;
                        $info_about_pab[$injunction_id]['date_first_status_format'] = date('d.m.Y', strtotime($injunction_first_status['date_time']));
                        $info_about_pab[$injunction_id]['date_first_status_formated'] = date('d.m.Y', strtotime($injunction_first_status['date_time']));
                    } else {
                        $info_about_pab[$injunction_id]['date_first_status'] = null;
                        $info_about_pab[$injunction_id]['date_first_status_format'] = null;
                        $info_about_pab[$injunction_id]['date_first_status_formated'] = null;
                    }
                }

                $info_about_pab[$injunction_id]['status_id'] = $pab_item['injunction_status_id'];

                $info_about_pab[$injunction_id]['mine_title'] = $pab_item['mine_title'];
                $info_about_pab[$injunction_id]['base_checking'] = 'На основании Федерального закона от 21 июля 1997 года №116-ФЗ "О промышленной безопасности производственных объектов"';
                /******************** ЗАПИСЫВАЕМ АУДИТОРА ********************/
                if ($pab_item['worker_type_id'] == self::WORKER_TYPE_AUDITOR)                               //если тип работника аудитор тогда записываем его
                {
                    $info_about_pab[$injunction_id]['auditor_worker']['worker_id'] = $pab_item['worker_type_worker_id'];
                    $info_about_pab[$injunction_id]['auditor_worker']['position_title'] = $pab_item['auditor_position_title'];
                    $info_about_pab[$injunction_id]['auditor_worker']['full_name'] = "{$pab_item['auditor_last_name']} {$pab_item['auditor_first_name']} {$pab_item['auditor_patronymic']}";
                }
                if ($pab_item['worker_type_id'] == self::WORKER_TYPE_NARUSHITEL) {
                    $info_about_pab[$injunction_id]['violator_worker']['worker_id'] = $pab_item['violator_worker_id'];
                    $info_about_pab[$injunction_id]['violator_worker']['position_title'] = $pab_item['violator_position_title'];
                    $info_about_pab[$injunction_id]['violator_worker']['full_name'] = "{$pab_item['violator_last_name']} {$pab_item['violator_first_name']} {$pab_item['violator_patronymic']}";
                }
                /******************** ДАННЫЕ О НАРУШЕНИЯХ ********************/
                $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['injunction_violation_id'] = $pab_item['inj_viol_id'];
                $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['injunction_violation_place_title'] = $pab_item['inj_vio_place_title'];
                $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['violation_id'] = $pab_item['inj_vio_violation_id'];
                $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['violation_title'] = $pab_item['violation_title'];
                $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['injunction_violation_img'] = $pab_item['inj_img'];
                $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['document_id'] = $pab_item['document_id'];
                $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['document_title'] = $pab_item['document_title'];
                $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['paragraph_pb_text'] = $pab_item['paragraph_pb_text'];
                $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['dangerous'] = $pab_item['dangerous'];
                $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['probability'] = $pab_item['probability'];
                $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['reason_danger_motion_id'] = $pab_item['reason_danger_motion_id'];
                $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['reason_danger_motion_title'] = $pab_item['reason_danger_motion_title'];
                $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['injunction_description'] = $pab_item['injunction_description'];
                $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['violators']['worker_id'] = $pab_item['violator_worker_id'];
                $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['violators']['position_title'] = $pab_item['violator_position_title'];
                $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['violators']['full_name'] = "{$pab_item['violator_last_name']} {$pab_item['violator_first_name']} {$pab_item['violator_patronymic']}";

                $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['injunction_img'] = null;
                $injunction_imgs = InjunctionImg::findAll(['injunction_violation_id' => $pab_item['inj_viol_id']]);
                foreach ($injunction_imgs as $model_injunction_img) {
                    $injunction_img['injunction_img_id'] = $model_injunction_img->id;
                    $injunction_img['injunction_img_path'] = $model_injunction_img->img_path;
                    $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['injunction_img'][$model_injunction_img->id] = $injunction_img;
                }

                if (!empty($pab_item['correct_measures_id'])) {
                    $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['correct_measures'][$pab_item['correct_measures_id']]['correct_measures_id'] = $pab_item['correct_measures_id'];
                    $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['correct_measures'][$pab_item['correct_measures_id']]['operation_id'] = $pab_item['operation_id'];
                    $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['correct_measures'][$pab_item['correct_measures_id']]['operation_title'] = $pab_item['operation_title'];
                    $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['correct_measures'][$pab_item['correct_measures_id']]['correct_measures_description'] = $pab_item['correct_measures_description'];
                    $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['correct_measures'][$pab_item['correct_measures_id']]['correct_measures_value'] = $pab_item['correct_measures_value'];
                    $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['correct_measures'][$pab_item['correct_measures_id']]['unit_id'] = $pab_item['unit_id'];
                    $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['correct_measures'][$pab_item['correct_measures_id']]['unit_sort_title'] = $pab_item['unit_short_title'];
                    if ($pab_item['date_time_date_time'] != null) {
                        $date_time_correct_measures = $pab_item['date_time_date_time'];
                        $date_time_correct_measures_format = date('d.m.Y', strtotime($pab_item['date_time_date_time']));
                    } else {
                        $date_time_correct_measures = null;
                        $date_time_correct_measures_format = null;
                    }
                    $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['correct_measures'][$pab_item['correct_measures_id']]['date_time'] = $date_time_correct_measures;
                    $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['correct_measures'][$pab_item['correct_measures_id']]['date_time_format'] = $date_time_correct_measures_format;
                    $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['correct_measures'][$pab_item['correct_measures_id']]['result_correct_measures'] = $pab_item['result_correct_measures'];
                    $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['correct_measures'][$pab_item['correct_measures_id']]['correct_measures_status_id'] = $pab_item['correct_measures_status_id'];
                } else {
                    $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['correct_measures'] = null;
                }
                if ($pab_item['stop_pb_id'] !== null)                                                 //если есть идентификатор остановки не пуст тогда записываем остановки
                {
                    if ($pab_item['inj_vio_place_id'] !== $pab_item['stop_pb_place_id'])      //если место остановки отличается от места на которое выдано предписание то это 'Технологически вынужденная остановка'
                    {
                        $type_stop_pb = 'forced_stop_pb';
                    } else {                                                                                  //иначе это 'Приостановка работ'
                        $type_stop_pb = 'stop_pb';
                    }
                    $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['stop_pbs'][$type_stop_pb][$pab_item['stop_pb_id']]['stop_pb_id'] = $pab_item['stop_pb_id'];
                    $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['stop_pbs'][$type_stop_pb][$pab_item['stop_pb_id']]['place_id'] = $pab_item['stop_pb_place_id'];
                    $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['stop_pbs'][$type_stop_pb][$pab_item['stop_pb_id']]['place_title'] = $pab_item['stop_place_title'];
                    $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['stop_pbs'][$type_stop_pb][$pab_item['stop_pb_id']]['stop_date_time_start'] = $pab_item['stop_date_time_start'];
                    $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['stop_pbs'][$type_stop_pb][$pab_item['stop_pb_id']]['stop_date_time_start_formated'] = date('d.m.Y', strtotime($pab_item['stop_date_time_start']));
                    if ($pab_item['stop_date_time_end'] == null) {
                        $date_end = null;
                        $date_end_format = null;
                        $until = true;
                    } else {
                        $date_end = $pab_item['stop_date_time_end'];
                        $date_end_format = date('d.m.Y', strtotime($pab_item['stop_date_time_end']));
                        $until = false;
                    }
                    $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['stop_pbs'][$type_stop_pb][$pab_item['stop_pb_id']]['stop_date_time_end'] = $date_end;
                    $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['stop_pbs'][$type_stop_pb][$pab_item['stop_pb_id']]['stop_date_time_end_formated'] = $date_end_format;
                    $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['stop_pbs'][$type_stop_pb][$pab_item['stop_pb_id']]['until_complete_flag'] = $until;
                    $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['stop_pbs'][$type_stop_pb][$pab_item['stop_pb_id']]['equipments'][$pab_item['stop_pb_equipment_id']]['equipment_id'] = $pab_item['equipment_id'];
                    $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['stop_pbs'][$type_stop_pb][$pab_item['stop_pb_id']]['equipments'][$pab_item['stop_pb_equipment_id']]['equipment_title'] = $pab_item['stop_equipment_title'];
                } else {
                    $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['stop_pbs'] = null;
                }

                $info_about_pab[$injunction_id]['injunction_violation'][$pab_item['inj_viol_id']]['kind_violation_title'] = $pab_item['kind_violation_title'];
            }
            if (isset($info_about_pab)) {
                foreach ($info_about_pab as $info) {
                    $injunction_id = $info['injunction_id'];
                    if (!empty($found_worker)) {
                        $info_about_pab[$injunction_id]['responsible_worker']['worker_id'] = $found_worker['id'];
                        $info_about_pab[$injunction_id]['responsible_worker']['position_title'] = $found_worker['position_title'];
                        $info_about_pab[$injunction_id]['responsible_worker']['full_name'] = "{$found_worker['last_name']} {$found_worker['first_name']} {$found_worker['patronymic']}";
                    } else {
                        $info_about_pab[$injunction_id]['responsible_worker']['worker_id'] = null;
                        $info_about_pab[$injunction_id]['responsible_worker']['position_title'] = "";
                        $info_about_pab[$injunction_id]['responsible_worker']['full_name'] = "";
                    }
                }
                if ($flag_array) {
                    $result = $info_about_pab;
                } else {
                    $result = $info_about_pab[$injunction_id];
                }
            } else {
                throw new Exception("info_about_pab = null");
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Конец метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод GetArchiveChecking() - получение данных для архива проверок
     * @param null $data_post - JSON с датой от начала (от какого числа хотим увидеть архив проверок), дата окончания
     *                          (да какого числа хотим увидеть архив проверок)
     * @return array - массив со следующей структурой: [checking_id]
     *                                                          checking_id:
     *                                                          date_time:
     *                                                          checking_type_id:
     *                                                          [auditors]
     *                                                              [worker_id]
     *                                                                    worker_id:
     *                                                           place_id:
     *                                                           company_department_id:
     *                                                           [injunctions]
     *                                                                  [injunction_id]
     *                                                                           injunction_id:
     *
     *
     * @throws Exception
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Checking&method=GetArchiveChecking&subscribe=&data={"date_time_start":"2019-07-05","date_time_end":"2019-07-15"}
     *
     * @author Рудов Михаил <rms@pfsz.ru>ву
     * Created date: on 23.07.2019 16:41
     * @package frontend\controllers\industrial_safety
     *
     */
    public static function GetArchiveChecking($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $archive_checking = array();                                                                                         // Промежуточный результирующий массив
        $company_departments = array();
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('GetArchiveChecking. Данные с фронта не получены');
            }
            $warnings[] = 'GetArchiveChecking. Данные успешно переданы';
            $warnings[] = 'GetArchiveChecking. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GetArchiveChecking. Декодировал входные параметры';

            if (
                !property_exists($post_dec, 'date_time_start') ||
                !property_exists($post_dec, 'date_time_end')
            )                                                                                                       // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('GetArchiveChecking. Переданы некорректные входные параметры');
            }
            $date_time_start = date('Y-m-d', strtotime($post_dec->date_time_start));
            $date_time_end = date('Y-m-d', strtotime($post_dec->date_time_end));
            if (property_exists($post_dec, 'company_department_id')) {
                $company_department_id = $post_dec->company_department_id;

                $response = DepartmentController::FindDepartment($company_department_id);
                if ($response['status'] != 1) {
                    $errors[] = $response['errors'];
                    $warnings[] = $response['warnings'];
                    throw new Exception('GetArchiveChecking. Ошибка получения вложенных департаментов' . $company_department_id);
                }

                $company_departments = $response['Items'];
            }
            $warnings[] = 'GetArchiveChecking.Данные с фронта получены';
            $warnings[] = 'GetArchiveChecking. Начало метода';
            $found_checking_data = Checking::find()
                ->innerJoinWith('companyDepartment.company')
                ->innerJoinWith('checkingType')
                ->innerJoinWith('checkingPlaces.place')
                ->innerJoinWith('checkingWorkerTypes.worker.employee')
                ->innerJoinWith('checkingWorkerTypes.worker.position')
                ->joinWith('injunctions.kindDocument')
                ->where(['>=', 'checking.date_time_start', $date_time_start . ' 00:00:00'])
                ->andWhere(['<=', 'checking.date_time_start', $date_time_end . ' 23:59:59'])
                ->andWhere(['or',
                    ['in', 'injunction.kind_document_id', [1, 3, 5]],                                                         // 1-предписание, 3 предписание РТН, 5 рапорт
                    ['is', 'injunction.id', null]
                ])
                ->andFilterWhere(['in', 'checking.company_department_id', $company_departments])
                ->limit(5000)
                ->all();
            if ($found_checking_data) {
                foreach ($found_checking_data as $checkig_item) {
                    foreach ($checkig_item->checkingPlaces as $checkingPlace) {
                        $archive_checking[$checkig_item->id]['checking_id'] = $checkig_item->id;
                        $ppk_id_rtn = $checkig_item->rostex_number;
                        $ppk_id_instruct = $checkig_item->instruct_id;
                        if ($ppk_id_rtn) {
                            $archive_checking[$checkig_item->id]['ppk_id'] = $ppk_id_rtn;
                        } else if ($ppk_id_instruct) {
                            $archive_checking[$checkig_item->id]['ppk_id'] = $ppk_id_instruct;
                        } else {
                            $archive_checking[$checkig_item->id]['ppk_id'] = "";
                        }
                        $archive_checking[$checkig_item->id]['date_time'] = date('d.m.Y H:i:s', strtotime($checkig_item->date_time_end));
                        $archive_checking[$checkig_item->id]['checking_type_title'] = $checkig_item->checkingType->title;
                        $archive_checking[$checkig_item->id]['auditor']['worker_id'] = $checkig_item->checkingWorkerTypes[0]->worker->id;
                        $full_name = Assistant::GetFullName($checkig_item->checkingWorkerTypes[0]->worker->employee->first_name, $checkig_item->checkingWorkerTypes[0]->worker->employee->patronymic, $checkig_item->checkingWorkerTypes[0]->worker->employee->last_name);
                        $archive_checking[$checkig_item->id]['auditor']['worker_full_name'] = $full_name;
                        $archive_checking[$checkig_item->id]['auditor']['worker_position_title'] = $checkig_item->checkingWorkerTypes[0]->worker->position->title;
                        $archive_checking[$checkig_item->id]['auditor']['worker_staff_number'] = $checkig_item->checkingWorkerTypes[0]->worker->tabel_number;
                        if (!isset($archive_checking[$checkig_item->id]['place_title'])) {
                            $archive_checking[$checkig_item->id]['place_title'] = $checkingPlace->place->title;
                        } else {
                            $archive_checking[$checkig_item->id]['place_title'] .= "; " . $checkingPlace->place->title;
                        }

                        $archive_checking[$checkig_item->id]['department_title'] = $checkig_item->companyDepartment->company->title;
                        $archive_checking[$checkig_item->id]['company_department_id'] = $checkig_item->company_department_id;
                    }
                    if (empty($checkig_item->injunctions)) {
                        $archive_checking[$checkig_item->id]['injunctions'] = null;
                    } else {
                        foreach ($checkig_item->injunctions as $injunction) {
                            $archive_checking[$checkig_item->id]['injunctions'][$injunction->id]['injunction_id'] = $injunction->id;
                            $archive_checking[$checkig_item->id]['injunctions'][$injunction->id]['kind_document_id'] = $injunction->kind_document_id;
                            $archive_checking[$checkig_item->id]['injunctions'][$injunction->id]['kind_document_title'] = $injunction->kindDocument->title;
                        }
                    }

                }
            }
        } catch (Throwable $exception) {
            $warnings[] = 'GetArchiveChecking. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetArchiveChecking. Конец метода';

        return array('Items' => $archive_checking, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод ReasonDangerMoutions() - возвращает список причин опасных действий
     * @return array - возвращает массив со структурой: [reason_danger_motion_id]
     *                                                              reason_danger_motion_id:
     *                                                              reason_danger_motion_title:
     *                                                              [parent_reason_danger_motion_id]                    -если не null
     *                                                                          [reason_danger_motion_id]
     *                                                                                      reason_danger_motion_id:
     *                                                                                      reason_danger_motion_title:
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Checking&method=ReasonDangerMotions&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 29.07.2019 13:41
     */
    public static function ReasonDangerMotions()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $dangers_motion_result = array();                                                                              // Промежуточный результирующий массив
        try {
            $warnings[] = 'ReasonDangerMotions. Начало метода';
            $warnings[] = 'ReasonDangerMotions. Поиск причин опасных действий';
            /******************** ПОИСК В БД ПРИЧИН ОПАСНЫХ ДЕЙСТВИЙ ********************/
            $found_danger_motions = ReasonDangerMotion::find()
                ->limit(1000)
                ->indexBy('id')
                ->asArray()
                ->all();
            if ($found_danger_motions)                                                                                 //если данные найдены тогда перебираем их и формируем структуру
            {
                $warnings[] = 'ReasonDangerMotions. Причины опасных действий найдены';
                $warnings[] = 'ReasonDangerMotions. Формирование стрктуры';
                foreach ($found_danger_motions as $danger_motion_item) {                                              //перебор найденных данных
                    $current_motion = $danger_motion_item;
                    while (!empty($current_motion['parent_reason_danger_motion_id']))                                //до тех пор пока есть родитель у записи записываем вложенно
                    {
                        $dangers_motion_result[$current_motion['parent_reason_danger_motion_id']]['nested_danger_motion'][$current_motion['id']]['reason_danger_motion_id'] = $current_motion['id'];
                        $dangers_motion_result[$current_motion['parent_reason_danger_motion_id']]['nested_danger_motion'][$current_motion['id']]['reason_danger_motion_title'] = $current_motion['title'];
                        $current_motion = 1;
                    }
                    if ($danger_motion_item['parent_reason_danger_motion_id'] == null) {
                        $dangers_motion_result[$danger_motion_item['id']]['reason_danger_motion_id'] = $danger_motion_item['id'];
                        $dangers_motion_result[$danger_motion_item['id']]['reason_danger_motion_title'] = $danger_motion_item['title'];
                    }
                }
                $warnings[] = 'ReasonDangerMotions. Стркутруа сформирована';
            } else {
                throw new Exception('ReasonDangerMotions. Данные не найдены');
            }
        } catch (Throwable $exception) {
            $errors[] = 'ReasonDangerMotions. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'ReasonDangerMotions. Конец метода';
        $result = $dangers_motion_result;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод KindStopPb() - возвращает список типов простоев
     * @return array - возвращает массив со структурой: [kind_stop_pb_id]
     *                                                              id:
     *                                                              title:
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Checking&method=KindStopPb&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 29.07.2019 13:41
     */
    public static function KindStopPb()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $kind_stop_pb_result = array();                                                                              // Промежуточный результирующий массив
        try {
            $warnings[] = 'KindStopPb. Начало метода';
            $warnings[] = 'KindStopPb. Поиск типов простоев';
            /******************** ПОИСК В БД ТИПОВ ПРОСТОЕВ ********************/
            $found_kind_stop_pb = KindStopPb::find()
                ->limit(10000)
                ->indexBy('id')
                ->asArray()
                ->all();
            if ($found_kind_stop_pb)                                                                                 //если данные найдены тогда перебираем их и формируем структуру
            {
                $warnings[] = 'KindStopPb. Типы простоев найдены';
                $warnings[] = 'KindStopPb. Формирование стрктуры';
                foreach ($found_kind_stop_pb as $kind_stop_pb_item) {                                              //перебор найденных данных

                    $kind_stop_pb_result[$kind_stop_pb_item['id']]['id'] = $kind_stop_pb_item['id'];
                    $kind_stop_pb_result[$kind_stop_pb_item['id']]['title'] = $kind_stop_pb_item['title'];

                }
                $warnings[] = 'KindStopPb. Стркутруа сформирована';
            } else {
                throw new Exception('KindStopPb. Данные не найдены');
            }
        } catch (Throwable $exception) {
            $errors[] = 'KindStopPb. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'KindStopPb. Конец метода';
        $result = $kind_stop_pb_result;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод AddResultCorrectMeasures() - добавляем результат корректирующего мероприятия
     * @param null $data_post - JSON массив с данными: идентификатор корректирующего мероприятия, результат корректирующего мероприятияя
     * @return array - стаднартный массив выходных данных
     *
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Checking&method=AddResultCorrectMeasures&subscribe=&data={%22correct_measures_id%22:8,%22result_correct_measures%22:%22%D0%A2%D0%B5%D1%81%D1%82%D0%B8%D1%80%D0%BE%D0%B2%D0%B0%D0%BD%D0%B8%D0%B5%20%D0%BF%D1%80%D0%BE%D0%B9%D0%B4%D0%B5%D0%BD%D0%BE%20%D1%83%D0%B4%D0%BE%D0%B2%D0%BB%D0%B5%D1%82%D0%B2%D0%BE%D1%80%D0%B8%D1%82%D0%B5%D0%BB%D1%8C%D0%BD%D0%BE%22}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 29.07.2019 16:01
     */
    public static function AddResultCorrectMeasures($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result_correct = array();                                                                                         // Промежуточный результирующий массив
        if ($data_post !== NULL && $data_post !== '') {
            $warnings[] = 'AddResultCorrectMeasures. Начало метода';
            $warnings[] = 'AddResultCorrectMeasures. Данные успешно переданы';
            $warnings[] = 'AddResultCorrectMeasures. Входной массив данных' . $data_post;
            try {
                $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
                $warnings[] = 'AddResultCorrectMeasures. Декодировал входные параметры';
                if (
                    property_exists($post_dec, 'correct_measures_id') &&
                    property_exists($post_dec, 'result_correct_measures')
                )                                                                                                       // Проверяем наличие в нем нужных нам полей
                {
                    $warnings[] = 'AddResultCorrectMeasures.Данные с фронта получены';
                    $correct_measures_id = $post_dec->correct_measures_id;
                    $result_correct_measures = $post_dec->result_correct_measures;
                    $found_correct_measures = CorrectMeasures::findOne(['id' => $correct_measures_id]);                   //поиск корректирующего мероприятия
                    if ($found_correct_measures) {                                                                      //если корректирующее мероприятие найдено тогда добалвеяем результат и ставим меняем статус
                        $found_correct_measures->result_correct_measures = $result_correct_measures;
                        $found_correct_measures->status_id = self::STATUS_INACTIVE;
                        if ($found_correct_measures->save()) {
                            $warnings[] = 'AddResultCorrectMeasures. Резульат успешно сохранён и статус изменён';
                        } else {
                            throw new Exception('AddResultCorrectMeasures. Ошибка при добалвлении результата корректирующиму мероприятию');
                        }
                    } else {
                        throw new Exception('AddResultCorrectMeasures. Не найдено корректирующее мероприятие');
                    }
                } else {
                    throw new Exception('AddResultCorrectMeasures. Переданы некорректные входные параметры');
                }
            } catch (Throwable $exception) {
                $errors[] = 'AddResultCorrectMeasures. Исключение';
                $errors[] = $exception->getMessage();
                $errors[] = $exception->getLine();
                $status *= 0;
            }
        } else {
            $errors[] = 'AddResultCorrectMeasures. Данные с фронта не получены';
            $status *= 0;
        }
        $warnings[] = 'AddResultCorrectMeasures. Конец метода';
        $result = $result_correct;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetEquipment() - получает все оборудования из таблицы object где object_table->equipment
     * @return array - массив со следующей структурой [equipment_id]
     *                                                          equipment_id:
     *                                                          equipment_title:
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Checking&method=GetEquipment&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 05.08.2019 14:01
     */
    public static function GetEquipment()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $get_equipment = array();                                                                                         // Промежуточный результирующий массив
        $warnings[] = 'GetEquipment. Начало метода';
        try {
            $warnings[] = 'GetEquipment. Поиск необходимых данных и формирование структуры';
            $found_data_equipment = Equipment::find()
                ->limit(5000)
                ->orderBy('title');
            foreach ($found_data_equipment->each() as $equipment) {
                $get_equipment[$equipment->id]['equipment_id'] = $equipment->id;
                $get_equipment[$equipment->id]['equipment_title'] = $equipment->title;
            }
            $warnings[] = 'GetEquipment. Структура сформирована';
        } catch (Throwable $exception) {
            $errors[] = 'GetEquipment. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetEquipment. Конец метода';
        $result = $get_equipment;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetEquipmentGroup() - Справочник оборудований сгрупиированный по типам объекта
     * @return array - массив со структурой: [object_types]
     *                                                  [object_type_id]
     *                                                              equipment_type_id:
     *                                                              equipment_type:
     *                                                              [equipments]
     *                                                                      [equipment_id]
     *                                                                              equipment_id:
     *                                                                              equipment_title:
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Checking&method=GetEquipmentGroup&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 30.10.2019 11:04
     */
    public static function GetEquipmentGroup()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetEquipmentGroup. Начало метода';
        try {
            $group_equipments = Equipment::find()
                ->joinWith('object.objectType')
                ->limit(5000)
                ->orderBy('title');
            foreach ($group_equipments->each() as $group_equipment) {
                $equip_id = $group_equipment->id;
                $obj_type_id = $group_equipment->object->objectType->id;
                $result['object_types'][$obj_type_id]['equipment_type_id'] = $group_equipment->object->objectType->id;
                $result['object_types'][$obj_type_id]['equipment_type'] = $group_equipment->object->objectType->title;
                $result['object_types'][$obj_type_id]['equipments'][$equip_id]['equipment_id'] = $equip_id;
                $result['object_types'][$obj_type_id]['equipments'][$equip_id]['equipment_title'] = $group_equipment->title;
            }

        } catch (Throwable $exception) {
            $errors[] = 'GetEquipmentGroup. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetEquipmentGroup. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод DeletedChecking() - удаление проверки
     * @param null $data_post - JSON с данными: идентификатор проверки
     * @return array - стандартный массив выходных данных
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.09.2019 11:26
     */
    public static function DeletedChecking($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $deleted = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = 'DeletedChecking. Начало метода';

        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('DeletedChecking. Не переданы входные параметры');
            }
            $warnings[] = 'DeletedChecking. Данные успешно переданы';
            $warnings[] = 'DeletedChecking. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'DeletedChecking. Декодировал входные параметры';
            if (!property_exists($post_dec, 'checking_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('DeletedChecking. Переданы некорректные входные параметры');
            }
            $warnings[] = 'DeletedChecking. Данные с фронта получены';
            $checking_id = $post_dec->checking_id;

            $find_injunction_violations = (new Query())
                ->select('injunction.id as injunction_id,injunction_violation.id as injunction_violation_id')
                ->from('injunction_violation')
                ->innerJoin('injunction', 'injunction.id=injunction_violation.injunction_id')
                ->where(['checking_id' => $checking_id])
                ->all();

            $injunction_ids = [];
            $injunction_violation_ids = [];
            if ($find_injunction_violations) {
                foreach ($find_injunction_violations as $find_injunction_violation) {
                    $injunction_violation_ids[] = $find_injunction_violation['injunction_violation_id'];
                    $injunction_ids[] = $find_injunction_violation['injunction_id'];
                }
            }
            $find_order_operation = false;
            if (!empty($injunction_ids) or !empty($injunction_violation_ids)) {
                $find_order_operation = OrderOperation::find()
                    ->where(['injunction_id' => $injunction_ids])
                    ->orWhere(['injunction_violation_id' => $injunction_violation_ids])
                    ->asArray()
                    ->all();
            }
            if (!$find_order_operation) {
                InjunctionViolation::deleteAll(['id' => $injunction_violation_ids]);
                Checking::deleteAll(['id' => $checking_id]);
            } else {
                throw new Exception('DeletedChecking. Удаление документа не возможно. Данный документ используется в нарядной системе');
            }


        } catch (Throwable $exception) {

            $errors[] = 'DeletedChecking. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'DeletedChecking. Конец метода';
        $result = $deleted;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод DeletedInjunction() - удаление предписания/ПАБа
     * @param null $data_post - JSON с данными: идентификатор предписания/ПАБа
     * @return array - стандартный массив выходных данных
     * http://127.0.0.1/read-manager-amicum?controller=industrial_safety%5CChecking&method=DeletedInjunction&subscribe=&data=%7B%22injunction_id%22%3A%226649309%22%7D
     * @package frontend\controllers\industrial_safety
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.09.2019 11:26
     */
    public static function DeletedInjunction($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $deleted = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = 'DeletedInjunction. Начало метода';

        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('DeletedInjunction. Не переданы входные параметры');
            }
            $warnings[] = 'DeletedInjunction. Данные успешно переданы';
            $warnings[] = 'DeletedInjunction. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'DeletedInjunction. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'injunction_id')
            )                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('DeletedInjunction. Переданы некорректные входные параметры');
            }
            $warnings[] = 'DeletedInjunction. Данные с фронта получены';

            $injunction_id = $post_dec->injunction_id;

            $find_injunction_violations = InjunctionViolation::find()
                ->select('id')
                ->where(['injunction_id' => $injunction_id])
                ->asArray()
                ->all();

            $injunction_violation_ids = [];
            if ($find_injunction_violations) {
                foreach ($find_injunction_violations as $find_injunction_violation) {
                    $injunction_violation_ids[] = $find_injunction_violation['id'];
                }
            }

            $find_order_operation = OrderOperation::find()
                ->where(['injunction_id' => $injunction_id])
                ->orWhere(['injunction_violation_id' => $injunction_violation_ids])
                ->asArray()
                ->all();

            if (!$find_order_operation) {
                InjunctionViolation::deleteAll(['id' => $injunction_violation_ids]);
                Injunction::deleteAll(['id' => $injunction_id]);
            } else {
                throw new Exception('DeletedInjunction. Удаление документа не возможно. Данный документ используется в нарядной системе');
            }


        } catch (Throwable $exception) {

            $errors[] = 'DeletedInjunction. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'DeletedInjunction. Конец метода';
        $result = $deleted;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод ChangeCheckingType() - Метод смены типа проверки
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * Входные обязательные параметры:
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Checking&method=ChangeCheckingType&subscribe=&data={"checking_id":10,"checking_type":1}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 30.09.2019 9:20
     */
    public static function ChangeCheckingType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $change_checking_type = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = 'ChangeCheckingType. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('ChangeCheckingType. Не переданы входные параметры');
            }
            $warnings[] = 'ChangeCheckingType. Данные успешно переданы';
            $warnings[] = 'ChangeCheckingType. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'ChangeCheckingType. Декодировал входные параметры';
            if (!property_exists($post_dec, 'checking_id') ||
                !property_exists($post_dec, 'checking_type'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('ChangeCheckingType. Переданы некорректные входные параметры');
            }
            $warnings[] = 'ChangeCheckingType. Данные с фронта получены';
            $checking_id = $post_dec->checking_id;
            $checking_type = $post_dec->checking_type;
            $changeable_checking = Checking::updateAll(['checking_type_id' => $checking_type], ['id' => $checking_id]);
            if ($changeable_checking != 0) {
                $warnings[] = 'ChangeCheckingType. Тип проверки успешно изменён';
            } else {
                throw new Exception('ChangeCheckingType. Ошибка при изменении типа проверки');
            }
        } catch (Throwable $exception) {
            $errors[] = 'ChangeCheckingType. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'ChangeCheckingType. Конец метода';
        $result = $change_checking_type;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    /**
     * Метод GetInjunctionStatistic() - Метод получения статистики по предписаниям
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Checking&method=GetInjunctionStatistic&subscribe=&data={%22date_start%22:%222018-10-02%22,%22date_end%22:%222019-10-03%22,%22company_department_id%22:20028766}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 27.09.2019 18:45
     */
    public static function GetInjunctionStatistic($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $injunction_statistic = array();                                                                                // Промежуточный результирующий массив
        $all_checking = null;
        $all_injunction = null;
        $warnings[] = 'GetInjunctionStatistic. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('GetInjunctionStatistic. Не переданы входные параметры');
            }
            $warnings[] = 'GetInjunctionStatistic. Данные успешно переданы';
            $warnings[] = 'GetInjunctionStatistic. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GetInjunctionStatistic. Декодировал входные параметры';
            if (!property_exists($post_dec, 'date_start') ||
                !property_exists($post_dec, 'date_end') ||
                !property_exists($post_dec, 'company_department_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('GetInjunctionStatistic. Переданы некорректные входные параметры');
            }
            $date_start = date('Y-m-d', strtotime($post_dec->date_start));
            $date_end = date('Y-m-d', strtotime($post_dec->date_end));
            $company_department_id = $post_dec->company_department_id;
            $warnings[] = 'GetInjunctionStatistic. Данные с фронта получены';
            $response = DepartmentController::FindDepartment($company_department_id);

            if ($response['status'] == 1) {
                $company_departments_ids = $response['Items'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception('GetInjunctionStatistic. Ошибка получения вложенных департаментов' . $company_department_id);
            }

            $result_get_checking = self::GetCheckingCounter($date_start, $date_end, $company_departments_ids);

            if ($result_get_checking['status'] == 1) {
                $injunction_statistic['counters'] = $result_get_checking['Items'];
                $warnings[] = $result_get_checking['warnings'];
            } else {
                $warnings[] = $result_get_checking['warnings'];
                $errors[] = $result_get_checking['errors'];
            }

            $result_get_checking_statistic_by_month = self::GetInjunctionStatisticByMonth($date_start, $date_end, $company_departments_ids);
            if ($result_get_checking_statistic_by_month['status'] == 1) {
                $injunction_statistic['statistic'] = $result_get_checking_statistic_by_month['Items'];
                $warnings[] = $result_get_checking_statistic_by_month['warnings'];
            } else {
                $warnings[] = $result_get_checking_statistic_by_month['warnings'];
                $errors[] = $result_get_checking_statistic_by_month['errors'];
            }


            $result_get_checking_statistic_by_year = self::GetInjunctionStatisticByYear($company_departments_ids);

            if ($result_get_checking_statistic_by_year['status'] == 1) {
                $injunction_statistic['statistic_by_year'] = $result_get_checking_statistic_by_year['Items'];
                $warnings[] = $result_get_checking_statistic_by_year['warnings'];
            } else {
                $warnings[] = $result_get_checking_statistic_by_year['warnings'];
                $errors[] = $result_get_checking_statistic_by_year['errors'];
            }

            $result_statistic__pab_by_company_department = self::GetPabByFilter($date_start, $date_end, $company_departments_ids);
            if ($result_statistic__pab_by_company_department['status'] == 1) {
                $injunction_statistic['statistic__pab_by_company_department'] = $result_statistic__pab_by_company_department['Items'];
                $warnings[] = $result_statistic__pab_by_company_department['warnings'];
            } else {
                $warnings[] = $result_statistic__pab_by_company_department['warnings'];
                $errors[] = $result_statistic__pab_by_company_department['errors'];
            }

            $result_get_checking_statistic_by_risk = self::GetInjunctionsRisk($date_start, $date_end, $company_departments_ids);
            if ($result_get_checking_statistic_by_risk['status'] == 1) {
                $injunction_statistic['statistic_risk'] = $result_get_checking_statistic_by_risk['Items'];
                $warnings[] = $result_get_checking_statistic_by_risk['warnings'];
            } else {
                $warnings[] = $result_get_checking_statistic_by_risk['warnings'];
                $errors[] = $result_get_checking_statistic_by_risk['errors'];
            }

        } catch (Throwable $exception) {
            $errors[] = 'GetInjunctionStatistic. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetInjunctionStatistic. Конец метода';
        $result = $injunction_statistic;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetCheckingCounter() - Метод расчёта блока 1 в статистике проведённых проверок применяется в методе GetInjunctionStatistic
     * @param $date_start -
     * @param $date_end
     * @param $company_departments_ids
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 02.10.2019 17:47
     */
    public static function GetCheckingCounter($date_start, $date_end, $company_departments_ids)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $count_inj = 0;
        $warnings[] = 'GetCheckingCounter. Начало метода';
        try {
            $count_checking_with_inj = Checking::find()
                ->select(['checking.company_department_id as company_department_id', 'checking.id as checking_id'])
                ->innerJoin('injunction', 'injunction.checking_id = checking.id')
                ->innerJoin('checking_worker_type', 'checking_worker_type.checking_id = checking.id')
                ->where(['in', 'checking.company_department_id', $company_departments_ids])
                ->andWhere(['in', 'injunction.kind_document_id', [1, 3]])
                ->andWhere(['between', 'checking.date_time_start', $date_start, date('Y-m-d', strtotime($date_end . "+ 1 day"))])
                ->groupBy('company_department_id,checking_id')
                ->asArray()
                ->all();

            foreach ($count_checking_with_inj as $inj) {
                $count_inj++;
            }
            $result['count_with_inj'] = $count_inj;
            unset($count_checking_with_inj);
            $count_checking_with_pab = Checking::find()
                ->select(['checking.company_department_id as company_department_id', 'injunction.id as injunction_id'])
                ->innerJoin('injunction', 'injunction.checking_id = checking.id')
                ->innerJoin('checking_worker_type', 'checking_worker_type.checking_id = checking.id')
                ->where(['in', 'checking.company_department_id', $company_departments_ids])
                ->where(['in', 'injunction.company_department_id', $company_departments_ids])
                ->andWhere(['injunction.kind_document_id' => self::KIND_PAB])
                ->andWhere(['between', 'checking.date_time_start', $date_start, date('Y-m-d', strtotime($date_end . "+ 1 day"))])
                ->groupBy('company_department_id,injunction_id')
                ->asArray()
                ->all();
            $count_pab = 0;
            foreach ($count_checking_with_pab as $pab) {
                $count_pab++;
            }
            $result['count_with_pab'] = $count_pab;
            $checking_without_inj = Checking::find()
                ->select(['count(checking.id) as count_checking', 'injunction.id as inj_id'])
                ->leftJoin('injunction', 'injunction.checking_id = checking.id')
                ->innerJoin('checking_worker_type', 'checking_worker_type.checking_id = checking.id')
                ->where(['in', 'checking.company_department_id', $company_departments_ids])
                ->andWhere(['between', 'checking.date_time_start', $date_start, date('Y-m-d', strtotime($date_end . "+ 1 day"))])
                ->groupBy('inj_id')
                ->having(['is', 'inj_id', null])
                ->asArray()
                ->limit(1)
                ->one();
            if (!empty($checking_without_inj)) {
                $count_without_anything = (int)$checking_without_inj['count_checking'];
            } else {
                $count_without_anything = 0;
            }
            $result['count_without_anything'] = $count_without_anything;
            $result['count_checking'] = (int)$count_inj + $count_without_anything;
        } catch (Throwable $exception) {
            $errors[] = 'GetCheckingCounter. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetCheckingCounter. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetInjunctionStatisticByMonth() - Метод расчёта статстистики предписаний с замечаниями и без по месяцам
     *                                          и расчёт колчества выданных ПАБ на участке (применяется в методе GetInjunctionStatistic)
     * @param $date_start
     * @param $date_end
     * @param $company_departments_ids
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 02.10.2019 17:47
     */
    public static function GetInjunctionStatisticByMonth($date_start, $date_end, $company_departments_ids)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetInjunctionStatisticbyMonth. Начало метода';
        try {
            $warnings[] = 'GetInjunctionStatisticbyMonth. $company_departments_ids';
            $warnings[] = $company_departments_ids;
            for ($i = 1; $i <= 12; $i++) {
                $result['without_inj'][$i] = 0;
                $result['with_inj'][$i] = 0;
                $result['with_pab'][$i] = 0;
            }
            $checking_without_inj = Checking::find()
                ->select(['count(checking.id) count_checking',
                    'injunction.id as inj_id',
                    'MONTH(checking.date_time_start) as month_checking'])
                ->leftJoin('(select id, checking_id from injunction where injunction.kind_document_id=1 or injunction.kind_document_id=3) injunction', 'injunction.checking_id = checking.id')
                ->innerJoin('checking_worker_type', 'checking_worker_type.checking_id = checking.id')
                ->where(['in', 'checking.company_department_id', $company_departments_ids])
                ->andWhere(['is not', 'checking.instruct_id', null])
                ->andWhere(['between', 'checking.date_time_start', $date_start, $date_end])
                ->groupBy('inj_id,MONTH(checking.date_time_start)')
                ->having(['is', 'inj_id', null])
                ->asArray()
                ->all();
            foreach ($checking_without_inj as $without_inj_by_month) {
                $result['without_inj'][$without_inj_by_month['month_checking']] = (int)$without_inj_by_month['count_checking'];
            }
            $checking_with_inj = Checking::find()
                ->select(['checking.company_department_id as company_department_id',
                    'MONTH(checking.date_time_start) as month_checking', 'checking.id as checking_id'])
                ->innerJoin('injunction', 'injunction.checking_id = checking.id')
                ->innerJoin('checking_worker_type', 'checking_worker_type.checking_id = checking.id')
                ->where(['in', 'checking.company_department_id', $company_departments_ids])
                ->andWhere(['in', 'injunction.kind_document_id', [1, 3]])
                ->andWhere(['between', 'checking.date_time_start', $date_start, $date_end])
                ->groupBy('company_department_id,MONTH(checking.date_time_start),checking_id')
                ->asArray()
                ->all();
            foreach ($checking_with_inj as $with_inj_by_month) {
                $result['with_inj'][$with_inj_by_month['month_checking']]++;
            }

            $checking_with_pab = Checking::find()
                ->select(['checking.company_department_id as company_department_id', 'MONTH(checking.date_time_start) as month_checking', 'injunction.id as injunction_id'])
                ->innerJoin('injunction', 'injunction.checking_id = checking.id')
                ->innerJoin('checking_worker_type', 'checking_worker_type.checking_id = checking.id')
                ->where(['in', 'injunction.company_department_id', $company_departments_ids])
                ->andWhere(['injunction.kind_document_id' => self::KIND_PAB])
                ->andWhere(['between', 'checking.date_time_start', $date_start, $date_end])
                ->groupBy('company_department_id,MONTH(checking.date_time_start),injunction_id')
                ->asArray()
                ->all();
            foreach ($checking_with_pab as $with_pab_by_month) {
                $result['with_pab'][$with_pab_by_month['month_checking']]++;
            }

        } catch (Throwable $exception) {
            $errors[] = 'GetInjunctionStatisticbyMonth. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetInjunctionStatisticbyMonth. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetInjunctionStatisticByYear() - Метод расчёта статсистики проведённых проверок по годам  без замечаний
     *                                      применяется в методе GetInjunctionStatistic
     * @param $company_departments_ids
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 02.10.2019 17:49
     */
    public static function GetInjunctionStatisticByYear($company_departments_ids)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetInjunctionStatisticByYear. Начало метода';
        try {
            $count_checking_without_injunction_by_year = Checking::find()
                ->select([
                    'count(checking.id) as count_checking_id',
                    'injunction.id as inj_id',
                    'YEAR(checking.date_time_start) as year_checking'
                ])
                ->leftJoin('(select id, checking_id from injunction where kind_document_id=1 or kind_document_id=3) injunction', 'checking.id = injunction.checking_id')
                ->innerJoin('checking_worker_type', 'checking_worker_type.checking_id = checking.id')
                ->where(['in', 'checking.company_department_id', $company_departments_ids])
                ->andWhere(['is not', 'checking.instruct_id', null])
                ->groupBy(['YEAR(checking.date_time_start)', 'inj_id'])
                ->having(['is', 'inj_id', null])
                ->asArray()
                ->all();
            foreach ($count_checking_without_injunction_by_year as $checking_by_year) {
                $count_checking_by_year_without[$checking_by_year['year_checking']] = $checking_by_year['count_checking_id'];
            }

            $count_checking_by_years = Checking::find()
                ->select([
                    'count(checking.id) as count_checking_id',
                    'YEAR(checking.date_time_start) as year_checking'
                ])
                ->leftJoin('injunction', 'injunction.checking_id = checking.id')
                ->where(['in', 'checking.company_department_id', $company_departments_ids])
                ->andWhere(['or',
                    ['in', 'injunction.kind_document_id', [1, 3]],
                    ['is', 'injunction.id', null]
                ])
                ->groupBy(['YEAR(checking.date_time_start)'])
                ->asArray()
                ->all();
            foreach ($count_checking_by_years as $checking_by_year) {
                $count_checking_by_year[$checking_by_year['year_checking']] = $checking_by_year['count_checking_id'];
            }
            if (isset($count_checking_by_year)) {
                foreach ($count_checking_by_year as $year => $value_checking) {
                    if ($value_checking != 0 and isset($count_checking_by_year_without[$year])) {
                        $result[$year] = round(($count_checking_by_year_without[$year] / $value_checking) * 100, 1);
                    } else {
                        $result[$year] = 0;
                    }
                }
            } else {
                $result = (object)array();
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetInjunctionStatisticByYear. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetInjunctionStatisticByYear. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetInjunctionsRisk() - Метод расчёта статстики нарушений по степени опасности
     *                              применяется в методе GetInjunctionStatistic
     * @param $date_start
     * @param $date_end
     * @param $company_departments_ids
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 02.10.2019 17:50
     */
    public static function GetInjunctionsRisk($date_start, $date_end, $company_departments_ids)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $light_counter = 0;
        $medium_counter = 0;
        $high_counter = 0;
        $extream_counter = 0;
        $warnings[] = 'GetInjunctionsRisk. Начало метода';
        try {
            $get_checking_with_inj = Checking::find()
                ->select(['checking.id as checking_id',
                    'injunction.id as inj_id',
                    'injunction_violation.probability',
                    'injunction_violation.gravity'])
                ->innerJoin('injunction', 'injunction.checking_id = checking.id')
                ->innerJoin('injunction_violation', 'injunction.id = injunction_violation.injunction_id')
                ->where(['in', 'checking.company_department_id', $company_departments_ids])
                ->andWhere(['in', 'injunction.kind_document_id', [1, 3]])
                ->andWhere(['between', 'checking.date_time_start', $date_start, date('Y-m-d', strtotime($date_end . "+ 1 day"))])
                ->asArray()
                ->all();
            foreach ($get_checking_with_inj as $inj_risk) {
                $risk = self::GetRisk($inj_risk['probability'], $inj_risk['gravity']);
                switch ($risk) {
                    case 'light':
                        $light_counter++;
                        break;
                    case 'medium':
                        $medium_counter++;
                        break;
                    case 'high':
                        $high_counter++;
                        break;
                    case 'extream':
                        $extream_counter++;
                        break;
                }
            }
            $result['light'] = $light_counter;
            $result['medium'] = $medium_counter;
            $result['high'] = $high_counter;
            $result['extream'] = $extream_counter;
            $result['unacceptable_level_of_risk'] = $extream_counter + $high_counter;
        } catch (Throwable $exception) {
            $errors[] = 'GetInjunctionsRisk. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetInjunctionsRisk. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetPabByFilter() - Метод расчёта ПАБ оп направлениям (по участку, по сотрудникам, по направлению)
     *                          применяется в методе GetInjunctionStatistic
     * @param $date_start
     * @param $date_end
     * @param $company_departments_ids
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * Входные обязательные параметры:
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 02.10.2019 17:50
     */
    public static function GetPabByFilter($date_start, $date_end, $company_departments_ids)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetPabByFilter. Начало метода';
        try {
            $checking_with_pab = Checking::find()
                ->select(['checking.company_department_id as company_department_id',
                    'injunction.id as inj_id',
                    'company.title as company_department_title'])
                ->innerJoin('injunction', 'injunction.checking_id = checking.id')
                ->innerJoin('company_department', 'company_department.id = injunction.company_department_id')
                ->innerJoin('company', 'company.id = company_department.company_id')
                ->where(['in', 'injunction.company_department_id', $company_departments_ids])
                ->andWhere(['injunction.kind_document_id' => self::KIND_PAB])
                ->andWhere(['between', 'checking.date_time_start', $date_start, date('Y-m-d', strtotime($date_end . "+ 1 day"))])
                ->asArray()
                ->all();
            foreach ($checking_with_pab as $checking_pab) {
                if (!isset($result['count_PAB_by_company_department'][$checking_pab['company_department_title']])) {
                    $result['count_PAB_by_company_department'][$checking_pab['company_department_title']] = 1;
                } else {
                    $result['count_PAB_by_company_department'][$checking_pab['company_department_title']]++;
                }
            }
            if (empty($result['count_PAB_by_company_department'])) {
                $result['count_PAB_by_company_department'] = (object)array();
            }

            $result['count_PAB_by_worker_id'] = array();
            $checking_pab_by_worker_id = Checking::find()
                ->select('violator.worker_id,checking.id as checking_id')
                ->innerJoin('injunction', 'checking.id = injunction.checking_id')
                ->innerJoin('injunction_violation', 'injunction_violation.injunction_id = injunction.id')
                ->innerJoin('violator', 'injunction_violation.id = violator.injunction_violation_id')
                ->where(['in', 'injunction.company_department_id', $company_departments_ids])
                ->andWhere(['injunction.kind_document_id' => self::KIND_PAB])
                ->andWhere(['between', 'checking.date_time_start', $date_start, date('Y-m-d', strtotime($date_end . "+ 1 day"))])
                ->groupBy('worker_id,checking_id')
                ->asArray()
                ->all();
            foreach ($checking_pab_by_worker_id as $count_pab_by_violator) {
                if (!isset($result['count_PAB_by_worker_id'][$count_pab_by_violator['worker_id']])) {
                    $result['count_PAB_by_worker_id'][$count_pab_by_violator['worker_id']] = 1;
                } else {
                    $result['count_PAB_by_worker_id'][$count_pab_by_violator['worker_id']]++;
                }
            }
            if (empty($result['count_PAB_by_worker_id'])) {
                $result['count_PAB_by_worker_id'] = (object)array();
            }

            $result['count_PAB_by_violation_type'] = array();
            $checking_pab_by_violation = Checking::find()
                ->select('violator.worker_id,checking.id as checking_id,violation_type.title as violation_type_title')
                ->innerJoin('injunction', 'checking.id = injunction.checking_id')
                ->innerJoin('injunction_violation', 'injunction_violation.injunction_id = injunction.id')
                ->innerJoin('violator', 'injunction_violation.id = violator.injunction_violation_id')
                ->innerJoin('violation', 'injunction_violation.violation_id = violation.id')
                ->innerJoin('violation_type', 'violation_type.id = violation.violation_type_id')
                ->where(['in', 'injunction.company_department_id', $company_departments_ids])
                ->andWhere(['injunction.kind_document_id' => self::KIND_PAB])
                ->andWhere(['between', 'checking.date_time_start', $date_start, date('Y-m-d', strtotime($date_end . "+ 1 day"))])
                ->groupBy('worker_id,checking_id,violation_type_id')
                ->asArray()
                ->all();
            foreach ($checking_pab_by_violation as $count_pab_by_violation) {
                if (!isset($result['count_PAB_by_violation_type'][$count_pab_by_violation['violation_type_title']])) {
                    $result['count_PAB_by_violation_type'][$count_pab_by_violation['violation_type_title']] = 1;
                } else {
                    $result['count_PAB_by_violation_type'][$count_pab_by_violation['violation_type_title']]++;
                }
            }
            if (empty($result['count_PAB_by_violation_type'])) {
                $result['count_PAB_by_violation_type'] = (object)array();
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetPabByFilter. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetPabByFilter. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetRisk() - Метод для расчёта к какому риску принадлежит сочетание: опасность + веростяность
     *                    применяется в методе GetPabByFilter
     * @param $gravity
     * @param $probability
     * @return string|null
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 02.10.2019 17:51
     */
    public static function GetRisk($gravity, $probability)
    {
        $risk = null;
        if (($gravity == 1 && $probability == 1) ||
            ($gravity == 1 && $probability == 2) ||
            ($gravity == 2 && $probability == 1)) {
            $risk = 'light';
        } elseif (($gravity == 2 && $probability == 2) ||
            ($gravity == 3 && $probability == 1) ||
            ($gravity == 1 && $probability == 3) ||
            ($gravity == 3 && $probability == 2) ||
            ($gravity == 2 && $probability == 3)) {
            $risk = 'medium';
        } elseif (($gravity == 1 && $probability == 4) ||
            ($gravity == 2 && $probability == 4) ||
            ($gravity == 3 && $probability == 4) ||
            ($gravity == 3 && $probability == 3) ||
            ($gravity == 4 && $probability == 1) ||
            ($gravity == 4 && $probability == 2) ||
            ($gravity == 4 && $probability == 3)) {
            $risk = 'high';
        } elseif (($gravity == 4 && $probability == 4) ||
            ($gravity == 1 && $probability == 5) ||
            ($gravity == 2 && $probability == 5) ||
            ($gravity == 3 && $probability == 5) ||
            ($gravity == 4 && $probability == 5) ||
            ($gravity == 5 && $probability == 5) ||
            ($gravity == 5 && $probability == 4) ||
            ($gravity == 5 && $probability == 3) ||
            ($gravity == 5 && $probability == 2) ||
            ($gravity == 5 && $probability == 1)) {
            $risk = 'extream';
        }
        return $risk;
    }

    /**
     * Метод GetPlannedAudit() - Получение запланированных аудитов за год
     * @param null $data_post - JSON с годом на который необходимо получить все запланированные аудиты
     *      Вариант №1:
     *          year            - год на который хотим получить график аудитов
     *      Вариант №2:
     *          date_end        - дата начала выборки
     *          date_start      - дата окончания выборки
     * @return array - массив со следующей структурой:
     *      audit_data_by_year - структура данных сгруппированная по году и месяцу
     *          {month}             - номер месяца
     *                month             - номер месяца
     *                audit             - группа аудита
     *                   {day}                  - день аудита
     *                      {audit_id}              - ключ аудита
     *                          audit_id                    - ключ аудиита
     *                          date_time                   - дата и время проведения аудита
     *                          month                       - месяц
     *                          description                 - примечание/описание
     *                          company_department_id       - ключ подразделения
     *                          company_department_title    - название подразделения в котором проводя проверку
     *                          company_department_path     - полный путь до подразделения
     *                          checking_id                 - ключ проверки
     *                          checking_type_id            - ключ типа проверки
     *                          places                      - список мест проведения проверки
     *                               {place_id}                 - ключ места
     *                                      place_id                - ключ места
     *                                      mine_id                 - ключ шахтного поля
     *                          workers                     - список аудиторов
     *                               {worker_id}                - ключ работника
     *                                      worker_id               - ключ работника (аудитора)
     *                                      worker_full_name        - ФИО работника (аудитора)
     *               checking           - группа проверок
     *                   [day]              - день проверки
     *                      checkings           - список проверок в этот день
     *                          {checking_id}               - ключ проверки
     *                              checking_id                 - ключ проверки
     *                              checking_type_id            - ключ типа проверки
     *                              injunctions                 - список предписаний
     *                                  []
     *                                      injunction_id           - ключ предписания
     *      audit_data_by_period - структура данных без группировки - используется при получении данных за период
     *                audits             - группа аудита
     *                      {audit_id}              - ключ аудита
     *                          audit_id                    - ключ аудиита
     *                          date_time                   - дата и время проведения аудита
     *                          month                       - месяц
     *                          description                 - примечание/описание
     *                          company_department_id       - ключ подразделения
     *                          company_department_title    - название подразделения в котором проводя проверку
     *                          company_department_path     - полный путь до подразделения
     *                          checking_id                 - ключ проверки
     *                          checking_type_id            - ключ типа проверки
     *                          places                      - список мест проведения проверки
     *                               {place_id}                 - ключ места
     *                                      place_id                - ключ места
     *                                      mine_id                 - ключ шахтного поля
     *                          workers                     - список аудиторов
     *                               {worker_id}                - ключ работника
     *                                      worker_id               - ключ работника (аудитора)
     *                                      worker_full_name        - ФИО работника (аудитора)
     *               checkings           - группа проверок
     *                          {checking_id}               - ключ проверки
     *                              checking_id                 - ключ проверки
     *                              checking_type_id            - ключ типа проверки
     *                              injunctions                 - список предписаний
     *                                  []
     *                                      injunction_id           - ключ предписания
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Checking&method=GetPlannedAudit&subscribe=&data={"year":"2019"}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 09.10.2019 13:27
     */
    public static function GetPlannedAudit($data_post = NULL)
    {
        $log = new LogAmicumFront('GetPlannedAudit');

        $result = array(
            "audit_data_by_year" => null,
            "audit_data_by_period" => null,
        );

        try {
            $log->addLog("Начало метода");

            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $post_dec = json_decode($data_post);
            if (property_exists($post_dec, 'year') and $post_dec->year != "") {
                $year = $post_dec->year;
                $date_start = $year . '-01-01';
                $date_end = $year . '-12-31';
            } else if (
                property_exists($post_dec, 'date_start') and $post_dec->date_start != "" and
                property_exists($post_dec, 'date_end') and $post_dec->date_end != ""
            ) {
                $date_start = $post_dec->date_start;
                $date_end = $post_dec->date_end;
            } else {
                throw new Exception('Переданы некорректные входные параметры');
            }


            /*** ФОРМИРУЕМ ВЫХОДНУЮ СТРУКТУРУ ДЛЯ ГОДА */
            for ($i = 1; $i <= 12; $i++) {
                if ($i < 10) {
                    $i = (int)'0' . $i;
                }
                $audit_data_by_year[$i]['month'] = $i;
                $audit_data_by_year[$i]['audit'] = array();
                $audit_data_by_year[$i]['checking'] = array();
            }

            /******************** НАХОДИМ ПРОВЕДЕННЫЕ ПРОВЕРКИ ********************/
            $found_checking_data = Checking::find()
                ->joinWith('injunctions')
                ->where(['>=', 'checking.date_time_start', $date_start . ' 00:00:00'])
                ->andWhere(['<=', 'checking.date_time_start', $date_end . ' 23:59:59'])
                ->andWhere(['in', 'checking.kind_document_id', [1, 3, 5]])                                            // 1 - предписание, 3 - предписание РТН, 5 - рапорт
                ->all();


            foreach ($found_checking_data as $checking) {
//                if($checking->id=="23905990") {
//                    $log->addData($checking,'$checking',__LINE__);
//                }
                $month = date('m', strtotime($checking->date_time_start));
                $day = date('d', strtotime($checking->date_time_start));
                $int_day = (int)$day;

                $injunctions = [];
                foreach ($checking->injunctions as $injunction) {
                    $injunctions[] = $injunction->id;
                }

                $audit_data_by_year[$month]['checking'][$int_day]['checkings'][$checking->id] = array(
                    'checking_id' => $checking->id,
                    'checking_type_id' => $checking->checking_type_id,
                    'injunctions' => $injunctions,
                );

                $audit_data_by_period['checkings'][$checking->id] = array(
                    'checking_id' => $checking->id,
                    'checking_type_id' => $checking->checking_type_id,
                    'injunctions' => $injunctions,
                );

            }

            /******************** НАХОДИМ ЗАПЛАНИРОВАННЫЕ АУДИТЫ ********************/
            $audits = Audit::find()
                ->innerJoinWith('companyDepartment.company')
                ->innerJoinWith('auditPlaces.place')
                ->innerJoinWith('auditWorkers.worker.employee')
                ->where(['between', 'audit.date_time', $date_start, $date_end])
                ->all();

            /******************** ПЕРЕБОР ЗАПЛАНИРОВАННЫХ АУДИТОВ ********************/
            $department_paths = null;
            if (!empty($audits)) {
                foreach ($audits as $audit) {
                    $places = null;
                    $workers = null;
                    $company_id = $audit->company_department_id;
                    if (!isset($department_paths[$company_id])) {
                        $response = HandbookDepartmentController::GetAllParentsCompaniesWithCompany($company_id);
                        if ($response['status'] == 1) {
                            $department_path = $response['Items'];
                        } else {
                            $department_path = "";
                        }
                        $department_paths[$company_id] = $department_path;
                    }

                    $month = date('m', strtotime($audit->date_time));                                                                       // месяц на который запланирован аудит
                    $day = (int)date('d', strtotime($audit->date_time));                                                                    // день на который запланирован аудит

                    foreach ($audit->auditPlaces as $auditPlace) {
                        $places[$auditPlace->place_id] = array(
                            'place_id' => $auditPlace->place_id,
                            'mine_id' => $auditPlace->place->mine_id,
                        );
                    }

                    foreach ($audit->auditWorkers as $auditWorker) {
                        $workers[$auditWorker->worker_id] = array(
                            'worker_id' => $auditWorker->worker_id,
                            'worker_full_name' => Assistant::GetFullName($auditWorker->worker->employee->first_name, $auditWorker->worker->employee->patronymic, $auditWorker->worker->employee->last_name),
                        );
                    }

                    $audit_data_by_year[$month]['audit'][$day][$audit->id] = array(
                        'audit_id' => $audit->id,                                                        // идентификатор аудита
                        'date_time' => $audit->date_time,                                                // дата проведения аудита
                        'month' => $month,                                                               // месяц проведения аудита
                        'description' => $audit->description,                                            // примечание к запланированному аудиту
                        'company_department_id' => $company_id,                                          // ключ участок где будет происходить аудит
                        'company_department_title' => $audit->companyDepartment->company->title,         // название участка где будет происходить аудит
                        'company_department_path' => $department_paths[$company_id],                     // путь до участка
                        'checking_id' => $audit->checking_id,                                            // проверка (если есть)
                        'checking_type_id' => $audit->checking_type_id,                                  // тип запланированного аудита
                        'places' => $places,                                                             // список мест где будет происходить аудит
                        'workers' => $workers,                                                           // список аудиторов которые будут проводить проверку
                    );

                    $audit_data_by_period['audits'][$audit->id] = array(
                        'audit_id' => $audit->id,                                                        // идентификатор аудита
                        'date_time' => $audit->date_time,                                                // дата проведения аудита
                        'month' => $month,                                                               // месяц проведения аудита
                        'description' => $audit->description,                                            // примечание к запланированному аудиту
                        'company_department_id' => $company_id,                                          // ключ участок где будет происходить аудит
                        'company_department_title' => $audit->companyDepartment->company->title,         // название участка где будет происходить аудит
                        'company_department_path' => $department_paths[$company_id],                     // путь до участка
                        'checking_id' => $audit->checking_id,                                            // проверка (если есть)
                        'checking_type_id' => $audit->checking_type_id,                                  // тип запланированного аудита
                        'places' => $places,                                                             // список мест где будет происходить аудит
                        'workers' => $workers,                                                           // список аудиторов которые будут проводить проверку
                    );

                }
            }

            for ($i = 1; $i <= 12; $i++) {
                if ($i < 10) {
                    $i = (int)'0' . $i;
                }
                if (empty($audit_data_by_year[$i]['audit'])) {
                    $audit_data_by_year[$i]['audit'] = (object)array();
                }
                if (empty($audit_data_by_year[$i]['checking'])) {
                    $audit_data_by_year[$i]['checking'] = (object)array();
                }
            }
            $result["audit_data_by_year"] = $audit_data_by_year;
            $result["audit_data_by_period"] = $audit_data_by_period;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод SavePlannedAudit() - Сохранение запланированного аудита
     * @param null $data_post - JSON c данными: [audit]
     *                                              audit_id:
     *                                              date_time:
     *                                              description:
     *                                              company_department_id:
     *                                              checking_id:
     *                                              checking_type_id:
     *                                              [places]
     *                                                  [place_id]
     *                                                       place_id:
     *                                              [workers]
     *                                                  [worker_id]
     *                                                       worker_id:
     * @return array - стандартный массив выходных данных
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example amicum/read-manager-amicum?controller=industrial_safety\Checking&method=SavePlannedAudit&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 10.10.2019 16:12
     */
    public static function SavePlannedAudit($data_post = NULL)
    {
        $log = new LogAmicumFront("SavePlannedAudit");

        $save_planned = array();                                                                                        // Промежуточный результирующий массив

        try {
            $log->addLog("Начал выполнение метода");

            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }

            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            if (!property_exists($post_dec, 'audit'))                                                           // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $audit = $post_dec->audit;
            $add_audit = new Audit();
            $add_audit->company_department_id = $audit->company_department_id;
            $add_audit->date_time = date('Y-m-d', strtotime($audit->date_time));
            $add_audit->description = $audit->description;
            $add_audit->checking_type_id = $audit->checking_type_id;
            $add_audit->checking_id = $audit->checking_id;
            if (!$add_audit->save()) {
                $log->addData($add_audit->errors, '$add_audit->errors', __LINE__);
                throw new Exception('Ошибка при сохранении аудита');
            }

            $add_audit->refresh();
            $add_audit_id = $add_audit->id;
            $save_planned['audit_id'] = $add_audit_id;
            $save_planned['company_department_id'] = $audit->company_department_id;
            $save_planned['date_time'] = $audit->date_time;
            $save_planned['month'] = date("m", strtotime($audit->date_time));
            $save_planned['description'] = $audit->description;
            $save_planned['checking_type_id'] = $audit->checking_type_id;
            $save_planned['checking_id'] = $audit->checking_id;


            foreach ($audit->places as $key => $place) {
                $save_planned['places'][$place->place_id]['place_id'] = $place->place_id;
                $audit_places[] = [$add_audit_id, $place->place_id];
            }

            foreach ($audit->workers as $key => $worker) {
                $save_planned['workers'][$worker->worker_id]['worker_id'] = $worker->worker_id;
                $audit_workers[] = [$add_audit_id, $worker->worker_id];
            }
            if (!empty($audit_places)) {
                $result_batch_places = Yii::$app->db
                    ->createCommand()
                    ->batchInsert('audit_place', ['audit_id', 'place_id'], $audit_places)
                    ->execute();
                if (!$result_batch_places) {
                    throw new Exception('Ошибка при сохранении связки планового аудита и места');
                }
            }

            if (!empty($audit_workers)) {
                $result_batch_workers = Yii::$app->db
                    ->createCommand()
                    ->batchInsert('audit_worker', ['audit_id', 'worker_id'], $audit_workers)
                    ->execute();
                if (!$result_batch_workers) {
                    throw new Exception('SavePlannedAudit. Ошибка при сохранении связки планового аудита и аудитора');
                }
            }

            $result = $save_planned;
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(["Items" => $result], $log->getLogAll());
    }

    /**
     * Метод DeletedAudit() - Удаление аудита по идентификатору
     * @param null $data_post - JSON с идентификатором удаляемого аудита
     * @return array - массив со стандартной структурой
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.10.2019 9:15
     */
    public static function DeletedAudit($data_post = null)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'DeletedAudit. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('SavePannedAudit. Не переданы входные параметры');
            }
            $post_dec = json_decode($data_post);
            if (!property_exists($post_dec, 'audit_id')) {
                throw new Exception('DeletedAudit. Не переданы входные параметры');
            }
            $audit_id = $post_dec->audit_id;
            $del_audit = Audit::deleteAll(['id' => $audit_id]);
            if ($del_audit != 0) {
                $warnings[] = 'DeletedAudit.  Аудит успешно удалён';
            } else {
                throw new Exception('DeletedAudit. Ошибка при удалении аудита');
            }
        } catch (Throwable $exception) {
            $errors[] = 'DeletedAudit. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }
        $warnings[] = 'DeletedAudit. Конец метода';
        $result_main = ['Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings];
        return $result_main;
    }

    /**
     * Метод ChangeAudit() - метод изменения аудита
     * @param null $data_post - JSON массив входных данных: идентификатор аудита, массив мест, массив аудиторов
     * @return array - массив со стандартной структурой
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.10.2019 9:14
     */
    public static function ChangeAudit($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $change_audit = array();                                                                                // Промежуточный результирующий массив
//        $delete_audit_places = array();
        $warnings[] = 'ChangeAudit. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('ChangeAudit. Не переданы входные параметры');
            }
            $warnings[] = 'ChangeAudit. Данные успешно переданы';
            $warnings[] = 'ChangeAudit. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'ChangeAudit. Декодировал входные параметры';
            if (!property_exists($post_dec, 'audit_id') ||
                !property_exists($post_dec, 'audit_places') ||
                !property_exists($post_dec, 'audit_workers'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('ChangeAudit. Переданы некорректные входные параметры');
            }
            $warnings[] = 'ChangeAudit. Данные с фронта получены';
            $audit_id = $post_dec->audit_id;
            $audit_places = $post_dec->audit_places;
            $audit_workers = $post_dec->audit_workers;
            /*
             * Удаление ранее созданных мест в запланированном аудите
             */
            $delete_audit_places = AuditPlace::deleteAll(['audit_id' => $audit_id]);
            if ($delete_audit_places != 0) {
                $warnings[] = 'ChangeAudit. Старые места успешно удалены';
            } else {
                $errors[] = 'ChangeAudit. Ошибка при удалении старых мест на аудите либо нечего удалять';
            }
            /*
             * Удаление ранее созданных аудиторов в запланированном аудите
             */
            $delete_audit_worker = AuditWorker::deleteAll(['audit_id' => $audit_id]);
            if ($delete_audit_worker != 0) {
                $warnings[] = 'ChangeAudit. Старые аудиторы на аудите успешно удалены';
            } else {
                $errors[] = 'ChangeAudit. Ошибка при удалении старых аудиторов на аудите либо нечего удалять';
            }
            $insert_audit_places = array();
            $insert_audit_workers = array();
            /*
             * Формирование массива на добавление связки аудита и мест
             */
            foreach ($audit_places as $audit_place) {
                $place_id = (int)$audit_place->place_id;
                $insert_audit_places[] = [(int)$audit_id, $place_id];
            }
            /*
             * Формирование массива на добавление связки аудита и аудиторов
             */
            foreach ($audit_workers as $audit_worker) {
                $audit_worker_id = (int)$audit_worker->worker_id;
                $insert_audit_workers[] = [(int)$audit_id, $audit_worker_id];
            }
            /*
             * Массовое добавление связки мест и аудита
             */
            if (!empty($insert_audit_places)) {
                $add_audit_places = Yii::$app->db
                    ->createCommand()
                    ->batchInsert('audit_place', ['audit_id', 'place_id'], $insert_audit_places)
                    ->execute();
                if ($add_audit_places != 0) {
                    $warnings[] = 'ChangeAudit. Связка мест и аудита успешно добавлена';
                } else {
                    throw new Exception('ChangeAudit. Произошла ошибка при добавлении связки места и аудита');
                }
            }

            /*
             * Массовое добавление связки аудита и аудиторов
             */
            if (!empty($insert_audit_workers)) {
                $add_audit_workers = Yii::$app->db
                    ->createCommand()
                    ->batchInsert('audit_worker', ['audit_id', 'worker_id'], $insert_audit_workers)
                    ->execute();
                if ($add_audit_workers != 0) {
                    $warnings[] = 'ChangeAudit. Связка аудитора и аудита успешно добавлена';
                } else {
                    throw new Exception('ChangeAudit. Произошла ошибка при добавлении связки аудитора и аудита');
                }
            }

        } catch (Throwable $exception) {
            $errors[] = 'ChangeAudit. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'ChangeAudit. Конец метода';
        $result = $change_audit;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetArchiveCheckingForStatistic() - Проверки для таблицы в статистике "Статистика предписаний/ПАБ"
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Checking&method=GetArchiveCheckingForStatistic&subscribe=&data={%22date_time_start%22:%222019-11-01%22,%22date_time_end%22:%222019-11-30%22,%22company_department_id%22:20028748}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 15.11.2019 12:15
     */
    public static function GetArchiveCheckingForStatistic($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $archive_checking = array();                                                                                         // Промежуточный результирующий массив
//        $company_departments = array();
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('GetArchiveCheckingForStatistic. Данные с фронта не получены');
            }
            $warnings[] = 'GetArchiveCheckingForStatistic. Данные успешно переданы';
            $warnings[] = 'GetArchiveCheckingForStatistic. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GetArchiveCheckingForStatistic. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'date_time_start') ||
                !property_exists($post_dec, 'date_time_end') ||
                !property_exists($post_dec, 'company_department_id')
            )                                                                                                       // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('GetArchiveCheckingForStatistic. Переданы некорректные входные параметры');
            }
            $date_time_start = date('Y-m-d', strtotime($post_dec->date_time_start));
            $date_time_end = date('Y-m-d', strtotime($post_dec->date_time_end));
            $company_department_id = $post_dec->company_department_id;
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception('GetArchiveCheckingForStatistic. Ошибка получения вложенных департаментов' . $company_department_id);
            }
            $warnings[] = 'GetArchiveCheckingForStatistic.Данные с фронта получены';
            $warnings[] = 'GetArchiveCheckingForStatistic. Начало метода';
            $found_checking_data = Checking::find()
                ->innerJoinWith('companyDepartment.company')
                ->innerJoinWith('checkingType')
                ->innerJoinWith('checkingWorkerTypes.worker.employee')
                ->innerJoinWith('checkingWorkerTypes.worker.position')
                ->joinWith('injunctions')
                ->where(['>=', 'checking.date_time_start', $date_time_start . ' 00:00:00'])
                ->andWhere(['<=', 'checking.date_time_start', $date_time_end . ' 23:59:59'])
                ->andWhere(['in', 'checking.company_department_id', $company_departments])
                ->andWhere(['or',
                    ['in', 'injunction.kind_document_id', [1, 3]],
                    ['is', 'injunction.id', null]
                ])
                ->limit(5000)
                ->all();
            if ($found_checking_data) {
                foreach ($found_checking_data as $checkig_item) {
                    $archive_checking[$checkig_item->id]['checking_id'] = $checkig_item->id;
                    $archive_checking[$checkig_item->id]['date_time'] = date('d.m.Y H:i:s', strtotime($checkig_item->date_time_end));
                    $archive_checking[$checkig_item->id]['checking_type_title'] = $checkig_item->checkingType->title;
                    if (isset($checkig_item->checkingWorkerTypes[0]) && isset($checkig_item->checkingWorkerTypes[0]->worker->id)) {
                        $archive_checking[$checkig_item->id]['auditor']['worker_id'] = $checkig_item->checkingWorkerTypes[0]->worker->id;
                        $full_name = "{$checkig_item->checkingWorkerTypes[0]->worker->employee->last_name} {$checkig_item->checkingWorkerTypes[0]->worker->employee->first_name} {$checkig_item->checkingWorkerTypes[0]->worker->employee->patronymic}";
                        $archive_checking[$checkig_item->id]['auditor']['worker_full_name'] = $full_name;
                        $archive_checking[$checkig_item->id]['auditor']['worker_position_title'] = $checkig_item->checkingWorkerTypes[0]->worker->position->title;
                        $archive_checking[$checkig_item->id]['auditor']['worker_staff_number'] = $checkig_item->checkingWorkerTypes[0]->worker->tabel_number;
                    } else {
                        $archive_checking[$checkig_item->id]['auditor']['worker_id'] = null;
                        $archive_checking[$checkig_item->id]['auditor']['worker_full_name'] = "Отсутствует Имя Отчество";
                        $archive_checking[$checkig_item->id]['auditor']['worker_position_title'] = "-";
                        $archive_checking[$checkig_item->id]['auditor']['worker_staff_number'] = '-';
                    }
                    $archive_checking[$checkig_item->id]['department_title'] = $checkig_item->companyDepartment->company->title;
                    $archive_checking[$checkig_item->id]['company_department_id'] = $checkig_item->company_department_id;
                    if (empty($checkig_item->injunctions)) {
                        $archive_checking[$checkig_item->id]['injunctions'] = null;
                    } else {
                        foreach ($checkig_item->injunctions as $injunction) {
                            $archive_checking[$checkig_item->id]['injunctions'][$injunction->id]['injunction_id'] = $injunction->id;
                            $archive_checking[$checkig_item->id]['injunctions'][$injunction->id]['kind_document_id'] = $injunction->kind_document_id;
                        }
                    }
                }
            } else {
                $errors[] = 'GetArchiveCheckingForStatistic. Нет данных';
            }
        } catch (Throwable $exception) {
            $warnings[] = 'GetArchiveCheckingForStatistic. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetArchiveCheckingForStatistic. Конец метода';
        $result = $archive_checking;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetPabsInforamtion() - Метод получения данных на странице "Учёт нарушений"
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Checking&method=GetPabsInforamtion&subscribe=&data={"company_department_id":4029720,"year":2019,"month":11}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 22.11.2019 16:50
     */
    public static function GetPabsInforamtion($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $pabs_info = array();                                                                                // Промежуточный результирующий массив
        $directions = array();
        $count_pab_by_company_title = array();
        $warnings[] = 'GetPabsInforamtion. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('GetPabsInforamtion. Не переданы входные параметры');
            }
            $warnings[] = 'GetPabsInforamtion. Данные успешно переданы';
            $warnings[] = 'GetPabsInforamtion. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GetPabsInforamtion. Декодировал входные параметры';
            if (!property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'year') ||
                !property_exists($post_dec, 'month'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('GetPabsInforamtion. Переданы некорректные входные параметры');
            }
            $warnings[] = 'GetPabsInforamtion. Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;
            $year = $post_dec->year;
            $month = $post_dec->month;

//            $date_start = date('Y-m-d', strtotime($year . '-' . $month . '-01'));
            $cal_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
//            $date_end = date('Y-m-d', strtotime($year . '-' . $month . '-' . $cal_days));
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception('GetPabsInforamtion. Ошибка получения вложенных департаментов' . $company_department_id);
            }
            $pabs = Injunction::find()
                ->joinWith('checking')
                ->joinWith('companyDepartment.company')
                ->joinWith('worker.employee')
                ->joinWith('worker.position')
                ->joinWith('injunctionViolations.violation.violationType')
                ->where(['injunction.kind_document_id' => self::KIND_PAB])
                ->andWhere(['in', 'injunction.company_department_id', $company_departments])
                ->andWhere("YEAR(checking.date_time_start)='" . $year . "'")
                ->andFilterWhere(['MONTH(checking.date_time_start)' => $month])
                ->all();
            if (!empty($pabs)) {
                foreach ($pabs as $pab) {
                    $worker_id = $pab->worker->id;
                    $pab_id = $pab->id;
                    $company_title = $pab->companyDepartment->company->title;
                    $pabs_info['accounting_violation'][$pab_id]['worker_id'] = $worker_id;
                    $pabs_info['accounting_violation'][$pab_id]['full_name'] = "{$pab->worker->employee->last_name} {$pab->worker->employee->first_name} {$pab->worker->employee->patronymic}";
                    $pabs_info['accounting_violation'][$pab_id]['position_title'] = $pab->worker->position->title;
                    $pabs_info['accounting_violation'][$pab_id]['company_title'] = $pab->companyDepartment->company->title;
                    if (isset($count_pab_by_company_title[$company_title])) {
                        $count_pab_by_company_title[$company_title]++;
                    } else {
                        $count_pab_by_company_title[$company_title] = 1;
                    }
                    $pabs_info['accounting_violation'][$pab_id]['date'] = date('d.m.Y', strtotime($pab->checking->date_time_start));
                    foreach ($pab->injunctionViolations as $injunctionViolation) {
                        $directions[$pab_id][] = $injunctionViolation->violation->violationType->title;
                    }

                    if (!empty($directions)) {
                        $implode_directions = implode('; ', array_unique($directions[$pab_id]));
                    } else {
                        $implode_directions = null;
                    }
                    $pabs_info['accounting_violation'][$pab_id]['violation_type'] = $implode_directions;
                    $pabs_info['accounting_violation'][$pab_id]['pab_id'] = $pab->id;
                }
            } else {
                $pabs_info['accounting_violation'] = (object)array();
            }
            $get_statistic = self::GetPabStatistic($company_departments, $month, $year, $count_pab_by_company_title);
            if ($get_statistic['status'] == 1) {
                $pabs_info['statistic'] = $get_statistic['Items'];
                $warnings[] = $get_statistic['warnings'];
            } else {
                $warnings[] = $get_statistic['warnings'];
                $errors[] = $get_statistic['errors'];
                throw new Exception('GetPabsInforamtion. Ошибка при получении статистики');
            }

        } catch (Throwable $exception) {
            $errors[] = 'GetPabsInforamtion. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getFile();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetPabsInforamtion. Конец метода';
        $result = $pabs_info;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetPabStatistic() - метод получения ститистики по ПАБам используется в методе GetPabsInforamtion
     * @param $company_departments - вложенные участки
     * @param $date_start - дата начала
     * @param $date_end - дата окончания
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.11.2019 10:19
     */
    public static function GetPabStatistic($company_departments, $month, $year, $count_pab_by_company_title)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $intermediate = array();
        $warnings[] = 'GetPabStatistic. Начало метода';
        try {
            $count_day = cal_days_in_month(CAL_GREGORIAN, $month, $year);                                  // количество дней в месяце
            $date = date('Y-m-d', strtotime($year . '-' . $month . '-' . $count_day));                 // период за месяц до конца месяца
            /******************** ВСЕГО СОТРУДНИКОВ ********************/
            $count_worker = Worker::find()
                ->select('count(worker.id) as count_worker')
                ->where(['in', 'worker.company_department_id', $company_departments])
                ->andWhere(['<=', 'worker.date_start', $date])
                ->andWhere(['or',
                    ['>', 'worker.date_end', $date],
                    ['is', 'worker.date_end', null]
                ])
                ->scalar();
            $result['count_worker'] = (int)$count_worker;
            /******************** СОТРУДНИКОВ С ЗАФИКСИРОВАННЫМИ НАРУШЕНИЯМИ ********************/
            $count_worker_with_pab = Injunction::find()
                ->select(['injunction.worker_id as worker_id'])
                ->innerJoin('checking', 'checking.id = injunction.checking_id')
                ->where(['injunction.kind_document_id' => self::KIND_PAB])
                ->andWhere(['in', 'injunction.company_department_id', $company_departments])
                ->andWhere("YEAR(checking.date_time_start)='" . $year . "'")
                ->andFilterWhere(['MONTH(checking.date_time_start)' => $month])
                ->groupBy('worker_id')
                ->count();
            $result['count_worker_with_pab'] = (int)$count_worker_with_pab;
            /******************** ВСЕГО НАРУШЕНИЙ ********************/
            $coun_pab = Injunction::find()
                ->innerJoin('worker', 'worker.id = injunction.worker_id')
                ->innerJoin('checking', 'checking.id = injunction.checking_id')
                ->where(['injunction.kind_document_id' => self::KIND_PAB])
                ->andWhere(['in', 'injunction.company_department_id', $company_departments])
                ->andWhere("YEAR(checking.date_time_start)='" . $year . "'")
                ->andFilterWhere(['MONTH(checking.date_time_start)' => $month])
                ->count();
            $result['all_pab'] = (int)$coun_pab;
            /******************** ПАБов ПО ГОДАМ ********************/
            $count_pab_by_year = Injunction::find()
                ->select(['count(injunction.id) as count_inj_id', 'YEAR(c.date_time_start) as checking_year'])
                ->innerJoin('checking c', 'injunction.checking_id = c.id')
                ->innerJoin('worker', 'worker.id = injunction.worker_id')
                ->where(['in', 'injunction.company_department_id', $company_departments])
                ->andWhere(['injunction.kind_document_id' => self::KIND_PAB])
//                ->andWhere(['between','c.date_time_start',$date_start,date('Y-m-d',strtotime($date_end."+ 1 day"))])
                ->groupBy('YEAR(c.date_time_start)')
                ->asArray()
                ->all();
            foreach ($count_pab_by_year as $statistic_by_year) {
                $result['statistic_by_year'][$statistic_by_year['checking_year']] = (int)$statistic_by_year['count_inj_id'];
            }

            /******************** СТАТИСТИКА ПО ПРОФЕССИЯМ ********************/
            $result['count_PAB_by_profession'] = array();
            $get_stat_by_prof = Injunction::find()
                ->select([
                    'p.title position_title',
                    'COUNT(injunction.id) AS count_inj_id'
                ])
                ->innerJoin('worker w', 'injunction.worker_id = w.id')
                ->innerJoin('position p', 'w.position_id = p.id')
                ->innerJoin('checking', 'checking.id = injunction.checking_id')
                ->where(['in', 'injunction.company_department_id', $company_departments])
                ->andWhere(['injunction.kind_document_id' => self::KIND_PAB])
                ->andWhere("YEAR(checking.date_time_start)='" . $year . "'")
                ->andFilterWhere(['MONTH(checking.date_time_start)' => $month])
                ->groupBy('position_title')
                ->asArray()
                ->all();
            foreach ($get_stat_by_prof as $stat_by_prof) {
                if (isset($result['count_PAB_by_profession'][$stat_by_prof['position_title']]['count'])) {
                    $result['count_PAB_by_profession'][$stat_by_prof['position_title']]['count'] += (int)$stat_by_prof['count_inj_id'];
                } else {
                    $result['count_PAB_by_profession'][$stat_by_prof['position_title']]['count'] = (int)$stat_by_prof['count_inj_id'];
                }
                $result['count_PAB_by_profession'][$stat_by_prof['position_title']]['position_title'] = $stat_by_prof['position_title'];
            }
            /******************** КОЛИЧЕСТВО ПАБ ПО УЧАСТКУ ********************/
            /**
             * Кол-во людей по участкам
             */
            $get_worker_by_comp_dep = Worker::find()
                ->select(['count(worker.id) as count_worker, company.title as company_title'])
                ->innerJoin('company_department', 'company_department.id = worker.company_department_id')
                ->innerJoin('company', 'company.id = company_department.company_id')
                ->where(['in', 'worker.company_department_id', $company_departments])
                ->asArray()
                ->indexBy('company_title')
                ->groupBy('company_title')
                ->all();
            $checking_with_pab = Checking::find()
                ->select(['checking.company_department_id as company_department_id',
                    'injunction.worker_id as inj_worker_id',
                    'company.title as company_department_title'])
                ->innerJoin('injunction', 'injunction.checking_id = checking.id')
                ->innerJoin('company_department', 'company_department.id = injunction.company_department_id')
                ->innerJoin('company', 'company.id = company_department.company_id')
                ->where(['in', 'injunction.company_department_id', $company_departments])
                ->andWhere(['injunction.kind_document_id' => self::KIND_PAB])
                ->andWhere("YEAR(checking.date_time_start)='" . $year . "'")
                ->andFilterWhere(['MONTH(checking.date_time_start)' => $month])
                ->groupBy('inj_worker_id,company_department_title,company_department_id')
                ->asArray()
                ->all();

            if (isset ($checking_with_pab) && !empty($checking_with_pab)) {
                foreach ($checking_with_pab as $checking_pab) {
                    if (!isset($intermediate['count_PAB_by_company_department'][$checking_pab['company_department_title']])) {
                        $intermediate['count_PAB_by_company_department'][$checking_pab['company_department_title']] = 1;
                    } else {
                        $intermediate['count_PAB_by_company_department'][$checking_pab['company_department_title']]++;
                    }
                }
            }

            if (isset ($intermediate['count_PAB_by_company_department'])) {
                foreach ($intermediate['count_PAB_by_company_department'] as $key => $item) {
                    $worker_pab = $count_pab_by_company_title[$key];
                    $count_worker = (int)$get_worker_by_comp_dep[$key]['count_worker'];
                    $result['count_PAB_by_company_department'][$key]['company_department'] = $key;
                    $result['count_PAB_by_company_department'][$key]['count'] = $worker_pab;
//              	  $result['count_PAB_by_company_department'][$key]['count_worker'] = $count_worker;
                    if ($count_worker != 0) {
                        $result['count_PAB_by_company_department'][$key]['percent'] = round(($item / $count_worker) * 100, 2);
                    } else {
                        $result['count_PAB_by_company_department'][$key]['percent'] = 0;
                    }
                }
            }
            if (isset($result['count_PAB_by_company_department']) && empty($result['count_PAB_by_company_department'])) {
                $result['count_PAB_by_company_department'] = (object)array();
            }
            /******************** КОЛИЧЕСТВО ПАБ ПО НАПРАВЛЕНИЯМ ********************/
            $result['count_PAB_by_violation_type'] = array();
            $checking_pab_by_violation = Checking::find()
                ->select('violator.worker_id,checking.id as checking_id,violation_type.title as violation_type_title')
                ->innerJoin('injunction', 'checking.id = injunction.checking_id')
                ->innerJoin('injunction_violation', 'injunction_violation.injunction_id = injunction.id')
                ->innerJoin('violator', 'injunction_violation.id = violator.injunction_violation_id')
                ->innerJoin('violation', 'injunction_violation.violation_id = violation.id')
                ->innerJoin('violation_type', 'violation_type.id = violation.violation_type_id')
                ->where(['in', 'injunction.company_department_id', $company_departments])
                ->andWhere(['injunction.kind_document_id' => self::KIND_PAB])
                ->andWhere("YEAR(checking.date_time_start)='" . $year . "'")
                ->andFilterWhere(['MONTH(checking.date_time_start)' => $month])
                ->groupBy('worker_id,checking_id,violation_type_id')
                ->asArray()
                ->all();
            foreach ($checking_pab_by_violation as $count_pab_by_violation) {
                if (!isset($result['count_PAB_by_violation_type'][$count_pab_by_violation['violation_type_title']]['count'])) {
                    $result['count_PAB_by_violation_type'][$count_pab_by_violation['violation_type_title']]['count'] = 1;
                } else {
                    $result['count_PAB_by_violation_type'][$count_pab_by_violation['violation_type_title']]['count']++;
                }
                $result['count_PAB_by_violation_type'][$count_pab_by_violation['violation_type_title']]['violation_type_title'] = $count_pab_by_violation['violation_type_title'];
            }
            if (empty($result['count_PAB_by_violation_type'])) {
                $result['count_PAB_by_violation_type'] = (object)array();
            }
        } catch (Throwable $exception) {
            $errors[] = 'GetPabStatistic. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetPabStatistic. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод MarkAboutCompletePab() - Отетка о выполнении ПАБа
     * @param null $data_post - JSON с идентификатором предписания, статус который необходимо установить предписанию
     *                                  причина опасного действия нарушителя, массив результатов корректирующих мероприятий
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Checking&method=MarkAboutCompletePab&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 02.12.2019 17:08
     */
    public static function MarkAboutCompletePab($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $mark_pab = array();                                                                                // Промежуточный результирующий массив
        $session = Yii::$app->session;
//        $data_post = '{"injunction_id":39006,"status_id":58,"reason_danger_motion_id":5,"correct_measures":{"2540":{"correct_measures_id":2540,"status_id":59,"result_correct_measures":"ыолцувртаоыв","attachment_id":null,"attachment_title":"title","attachment_type":"doc","attachment_path":"blooob","attachment_status":"куку"}}}';
        $warnings[] = 'MarkAboutCompletePab. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('MarkAboutCompletePab. Не переданы входные параметры');
            }
            $warnings[] = 'MarkAboutCompletePab. Данные успешно переданы';
            $warnings[] = 'MarkAboutCompletePab. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'MarkAboutCompletePab. Декодировал входные параметры';
            if (!property_exists($post_dec, 'injunction_id') ||
                !property_exists($post_dec, 'status_id') ||
                !property_exists($post_dec, 'reason_danger_motion_id') ||
                !property_exists($post_dec, 'correct_measures'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('MarkAboutCompletePab. Переданы некорректные входные параметры');
            }
            $warnings[] = 'MarkAboutCompletePab. Данные с фронта получены';
            $injunction_id = $post_dec->injunction_id;
            $status_id = $post_dec->status_id;
            $reason_danger_motion_id = $post_dec->reason_danger_motion_id;
            $correct_measures = $post_dec->correct_measures;
            if ($status_id != null) {
                Injunction::updateAll(['status_id' => $status_id, 'reason_danger_motion_id' => $reason_danger_motion_id], ['id' => $injunction_id]);
            } else {
                Injunction::updateAll(['reason_danger_motion_id' => $reason_danger_motion_id], ['id' => $injunction_id]);
            }
            if (!empty($correct_measures)) {
                foreach ($correct_measures as $correct_measure) {
                    $corr_mes = CorrectMeasures::findOne(['id' => $correct_measure->correct_measures_id]);
                    if ($corr_mes) {
                        $corr_mes->status_id = $correct_measure->status_id;
                        $corr_mes->result_correct_measures = $correct_measure->result_correct_measures;
                        if ($correct_measure->attachment_status == 'new') {
                            $normalize_path = Assistant::UploadFile($correct_measure->attachment_path, $correct_measure->attachment_title, 'attachment', $correct_measure->attachment_type);
                            $add_attachemnt = new Attachment();
                            $add_attachemnt->title = $correct_measure->attachment_title;
                            $add_attachemnt->path = $normalize_path;
                            $add_attachemnt->date = BackendAssistant::GetDateFormatYMD();
                            $add_attachemnt->worker_id = $session['worker_id'];
                            $add_attachemnt->section_title = 'Книга предписаний';
                            $add_attachemnt->attachment_type = $correct_measure->attachment_type;
                            if ($add_attachemnt->save()) {
                                $warnings[] = 'MarkAboutCompletePab. Вложение успешно сохранено';
                                $add_attachemnt->refresh();
                                $attachment_id = $add_attachemnt->id;
                            } else {
                                $errors[] = $add_attachemnt->errors;
                                throw new Exception('MarkAboutCompletePab. Ошибка при сохранении вложения');
                            }
                        } else {
                            $attachment_id = null;
                        }
                        $corr_mes->attachment_id = $attachment_id;
                        if ($corr_mes->save()) {
                            $warnings[] = 'MarkAboutCompletePab. Отметка о выполнении успешно поставлена';
                        } else {
                            $errors[] = $corr_mes->errors;
                            throw new Exception('MarkAboutCompletePab. Ошибка при сохранении отметки о выполнении ПАБа');
                        }
                    }
                }
            }

        } catch (Throwable $exception) {
            $errors[] = 'MarkAboutCompletePab. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'MarkAboutCompletePab. Конец метода';
        $result = $mark_pab;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод SaveViolationDisconformity() - Сохранение нарушения несоответствия
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\industrial_safety
     *
     * Входные обязательные параметры:
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\Checking&method=SaveViolationDisconformity&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.02.2020 11:58
     */
    public static function SaveViolationDisconformity($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
//        $new_injunction_id = array();                                                                                   // Промежуточный результирующий массив
//        $add_worker_type = array();
        $result = array();
//        $checking_worekr_types = array();
        $checking_id = null;
        $method_name = 'SaveViolationDisconformity';
//        $data_post = '{"checking_id":985302,"checking_title":"Тест сохранения Нарушение несоответствие","company_department_id":20028748,"kind_document_id":4,"crew_member":{"1737037":{"crew_member_id":1737037,"worker_id":2050735,"worker_type_id":1},"1737038":{"crew_member_id":1737038,"worker_id":2050775,"worker_type_id":2}},"injunction":{"new_injunction_0":{"attachments":{},"injunction_id":-1,"observation_number":0,"place_id":6183,"worker_id":70000536,"rtn_statistic_status":56,"injunction_violation":{"new_injunction_violation_0":{"injunction_violation_id":-1,"reason_danger_motion_id":null,"reason_danger_motion_title":null,"probability":null,"dangerous":null,"correct_period":null,"violation_id":-1,"violation_type_id":null,"violation_type_title":null,"document_id":null,"document_title":null,"paragraph_pb_id":-1,"injunction_img":{},"injunction_description":"Нарушение несоответствие. Тест 4.","paragraph_injunction_description":null,"correct_measures":{},"stop_pb":{},"violators":{}}}}}}';
        try {
            if ($data_post !== NULL && $data_post !== '')                                                               // Проверяем передана ли входная JSON строка
            {
                $warnings[] = $method_name . '. Данные успешно переданы';
                $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            } else {
                throw new Exception($method_name . '. Не получена входная JSON строка');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'checking_id') ||
                !property_exists($post_dec, 'checking_title') ||
                !property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'kind_document_id') ||
                !property_exists($post_dec, 'injunction') ||
                !property_exists($post_dec, 'crew_member')
            )                                                                                                           // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $checking_id = $post_dec->checking_id;
            $checking_title = $post_dec->checking_title;
            $company_department_id = $post_dec->company_department_id;
            $kind_document_id = $post_dec->kind_document_id;
            $injunction = $post_dec->injunction;
            $crew_members = $post_dec->crew_member;
            $result = ['checking_id' => $checking_id, 'company_department_id' => $company_department_id, 'kind_document_id' => $kind_document_id, 'crew_members' => $crew_members, 'injunction' => $injunction];
            $checking = Checking::findOne(['id' => $checking_id]);
            if (!empty($checking)) {
                $checking->title = $checking_title;
                if (!$checking->save()) {
                    $errors[] = $checking->errors;
                    throw new Exception($method_name . '. Ошибка при изменении наименования проверки');
                }
            }
            if (isset($crew_members) && !empty($crew_members)) {
                CheckingWorkerType::deleteAll(['checking_id' => $checking_id]);
                foreach ($crew_members as $crew_member) {
                    $checking_worker_types[] = [$checking_id, $crew_member->worker_type_id, $crew_member->worker_id];
                }
            }
            unset($crew_members, $crew_member);
            if (isset($checking_worker_types) && !empty($checking_worker_types)) {
                $inserted_worker_types = Yii::$app->db->createCommand()->batchInsert('checking_worker_type',
                    ['checking_id', 'worker_type_id', 'worker_id'], $checking_worker_types)->execute();
                if ($inserted_worker_types == 0) {
                    $errors[] = $method_name . '. Ошибка при добавлении типов работников на проверку';
                    throw new Exception($method_name . '. Ошибка при добавлении типов работников на проверку');
                }
                unset($checking_worker_types);
            }
            unset($checking);
            $json = json_encode(['checking_id' => $checking_id, 'company_department_id' => $company_department_id, 'kind_document_id' => $kind_document_id, 'injunction' => $injunction]);
            $response = InjunctionController::AddViolationDisconformity($json);
            if ($response['status'] == 0) {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception($method_name . '. Ошибка при сохранении предписания');
            }

            $json_get_checking = json_encode(['company_department_id' => $company_department_id, 'checking_id' => $checking_id]);
            $get_checking_data = self::GetCheckingData($json_get_checking);
            if ($get_checking_data['status'] == 1) {
                $result = $get_checking_data['Items'];
                $warnings[] = $get_checking_data['warnings'];
            } else {
                $warnings[] = $get_checking_data['warnings'];
                $errors[] = $get_checking_data['errors'];
                throw new Exception($method_name . '. Ошибка получение данных проверки');
            }
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $errors[] = $exception->getFile();
            $status = 0;
        }
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetArchiveNN() - Метод получения архива нарушений несоответствий
     * @param null $data_post - JSON с данными: date_time_start     - дата начала с который необходимо выгрузить
     *                                          date_time_end       - дата окончания до которой необходимо выгрузить
     * @return array - массив следующего вида:
     *
     *
     * @package frontend\controllers\industrial_safety
     *
     * Входные обязательные параметры:
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 05.03.2020 16:06
     */
    public static function GetArchiveNN($data_post = NULL)//TODO 05.03.2020 rudov:  тупо дублирование кода, незнаю зачем, сказали надо отдельно метод
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $archive_injunction = array();                                                                                         // Промежуточный результирующий массив
//        $auditor = array();
        $directions = array();
//        $kind_documents = array();
        $method_name = 'GetArchiveNN';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Данные с фронта не получены');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'date_time_start') ||
                !property_exists($post_dec, 'date_time_end') ||
                !property_exists($post_dec, 'company_department_id')
            )                                                                                                       // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '.Данные с фронта получены';
            $date_time_start = date('Y-m-d', strtotime($post_dec->date_time_start));
            $date_time_end = date('Y-m-d', strtotime($post_dec->date_time_end));
            $company_department_id = $post_dec->company_department_id;

            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $warnings[] = $response['warnings'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception('GetArchiveNN. Ошибка при получении вложенных участков');
            }
            unset($response);

            $warnings[] = $method_name . '. Начало метода.';
            $warnings[] = $method_name . '. Поиск предписание нарушения.';
            /**
             * Виды документов которые необходимо выгрузить
             */
            $kind_documents = [4];                                                                                      // 4 - н/н
            $found_worker = PlaceCompanyDepartment::find()
                ->select(['worker.id', 'CONCAT(`employee`.`last_name`," ",
                                `employee`.`first_name`," ",`employee`.`patronymic`) as full_name',
                    'position.title as position_title', 'worker.tabel_number as worker_tabel_number', 'place_company_department.place_id as place_id'])
                ->leftJoin('worker', 'worker.company_department_id = place_company_department.company_department_id')
                ->leftJoin('employee', 'employee.id = worker.employee_id')
                ->leftJoin('position', 'position.id = worker.position_id')
                ->leftJoin('worker_object', 'worker.id = worker_object.worker_id')
                ->where(['worker_object.role_id' => self::ROLE_CHIEF_DEPARTMENT])
                ->limit(50000)
                ->indexBy('place_id')
                ->asArray()
                ->all();
            $found_data_injunction = Checking::find()
                ->joinWith('companyDepartment.company')
                ->joinWith('checkingWorkerTypes.worker.employee')
                ->joinWith('checkingWorkerTypes.worker.position')
                ->joinWith('injunctions.place')
                ->joinWith('injunctions.firstInjunctionStatuses')
                ->joinWith('injunctions.injunctionViolations.violation.violationType.kindViolation')
                ->where(['in', 'injunction.kind_document_id', $kind_documents])
                ->andWhere(['>=', 'checking.date_time_start', $date_time_start . ' 00:00:00'])
                ->andWhere(['<=', 'checking.date_time_start', $date_time_end . ' 23:59:59'])
                ->andWhere(['in', 'checking.company_department_id', $company_departments])
                ->all();
            if ($found_data_injunction) {
                foreach ($found_data_injunction as $checking) {
                    $auditor = null;
                    $resposible = null;
                    $checking_id = $checking->id;
                    $company_department_id = $checking->company_department_id;
                    $company_title = $checking->companyDepartment->company->title;
                    foreach ($checking->checkingWorkerTypes as $checkingWorkerType) {
                        if ($checkingWorkerType->worker_type_id == self::WORKER_TYPE_AUDITOR) {
                            $auditor['worker_id'] = $checkingWorkerType->worker->id;
                            $full_name = "{$checkingWorkerType->worker->employee->last_name} {$checkingWorkerType->worker->employee->first_name} {$checkingWorkerType->worker->employee->patronymic}";
                            $auditor['worker_full_name'] = $full_name;
                            unset($full_name);
                            $auditor['worker_position_title'] = $checkingWorkerType->worker->position->title;
                            $auditor['worker_staff_number'] = $checkingWorkerType->worker->tabel_number;
                        } elseif ($checkingWorkerType->worker_type_id == self::WORKER_TYPE_RESPONSIBLE) {
                            $resposible['worker_id'] = $checkingWorkerType->worker->id;
                            $full_name = "{$checkingWorkerType->worker->employee->last_name} {$checkingWorkerType->worker->employee->first_name} {$checkingWorkerType->worker->employee->patronymic}";
                            $resposible['worker_full_name'] = $full_name;
                            unset($full_name);
                            $resposible['worker_position_title'] = $checkingWorkerType->worker->position->title;
                            $resposible['worker_staff_number'] = $checkingWorkerType->worker->tabel_number;
                        }
                    }
                    foreach ($checking->injunctions as $injunction) {
                        $injunction_id = $injunction->id;
                        $place_title = $injunction->place->title;
                        $place_id = $injunction->place_id;
                        $inj_status_id = $injunction->status_id;
                        $inj_rtn_status_id = $injunction->rtn_statistic_status_id;
                        foreach ($injunction->injunctionViolations as $injunctionViolation) {
                            if (isset($injunction->firstInjunctionStatuses->date_time)) {
                                $date_first = date('d.m.Y H:i:s', strtotime($injunction->firstInjunctionStatuses->date_time));
                                $date_first_format = date('d.m.Y', strtotime($date_first));
                            } else {
                                $date_first = null;
                                $date_first_format = null;
                            }
                            $archive_injunction[$injunction_id]['checking_id'] = $checking_id;
                            $ppk_id = explode("_", $checking->nn_id);
                            if (isset($ppk_id[1])) {
                                $archive_injunction[$injunction_id]['ppk_id'] = $ppk_id[1];
                            } else {
                                $archive_injunction[$injunction_id]['ppk_id'] = "";
                            }
                            $archive_injunction[$injunction_id]['company_department_id'] = $company_department_id;
                            $archive_injunction[$injunction_id]['injunction_violation_id'] = $injunctionViolation->id;
                            $archive_injunction[$injunction_id]['injunction_id'] = $injunction_id;
                            $archive_injunction[$injunction_id]['date_time'] = $date_first;
                            $archive_injunction[$injunction_id]['date_time_formated'] = $date_first_format;
                            $archive_injunction[$injunction_id]['place_title'] = $place_title;
                            if (empty($resposible)) {
                                if (!empty($found_worker[$place_id]['id'])) {
                                    $resposible ['worker_id'] = $found_worker[$place_id]['id'];
                                    $resposible ['worker_full_name'] = $found_worker[$place_id]['full_name'];
                                    $resposible ['worker_position_title'] = $found_worker[$place_id]['position_title'];
                                    $resposible ['worker_staff_number'] = $found_worker[$place_id]['worker_tabel_number'];
                                } else {
                                    $resposible ['worker_id'] = null;
                                    $resposible ['worker_full_name'] = null;
                                    $resposible ['worker_position_title'] = null;
                                    $resposible ['worker_staff_number'] = null;
                                }
                            }
                            $archive_injunction[$injunction_id]['responsible_worker'] = $resposible;
                            $directions[] = $injunctionViolation->violation->violationType->title;
                            $archive_injunction[$injunction_id]['direction'] = array_unique($directions);
                            $archive_injunction[$injunction_id]['auditor'] = $auditor;
                            $archive_injunction[$injunction_id]['department_title'] = $company_title;
                            $archive_injunction[$injunction_id]['rtn_statistic_status_id'] = $inj_rtn_status_id;
                            $archive_injunction[$injunction_id]['status_id'] = $inj_status_id;
                        }
                        unset($directions);
                    }
                }
                unset($found_data_injunction, $found_worker);
            } else {
                $errors[] = $method_name . '. Нет данных';
            }

        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода.';
        $result = $archive_injunction;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetMineInjunctions() - получение данных для архива предписаний по шахтам
     *
     * @param integer year - год, за который получаем данные с сервера
     * @param integer month - месяц, за который получаем данные с сервера
     * @param string period - тип период, за который получаем данные с сервера  - год/месяц 'month/year'
     * @param integer company_department_id - код подразделения по которому получаем список предписаний, н/н, ПАБ/ предписаний РТН
     *
     * @return array - массив со следующей структурой:
     * mineInjunctionItem: {                                                                                            //Список предписания по шахте
     *      checking_id: null,                                                                                          // Ключ проверки
     *      injunction_id: null,                                                                                        // Ключ предписания
     *      date_time: "",                                                                                              // Дата выдачи предписания
     *      date_time_format: '',                                                                                       // Форматированная дата выдачи
     *      givens: {                                                                                                    // Список выдавшего предписания
     *          {worker_id}                                                                                                 // ключ работника
     *              worker_full_name: '',                                                                                       // ФИО выдавшего предписание
     *              worker_id: null,                                                                                            // Ключ выдавшего предписание
     *              worker_position_id: null,                                                                                   // Ключ должности выдавшего предписание
     *              worker_position_title: '',                                                                                  // Наименование должности выдашего предписание
     *              worker_staff_number: ""                                                                                     // Табельный номер выдавшего предписание
     *      },
     *      responsibles: {                                                                                              // Список ответственного
     *          {worker_id}                                                                                                 // ключ работника
     *              worker_full_name: '',                                                                                       // ФИО ответственного
     *              worker_id: null,                                                                                            // Ключ ответственного
     *              worker_position_id: null,                                                                                   // Ключ должности ответственного
     *              worker_position_title: '',                                                                                  // Наименование должности ответственного
     *              worker_staff_number: ""                                                                                     // Табельный номер ответственного
     *      },
     *      place_id: null,                                                                                             // Ключ места нарушения
     *      place_title: "",                                                                                            // Наименование места нарушения
     *      company_department_id: null,                                                                                // Ключ участка
     *      company_department_title: "",                                                                               // Наименование участка
     *      direction: [],                                                                                              // массив видов и типов направлений нарушений
     *      rtn_statistic_status: "",                                                                                   // Статус РТН
     *      injunction_violation_status_id: null,                                                                       // Ключ статуса предписания
     *      injunction_violation_status_title: "",                                                                      // Наименование статуса предписания
     *      ppk_id: null,                                                                                               // Ключ ППК
     *      kind_document_id: null,                                                                                     // ключ вида документа
     * },
     * @throws Exception
     * @example http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Checking&method=GetMineInjunctions&subscribe=&data={%22company_department_id%22:4029720,%22year%22:%222020%22,%22month%22:%2201%22,%22period%22:%22month%22}
     * @example http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Checking&method=GetMineInjunctions&subscribe=&data={%22company_department_id%22:4029720,%22year%22:%222020%22,%22month%22:%2201%22,%22period%22:%22year%22}
     *
     * @author Якимов М.Н.
     * Created date: on 27.01.2021 08:20
     * @package frontend\controllers\industrial_safety
     *
     */
    public static function GetMineInjunctions($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $archive_injunction = array();                                                                                  // Промежуточный результирующий массив
        $directions = array();
        $method_name = 'GetMineInjunctions. ';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . 'Данные с фронта не получены');
            }
            $warnings[] = $method_name . 'Данные успешно переданы';
            $warnings[] = $method_name . 'Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . 'Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'company_department_id') ||                                       // ключ департамента
                !property_exists($post_dec, 'year') ||                                                         // год
                !property_exists($post_dec, 'month') ||                                                        // месяц
                !property_exists($post_dec, 'period')                                                         // период 'month/year'
            )                                                                                                       // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . 'Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . 'Данные с фронта получены';

            $period = $post_dec->period;                                                                                // период за который строится статистика
            $year = $post_dec->year;                                                                                    // год за который строится статистика
            $month = $post_dec->month;                                                                                  // месяц за который строится статистика

            if ($period === 'month') {
                $count_day = cal_days_in_month(CAL_GREGORIAN, $month, $year);                                   // количество дней в месяце
                $date_time_start = date('Y-m-d', strtotime($year . '-' . $month . '-' . 01));            // период за месяц до конца месяца
                $date_time_end = date('Y-m-d', strtotime($year . '-' . $month . '-' . $count_day));      // период за месяц до конца месяца
            } elseif ($period === 'year') {
                $date_time_start = date('Y-m-d', strtotime($year . '-01-01'));                           // период за год до конца года
                $date_time_end = date('Y-m-d', strtotime($year . '-12-31'));                             // период за год до конца года
                $month = null;                                                                                          // принудительно обнуляем переменную месяц для исключения ее из фильтров запросов (Null)
            } else {
                throw new Exception($method_name . '. Некорректный период: ' . $period);
            }

            $company_department_id = $post_dec->company_department_id;                                                  // подразделение по которому нужно получить статистику

            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $warnings[] = $response['warnings'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception($method_name . '. Ошибка при получении вложенных участков');
            }
            unset($response);

            $warnings[] = $method_name . 'Начало метода.';
            $warnings[] = $method_name . 'Поиск предписание нарушения.';
            /**
             * Виды документов которые необходимо выгрузить
             */
            $found_data_injunction = Checking::find()
                ->joinWith('injunctions.injunctionViolations.violation.violationType.kindViolation')
                ->joinWith('injunctions.place.mine')
                ->joinWith('injunctions.companyDepartment.company')
                ->joinWith('injunctions.rtnStatisticStatus')
                ->joinWith('injunctions.status')
                ->joinWith('checkingWorkerTypes.worker.employee')
                ->joinWith('checkingWorkerTypes.worker.position')
                ->where(['>=', 'checking.date_time_start', $date_time_start . ' 00:00:00'])
                ->andWhere(['<=', 'checking.date_time_start', $date_time_end . ' 23:59:59'])
                ->andWhere(['in', 'injunction.company_department_id', $company_departments])
                ->all();

            if ($found_data_injunction) {
                foreach ($found_data_injunction as $checking) {
                    $date_time = $checking->date_time_start;
                    $date_time_format = date('d.m.Y', strtotime($date_time));

                    $given = null;
                    $givens = [];
                    $responsible = null;
                    $responsibles = [];

                    $ppk_id_rtn = $checking->rostex_number;
                    $ppk_id_instruct = $checking->instruct_id;
                    $ppk_id_nn = explode("_", $checking->nn_id);
                    $ppk_id_pab = explode("_", $checking->pab_id);

                    foreach ($checking->checkingWorkerTypes as $checkingWorkerType) {

                        if ($checkingWorkerType->worker_type_id == self::WORKER_TYPE_AUDITOR) {
                            $given['worker_id'] = $checkingWorkerType->worker->id;
                            $full_name = "{$checkingWorkerType->worker->employee->last_name} {$checkingWorkerType->worker->employee->first_name} {$checkingWorkerType->worker->employee->patronymic}";
                            $given['worker_full_name'] = $full_name;
                            unset($full_name);
                            $given['worker_position_id'] = $checkingWorkerType->worker->position_id;
                            $given['worker_position_title'] = $checkingWorkerType->worker->position->title;
                            $given['worker_staff_number'] = $checkingWorkerType->worker->tabel_number;
                            $givens[$checkingWorkerType->worker->id] = $given;
                        } elseif ($checkingWorkerType->worker_type_id == self::WORKER_TYPE_RESPONSIBLE) {
                            $responsible['worker_id'] = $checkingWorkerType->worker->id;
                            $full_name = "{$checkingWorkerType->worker->employee->last_name} {$checkingWorkerType->worker->employee->first_name} {$checkingWorkerType->worker->employee->patronymic}";
                            $responsible['worker_full_name'] = $full_name;
                            unset($full_name);
                            $responsible['worker_position_id'] = $checkingWorkerType->worker->position_id;
                            $responsible['worker_position_title'] = $checkingWorkerType->worker->position->title;
                            $responsible['worker_staff_number'] = $checkingWorkerType->worker->tabel_number;
                            $responsibles[$checkingWorkerType->worker->id] = $responsible;
                        }
                    }


                    foreach ($checking->injunctions as $injunction) {
                        $injunction_id = $injunction->id;
                        foreach ($injunction->injunctionViolations as $injunctionViolation) {
                            $directions[] = $injunctionViolation->violation->violationType->kindViolation->title . " /" . $injunctionViolation->violation->violationType->title;
                        }

                        $archive_injunction[$injunction_id]['checking_id'] = $checking->id;
                        $archive_injunction[$injunction_id]['injunction_id'] = $injunction_id;
                        $archive_injunction[$injunction_id]['date_time'] = $date_time;
                        $archive_injunction[$injunction_id]['date_time_formated'] = $date_time_format;
                        $archive_injunction[$injunction_id]['place_id'] = $injunction->place_id;
                        $archive_injunction[$injunction_id]['place_title'] = isset($injunction->place->title) ? ($injunction->place->mine->title . " / " . $injunction->place->title) : 'Нет места в справочнике';
                        $archive_injunction[$injunction_id]['company_department_id'] = $injunction->company_department_id;
                        $archive_injunction[$injunction_id]['company_department_title'] = $injunction->companyDepartment->company->title;
                        if (isset($directions)) {
                            $archive_injunction[$injunction_id]['direction'] = array_unique($directions);
                        } else {
                            $archive_injunction[$injunction_id]['direction'] = [];
                        }
                        $archive_injunction[$injunction_id]['rtn_statistic_status'] = $injunction->rtnStatisticStatus->title;
                        $archive_injunction[$injunction_id]['rtn_statistic_status_id'] = $injunction->rtn_statistic_status_id;
                        $archive_injunction[$injunction_id]['injunction_violation_status_id'] = $injunction->status_id;
                        $archive_injunction[$injunction_id]['injunction_violation_status_title'] = $injunction->status->title;
                        $archive_injunction[$injunction_id]['kind_document_id'] = $injunction->kind_document_id;
                        $archive_injunction[$injunction_id]['givens'] = $givens;
                        $archive_injunction[$injunction_id]['responsibles'] = $responsibles;


                        if ($ppk_id_rtn) {
                            $archive_injunction[$injunction_id]['ppk_id'] = "РТН № " . $ppk_id_rtn;
                        } else if ($ppk_id_instruct) {
                            $archive_injunction[$injunction_id]['ppk_id'] = "Предп. № " . $ppk_id_instruct;
                        } else if ($ppk_id_nn and isset($ppk_id_nn[1])) {
                            $archive_injunction[$injunction_id]['ppk_id'] = "Н/Н № " . $ppk_id_nn[1];
                        } else if ($ppk_id_pab and isset($ppk_id_pab[1])) {
                            $archive_injunction[$injunction_id]['ppk_id'] = "ПАБ № " . $ppk_id_pab[1];
                        } else {
                            $archive_injunction[$injunction_id]['ppk_id'] = "";
                        }

                        unset($directions);
                    }
                    unset($givens);
                    unset($responsibles);
                }
            } else {
                $errors[] = $method_name . 'Нет данных';
            }

        } catch (Throwable $exception) {
            $errors[] = $method_name . 'Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . 'Конец метода.';
        $result = $archive_injunction;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод SaveCheckingGratitude() - Сохранение благодарности
     * @param null $data_post - JSON c данной структурой:
     *      "checking_gratitude":{
     *          "checking_gratitude_status":"del",
     *          "checking_gratitude_id":-1,
     *          "checking_id":1,
     *          "company_department_id":1,
     * 	        "place_id":1,
     *          "comment":"",
     *          "date_time":"",
     *          "checking_gratitude_workers":{
     *              "-10":{
     *                  "checking_gratitude_worker_id":-10,
     *                  "checking_gratitude_worker_status":"del"
     *                  "worker_id":-10
     *              },
     *              "...":{}
     *          },
     *          "checking_gratitude_attachments":{
     *              "-2":{
     *                  "checking_gratitude_attachment_id":-2,
     *                  "attachment_id":-1,
     *                  "attachment_status":"update",
     *                  "attachment_path":"",
     *                  "attachment_title":"",
     *                  "attachment_type":"png",
     *                  "attachment_blob":""
     *              },
     *              "...":{}
     *          }
     *      }
     * @return array
     * @package frontend\controllers\industrial_safety
     * @example http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Checking&method=SaveCheckingGratitude&subscribe=&data={}
     */
    public static function SaveCheckingGratitude($data_post = NULL)
    {
        $log = new LogAmicumFront("SaveCheckingGratitude");

        $result = null;
        $attachments = array();
        $workers = array();

        try {
            $log->addLog("Начало выполнения метода");

            if (is_null($data_post) or $data_post == "") {
                throw new Exception("Входной массив данных post не передан");
            }

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];

            if (
                !property_exists($post, 'checking_gratitude') ||
                !property_exists($post->checking_gratitude, 'checking_gratitude_status') || $post->checking_gratitude->checking_gratitude_status == '' ||
                !property_exists($post->checking_gratitude, 'checking_gratitude_id') || $post->checking_gratitude->checking_gratitude_id == '' ||
                !property_exists($post->checking_gratitude, 'checking_id') || $post->checking_gratitude->checking_id == '' ||
                !property_exists($post->checking_gratitude, 'company_department_id') || $post->checking_gratitude->company_department_id == '' ||
                !property_exists($post->checking_gratitude, 'place_id') || $post->checking_gratitude->place_id == '' ||
                !property_exists($post->checking_gratitude, 'comment') || $post->checking_gratitude->comment == '' ||
                !property_exists($post->checking_gratitude, 'date_time') || $post->checking_gratitude->date_time == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $checking_gratitude = (array) $post->checking_gratitude;

            if ($checking_gratitude['checking_gratitude_status'] == 'del') {
                CheckingGratitude::deleteAll(['id' => $checking_gratitude['checking_gratitude_id']]);
                $checking_gratitude['checking_gratitude_id'] = null;
                $checking_gratitude['checking_gratitude_workers'] = null;
                $checking_gratitude['checking_gratitude_attachments'] = null;
                $result = $checking_gratitude;
            } else {
                $model_c_gratitude = CheckingGratitude::find()
                    ->joinWith('checkingGratitudeAttachments.attachment')
                    ->joinWith('checkingGratitudeWorkers')
                    ->where(['checking_gratitude.id' => $checking_gratitude['checking_gratitude_id']])
                    ->one();

                if (!$model_c_gratitude) {
                    $model_c_gratitude = new CheckingGratitude();
                } else {
                    if (isset($model_c_gratitude->checkingGratitudeAttachments)) {
                        foreach ($model_c_gratitude->checkingGratitudeAttachments as $model_c_g_attachment) {
                            $attachments[$model_c_g_attachment->id] = $model_c_g_attachment;
                        }
                    }
                    if (isset($model_c_gratitude->checkingGratitudeWorkers)) {
                        foreach ($model_c_gratitude->checkingGratitudeWorkers as $model_c_g_worker) {
                            $workers[$model_c_g_worker->id] = $model_c_g_worker;
                        }
                    }
                }
                $model_c_gratitude->checking_id = $checking_gratitude['checking_id'];
                $model_c_gratitude->company_department_id = $checking_gratitude['company_department_id'];
                $model_c_gratitude->place_id = $checking_gratitude['place_id'];
                $model_c_gratitude->comment = $checking_gratitude['comment'];
                $model_c_gratitude->date_time = $checking_gratitude['date_time'];
                if (!$model_c_gratitude->save()) {
                    $log->addData($model_c_gratitude->errors, '$model_c_gratitude->errors', __LINE__);
                    throw new Exception("Ошибка сохранения модели CheckingGratitude");
                }
                $checking_gratitude['checking_gratitude_id'] = $model_c_gratitude->id;

                foreach ($checking_gratitude['checking_gratitude_workers'] as $checking_gratitude_worker) {
                    if ($checking_gratitude_worker->checking_gratitude_worker_status == "del") {
                        CheckingGratitudeWorker::deleteAll(['id' => $checking_gratitude_worker->checking_gratitude_worker_id]);
                    } else {
                        if (isset($workers[$checking_gratitude_worker->checking_gratitude_worker_id])) {
                            $model_c_g_worker = $workers[$checking_gratitude_worker->checking_gratitude_worker_id];
                        } else {
                            $model_c_g_worker = new CheckingGratitudeWorker();
                        }
                        $model_c_g_worker->checking_gratitude_id = $model_c_gratitude->id;
                        $model_c_g_worker->worker_id = $checking_gratitude_worker->worker_id;
                        if (!$model_c_g_worker->save()) {
                            $log->addData($model_c_g_worker->errors, '$model_c_g_worker->errors', __LINE__);
                            throw new Exception("Ошибка сохранения модели CheckingGratitudeWorker");
                        }
                        $checking_gratitude_worker->checking_gratitude_worker_id = $model_c_g_worker->id;
                        $checking_gratitude_worker->worker_id = $model_c_g_worker->worker_id;
                        $checking_gratitude_workers[$model_c_g_worker->id] = $checking_gratitude_worker;
                    }
                }
                if (isset($checking_gratitude_workers)) {
                    $checking_gratitude['checking_gratitude_workers'] = $checking_gratitude_workers;
                }

                foreach ($checking_gratitude['checking_gratitude_attachments'] as $c_g_attachment) {
                    if (isset($attachments[$c_g_attachment->checking_gratitude_attachment_id])) {
                        $model_c_g_attachment = $attachments[$c_g_attachment->checking_gratitude_attachment_id];
                    } else {
                        $model_c_g_attachment = new CheckingGratitudeAttachment();
                    }
                    $model_c_g_attachment->checking_gratitude_id = $model_c_gratitude->id;

                    if ($c_g_attachment->attachment_status == "del") {
                        CheckingGratitudeAttachment::deleteAll(['id' => $c_g_attachment->attachment_id]);
                    } else {
                        $session = Yii::$app->session;
                        if (
                            isset($model_c_g_attachment->attachment) &&
                            $model_c_g_attachment->attachment->id == $c_g_attachment->attachment_id &&
                            $c_g_attachment->attachment_status == "update"
                        ) {
                            $model_attachment = $model_c_g_attachment->attachment;
                        } else {
                            $model_attachment = new Attachment();
                            $c_g_attachment->attachment_path = Assistant::UploadFile(
                                $c_g_attachment->attachment_blob,
                                $c_g_attachment->attachment_title,
                                'attachment'
                            );
                            $c_g_attachment->attachment_blob = null;
                            $model_attachment->path = $c_g_attachment->attachment_path;
                            $model_attachment->title = $c_g_attachment->attachment_title;
                            $model_attachment->attachment_type = $c_g_attachment->attachment_type;
                            $model_attachment->date = BackendAssistant::GetDateFormatYMD();
                            $model_attachment->section_title = 'Благодарность';
                            $model_attachment->worker_id = $session['worker_id'];
                            if (!$model_attachment->save()) {
                                $log->addData($model_attachment->errors, '$model_attachment->errors', __LINE__);
                                throw new Exception("Ошибка сохранения модели Attachment");
                            }
                            $log->addLog('Вложение успешно сохранено');
                        }
                        $model_c_g_attachment->attachment_id = $model_attachment->id;
                        if (!$model_c_g_attachment->save()) {
                            $log->addData($model_c_g_attachment->errors, '$model_c_g_attachment->errors', __LINE__);
                            throw new Exception("Ошибка сохранения модели CheckingGratitudeAttachment");
                        }
                        $c_g_attachment->checking_gratitude_attachment_id = $model_c_g_attachment->id;
                        $c_g_attachment->attachment_id = $model_attachment->id;
                        $checking_gratitude_attachments[$model_c_g_attachment->id] = $c_g_attachment;
                    }
                }
                if (isset($checking_gratitude_attachments)) {
                    $checking_gratitude['checking_gratitude_attachments'] = $checking_gratitude_attachments;
                }

                $result['checking_gratitude'] = $checking_gratitude;
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод GetCheckingGratitudeList() - получения списка благодарностей
     * @param null $data_post - JSON c данной структурой: {"checking_id":1}
     * @return array
     *      "10":{
     *          "checking_gratitude_id":10,
     *          "checking_id":1,
     *          "company_department_id":1,
     *          "company_title":"",
     * 	        "place_id":1,
     *          "place_title":"",
     *          "comment":"",
     *          "date_time":"",
     *          "checking_gratitude_workers":{
     *              "1":{
     *                  "checking_gratitude_worker_id":1,
     *                  "worker_id":100,
     *                  "worker_full_name":"",
     *                  "worker_position_title":""
     *              },
     *              "...":{}
     *          },
     *          "checking_gratitude_attachments":{
     *              "2":{
     *                  "checking_gratitude_attachment_id":2,
     *                  "attachment_id":1,
     *                  "attachment_path":"",
     *                  "attachment_title":"",
     *                  "attachment_type":"png"
     *              },
     *              "...":{}
     *          }
     *      },
     *      "...":{}
     * @package frontend\controllers\industrial_safety
     * @example http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Checking&method=GetCheckingGratitudeList&subscribe=&data={}
     */
    public static function GetCheckingGratitudeList($data_post = NULL)
    {
        $log = new LogAmicumFront("GetCheckingGratitudeList");

        $result = null;
        $attachments = array();
        $workers = array();

        try {
            $log->addLog("Начало выполнения метода");

            if (is_null($data_post) or $data_post == "") {
                throw new Exception("Входной массив данных post не передан");
            }

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];

            if (
                !property_exists($post, 'checking_id') || $post->checking_id == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $checking_id = $post->checking_id;

            $model_c_gratitudes = CheckingGratitude::find()
                ->joinWith('checkingGratitudeAttachments.attachment')
                ->joinWith('checkingGratitudeWorkers.worker.employee')
                ->joinWith('checkingGratitudeWorkers.worker.position')
                ->joinWith('companyDepartment.company')
                ->joinWith('place')
                ->where(['checking_id' => $checking_id])
                ->all();

            foreach ($model_c_gratitudes as $model_c_gratitude) {
                $checking_gratitude['checking_gratitude_id'] = $model_c_gratitude->id;
                $checking_gratitude['checking_id'] = $model_c_gratitude->checking_id;
                $checking_gratitude['company_department_id'] = $model_c_gratitude->company_department_id;
                $checking_gratitude['company_title'] = $model_c_gratitude->companyDepartment->company->title;
                $checking_gratitude['place_id'] = $model_c_gratitude->place_id;
                $checking_gratitude['place_title'] = $model_c_gratitude->place->title;
                $checking_gratitude['comment'] = $model_c_gratitude->comment;
                $checking_gratitude['date_time'] = $model_c_gratitude->date_time;
                foreach ($model_c_gratitude->checkingGratitudeWorkers as $model_c_g_worker) {
                    $checking_gratitude_worker['checking_gratitude_worker_id'] = $model_c_g_worker->id;
                    $checking_gratitude_worker['worker_id'] = $model_c_g_worker->worker_id;
                    $fullName = Assistant::GetFullName($model_c_g_worker->worker->employee->first_name,$model_c_g_worker->worker->employee->patronymic,$model_c_g_worker->worker->employee->last_name);
                    $checking_gratitude_worker['worker_full_name'] = $fullName;
                    $checking_gratitude_worker['worker_position_title'] = $model_c_g_worker->worker->position->title;
                    $checking_gratitude['checking_gratitude_workers'][$model_c_g_worker->id] = $checking_gratitude_worker;
                }
                foreach ($model_c_gratitude->checkingGratitudeAttachments as $model_c_g_attachment) {
                    $checking_gratitude_attachment['checking_gratitude_attachment_id'] = $model_c_g_attachment->id;
                    $checking_gratitude_attachment['attachment_id'] = $model_c_g_attachment->attachment->id;
                    $checking_gratitude_attachment['attachment_path'] = $model_c_g_attachment->attachment->path;
                    $checking_gratitude_attachment['attachment_title'] = $model_c_g_attachment->attachment->title;
                    $checking_gratitude_attachment['attachment_type'] = $model_c_g_attachment->attachment->attachment_type;
                    $checking_gratitude['checking_gratitude_attachments'][$model_c_g_attachment->id] = $checking_gratitude_attachment;
                }
                $result[$model_c_gratitude->id] = $checking_gratitude;
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }
}


