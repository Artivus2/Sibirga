<?php

namespace frontend\controllers\industrial_safety;

use backend\controllers\Assistant as BackendAssistant;
use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\handbooks\DepartmentController;
use frontend\models\Attachment;
use frontend\models\CheckKnowledge;
use frontend\models\CheckKnowledgeWorker;
use frontend\models\CheckProtocol;
use frontend\models\ReasonCheckKnowledge;
use Throwable;
use Yii;
use yii\web\Controller;

class CheckKnowledgeController extends Controller
{
    #region Структура контроллера
    //GetCheckKnowledge                 - Метод получения проверки знаний/аттестации у сотрудников и ИТР
    //SaveCheckKnowledge                - Метод сохранение проверки знаний/аттестации
    //DeleteCheckKnowledge              - Удаление проверки знаний/аттестации
    //DeleteCheckKnowledgeWorker        - Удаление работника с проверки знаний/аттестации
    #endregion

    #region Блок констант
    const PASSED = 79;

    #endregion

    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод GetCheckKnowledge() - Метод получения проверки знаний/аттестации у сотрудников и ИТР
     * @param null $data_post - JSON с данными: идентификатор участка, тип проверки знаний
     * @return array массив данных со структурой: [company_department_id]
     *                                                          company_department_id:
     *                                                          company_title:
     *                                                          [worker_list]
     *                                                                  [worker_id]
     *                                                                          worker_id:
     *                                                                          date:
     *                                                                          full_name:
     *                                                                          stuff_number:
     *                                                                          role_title:
     *                                                                          number_certificate:
     *                                                                          [protocol]
     *                                                                                [check_protocol_id]
     *                                                                                          check_protocol_id:
     *                                                                                          document_attachment_id:
     *                                                                                          attachment_path:
     *                                                                                          attachment_blob:
     *                                                                                          title:
     *                                                                                          attachment_type:
     *                                                                                          sketch:
     *                                                                                          attachment_status:
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\CheckKnowledge&method=GetCheckKnowledge&subscribe=&data={%22company_department_id%22:4029938,%22type_check_knowledge%22:1}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 19.10.2019 14:14
     */
    public static function GetCheckKnowledge($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $check_knowledge = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = 'GetCheckKnowledge. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('GetCheckKnowledge. Не переданы входные параметры');
            }
            $warnings[] = 'GetCheckKnowledge. Данные успешно переданы';
            $warnings[] = 'GetCheckKnowledge. Входной мас3сив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GetCheckKnowledge. Декодировал входные параметры';
            if (!property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'type_check_knowledge'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('GetCheckKnowledge. Переданы некорректные входные параметры');
            }
            $warnings[] = 'GetCheckKnowledge. Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;
            $type_check_knowledge = $post_dec->type_check_knowledge;

            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception('GetFireFightEquipmentSpecific. Ошибка получения вложенных департаментов' . $company_department_id);
            }
            $found_check_knowledge_worker = CheckKnowledge::find()
                ->select('
                    check_knowledge.date                        as check_knowledge_date,
                    check_knowledge.id                          as check_knowledge_id,
                    check_knowledge.type_check_knowledge_id     as type_check_knowledge_id,
                    e.last_name                                 as last_name,
                    e.first_name                                as first_name,
                    w.id                                        as worker_id,
                    w.tabel_number                              as tabel_number,
                    e.patronymic                                as patronymic,
                    po.title                                    as position_title,
                    po.id                                       as position_id,
                    c.title                                     as company_title,
                    cd.id                                       as company_department_id,
                    ckw.number_certificate                      as number_certificate,
                    ckw.id                                      as check_knowledge_worker_id,
                    a2.path                                     as attachment_path,
                    a2.id                                       as attachment_id,
                    a2.title                                    as title,
                    a2.attachment_type                          as attachment_type,
                    cp.id                                       as check_protocol_id,
                    a2.id                                       as attachment_id,
                    a2.sketch                                   as sketch,
                    reason_check_knowledge.title                as reason_check_knowledge_title,
                    reason_check_knowledge.id                   as reason_check_knowledge_id
                ')
                ->leftJoin('check_knowledge_worker ckw', 'check_knowledge.id = ckw.check_knowledge_id')
                ->leftJoin('worker w', 'ckw.worker_id = w.id')
                ->leftJoin('position po', 'w.position_id = po.id')
                ->leftJoin('employee e', ' w.employee_id = e.id')
                ->leftJoin('check_protocol cp', 'check_knowledge.id = cp.check_knowledge_id')
                ->leftJoin('attachment a2', 'cp.attachment_id = a2.id')
                ->leftJoin('company_department cd', 'check_knowledge.company_department_id = cd.id')
                ->leftJoin('company c', 'cd.company_id = c.id')
                ->leftJoin('reason_check_knowledge', 'reason_check_knowledge.id = check_knowledge.reason_check_knowledge_id')
                ->where(['in', 'check_knowledge.company_department_id', $company_departments])
                ->andWhere(['check_knowledge.type_check_knowledge_id' => $type_check_knowledge])
                ->asArray()
                ->all();
            if (isset($found_check_knowledge_worker)) {
                foreach ($found_check_knowledge_worker as $check_knowledge_worker) {
                    $check_knowledge[$check_knowledge_worker['company_department_id']][$check_knowledge_worker['check_knowledge_id']]['company_department_id'] = $check_knowledge_worker['company_department_id'];
                    $check_knowledge[$check_knowledge_worker['company_department_id']][$check_knowledge_worker['check_knowledge_id']]['company_title'] = $check_knowledge_worker['company_title'];
                    $check_knowledge[$check_knowledge_worker['company_department_id']][$check_knowledge_worker['check_knowledge_id']]['check_knowledge_id'] = (int)$check_knowledge_worker['check_knowledge_id'];
                    $check_knowledge[$check_knowledge_worker['company_department_id']][$check_knowledge_worker['check_knowledge_id']]['type_check_knowledge_id'] = (int)$check_knowledge_worker['type_check_knowledge_id'];
                    $check_knowledge[$check_knowledge_worker['company_department_id']][$check_knowledge_worker['check_knowledge_id']]['date'] = $check_knowledge_worker['check_knowledge_date'];
                    $check_knowledge[$check_knowledge_worker['company_department_id']][$check_knowledge_worker['check_knowledge_id']]['date_formated'] = date('d.m.Y',strtotime($check_knowledge_worker['check_knowledge_date']));
                    $check_knowledge[$check_knowledge_worker['company_department_id']][$check_knowledge_worker['check_knowledge_id']]['reason_check_knowledge_id'] = $check_knowledge_worker['reason_check_knowledge_id'];
                    $check_knowledge[$check_knowledge_worker['company_department_id']][$check_knowledge_worker['check_knowledge_id']]['reason_check_knowledge_title'] = $check_knowledge_worker['reason_check_knowledge_title'];
                    $check_knowledge[$check_knowledge_worker['company_department_id']][$check_knowledge_worker['check_knowledge_id']]['protocol'] = array();
                    $check_knowledge[$check_knowledge_worker['company_department_id']][$check_knowledge_worker['check_knowledge_id']]['protocol']['check_protocol_id'] = $check_knowledge_worker['check_protocol_id'];
                    $check_knowledge[$check_knowledge_worker['company_department_id']][$check_knowledge_worker['check_knowledge_id']]['protocol']['attachment_id'] = (int)$check_knowledge_worker['attachment_id'];
                    $check_knowledge[$check_knowledge_worker['company_department_id']][$check_knowledge_worker['check_knowledge_id']]['protocol']['attachment_path'] = $check_knowledge_worker['attachment_path'];
                    $check_knowledge[$check_knowledge_worker['company_department_id']][$check_knowledge_worker['check_knowledge_id']]['protocol']['attachment_blob'] = (object)array();
                    $check_knowledge[$check_knowledge_worker['company_department_id']][$check_knowledge_worker['check_knowledge_id']]['protocol']['title'] = $check_knowledge_worker['title'];
                    $check_knowledge[$check_knowledge_worker['company_department_id']][$check_knowledge_worker['check_knowledge_id']]['protocol']['attachment_type'] = $check_knowledge_worker['attachment_type'];
                    $check_knowledge[$check_knowledge_worker['company_department_id']][$check_knowledge_worker['check_knowledge_id']]['protocol']['sketch'] = $check_knowledge_worker['sketch'];
                    $check_knowledge[$check_knowledge_worker['company_department_id']][$check_knowledge_worker['check_knowledge_id']]['protocol']['attachment_status'] = '';
                    if ($check_knowledge_worker['check_protocol_id'] == null) {
                        $check_knowledge[$check_knowledge_worker['company_department_id']][$check_knowledge_worker['check_knowledge_id']]['protocol'] = (object)array();
                    }
                    $check_knowledge[$check_knowledge_worker['company_department_id']][$check_knowledge_worker['check_knowledge_id']]['workers'][$check_knowledge_worker['worker_id']]['worker_id'] = $check_knowledge_worker['worker_id'];
                    $check_knowledge[$check_knowledge_worker['company_department_id']][$check_knowledge_worker['check_knowledge_id']]['workers'][$check_knowledge_worker['worker_id']]['check_knowledge_worker_id'] = $check_knowledge_worker['check_knowledge_worker_id'];
                    $check_knowledge[$check_knowledge_worker['company_department_id']][$check_knowledge_worker['check_knowledge_id']]['workers'][$check_knowledge_worker['worker_id']]['full_name'] = "{$check_knowledge_worker['last_name']} {$check_knowledge_worker['first_name']} {$check_knowledge_worker['patronymic']}";
                    $check_knowledge[$check_knowledge_worker['company_department_id']][$check_knowledge_worker['check_knowledge_id']]['workers'][$check_knowledge_worker['worker_id']]['stuff_number'] = $check_knowledge_worker['tabel_number'];
                    $check_knowledge[$check_knowledge_worker['company_department_id']][$check_knowledge_worker['check_knowledge_id']]['workers'][$check_knowledge_worker['worker_id']]['position_title'] = $check_knowledge_worker['position_title'];
                    $check_knowledge[$check_knowledge_worker['company_department_id']][$check_knowledge_worker['check_knowledge_id']]['workers'][$check_knowledge_worker['worker_id']]['position_id'] = $check_knowledge_worker['position_id'];
                    $check_knowledge[$check_knowledge_worker['company_department_id']][$check_knowledge_worker['check_knowledge_id']]['workers'][$check_knowledge_worker['worker_id']]['number_certificate'] = $check_knowledge_worker['number_certificate'];
                    if ($check_knowledge_worker['worker_id'] == null) {
                        $check_knowledge[$check_knowledge_worker['company_department_id']][$check_knowledge_worker['check_knowledge_id']]['workers'] = (object)array();
                    }
                }
            }

        } catch (Throwable $exception) {
            $errors[] = 'GetCheckKnowledge. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetCheckKnowledge. Конец метода';
        $result = $check_knowledge;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод SaveCheckKnowledge() - Метод сохранение проверки знаний/аттестации
     * @param null $data_post - JSON с данными: идентификатор участка, тип проверки знаний, дата, работники, вложение
     * @return array - стандартный массив выходных данных
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\CheckKnowledge&method=SaveCheckKnowledge&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 19.10.2019 16:04
     */
    public static function SaveCheckKnowledge($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $check_knowledge = array();                                                                                // Промежуточный результирующий массив
        $result = array();
        $inserted_check_knowledge_workers_status = array();
        $session = Yii::$app->session;
        $date_time_now = date('Y-m-d H:i:s',strtotime(BackendAssistant::GetDateNow()));
        $warnings[] = 'SaveCheckKnowledge. Начало метода';
//        $data_post = '{"company_department_id":"4029938","company_title":"Участок подготовительных работ №1","check_knowledge_id":41,"type_check_knowledge_id":1,"date":"2019-10-22","date_formated":"10.10.2019","protocol":{"check_protocol_id":-3,"attachment_id":94,"attachment_path":"/img/attachment/111_19-10-2019 11-48-23.1571478503.pdf","attachment_blob":{},"title":"111","attachment_type":"pdf","sketch":null,"attachment_status":""},"workers":{"2002726":{"worker_id":"2002726","number_certificate": "123"},"2020152":{"worker_id":"2020152","number_certificate": "123"}}}';
        try {
            $transaction = Yii::$app->db->beginTransaction();
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('SaveCheckKnowledge. Не переданы входные параметры');
            }
            $warnings[] = 'SaveCheckKnowledge. Данные успешно переданы';
            $warnings[] = 'SaveCheckKnowledge. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'SaveCheckKnowledge. Декодировал входные параметры';
            if (!property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'type_check_knowledge') ||
                !property_exists($post_dec, 'check_knowledge_id') ||
                !property_exists($post_dec, 'date') ||
                !property_exists($post_dec, 'reason_check_knowledge_id') ||
                !property_exists($post_dec, 'workers') ||
                !property_exists($post_dec, 'protocol'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('SaveCheckKnowledge. Переданы некорректные входные параметры');
            }
            $warnings[] = 'SaveCheckKnowledge. Данные с фронта получены';
            $check_knowledge_id = $post_dec->check_knowledge_id;
            $company_department_id = $post_dec->company_department_id;
            $type_check_knowledge_id = $post_dec->type_check_knowledge;
            $reason_check_knowledge_id = $post_dec->reason_check_knowledge_id;
            $date = date('Y-m-d', strtotime($post_dec->date));
            $protocol = $post_dec->protocol;
            $workers = $post_dec->workers;

            $find_check_knowledge = CheckKnowledge::findOne(['id'=>$check_knowledge_id]);
            if ($find_check_knowledge == null){
                $new_check_knowledge = new CheckKnowledge();
            }else{
                $new_check_knowledge = $find_check_knowledge;
                /*
                 * Удаление старых работников
                 */
                $del_check_workers = CheckKnowledgeWorker::deleteAll(['check_knowledge_id'=>$find_check_knowledge->id]);
                if ($del_check_workers != 0){
                    $warnings[] = 'SaveCheckKnowledge. Работники удалены';
                }else{
                    $warnings[] = 'SaveCheckKnowledge. Работников либо небыло, либо возникла ошибка во время удаления работников';
                }
                /*
                 * Удаление старого протокола
                 */
                $del_check_protocol  = CheckProtocol::deleteAll(['check_knowledge_id'=>$find_check_knowledge->id]);
                if ($del_check_protocol != 0){
                    $warnings[] = 'SaveCheckKnowledge. Протокол удалён';
                }else{
                    $warnings[] = 'SaveCheckKnowledge. Протокола либо небыло, либо возникла ошибка во время удаления протокола';
                }
            }
            $new_check_knowledge->type_check_knowledge_id = $type_check_knowledge_id;
            $new_check_knowledge->company_department_id = $company_department_id;
            $new_check_knowledge->reason_check_knowledge_id = $reason_check_knowledge_id;
            $new_check_knowledge->date = $date;
            if ($new_check_knowledge->save()) {
                $new_check_knowledge->refresh();
                $check_knowledge_id = $new_check_knowledge->id;
                $post_dec->check_knowledge_id = $check_knowledge_id;
                $warnings[] = 'SaveCheckKnowledge. Новая проверка знаний успешно сохранена';
            } else {
                $errors[] = $new_check_knowledge->errors;
                throw new Exception('SaveCheckKnowledge. Ошибка при сохранении проверки знаний');
            }

            if (!empty($protocol->attachment_blob)) {
                $found_attachment = Attachment::findOne(['id' => $protocol->attachment_id]);
                if ($found_attachment == null) {
                    $normal_path = Assistant::UploadFile($protocol->attachment_blob, $protocol->title, 'attachment', $protocol->attachment_type);
                    $add_attachment = new Attachment();
                    $add_attachment->path = $normal_path;
                    $add_attachment->date = BackendAssistant::GetDateFormatYMD();
                    $add_attachment->worker_id = $session['worker_id'];
                    $add_attachment->section_title = 'ОТ и ПБ/Проверка знаний/Аттестация';
                    $add_attachment->title = $protocol->title;
                    $add_attachment->attachment_type = $protocol->attachment_type;
                    if ($add_attachment->save()) {
                        $add_attachment->refresh();
                        $attachment_id = $add_attachment->id;
                        $post_dec->protocol->attachment_id = $attachment_id;
                        $post_dec->protocol->attachment_path = $normal_path;
                        $warnings[] = 'SaveCheckKnowledge. Вложение успешно сохраненно';
                    } else {
                        $errors[] = $add_attachment->errors;
                        throw new Exception('SaveCheckKnowledge. Ошибка при сохранении вложения');
                    }
                }else{
                    $attachment_id = $protocol->attachment_id;
                }

                if (isset($attachment_id)) {
                    $add_check_protocol = new CheckProtocol();
                    $add_check_protocol->check_knowledge_id = $check_knowledge_id;
                    $add_check_protocol->attachment_id = $attachment_id;
                    if ($add_check_protocol->save()) {
                        $add_check_protocol->refresh();
                        $warnings[] = 'SaveCheckKnowledge. Связка вложения и протокола успешно сохранена';
                        $post_dec->protocol->check_protocol_id = $add_check_protocol->id;
                    } else {
                        $errors[] = $add_check_protocol->errors;
                        throw new Exception('SaveCheckKnowledge. Ошибка при сохранении связки вложения и проткола');
                    }
                }
            }

            if (!empty($workers)) {
                foreach ($workers as $worker) {
                    if ($type_check_knowledge_id == 3) {
                        $worker->number_certificate = null;
                    }
                    $inserted_check_knowledge_workers[] = [$check_knowledge_id, $worker->worker_id, self::PASSED, $worker->number_certificate];
                    $check_knowledge_workers[] = $worker->worker_id;
                }
                if (!empty($inserted_check_knowledge_workers)) {

                    $result_inserted_check_knowledge_workers = Yii::$app->db
                        ->createCommand()
                        ->batchInsert('check_knowledge_worker', [
                            'check_knowledge_id',
                            'worker_id',
                            'status_id',
                            'number_certificate'
                        ], $inserted_check_knowledge_workers)
                        ->execute();
                    if ($result_inserted_check_knowledge_workers != 0) {
                        $warnings[] = 'SaveCheckKnowledge. Связка проверки знаний/аттестации с работником прошла успешно';
                    } else {
                        throw new Exception('SaveCheckKnowledge. Ошибка при добавлении связки проверки знаний/аттестации с работником');
                    }
                }
                $transaction->commit();
                $found_check_knowledge_workers = CheckKnowledgeWorker::find()
                    ->select(['id'])
                    ->where(['check_knowledge_id'=>$check_knowledge_id])
                    ->andWhere(['in','worker_id',$check_knowledge_workers])
                    ->asArray()
                    ->all();
                if (isset($found_check_knowledge_workers)){
                    foreach ($found_check_knowledge_workers as $found_check_knowledge_worker) {
                        $inserted_check_knowledge_workers_status[] = [$found_check_knowledge_worker['id'], self::PASSED, $date_time_now];
                    }
                }
                if (!empty($inserted_check_knowledge_workers_status)){
                    $result_inserted_check_knowledge_workers_status = Yii::$app->db
                        ->createCommand()
                        ->batchInsert('check_knowledge_worker_status',[
                            'check_knowledge_worker_id',
                            'status_id',
                            'date_time'
                        ],$inserted_check_knowledge_workers_status)
                        ->execute();
                    if ($result_inserted_check_knowledge_workers_status != 0) {
                        $warnings[] = 'SaveCheckKnowledge. Добавление статуса в историю статусов проверки знаний у работника успешно добавлена';
                    } else {
                        throw new Exception('SaveCheckKnowledge. Ошибка при добавлении статуса в историю статусов проверки знаний у работника');
                    }
                }
            }
        } catch (Throwable $exception) {
            $transaction->rollBack();
            $errors[] = 'SaveCheckKnowledge. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
//        $json_check_knowledge = '{"company_department_id":'.$company_department_id.',"type_check_knowledge":'.$type_check_knowledge_id.'}';
//        $result_check_knowledge = self::GetCheckKnowledge($json_check_knowledge);
//        if ($result_check_knowledge['status'] == 1){
//            $check_knowledge = $result_check_knowledge['Items'];
//            $warnings[] = $result_check_knowledge['warnings'];
//        }else{
//            $errors[] = $result_check_knowledge['errors'];
//            $warnings[] = $result_check_knowledge['warnings'];
//        }
        $warnings[] = 'SaveCheckKnowledge. Конец метода';
        if (isset($post_dec)) {
            $result = $post_dec;
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteCheckKnowledge() - Удаление проверки знаний/аттестации
     * @param null $data_post - Json с данными: идентификатор проверки знанний/аттестации которую небходимо удалить
     * @return array - количество затронутых записей при удалении, если до запроса не дошло то вернёт -1
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\CheckKnowledge&method=DeleteCheckKnowledge&subscribe=&data={"check_knowledge_id":24}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 21.10.2019 13:50
     */
    public static function DeleteCheckKnowledge($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = null;
        $warnings[] = 'DeleteCheckKnowledge. Начало метода';
        try {
            $transaction = Yii::$app->db->beginTransaction();
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('DeleteCheckKnowledge. Не переданы входные параметры');
            }
            $warnings[] = 'DeleteCheckKnowledge. Данные успешно переданы';
            $warnings[] = 'DeleteCheckKnowledge. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'DeleteCheckKnowledge. Декодировал входные параметры';
            if (!property_exists($post_dec, 'check_knowledge_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('DeleteCheckKnowledge. Переданы некорректные входные параметры');
            }
            $warnings[] = 'DeleteCheckKnowledge. Данные с фронта получены';
            $check_knowledge_id = $post_dec->check_knowledge_id;
            $delete_check_knowledge = CheckKnowledge::deleteAll(['id'=>$check_knowledge_id]);
            $transaction->commit();
        } catch (Throwable $exception) {
            $transaction->rollBack();
            $errors[] = 'DeleteCheckKnowledge. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'DeleteCheckKnowledge. Конец метода';
        if (isset($delete_check_knowledge)) {
            $result = $delete_check_knowledge;
        } else {
            $result = -1;
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteCheckKnowledgeWorker() - Удаление работника с проверки знаний/аттестации
     * @param null $data_post - JSON с данными: идентификатор  связки работника и проверки знаний/аттестации
     * @return array - количество затронутых записей при удалении, если до запроса не дошло вернёт -1
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example http://amicum/read-manager-amicum?controller=industrial_safety\CheckKnowledge&method=DeleteCheckKnowledgeWorker&subscribe=&data={"check_knowledge_worker_id":35}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 21.10.2019 13:55
     */
    public static function DeleteCheckKnowledgeWorker($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();																				// Промежуточный результирующий массив
        $warnings[] = 'DeleteCheckKnowledgeWorker. Начало метода';
        try {
            $transaction = Yii::$app->db->beginTransaction();
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('DeleteCheckKnowledgeWorker. Не переданы входные параметры');
            }
            $warnings[] = 'DeleteCheckKnowledgeWorker. Данные успешно переданы';
            $warnings[] = 'DeleteCheckKnowledgeWorker. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'DeleteCheckKnowledgeWorker. Декодировал входные параметры';
            if (!property_exists($post_dec, 'check_knowledge_worker_id') ||
                !property_exists($post_dec,'check_knowledge_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('DeleteCheckKnowledgeWorker. Переданы некорректные входные параметры');
            }
            $warnings[] = 'DeleteCheckKnowledgeWorker. Данные с фронта получены';
            $check_knowledge_worker_id = $post_dec->check_knowledge_worker_id;
            $check_knowledge_id = $post_dec->check_knowledge_id;
            $delete_check_knowledge_worker = CheckKnowledgeWorker::deleteAll(['id'=>$check_knowledge_worker_id]);

            $found_check_knowldege = CheckKnowledge::find()
                ->joinWith('checkKnowledgeWorkers')
                ->where(['check_knowledge.id'=>$check_knowledge_id])
                ->asArray()
                ->all();
            if (isset($found_check_knowldege)){
                if (empty($found_check_knowldege[0]['checkKnowledgeWorkers'])){
                    $delete_check_knowledge = CheckKnowledge::deleteAll(['id'=>$check_knowledge_id]);
                }
            }
            $transaction->commit();
        } catch (Throwable $exception) {
            $transaction->rollBack();
            $errors[] = 'DeleteCheckKnowledgeWorker. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'DeleteCheckKnowledgeWorker. Конец метода';
        if (isset($delete_check_knowledge_worker)) {
            $result['count_deleted_check_knowledge_workers'] = $delete_check_knowledge_worker;
            if (isset($delete_check_knowledge)){
                $result['count_deleted_check_knowledges'] = $delete_check_knowledge;
            }
        } else {
            $result = -1;
        }
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод SaveReasonCheckKnowledge() - Сохранение причины проверки знаний
     * @param null $data_post - JSON с данными: наименованием сохраняемой проверки знаний
     * @return array - идентификатор сохранённой причины проверки знаний и наименование
     *
     * @package frontend\controllers\industrial_safety
     *
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 21.10.2019 14:05
     */
    public static function SaveReasonCheckKnowledge($data_post = NULL)
    {
        $status = 1; // Флаг успешного выполнения метода
        $warnings = array(); // Массив предупреждений
        $errors = array(); // Массив ошибок
        $method_name = 'SaveReasonCheckKnowledge';
        $result = array(); // Промежуточный результирующий массив
        $warnings[] = $method_name.'. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name.'. Не переданы входные параметры');
            }
            $warnings[] = $method_name.'. Данные успешно переданы';
            $warnings[] = $method_name.'. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post); // Декодируем входной массив данных
            $warnings[] = $method_name.'. Декодировал входные параметры';
            if (!property_exists($post_dec, 'title')) // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $title = $post_dec->title;
            $reason_check_knowledge = new ReasonCheckKnowledge();
            $reason_check_knowledge->title = $title;
            if ($reason_check_knowledge->save()){
                $warnings[] = $method_name.' Причина проверки знаний успешно сохранена';
                $post_dec->id = $reason_check_knowledge->id;
            }else{
                $errors[] = $reason_check_knowledge->errors;
                throw new \Exception($method_name.'. Ошибка при сохранении причины проверки знаний');
            }

        } catch (\Throwable $exception) {
            $errors[] = $method_name.'. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name.'. Конец метода';
        $result = $post_dec;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }
}
