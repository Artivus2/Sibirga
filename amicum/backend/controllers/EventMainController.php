<?php
/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace backend\controllers;

use backend\controllers\cachemanagers\EquipmentCacheController;
use backend\controllers\cachemanagers\EventCacheController;
use backend\controllers\cachemanagers\LogCacheController;
use backend\controllers\cachemanagers\SensorCacheController;
use backend\controllers\cachemanagers\SituationCacheController;
use backend\controllers\cachemanagers\WorkerCacheController;
use backend\controllers\const_amicum\EventEnumController;
use backend\controllers\const_amicum\StatusEnumController;
use backend\controllers\sms\SmsSender;
use Exception;
use frontend\controllers\system\LogAmicumFront;
use frontend\controllers\WebsocketController;
use frontend\controllers\XmlController;
use frontend\models\EventCompareGas;
use Throwable;
use yii\db\Query;
use yii\web\Controller;

class EventMainController extends Controller
{
    // createEventFor                       - Создание нового события
    // createEventForWorkerGas              - Создание нового события у работников по газам
    // isChangeEvent                        - Проверяет изменилось ли событие - загрубление смена.
    // isChangeEventDay                     - Проверяет изменилось ли событие - загрубление один день.
    // createCompareEvent                   - метод обработки событий сравнения двух газов
    // writeEventCompareGas                 - метод сохранения события по газам стационарным и индивидуальным в БД
    // createNewSituationWithEvent          - метод по созданию новой ситуации с событием
    // GetObjectTitle                       - метод получения названия объекта по типу
    // CronSituation                        - планировщик оповещения персонала о ситуациях

    // СТАТУСЫ ОТПРАВКИ СООБЩЕНИЙ SMS/EMAIL
    //      104	    Подготовил к отправке
    //      105	    Отправил
    //      106	    Доставил
    // ТИПЫ ОТПРАВКИ:
    //      1	    email
    //      2	    local
    //      3	    server
    //      4	    ftp
    //      5	    sms

    /**
     * Создание нового события
     *
     * Пример использования:
     * EventMainController::createEventFor('sensor', $sensor_id, EventEnumController::LOW_BATTERY, $batteryPercent,
     * $pack->timestamp, StatusEnumController::EMERGENCY_VALUE, ParameterEnumController::COMMNODE_BATTERY_PERCENT, $mine_id,
     * StatusEnumController::EVENT_RECEIVED, $edge_id);
     *
     * @param $object_table -   название таблицы объекта (sensor, equipment, worker)
     * @param $main_id -   идентификатор объекта с которым связано событие
     * @param $event_id -   идентификатор события
     * @param $value -   значение параметра события
     * @param $date_time -   дата и время события
     * @param $value_status_id -   идентификатор статуса значения параметра
     * @param $parameter_id -   идентификатор параметра значения
     * @param $mine_id -   идентификатор шахты
     * @param $event_status_id -   идентификатор статуса события
     * @param $edge_id -   идентификатор выработки на которой произошло событие
     * @param $xyz -   координата события
     *
     * @return array
     */
    public static function createEventFor($object_table, $main_id, $event_id, $value,
                                          $date_time, $value_status_id, $parameter_id,
                                          $mine_id, $event_status_id, $edge_id = -1, $xyz = -1)
    {
        $microtime_start = microtime(true);
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        $warnings[] = 'createEventFor. Начало метода';
        $alarm_group_id = NULL;
        try {
            /**
             * Получение дополнительных параметров, нужных для записи в таблицу
             */
            $warnings[] = 'createEventFor. Ключ выработки: ' . $edge_id;
            switch ($object_table) {
                case 'sensor' :
                {
                    $warnings[] = 'createEventFor. Объект из таблицы sensor';
                    $sensor = (new SensorCacheController())->getSensorMineBySensorOneHash($mine_id, $main_id);
                    if ($sensor === false) {
                        throw new Exception("createEventFor. Сенсор $main_id не найден в кэше. Шахта $mine_id. Таблица $object_table ");
                    }

                    $parameter_type_id = SensorCacheController::isStaticSensor($sensor['object_type_id']);

                    // !!! Если значения не будет в кэше, метод дальше не пойдет
                    if ($edge_id == -1) {
                        $edge = (new SensorCacheController())->getParameterValueHash($main_id, 269/*ParameterEnumController::EDGE_ID*/, $parameter_type_id);
                        if (!$edge) {
                            throw new Exception('createEventFor. Значение выработки не найдено в кэше Объект: ' . $main_id . ' object_table: ' . $object_table . ' edge_id: ' . $edge_id);
                        }
                        $edge_id = $edge['value'];
                    }

                    if ($xyz == -1) {
                        $xyz = (new SensorCacheController())->getParameterValueHash($main_id, 83/*ParameterEnumController::COORD*/, $parameter_type_id);
                        if ($xyz === false) {
                            throw new Exception('createEventFor. Значение координат не найдено в кэше');
                        }
                        $xyz = $xyz['value'];
                    }

                    $alarm_group_id = (new SensorCacheController())->getParameterValueHash($main_id, 18/*ParameterEnumController::PREDPRIYATIE*/, 1/*ParameterTypeEnumController::REFERENCE*/);
                    if ($alarm_group_id === false) {
                        $alarm_group_id = null;
                    } else {
                        $alarm_group_id = $alarm_group_id['value'];
                    }


                    $object_id = $sensor['object_id'];
                    $object_title = $sensor['sensor_title'];

                    break;
                }
                case 'worker' :
                {
                    $warnings[] = 'createEventFor. Объект из таблицы worker';
                    $worker = (new WorkerCacheController())->getWorkerMineByWorkerOneHash($mine_id, $main_id);
                    if ($worker === false) {
                        $worker = (new Query())
                            ->select([
                                'worker_object.object_id as object_id',
                                'employee.last_name as last_name',
                                'employee.first_name as first_name',
                                'employee.patronymic as patronymic'
                            ])
                            ->from('worker')
                            ->innerJoin('worker_object', 'worker_object.worker_id = worker.id')
                            ->innerJoin('employee', 'worker.employee_id = employee.id')
                            ->where([
                                'worker.id' => $main_id
                            ])
                            ->limit(1)
                            ->one();
                        if ($worker === false) {
                            throw new Exception("createEventFor. Воркер $main_id не найден в БД");
                        }
                        $worker['full_name'] = $worker['last_name'] . ' ' . $worker['first_name'] . ' ' . $worker['patronymic'];
                    }

                    if ($edge_id == -1) {
                        $edge = (new WorkerCacheController())->getParameterValueHash($main_id, 269/*ParameterEnumController::EDGE_ID*/, 2/*ParameterTypeEnumController::MEASURED*/);
                        if (!$edge) {
                            $errors[] = $edge;
                            throw new Exception('createEventFor. Значение выработки не найдено в кэше по параметру 269 для работника: ' . $main_id);
                        }
                        $edge_id = $edge['value'];
                    }

                    if ($xyz == -1) {
                        $xyz = (new WorkerCacheController())->getParameterValueHash($main_id, 83/*ParameterEnumController::COORD*/, 2/*ParameterTypeEnumController::MEASURED*/);
                        if ($xyz === false) {
                            throw new Exception('createEventFor. Значение координат не найдено в кэше');
                        }
                        $xyz = $xyz['value'];
                    }

                    $alarm_group_id = (new WorkerCacheController())->getParameterValueHash($main_id, 18/*ParameterEnumController::PREDPRIYATIE*/, 1/*ParameterTypeEnumController::REFERENCE*/);
                    if ($alarm_group_id === false) {
                        $alarm_group_id = null;
                    } else {
                        $alarm_group_id = $alarm_group_id['value'];
                    }

                    $object_id = $worker['object_id'];
                    $object_title = $worker['full_name'];

                    break;
                }
                case 'equipment' :
                {
                    $warnings[] = 'createEventFor. Объект из таблицы equipment';
                    $equipment = (new EquipmentCacheController())->getEquipmentMineByEquipmentOne($mine_id, $main_id);
                    if ($equipment === false)
                        throw new Exception("createEventFor. Оборудование $main_id не найдено в кэше");

                    if ($edge_id == -1) {
                        $edge = (new EquipmentCacheController())->getParameterValue($main_id, 269/*ParameterEnumController::EDGE_ID*/, 2/*ParameterTypeEnumController::MEASURED*/);
                        if (!$edge) {
                            $errors[] = $edge;
                            throw new Exception('createEventFor. Значение выработки по параметру 269 не найдено в кэше для объекта (оборудование): ' . $main_id);
                        }
                        $edge_id = $edge['value'];
                    }

                    if ($xyz == -1) {
                        $xyz = (new EquipmentCacheController())->getParameterValue($main_id, 83/*ParameterEnumController::COORD*/, 2/*ParameterTypeEnumController::MEASURED*/);
                        if ($xyz === false) {
                            throw new Exception('createEventFor. Значение координат не найдено в кэше');
                        }
                        $xyz = $xyz['value'];
                    }

                    $alarm_group_id = (new EquipmentCacheController())->getParameterValue($main_id, 18/*ParameterEnumController::PREDPRIYATIE*/, 1/*ParameterTypeEnumController::REFERENCE*/);
                    if ($alarm_group_id === false) {
                        $alarm_group_id = null;
                    } else {
                        $alarm_group_id = $alarm_group_id['value'];
                    }

                    $object_id = $equipment['object_id'];
                    $object_title = $equipment['equipment_title'];

                    break;
                }
                default :
                {
                    throw new Exception('createEventFor. Неизвестное имя таблицы объекта ' . $object_table);
                }
            }

            /**
             * Проверка на то, что событие изменилось
             */
            $response = self::isChangeEvent($mine_id, $event_id, $main_id, $date_time, $edge_id, $value_status_id);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $flag_save = $response['flag_save'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception('createEventFor. Ошибка при проверке изменения события');
            }

            if ($flag_save) {
                /**
                 * Создание записи в таблице event_journal
                 */
                $response = EventBasicController::createEventJournalEntry(
                    $event_id,
                    $main_id,
                    $edge_id,
                    $value,
                    $date_time,
                    $xyz,
                    $value_status_id,
                    $parameter_id,
                    $object_id,
                    $mine_id,
                    $object_title,
                    $object_table,
                    $event_status_id,
                    $alarm_group_id
                );
                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    $event_journal_id = $response['event_journal_id'];
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception('createEventFor. Ошибка сохранения в event_journal');
                }

                /**
                 * Создание записи в таблице event_status - используется для отслеживания действия диспетчера.
                 */
                $response = EventBasicController::createEventStatusEntry(
                    $event_journal_id,
                    $event_status_id,
                    $date_time
                );
                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception('createEventFor. Ошибка сохранения в event_status');
                }

                /**
                 * Сохранение события в кэш
                 */
                $response = (new EventCacheController())->setEvent(
                    $mine_id,
                    $event_id,
                    $main_id,
                    $event_status_id,
                    $edge_id,
                    $value,
                    $value_status_id,
                    $date_time,
                    $xyz,
                    $parameter_id,
                    $object_id,
                    $object_title,
                    $object_table,
                    $event_journal_id,
                    $alarm_group_id
                );
                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception('createEventFor. Ошибка сохранения события в кэш');
                }

                /**
                 * Рассылка
                 */
                if ($value_status_id == 44/*StatusEnumController::EMERGENCY_VALUE*/) {
                    $event_title = (new Query())
                        ->select('title')
                        ->from('event')
                        ->where(['id' => $event_id])
                        ->limit(1)
                        ->scalar();
                    if (!$event_title) {
                        throw new Exception('createEventFor. Не найдена запись из таблицы event c id = ' . $event_id);
                    }

                    $message = $event_title;
                    $message .= ". Объект: $object_title";
                    $message .= ". Значение: $value";
                    $message .= '. Дата: ' . explode('.', $date_time)[0];

                    $place_id = null;
                    $place = (new Query())
                        ->select('place.id as place_id, place.title as place_title')
                        ->from('place')
                        ->innerJoin('edge', 'edge.place_id = place.id')
                        ->where(['edge.id' => $edge_id])
                        ->limit(1)
                        ->one();
                    if ($place) {
                        $message .= ". Место: " . $place['place_title'];
                        $place_id = $place['place_id'];
                    }
                    // отправляем событие в журнал оператора АБ в том случае, если оно по превышению газов
                    if ($object_table == 'sensor' and $value_status_id == 44 and ($event_id == 22409 or $event_id == 7130)) {
                        $event_journal_send = array(
                            'event_journal_id' => $event_journal_id,
                            'event_id' => $event_id,
                            'event_title' => $event_title,
                            'status_checked' => 0,
                            'event_date_time' => $date_time,
                            'event_date_time_format' => date('d.m.Y H:i:s', strtotime($date_time)),
                            'sensor_id' => $main_id,
                            'sensor_title' => $object_title,
                            'edge_id' => $edge_id,
                            'place_id' => $place_id,
                            'status_id' => $event_status_id,
                            'alarm_group_id' => $alarm_group_id,
                            'sensor_value' => $value,
                            'kind_reason_id' => null,
                            'status_date_time' => date('d.m.Y H:i:s', strtotime($date_time)),
                            'event_status_id' => null,
                            'duration' => null,
                            'statuses' => [],
                            'gilties' => [],
                            'operations' => [],
                        );
                        $response = WebsocketController::SendMessageToWebSocket('addNewEventJournal', $event_journal_send);
                        if ($response['status'] == 1) {
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                        } else {
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                            throw new Exception(__FUNCTION__ . '. Ошибка отправки данных на вебсокет (addNewEventJournal)');
                        }
                    }
//                    /**
//                     * Email
//                     */
//                    $addresses = XmlController::getEmailSendingList($event_id, $alarm_group_id);
//                    if ($addresses) {
//                        $response = XmlController::SendSafetyEmail($message, $addresses);
//                        if ($response['status'] == 1) {
//                            $warnings[] = $response['warnings'];
//                        } else {
//                            $warnings[] = $response['warnings'];
//                            $errors[] = $response['errors'];
//                        }
//                    } else {
//                        $warnings[] = 'createEventFor. Нет актуальных email адресов для рассылки';
//                    }
//
//                    /**
//                     * СМС
//                     */
//                    $numbers = XmlController::getSmsSendingList($event_id, $alarm_group_id);
//                    if ($numbers) {
//                        $response = SmsSender::actionSendSmsProxy($message, $numbers);
//                        if ($response['status'] == 1) {
//                            $warnings[] = $response['warnings'];
//                        } else {
//                            $warnings[] = $response['warnings'];
//                            $errors[] = $response['errors'];
//                        }
//                    } else {
//                        $warnings[] = 'createEventFor. Нет актуальных номеров для СМС рассылки';
//                    }
                }
            }

        } catch (Throwable $exception) {
            $errors[] = 'createEventFor. Исключение при генерации события ' . $event_id;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
            $data_to_cache_log = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
            LogCacheController::setEventLogValue('createEventFor', $data_to_cache_log, '2');
        }

        $warnings[] = 'createEventFor. Конец метода';
        $duration_method = round(microtime(true) - $microtime_start, 6);
        $warnings[] = 'createEventFor. Время выполнения метода ' . $duration_method;
        $result_main = array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * createEventForWorkerGas - Создание нового события у работников по газам
     *
     * Пример использования:
     * EventMainController::createEventForWorkerGas('sensor', $sensor_id, EventEnumController::LOW_BATTERY, $batteryPercent,
     * $pack->timestamp, StatusEnumController::EMERGENCY_VALUE, ParameterEnumController::COMMNODE_BATTERY_PERCENT, $mine_id,
     * StatusEnumController::EVENT_RECEIVED, $edge_id);
     * Алгоритм:
     * ---- Событие случилось в первый раз оно нормальное, неснятых ситуаций нет ----
     * иными словами, самый первый старт метода, предыдущих ситуаций/событий нет или они были нормальные, либо снятые, и мы хотим сгенерировать новое НОРМАЛЬНОЕ событие
     *      а. Ищем все ситуации действующие в кеше
     *      б. если ситуаций нет? то пропускаем это событие.
     *      в. если ситуации есть?
     *          г. то ищем это событие
     *          д. Событие есть?
     *              е. Событие было плохим?
     *                  ё. меняем ему статус на хорошее
     *                  ж. ставим дату окончания события.
     *              з. Событие было хорошим? пропускаем
     *              и. Есть плохие события в ситуации?
     *                  й. Нет? ставим времяокончания ситуации по дате события
     *                  к. Да? идем дальше
     * ---- Событие случилось в первый раз оно Аварийное, неснятых ситуаций нет ----
     * иными словами, самей первый старт метода, предыдущих ситуаций/событий нет или они были нормальные, либо снятые, и мы хотим сгенерировать новое АВАРИЙНОЕ событие и создать ситуацию
     *      а. Ищем все ситуации действующие в кеше
     *      б. если ситуаций нет
     *      в. определяем ситуацию по событию (классифицируем)
     *      г. создаем новую ситуацию в БД и в кеше. статус ситуации 34 - неподтвержденная.
     *          создаем статус ситуации как неподтвержденная - т.к. неизвестно, является она штатной или внештатной
     *          main_id ставим пока объекта сгенерировавшего событие, но в идеале должен быть определен объект на котором произощла ситуация - например забой
     *          company_department_id - оставляем пустой, данное поле появится после того, как будет определен объект на котором произошло событие (например, по забою можем найти подразделение, которое з анего отвечает)
     *      д. новое событие в БД
     *      е. новую привязку ситуации и события в БД
     *      ё. новое событие в кеше
     *      ж. создаем привязку события к ситуации в кеше
     *      з. ищем опасную зону для ситуации
     *      и. новый список зон ситуайий в БД и в кеше
     * ---- Событие случилось второй раз (не важно тоже или другое) - есть ситуация в этом месте
     *      а. Ищем все ситуации действующие в кеше
     *      б. есть действующие ситуации в данном edge_id
     *      в. перебрать все ситуации что нашлись в этом edge_id
     *      г. ПОднять флаг создания новой независимой ситуации на основе этого события
     *      д. Сравнить события из ситуации с пришедшим событием
     *          е. События такого нет?
     *              ё. ищем комплект ситуаций с таким событием и событиями из ситуации в БД
     *              ж. комплект есть? создаем новую ситуацию с данными из старой + новое событие,старую закрываем. Обнуляем флаг создания новой независимой ситуации на основе этого события
     *              з. комплекта нет? создаем новую ситуацию с этим событием
     *          и. Событие такое есть?
     *              й. объект новый?
     *                  к. создать новое событие. дополнить ситуацию
     *              л. объект был?
     *                  м. время > 30 прошло от конца ситуации?
     *                      н. да? создать новую ситуацию
     *                      о. нет? обновить время окончания ситуации в ситуации и в событии. Обнуляем флаг создания новой независимой ситуации на основе этого события
     *      п. флаг создания новой независимой ситуации на основе этого события поднят?
     *          р. создать новую ситуацию на основе этого события
     * ---- Событие случилось второй раз (не важно тоже или другое) - есть ситуация в этом месте и событие хорошее
     *      а. Ищем все ситуации действующие в кеше
     *      б. есть действующие ситуации в данном edge_id
     *      в. перебрать все ситуации что нашлись в этом edge_id
     *      д. Сравнить события из ситуации с пришедшим событием
     *          е. События такого нет?
     *              ё. выходим из метода
     *          ж. Событие такое есть?
     *              з. оно было плохим $value_status_id == 44?
     *                  и. делаем хорошим $value_status_id == 45. event_status_id = 52 и обновляем время окончания ситуации
     *              к. оно было хорошим?
     *                  л. выходим из метода
     * 2. если последнее событие было нормальное $value_status_id!=44, и текущее событие нормальное, то событие не пишется и производится выход из метода
     * @param $object_table -   название таблицы объекта (worker)
     * @param $main_id -   идентификатор объекта с которым связано событие (worker_id)
     * @param $event_id -   идентификатор события (тип события, например, превышение кончентрации газа)
     * @param $value -   значение параметра события
     * @param $date_time -   дата и время события
     * @param $value_status_id -   идентификатор статуса значения параметра (нормальное 45 или аварийное 44)
     * @param $parameter_id -   идентификатор параметра значения (99 метан, 98 - СО)
     * @param $mine_id -   идентификатор шахты
     * @param $event_status_id -   идентификатор статуса события (38 - получено диспетчером, 39 - устраняется, 40 - устранено диспетчером, 52 - снято системой )
     * @param $edge_id -   идентификатор выработки на которой произошло событие
     * @param $xyz -   координата события
     * @param $lamp_sensor_id -   для работников передается его лампа (используется для контроля отсечки не верных показаний)
     *
     * @return array
     */
    public static function createEventForWorkerGas($object_table, $main_id, $event_id, $value,
                                                   $date_time, $value_status_id, $parameter_id,
                                                   $mine_id, $event_status_id, $edge_id, $xyz, $lamp_sensor_id = null)
    {
        $log = new LogAmicumFront("createEventForWorkerGas");

        // базовые входные параметры скрипта
        $result = null;                                                                                                 // результирующий массив (если требуется)

        $new_alarm = 0;                                                                                                 // статус оповещения о новой ситуации - отключен по умолчанию и включается, только при новой ситуации
        $group_alarm_id = null;                                                                                         // ключ группы оповещения
        try {
            $log->addLog('Начало выполнения метода');

            /** Метод начало */
            // делаем проверку на отказавший датчик у работника
            // если событие не  Отказ светильника/7163, то делаем проверку на верность показаний этого датчика
            // получаем событие Отказ светильника/7163 () из кеша по этому датчику
            // если такое событие есть и оно произошло менее 10 часов назад, то событие данного датчика меняем на Отказ светильника/7163
            if ($event_id != EventEnumController::CH4_CRUSH_LAMP and $object_table == 'worker') {
                $event_cache = (new EventCacheController());
                $response = $event_cache->getEvent($mine_id, EventEnumController::CH4_CRUSH_LAMP, $lamp_sensor_id);
                $log->addLogAll($response);
                if ($response['status'] == 1) {
                    $event_crush = $response['Items'];

                    if ($event_crush and (strtotime($date_time) - strtotime($event_crush['date_time'])) < 36000) {
                        $event_id = EventEnumController::CH4_CRUSH_LAMP;
                    }
                }
            }

            // получаем все действующие зоны ситуаций из кеша
            $situation_cache_controller = (new SituationCacheController());
            $response = $situation_cache_controller->getZoneList($mine_id, $edge_id, '*');
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка при проверке изменения события');
            }
            $zones = $response['Items'];

            $log->addLog('Получил список зон и их ситуаций');

            if (!$zones and $value_status_id == StatusEnumController::NORMAL_VALUE) {
                // список ситуаций пуст и ситуация нормальная то просто пропускаем событие и идем дальше
                $log->addLog('список пуст и ситуация нормальная. Все норм. идем дальше - 0');

            } elseif (!$zones and $value_status_id == StatusEnumController::EMERGENCY_VALUE) {
                // список ситуаций пуст и ситуация Аварийная - создаем новую ситуацию
                $new_alarm = 1;

                $log->addLog('список пуст и ситуация Аварийная. Создаем новую ситуацию - 1');

                //      в. определяем ситуацию по событию (классифицируем)
                $response = SituationBasicController::getSituationByEventId($event_id);
                $log->addLogAll($response);

                if ($response['status'] != 1) {
                    throw new Exception('Ошибка при получении ситуации по событию');
                }
                $situation = $response['Items'];

                if ($situation) {
                    // создаем новую ситуацию и событие
                    $response = self::createNewSituationWithEvent($event_id, $event_status_id, $value_status_id, $object_table, $parameter_id, $xyz, $value, $edge_id, $mine_id, $main_id, $date_time, $situation['id'], $situation['danger_level_id'], 34);
                    $log->addLogAll($response);

                    if ($response['status'] != 1) {
                        throw new Exception('Ошибка при получении ситуации по событию');
                    }

                    $situation_current_to_ws = $response['situation_current'];

                } else {
                    $log->addLog('Событие не включено ни в одну ситуацию!!!!!! Проверить верность генерации аварийного события');

                    $new_alarm = 0;
                    $response = self::GetObjectTitle($object_table, $mine_id, $main_id);
                    $log->addLogAll($response);
                    if ($response['status'] != 1) {
                        throw new Exception('Не смог получить название объекта ');
                    }
                    $object_id = $response['object_id'];
                    $object_title = $response['object_title'];
                    $group_alarm_id = $response['group_alarm_id'];
                    $situation_current_to_ws['object_title'] = $object_title;
                }
            } elseif ($zones and $value_status_id == StatusEnumController::EMERGENCY_VALUE) {
                // список ситуаций полон - событие плохое
                $log->addLog('список полон - событие плохое - 2. Если события нет в ситуациях, то ищем новую ситуацию из всех событий, иначе создаем новую ситуацию. 
                Если событие есть в ситуации, и ситуация свежая, то обновляем эту ситуацию и событие.
                Если событие есть в ситуации, и ситуация старая, то ищем новую ситуацию на основе группы, и если такой нет, то создаем новую ситуацию 
                ');

                //      г. Поднять флаг создания новой независимой ситуации на основе этого события
                $new_situation_flag_create = 1;                                                                         // поднимаем флаг создания новой ситуации

                //      в. перебрать все ситуации, что нашлись в этом edge_id
                $find_event_journal_id = -1;                                                                            // ключ сохраненного журнала событий (нужно, что бы не было дублирования одного и того же события в разных ситуациях)
                // может быть ситуация когда мы перебираем ситуации и в одной ситуации этого события не было, и тогда мы должны вроде как создать новую ситуацию с этим событием,
                // но  в другой ситуации в этом же месте это событие есть, но ситуация не является устаревшей, потому там оно должно дополнится/обновится, и новой ситуации создаваться не должно
                $new_situation_flag_create_by_old = 0;                                                                  // флаг создания новой ситуации по причине наличия устаревшей ситуации с данным набором
                $new_situation_flag_create_by_one_actual = 0;                                                           // флаг существования хотя бы одной актуальной ситуации
                foreach ($zones as $zone) {
                    $situation_journal_id = $zone['situation_journal_id'];                                              // текущий рассматриваемый ключ журнала ситуация
                    $situation_id = $zone['situation_id'];                                                              // текущий рассматриваемый тип ситуации

                    // получить список событий данной ситуации
                    $response = $situation_cache_controller->getEventSituationList($mine_id, '*', $situation_journal_id);
                    $log->addLogAll($response);
                    if ($response['status'] != 1) {
                        throw new Exception('Ошибка при получении списка событий по конкретной ситуации');
                    }
                    $list_event_situation_temp = $response['Items'];

                    $list_event_situation = array();
                    // проверяем на наличие действующих/актуальных событий в ситуациях и формируем список актуальных ситуаций и их событий
                    foreach ($list_event_situation_temp as $event_situation) {
                        $delta_time_event = strtotime($date_time) - strtotime($event_situation['date_time_end']);       // в секундах
                        if ($delta_time_event < 3600) {                                                                 // (1 час = 60 мин * 60 сек = 3600сек)
                            $list_event_situation[] = $event_situation;
                        }
                    }
                    $event_journal_ids = null;

                    // перебрать все найденные события в ситуации
                    // ищем событие конкретного объекта в списке событий по ситуациям
                    $flag_exist_event = 0;                                                                              // флаг существования события в данной ситуации
                    $flag_exist_main = 0;                                                                               // флаг существования объекта в данном событии
                    $find_event_journal_id = -1;                                                                        // ключ конкретного найденного журнала событий
                    $situations_events[$situation_journal_id]['situation_journal_id'] = $situation_journal_id;          // ключ журнала ситуаций
                    $situations_events[$situation_journal_id]['situation_id'] = $situation_id;                          // ключ ситуации
                    $situations_events[$situation_journal_id]['events'] = array();                                      // список событий внутри ситуации
                    foreach ($list_event_situation as $event_situation) {

                        //                        $event_journal_id = $event_situation['event_journal_id'];
                        //                          $event_situation:
                        //                                main_id
                        //                                situation_journal_id
                        //                                event_journal_id
                        //                                event_id
                        //                                event_status_id
                        //                                value_status_id
                        //                                mine_id
                        //                                date_time_start
                        //                                date_time_end

                        $event_journal_ids[$event_situation['event_journal_id']] = $event_situation['event_journal_id'];// список журналов событий всех ситуаций (нужен для создания новой ситуации более крупной чем существующая)

                        $event_date_time_start = $event_situation['date_time_start'];
                        //      д. Сравнить события из ситуации с пришедшим событием, собрать события ситуации в кучу
                        if ($event_situation['event_id'] == $event_id) {
                            $new_situation_flag_create = 0;                                                             // опускаем флаг создания новой ситуации
                            $flag_exist_event = 1;
                            // проверить объект тот же?
                            if ($event_situation['main_id'] == $main_id) {
                                $flag_exist_main = 1;
                                $find_event_journal_id = $event_situation['event_journal_id'];
                                $find_event_id = $event_situation['event_id'];
                            }
                        }
                        $situations_events[$situation_journal_id]['events'][$event_situation['event_id']]['event_id'] = $event_situation['event_id'];
                        $situations_events[$situation_journal_id]['events'][$event_situation['event_id']]['event_situations'][] = $event_situation;
                    }

                    $log->addLog('флаг существования события в ситуации = ' . $flag_exist_event);
                    $log->addLog('флаг существования объекта с событием = ' . $flag_exist_main);
                    $log->addLog('Найденный ключ журнала событий = ' . $find_event_journal_id);
                    $log->addLog('Флаг создания новой ситуации = ' . $new_situation_flag_create);

                    $log->addLog('перебрать все найденные события в ситуации. ' .
                        ' флаг существования события в ситуации flag_exist_event = ' . $flag_exist_event .
                        ' флаг существования объекта с событием flag_exist_main = ' . $flag_exist_main .
                        ' Найденный ключ журнала событий find_event_journal_id = ' . $find_event_journal_id .
                        ' Флаг создания новой ситуации new_situation_flag_create = ' . $new_situation_flag_create);

                    //          и. Событие такое есть?
                    if ($flag_exist_event === 1) {
                        // проверить ситуацию на устаревание, и если она устарела, то создать новую, иначе дополнить или обновить
                        $response = $situation_cache_controller->getSituation($mine_id, $situation_id, $situation_journal_id);
                        $log->addLogAll($response);
                        if ($response['status'] != 1) {
                            throw new Exception('Ошибка при получении ситуации из кеша ');
                        }

                        $situation_current = $response['Items'];

                        // берем от времени окончания ситуации расчет 30 минут, если там пусто, то от даты начала ситуации
                        $date_time_end_situation = $situation_current['date_time_end'];
                        if (!$date_time_end_situation) {
                            $date_time_end_situation = $situation_current['date_time_start'];
                        }
                        $delta_time = strtotime($date_time) - strtotime($date_time_end_situation);

                        $log->addLog('расчет времени события в секундах по отношению к ситуации. ' .
                            ' $delta_time = ' . $delta_time .
                            ' Время события date_time = ' . $date_time .
                            ' Время ситуации date_time_end_situation = ' . $date_time_end_situation);

                        // время в секундах
                        if ($delta_time > 1800) {
                            $new_situation_flag_create_by_old = 1;                                                      // Поднимаем флаг создания новой ситуации, по причине устаревшей ситуации
                            unset($situations_events[$situation_journal_id]);                                           // Ситуация устарела, выкидываем ее из списка на анализ

                            $log->addLog('Событие случилось по истечению 30 минут после ситуации. ');
                        } else {
                            $new_situation_flag_create_by_one_actual = 1;                                               // Есть хотя бы одна актуальная ситуация
                            //              й. Объект новый?
                            //                  к. создать новое событие. Дополнить ситуацию
                            if ($flag_exist_main == 0) {
                                //      д. создаем новое событие в БД
                                /**
                                 * Получение дополнительных параметров по объекту сгенерировавшему событие, нужных для записи в таблицу
                                 */
                                $log->addLog('Создать новое событие. Дополнить ситуацию ключ журнала событий. find_event_journal_id = ' . $find_event_journal_id);

                                if ($find_event_journal_id == -1) {

                                    $response = self::GetObjectTitle($object_table, $mine_id, $main_id);
                                    $log->addLogAll($response);
                                    if ($response['status'] != 1) {
                                        throw new Exception('Не смог получить название объекта ');
                                    }
                                    $object_id = $response['object_id'];
                                    $object_title = $response['object_title'];
                                    $group_alarm_id = $response['group_alarm_id'];

                                    $response = EventBasicController::createEventJournalWithStatus($event_id, $main_id, $edge_id, $value, $date_time, $xyz, $value_status_id, $parameter_id, $object_id, $mine_id, $object_title, $object_table, $event_status_id, $group_alarm_id);
                                    $log->addLogAll($response);

                                    if ($response['status'] != 1) {
                                        throw new Exception('Ошибка сохранения в event_journal');
                                    }
                                    $event_journal_id = $response['event_journal_id'];
//                                        $save_event_journal_id = $response['event_journal_id'];

                                    /**
                                     * Сохранение события в кэш
                                     */
                                    $response = (new EventCacheController())->setEvent(
                                        $mine_id, $event_id, $main_id, $event_status_id, $edge_id, $value, $value_status_id, $date_time,
                                        $xyz, $parameter_id, $object_id, $object_title, $object_table, $event_journal_id, $group_alarm_id
                                    );
                                    $log->addLogAll($response);
                                    if ($response['status'] != 1) {
                                        throw new Exception('Ошибка сохранения события в кэш');
                                    }
                                } else {
                                    $event_journal_id = $find_event_journal_id;
                                }


                                //      е. создаем новую привязку ситуации и события в БД

                                // сохранение ситуации в кеш
                                $response = EventBasicController::createEventJournalSituationJournal($event_journal_id, $situation_journal_id);
                                $log->addLogAll($response);
                                if ($response['status'] != 1) {
                                    throw new Exception('Ошибка сохранения в event_journal');
                                }

                                //      сохраняем привязку событий к ситуациям в кеш
                                $response = $situation_cache_controller->setEventSituation($situation_journal_id, $event_journal_id, $main_id, $event_id, $mine_id, $date_time, $date_time, $event_status_id, $value_status_id);
                                $log->addLogAll($response);
                                if ($response['status'] != 1) {
                                    throw new Exception('Ошибка при сохранении привязки события к ситуации в кеш');
                                }

                                //      ж. ищем опасную зону для ситуации (охват 100м от выработки искомой)
                                if ($edge_id and $edge_id != -1) {
                                    $response = EdgeMainController::GetEdgesRelation($edge_id, $mine_id, 50, $xyz);
                                    $log->addLogAll($response);
                                    if ($response['status'] != 1) {
                                        throw new Exception('Ошибка при расчете опасной зоны');
                                    }
                                    $zones_new = $response['Items'];

                                    //      добавляем выработку в зону в БД
                                    $response = SituationBasicController::addSituationJournalZone($situation_journal_id, $zones_new);
                                    $log->addLogAll($response);
                                    if ($response['status'] != 1) {
                                        throw new Exception('Ошибка при сохранении опасной зоны в БД');
                                    }

                                    //      дополнил список зон ситуаций в кеше
                                    $response = $situation_cache_controller->multiSetZone($situation_journal_id, $zones_new, $mine_id, $situation_id);
                                    $log->addLogAll($response);

                                    if ($response['status'] != 1) {
                                        throw new Exception('Ошибка при сохранении опасной зоны в кеш');
                                    }
                                }

                                // создаем результирующий объект ситуации
                                $situation_current_to_ws = array(
                                    'situation_journal_id' => $situation_journal_id,                                                                // ключ журнала ситуации
                                    'mine_id' => $mine_id,                                                                                          // ключ шахты
                                    'situation_id' => $situation_id,                                                                                // ключ ситуации
                                    'situation_title' => '',                                                                                        // название ситуации
                                    'status_checked' => 0,                                                                                          // статус проверки
                                    'situation_date_time' => $situation_current['date_time_start'],                                                 // время создания ситуации
                                    'situation_date_time_format' => date('d.m.Y H:i:s', strtotime($situation_current['date_time_start'])),   // время создания ситуации форматированное
                                    'object_id' => $object_id,                                                                                      // ключ работника
                                    'object_title' => $object_title,                                                                                // ФИО работника
                                    'edge_id' => $edge_id,                                                                                          // выработка в которой произошла ситуация
                                    'place_id' => 0,                                                                                                // место ситуации
                                    'status_id' => $value_status_id,                                                                                // статус значения (нормальное/ аварийное)
                                    'sensor_value' => $value,                                                                                       // значение концентрации газа
                                    'kind_reason_id' => null,                                                                                       // вид причины опасного действия
                                    'status_date_time' => date('d.m.Y H:i:s', strtotime($date_time)),                                        // время изменения статуса ситуации
                                    'situation_status_id' => $situation_current['situation_status_id'],                                             // текущий статус ситуации (принята в работу, устранена и т.д.)
                                    'duration' => null,                                                                                             // продолжительность ситуации
                                    'statuses' => [],                                                                                               // список статусов (история изменения ситуации)
                                    'gilties' => [],                                                                                                // список виновных
                                    'operations' => [],                                                                                             // список принятых мер
                                    'event_journals' => (object)array(),                                                                            // список журнала событий ситуации
                                    'object_table' => $object_table                                                                                 // таблица в котрой лежит объект (сенсор, воркер)
                                );

                                $log->addLog('Создал новое событие дополнил ситуацию');
                            } else {
                                //              л. Объект был?
                                //                  Обновить время окончания ситуации в ситуации и в событии. Обнуляем флаг создания новой независимой ситуации на основе этого события

                                $log->addLog('Обновляем время в ситуации и в событии.');

                                // обновляем время в ситуации в БД
                                $response = SituationBasicController::updateSituationJournal($situation_current['situation_journal_id'], $date_time);
                                $log->addLogAll($response);
                                if ($response['status'] != 1) {
                                    throw new Exception('Ошибка при обновлении времени окончания ситуации в БД:' . $situation_current['situation_journal_id']);
                                }

                                // обновляем время в ситуации в кеше
                                $response = $situation_cache_controller->setSituation(
                                    $situation_current['situation_journal_id'],
                                    $situation_current['situation_id'],
                                    $situation_current['date_time'],
                                    $situation_current['main_id'],
                                    $situation_current['situation_status_id'],
                                    $situation_current['danger_level_id'],
                                    $situation_current['company_department_id'],
                                    $situation_current['mine_id'],
                                    $situation_current['date_time_start'],
                                    $date_time);
                                $log->addLogAll($response);
                                if ($response['status'] != 1) {
                                    throw new Exception('Ошибка при сохранении ситуации в кеше');
                                }

                                // создаем результирующий объект ситуации
                                $situation_current_to_ws = array(
                                    'situation_journal_id' => $situation_current['situation_journal_id'],                                               // ключ журнала ситуации
                                    'mine_id' => $mine_id,                                                                                              // ключ шахты
                                    'situation_id' => $situation_current['situation_id'],                                                               // ключ ситуации
                                    'situation_title' => '',                                                                                            // название ситуации
                                    'status_checked' => 0,                                                                                              // статус проверки
                                    'situation_date_time' => $situation_current['situation_status_id'],                                                 // время создания ситуации
                                    'situation_date_time_format' => date('d.m.Y H:i:s', strtotime($situation_current['situation_status_id'])),   // время создания ситуации форматированное
                                    'object_id' => $situation_current['main_id'],                                                                       // ключ работника
                                    'object_title' => '',                                                                                               // ФИО работника
                                    'edge_id' => $edge_id,                                                                                              // выработка в которой произошла ситуация
                                    'place_id' => 0,                                                                                                    // место ситуации
                                    'status_id' => $value_status_id,                                                                                    // статус значения (нормальное/ аварийное)
                                    'sensor_value' => $value,                                                                                           // значение концентрации газа
                                    'kind_reason_id' => null,                                                                                           // вид причины опасного действия
                                    'status_date_time' => date('d.m.Y H:i:s', strtotime($date_time)),                                            // время изменения статуса ситуации
                                    'situation_status_id' => $situation_current['situation_status_id'],                                                 // текущий статус ситуации (принята в работу, устранена и т.д.)
                                    'duration' => null,                                                                                                 // продолжительность ситуации
                                    'statuses' => [],                                                                                                   // список статусов (история изменения ситуации)
                                    'gilties' => [],                                                                                                    // список виновных
                                    'operations' => [],                                                                                                 // список принятых мер
                                    'event_journals' => (object)array(),                                                                                // список журнала событий ситуации
                                    'object_table' => $object_table                                                                                     // таблица в которой лежит объект (сенсор, воркер)
                                );

                                // обновляем время в событии
                                $response = (new EventCacheController())->updateEventCacheValueWithStatus($mine_id, $event_id, $main_id, $date_time, $value, $event_status_id, $value_status_id);
                                $log->addLogAll($response);
                                if ($response['status'] != 1) {
                                    throw new Exception('Ошибка при сохранении события в кеше');
                                }
                                $event_journal_id = $response['event_journal_id'];
                                $value_from_cache = $response['value_from_cache'];

                                // пишем в бд обновленный статус у события и значение статуса события
                                $response = EventBasicController::updateEventJournalWithStatus($event_journal_id, $event_status_id, $value_status_id, $date_time, $value_from_cache);
                                $log->addLogAll($response);
                                if ($response['status'] != 1) {
                                    throw new Exception('Ошибка при обновлении события, когда оно кончилось и сразу началось');
                                }

                                // обновляем время в связке события и ситуации
                                //      сохраняем привязку событий к ситуациям в кеш
                                $response = $situation_cache_controller->setEventSituation($situation_journal_id, $event_journal_id, $main_id, $event_id, $mine_id, $event_date_time_start, $date_time, $event_status_id, $value_status_id);
                                $log->addLogAll($response);
                                if ($response['status'] != 1) {
                                    throw new Exception('Ошибка при сохранении привязки события к ситуации в кеш');
                                }
                            }
                        }
                    }
                }

                $log->addLog('Прошел обработку существующих событий и ситуаций. флаг создания новой ситуации new_situation_flag_create = ' . $new_situation_flag_create . '; new_situation_flag_create_by_old = ' . $new_situation_flag_create_by_old . '; new_situation_flag_create_by_one_actual = ' . $new_situation_flag_create_by_one_actual);

                //      п. флаг создания новой независимой ситуации на основе этого события поднят?
                // либо это событие не встретилось ни в одной ситуации и потому нужно создать
                // или были ситуации с этим событием, но устаревшие, в любом случае нужно организовывать поиск новой ситуации
                // либо с одним этим событием, или с другими событиями, если таковые имелись (не ансетились)
                if ($new_situation_flag_create === 1 or ($new_situation_flag_create === 0 and $new_situation_flag_create_by_old === 1 and $new_situation_flag_create_by_one_actual === 0)) {
                    $log->addLog('п. флаг создания новой независимой ситуации на основе этого события поднят');
                    //              ё. ищем комплект ситуаций с таким событием и событиями из ситуации в БД
                    //              ж. комплект есть? создаем новую ситуацию с данными из старой + новое событие,старую закрываем. Обнуляем флаг создания новой независимой ситуации на основе этого события
                    //              з. комплекта нет? создаем новую ситуацию с этим событием
                    //          р. создать новую ситуацию на основе этого события
                    // $situations_events - содержит список актуальных ситуаций и событий в ней
                    // структура $situations_events:
                    //              [situation_journal_id]
                    //                      situation_journal_id:   - ключ журнала ситуаций
                    //                      situation_id:           - ключ ситуации
                    //                      events:
                    //                          [event_id]
                    //                              event_id:           - ключ события
                    //                              event_situations    - список событий в ситуации
                    //                                  [ ]
                    //                                      situation_journal_id     -   идентификатор ключа ситуации в журнале ситуаций
                    //                                      event_journal_id         -   идентификатор журнала событий
                    //                                      main_id                  -   идентификатор объекта к которому относится событие
                    //                                      event_id                 -   идентификатор события
                    //                                      mine_id                  -   идентификатор шахты
                    //                                      date_time_start          -   дата начала события
                    //                                      date_time_end            -   дата окончания события
                    //                                      event_status_id          -   статус события (устранено/устраняется и т.д.)
                    //                                      value_status_id          -   статус значения события (нормальное/аварийное)
                    if (isset($situations_events) and !empty($situations_events)) {
                        $event_object[$event_id] = $event_id;                                                           // гарантированно на поиск заталкиваем пришедшее событие
                        foreach ($situations_events as $situation_item) {                                               // перебираем все действующие ситуации, с целью получения массива событий, для поиска новой ситуации
                            if (isset($situation_item['events'])) {
                                foreach ($situation_item['events'] as $event_item) {
                                    $event_object[$event_item['event_id']] = $event_item['event_id'];
                                }
                            } else {
                                $log->addLog('У ситуации пустой список событий!!!!!!!');
                            }
                        }
                        $eventKey = "";
                        asort($event_object);                                                                     // сортировка по возрастанию
                        foreach ($event_object as $event_item) {
                            $events[] = $event_item;
                            $eventKey .= $event_item;
                        }

                        // получаем новую ситуацию по группе событий
                        $response = SituationBasicController::getSituationByEvents($events, $event_id, $eventKey);
                        $log->addLogAll($response);
                        if ($response['status'] != 1) {
                            throw new Exception('Ошибка при получении ситуации по событию');
                        }
                        $situation = $response['Items'];
                    }

                    // если ситуации по группе событий нет, то получаем ситуацию по конкретному событию (текущему)
                    if (!isset($situation) or !$situation or count($events) == 1) {
                        $log->addLog('новую ситуацию на основе группы не нашел, потому ищу ситуацию на основе одного события');

                        $response = SituationBasicController::getSituationByEventId($event_id);
                        $log->addLogAll($response);
                        if ($response['status'] != 1) {
                            throw new Exception('Ошибка при получении ситуации по событию');
                        }
                        $situation = $response['Items'];

                        if ($situation) {
                            $new_alarm = 1;
                            // создаем новую ситуацию и событие
                            $response = self::createNewSituationWithEvent($event_id, $event_status_id, $value_status_id, $object_table, $parameter_id, $xyz, $value, $edge_id, $mine_id, $main_id, $date_time, $situation['id'], $situation['danger_level_id'], 34);
                            $log->addLogAll($response);
                            if ($response['status'] != 1) {
                                throw new Exception('Ошибка при получении ситуации по событию');
                            }
                            $situation_current_to_ws = $response['situation_current'];
                        } else {
                            $log->addLog('Событие не включено ни в одну ситуацию!!!!!! Проверить верность генерации аварийного события');
                        }
                    } else {
                        // ПЕРЕКВАЛИФИКАЦИЯ СОБЫТИЯ. Закрываем предыдущие ситуации
                        $log->addLog('закрываем предыдущие ситуации');

                        //      г. создаем новую ситуацию в БД и в кеше. Статус ситуации 34 - неподтвержденная
                        //          создаем статус ситуации как неподтвержденная - т.к. неизвестно, является она штатной или внештатной
                        //          main_id ставим пока объекта сгенерировавшего событие, но в идеале должен быть определен объект, на котором произошла ситуация, например, забой
                        //          company_department_id - оставляем пустой, данное поле появится после того, как будет определен объект, на котором произошло событие (например, по забою можем найти подразделение, которое за него отвечает)
//                        $response = SituationBasicController::createSituationJournal($situation['id'], $date_time, $main_id, 34, $situation['danger_level_id'], NULL, $mine_id, $date_time);
                        $response = self::createNewSituationWithEvent($event_id, $event_status_id, $value_status_id, $object_table, $parameter_id, $xyz, $value, $edge_id, $mine_id, $main_id, $date_time, $situation['id'], $situation['danger_level_id'], 34);
                        $log->addLogAll($response);
                        if ($response['status'] != 1) {
                            throw new Exception('Ошибка при получении ситуации по событию');
                        }
                        $situation_journal_id = $response['situation_current']['situation_journal_id'];

                        //      ё. новую ситуацию и новое событие в кеше
                        $response = $situation_cache_controller->setSituation($situation_journal_id, $situation['id'], $date_time, $main_id, 34, $situation['danger_level_id'], NULL, $mine_id, $date_time, NULL);
                        $log->addLogAll($response);
                        if ($response['status'] != 1) {
                            throw new Exception('Ошибка при укладыванию ситуации в кеш');
                        }

                        $response = self::addZoneAndSituationEventToSituation($situation_journal_id, $zones, $mine_id, $event_journal_ids, $edge_id, $situation['id'], $xyz);
                        $log->addLogAll($response);
                        if ($response['status'] != 1) {
                            throw new Exception('Ошибка при переносе событий и зон в новую ситуацию');
                        }
                        // создаем результирующий объект ситуации
                        $situation_current_to_ws = array(
                            'situation_journal_id' => $situation_journal_id,                                            // ключ журнала ситуации
                            'mine_id' => $mine_id,                                                                      // ключ шахты
                            'situation_id' => $situation['id'],                                                         // ключ ситуации
                            'situation_title' => '',                                                                    // название ситуации
                            'status_checked' => 0,                                                                      // статус проверки
                            'situation_date_time' => $date_time,                                                        // время создания ситуации
                            'situation_date_time_format' => date('d.m.Y H:i:s', strtotime($date_time)),          // время создания ситуации форматированное
                            'object_id' => $main_id,                                                                    // ключ работника
                            'object_title' => '',                                                                       // ФИО работника
                            'edge_id' => $edge_id,                                                                      // выработка в которой произошла ситуация
                            'place_id' => 0,                                                                            // место ситуации
                            'status_id' => $value_status_id,                                                            // статус значения (нормальное/ аварийное)
                            'sensor_value' => $value,                                                                   // значение концентрации газа
                            'kind_reason_id' => null,                                                                   // вид причины опасного действия
                            'status_date_time' => date('d.m.Y H:i:s', strtotime($date_time)),                    // время изменения статуса ситуации
                            'situation_status_id' => 38,                                                                // текущий статус ситуации (принята в работу, устранена и т.д.)
                            'duration' => null,                                                                         // продолжительность ситуации
                            'statuses' => [],                                                                           // список статусов (история изменения ситуации)
                            'gilties' => [],                                                                            // список виновных
                            'operations' => [],                                                                         // список принятых мер
                            'event_journals' => (object)array(),                                                        // список журнала событий ситуации
                            'object_table' => $object_table                                                             // таблица в которой лежит объект (сенсор, воркер)
                        );

                        $log->addLog('Закрываем предыдущие ситуации');

                        foreach ($situations_events as $situations_event_item) {
                            // пишем в БД обновленное время ситуации и ее статус 32 (переквалифицировано системой)
                            $response = SituationBasicController::updateSituationJournalDateTimeEndStatus($situations_event_item['situation_journal_id'], $date_time, 32);
                            $log->addLogAll($response);
                            if ($response['status'] != 1) {
                                throw new Exception('Ошибка обновлении времени окончания ситуации и ее статуса в БД');
                            }
                            // удалить из кеша ситуацию
                            $response = $situation_cache_controller->deleteSituation($mine_id, $situations_event_item['situation_journal_id'], $situations_event_item['situation_id']);
                            $log->addLogAll($response);
                            if ($response['status'] != 1) {
                                throw new Exception('Ошибка обновлении времени окончания ситуации и ее статуса в БД');
                            }
                            // удалить из кеша зону
                            $response = $situation_cache_controller->deleteMultiZone($mine_id, $situations_event_item['situation_journal_id']);
                            $log->addLogAll($response);
                            if ($response['status'] != 1) {
                                throw new Exception('Ошибка обновлении времени окончания ситуации и ее статуса в БД');
                            }

                            // удалить из кеша связь события с ситуацией
                            $response = $situation_cache_controller->deleteMultiSituationEvent($mine_id, $situations_event_item['situation_journal_id']);
                            $log->addLogAll($response);
                            if ($response['status'] != 1) {
                                throw new Exception('Ошибка обновлении времени окончания ситуации и ее статуса в БД');
                            }

                            $situations_event_item['date_time_now'] = $date_time;
                            $log->addLog('Отправка данных на вебсокет (overrideSituationJournal)');
                            $response = WebsocketController::SendMessageToWebSocket('overrideSituationJournal', $situations_event_item);
                            $log->addLogAll($response);
                            if ($response['status'] != 1) {
                                throw new Exception('Ошибка отправки данных на вебсокет (overrideSituationJournal)');
                            }
                        }
                    }
                } else {
                    $log->addLog('п. флаг создания новой независимой ситуации ОПУЩЕН!!!');
                }

            } elseif ($zones and $value_status_id == StatusEnumController::NORMAL_VALUE) {
                // список ситуаций полон - событие хорошее

                $log->addLog('список полон - событие хорошее - 3. Плохое событие делаем хорошим обновляем время окончания ситуации');

                //     в. перебрать все ситуации, что нашлись в этом edge_id
                foreach ($zones as $zone) {
                    $situation_journal_id = $zone['situation_journal_id'];                                              // текущий рассматриваемый ключ журнала ситуация
                    $situation_id = $zone['situation_id'];                                                              // текущий рассматриваемый тип ситуации

                    // получить список событий данной ситуации
                    $response = $situation_cache_controller->getEventSituationList($mine_id, '*', $situation_journal_id);
                    $log->addLogAll($response);
                    if ($response['status'] != 1) {
                        throw new Exception('Ошибка при получении списка событий по конкретной ситуации');
                    }
                    $list_event_situation = $response['Items'];

                    // перебрать все найденные события в ситуации
                    // ищем событие конкретного объекта в списке событий по ситуациям
                    //     д. Сравнить события из ситуации с пришедшим событием
                    $flag_exist_event = 0;                                                                              // флаг существования события в данной ситуации
                    $flag_exist_main = 0;                                                                               // флаг существования объекта в данном событии
                    foreach ($list_event_situation as $event_situation) {
                        //      д. Сравнить события из ситуации с пришедшим событием, собрать события ситуации в кучу
                        if ($event_situation['event_id'] == $event_id) {
                            $flag_exist_event = 1;
                            // проверить объект тот же?
                            if ($event_situation['main_id'] == $main_id) {
                                $flag_exist_main = 1;
                                $find_event_journal = $event_situation;
                            }
                        }
                    }

                    $log->addLog('флаг существования события в ситуации = ' . $flag_exist_event);
                    $log->addLog('флаг существования объекта с событием = ' . $flag_exist_main);

                    $log->addLog('перебрать все найденные события в ситуации. ' .
                        ' флаг существования события в ситуации flag_exist_event = ' . $flag_exist_event .
                        ' флаг существования объекта с событием flag_exist_main = ' . $flag_exist_main);

                    //         е. События такого нет?
                    //             ё. выходим из метода
                    //         ж. Событие такое есть?
                    //             з. оно было плохим $value_status_id == 44?
                    //                 и. делаем хорошим $value_status_id == 45. event_status_id = 52 и обновляем время окончания ситуации
                    //             к. оно было хорошим?
                    //                 л. выходим из метода
                    if ($flag_exist_event === 1 and $flag_exist_main === 1 and $find_event_journal['value_status_id'] != $value_status_id) {

                        // пишем в кеш обновленный статус у события в кеш Событий
                        $response = (new EventCacheController())->updateEventCacheValueWithStatusAndDateTime($find_event_journal['event_journal_id'], $mine_id, $event_id, $main_id, 'value_status_id', $value_status_id, $event_status_id, $date_time);
                        $log->addLogAll($response);
                        if ($response['status'] != 1) {
                            throw new Exception('Ошибка обновлении события в кэше');
                        }
                        $value_from_cache = $response['value_from_cache'];

                        // пишем в бд обновленный статус у события и значение статуса события
                        $response = EventBasicController::updateEventJournalWithStatus($find_event_journal['event_journal_id'], $event_status_id, $value_status_id, $date_time, $value_from_cache);
                        $log->addLogAll($response);
                        if ($response['status'] != 1) {
                            throw new Exception('Ошибка при обновлении статуса события на хорошее');
                        }

                        // пишем в кеш обновленный статус у события в кеш Ситуаций
                        $response = $situation_cache_controller->setEventSituation($situation_journal_id, $find_event_journal['event_journal_id'], $main_id, $event_id, $mine_id, $find_event_journal['date_time_start'], $date_time, $event_status_id, $value_status_id);
                        $log->addLogAll($response);
                        if ($response['status'] != 1) {
                            throw new Exception('Ошибка обновлении связки события с ситуацией в кэше');
                        }
                        // пишем в БД обновленное время ситуации
                        $response = SituationBasicController::updateSituationJournal($situation_journal_id, $date_time);
                        $log->addLogAll($response);
                        if ($response['status'] != 1) {
                            throw new Exception('Ошибка обновлении времени окончания ситуации в БД');
                        }
                        // пишем в кеш обновенное время ситуации
                        $response = $situation_cache_controller->updateSituationCacheValue($mine_id, $situation_id, $situation_journal_id, 'date_time_end', $date_time);
                        $log->addLogAll($response);
                        if ($response['status'] != 1) {
                            throw new Exception('Ошибка обновлении времени окончания ситуации в БД');
                        }
                        // создаем результирующий объект ситуации
                        $situation_current_to_ws = array(
                            'situation_journal_id' => $situation_journal_id,                                            // ключ журнала ситуации
                            'situation_id' => $situation_id,                                                            // ключ ситуации
                            'situation_title' => '',                                                                    // название ситуации
                            'mine_id' => $mine_id,                                                                      // ключ шахты
                            'status_checked' => 0,                                                                      // статус проверки
                            'situation_date_time' => $date_time,                                                        // время создания ситуации
                            'situation_date_time_format' => date('d.m.Y H:i:s', strtotime($date_time)),          // время создания ситуации форматированное
                            'object_id' => $main_id,                                                                    // ключ работника
                            'object_title' => '',                                                                       // ФИО работника
                            'edge_id' => $edge_id,                                                                      // выработка в которой произошла ситуация
                            'place_id' => 0,                                                                            // место ситуации
                            'status_id' => $value_status_id,                                                            // статус значения (нормальное/ аварийное)
                            'sensor_value' => $value,                                                                   // значение концентрации газа
                            'kind_reason_id' => null,                                                                   // вид причины опасного действия
                            'status_date_time' => date('d.m.Y H:i:s', strtotime($date_time)),                    // время изменения статуса ситуации
                            'situation_status_id' => '',                                                                // текущий статус ситуации (принята в работу, устранена и т.д.)
                            'duration' => null,                                                                         // продолжительность ситуации
                            'statuses' => [],                                                                           // список статусов (история изменения ситуации)
                            'gilties' => [],                                                                            // список виновных
                            'operations' => [],                                                                         // список принятых мер
                            'event_journals' => (object)array(),                                                        // список журнала событий ситуации
                            'object_table' => $object_table                                                             // таблица в котрой лежит объект (сенсор, воркер)
                        );
                    }
                }
            } else {
                // неизвестный случай, проверьте алгоритм
                $log->addLog('неизвестный случай, проверьте алгоритм - 4');
            }
            /**
             * Рассылка
             */
            if ($value_status_id == 44/*StatusEnumController::EMERGENCY_VALUE*/) {
                $event_title = (new Query())
                    ->select('title')
                    ->from('event')
                    ->where(['id' => $event_id])
                    ->limit(1)
                    ->scalar();
                if (!$event_title) {
                    throw new Exception('. Не найдена запись из таблицы event c id = ' . $event_id);
                }


                $message = $event_title;
                $message .= ". Объект: " . $situation_current_to_ws['object_title'];
                $message .= ". Значение: $value";
                $message .= '. Дата: ' . explode('.', $date_time)[0];

                $place_id = null;
                $place = (new Query())
                    ->select('place.id as place_id, place.title as place_title')
                    ->from('place')
                    ->innerJoin('edge', 'edge.place_id = place.id')
                    ->where(['edge.id' => $edge_id])
                    ->limit(1)
                    ->one();
                if ($place) {
                    $message .= ". Место: " . $place['place_title'];
                    $place_id = $place['place_id'];
                    $place_title = $place['place_title'];
                }
                $situation_current_to_ws['place_id'] = $place_id;
                $situation_current_to_ws['place_title'] = $place_title;
                // отправляем событие в журнал оператора АБ в том случае, если оно по превышению газов
                // делаем проверку на существование обязательно генерируемой ситуации (может быть, что событие есть, а ситуации для него нет)
                if (
                    isset($situation_current_to_ws['situation_journal_id']) and
                    $value_status_id == 44 and
                    (
                        $event_id == EventEnumController::CH4_EXCESS_LAMP or // Превышение CH4 со светильника
                        $event_id == EventEnumController::CH4_EXCESS_STAC or // Превышение концентрации газа CH4
                        $event_id == EventEnumController::DCS_STOP or // Отказ службы сбора данных
                        $event_id == EventEnumController::DUST_EXCESS_STAC or // Превышение удельной массы пыли
                        $event_id == EventEnumController::CH4_CRUSH_LAMP or // Отказ светильника
                        $event_id == EventEnumController::CH4_CRUSH_STAC                                                // Отказ стационарного датчика
                    )
                ) {
                    $response = WebsocketController::SendMessageToWebSocket('addNewSituationJournal', $situation_current_to_ws);
                    $log->addLogAll($response);
                    if ($response['status'] != 1) {
                        throw new Exception('Ошибка отправки данных на вебсокет (addNewSituationJournal)');
                    }
//                    $log->saveLogInGasCache();
                } else {
                    $log->addLog('на веб сокет не слал - не то сообщение');
                    $log->addLog('value_status_id: ');
                    $log->addLog($value_status_id);
                    $log->addLog('event_id');
                    $log->addLog($event_id);
                    $log->addLog('situation_journal_id');

                    if (isset($situation_current_to_ws['situation_journal_id'])) {
                        $log->addLog($situation_current_to_ws['situation_journal_id']);
                    }
                }

                if ($new_alarm == 1) {
                    $date_now = Assistant::GetDateNow();
                    // делаем отметку о расслыке - пока не факт, что успешной
                    // СТАТУСЫ:
                    //      104     Подготовил к отправке
                    //      105	    Отправил
                    //      106	    Доставил
                    // ТИПЫ ОТПРАВКИ:
                    //      1	    email
                    //      2	    local
                    //      3	    server
                    //      4	    ftp
                    //      5	    sms
                    $response = SituationBasicController::createSendStatus($situation_current_to_ws['situation_journal_id'], 1, 104, $date_now);
                    $log->addLogAll($response);
                    if ($response['status'] != 1) {
                        throw new Exception('Ошибка сохранения статуса начала отправки сообщений ситуации в БД');
                    }
                    $situation_journal_send_status_id = $response['situation_journal_send_status_id'];

                    $situation_cache_controller->setSendStatus($mine_id, $situation_current_to_ws['situation_journal_id'], 1, $date_now, 104, $situation_journal_send_status_id);
                    $log->addLogAll($response);
                    if ($response['status'] != 1) {
                        throw new Exception('Ошибка сохранения статуса начала отправки сообщений ситуации В КЕШ');
                    }

                    // получаем привязку людей к шахте - нужно для определения на какую шахту слать (заполярную или воркутинскую)
                    //в 18 параметре 1 - Заполярная, 2 - Воркутинская -  данная часть хранится в справочнике group_alarm
                    $alarm_group_id = (new WorkerCacheController())->getParameterValueHash($main_id, 18/*принадлежность к компании*/, 1/*ParameterTypeEnumController::REFERENCE*/);
                    if ($alarm_group_id === false) {
                        $alarm_group_id = null;
                    } else {
                        $alarm_group_id = $alarm_group_id['value'];
                    }

                    /**
                     * Email
                     */
                    $addresses = XmlController::getEmailSendingList($event_id, $alarm_group_id);
                    if ($addresses) {
                        $response = XmlController::SendSafetyEmail($message, $addresses);
                        $log->addLogAll($response);
                    } else {
                        $log->addLog('Нет актуальных email адресов для рассылки');
                    }

                    $date_now = Assistant::GetDateNow();
                    $response = SituationBasicController::updateSendStatus($situation_journal_send_status_id, 105, $date_now);
                    $log->addLogAll($response);
                    if ($response['status'] != 1) {
                        throw new Exception('Ошибка обновления статуса начала отправки сообщений ситуации');
                    }

                    $response = $situation_cache_controller->setSendStatus($mine_id, $situation_current_to_ws['situation_journal_id'], 1, $date_now, 105, $situation_journal_send_status_id);
                    $log->addLogAll($response);
                    if ($response['status'] != 1) {
                        throw new Exception('Ошибка сохранения статуса начала отправки сообщений ситуации В КЕШ');
                    }

                    /**
                     * СМС
                     */
                    $numbers = XmlController::getSmsSendingList($event_id, $alarm_group_id);
                    if ($numbers) {
                        $response = SmsSender::actionSendSmsProxy($message, $numbers);
                        $log->addLogAll($response);
                    } else {
                        $log->addLog('Нет актуальных номеров для СМС рассылки');
                    }
                } else {
                    $log->addLog('Ситуация старая, рассылка не производилась или для этого события не предусмотрена ситуация');
                }
            }

            /** Метод окончание */

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
            $log->saveLogInGasCache();
        }

        $log->addLog('Окончание выполнения метода');

        return array_merge(['Items' => $result], $log->getLogAll());;
    }

    // createNewSituationWithEvent - метод по созданию новой ситуации с событием
    //        $event_id,                - ключ события которое произошло
    //        $event_status_id,         - ключ статуса события ( получено, снято, снято диспетчером, снято системой)
    //        $value_status_id,         - статус значения события (аварийное, нормальное)
    //        $object_table,            - таблица объекта сгенерировавшего события
    //        $parameter_id,            - ключ параметра (газ сн4, и т.д.)
    //        $xyz,                     - координата в которой произошло событие
    //        $value,                   - значение параметра события
    //        $edge_id,                 - ключ выработки в которой произошло событие
    //        $mine_id,                 - ключ шахтного поля
    //        $main_id,                 - ключ объекта сгенерировавшего события
    //        $date_time,               - дата когда произошло событие
    //        $situation_id,            - ключ справочника ситуации
    //        $danger_level_id,         - ключ уровня опасности
    //        $situation_status_id      - статус ситуации
    // выходной объект:
    //      situation_current:
    //          'situation_journal_id' => $situation_journal_id,                                                        // ключ журнала ситуации
    //          'situation_id' => $situation_id,                                                                        // ключ ситуации
    //          'situation_title' => $situation_title,                                                                  // название ситуации
    //          'status_checked' => 0,                                                                                  // статус проверки
    //          'situation_date_time' => $situation_date_time,                                                          // время создания ситуации
    //          'situation_date_time_format' => date('d.m.Y H:i:s', strtotime($situation_date_time)),                   // время создания ситуации форматированное
    //          'object_id' => $main_id,                                                                                // ключ работника
    //          'object_title' => $object_title,                                                                    // ФИО работника
    //          'edge_id' => $edge_id,                                                                                  // выработка в которой произошла ситуация
    //          'place_id' => $place_id,                                                                                // место ситуации
    //          'status_id' => $value_status_id,                                                                        // статус значения (нормальное/ аварийное)
    //          'sensor_value' => $value,                                                                               // значение концентрации газа
    //          'kind_reason_id' => null,                                                                               // вид причины опасного действия
    //          'status_date_time' => date('d.m.Y H:i:s', strtotime($situation_date_time)),                             // время изменения статуса ситуации
    //          'situation_status_id' => 38,                                                                            // текущий статус ситуации (принята в работу, устранена и т.д.)
    //          'duration' => null,                                                                                     // продолжительность ситуации
    //          'statuses' => [],                                                                                       // список статусов (история изменения ситуации)
    //          'gilties' => [],                                                                                        // список виновных
    //          'operations' => [],                                                                                     // список принятых мер
    //          'event_journals' => {},                                                                                 // список журнала событий ситуации
    //          'object_table' => $object_table                                                                         // таблица в котрой лежит объект (сенсор, воркер)
    // разработал: Якимов М.Н.
    // дата 06.02.2020
    public static function createNewSituationWithEvent(
        $event_id, $event_status_id, $value_status_id, $object_table, $parameter_id, $xyz, $value,
        $edge_id, $mine_id, $main_id, $date_time, $situation_id, $danger_level_id, $situation_status_id
    )
    {
        $log = new LogAmicumFront("createNewSituationWithEvent");

        $situation_current = array();                                                                                   // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $group_alarm_id = null;                                                                                         // результирующий массив (если требуется)

        try {
            $log->addLog("Начало выполнения метода");

            /** Метод начало */
            $situation_cache_controller = (new SituationCacheController());
            //      г. Создаем новую ситуацию в БД и в кеше. статус ситуации 34 - неподтвержденная
            //          создаем статус ситуации как неподтвержденная - т.к. неизвестно, является она штатной или внештатной
            //          main_id ставим пока объекта сгенерировавшего событие, но в идеале должен быть определен объект на котором произощла ситуация - например забой
            //          company_department_id - оставляем пустой, данное поле появится после того, как будет определен объект на котором произошло событие (например, по забою можем найти подразделение, которое з анего отвечает)
            $response = SituationBasicController::createSituationJournal($situation_id, $date_time, $main_id, $situation_status_id, $danger_level_id, NULL, $mine_id, $date_time);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка при получении ситуации по событию');
            }
            $situation_journal_id = $response['situation_journal_id'];

            //      д. Создаем новое событие в БД
            /**
             * Получение дополнительных параметров по объекту сгенерировшему событие, нужных для записи в таблицу
             */
            $response = self::GetObjectTitle($object_table, $mine_id, $main_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Не смог получить название объекта ');
            }
            $object_id = $response['object_id'];
            $object_title = $response['object_title'];
            $group_alarm_id = $response['group_alarm_id'];

            $response = EventBasicController::createEventJournalWithStatus($event_id, $main_id, $edge_id, $value, $date_time, $xyz, $value_status_id, $parameter_id, $object_id, $mine_id, $object_title, $object_table, $event_status_id, $group_alarm_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка сохранения в event_journal');
            }
            $event_journal_id = $response['event_journal_id'];

            //      е. Создаем новую привязку ситуации и события в БД сохранение ситуации в кеш
            $response = EventBasicController::createEventJournalSituationJournal($event_journal_id, $situation_journal_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка сохранения в event_journal');
            }

            //      ж. Ищем опасную зону для ситуации (охват 100м от выработки искомой)
            if ($edge_id and $edge_id != -1) {
                $response = EdgeMainController::GetEdgesRelation($edge_id, $mine_id, 50, $xyz);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new Exception('Ошибка при расчете опасной зоны');
                }
                $zones = $response['Items'];
            } else {
                throw new Exception('edge_id пуст или равен -1 - невозможно идентифицировать зону');
            }
            //      з. Новый список зон ситуаций в БД
            $response = SituationBasicController::createSituationJournalZone($situation_journal_id, $zones, $mine_id, $situation_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка при сохранении опасной зоны в БД');
            }


            //      ё. Новую ситуацию и новое событие в кеше
            $response = $situation_cache_controller->setSituation($situation_journal_id, $situation_id, $date_time, $main_id, 34, $danger_level_id, NULL, $mine_id, $date_time, NULL);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка при укладыванию ситуации в кеш');
            }
            /**
             * Сохранение события в кэш
             */
            $response = (new EventCacheController())->setEvent($mine_id, $event_id, $main_id, $event_status_id, $edge_id, $value, $value_status_id, $date_time, $xyz, $parameter_id, $object_id, $object_title, $object_table, $event_journal_id, $group_alarm_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка сохранения события в кэш');
            }

            //      сохраняем привязку событий к ситуациям в кеш
            $response = $situation_cache_controller->setEventSituation($situation_journal_id, $event_journal_id, $main_id, $event_id, $mine_id, $date_time, $date_time, $event_status_id, $value_status_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка при сохранении привязки события к ситуации в кеш');
            }

            //      з. Новый список зон ситуаций в кеше
            $response = $situation_cache_controller->multiSetZone($situation_journal_id, $zones, $mine_id, $situation_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception('Ошибка при сохранении опасной зоны в кеш');
            }

            // создаем результирующий объект ситуации
            $situation_current = array(
                'situation_journal_id' => $situation_journal_id,                                                        // ключ журнала ситуации
                'situation_id' => $situation_id,                                                                        // ключ ситуации
                'situation_title' => '',                                                                                // название ситуации
                'status_checked' => 0,                                                                                  // статус проверки
                'situation_date_time' => $date_time,                                                                    // время создания ситуации
                'situation_date_time_format' => date('d.m.Y H:i:s', strtotime($date_time)),                      // время создания ситуации форматированное
                'object_id' => $main_id,                                                                                // ключ работника
                'object_title' => $object_title,                                                                        // ФИО работника
                'edge_id' => $edge_id,                                                                                  // выработка в которой произошла ситуация
                'place_id' => 0,                                                                                        // место ситуации
                'status_id' => $value_status_id,                                                                        // статус значения (нормальное/ аварийное)
                'sensor_value' => $value,                                                                               // значение концентрации газа
                'kind_reason_id' => null,                                                                               // вид причины опасного действия
                'status_date_time' => date('d.m.Y H:i:s', strtotime($date_time)),                                // время изменения статуса ситуации
                'situation_status_id' => 38,                                                                            // текущий статус ситуации (принята в работу, устранена и т.д.)
                'duration' => null,                                                                                     // продолжительность ситуации
                'statuses' => [],                                                                                       // список статусов (история изменения ситуации)
                'gilties' => [],                                                                                        // список виновных
                'operations' => [],                                                                                     // список принятых мер
                'event_journals' => (object)array(),                                                                    // список журнала событий ситуации
                'object_table' => $object_table                                                                         // таблица, в которой лежит объект (сенсор, воркер)
            );
            $log->addLog("Закончил формировать ситуацию/события и зоны");

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result, 'situation_current' => $situation_current], $log->getLogAll());
    }

    // addZoneAndSituationEventToSituation - метод по установке в заданный журнал ситуации зоны и событий ситуации
    //        $situation_journal_id,    - ключ события которое произошло
    //        $zone_last,               - зона новой ситуации
    //        $mine_id,                 - ключ шахтного поля
    //        $event_journal_ids        - список событий ситуаций для добавления
    //        $edge_id                  - выработка в которой произошло событие
    //        $situation_id             - тип ситуации
    //        $xyz                      - координата события
    // разработал: Якимов М.Н.
    // дата 06.02.2020
    public static function addZoneAndSituationEventToSituation($situation_journal_id, $zone_last, $mine_id, $event_journal_ids, $edge_id, $situation_id, $xyz)
    {
        // Стартовая отладочная информация
        $method_name = 'addZoneAndSituationEventToSituation';                                                                             // название логируемого метода
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
            $situation_cache_controller = (new SituationCacheController());

            //      е. создаем новую привязку ситуации и события в БД массово
            $response = EventBasicController::createEventJournalSituationJournalBatch($event_journal_ids, $situation_journal_id);
            $warnings[] = $response['warnings'];
            $errors[] = $response['errors'];
            if ($response['status'] != 1) {
                throw new Exception($method_name . '. Ошибка массового сохранения в event_journal');
            }

            //      ж. ищем опасную зону для ситуации (охват 100м от выработки искомой)
            if ($edge_id and $edge_id != -1) {
                $response = EdgeMainController::GetEdgesRelation($edge_id, $mine_id, 50, $xyz);
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['status'] != 1) {
                    throw new Exception($method_name . '. Ошибка при расчете опасной зоны');
                }
                $zones = $response['Items'];
            }

            // соединяем зоны по зоне события и зонам из старых событий
            // $zone_last:
            //      []
            //          edge_id -   идентификатор выработки, на которой произошло ситуация
            //          mine_id -   идентификатор шахты, на которой произошло ситуация
            //          situation_journal_id -   ключ ситуации в журнале
            //          situation_id -   ключ типа ситуации
            foreach ($zone_last as $zone_item) {
                $zones[$zone_item['edge_id']] = $zone_item['edge_id'];
            }
            //      з. новый список зон ситуаций в БД
            $response = SituationBasicController::createSituationJournalZone($situation_journal_id, $zones, $mine_id, $situation_id);
            $warnings[] = $response['warnings'];
            $errors[] = $response['errors'];
            if ($response['status'] != 1) {
                throw new Exception($method_name . '. Ошибка при сохранении опасной зоны в БД');
            }

            //      з. новый список зон ситуаций в кеше
            $response = $situation_cache_controller->multiSetZone($situation_journal_id, $zones, $mine_id, $situation_id);
            $warnings[] = $response['warnings'];
            $errors[] = $response['errors'];
            if ($response['status'] != 1) {
                throw new Exception($method_name . '. Ошибка при сохранении опасной зоны в кеш');
            }

            /** Отладка */
            $description = 'Закончил формировать ситуацию/события и зоны';                                                                      // описание текущей отладочной точки
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
//        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
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
     * isChangeEvent - Проверяет изменилось ли событие - загрубление одна смена.
     * Проверка выполняется по значению параметра события, по месту где оно
     * произошло и по изменению статуса события.
     * Если событие не найдено в кэше, предполагается, что оно изменилось
     *
     * @param $mine_id -   идентификатор шахты
     * @param $event_id -   идентификатор события
     * @param $main_id -   идентификатор объекта с которым связано событие
     * @param $date_time -   дата и время события
     * @param $edge_id -   идентификатор выработки в которой произошло событие
     * @param $value_status_id -   идентификатор статуса значения параметра
     * @return array
     */
    private static function isChangeEvent($mine_id, $event_id, $main_id,
                                          $date_time, $edge_id,
                                          $value_status_id)
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        $warnings[] = 'isChangeEvent. Начало метода';

        $flag_save = 0;

        try {
            /**
             * Получение события из кэша
             */
            $event = false;
            $response = (new EventCacheController())->getEvent($mine_id, $event_id, $main_id);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $warnings[] = 'isChangeEvent. Значение найдено в кэше';
                $errors[] = $response['errors'];
                $event = $response['Items'];
            } else {
                $warnings[] = $response['warnings'];
                $warnings[] = 'isChangeEvent. Значение не найдено в кэше, флаг записи = 1';
                $flag_save = 1;
            }

            /**
             * Если событие найдено в кеше, то проверяем, изменилось ли оно
             */
            if ($event) {
                // Проверка на изменение места, в котором произошло событие
                if ($event['edge_id'] != $edge_id) {
                    $warnings[] = 'isChangeEvent. Место события изменилось с ' . $event['edge_id'] . ' на ' . $edge_id;
                    $warnings[] = 'isChangeEvent. Флаг записи = 1';
                    $flag_save = 1;
                } else {
                    $warnings[] = 'isChangeEvent. Место события не изменилось';
                }

                // Проверка на изменение статуса значения события
                if ($event['value_status_id'] != $value_status_id) {
                    $warnings[] = 'isChangeEvent. Статус события изменился, флаг записи = 1';
                    $flag_save = 1;
                } elseif ($value_status_id == 44/*StatusEnumController::EMERGENCY_VALUE*/) {
                    // Значение события повторно пришло с аварийным статусом
                    // Вычисляем разницу во времени между значениями. Если
                    // разница больше 6 часов - генерируем событие
                    $warnings[] = 'isChangeEvent. Событие повторно пришло с аварийным статусом';
                    $delta_time = strtotime($date_time) - strtotime($event['date_time']);
                    if ($delta_time > 21600 /*6 часов*/) {
                        $warnings[] = 'isChangeEvent. Разница во времени больше 6 часов, флаг записи = 1';
                        $flag_save = 1;
                    } else {
                        $warnings[] = 'isChangeEvent. Разница во времени меньше 6 часов';
                    }
                } elseif ($value_status_id == 45/*StatusEnumController::NORMAL_VALUE*/) {
                    // Событие повторно пришло с нормальным значением
                    $warnings[] = 'isChangeEvent. Событие повторно пришло с нормальным значением, флаг записи = 0';
                    $flag_save = 0;
                }

            } else {
                $warnings[] = 'isChangeEvent. Событие в кэше не найдено, флаг записи = 1';
                $flag_save = 1;
            }

        } catch (Throwable $exception) {
            $errors[] = 'isChangeEvent. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $warnings[] = 'isChangeEvent. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings,
            'flag_save' => $flag_save);
        return $result_main;
    }

    /**
     * isChangeEventDay - Проверяет изменилось ли событие - загрубление один день.
     * Проверка выполняется по значению параметра события, по месту где оно
     * произошло и по изменению статуса события.
     * Если событие не найдено в кэше, предполагается, что оно изменилось
     * Убрана проверка на изменение горной выработки
     * @param $mine_id -   идентификатор шахты
     * @param $event_id -   идентификатор события
     * @param $main_id -   идентификатор объекта с которым связано событие
     * @param $date_time -   дата и время события
     * @param $edge_id -   идентификатор выработки в которой произошло событие
     * @param $value_status_id -   идентификатор статуса значения параметра
     * @return array
     */
    private static function isChangeEventDay($mine_id, $event_id, $main_id,
                                             $date_time, $edge_id,
                                             $value_status_id)
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        $warnings[] = 'isChangeEventDay. Начало метода';

        $flag_save = 0;

        try {
            /**
             * Получение события из кэша
             */
            $event = false;
            $response = (new EventCacheController())->getEvent($mine_id, $event_id, $main_id);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $warnings[] = 'isChangeEventDay. Значение найдено в кэше';
                $errors[] = $response['errors'];
                $event = $response['Items'];
            } else {
                $warnings[] = $response['warnings'];
                $warnings[] = 'isChangeEventDay. Значение не найдено в кэше, флаг записи = 1';
                $flag_save = 1;
            }

            /**
             * Если событие найдено в кеше, то проверяем, изменилось ли оно
             */
            $event_journal_id = -1;
            if ($event) {
                $event_journal_id = $event['event_journal_id'];

                // Проверка на изменение статуса значения события
                if ($event['value_status_id'] != $value_status_id) {
                    $warnings[] = 'isChangeEventDay. Статус события изменился, флаг записи = 1';
                    $flag_save = 1;
                } elseif ($value_status_id == 44/*StatusEnumController::EMERGENCY_VALUE*/) {
                    // Значение события повторно пришло с аварийным статусом
                    // Вычисляем разницу во времени между значениями. Если
                    // разница больше 6 часов - генерируем событие
                    $warnings[] = 'isChangeEventDay. Событие повторно пришло с аварийным статусом';
                    $delta_time = strtotime($date_time) - strtotime($event['date_time']);
                    if ($delta_time > 86400 /*24 часа*/) {
                        $warnings[] = 'isChangeEventDay. Разница во времени больше 24 часов, флаг записи = 1';
                        $flag_save = 1;
                    } else {
                        $warnings[] = 'isChangeEventDay. Разница во времени меньше 24 часов';
                    }
                } elseif ($value_status_id == 45/*StatusEnumController::NORMAL_VALUE*/) {
                    // Событие повторно пришло с нормальным значением
                    $warnings[] = 'isChangeEventDay. Событие повторно пришло с нормальным значением, флаг записи = 0';
                    $flag_save = 0;
                }

            } else {
                $warnings[] = 'isChangeEventDay. Событие в кэше не найдено, флаг записи = 1';
                $flag_save = 1;
            }

        } catch (Throwable $exception) {
            $errors[] = 'isChangeEventDay. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $warnings[] = 'isChangeEventDay. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings,
            'flag_save' => $flag_save, 'event_journal_id' => $event_journal_id);
        return $result_main;
    }


    /**
     * Обработка события сравнения 2 газов.
     * По идее костыль. Нужен только для генерации специфичного сообщения для
     * рассылки и устранения дублирования сообщений по данному событию
     *
     * @warning Не использовать, если не понимаешь зачем это надо!
     *
     * @param int $static_sensor_id идентификатор стационарного датчика
     * @param int $lamp_id идентификатор луча
     * @param int $event_id идентификатор события
     * @param float $static_sensor_value значение газа стационарного датчика
     * @param float $lamp_value значение газа луча
     * @param string $date_time дата и время события
     * @param int $value_status_id идентификатор статуса значения
     * @param int $parameter_id идентификатор параметра
     * @param int $mine_id идентификатор шахты
     * @param int $event_status_id идентификатор статуса события
     * @param int $static_edge_id идентификатор выработки стационарного датчика
     * @param int $lamp_edge_id идентификатор выработки луча
     * @param int $static_xyz координата стационарного сенсора
     * @param int $lamp_CH4_xyz координата лампы
     * @return array
     */
    public static function createCompareEvent($static_sensor_id, $lamp_id, $event_id,
                                              $static_sensor_value, $lamp_value,
                                              $date_time, $value_status_id, $parameter_id,
                                              $mine_id, $event_status_id, $static_edge_id = -1, $lamp_edge_id = -1,
                                              $static_xyz = -1, $lamp_xyz = -1
    )
    {
        $microtime_start = microtime(true);
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        $event_journal_id = null;
        $alarm_group_id = null;
        $warnings[] = __FUNCTION__ . '. Начало метода';
        $warnings['argv'] = array(
            '$static_sensor_id' => $static_sensor_id,
            '$lamp_id' => $lamp_id,
            '$event_id' => $event_id,
            '$static_sensor_value' => $static_sensor_value,
            '$lamp_value' => $lamp_value,
            '$date_time' => $date_time,
            '$value_status_id' => $value_status_id,
            '$parameter_id' => $parameter_id,
            '$mine_id' => $mine_id,
            '$event_status_id' => $event_status_id,
            '$static_edge_id' => $static_edge_id,
            '$lamp_edge_id' => $lamp_edge_id,
        );

        try {
            /**=================================================================
             * Получение дополнительных параметров, нужных для записи в таблицу
             * для стационарного датчика
             * =================================================================
             **/
            $sensor_cache_controller = (new SensorCacheController());
            $static_sensor = $sensor_cache_controller->getSensorMineBySensorOneHash($mine_id, $static_sensor_id);
            if ($static_sensor === false)
                throw new Exception(__FUNCTION__ . ". Сенсор $static_sensor_id не найден в кэше");

            $parameter_type_id = SensorCacheController::isStaticSensor($static_sensor['object_type_id']);

            // !!! Если значения не будет в кэше, метод дальше не пойдет
            // выработка стационарного сенсора
            if ($static_edge_id == -1) {
                $static_edge_id = $sensor_cache_controller->getParameterValueHash($static_sensor_id, 269/*ParameterEnumController::EDGE_ID*/, $parameter_type_id);
                if ($static_edge_id === false) {
                    throw new Exception(__FUNCTION__ . '. Значение выработки не найдено в кэше');
                }
                $static_edge_id = $static_edge_id['value'];
            }

            // координата стационарного сенсора
            if ($static_xyz === -1) {
                $static_xyz = $sensor_cache_controller->getParameterValueHash($static_sensor_id, 83/*ParameterEnumController::COORD*/, $parameter_type_id);
                if ($static_xyz === false) {
                    throw new Exception(__FUNCTION__ . '. Значение координат не найдено в кэше');
                }
                $static_xyz = $static_xyz['value'];
            }
            // группа оповещения
            $alarm_group_id = $sensor_cache_controller->getParameterValueHash($static_sensor_id, 18/*ParameterEnumController::PREDPRIYATIE*/, 1/*ParameterTypeEnumController::REFERENCE*/);
            if ($alarm_group_id === false) {
                $alarm_group_id = null;
            } else {
                $alarm_group_id = $alarm_group_id['value'];
            }

            $static_object_id = $static_sensor['object_id'];
            $static_object_title = $static_sensor['sensor_title'];

            /**=================================================================
             * Проверка на то, что событие изменилось
             * Для стационарного датчика
             * =================================================================*/
            $response = self::isChangeEventDay($mine_id, $event_id, $static_sensor_id,
                $date_time, $static_edge_id, $value_status_id);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $flag_save = $response['flag_save'];
                $static_event_journal_id = $response['event_journal_id'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception(__FUNCTION__ . '. Ошибка при проверке изменения события');
            }

            if ($flag_save) {
                /**
                 * Создание записи в таблице event_journal
                 * Для стационарного датчика
                 */
                $response = EventBasicController::createEventJournalEntry(
                    $event_id,
                    $static_sensor_id,
                    $static_edge_id,
                    $static_sensor_value,
                    $date_time,
                    $static_xyz,
                    $value_status_id,
                    $parameter_id,
                    $static_object_id,
                    $mine_id,
                    $static_object_title,
                    'sensor',
                    $event_status_id,
                    $alarm_group_id
                );
                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    $static_event_journal_id = $response['event_journal_id'];
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception(__FUNCTION__ . '. Ошибка сохранения в event_journal');
                }

                /**
                 * Создание записи в таблице event_status - используется для отслеживания действия диспетчера.
                 * Для стационарного датчика
                 */
                $response = EventBasicController::createEventStatusEntry(
                    $static_event_journal_id,
                    $event_status_id,
                    $date_time
                );
                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception(__FUNCTION__ . '. Ошибка сохранения в event_status');
                }

                /**
                 * Сохранение события в кэш
                 * Для стационарного датчика
                 */
                $response = (new EventCacheController())->setEvent(
                    $mine_id,
                    $event_id,
                    $static_sensor_id,
                    $event_status_id,
                    $static_edge_id,
                    $static_sensor_value,
                    $value_status_id,
                    $date_time,
                    $static_xyz,
                    $parameter_id,
                    $static_object_id,
                    $static_object_title,
                    'sensor',
                    $static_event_journal_id,
                    $alarm_group_id
                );
                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception(__FUNCTION__ . '. Ошибка сохранения события в кэш');
                }
            }
            unset($flag_save);

            /**=================================================================
             * Получение дополнительных параметров, нужных для записи в таблицу
             * для луча
             * =================================================================*/
            $lamp_sensor = $sensor_cache_controller->getSensorMineBySensorOneHash($mine_id, $lamp_id);
            if ($lamp_sensor === false)
                throw new Exception(__FUNCTION__ . ". Сенсор $lamp_id не найден в кэше");

            $parameter_type_id = SensorCacheController::isStaticSensor($lamp_sensor['object_type_id']);

            // !!! Если значения не будет в кэше, метод дальше не пойдет
            // получение информации по выработке лампы
            if ($lamp_edge_id == -1) {
                $lamp_edge_id = $sensor_cache_controller->getParameterValueHash($lamp_id, 269/*ParameterEnumController::EDGE_ID*/, $parameter_type_id);
                if ($lamp_edge_id === false) {
                    throw new Exception(__FUNCTION__ . '. Значение выработки не найдено в кэше');
                }
                $lamp_edge_id = $lamp_edge_id['value'];
            }

            // получение информации по координате лампы
            if ($lamp_xyz === -1) {
                $lamp_xyz = $sensor_cache_controller->getParameterValueHash($lamp_id, 83/*ParameterEnumController::COORD*/, $parameter_type_id);
                if ($lamp_xyz === false) {
                    throw new Exception(__FUNCTION__ . '. Значение координат не найдено в кэше');
                }
                $lamp_xyz = $lamp_xyz['value'];
            }

            $lamp_object_id = $lamp_sensor['object_id'];
            $lamp_object_title = $lamp_sensor['sensor_title'];

            /**=================================================================
             * Проверка на то, что событие изменилось
             * Для луча
             * =================================================================*/
            $response = self::isChangeEventDay($mine_id, $event_id, $lamp_id,
                $date_time, $lamp_edge_id, $value_status_id);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $flag_save = $response['flag_save'];
                $lamp_event_journal_id = $response['event_journal_id'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception(__FUNCTION__ . '. Ошибка при проверке изменения события луча');
            }

            if ($flag_save) {
                /**
                 * Создание записи в таблице event_journal
                 * Для луча
                 */
                $warnings['event_journal'] = "Создание записи в таблице event_journal";
                $response = EventBasicController::createEventJournalEntry(
                    $event_id,
                    $lamp_id,
                    $lamp_edge_id,
                    $lamp_value,
                    $date_time,
                    $lamp_xyz,
                    $value_status_id,
                    $parameter_id,
                    $lamp_object_id,
                    $mine_id,
                    $lamp_object_title,
                    'sensor',
                    $event_status_id,
                    $alarm_group_id
                );
                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    $lamp_event_journal_id = $response['event_journal_id'];
                    $warnings['Полученный event_journal_id'] = $lamp_event_journal_id;
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception(__FUNCTION__ . '. Ошибка сохранения в event_journal для луча');
                }

                /**
                 * Создание записи в таблице event_status - используется для отслеживания действия диспетчера.
                 * Для луча
                 */
                $response = EventBasicController::createEventStatusEntry(
                    $lamp_event_journal_id,
                    $event_status_id,
                    $date_time
                );
                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception(__FUNCTION__ . '. Ошибка сохранения в event_status для луча');
                }

                /**
                 * Сохранение события в кэш
                 * Для луча
                 */
                $response = (new EventCacheController())->setEvent(
                    $mine_id,
                    $event_id,
                    $lamp_id,
                    $event_status_id,
                    $lamp_edge_id,
                    $lamp_value,
                    $value_status_id,
                    $date_time,
                    $lamp_xyz,
                    $parameter_id,
                    $lamp_object_id,
                    $lamp_object_title,
                    'sensor',
                    $lamp_event_journal_id,
                    $alarm_group_id
                );
                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception(__FUNCTION__ . '. Ошибка сохранения события в кэш для луча');
                }

                /**
                 * Рассылка
                 */
                if ($value_status_id == 44/*StatusEnumController::EMERGENCY_VALUE*/) {
                    $event_title = (new Query())
                        ->select('title')
                        ->from('event')
                        ->where(['id' => $event_id])
                        ->limit(1)
                        ->scalar();
                    if (!$event_title) {
                        throw new Exception(__FUNCTION__ . '. Не найдена запись из таблицы event c id = ' . $event_id);
                    }

                    $message = $event_title;
                    $message .= ". Объекты: $static_object_title [$static_sensor_value] ";
                    $message .= "vs $lamp_object_title [$lamp_value]";
                    $message .= '. Дата: ' . explode('.', $date_time)[0];

                    $place_title = (new Query())
                        ->select('title')
                        ->from('place')
                        ->innerJoin('edge', 'edge.place_id = place.id')
                        ->where(['edge.id' => $lamp_edge_id])
                        ->limit(1)
                        ->scalar();
                    if ($place_title) {
                        $message .= ". Место: $place_title";
                    }
                    $warnings['message'] = $message;
                    /**
                     * Email
                     */
                    $addresses = XmlController::getEmailSendingList($event_id, $alarm_group_id);
                    if ($addresses) {
                        $response = XmlController::SendSafetyEmail($message, $addresses);
                        if ($response['status'] == 1) {
                            $warnings[] = $response['warnings'];
                        } else {
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                        }
                    } else {
                        $warnings[] = __FUNCTION__ . '. Нет актуальных email адресов для рассылки';
                    }

                    /**
                     * СМС
                     */
                    $numbers = XmlController::getSmsSendingList($event_id, $alarm_group_id);
                    if ($numbers) {
                        $response = SmsSender::actionSendSmsProxy($message, $numbers);
                        if ($response['status'] == 1) {
                            $warnings[] = $response['warnings'];
                        } else {
                            $warnings[] = $response['warnings'];
                            $errors[] = $response['errors'];
                        }
                    } else {
                        $warnings[] = __FUNCTION__ . '. Нет актуальных номеров для СМС рассылки';
                    }
                }
            }
            // сохраняем расхождения газов в отдельную таблицу event_compare_gas БД
            $response = self::writeEventCompareGas(
                $event_id,
                $date_time,
                $static_edge_id,
                $static_sensor_value,
                $static_sensor_id,
                $static_xyz,
                $value_status_id,
                $parameter_id,
                $static_object_id,
                $mine_id,
                $static_object_title,
                'sensor',
                $static_event_journal_id,
                $lamp_id,
                $lamp_edge_id,
                $lamp_value,
                $lamp_xyz,
                $value_status_id,
                $parameter_id,
                $lamp_object_id,
                $mine_id,
                $lamp_object_title,
                'sensor',
                $lamp_event_journal_id
            );
            // $test_string_to_log = "Было вызвано метод writeEventCompareGas".
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception(__FUNCTION__ . '. Ошибка сохранения сводной записи в БД по сравнению двух газов');
            }
        } catch (Throwable $exception) {
            $errors[] = __FUNCTION__ . '. Исключение при генерации события ' . $event_id;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
            $data_to_cache_log = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
            LogCacheController::setEventLogValue('createCompareEvent', $data_to_cache_log, '2');
        }

        $warnings[] = __FUNCTION__ . '. Конец метода';
        $duration_method = round(microtime(true) - $microtime_start, 6);
        $warnings[] = __FUNCTION__ . '. Время выполнения метода ' . $duration_method;
        $result_main = array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // writeEventCompareGas - метод сохранения события по газам стационарным и индивидуальным в БД
    // метод получает данные по стационарному и индивидуальному датчику:
    // event_id                 - событие
    // date_time                - дата время события
    // static_edge_id           - ветвь стационарного датчика
    // static_value             - значение стационарного датчика
    // static_sensor_id         - ключ стационарного сенсора
    // static_xyz               - координата стационарного датчика
    // static_status_id         - статус стационарного датчика
    // static_parameter_id      - параметр стационарного датчика
    // static_object_id         - ключ типового объекта стационарного датчика
    // static_mine_id           - ключ шахты стационарного датчика
    // static_object_title      - название стационарного датчика
    // static_object_table      - название таблицы стационарного датчика
    // static_event_journal_id  - ключ события из журнала событий по стационарному датчику
    // lamp_sensor_id           - ключ лампы
    // lamp_edge_id             - ключ ветви лампы
    // lamp_value               - значение лампы
    // lamp_xyz                 - значение координаты лампы
    // lamp_status_id           - статус лампы (было или нет превышение)
    // lamp_parameter_id        - парамтер лампы
    // lamp_object_id           - ключ типового объекта лампы
    // lamp_mine_id             - ключ шахты лампы
    // lamp_object_title        - название лампы
    // lamp_object_table        - название таблицы лампы
    // lamp_event_journal_id    - ключ из журнала событий по лампе
    // разработал: Якимов М.Н.
    public static function writeEventCompareGas(
        $event_id, $date_time, $static_edge_id, $static_value,
        $static_sensor_id, $static_xyz, $static_status_id, $static_parameter_id,
        $static_object_id, $static_mine_id, $static_object_title, $static_object_table,
        $static_event_journal_id, $lamp_sensor_id, $lamp_edge_id, $lamp_value,
        $lamp_xyz, $lamp_status_id, $lamp_parameter_id, $lamp_object_id,
        $lamp_mine_id, $lamp_object_title, $lamp_object_table, $lamp_event_journal_id
    )
    {
        $warnings = array();
        $result = array();
        $errors = array();
        $status = 1;
        $warnings[] = 'writeEventCompareGas. Начало метода';
        try {
            $event_compare = new EventCompareGas();
            $event_compare->event_id = $event_id;
            $event_compare->date_time = $date_time;
            $event_compare->static_edge_id = intval($static_edge_id);
            $event_compare->static_value = (string)($static_value);
            $event_compare->static_sensor_id = intval($static_sensor_id);
            $event_compare->static_xyz = $static_xyz;
            $event_compare->static_status_id = $static_status_id;
            $event_compare->static_parameter_id = $static_parameter_id;
            $event_compare->static_object_id = $static_object_id;
            $event_compare->static_mine_id = intval($static_mine_id);
            $event_compare->static_object_title = $static_object_title;
            $event_compare->static_object_table = $static_object_table;
            $event_compare->lamp_sensor_id = intval($lamp_sensor_id);
            $event_compare->lamp_edge_id = intval($lamp_edge_id);
            $event_compare->lamp_value = $lamp_value;
            $event_compare->lamp_xyz = $lamp_xyz;
            $event_compare->lamp_status_id = $lamp_status_id;
            $event_compare->lamp_parameter_id = $lamp_parameter_id;
            $event_compare->lamp_object_id = intval($lamp_object_id);
            $event_compare->lamp_mine_id = intval($lamp_mine_id);
            $event_compare->lamp_object_title = $lamp_object_title;
            $event_compare->lamp_object_table = $lamp_object_table;
            $event_compare->static_event_journal_id = intval($static_event_journal_id);
            $event_compare->lamp_event_journal_id = intval($lamp_event_journal_id);
            if ($event_compare->save()) {
                $warnings[] = 'createEventStatusEntry. Событие добавлено в таблицу EventCompareGas';
            } else {
                $errors[] = $event_compare->errors;
                throw new Exception('writeEventCompareGas. Ошибка при сохранении в таблицу EventCompareGas');
            }
        } catch (Throwable $exception) {
            $errors[] = "writeEventCompareGas. Исключение:";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
            $data_to_cache_log = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
            LogCacheController::setEventLogValue('writeEventCompareGas', $data_to_cache_log, '2');
        }
        $warnings[] = 'writeEventCompareGas. Конец метода';

        $result_main = array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetObjectTitle - метод получения названия объекта по типу
    // входные параметры:
    //      $object_table   -   тип объекта (sensor, worker)
    //      $mine_id        -   ключ шахты в которой ищем объект
    //      $main_id        -   ключ объекта который ищем
    // выходные параметры:
    //      $object_id      -   ключ объекта
    //      $object_title   -   наименование объекта
    // разработал: Якимов М.Н.
    // дата 09.02.2020
    public static function GetObjectTitle($object_table, $mine_id, $main_id)
    {
// Стартовая отладочная информация
        $method_name = 'GetObjectTitle';                                                                             // название логируемого метода
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
        $object_id = null;                                                                                              // ключ объекта
        $object_title = null;                                                                                           // наименование объекта
        $group_alarm_id = null;                                                                                         // ключ группы оповещения
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
            if ($object_table == 'worker') {
                $worker = (new WorkerCacheController())->getWorkerMineByWorkerOneHash($mine_id, $main_id);
                if ($worker === false) {
                    $worker = (new Query())
                        ->select([
                            'employee.last_name as last_name',
                            'employee.first_name as first_name',
                            'employee.patronymic as patronymic'
                        ])
                        ->from('employee')
                        ->where([
                            'id' => $main_id
                        ])
                        ->limit(1)
                        ->one();
                    if ($worker === false) {
                        throw new Exception($method_name . ". Воркер $main_id не найден в БД");
                    }
                    $worker['full_name'] = $worker['last_name'] . ' ' . $worker['first_name'] . ' ' . $worker['patronymic'];
                    $worker['object_id'] = 25;
                }

                $object_id = $worker['object_id'];
                $object_title = $worker['full_name'];

                // получаем привязку людей к шахте - нужно для определения на какую шахту слать (заполярную или воркутинскую)
                //в 18 параметре 1 - Заполярная, 2 - Воркутинская -  данная часть хранится в справочнике group_alarm
                $alarm_group_id = (new WorkerCacheController())->getParameterValueHash($main_id, 18/*принадлежность к компании*/, 1/*ParameterTypeEnumController::REFERENCE*/);
                if ($alarm_group_id === false) {
                    $group_alarm_id = null;
                } else {
                    $group_alarm_id = $alarm_group_id['value'];
                }

            } else if ($object_table == 'sensor') {
                $warnings[] = $method_name . '. Объект из таблицы sensor';
                $sensor = (new SensorCacheController())->getSensorMineBySensorOneHash($mine_id, $main_id);
                if ($sensor === false) {
                    throw new Exception($method_name . ". Сенсор $main_id не найден в кэше");
                }
                $object_id = $sensor['object_id'];
                $object_title = $sensor['sensor_title'];

                // получаем привязку людей к шахте - нужно для определения на какую шахту слать (заполярную или воркутинскую)
                //в 18 параметре 1 - Заполярная, 2 - Воркутинская -  данная часть хранится в справочнике group_alarm
                $alarm_group_id = (new SensorCacheController())->getParameterValueHash($main_id, 18/*принадлежность к компании*/, 1/*ParameterTypeEnumController::REFERENCE*/);
                if ($alarm_group_id === false) {
                    $group_alarm_id = null;
                } else {
                    $group_alarm_id = $alarm_group_id['value'];
                }
            } else {
                throw new Exception($method_name . ". Неизвестный object_table = $object_table");
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
//        $date_time_debug_end = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время окончания выполнения метода
//        LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
//            $date_time_debug_start, $date_time_debug_end, $log_id,
//            $duration_summary, $max_memory_peak, $count_all);

        $result_main = array(
            'object_id' => $object_id,
            'object_title' => $object_title,
            'group_alarm_id' => $group_alarm_id,
            'Items' => $result,
            'errors' => $errors,
            'status' => $status,
            'warnings' => $warnings,
            'debug' => $debug);
        return $result_main;
    }

    // CronSituation - планировщик оповещения персонала о ситуациях
    // входные параметры:
    // выходные параметры:
    // алгоритм:
    //  1. Получить по шахте по умолчанию текущий список ситуаций из кеша
    //  2. перебор всех ситуаций
    //      2.1. определить текущий номер рассылки через 30 минут, через 1,5 часа, через 6 часов. и увеличить его на 1.
    //           текущий уровень определяется по времени от начала кратно 30 минутам.
    //      2.2. определить в какой группе находится  ситуацию для рассылки. Для этого
    //      2.3. Получить список событий в ситуации и из них - группу оповещения
    //      2.4. получить список людей для рассылки по текущему номеру без учета группы шахты
    //      2.5. если в списке есть люди с искомой группой оповещения - сделать рассылку
    //      2.6. если для рассылки есть люди, то начинаем готовить данные:
    //      2.7. Получить недостающие сведения о ситуации из БД,
    //      2.8. сформировать сообщение
    //      2.9. Осуществить рассылку
    //    ВАЖНО!!!
    //          Если есть ситуации, то должны быть и события, иначе будет ошибка.
    //          Ошибка возможна в событиях, т.к. данные получаются из кеша событий, и если его отчистили, то там пусто, т.к. он не инициализируется автоматически!!!!
    // разработал: Якимов М.Н.
    // дата 09.02.2020
    // 127.0.0.1/synchronization-front/cron-situation?mine_id=270&mine_title='ш.Заполярная-2'
    public static function CronSituation($mine_id = AMICUM_DEFAULT_MINE, $mine_title = AMICUM_DEFAULT_MINE_TITLE)
    {
        $log = new LogAmicumFront("CronSituation");

        // базовые входные параметры скрипта
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $object_title = null;
        $object_id = null;

        try {
            $log->addLog("Начало выполнения метода");

            // СТРУКТУРА:
            // СИТУАЦИЯ($situation_list) -> СПИОК СОБЫТИЙ СИТУАЦИИ ($event_situation_list) -> СОБЫТИЯ ($event_list)
            //  1. Получить по шахте по умолчанию текущий список ситуаций из кеша
            $situation_cache = (new SituationCacheController());
            $response = $situation_cache->getSituationList($mine_id);
            if ($response['status'] != 1) {
                $log->addLogAll($response);
                throw new Exception('Ошибка получения списка ситуаций из кеша или Список ситуаций пуст');
            }
            $situation_list = $response['Items'];

            // получаем список всех событий ситуаций
            $response = $situation_cache->getEventSituationList($mine_id);
            if ($response['status'] != 1) {
                $log->addLogAll($response);
                throw new Exception('Ошибка получения списка событий ситуаций из кеша или Список событий ситуаций пуст');
            }
            $event_situation_list = $response['Items'];

            foreach ($event_situation_list as $event_situation) {
                $event_situation_handbook[$event_situation['situation_journal_id']][$event_situation['event_journal_id']] = $event_situation;
            }
            unset($event_situation_list);

            // получаем список статусов отправки сообщений ситуации
            $response = $situation_cache->getSendStatusList($mine_id);
            if ($response['status'] != 1) {
                $log->addLogAll($response);
                throw new Exception('Ошибка получения списка статусов отправки сообщений ситуаций из кеша или Список событий ситуаций пуст');
            }
            $send_status_list = $response['Items'];

            foreach ($send_status_list as $send_status) {
                $send_status_handbook[$send_status['situation_journal_id']] = $send_status;
            }
            unset($send_status_list);

            // получаем список всех событий
            $event_cache = (new EventCacheController());
            $response = $event_cache->getEventsList($mine_id);
            if ($response['status'] != 1) {
                $log->addLogAll($response);
                throw new Exception('Ошибка получения списка событий ситуаций из кеша или Список событий ситуаций пуст');
            }
            $event_journal_list = $response['Items'];

            if ($event_journal_list) {
                foreach ($event_journal_list as $event_journal) {
                    $event_journal_handbook[$event_journal['event_journal_id']] = $event_journal;
                }
                unset($event_journal_list);

                $date_time_now = strtotime(Assistant::GetDateTimeNow());                                                    // текущая дата и время

                //  2. перебор всех ситуаций
                foreach ($situation_list as $situation) {
                    // проверяем статус ситуации (снята/в работе) - выполняем метод для актуальных ситуаций
                    //  31	Ситуация устраняется
                    //  32	Ситуация переквалифицирована системой
                    //  33	Ситуация устранена
                    //  34	Неподтвержденная ситуация
                    if ($situation['situation_status_id'] == 31 or $situation['situation_status_id'] == 34) {

                        $date_situation = strtotime($situation['date_time_start']);
                        //      2.1. определить текущий номер рассылки через 30 минут, через 1,5 часа, через 6 часов. и увеличить его на 1.
                        //           текущий уровень определяется по времени от начала кратно 30 минутам.
                        if (
                            isset($send_status_handbook[$situation['situation_journal_id']]) and
                            floor(($date_time_now - strtotime($send_status_handbook[$situation['situation_journal_id']]['date_time'])) / 1800) < 1
                        ) {
                            $flag_send = 0;
                        } else {
                            $flag_send = 1;
                        }
                        if ($flag_send) {
                            $position = floor(($date_time_now - $date_situation) / 1800);

                            //      2.2. определить в какой группе находится  ситуацию для рассылки. Для этого
                            //      2.3. Получить список событий в ситуации и из них - группу оповещения

                            if (isset($event_situation_handbook[$situation['situation_journal_id']])) {

                                foreach ($event_situation_handbook[$situation['situation_journal_id']] as $event_situation) {
                                    if (isset($event_journal_handbook[$event_situation['event_journal_id']])) {
                                        $group_alarms[$event_journal_handbook[$event_situation['event_journal_id']]['group_alarm_id']] = $event_journal_handbook[$event_situation['event_journal_id']]['group_alarm_id'];
                                        $events[$event_journal_handbook[$event_situation['event_journal_id']]['event_id']] = $event_journal_handbook[$event_situation['event_journal_id']]['event_id'];
                                        $event_journals[$event_situation['event_journal_id']] = $event_situation['event_journal_id'];
                                    }
                                }
                                if (isset($events)) {

                                    foreach ($events as $event) {
                                        $event_ids[] = $event;
                                    }
                                    unset($events);

                                    foreach ($group_alarms as $group_alarm) {
                                        $group_alarm_ids[] = $group_alarm;
                                    }
                                    unset($group_alarms);

                                    foreach ($event_journals as $event_journal) {
                                        $event_journal_ids[] = $event_journal;
                                    }
                                    unset($group_alarms);


                                    //      2.4. получить список людей для рассылки по текущему номеру (position) группе оповещения и списку событий
                                    /**
                                     * Email
                                     */
                                    $addresses = XmlController::getEmailRepeatSendingList($event_ids, $group_alarm_ids, $position);
                                    //      2.5. если в списке есть люди с искомой группой оповещения - сделать рассылку
                                    if ($addresses) {
                                        $message = "";

                                        //      2.6. если для рассылки есть люди, то начинаем готовить данные:
                                        //      2.7. Получить недостающие сведения о ситуации из БД,
                                        $event_journals_detail = (new Query())
                                            ->select('
                                        place.title as place_title,
                                        event_journal.date_time as date_time,
                                        event_journal.object_title as object_title,
                                        event_journal.value as value,
                                        unit.short as unit_title,
                                        event.title as event_title,
                                        group_alarm.title as group_alarm_title
                                    ')
                                            ->from('event_journal')
                                            ->innerJoin('edge', 'edge.id=event_journal.edge_id')
                                            ->innerJoin('place', 'place.id=edge.place_id')
                                            ->innerJoin('parameter', 'parameter.id=event_journal.parameter_id')
                                            ->innerJoin('unit', 'unit.id=parameter.unit_id')
                                            ->innerJoin('event', 'event.id=event_journal.event_id')
                                            ->leftJoin('group_alarm', 'group_alarm.id=event_journal.group_alarm_id')
                                            ->where(['event_journal.id' => $event_journal_ids])
                                            ->all();
                                        if ($event_journals_detail) {
                                            //      2.8. сформировать сообщение
                                            foreach ($event_journals_detail as $event_journal) {
                                                $message .= " { ";
                                                $message .= " -ВРЕМЯ: ";
                                                $message .= date("d.m.Y H:i:s", strtotime($event_journal['date_time']));
                                                $message .= " | ";
                                                $message .= " -СОБЫТИЕ: ";
                                                $message .= $event_journal['event_title'];
                                                $message .= " | ";
                                                $message .= " -ШАХТА: ";
                                                $message .= $mine_title;
                                                $message .= " | ";
                                                $message .= " -БЛОК: ";
                                                $message .= $event_journal['group_alarm_title'];
                                                $message .= " | ";
                                                $message .= " -МЕСТО: ";
                                                $message .= $event_journal['place_title'];
                                                $message .= " | ";
                                                $message .= " -ОБЪЕКТ: ";
                                                $message .= $event_journal['object_title'];
                                                $message .= " | ";
                                                $message .= " -ЗНАЧЕНИЕ: ";
                                                $message .= $event_journal['value'] . $event_journal['unit_title'];
                                                $message .= " } ";
                                            }
//                                        $warnings[] = $message;
                                            $date_now = Assistant::GetDateNow();
                                            if (!isset($send_status_handbook[$situation['situation_journal_id']])) {
                                                $response = SituationBasicController::createSendStatus($situation['situation_journal_id'], 1, 104, $date_now);
                                                if ($response['status'] != 1) {
                                                    $log->addLogAll($response);
                                                    throw new Exception('Ошибка сохранения статуса начала отправки сообщений ситуации в БД');
                                                }
                                                $situation_journal_send_status_id = $response['situation_journal_send_status_id'];
                                                $response = $situation_cache->setSendStatus($mine_id, $situation['situation_journal_id'], 1, $date_now, 104, $situation_journal_send_status_id);
                                                if ($response['status'] != 1) {
                                                    $log->addLogAll($response);
                                                    throw new Exception('Ошибка сохранения статуса начала отправки сообщений ситуации В КЕШ');
                                                }
                                            } else {
                                                $situation_journal_send_status_id = $send_status_handbook[$situation['situation_journal_id']]['situation_journal_send_status_id'];
                                                $response = SituationBasicController::updateSendStatus($situation_journal_send_status_id, 104, $date_now);
                                                if ($response['status'] != 1) {
                                                    $log->addLogAll($response);
                                                    throw new Exception('Ошибка обновления статуса начала отправки сообщений ситуации');
                                                }
                                                $response = $situation_cache->setSendStatus($mine_id, $situation['situation_journal_id'], 1, $date_now, 104, $situation_journal_send_status_id);
                                                if ($response['status'] != 1) {
                                                    $log->addLogAll($response);
                                                    throw new Exception('Ошибка сохранения статуса начала отправки сообщений ситуации В КЕШ');
                                                }
                                            }

                                            //      2.9. Осуществить рассылку
                                            $response = XmlController::SendSafetyEmail($message, $addresses);
                                            $log->addLogAll($response);
                                            if ($response['status'] == 1) {
                                                $date_now = Assistant::GetDateNow();
                                                $response = SituationBasicController::updateSendStatus($situation_journal_send_status_id, 105, $date_now);
                                                if ($response['status'] != 1) {
                                                    $log->addLogAll($response);
                                                    throw new Exception('Ошибка обновления статуса начала отправки сообщений ситуации');
                                                }
                                                $response = $situation_cache->setSendStatus($mine_id, $situation['situation_journal_id'], 1, $date_now, 105, $situation_journal_send_status_id);
                                                if ($response['status'] != 1) {
                                                    $log->addLogAll($response);
                                                    throw new Exception('Ошибка сохранения статуса начала отправки сообщений ситуации В КЕШ');
                                                }

                                            }
                                        }
                                        // 2.10. сделать новую отметку об отправке
                                    }

                                    unset($group_alarms);
                                    unset($events);
                                }
                            }
                        }
                    }
                }


//            if ($new_alarm == 1) {
//                /**
//                 * СМС
//                 */
//                $numbers = XmlController::getSmsSendingList($event_id, $alarm_group_id);
//                if ($numbers) {
//                    $response = SmsSender::actionSendSmsProxy($message, $numbers);
//                    $log->addLogAll($response)
//                }
//            }
            }
            /** Метод окончание */
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result, 'object_id' => $object_id, 'object_title' => $object_title], $log->getLogAll());
    }


    /**
     * Метод генерации события/ситуации отказ службы сбора данных АГК
     * @example http://127.0.0.1/admin/read-manager-amicum?controller=EventMain&method=CreateEventStopOPCMikon&subscribe=&data=
     * @return array|null[]
     */
    public static function CreateEventStopOPCMikon()
    {
        $log = new LogAmicumFront("CreateEventStopOPCMikon");
        $result = null;
        try {
            $log->addLog("Начало выполнения метода");

            $opcs = (new Query())
                ->select('sensor.id as sensor_id, sensor.title, max(date_time_work) as max_date_time')
                ->from('sensor')
                ->innerJoin('view_sensor_parameter_value_only_main', 'view_sensor_parameter_value_only_main.sensor_id=sensor.id')
                ->where(['sensor.object_id' => 155, 'sensor.asmtp_id' => 2])
                ->andWhere('parameter_id!=164')
                ->groupBy(['sensor.id', 'sensor.title'])
                ->all();

//            $log->addData($opcs, '$opc', __LINE__);

            $date_time_now = Assistant::GetDateTimeNow();
            foreach ($opcs as $opc) {
                $sensor_params = SensorBasicController::getSensorParameterHandbookValue($opc['sensor_id']);
//                $log->addData($sensor_params, '$opc', __LINE__);

                $mine_id = -1;
                $edge_id = -1;
                $xyz = -1;
                foreach ($sensor_params as $sensor_param) {
                    if ($sensor_param['parameter_id'] == 346) {                                                         // шахтное поле
                        $mine_id = $sensor_param['value'];
                    }

                    if ($sensor_param['parameter_id'] == 269) {                                                         // ветвь
                        $edge_id = $sensor_param['value'];
                    }

                    if ($sensor_param['parameter_id'] == 83) {                                                          // координата
                        $xyz = $sensor_param['value'];
                    }
                }


                if (
                    $edge_id != -1 and
                    $mine_id != -1 and
                    $mine_id == AMICUM_DEFAULT_MINE
                ) {

                    if ($opc['max_date_time'] == -1 or
                        Assistant::GetMysqlTimeDifference($date_time_now, $opc['max_date_time']) > 60
                    ) {
                        $value_to_record = StatusEnumController::EMERGENCY_VALUE;
                        $status_to_record = StatusEnumController::EVENT_RECEIVED;
                        $value = 0;
                    } else {
                        $value_to_record = StatusEnumController::NORMAL_VALUE;
                        $status_to_record = StatusEnumController::EVENT_ELIMINATED_BY_SYSTEM;
                        $value = 1;
                    }

                    if ($opc['max_date_time'] == -1) {
                        $date_time = $date_time_now;
                    } else {
                        $date_time = $opc['max_date_time'];
                    }

//                    $log->addData($mine_id, '$mine_id', __LINE__);
//                    $log->addData($edge_id, '$edge_id', __LINE__);
//                    $log->addData($xyz, '$xyz', __LINE__);
                    $log->addData($date_time, '$date_time', __LINE__);

                    /**
                     * Записываем состояние привязанного сенсора 164
                     */
                    $response = SensorMainController::GetOrSetSensorParameter($opc['sensor_id'], 164, 3);
                    $log->addLogAll($response);
                    if ($response['status'] === 0) {
                        throw new Exception("Не смог получить из кеша, а так же создать в базе данных для парамтера 164 sensor_parameter_id");
                    }

                    $sensor_parameter_id = $response['sensor_parameter_id'];


                    /**
                     * Запись значения параметра сенсора 164 в БД
                     */
                    $response = SensorBasicController::addSensorParameterValue($sensor_parameter_id, $value, $value_to_record, $date_time_now);
                    $log->addLogAll($response);
                    if ($response['status'] === 0) {
                        throw new Exception("Сохранение значения параметра 164 не удалось");
                    }


                    // 38 - получено диспетчером
                    // 44 - аварийное событие
                    $response = EventMainController::createEventForWorkerGas('sensor', $opc['sensor_id'], EventEnumController::DCS_STOP, $value, $date_time_now, $value_to_record, 164, $mine_id, $status_to_record, $edge_id, $xyz);
                    $log->addLogAll($response);
                    if ($response['status'] != 1) {
                        throw new Exception('Ошибка генерации события');
                    }
                }
            }

            $log->addLog("Окончание выполнения метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $result], $log->getLogAll());
    }
}