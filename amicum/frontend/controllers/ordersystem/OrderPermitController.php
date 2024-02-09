<?php

namespace frontend\controllers\ordersystem;


use backend\controllers\Assistant as BackendAssistant;
use frontend\controllers\Assistant;
use frontend\controllers\handbooks\DepartmentController;
use frontend\models\Attachment;
use frontend\models\OrderPermit;
use frontend\models\OrderPermitAttachment;
use frontend\models\OrderPermitOperation;
use frontend\models\OrderPermitStatus;
use frontend\models\OrderPermitWorker;

class OrderPermitController extends \yii\web\Controller
{
    // внешние методы
    //

    // внутренние методы:
    //      getOrderPermit                 - Получение списка наряд-допусков
    //      delOrderPermit                 - удалить наряд допуск
    //      saveOrderPermit                - сохранить наряд допуск


    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод getOrderPermit         - Получение списка наряд допусков
     * @param null $data_post
     * $shift_id                              - ключ смены
     * $company_department_id                 - департамент по которому получаем список наряд допусков
     *
     * @return array
     *      ordersList:
     *          {order_permit_id}                                                                                            // Объект для внесения данных о наряд-допуске(Модака)
     *              order_permit_id: null,                                                                                          // Ключ наряда-допуска
     *              shift_id: null,                                                                                                 // ключ смены
     *              company_department_id: null,                                                                                    // Ключ участка(департмента)
     *              company_department_title: '',                                                                                   // наименование департамента
     *              number_order: "",                                                                                               // Номер наряда-допуска
     *              date_time_create: new Date(),                                                                                   // дата и время создания наряд допуска
     *              date_time_issue: '',                                                                                            // Дата и время выдачи наряд-допуска
     *              date_time_done: '',                                                                                             // дата и время сдачи наряд-допуска
     *              place_id: null,                                                                                                 // Кюч места
     *              place_title: '',                                                                                                // Название места
     *              responsible: {
     *                  {worker_id}
     *                              worker_id: null,                                                                                           // Ключ к сотруднику кем выдан
     *                              worker_full_name: '',                                                                                       // ФИО сотрудника кем выдан
     *                              worker_tabel_number: null,                                                                                 // Таб. номер сотрудника кем выдан
     *                              worker_position_id: null,                                                                                  // Ключ должности сотруднкиа кем выдан
     *                              worker_position_title: '',                                                                                 // Название должности сотрудника кем выдан
     *              },                                                                                                // Выдавшие наряд-допуск
     *              workers: {
     *                  {worker_id}
     *                              worker_id: 8000,                                                                                                // Ключ к сотруднику кому выдан
     *                              worker_full_name: 'Тролимов Н.А',                                                                                // ФИО сотрудники кому выдан
     *                              worker_tabel_number: 1222,                                                                                      // Таб. номер сотрудника кому выдан
     *                              worker_position_id: 1,                                                                                          // Ключ должности сотрудника кому выдан
     *                              worker_position_title: 'Горнорабочий участка',                                                                  // Название должности сотрудника кому выдан
     *              },                                                                                                    // Сотрудники, кому выдан наряд-допуск
     *              order_status_done: 1,                                                                                           // Статус сдачи наряда (сдан/не сдан)
     *              status_id: 50,                                                                                                  // Статус сдачи наряда (выдан, скорректирован, новый и т.д)
     *              description: '',                                                                                                // Примечание
     *              attachments: {
     *                  {order_permit_attachment_id}
     *                      order_permit_attachment_id: -1,                                                                                 // ключ привязки вложения и наряд допуска
     *                      attachment_id: -1,                                                                                              // ключ вложения
     *                      attachment_path: "",                                                                                            // путь до вложения на сервере
     *                      attachment_blob: {},                                                                                            // само вложение
     *                      title: "",                                                                                                      // название вложения
     *                      attachment_type: "",                                                                                            // тип вложения джепег
     *                      sketch: {},                                                                                                     // эскиз - маленький эксземпляр вложения
     *                      attachment_status: "",                                                                                          // статус вложения, при передачи внутри текста "del" вложение будет отвязано от документа, в иных случая или добавится или обновится
     *              },                                                                                                // Документы вложения по наряд допуску
     *              operations: {
     *                  {order_permit_operation_id}
     *                      order_permit_operation_id: null,                                                                                // ключ привязки операции к наряд допуску
     *                      operation_id: null,                                                                                             // Ключ операции
     *                      operation_title: '',                                                                                            // Название операции
     *                      equipment_id: null,                                                                                             // Ключ оборудования
     *                      equipment_title: ''                                                                                             // Ключ оборудования
     *              }                                                                                                  // Работы по наряд-допуску
     *              statuses:                                                                                          // история изменения наряд допуска
     *                  {order_permit_status_id}
     *                      status_id                                                                                                       // ключ статуса
     *                      worker_id                                                                                                       // ключ работника изменившего статус
     *                      date_time_create                                                                                                // дата и время изменения статуса
     *                      description                                                                                                     // описание причины изменения статуса
     *                      order_permit_status_id                                                                                          // ключ истории изменения статуса
     * },
     * @package frontend\controllers\prostoi
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\OrderPermit&method=getOrderPermit&subscribe=&data={"company_department_id":4029293,"shift_id":"1"}
     * http://127.0.0.1/read-manager-amicum?controller=ordersystem\OrderPermit&method=getOrderPermit&subscribe=&data={"company_department_id":4029293,"shift_id":NULL}
     *
     * @author Якимов М.Н,
     * Created date: on 30.12.2019 9:19
     */
    public static function getOrderPermit($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'getOrderPermit';
        $result = array();                                                                                        // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'company_department_id') ||
                !property_exists($post_dec, 'shift_id'))                                                            // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $company_department_id = $post_dec->company_department_id;
            $shift_id = $post_dec->shift_id;

            // ищем вложенные подразделения
            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new \Exception($method_name . '. Ошибка получения вложенных департаментов' . $company_department_id);
            }

            // получаем список материалов
            $orders = OrderPermit::find()
//                ->select('
//                    order_permit.company_department_id as order_permit_company_department_id
//                ')
                ->joinWith('orderPermitOperations.operation')
                ->joinWith('orderPermitOperations.equipment')
                ->joinWith('orderPermitAttachments.attachment')
                ->joinWith('place')
                ->joinWith('companyDepartment.company')
                ->joinWith('orderPermitStatuses')
                ->joinWith('orderPermitWorkers.worker.companyDepartment1.company1')
                ->where(['in', 'order_permit.company_department_id', $company_departments])
                ->andFilterWhere(['shift_id' => $shift_id])
                ->asArray()
                ->all();
            if ($orders) {
                // обрабатываем список наряд допусков
                foreach ($orders as $order) {
                    $order_permit_id = $order['id'];
                    $orders_result[$order_permit_id]['order_permit_id'] = $order_permit_id;
                    $orders_result[$order_permit_id]['shift_id'] = $order['shift_id'];
                    $orders_result[$order_permit_id]['company_department_id'] = $order['company_department_id'];
                    $orders_result[$order_permit_id]['company_department_title'] = $order['companyDepartment']['company']['title'];
                    $orders_result[$order_permit_id]['number_order'] = $order['number_order'];
                    $orders_result[$order_permit_id]['date_time_create'] = $order['date_time_create'];
                    if ($order['date_time_create']) {
                        $orders_result[$order_permit_id]['date_time_create_format'] = date('d.m.Y', strtotime($order['date_time_create']));
                    }
                    $orders_result[$order_permit_id]['date_time_issue'] = $order['date_time_start'];
                    if ($order['date_time_start']) {
                        $orders_result[$order_permit_id]['date_time_issue_format'] = date('d.m.Y H:i:s', strtotime($order['date_time_start']));
                        $orders_result[$order_permit_id]['date_time_issue_format'] = date('d.m.Y  H:i:s', strtotime($order['date_time_start']));
                    }
                    $orders_result[$order_permit_id]['date_time_done'] = $order['date_time_end'];
                    if ($order['date_time_end']) {
                        $orders_result[$order_permit_id]['date_time_done_format'] = date('d.m.Y H:i:s', strtotime($order['date_time_end']));
                        $orders_result[$order_permit_id]['date_time_done_format'] = date('d.m.Y  H:i:s', strtotime($order['date_time_end']));
                    }
                    $orders_result[$order_permit_id]['order_status_done'] = $order['order_status_done'];
                    $orders_result[$order_permit_id]['status_id'] = $order['status_id'];
                    $orders_result[$order_permit_id]['description'] = $order['description'];
                    $orders_result[$order_permit_id]['place_id'] = $order['place_id'];
                    if($order['place']) {
                        $orders_result[$order_permit_id]['place_title'] = $order['place']['title'];
                    } else {
                        $orders_result[$order_permit_id]['place_title'] = "";
                    }
                    $orders_result[$order_permit_id]['responsible']['worker_id'] = $order['worker_id'];
                    $orders_result[$order_permit_id]['responsible']['worker_full_name'] = "";
                    $orders_result[$order_permit_id]['responsible']['worker_tabel_number'] = "";
                    $orders_result[$order_permit_id]['responsible']['worker_position_id'] = "";
                    $orders_result[$order_permit_id]['responsible']['worker_position_title'] = "";
//                foreach ($order['orderPermitWorkers'] as $worker) {
//                    $orders_result[$order_permit_id]['workers'][$worker['worker_id']]['worker_id'] = $worker['worker_id'];
//                }
                    foreach ($order['orderPermitWorkers'] as $worker) {
                        $orders_result[$order_permit_id]['workers'][$worker['worker_id']]['worker_id'] = $worker['worker_id'];
                        $orders_result[$order_permit_id]['workers'][$worker['worker_id']]['company_department_id'] = $worker['worker']['companyDepartment1']['company1']['id'];
                        $orders_result[$order_permit_id]['workers'][$worker['worker_id']]['company_title'] = $worker['worker']['companyDepartment1']['company1']['title'];
                        $orders_result[$order_permit_id]['workers'][$worker['worker_id']]['worker_full_name'] = "";
                        $orders_result[$order_permit_id]['workers'][$worker['worker_id']]['worker_tabel_number'] = "";
                        $orders_result[$order_permit_id]['workers'][$worker['worker_id']]['worker_position_id'] = "";
                        $orders_result[$order_permit_id]['workers'][$worker['worker_id']]['worker_position_title'] = "";
                    }
                    foreach ($order['orderPermitStatuses'] as $status_item) {
                        $orders_result[$order_permit_id]['statuses'][$status_item['id']]['order_permit_status_id'] = $status_item['id'];
                        $orders_result[$order_permit_id]['statuses'][$status_item['id']]['status_id'] = $status_item['status_id'];
                        $orders_result[$order_permit_id]['statuses'][$status_item['id']]['worker_id'] = $status_item['worker_id'];
                        $orders_result[$order_permit_id]['statuses'][$status_item['id']]['date_time_create'] = $status_item['date_time_create'];
                        $orders_result[$order_permit_id]['statuses'][$status_item['id']]['description'] = $status_item['description'];

                    }
                    foreach ($order['orderPermitAttachments'] as $order_permit_attachment) {
                        $orders_result[$order_permit_id]['attachments'][$order_permit_attachment['id']]['order_permit_attachment_id'] = $order_permit_attachment['id'];
                        $orders_result[$order_permit_id]['attachments'][$order_permit_attachment['id']]['attachment_id'] = $order_permit_attachment['attachment']['id'];
                        $orders_result[$order_permit_id]['attachments'][$order_permit_attachment['id']]['attachment_path'] = $order_permit_attachment['attachment']['path'];
                        $orders_result[$order_permit_id]['attachments'][$order_permit_attachment['id']]['attachment_blob'] = (object)array();
                        $orders_result[$order_permit_id]['attachments'][$order_permit_attachment['id']]['title'] = $order_permit_attachment['attachment']['title'];
                        $orders_result[$order_permit_id]['attachments'][$order_permit_attachment['id']]['attachment_type'] = $order_permit_attachment['attachment']['attachment_type'];
                        $orders_result[$order_permit_id]['attachments'][$order_permit_attachment['id']]['sketch'] = $order_permit_attachment['attachment']['sketch'];
                        $orders_result[$order_permit_id]['attachments'][$order_permit_attachment['id']]['attachment_status'] = "";
                    }
                    foreach ($order['orderPermitOperations'] as $order_permit_operation) {
                        $orders_result[$order_permit_id]['operations'][$order_permit_operation['id']]['order_permit_operation_id'] = $order_permit_operation['id'];
                        $orders_result[$order_permit_id]['operations'][$order_permit_operation['id']]['operation_id'] = $order_permit_operation['operation_id'];
                        $orders_result[$order_permit_id]['operations'][$order_permit_operation['id']]['equipment_id'] = $order_permit_operation['equipment_id'];
                        $orders_result[$order_permit_id]['operations'][$order_permit_operation['id']]['operation_title'] = $order_permit_operation['operation']['title'];
                        $orders_result[$order_permit_id]['operations'][$order_permit_operation['id']]['equipment_title'] = $order_permit_operation['equipment']['title'];
                    }
                }

                foreach ($orders_result as $order) {
                    if (!isset($order['operations'])) {
                        $orders_result[$order['order_permit_id']]['operations'] = (object)array();
                    }
                    if (!isset($order['attachments'])) {
                        $orders_result[$order['order_permit_id']]['attachments'] = (object)array();
                    }
                    if (!isset($order['workers'])) {
                        $orders_result[$order['order_permit_id']]['workers'] = (object)array();
                    }
                    if (!isset($order['responsible'])) {
                        $orders_result[$order['order_permit_id']]['responsible'] = (object)array();
                    }
                    if (!isset($order['statuses'])) {
                        $orders_result[$order['order_permit_id']]['statuses'] = (object)array();
                    }
                }
            }
            if (!isset($orders_result)) {
                $result = (object)array();
            } else {
                $result = $orders_result;
            }


        } catch (\Throwable $exception) {
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
     * Метод delOrderPermit() - удалить наряд допуск
     * @param null $data_post
     * order_permit_id                              - ключ наряд допуска
     * @return array
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\OrderPermit&method=delOrderPermit&subscribe=&data={"order_permit_id":1}
     *
     * @author Якимов М.Н.
     * Created date: on 30.12.2019 9:19
     */
    public static function delOrderPermit($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'delOrderPermit';
        $result = array();                                                                                        // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'order_permit_id')
            )                                                            // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $order_permit_id = $post_dec->order_permit_id;

            // Удаляем простой
            $result = OrderPermit::deleteAll(['id' => $order_permit_id]);

        } catch (\Throwable $exception) {
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
     * Метод saveOrderPermit() - сохранить наряд допуск
     * @param null $data_post
     * order_permit
     * order_permit_id: null,                                                                                          // Ключ наряда-допуска
     * shift_id: null,                                                                                                 // ключ смены
     * company_department_id: null,                                                                                    // Ключ участка(департмента)
     * company_department_title: '',                                                                                   // наименование департамента
     * number_order: "",                                                                                               // Номер наряда-допуска
     * date_time_create: new Date(),                                                                                   // дата и время создания наряд допуска
     * date_time_issue: '',                                                                                            // Дата и время выдачи наряд-допуска
     * date_time_done: '',                                                                                             // дата и время сдачи наряд-допуска
     * place_id: null,                                                                                                 // Кюч места
     * place_title: '',                                                                                                // Название места
     * responsible: {},                                                                                                // Выдавшие наряд-допуск
     * workers: {},                                                                                                    // Сотрудники, кому выдан наряд-допуск
     *      status_id: null                                                                                                 // статус наряд допуска (выдан, получен, сдан, корректирован)
     * order_status_done: 1,                                                                                           // Статус сдачи наряда (сдан/не сдан)
     * description: '',                                                                                                // Примечание
     * attachments: {},                                                                                                // Документы вложения по наряд допуску
     * operations: {},                                                                                                 // Работы по наряд-допуску
     * statuses: {}                                                                                                    // история изменения наряд допуска
     * @return array
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\OrderPermit&method=saveOrderPermit&subscribe=&data={"storage":{}}
     *
     * @author Якимов М.Н.
     * Created date: on 30.12.2019 9:19
     */
    public static function saveOrderPermit($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'saveOrderPermit';
        $storage = array();                                                                                             // Промежуточный результирующий массив
        $session = \Yii::$app->session;
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'order_permit')
            )                                                            // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $order_permit = $post_dec->order_permit;
            $order_permit_id = $order_permit->order_permit_id;

            // сохраняем простой
            $save_order_permit = OrderPermit::findOne(['id' => $order_permit_id]);

            if (!$save_order_permit) {
                $save_order_permit = new OrderPermit();
                $save_order_permit->date_time_create = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));
                $warnings[] = $method_name . ". Запись новая";
            }

            $save_order_permit->title = $order_permit->company_department_id;
            $save_order_permit->object_id = 24;
            $save_order_permit->title = $order_permit->title;
            $save_order_permit->company_department_id = $order_permit->company_department_id;
            $save_order_permit->date_time_start = date('Y-m-d H:i:s', strtotime($order_permit->date_time_issue));
            $save_order_permit->date_time_end = date('Y-m-d H:i:s', strtotime($order_permit->date_time_done));
            $save_order_permit->shift_id = $order_permit->shift_id;
            $save_order_permit->status_id = $order_permit->status_id;
            $save_order_permit->place_id = $order_permit->place_id;
            $save_order_permit->order_status_done = $order_permit->order_status_done;
            $save_order_permit->description = $order_permit->description;
            $save_order_permit->number_order = $order_permit->number_order;
            $save_order_permit->worker_id = $order_permit->responsible->worker_id;

            if ($save_order_permit->save()) {
                $save_order_permit->refresh();
                $order_permit_id = $save_order_permit->id;
                $order_permit->order_permit_id = $order_permit_id;
                $warnings[]=$method_name . '. новый ключ order_permit_id ' . $order_permit_id;
            } else {
                $errors[] = $save_order_permit->errors;
                throw new \Exception($method_name . '. Ошибка сохранения модели наряд допуска OrderPermit');
            }


            $del = OrderPermitOperation::deleteAll(['order_permit_id' => $order_permit_id]);
            foreach ($order_permit->operations as $operation) {
                $save_order_permit_operation = new OrderPermitOperation();
                $save_order_permit_operation->order_permit_id = $order_permit_id;
                $save_order_permit_operation->operation_id = $operation->operation_id;
//                $save_order_permit_operation->operation_value_plan = $operation->operation_value_plan;
//                $save_order_permit_operation->operation_value_fact = $operation->operation_value_fact;
                $save_order_permit_operation->equipment_id = $operation->equipment_id;
                if ($save_order_permit_operation->save()) {
                    $save_order_permit_operation->refresh();
                    $operation->order_permit_operation_id = $save_order_permit_operation->id;
                } else {
                    $errors[] = $save_order_permit_operation->errors;
                    throw new \Exception($method_name . '. Ошибка сохранения модели операции наряд допуска OrderPermitOperation');
                }
            }


            $del = OrderPermitStatus::deleteAll(['order_permit_id' => $order_permit_id]);
            foreach ($order_permit->statuses as $status_permit) {
                $save_order_permit_status = new OrderPermitStatus();
                $save_order_permit_status->order_permit_id = $order_permit_id;
                $save_order_permit_status->status_id = $status_permit->status_id;
                $save_order_permit_status->worker_id = $status_permit->worker_id;
                $save_order_permit_status->date_time_create = date('Y-m-d', strtotime($status_permit->date_time_create));
                $save_order_permit_status->description = $status_permit->description;
                if ($save_order_permit_status->save()) {
                    $save_order_permit_status->refresh();
                    $status_permit->order_permit_status_id = $save_order_permit_status->id;
                } else {
                    $errors[] = $save_order_permit_status->errors;
                    throw new \Exception($method_name . '. Ошибка сохранения модели операции наряд допуска OrderPermitStatus');
                }
            }

            $del = OrderPermitWorker::deleteAll(['order_permit_id' => $order_permit_id]);
            foreach ($order_permit->workers as $key => $worker) {
                $save_order_permit_worker = new OrderPermitWorker();
                $save_order_permit_worker->order_permit_id = $order_permit_id;
                $save_order_permit_worker->worker_id = $worker->worker_id;
                if ($save_order_permit_worker->save()) {
                    $save_order_permit_worker->refresh();
                } else {
                    $errors[] = $save_order_permit_worker->errors;
                    throw new \Exception($method_name . '. Ошибка сохранения модели операции наряд допуска OrderPermitStatus');
                }
            }

            foreach ($order_permit->attachments as $key_attach=>$docum_attachment) {
                // проверяем статус вложения на удаление или на добавление
                if (isset($docum_attachment->attachment_status) && $docum_attachment->attachment_status == "del") {
                    //$delete_order = Yii::$app->db->createCommand()->delete('order_permit_attachment', 'id=' . $docum_attachment->order_permit_attachment_id)->execute();
                    $delete_order = OrderPermitAttachment::deleteAll(['id' => $docum_attachment->order_permit_attachment_id]);
                    unset($order_permit->{"attachments"}->{$key_attach});
                    $warnings[] = $method_name . ". Удалил связку вложения $docum_attachment->order_permit_attachment_id. Количество " . $delete_order;
                } else {
                    /**
                     * сохраняем вложение документа в таблицу Attachment
                     **/
                    $docum_attachment_id = $docum_attachment->attachment_id;
                    $new_docum_attachment = Attachment::findOne(['id' => $docum_attachment_id]);
                    if (!$new_docum_attachment) {
                        $new_docum_attachment = new Attachment();
                        $path = Assistant::UploadFile($docum_attachment->attachment_blob, $docum_attachment->title, 'attachment', $docum_attachment->attachment_type);
                        $new_docum_attachment->path = $path;
                        $new_docum_attachment->date = BackendAssistant::GetDateFormatYMD();
                        $new_docum_attachment->worker_id = $session['worker_id'];
                        $new_docum_attachment->section_title = 'Наряд-допуск';
                        $new_docum_attachment->title = $docum_attachment->title;
                        $new_docum_attachment->attachment_type = $docum_attachment->attachment_type;
                        $new_docum_attachment->sketch = $docum_attachment->sketch;
                        if ($new_docum_attachment->save()) {
                            $new_docum_attachment->refresh();
                            $new_docum_attachment_id = $new_docum_attachment->id;
                            $docum_attachment->attachment_id = $new_docum_attachment_id;
                            $docum_attachment->attachment_path = $path;
                            $docum_attachment->attachment_blob = null;
                            $warnings[] = $method_name . '. Данные успешно сохранены ВЛОЖЕНИЯ ДОКУМЕНТА в модель Attachment';
                        } else {
                            $errors[] = $new_docum_attachment->errors;
                            throw new \Exception($method_name . '. Ошибка сохранения ВЛОЖЕНИЯ ДОКУМЕНТА модели Attachment');
                        }
                    } else {
                        $warnings[] = $method_name . ". вложение ДОКУМЕНТА уже было ";
                        $new_docum_attachment_id = $docum_attachment_id;
                    }

                    /**
                     * сохраняем привязку вложения и расследования
                     **/
                    $order_permit_attachment_id = $docum_attachment->order_permit_attachment_id;
                    $new_document_order_permit_attachment = OrderPermitAttachment::findOne(['id' => $order_permit_attachment_id]);
                    if (!$new_document_order_permit_attachment) {
                        $new_document_order_permit_attachment = new OrderPermitAttachment();
                    } else {
                        $warnings[] = $method_name . ". Вложение документа уже было ";
                    }
                    $new_document_order_permit_attachment->order_permit_id = $order_permit_id;
                    $new_document_order_permit_attachment->attachment_id = $new_docum_attachment_id;
                    if ($new_document_order_permit_attachment->save()) {
                        $new_document_order_permit_attachment->refresh();
                        $order_permit_attachment_id = $new_document_order_permit_attachment->id;
                        $docum_attachment->order_permit_attachment_id = $order_permit_attachment_id;
                        $warnings[] = $method_name . '. Данные успешно сохранены в модель OrderPermitAttachment';
                    } else {
                        $errors[] = $new_document_order_permit_attachment->errors;
                        throw new \Exception($method_name . '. Ошибка сохранения модели OrderPermitAttachment');
                    }
                }
            }

        } catch (\Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $storage = $order_permit;
        $warnings[] = $method_name . '. Конец метода';
        $result_main = array('Items' => $storage, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

}
