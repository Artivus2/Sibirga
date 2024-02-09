<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\cachemanagers;

use backend\controllers\Assistant;
use frontend\controllers\system\LogAmicumFront;
use Throwable;
use Yii;
use yii\db\Query;

class SituationCacheController
{
    // Базовый контроллер по работе с кешем Ситуаций и ее свойствами

    // runInit                      - Метод полной инициализации кэша неустраненных ситуаций по шахте и их зон
    // initSituation                - метод инициализации всех неустраненных ситуаций
    // initEventSituation           - метод инициализации всех событий неустраненных ситуаций
    // initZone                     - метод инициализации всех зон всех неустраненных ситуаций
    // initSendStatus               - метод инициализации всех статусов отправки всех неустраненных ситуаций
    // removeAll                    - Метод полного удаления кэша ситуаций. Очищает все кэши связанные с ситуациями
    // amicum_flushall              - метод очистки кеша ситуаций

    // buildCacheKeySituation               - Метод для построения ключа Ситуаций, по которому ситуация записывется в кэш
    // buildCacheKeyZone                    - Метод для построения ключа Зон Ситуаций, по которому ситуация записывется в кэш
    // buildCacheKeySituationEvent          - Метод для построения ключа событий ситуации
    // buildCacheKeySendStatus              - Метод для построения ключа статуса отправки ситуации при опопвещении

    // buildCacheStructureSituation         - Создаёт структуру ситуации для записи в кэш
    // buildCacheStructureSituationEvent    - Создаёт структуру события ситуации для записи в кэш
    // buildCacheStructureZone              - Создаёт структуру Зоны ситуации для записи в кэш
    // buildCacheStructureSendStatus        - Создаёт структуру статуса отправки по ситуации для записи в кэш

    // getSituationList             - получение списка ситуаций из кэша
    // getSituation                 - получение ситуации из кэша
    // getEventSituationList        - Получение событий ситуаций из кэша
    // getEventSituation            - Получение собятия ситуации из кэша
    // getZoneList                  - получение списка зон ситуаций из кэша
    // getZone                      - получение зоны ситуации из кэша
    // getSendStatusList            - получение списка статусов отправки из кэша
    // getSendStatus                - получение статуса отправки из кэша
    // multiSetSituation            - Сохраняет ситуации в кэш массово
    // multiSetZone                 - Сохраняет зоны ситуаций в кэш массово
    // multiSetSendStatus           - Сохраняет статусы отправки ситуаций в кэш массово
    // setSituation                 - сохранение ситуацию в кэш
    // setZone                      - Сохраняет зону ситуации в кэш разово
    // setEventSituation            - сохранение событие ситуацию в кэш
    // setSendStatus                - сохранение статус отправки ситуации в кэш
    // deleteOldSituations          - Удаление из кэша устаревших ситуаций
    // deleteSituationEvent         - Удаление из кэша всех событий ситуации
    // deleteMultiSituationEvent    - Удаление из кэша всех событий ситуации с поиском ключа
    // deleteMultiSendStatus        - Удаление из кэша всех статусов отправки ситуаций
    // deleteSendStatus             - Удаление из кэша статуса отправки ситуации
    // deleteZone                   - Удаление из кэша Зоны ситуации
    // deleteMultiZone              - Удаление из кэша Зоны ситуации с поиском ключа
    // deleteSituation              - Удаление из кэша ситуации
    // deleteMultiSituation         - Удаление из кэша ситуации с поиском ключа
    // updateSituationCacheValue    - Обновление определённого поля в кэше ситуации


    // amicum_mSet - Метод вставки значений в кэш командами редиса.
    // amicum_mGet - метод получения данных с редис за один раз методами редиса
    // amicum_rSet - Метод вставки значений в кэш командами редиса.
    // amicum_rGet - Метод получения значения из кэша на прямую из редис

    public static $situation_key = 'Si';                        // кеш ситуаций
    public static $zone_key = 'Zo';                             // кеш зон ситуаций
    public static $situation_event_key = 'SiEv';                // кеш привязок событий и ситуаций
    public static $send_status_key = 'StSe';                    // кеш статусов отправки сообщений ситуаций

    public $redis_cache;

    public function __construct()
    {
        $this->redis_cache = Yii::$app->redis_situation;
    }

    /**
     * Название метода: runInit()
     * Назначение метода: Метод полной инициализации кэша неустраненных ситуаций по шахте и их зон
     * @param int $mine_id - идентификатор шахты
     * @return array $result - массив рузельтата выполнения метода. Сами данные не возвращает
     *
     * @package backend\controllers\cachemanagers
     * Входные обязательные параметры:
     * @author Якимов М.Н.
     * Created date: on 02.01.2020 13:19
     * @since ver
     */
    public function runInit($mine_id)
    {
//        ini_set('max_execution_time', 6000);
//        ini_set('memory_limit', '5000M');
        $errors = array();
        $status = array();
        if ($mine_id != "") {
//            $this->removeAll($mine_id);
            $status['initSituation'] = $this->initSituation($mine_id);                                                          // инициализируем кэш ситуаций
            $status['initZone'] = $this->initZone($mine_id);                                                                    // инициализируем кэш зон ситуаций
            $status['initEventSituation'] = $this->initEventSituation($mine_id);                                                // инициализируем кэша привязок журнала событий и ситуаций
            $status['initSendStatus'] = $this->initSendStatus($mine_id);                                                        // инициализируем кэш статусов отправки
        } else $errors[] = "Идентификатор шахты не передан. Ошибка инициализации кэша ситуаций";
        $result = array('errors' => $errors, 'status' => $status);
        unset($status);
        return $result;
    }

    /**
     * Название метода: initEventSituation - метод инициализации связок событий и всех неустраненных ситуаций
     *
     * Входные необязательные параметры
     * @param $mine_id - идентификатор шахтного поля. Если указать этот параметр, то берет данные для конкретной шахты
     * и добавляет в кэш
     * @param $sql - условие для фильтра!
     *
     * @return mixed возвращает true при успешном добавлении в кэш, иначе false
     *
     * @author Якимов М.Н.
     * Created date: on 02.01.2020 13:19
     * @since ver
     */
    public function initEventSituation($mine_id = -1, $sql = '')
    {
        $sql_filter = '';
        if ($mine_id !== -1) {
            $sql_filter .= "situation_journal.mine_id = $mine_id ";
        }

        if ($sql !== '') {
            $sql_filter .= ' AND ' . $sql;
        }

        $situations = (new Query())
            ->select([
                'situation_journal.id as situation_journal_id',
                'event_journal.main_id as main_id',
                'event_journal.id as event_journal_id',
                'event_journal.event_id as event_id',
                'event_journal.event_status_id as event_status_id',
                'event_journal.status_id as value_status_id',
                'situation_journal.mine_id as mine_id',
                'situation_journal.date_time_start as date_time_start',
                'situation_journal.date_time_end as date_time_end'
            ])
            ->from('situation_journal')
            ->innerJoin('event_journal_situation_journal', 'event_journal_situation_journal.situation_journal_id=situation_journal.id')
            ->innerJoin('event_journal', 'event_journal_situation_journal.event_journal_id=event_journal.id')
            ->where($sql_filter)
            ->andWhere('situation_journal.status_id is null or (situation_journal.status_id!=33 and situation_journal.status_id!=37)')
            ->andWhere('situation_journal.date_time_start > "' . date('Y-m-d H:i:s', strtotime(Assistant::GetDateTimeNow() . '-1day')) . '"')
            ->all();
        if ($situations) {
            foreach ($situations as $situation) {
                $key = self::buildCacheKeySituationEvent($situation['mine_id'], $situation['event_journal_id'], $situation['situation_journal_id']);
                $date_to_cache[$key] = $situation;
            }
            $this->amicum_mSet($date_to_cache);
            return true;
        }
        return false;
    }

    /**
     * Название метода: initSituation - метод инициализации всех неустраненных ситуаций
     *
     * Входные необязательные параметры
     * @param $mine_id - идентификатор шахтного поля. Если указать этот параметр, то берет данные для конкретной шахты
     * и добавляет в кэш
     * @param $sql - условие для фильтра!
     *
     * @return mixed возвращает true при успешном добавлении в кэш, иначе false
     *
     * @author Якимов М.Н.
     * Created date: on 02.01.2020 13:19
     * @since ver
     */
    public function initSituation($mine_id = -1, $sql = '')
    {
        $sql_filter = '';
        if ($mine_id !== -1) {
            $sql_filter .= "mine_id = $mine_id ";
        }

        if ($sql !== '') {
            $sql_filter .= ' AND ' . $sql;
        }

        $situations = (new Query())
            ->select([
                'id as situation_journal_id',
                'situation_id',
                'date_time',
                'main_id',
                'status_id as situation_status_id',
                'danger_level_id',
                'company_department_id',
                'mine_id',
                'date_time_start',
                'date_time_end'
            ])
            ->from('situation_journal')
            ->where($sql_filter)
            ->andWhere('status_id is null or (status_id!=33 and status_id!=37)')
            ->andWhere('date_time_start > "' . date('Y-m-d H:i:s', strtotime(Assistant::GetDateTimeNow() . '-1day')) . '"')
            ->all();
        if ($situations) {
            foreach ($situations as $situation) {
                $key = self::buildCacheKeySituation($situation['mine_id'], $situation['situation_id'], $situation['situation_journal_id']);
                $date_to_cache[$key] = $situation;
            }
            $this->amicum_mSet($date_to_cache);
            return true;
        }
        return false;
    }

    /**
     * Сохраняет ситуацию в кэш
     * @param $situation_journal_id -   идентификатор ключа ситуации в журнале ситуаци
     * @param $situation_id -   идентификатор ситуации
     * @param $date_time -   дата и время ситуации
     * @param $main_id -   идентификатор объекта к которому относится ситуация
     * @param $situation_status_id -   идентификатор статуса ситуации
     * @param $danger_level_id -   ключ уровня опасности ситуации (риск)
     * @param $company_department_id -   ключ подразделения/ситуации на котором произошла ситуация
     * @param $mine_id -   идентификатор шахты
     * @param $date_time_start -   дата начала ситуации
     * @param $date_time_end -   дата окончания ситуации
     * @return array
     */
    public function setSituation($situation_journal_id, $situation_id, $date_time, $main_id, $situation_status_id,
                                 $danger_level_id, $company_department_id, $mine_id, $date_time_start, $date_time_end)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'saveSituation. Начало метода';

        try {
            /**
             * Генерация ключа
             */
            $cache_key = self::buildCacheKeySituation($mine_id, $situation_id, $situation_journal_id);
            $warnings[] = 'saveSituation. Сгенерировал ключ ' . $cache_key;

            /**
             * Генерация структуры значения кэша и его сохранение
             */
            $cache_value = self::buildCacheStructureSituation($situation_journal_id, $situation_id, $date_time, $main_id, $situation_status_id,
                $danger_level_id, $company_department_id, $mine_id, $date_time_start, $date_time_end);
            $this->amicum_rSet($cache_key, $cache_value);
            $warnings[] = 'saveSituation. Значение сохранено в кэш';

            /**
             * Очистка памяти
             */
            unset($cache_value);

        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'saveSituation. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'saveSituation. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * setEventSituation - Сохраняет событие ситуации в кэш
     * @param $situation_journal_id -   идентификатор ключа ситуации в журнале ситуаци
     * @param $event_journal_id -   идентификатор журнала событий
     * @param $main_id -   идентификатор объекта к которому относится событие
     * @param $event_id -   идентификатор события
     * @param $mine_id -   идентификатор шахты
     * @param $date_time_start -   дата начала события
     * @param $date_time_end -   дата окончания события
     * @param $event_status_id -   статус события (устаренно, устраняется, получено)
     * @param $value_status_id -   статус значения события (нормальное, аварийное)
     * @return array
     */
    public function setEventSituation($situation_journal_id, $event_journal_id, $main_id, $event_id, $mine_id, $date_time_start, $date_time_end, $event_status_id, $value_status_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'setEventSituation. Начало метода';

        try {
            /**
             * Генерация ключа
             */
            $cache_key = self::buildCacheKeySituationEvent($mine_id, $event_journal_id, $situation_journal_id);
            $warnings[] = 'setEventSituation. Сгенерировал ключ ' . $cache_key;

            /**
             * Генерация структуры значения кэша и его сохранение
             */
            $cache_value = self::buildCacheStructureSituationEvent($situation_journal_id, $event_journal_id, $main_id, $event_id, $mine_id, $date_time_start, $date_time_end, $event_status_id, $value_status_id);
            $this->amicum_rSet($cache_key, $cache_value);
            $warnings[] = 'setEventSituation. Значение сохранено в кэш';

            /**
             * Очистка памяти
             */
            unset($cache_value);

        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'setEventSituation. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'setEventSituation. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * setSendStatus - Сохраняет статус отправки ситуации в кэш
     * @param $situation_journal_id -   идентификатор ключа ситуации в журнале ситуаци
     * @param $xml_send_type_id -   идентификатор ключа типа отправки (sms/email)
     * @param $date_time -   дата и время отправки
     * @param $status_id -   идентификатор статуса отправки
     * @param $mine_id -   идентификатор шахты
     * @param $situation_journal_send_status_id -   ключ отправки сообщения
     * @return array
     */
    public function setSendStatus($mine_id, $situation_journal_id, $xml_send_type_id, $date_time, $status_id, $situation_journal_send_status_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'setSendStatus. Начало метода';

        try {
            /**
             * Генерация ключа
             */
            $cache_key = self::buildCacheKeySendStatus($mine_id, $situation_journal_id, $xml_send_type_id);
            $warnings[] = 'setSendStatus. Сгенерировал ключ ' . $cache_key;

            /**
             * Генерация структуры значения кэша и его сохранение
             */
            $cache_value = self::buildCacheStructureSendStatus($mine_id, $situation_journal_id, $xml_send_type_id, $date_time, $status_id, $situation_journal_send_status_id);
            $this->amicum_rSet($cache_key, $cache_value);
            $warnings[] = 'setSendStatus. Значение сохранено в кэш';

            /**
             * Очистка памяти
             */
            unset($cache_value);

        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'setSendStatus. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'setSendStatus. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Название метода: initZone - метод инициализации всех зон всех неустраненных ситуаций
     *
     * Входные необязательные параметры
     * @param $mine_id - идентификатор шахтного поля. Если указать этот параметр, то берет данные для конкретной шахты
     * и добавляет в кэш
     * @param $sql - условие для фильтра. Если указать этот параметр, то  $worker_id не учитывается!!!!!
     *
     * @return mixed возвращает true при успешном добавлении в кэш, иначе false
     *
     *
     * @author Якимов М.Н.
     * Created date: on 02.01.2020 13:19
     * @since ver
     */
    public function initZone($mine_id = -1, $sql = '')
    {
        $sql_filter = '';
        if ($mine_id !== -1) {
            $sql_filter .= "situation_journal.mine_id = $mine_id ";
        }

        if ($sql !== '') {
            $sql_filter .= ' AND ' . $sql;
        }

        $situation_zones = (new Query())
            ->select([
                'situation_journal_id',
                'edge_id',
                'mine_id',
                'situation_id'
            ])
            ->from('situation_journal_zone')
            ->innerJoin('situation_journal', 'situation_journal.id=situation_journal_zone.situation_journal_id')
            ->where($sql_filter)
            ->andWhere('situation_journal.status_id is null or (situation_journal.status_id!=33 and situation_journal.status_id!=37)')
            ->andWhere('situation_journal.date_time_start > "' . date('Y-m-d H:i:s', strtotime(Assistant::GetDateTimeNow() . '-1day')) . '"')
            ->all();
        if ($situation_zones) {
            foreach ($situation_zones as $zone) {
                $key = self::buildCacheKeyZone($zone['mine_id'], $zone['edge_id'], $zone['situation_journal_id']);
                $date_to_cache[$key] = $zone;
            }
            $this->amicum_mSet($date_to_cache);
            return true;
        }
        return false;
    }

    /**
     * Название метода: initSendStatus - метод инициализации всех статусов отправки всех неустраненных ситуаций
     *
     * Входные необязательные параметры
     * @param $mine_id - идентификатор шахтного поля. Если указать этот параметр, то берет данные для конкретной шахты
     * и добавляет в кэш
     * @param $sql - условие для фильтра. Если указать этот параметр, то  $worker_id не учитывается!!!!!
     *
     * @return mixed возвращает true при успешном добавлении в кэш, иначе false
     *
     *
     * @author Якимов М.Н.
     * Created date: on 02.01.2020 13:19
     * @since ver
     */
    public function initSendStatus($mine_id = -1, $sql = '')
    {
        $sql_filter = '';
        if ($mine_id !== -1) {
            $sql_filter .= "situation_journal.mine_id = $mine_id ";
        }

        if ($sql !== '') {
            $sql_filter .= ' AND ' . $sql;
        }

        $send_statuses = (new Query())
            ->select([
                'mine_id',
                'situation_journal_send_status.id as situation_journal_send_status_id',
                'situation_journal_id',
                'xml_send_type_id',
                'situation_journal_send_status.status_id as status_id',
                'situation_journal_send_status.date_time as date_time'
            ])
            ->from('situation_journal_send_status')
            ->innerJoin('situation_journal', 'situation_journal.id=situation_journal_send_status.situation_journal_id')
            ->where($sql_filter)
            ->andWhere('situation_journal.status_id is null or (situation_journal.status_id!=33 and situation_journal.status_id!=37)')
            ->andWhere('situation_journal.date_time_start > "' . date('Y-m-d H:i:s', strtotime(Assistant::GetDateTimeNow() . '-1day')) . '"')
            ->all();
        if ($send_statuses) {
            foreach ($send_statuses as $send_status) {
                $key = self::buildCacheKeySendStatus($send_status['mine_id'], $send_status['situation_journal_id'], $send_status['xml_send_type_id']);
                $date_to_cache[$key] = $send_status;
            }
            $this->amicum_mSet($date_to_cache);
            return true;
        }
        return false;
    }

    /**
     * Название метода: removeAll() - Метод полного удаления кэша ситуаций. Очищает все кэши связанные с ситуациями
     * Назначение метода: Метод полного удаления кэша ситуаций. Очищает все кэши связанные с ситуациями, а именно:
     *    -- Situation
     *    -- SituatuionEvent
     *    -- Zone
     * @param $mine_id - ключ шахты
     * @example (new SituationCacheController())->removeAll();
     *
     * @author Якимов М.Н.
     * Created date: on 02.01.2020 13:19
     * @since ver
     */
    public function removeAll($mine_id = '*')
    {
        $situation_keys = $this->redis_cache->scan(0, 'MATCH', self::$situation_key . ':' . $mine_id . ':*', 'COUNT', '10000000')[1];
        if ($situation_keys)
            $this->amicum_mDel($situation_keys);

        $zone_keys = $this->redis_cache->scan(0, 'MATCH', self::$zone_key . ':' . $mine_id . ':*', 'COUNT', '10000000')[1];
        if ($zone_keys)
            $this->amicum_mDel($zone_keys);

        $event_situation_keys = $this->redis_cache->scan(0, 'MATCH', self::$situation_event_key . ':' . $mine_id . ':*', 'COUNT', '10000000')[1];
        if ($event_situation_keys)
            $this->amicum_mDel($event_situation_keys);

        $send_status_keys = $this->redis_cache->scan(0, 'MATCH', self::$send_status_key . ':' . $mine_id . ':*', 'COUNT', '10000000')[1];
        if ($send_status_keys)
            $this->amicum_mDel($send_status_keys);
    }

    //  amicum_flushall - метод очистки кеша ситуаций
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
     * Получение списка ситуаций из кэша
     * В параметрах задаётся маска для поиска ключей
     * @param string $mine_id - ключ шахты
     * @param string $situation_id - клюс типа ситуации
     * @param string $situation_journal_id - ключ журнала ситуации
     * @return array
     */
    public function getSituationList($mine_id = '*', $situation_id = '*', $situation_journal_id = '*')
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'getSituationList. Начало метода';

        try {
            /**
             * Поиск ключей в кэше
             */
            $cache_key_filter = self::buildCacheKeySituation($mine_id, $situation_id, $situation_journal_id);
            $keys = $this->redis_cache->scan(0, 'MATCH', $cache_key_filter, 'COUNT', '10000000')[1];

            /**
             * Получение значений из кэша
             * Если в кэше не найдены ключи или по ключам не найдено ситуаций,
             * то статус выполнения ставится в 0
             */
            if ($keys) {
                $situations = $this->amicum_mGet($keys);
                if ($situations) {
                    $result = $situations;
                    $warnings[] = 'getSituationList. Получил ситуации из кэша';
                } else {
                    $status = 0;
                    $errors[] = "getSituationList. В кэше не найдены ситуации с параметрами
                    mine_id $mine_id, situation_id $situation_id, situation_journal_id $situation_journal_id";
                }
            }

        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'getSituationList. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'getSituationList. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * getEventSituationList - Получение событий ситуаций из кэша
     * В параметрах задаётся маска для поиска ключей
     * @param string $mine_id - ключ шахты
     * @param string $event_journal_id - клюс журнала событий
     * @param string $situation_journal_id - ключ журнала ситуации
     * @return array
     */
    public function getEventSituationList($mine_id = '*', $event_journal_id = '*', $situation_journal_id = '*')
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'getEventSituationList. Начало метода';

        try {
            /**
             * Поиск ключей в кэше
             */
            $cache_key_filter = self::buildCacheKeySituationEvent($mine_id, $event_journal_id, $situation_journal_id);
            $keys = $this->redis_cache->scan(0, 'MATCH', $cache_key_filter, 'COUNT', '10000000')[1];

            /**
             * Получение значений из кэша
             * Если в кэше не найдены ключи или по ключам не найдено ситуаций,
             * то статус выполнения ставится в 0
             */
            if ($keys) {
                $events_situations = $this->amicum_mGet($keys);
                if ($events_situations) {
                    $result = $events_situations;
                    $warnings[] = 'getEventSituationList. Получил список событий ситуации из кэша';
//                    $warnings[] = $events_situations;
                } else {
                    $status = 0;
                    $errors[] = "getEventSituationList. В кэше не найдены события ситуаций с параметрами
                    mine_id $mine_id, event_journal_id $event_journal_id, situation_journal_id $situation_journal_id";
                }
            }

        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'getEventSituationList. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'getEventSituationList. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Получение списка Зон ситуаций из кэша
     * В параметрах задаётся маска для поиска ключей
     * @param string $mine_id - ключ шахты
     * @param string $edge_id - клюс выработки в которой произошла ситуация
     * @param string $situation_journal_id - ключ журнала ситуации
     * @return array
     */
    public function getZoneList($mine_id = '*', $edge_id = '*', $situation_journal_id = '*')
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'getZoneList. Начало метода';

        try {
            /**
             * Поиск ключей в кэше
             */
            $cache_key_filter = self::buildCacheKeyZone($mine_id, $edge_id, $situation_journal_id);
            $keys = $this->redis_cache->scan(0, 'MATCH', $cache_key_filter, 'COUNT', '10000000')[1];

            /**
             * Получение значений из кэша
             * Если в кэше не найдены ключи или по ключам не найдены зоны ситуаций,
             * то статус выполнения ставится в 0
             */
            if ($keys) {
                $zones = $this->amicum_mGet($keys);
                if ($zones) {
                    $result = $zones;
                    $warnings[] = 'getZoneList. Получил зоны ситуации из кэша';
                } else {
                    $status = 0;
                    $errors[] = "getZoneList. В кэше не найдены зоны ситуации mine_id $mine_id, edge_id $edge_id, situation_journal_id $situation_journal_id";
                }
            }

        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'getZoneList. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'getZoneList. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Получение списка Статусов отправки из кэша
     * @param $mine_id -   идентификатор шахты
     * @param $situation_journal_id -   ключ журнала ситуаций
     * @param $xml_send_type_id -   идентификатортипа отправки (смс/email)
     * @return array
     */
    public function getSendStatusList($mine_id = '*', $situation_journal_id = '*', $xml_send_type_id = '*')
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = array();
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'getSendStatusList. Начало метода';

        try {
            /**
             * Поиск ключей в кэше
             */
            $cache_key_filter = self::buildCacheKeySendStatus($mine_id, $situation_journal_id, $xml_send_type_id);
            $keys = $this->redis_cache->scan(0, 'MATCH', $cache_key_filter, 'COUNT', '10000000')[1];

            /**
             * Получение значений из кэша
             * Если в кэше не найдены ключи или по ключам не найдены статусы отправки ситуаций,
             * то статус выполнения ставится в 0
             */
            if ($keys) {
                $send_statuses = $this->amicum_mGet($keys);
                if ($send_statuses) {
                    $result = $send_statuses;
                    $warnings[] = 'getSendStatusList. Получил статусы отправки из кэша';
                } else {
                    $status = 0;
                    $errors[] = "getSendStatusList. В кэше не найдены статусы отправки
                    mine_id $mine_id, situation_journal_id $situation_journal_id, xml_send_type_id $xml_send_type_id";
                }
            }

        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'getSendStatusList. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'getZoneList. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Получение ситуации из кэша
     * @param $mine_id -   идентификатор шахты
     * @param $situation_id -   идентификатор ситуации
     * @param $situation_journal_id -   идентификатор журнала ситуаций
     * @return array
     */
    public function getSituation($mine_id, $situation_id, $situation_journal_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'getSituation. Начало метода';

        try {
            /**
             * Генерация ключа
             */
            $cache_key = self::buildCacheKeySituation($mine_id, $situation_id, $situation_journal_id);
            $warnings[] = 'getSituation. Сгенерировал ключ ' . $cache_key;

            /**
             * Получение значения
             */
            $result = $this->amicum_rGet($cache_key);

        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'getSituation. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'getSituation. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * getEventSituation - Получение собятия ситуации из кэша
     * @param $mine_id -   идентификатор шахты
     * @param $event_journal_id -   идентификатор журнала событий
     * @param $situation_journal_id -   идентификатор журнала ситуаций
     * @return array
     */
    public function getEventSituation($mine_id, $event_journal_id, $situation_journal_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'getEventSituation. Начало метода';

        try {
            /**
             * Генерация ключа
             */
            $cache_key = self::buildCacheKeySituationEvent($mine_id, $event_journal_id, $situation_journal_id);
            $warnings[] = 'getEventSituation. Сгенерировал ключ ' . $cache_key;

            /**
             * Получение значения
             */
            $result = $this->amicum_rGet($cache_key);

        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'getEventSituation. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'getEventSituation. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Получение зоны ситуации из кэша
     * @param $mine_id -   идентификатор шахты
     * @param $edge_id -   идентификатор выработки в которой произошла ситуация
     * @param $situation_journal_id -   идентификатор журнала ситуаций
     * @return array
     */
    public function getZone($mine_id, $edge_id, $situation_journal_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'getZone. Начало метода';

        try {
            /**
             * Генерация ключа
             */
            $cache_key = self::buildCacheKeyZone($mine_id, $edge_id, $situation_journal_id);
            $warnings[] = 'getZone. Сгенерировал ключ ' . $cache_key;

            /**
             * Получение значения
             */
            $result = $this->amicum_rGet($cache_key);

        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'getZone. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'getZone. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Получение статуса отправки из кэша
     * @param $mine_id -   идентификатор шахты
     * @param $situation_journal_id -   ключ журнала ситуаций
     * @param $xml_send_type_id -   идентификатортипа отправки (смс/email)
     * @return array
     */
    public function getSendStatus($mine_id, $situation_journal_id, $xml_send_type_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'getSendStatus. Начало метода';

        try {
            /**
             * Генерация ключа
             */
            $cache_key = self::buildCacheKeySendStatus($mine_id, $situation_journal_id, $xml_send_type_id);
            $warnings[] = 'getSendStatus. Сгенерировал ключ ' . $cache_key;

            /**
             * Получение значения
             */
            $result = $this->amicum_rGet($cache_key);

        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'getSendStatus. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'getSendStatus. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * multiSetSituation - Сохраняет ситуации в кэш массово
     * @param $situations - массив ситуаций
     * mine_id                      - ключ шахты ситуации
     * situation_id                 - ключ ситуации
     * situation_journal_id         - ключ журнала ситуаций
     * @return array
     */
    public function multiSetSituation($situations)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'multiSetSituation. Начало метода';
        try {
            foreach ($situations as $situation) {
                $key = self::buildCacheKeySituation($situation['mine_id'], $situation['situation_id'], $situation['situation_journal_id']);
                $situation = self::buildCacheStructureSituation(
                    $situation['situation_journal_id'],
                    $situation['situation_id'],
                    $situation['date_time'],
                    $situation['main_id'],
                    $situation['situation_status_id'],
                    $situation['danger_level_id'],
                    $situation['company_department_id'],
                    $situation['mine_id'],
                    $situation['date_time_start'],
                    $situation['date_time_end']
                );

                $date_to_cache[$key] = $situation;
            }
            $result = $this->amicum_mSet($date_to_cache);

        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'multiSetSituation. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'multiSetSituation. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * multiSetSendStatus - Сохраняет статусы ситуаций в кэш массово
     * @param $xml_send_type_id - ключ сбособа отправки (sms/email)
     * @param $status_send_array - массив статусов отправки и ключ журнала ситуаций
     * {
     *      'situation_journal_id'=>$situation_journal_id ,
     *      'status_id' => $status_id,
     *      'date_time' => $date_time,
     *      'situation_journal_send_status_id' => $situation_journal_send_status_id
     * }
     * @param $mine_id - ключ шахного поля
     * @return array
     */
    public function multiSetSendStatus($mine_id, $status_send_array, $xml_send_type_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'multiSetSendStatus. Начало метода';
        try {
            foreach ($status_send_array as $status_send) {
                $key = self::buildCacheKeySendStatus($mine_id, $status_send['situation_journal_id'], $xml_send_type_id);
                $status_send_item = self::buildCacheStructureSendStatus($mine_id, $status_send['situation_journal_id'], $xml_send_type_id, $status_send['date_time'], $status_send['status_id'], $status_send['situation_journal_send_status_id']);
                $date_to_cache[$key] = $status_send_item;
            }
            $result = $this->amicum_mSet($date_to_cache);

        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'multiSetSendStatus. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'multiSetSendStatus. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * multiSetZone - Сохраняет зоны ситуаций в кэш массово
     * @param $situation_journal_id - ключ журанал ситуаций
     * @param $situation_id - ключ типа ситуаций
     * @param $zones - массив зон содержащий edge_id
     * @param $mine_id - ключ шахного поля
     * @return array
     */
    public function multiSetZone($situation_journal_id, $zones, $mine_id, $situation_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'multiSetZone. Начало метода';
        try {
            foreach ($zones as $edge_id) {
                $key = self::buildCacheKeyZone($mine_id, $edge_id, $situation_journal_id);
                $zone_item = self::buildCacheStructureZone($mine_id, $edge_id, $situation_journal_id, $situation_id);
                $date_to_cache[$key] = $zone_item;
            }
            $result = $this->amicum_mSet($date_to_cache);

        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'multiSetZone. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'multiSetZone. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * setZone - Сохраняет зону ситуации в кэш разово
     * @param $situation_journal_id - ключ журанал ситуаций
     * @param $situation_id - ключ типа ситуаций
     * @param $edge_id - массив зон содержащий edge_id
     * @param $mine_id - ключ шахного поля
     * @return array
     */
    public function setZone($situation_journal_id, $edge_id, $mine_id, $situation_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();                                                                                              //массив предупреждений
        $warnings[] = 'setZone. Начало метода';
        try {
            /**
             * Генерация ключа
             */
            $cache_key = self::buildCacheKeyZone($mine_id, $edge_id, $situation_journal_id);
            $warnings[] = 'setZone. Сгенерировал ключ ' . $cache_key;

            /**
             * Генерация структуры значения кэша и его сохранение
             */
            $cache_value = self::buildCacheStructureZone($mine_id, $edge_id, $situation_journal_id, $situation_id);
            $this->amicum_rSet($cache_key, $cache_value);
            $warnings[] = 'setZone. Значение сохранено в кэш';

            /**
             * Очистка памяти
             */
            unset($cache_value);
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'setZone. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'setZone. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Обновление определённого поля в кэше ситуаций
     * @param $mine_id -   идентификатор шахты
     * @param $situation_id -   идентификатор ситуации
     * @param $situation_journal_id -   идентификатор журнала ситуации
     * @param $field -   имя поля, которое необходимо изменить
     * @param $value -   значение на которое меняем поле $field
     * @return array
     */
    public function updateSituationCacheValue($mine_id, $situation_id, $situation_journal_id, $field, $value)
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        $warnings[] = 'updateCacheValue. Начало метода';

        try {
            /**
             * Получение ситуаций из кэша
             */
            $situation = false;
            $response = $this->getSituation($mine_id, $situation_id, $situation_journal_id);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $warnings[] = 'updateCacheValue. Значение найдено в кэше';
                $situation = $response['Items'];
            } else {
                $errors[] = $response['errors'];
                throw new \Exception('updateCacheValue. Значение не найдено в кэше');
            }

            /**
             * Обновление и сохранение в кэш
             */
            if ($situation) {
                $situation[$field] = $value;
                $response = $this->setSituation(
                    $situation_journal_id,
                    $situation_id,
                    $situation['date_time'],
                    $situation['main_id'],
                    $situation['situation_status_id'],
                    $situation['danger_level_id'],
                    $situation['company_department_id'],
                    $mine_id,
                    $situation['date_time_start'],
                    $situation['date_time_end']
                );

                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new \Exception('updateCacheValue. Ошибка сохранения ситуации в кэш');
                }

            }

        } catch (Throwable $exception) {
            $errors[] = 'updateCacheValue. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $warnings[] = 'updateCacheValue. Конец метода';
        return array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
    }


    /**
     * deleteOldSituations - Удаление из кэша устаревших ситуаций
     * Устаревшими считаются ситуации, с момента создания которых прошло 4 и более дней.
     * @return array
     */
    public function deleteOldSituations()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("deleteOldSituations");

        try {
            $log->addLog("Начало выполнения метода");

            /**
             * Получение всех ситуаций из кэша
             */
            $cache_key = self::buildCacheKeySituation('*', '*', '*');
            $keys = $this->redis_cache->scan(0, 'MATCH', $cache_key, 'COUNT', '10000000')[1];

            $log->addLog("Нашел ключи в кеше");

            if ($keys) {
                $situations = $this->amicum_mGet($keys);

                $log->addLog("Получил ключи из кеша");

                /**
                 * Проверка даты ситуации. Если оно создано более 3 дней назад, то
                 * пометить его на удаление
                 */
                $curr_date_time = Assistant::GetDateNow();

                $log->addLog('deleteOldSituations. Текущая дата: ' . $curr_date_time);

                $situation_keys_to_delete = array();
                foreach ($situations as $situation) {
                    $delta_time = strtotime($curr_date_time) - strtotime($situation['date_time']);
                    if ($delta_time > 345600) { // Кол-во секунд в 4 днях
                        $count_record++;
                        $situation_keys_to_delete[] = self::buildCacheKeySituation($situation['mine_id'], $situation['situation_id'], $situation['situation_journal_id']);


                        $zone_keys = $this->redis_cache->scan(0, 'MATCH', self::$zone_key . ':' . $situation['mine_id'] . ':*:' . $situation['situation_journal_id'], 'COUNT', '10000000')[1];
                        if ($zone_keys)
                            $this->amicum_mDel($zone_keys);

                        $event_situation_keys = $this->redis_cache->scan(0, 'MATCH', self::$situation_event_key . ':' . $situation['mine_id'] . ':*:' . $situation['situation_journal_id'], 'COUNT', '10000000')[1];
                        if ($event_situation_keys)
                            $this->amicum_mDel($event_situation_keys);

                        $send_status_keys = $this->redis_cache->scan(0, 'MATCH', self::$send_status_key . ':' . $situation['mine_id'] . ':' . $situation['situation_journal_id'] . ':*', 'COUNT', '10000000')[1];
                        if ($send_status_keys)
                            $this->amicum_mDel($send_status_keys);
                    }
                }

                /**
                 * Удаление ситуации из кэша
                 */
                $this->amicum_mDel($situation_keys_to_delete);

                $log->addLog("Удалил ключи из кеша");
                $log->addData($situation_keys_to_delete, '$situation_keys_to_delete', __LINE__);
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Окончание выполнения метода");
        $log->saveLogSynchronization($count_record);

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * deleteSituation - Удаление из кэша ситуации
     * @param $mine_id - ключ шахты
     * @param $situation_journal_id - ключ журнала ситуации
     * @return array
     */
    public function deleteSituation($mine_id, $situation_journal_id, $situation_id)
    {
        $result = array();
        $status = 1;
        $errors = array();
        $warnings = array();

        $warnings[] = 'deleteSituation. Начало метода';

        try {
            /**
             * Получение всех ситуаций из кэша
             */
            $cache_key = self::buildCacheKeySituation($mine_id, $situation_id, $situation_journal_id);

            /**
             * Удаление ситуации из кэша
             */
            $this->amicum_rDel($cache_key);
            $result = true;
            $warnings[] = 'deleteSituation. Удалил следующий ключ:';
            $warnings[] = $cache_key;

        } catch (Throwable $exception) {
            $errors[] = 'deleteSituation. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        return array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * deleteMultiSituation - Удаление из кэша ситуации с поиском ключа
     * @param $mine_id - ключ шахты
     * @param $situation_journal_id - ключ журнала ситуации
     * @return array
     */
    public function deleteMultiSituation($mine_id, $situation_journal_id)
    {
        $result = array();
        $status = 1;
        $errors = array();
        $warnings = array();

        $warnings[] = 'deleteMultiSituation. Начало метода';

        try {
            /**
             * Получение всех ситуаций из кэша
             */
            $cache_key = self::buildCacheKeySituation($mine_id, '*', $situation_journal_id);
            $keys = $this->redis_cache->scan(0, 'MATCH', $cache_key, 'COUNT', '10000000')[1];
            /**
             * Удаление ситуации из кэша
             */
            $this->amicum_mDel($keys);
            $result = true;
            $warnings[] = 'deleteMultiSituation. Удалил следующий ключ:';
            $warnings[] = $cache_key;

        } catch (Throwable $exception) {
            $errors[] = 'deleteMultiSituation. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        return array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * deleteZone - Удаление из кэша Зоны ситуации
     * @param $mine_id - ключ шахты
     * @param $situation_journal_id - ключ журнала ситуации
     * @return array
     */
    public function deleteZone($mine_id, $situation_journal_id, $edge_id)
    {
        $result = array();
        $status = 1;
        $errors = array();
        $warnings = array();

        $warnings[] = 'deleteZone. Начало метода';

        try {
            /**
             * Получение всех зон ситуации из кэша
             */
            $cache_key = self::buildCacheKeyZone($mine_id, $edge_id, $situation_journal_id);

            /**
             * Удаление ситуации из кэша
             */
            $this->amicum_rDel($cache_key);
            $result = true;
            $warnings[] = 'deleteZone. Удалил следующий ключ:';
            $warnings[] = $cache_key;

        } catch (Throwable $exception) {
            $errors[] = 'deleteZone. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        return array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * deleteMultiZone - Удаление из кэша Зоны ситуации с поиском ключа
     * @param $mine_id - ключ шахты
     * @param $situation_journal_id - ключ журнала ситуации
     * @return array
     */
    public function deleteMultiZone($mine_id, $situation_journal_id)
    {
        $result = array();
        $status = 1;
        $errors = array();
        $warnings = array();

        $warnings[] = 'deleteMultiZone. Начало метода';

        try {
            /**
             * Получение всех зон ситуации из кэша
             */
            $cache_key = self::buildCacheKeyZone($mine_id, '*', $situation_journal_id);
            $keys = $this->redis_cache->scan(0, 'MATCH', $cache_key, 'COUNT', '10000000')[1];
            /**
             * Удаление ситуации из кэша
             */
            $this->amicum_mDel($keys);
            $result = true;
            $warnings[] = 'deleteMultiZone. Удалил следующий ключ:';
            $warnings[] = $cache_key;

        } catch (Throwable $exception) {
            $errors[] = 'deleteMultiZone. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        return array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * deleteSituationEvent - Удаление из кэша всех событий ситуации
     * @param $mine_id - ключ шахты
     * @param $situation_journal_id - ключ журнала ситуации
     * @return array
     */
    public function deleteSituationEvent($mine_id, $situation_journal_id, $event_journal_id)
    {
        $result = array();
        $status = 1;
        $errors = array();
        $warnings = array();

        $warnings[] = 'deleteSituationEvent. Начало метода';

        try {
            /**
             * Получение всех событий ситуации из кэша
             */
            $cache_key = self::buildCacheKeySituationEvent($mine_id, $event_journal_id, $situation_journal_id);

            /**
             * Удаление события ситуации из кэша
             */
            $this->amicum_rDel($cache_key);
            $result = true;
            $warnings[] = 'deleteSituationEvent. Удалил следующий ключ:';
            $warnings[] = $cache_key;

        } catch (Throwable $exception) {
            $errors[] = 'deleteSituationEvent. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        return array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * deleteMultiSituationEvent - Удаление из кэша всех событий ситуации с поиском ключа
     * @param $mine_id - ключ шахты
     * @param $situation_journal_id - ключ журнала ситуации
     * @return array
     */
    public function deleteMultiSituationEvent($mine_id, $situation_journal_id)
    {
        $result = array();
        $status = 1;
        $errors = array();
        $warnings = array();

        $warnings[] = 'deleteMultiSituationEvent. Начало метода';

        try {
            /**
             * Получение всех событий ситуации из кэша
             */
            $cache_key = self::buildCacheKeySituationEvent($mine_id, '*', $situation_journal_id);
            $keys = $this->redis_cache->scan(0, 'MATCH', $cache_key, 'COUNT', '10000000')[1];
            /**
             * Удаление события ситуации из кэша
             */
            $this->amicum_mDel($keys);
            $result = true;
            $warnings[] = 'deleteMultiSituationEvent. Удалил следующий ключ:';
            $warnings[] = $cache_key;

        } catch (Throwable $exception) {
            $errors[] = 'deleteMultiSituationEvent. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        return array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * deleteMultiSendStatus - Удаление из кэша всех статусов отправки ситуации с поиском ключа
     * @param $mine_id - ключ шахты
     * @param $situation_journal_id - ключ журнала ситуации
     * @return array
     */
    public function deleteMultiSendStatus($mine_id, $situation_journal_id)
    {
        $result = array();
        $status = 1;
        $errors = array();
        $warnings = array();

        $warnings[] = 'deleteMultiSendStatus. Начало метода';

        try {
            /**
             * Создание ключа для поиска
             */
            $cache_key = self::buildCacheKeySendStatus($mine_id, '*', '*');
            $keys = $this->redis_cache->scan(0, 'MATCH', $cache_key, 'COUNT', '10000000')[1];
            /**
             * Удаление события ситуации из кэша
             */
            $this->amicum_mDel($keys);
            $result = true;
            $warnings[] = 'deleteMultiSendStatus. Удалил следующий ключ:';
            $warnings[] = $cache_key;

        } catch (Throwable $exception) {
            $errors[] = 'deleteMultiSendStatus. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        return array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * deleteSendStatus - Удаление из кэша статуса отправки ситуации
     * @param $mine_id -   идентификатор шахты
     * @param $situation_journal_id -   ключ журнала ситуаций
     * @param $xml_send_type_id -   идентификатортипа отправки (смс/email)
     * @return array
     */
    public function deleteSendStatus($mine_id, $situation_journal_id, $xml_send_type_id)
    {
        $result = array();
        $status = 1;
        $errors = array();
        $warnings = array();

        $warnings[] = 'deleteSendStatus. Начало метода';

        try {
            /**
             * Получение всех ситуаций из кэша
             */
            $cache_key = self::buildCacheKeySendStatus($mine_id, $situation_journal_id, $xml_send_type_id);

            /**
             * Удаление ситуации из кэша
             */
            $this->amicum_rDel($cache_key);
            $result = true;
            $warnings[] = 'deleteSendStatus. Удалил следующий ключ:';
            $warnings[] = $cache_key;

        } catch (Throwable $exception) {
            $errors[] = 'deleteSendStatus. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        return array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
    }


    /**
     * buildCacheKeySituation - Метод для построения ключа Ситуаций, по которому ситуация записывется в кэш
     * @param $mine_id -   идентификатор шахты
     * @param $situation_id -   идентификатор ситуации
     * @param $situation_journal_id -   идентификатор журнала ситуации
     * @return string   сформированный ключ
     */
    public static function buildCacheKeySituation($mine_id, $situation_id, $situation_journal_id)
    {
        return self::$situation_key . ':' . $mine_id . ':' . $situation_id . ':' . $situation_journal_id;
    }

    /**
     * buildCacheKeyZone - Метод для построения ключа Зон ситуаций, по которому ситуация записывется в кэш
     * @param $mine_id -   идентификатор шахты
     * @param $edge_id -   ключ выработки, которая находится в зоне действия ситуации
     * @param $situation_journal_id -   идентификатор журнала ситуации
     * @return string   сформированный ключ
     */
    public static function buildCacheKeyZone($mine_id, $edge_id, $situation_journal_id)
    {
        return self::$zone_key . ':' . $mine_id . ':' . $edge_id . ':' . $situation_journal_id;
    }

    /**
     * buildCacheKeySituationEvent - Метод для построения ключа событий ситуации
     * @param $mine_id -   идентификатор шахты
     * @param $event_journal_id -   ключ журнала события
     * @param $situation_journal_id -   идентификатор журнала ситуации
     * @return string   сформированный ключ
     */
    public static function buildCacheKeySituationEvent($mine_id, $event_journal_id, $situation_journal_id)
    {
        return self::$situation_event_key . ':' . $mine_id . ':' . $situation_journal_id . ':' . $event_journal_id;
    }

    /**
     * buildCacheKeySendStatus - Метод для построения ключа статуса отправки ситуации при оповещении
     * @param $mine_id -   идентификатор шахты
     * @param $situation_journal_id -   ключ журнала ситуаций
     * @param $xml_send_type_id -   идентификатортипа отправки (смс/email)
     * @return string   сформированный ключ
     */
    public static function buildCacheKeySendStatus($mine_id, $situation_journal_id, $xml_send_type_id)
    {
        return self::$send_status_key . ':' . $mine_id . ':' . $situation_journal_id . ':' . $xml_send_type_id;
    }

    /**
     * Создаёт структуру ситуации для записи в кэш
     * @param $situation_journal_id -   идентификатор ключа ситуации в журнале ситуаци
     * @param $situation_id -   идентификатор ситуации
     * @param $date_time -   дата и время ситуации
     * @param $main_id -   идентификатор объекта к которому относится ситуация
     * @param $situation_status_id -   идентификатор статуса ситуации
     * @param $danger_level_id -   ключ уровня опасности ситуации (риск)
     * @param $company_department_id -   ключ подразделения/ситуации на котором произошла ситуация
     * @param $mine_id -   идентификатор шахты
     * @param $date_time_start -   дата начала ситуации
     * @param $date_time_end -   дата окончания ситуации
     * @return array
     */
    public static function buildCacheStructureSituation(
        $situation_journal_id, $situation_id, $date_time, $main_id, $situation_status_id,
        $danger_level_id, $company_department_id, $mine_id, $date_time_start, $date_time_end

    )
    {
        $cache_struct = array();
        $cache_struct['situation_journal_id'] = $situation_journal_id;
        $cache_struct['situation_id'] = (int)$situation_id;
        $cache_struct['date_time'] = $date_time;
        $cache_struct['main_id'] = $main_id;
        $cache_struct['situation_status_id'] = $situation_status_id;
        $cache_struct['danger_level_id'] = $danger_level_id;
        $cache_struct['company_department_id'] = $company_department_id;
        $cache_struct['mine_id'] = $mine_id;
        $cache_struct['date_time_start'] = $date_time_start;
        $cache_struct['date_time_end'] = $date_time_end;
        return $cache_struct;
    }

    /**
     * buildCacheStructureSituationEvent - Создаёт структуру ситуации для записи в кэш
     * @param $situation_journal_id -   идентификатор ключа ситуации в журнале ситуаци
     * @param $event_journal_id -   идентификатор журнала событий
     * @param $main_id -   идентификатор объекта к которому относится событие
     * @param $event_id -   идентификатор события
     * @param $mine_id -   идентификатор шахты
     * @param $date_time_start -   дата начала события
     * @param $date_time_end -   дата окончания события
     * @param $event_status_id -   статус события (устранено/устраняется и т.д.)
     * @param $value_status_id -   статус значения события (нормальное/аварийное)
     * @return array
     */
    public static function buildCacheStructureSituationEvent(
        $situation_journal_id, $event_journal_id, $main_id, $event_id, $mine_id,
        $date_time_start, $date_time_end,
        $event_status_id, $value_status_id
    )
    {
        $cache_struct = array();
        $cache_struct['main_id'] = $main_id;
        $cache_struct['situation_journal_id'] = $situation_journal_id;
        $cache_struct['event_journal_id'] = $event_journal_id;
        $cache_struct['event_id'] = $event_id;
        $cache_struct['event_status_id'] = $event_status_id;
        $cache_struct['value_status_id'] = $value_status_id;
        $cache_struct['mine_id'] = $mine_id;
        $cache_struct['date_time_start'] = $date_time_start;
        $cache_struct['date_time_end'] = $date_time_end;
        return $cache_struct;
    }

    /**
     * Создаёт структуру Зон ситуации для записи в кэш
     * @param $edge_id -   идентификатор выработки, на которой произошло ситуация
     * @param $mine_id -   идентификатор шахты, на которой произошло ситуация
     * @param $situation_journal_id -   ключ ситуации в журнале
     * @param $situation_id -   ключ типа ситуации
     * @return array
     */
    public static function buildCacheStructureZone($mine_id, $edge_id, $situation_journal_id, $situation_id)
    {
        $cache_struct = array();
        $cache_struct['edge_id'] = $edge_id;
        $cache_struct['mine_id'] = $mine_id;
        $cache_struct['situation_journal_id'] = $situation_journal_id;
        $cache_struct['situation_id'] = $situation_id;
        return $cache_struct;
    }

    /**
     * Создаёт структуру статуса отправки ситуации при оповещении для записи в кэш
     * @param $mine_id -   идентификатор шахты, на которой произошло ситуация
     * @param $situation_journal_id -   ключ журнала ситуации
     * @param $xml_send_type_id -   ключ типа отправки собщения
     * @param $status_id -   ключ статуса
     * @param $date_time -   дата установки статуса
     * @return array
     */
    public static function buildCacheStructureSendStatus($mine_id, $situation_journal_id, $xml_send_type_id, $date_time, $status_id, $situation_journal_send_status_id)
    {
        $cache_struct = array();
        $cache_struct['mine_id'] = $mine_id;
        $cache_struct['situation_journal_id'] = $situation_journal_id;
        $cache_struct['xml_send_type_id'] = $xml_send_type_id;
        $cache_struct['status_id'] = $status_id;
        $cache_struct['date_time'] = $date_time;
        $cache_struct['situation_journal_send_status_id'] = $situation_journal_send_status_id;
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

//        if (REDIS_REPLICA_MODE === true) {
//            $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $this->redis_cache->port, 'mset', $data);
//        }

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

//        if (REDIS_REPLICA_MODE === true) {
//            $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $this->redis_cache->port, 'set', $data);
//        }

        return $mset;
    }

    public function amicum_repRedis($hostname, $port, $command_redis, $data)
    {
        $errors = array();
        $warnings = array();
        $status = 1;
        $result = array();

        $warnings[] = 'amicum_repRedis. Начало метода';
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
     */
    public function amicum_mDel($keys)
    {
        //Todo: сделать проверку в будущем на возвращаемые из redis
        if ($keys) {
            foreach ($keys as $key) {
                $key1 = array();
                $key1[] = $key;
                $value = $this->redis_cache->executeCommand('del', $key1);

//                if (REDIS_REPLICA_MODE === true) {
//                    $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $this->redis_cache->port, 'del', $key1);
//                }
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
//        if (REDIS_REPLICA_MODE === true) {
//            $this->amicum_repRedis(REDIS_REPLICA_HOSTNAME, $this->redis_cache->port, 'del', $key1);
//        }
    }
}
