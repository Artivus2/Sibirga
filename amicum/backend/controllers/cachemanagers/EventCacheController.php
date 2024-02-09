<?php


namespace backend\controllers\cachemanagers;

use backend\controllers\Assistant;
use Exception;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\EventJournal;
use Throwable;
use Yii;

class EventCacheController
{
    // getEventsList                    - получение списка событий из кэша
    // getEvent                         - получение события из кэша
    // setEvent                         - сохранение события в кэш
    // buildCacheKey                    - Метод для построения ключа, по которому событие записывется в кэш
    // buildCacheStructure              - Создаёт структуру события для записи в кэш
    // multiSetEvent                    - Сохраняет событие в кэш массово
    // updateEventCacheValue            - Обновление определённого поля в кэше события
    // updateEventCacheValueWithStatus  - Обновление определённого поля в кэше события и его статуса
    // updateEventCacheValueWithStatusAndDateTime - Обновление определённого поля в кэше события и его статуса и даты
    // deleteOldEvents                  - Удаление из кэша устаревших событий
    // deleteEvent                      - Удалить из кэша событие
    // amicum_flushall                  - метод очистки кеша событий
    // removeAll()                      - Метод полного удаления кэша событий

    // amicum_mSet - Метод вставки значений в кэш командами редиса.
    // amicum_mGet - метод получения данных с редис за один раз методами редиса
    // amicum_rSet - Метод вставки значений в кэш командами редиса.
    // amicum_rGet - Метод получения значения из кэша на прямую из редис

    public static $event_key = 'Ev';

    public $redis_cache;

    public function __construct()
    {
        $this->redis_cache = Yii::$app->redis_event;
    }

    /**
     * Получение списка событий из кэша
     * В параметрах задаётся маска для поиска ключей, например:
     *
     * Получить все события из кэша
     * getEventsList();
     *
     * Получить все события превышения CH4 по конкретной шахте
     * getEventsList(290, 22409);
     *
     * Получить все события для конкретного объекта (воркера, сенсора или оборудования)
     * getEventsList('*', '*', 32167);
     *
     * @param string $mine_id
     * @param string $event_id
     * @param string $main_id
     * @return array
     */
    public function getEventsList($mine_id = '*', $event_id = '*', $main_id = '*')
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'getEventsList. Начало метода';

        try {
            /**
             * Поиск ключей в кэше
             */
            $cache_key_filter = self::buildCacheKey($mine_id, $event_id, $main_id);
            $keys = $this->redis_cache->scan(0, 'MATCH', $cache_key_filter, 'COUNT', '10000000')[1];

            /**
             * Получение значений из кэша
             * Если в кэше не найдены ключи или по ключам не найдено событий,
             * то статус выполнения ставится в 0
             */
            if ($keys) {
                $events = $this->amicum_mGet($keys);
                if ($events) {
                    $result = $events;
                    $warnings[] = 'getEventsList. Получил события из кэша';
                } else {
                    $status = 0;
                    $errors[] = "getEventsList. В кэше не найдены события с параметрами
                    mine_id: $mine_id, event_id: $event_id, main_id: $main_id";
                }
            }

        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'getEventsList. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'getEventsList. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Получение события из кэша
     * @param $mine_id -   идентификатор шахты
     * @param $event_id -   идентификатор события
     * @param $main_id -   идентификатор объекта к которому относится событие
     * @return array
     */
    public function getEvent($mine_id, $event_id, $main_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'getEvent. Начало метода';

        try {
            /**
             * Генерация ключа
             */
            $cache_key = self::buildCacheKey($mine_id, $event_id, $main_id);
            $warnings[] = 'getEvent. Сгенерировал ключ ' . $cache_key;

            /**
             * Получение значения
             */
            $result = $this->amicum_rGet($cache_key);
            if ($result === false) {
                $warnings[] = 'getEvent. Значение в кэше не найдено';
                $status = 0;
            } else {
                $warnings[] = 'getEvent. Значение получено из кэша';
                $status = 1;
            }

        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'getEvent. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'getEvent. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);;
    }

    /**
     * Сохраняет событие в кэш
     * @param $mine_id -   идентификатор шахты
     * @param $event_id -   идентификатор события
     * @param $main_id -   идентификатор объекта к которому относится событие
     * @param $event_status_id -   идентификатор статуса события
     * @param $edge_id -   идентификатор выработки, на которой произошло событие
     * @param $value -   значение параметра, вызвавшего генерацию события
     * @param $value_status_id -   идентификатор статуса параметра
     * @param $date_time -   дата и время события
     * @param $xyz -   координата события
     * @param $parameter_id -   параметр события, сгенерировавший события
     * @param $object_id -   идентификатор типового объекта, на котором произошло событие
     * @param $object_title -   название типового объекта, на котором произошло событие
     * @param $object_table -   название объекта на котором произошло событие
     * @param $event_journal_id -   идентификатор ключа события в журнале
     * @param $group_alarm_id -   группа оповещения
     * @return array
     */
    public function setEvent($mine_id, $event_id, $main_id, $event_status_id,
                             $edge_id, $value, $value_status_id, $date_time,
                             $xyz, $parameter_id, $object_id, $object_title, $object_table, $event_journal_id, $group_alarm_id = NULL)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'saveEvent. Начало метода';

        try {
            /**
             * Генерация ключа
             */
            $cache_key = self::buildCacheKey($mine_id, $event_id, $main_id);
            $warnings[] = 'saveEvent. Сгенерировал ключ ' . $cache_key;

            /**
             * Генерация структуры значения кэша и его сохранение
             */
            $cache_value = self::buildCacheStructure($event_id, $edge_id, $value, $value_status_id, $date_time,
                $event_status_id, $main_id, $mine_id,
                $xyz, $parameter_id, $object_id, $object_title, $object_table, $event_journal_id, $group_alarm_id);
            $this->amicum_rSet($cache_key, $cache_value);
            $warnings[] = 'saveEvent. Значение сохранено в кэш';

            /**
             * Очистка памяти
             */
            unset($cache_value);

        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'saveEvent. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'saveEvent. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * multiSetEvent - Сохраняет событие в кэш массово
     * @param $events -   идентификатор шахты
     * @return array
     */
    public function multiSetEvent($events)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'multiSetEvent. Начало метода';
        try {
            foreach ($events as $event) {
                $key = self::buildCacheKey($event['mine_id'], $event['event_id'], $event['main_id']);
                $event = self::buildCacheStructure(
                    $event['event_id'],
                    $event['edge_id'],
                    $event['value'],
                    $event['value_status_id'],
                    $event['date_time'],
                    $event['event_status_id'],
                    $event['main_id'],
                    $event['mine_id'],
                    $event['xyz'],
                    $event['parameter_id'],
                    $event['object_id'],
                    $event['object_title'],
                    $event['object_table'],
                    $event['event_journal_id'],
                    $event['group_alarm_id']
                );
                $date_to_cache[$key] = $event;
            }
            if (isset($date_to_cache)) {
                $result = $this->amicum_mSet($date_to_cache);
            }

        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'multiSetEvent. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'multiSetEvent. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Обновление определённого поля в кэше события
     * @param $mine_id -   идентификатор шахты
     * @param $event_id -   идентификатор события
     * @param $main_id -   идентификатор объекта, с которым связано событие
     * @param $field -   имя поля, которое необходимо изменить
     * @param $value -   значение на которое меняем поле $field
     * @return array
     */
    public function updateEventCacheValue($mine_id, $event_id, $main_id, $field, $value)
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        $warnings[] = 'updateEventCacheValue. Начало метода';

        try {
            /**
             * Получение события из кэша
             */
            $response = $this->getEvent($mine_id, $event_id, $main_id);
            if ($response['status'] != 1) {
                $warnings[] = $response['warnings'];
                $warnings[] = 'updateEventCacheValue. Значение не найдено в кэше';
                $errors[] = $response['errors'];
                throw new Exception('updateEventCacheValue. Значение не найдено в кэше');
            }
            $event = $response['Items'];

            /**
             * Обновление и сохранение в кэш
             */
            if ($event) {
                $event[$field] = $value;
                $response = $this->setEvent(
                    $mine_id,
                    $event_id,
                    $main_id,
                    $event['event_status_id'],
                    $event['edge_id'],
                    $event['value'],
                    $event['value_status_id'],
                    $event['date_time'],
                    $event['xyz'],
                    $event['parameter_id'],
                    $event['object_id'],
                    $event['object_title'],
                    $event['object_table'],
                    $event['event_journal_id'],
                    $event['group_alarm_id']
                );
                if ($response['status'] != 1) {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception('updateEventCacheValue. Ошибка сохранения события в кэш');
                }

            }

        } catch (Throwable $exception) {
            $errors[] = 'updateEventCacheValue. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $warnings[] = 'updateEventCacheValue. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * updateEventCacheValueWithStatus - Обновление определённого поля в кэше события и его статуса
     * @param $mine_id -   идентификатор шахты
     * @param $event_id -   идентификатор события
     * @param $main_id -   идентификатор объекта, с которым связано событие
     * @param $date_time -   новое дата и время
     * @param $value -   значение на которое меняем поле $field
     * @param $event_status_id -   статус решения события
     * @param $value_status_id - статус события
     * @return array
     */
    public function updateEventCacheValueWithStatus($mine_id, $event_id, $main_id, $date_time, $value, $event_status_id, $value_status_id)
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        $warnings[] = 'updateEventCacheValueWithStatus. Начало метода';
        $value_from_cache = -1;
        $event_journal_id = -1;
        try {
            /**
             * Получение события из кэша
             */
            $response = $this->getEvent($mine_id, $event_id, $main_id);

            if ($response['status'] != 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new Exception('updateEventCacheValueWithStatus. Значение не найдено в кэше');
            }

            $event = $response['Items'];
            $value_from_cache = $event['value'];
            $event_journal_id = $event['event_journal_id'];

            if ($value < $value_from_cache) {
                $value = $value_from_cache;
            }
            /**
             * Обновление и сохранение в кэш
             */
            if ($event) {
                $response = $this->setEvent(
                    $mine_id,
                    $event_id,
                    $main_id,
                    $event_status_id,
                    $event['edge_id'],
                    $value,
                    $value_status_id,
                    $date_time,
                    $event['xyz'],
                    $event['parameter_id'],
                    $event['object_id'],
                    $event['object_title'],
                    $event['object_table'],
                    $event['event_journal_id'],
                    $event['group_alarm_id']
                );
                if ($response['status'] != 1) {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception('updateEventCacheValueWithStatus. Ошибка сохранения события в кэш');
                }

            }

            $warnings[] = 'updateEventCacheValueWithStatus. Конец метода';

        } catch (Throwable $exception) {
            $errors[] = 'updateEventCacheValueWithStatus. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'value_from_cache' => $value_from_cache, 'event_journal_id' => $event_journal_id);
    }

    /**
     * updateEventCacheValueWithStatusAndDateTime - Обновление определённого поля в кэше события и его статуса и даты
     * @param $event_journal_id -   ключ журнала событий
     * @param $mine_id -   идентификатор шахты
     * @param $event_id -   идентификатор события
     * @param $main_id -   идентификатор объекта, с которым связано событие
     * @param $field -   имя поля, которое необходимо изменить
     * @param $value -   значение на которое меняем поле $field
     * @param $event_status_id -   статус события
     * @param $date_time -   дата и время события
     * @return array
     */
    public function updateEventCacheValueWithStatusAndDateTime($event_journal_id, $mine_id, $event_id, $main_id, $field, $value, $event_status_id, $date_time)
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        $warnings[] = 'updateEventCacheValueWithStatusAndDateTime. Начало метода';
        $value_from_cache = -1;

        try {
            /**
             * Получение события из кэша
             */
            $response = $this->getEvent($mine_id, $event_id, $main_id);
            if ($response['status'] != 1) {
                $warnings[] = $response['warnings'];
                $warnings[] = 'updateEventCacheValueWithStatusAndDateTime. Значение не найдено в кэше';
                $errors[] = $response['errors'];
                $event = EventJournal::find()->where(['id' => $event_journal_id])->asArray()->one();
                if (!$event) {
                    throw new Exception("updateEventCacheValueWithStatusAndDateTime. Событие не найдено в кэше и в БД event_journal_id: $event_journal_id. Шахта: $mine_id. Событие: $event_id. Объект: $main_id");
                }
            } else {
                $event = $response['Items'];
            }
            $value_from_cache = $event['value'];

            /**
             * Обновление и сохранение в кэш
             */
            if ($event) {
                $event[$field] = $value;
                $response = $this->setEvent(
                    $mine_id,
                    $event_id,
                    $main_id,
                    $event_status_id,
                    $event['edge_id'],
                    $event['value'],
                    $event['value_status_id'],
                    $date_time,
                    $event['xyz'],
                    $event['parameter_id'],
                    $event['object_id'],
                    $event['object_title'],
                    $event['object_table'],
                    $event['event_journal_id'],
                    $event['group_alarm_id']
                );
                if ($response['status'] != 1) {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new Exception('updateEventCacheValueWithStatusAndDateTime. Ошибка сохранения события в кэш');
                }

            }

        } catch (Throwable $exception) {
            $errors[] = 'updateEventCacheValueWithStatusAndDateTime. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $warnings[] = 'updateEventCacheValueWithStatusAndDateTime. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'value_from_cache' => $value_from_cache);
    }

    /**
     * Название метода: removeAll() - Метод полного удаления кэша событий. Очищает все кэши связанные с событиями
     * Назначение метода: Метод полного удаления кэша событий. Очищает все кэши связанные с событиями, а именно:
     *    -- Event
     * @param $mine_id - ключ шахты
     * @example (new SituationCacheController())->removeAll();
     *
     * @author Якимов М.Н.
     * Created date: on 02.01.2020 13:19
     * @since ver
     */
    public function removeAll($mine_id = '*')
    {
        $situation_keys = $this->redis_cache->scan(0, 'MATCH', self::$event_key . ':' . $mine_id . ':*', 'COUNT', '10000000')[1];
        if ($situation_keys)
            $this->amicum_mDel($situation_keys);
    }

    // amicum_flushall - метод очистки кеша событий
    public function amicum_flushall()
    {
        $this->redis_cache->executeCommand('flushall');

        if (REDIS_REPLICA_MODE === true) {
            // главный кеш
            $redis_replica = new yii\redis\Connection();
            $redis_replica->hostname = REDIS_REPLICA_HOSTNAME;
            $redis_replica->port = $this->redis_cache->port;
            $redis_replica->executeCommand('flushall');
        }
    }

    /**
     * Удаление из кэша устаревших событий
     * Устаревшими считаются события, с момента создания которых прошло 3 и более дней.
     * @return array
     */
    public function deleteOldEvents()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("deleteOldEvents");

        try {
            $log->addLog("Начало выполнения метода");

            /**
             * Получение всех событий из кэша
             */
            $cache_key = self::buildCacheKey('*', '*', '*');
            $keys = $this->redis_cache->scan(0, 'MATCH', $cache_key, 'COUNT', '10000000')[1];

            $log->addLog("Нашел ключи в кеше");

            if ($keys) {
                $events = $this->amicum_mGet($keys);

                $log->addLog("Получил ключи из кеша");

                /**
                 * Проверка даты события. Если оно создано более 3 дней назад, то
                 * пометить его на удаление
                 */
                $curr_date_time = Assistant::GetDateNow();

                $log->addLog('Текущая дата: ' . $curr_date_time);

                $event_keys_to_delete = array();
                foreach ($events as $event) {
                    $delta_time = strtotime($curr_date_time) - strtotime($event['date_time']);
                    if ($delta_time > 459200) { // Кол-во секунд в 3 днях
                        $count_record++;
                        $event_keys_to_delete[] = self::buildCacheKey($event['mine_id'], $event['event_id'], $event['main_id']);
                    }
                }

                /**
                 * Удаление событий из кэша
                 */
                $this->amicum_mDel($event_keys_to_delete);

                $log->addLog("Удалил ключи из кеша");
                $log->addData($event_keys_to_delete, '$event_keys_to_delete', __LINE__);
            }
            $log->addLog("Окончил выполнение метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Окончание выполнения метода");
        $log->saveLogSynchronization($count_record);

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * deleteEvent - Удалить из кэша событие
     * @return array
     */
    public function deleteEvent($mine_id, $event_id, $main_id)
    {
        $result = array();
        $status = 1;
        $errors = array();
        $warnings = array();

        $warnings[] = 'deleteEvent. Начало метода';

        try {
            /**
             * Получение всех событий из кэша
             */
            $cache_key = self::buildCacheKey($mine_id, $event_id, $main_id);
            $keys = $this->redis_cache->scan(0, 'MATCH', $cache_key, 'COUNT', '10000000')[1];
            /**
             * Удаление событий из кэша
             */
            $this->amicum_mDel($keys);
            $warnings[] = 'deleteEvent. Удалил следующие ключи:';
            $warnings[] = $keys;

        } catch (Throwable $exception) {
            $errors[] = 'deleteEvent. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        return array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
    }


    /**
     * Метод для построения ключа, по которому событие записывется в кэш
     * @param $mine_id -   идентификатор шахты
     * @param $event_id -   идентификатор события
     * @param $main_id -   идентификатор объекта к которому относится событие
     * @return string   сформированный ключ
     */
    public static function buildCacheKey($mine_id, $event_id, $main_id)
    {
        return self::$event_key . ':' . $mine_id . ':' . $event_id . ':' . $main_id;
    }

    /**
     * Создаёт структуру события для записи в кэш
     * @param $event_id -   идентификатор события
     * @param $edge_id -   идентификатор выработки, на которой произошло событие
     * @param $value -   значение параметра, вызвавшего генерацию события
     * @param $value_status_id -   идентификатор статуса параметра
     * @param $date_time -   дата и время события
     * @param $event_status_id -   идентификатор статуса события
     * @param $main_id -   идентификатор объекта, у которого произошло событие
     * @param $mine_id -   идентификатор шахты, на которой произошло событие
     * @param $xyz -   координата события
     * @param $parameter_id -   параметр события, сгенерировавший события
     * @param $object_id -   идентификатор типового объекта, на котором произошло событие
     * @param $object_title -   название типового объекта, на котором произошло событие
     * @param $object_table -   название объекта на котором произошло событие
     * @param $event_journal_id -   ключ события в журнале
     * @return array
     */
    public static function buildCacheStructure($event_id, $edge_id, $value, $value_status_id, $date_time,
                                               $event_status_id, $main_id, $mine_id,
                                               $xyz, $parameter_id, $object_id, $object_title, $object_table, $event_journal_id, $group_alarm_id)
    {
        $cache_struct = array();
        $cache_struct['event_id'] = (int)$event_id;
        $cache_struct['main_id'] = $main_id;
        $cache_struct['edge_id'] = $edge_id;
        $cache_struct['value'] = $value;
        $cache_struct['value_status_id'] = (int)$value_status_id;
        $cache_struct['date_time'] = $date_time;
        $cache_struct['event_status_id'] = (int)$event_status_id;
        $cache_struct['mine_id'] = $mine_id;
        $cache_struct['xyz'] = $xyz;
        $cache_struct['parameter_id'] = (int)$parameter_id;
        $cache_struct['object_id'] = (int)$object_id;
        $cache_struct['object_title'] = $object_title;
        $cache_struct['object_table'] = $object_table;
        $cache_struct['event_journal_id'] = $event_journal_id;
        $cache_struct['group_alarm_id'] = $group_alarm_id;
        return $cache_struct;
    }


    /**
     * Метод получения данных с редис за один раз методами редиса
     * @param $keys
     * @return array|bool
     */
    public function amicum_mGet($keys)
    {
        $mgets = $this->redis_cache->executeCommand('mget', $keys);
        if ($mgets) {
            foreach ($mgets as $mget) {
                $result[] = unserialize($mget)[0];
            }
            return $result;
        }
        return false;
    }

    /**
     * amicum_mSet - Метод вставки значений в кэш командами редиса.
     * Аналогичен методу set(), только ключи не преобразуются в какой-либо формат,
     * они добавляюся как есть
     * @param $items
     * @param null $dependency
     * @return mixed
     */
    private function amicum_mSet($items, $dependency = null)
    {
        $data = [];
        foreach ($items as $key => $value) {
            $value = serialize([$value, $dependency]);
            $data[] = $key;
            $data[] = $value;
        }

        $mset = $this->redis_cache->executeCommand('mset', $data);

        if (REDIS_REPLICA_MODE === true) {
            $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $this->redis_cache->port, 'mset', $data);
        }

        return $mset;
    }

    /**
     * amicum_rSet - Метод вставки значений в кэш командами редиса.
     * Аналогичен методу set(), только ключи не преобразуются в какой-либо формат,
     * они добавляюся как есть
     * @param $key
     * @param $value
     * @param null $dependency
     * @return mixed
     */
    private function amicum_rSet($key, $value, $dependency = null)
    {
        $value = serialize([$value, $dependency]);
        $data[] = $key;
        $data[] = $value;

        $mset = $this->redis_cache->executeCommand('set', $data);

        if (REDIS_REPLICA_MODE === true) {
            $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $this->redis_cache->port, 'set', $data);
        }

        return $mset;
    }

    public function amicum_repRedis($hostname, $port, $command_redis, $data)
    {
        $errors = array();
        $warnings = array();
        $status = 1;
        $result = array();

        $warnings[] = 'amicum_repRedis. Начало метода';
        $microtime_start = microtime(true);
        try {
            $redis_replica = new yii\redis\Connection();
            $redis_replica->hostname = $hostname;
            $redis_replica->port = $port;
            $result = $redis_replica->executeCommand($command_redis, $data);
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'amicum_repRedis. Исключение:';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'amicum_repRedis. Конец метода';
        return array('Items' => $result, 'warnings' => $warnings, 'errors' => $errors, 'status' => $status);
    }

    /**
     * Метод получение значения из кэша на прямую из редис
     * @param $key
     * @return bool
     */
    private function amicum_rGet($key)
    {
        $key1[] = $key;
        $value = $this->redis_cache->executeCommand('get', $key1);

        if ($value) {
            $value = unserialize($value)[0];
            return $value;
        }
        return false;
    }

    /**
     * Метод удаления по указанным ключам
     * @param $keys
     * @return bool
     */
    public function amicum_mDel($keys)
    {
        //Todo: сделать проверку в будущем на возвращаемые из redis
        if ($keys) {
            foreach ($keys as $key) {
                $key1 = array();
                $key1[] = $key;
                $value = $this->redis_cache->executeCommand('del', $key1);

                if (REDIS_REPLICA_MODE === true) {
                    $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $this->redis_cache->port, 'del', $key1);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Метод удаления по указанному ключу
     */
    public function amicum_rDel($key)
    {
        $key1[] = $key;
        $value = $this->redis_cache->executeCommand('del', $key1);
        if (REDIS_REPLICA_MODE === true) {
            $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $this->redis_cache->port, 'del', $key1);
        }
    }
}
