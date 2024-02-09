<?php

namespace frontend\controllers\positioningsystem;

use frontend\models\ForbiddenTime;
use frontend\models\ForbiddenZapret;
use frontend\models\ForbiddenZapretStatus;
use frontend\models\ForbiddenZone;
use Throwable;
use Yii;
use yii\db\Exception;
use yii\db\Query;
use yii\web\Controller;

class ForbiddenZoneController extends Controller
{

    // КОНТРОЛЬ ЗАПРЕТНЫХ ЗОН

    // DeactivateZone               - деактивируем всю зону
    // ChangeStatusZapret           - Изменяем статус запрета (активный/не активный)
    // GetZones                     - Получаем список всех запретов
    // GetZone                      - Получаем конкретную зону
    // SaveZone                     - Редактирование запретной зоны.
    // DeleteForbiddenZone          - Удаляет запретную зону по ее ID.
    // GetForbiddenZoneByEdge       - Метод вывода списка (массив) названий активных запретных зон, которые находятся на определенной выработке
    // GetGournalZapret             - получить журнал запретных зон
    // GetActiveForbiddenZoneByEdge - Выводит список названий активных запретных зон, которые находятся на определенной выработке, а так же делает расчет статуса запрета


    const STATUS_ACTIVE = 1;                // активный запрет
    const NOT_ACTIVE = 19;                  // не активный запрет
    const ZONE_TIMING = 2;                  // временной запрет
    const ZONE_CONSTANT = 1;                // постоянный запрет


    /**
     * Метод DeleteForbiddenZone() - Удаляет запретную зону по ее ID.
     * @param null $data_post - Параметр запретной зоны - ID
     * @return array Сообщение, что статус маршрута изменен
     * @package frontend\controllers\positioningsystem
     * @example
     *
     * http://localhost/read-manager-amicum?controller=positioningsystem\ForbiddenZone&method=DeleteForbiddenZone&subscribe=&data={%22zone_id%22:85}
     *
     *
     * @author Якимов М.Н.
     * Created date: on 15.08.2019 10:00
     */

    public static function DeleteForbiddenZone($data_post = NULL)
    {
        // Стартовая отладочная информация
        $method_name = 'DeleteForbiddenZone';                                                                             // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                              // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));                       // время начала выполнения метода
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
            $warnings[] = 'DeleteForbiddenZone. Начало метода';
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('DeleteForbiddenZone. Не переданы входные параметры');
            }
            $warnings[] = 'DeleteForbiddenZone. Данные успешно переданы';
            $warnings[] = 'DeleteForbiddenZone. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = 'DeleteForbiddenZone. Декодировал входные параметры';
            if (
            !property_exists($post_dec, 'zone_id'))                                                         //
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }

            $warnings[] = 'DeleteForbiddenZone. Данные с фронта получены';
            $zone_id = $post_dec->zone_id;

            ForbiddenZone::deleteAll(['id' => $zone_id]);

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
     * Метод GetForbiddenZoneByEdge() - Выводит список названий активных запретных зон, которые находятся на определенной выработке
     * @param null $data_post - Параметр вырабоки (Edge) - ID
     * @return array Массив названий запретных зон на определенной выработке
     *          []
     *              zone_id         - ключ запретной зоны
     *              zone_title      - наименование запретной зоны
     * @package frontend\controllers\positioningsystem
     * @example
     *
     * http://localhost/read-manager-amicum?controller=positioningsystem\ForbiddenZone&method=GetForbiddenZoneByEdge&subscribe=&data={%22edge_id%22:22141}
     *
     *
     * @author Якимов М.Н.
     * Created date: on 16.08.2019 13:00
     */
    public static function GetForbiddenZoneByEdge($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $result = array();                                                                                        // Промежуточный результирующий массив

        $warnings[] = 'Начало работы метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('GetForbiddenZoneByEdge. Данные с фронта не получены');
            }
            $warnings[] = 'GetForbiddenZoneByEdge. Данные успешно переданы';
            $warnings[] = 'GetForbiddenZoneByEdge. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GetForbiddenZoneByEdge. Декодировал входные параметры';
            if
            (
            !(property_exists($post_dec, 'edge_id') ||
                $post_dec->edge_id != "")
            )                                                                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('GetForbiddenZoneByEdge. Переданы некорректные входные параметры');
            }
            $warnings[] = 'GetForbiddenZoneByEdge.Данные с фронта получены';
            $edge_id = $post_dec->edge_id;

            $result = (new Query())
                ->select('
                    forbidden_zone.id as zone_id,
                    forbidden_zone.title as zone_title
                ')
                ->from('forbidden_edge')
                ->innerJoin('forbidden_zone', 'forbidden_zone.id=forbidden_edge.forbidden_zone_id')
                ->innerJoin('forbidden_zapret', 'forbidden_zapret.forbidden_zone_id=forbidden_zone.id')
                ->where(['forbidden_edge.edge_id' => $edge_id])
                ->andWhere(['forbidden_zapret.status_id' => self::STATUS_ACTIVE])
                ->limit(5000)
                ->groupBy('zone_id, zone_title')
                ->orderBy('forbidden_zone.title')
                ->all();


        } catch (Throwable $exception) {
            $errors[] = 'GetForbiddenZoneByEdge. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }


        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeactivateZone() - Изменяем статус зоны ( активная/не активная)
     *
     * @param null $data_post -
     * zone_id              - ключ зоны
     * status_id            - новый статус зоны
     * @return array Сообщение, что статус запрета изменен
     *
     * @package frontend\controllers\positioningsystem
     * @example  http://localhost/read-manager-amicum?controller=positioningsystem\ForbiddenZone&method=DeactivateZone&subscribe=&data={%22zone_id%22:81,"status_id":19}
     *
     *
     * @author Якимов М.Н.
     * Created date: on 16.08.2019 15:00
     */

    public static function DeactivateZone($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $result = array();                                                                                        // Промежуточный результирующий массив

        $warnings[] = 'Начало работы метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('DeactivateZone. Данные с фронта не получены');
            }
            $warnings[] = 'DeactivateZone. Данные успешно переданы';
            $warnings[] = 'DeactivateZone. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'DeactivateZone. Декодировал входные параметры';
            if
            (
                !property_exists($post_dec, 'zone_id') ||
                !property_exists($post_dec, 'status_id')
            )                                                                                                     // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('DeactivateZone. Переданы некорректные входные параметры');
            }
            $warnings[] = 'DeactivateZone. Данные с фронта получены';
            $zone_id = $post_dec->zone_id;
            $status_id = $post_dec->status_id;

            $forbidden_zapret = ForbiddenZapret::updateAll(['status_id' => self::NOT_ACTIVE], ['forbidden_zone_id' => $zone_id]);
            $result = $forbidden_zapret;
        } catch (Throwable $exception) {
            $errors[] = 'DeactivateZone. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод ChangeStatusZapret() - Изменяем статус запрета ( активный/не активный)
     *
     * @param null $data_post -
     * zapret_id            - ключ запрета
     * status_id            - новый статус зоны
     * @return array Сообщение, что статус запрета изменен
     *
     * @package frontend\controllers\positioningsystem
     * @example  http://localhost/read-manager-amicum?controller=positioningsystem\ForbiddenZone&method=ChangeStatusZapret&subscribe=&data={%22zapret_id%22:81,"status_id":19}
     *
     *
     * @author Якимов М.Н.
     * Created date: on 16.08.2019 15:00
     */

    public static function ChangeStatusZapret($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $result = array();                                                                                        // Промежуточный результирующий массив

        $warnings[] = 'Начало работы метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('ChangeStatusZapret. Данные с фронта не получены');
            }
            $warnings[] = 'ChangeStatusZapret. Данные успешно переданы';
            $warnings[] = 'ChangeStatusZapret. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'ChangeStatusZapret. Декодировал входные параметры';
            if
            (
                !property_exists($post_dec, 'zapret_id') ||
                !property_exists($post_dec, 'status_id')
            )                                                                                                     // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('ChangeStatusZapret. Переданы некорректные входные параметры');
            }
            $warnings[] = 'ChangeStatusZapret. Данные с фронта получены';
            $zapret_id = $post_dec->zapret_id;
            $status_id = $post_dec->status_id;

            $forbidden_zapret = ForbiddenZapret::findOne(['id' => $zapret_id]);
            if (!$forbidden_zapret) {
                throw new Exception('ChangeStatusZapret. Не существует такого запрета в БД');
            }
            $forbidden_zapret->status_id = $status_id;
            if (!$forbidden_zapret->save()) {                                                                                  //сохранение запрета
                $errors[] = $forbidden_zapret->errors;
                throw new Exception('ChangeStatusZapret. Ошибка при сохранении данных о запрете ForbiddenZapret');
            }
            $session = Yii::$app->session;

            $new_status_zapret = new  ForbiddenZapretStatus();
            $new_status_zapret->status_id = $status_id;
            $new_status_zapret->worker_id = $session['worker_id'];
            $new_status_zapret->forbidden_zapret_id = $zapret_id;
            $new_status_zapret->date_time_create = \backend\controllers\Assistant::GetDateTimeNow();
            if (!$new_status_zapret->save()) {                                                                                  //сохранение запрета
                $errors[] = $new_status_zapret->errors;
                throw new Exception('ChangeStatusZapret. Ошибка при сохранении данных о запрете ForbiddenZapretStatus');
            }

        } catch (Throwable $exception) {
            $errors[] = 'ChangeStatusZapret. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Метод SaveZone - Редактирование запретной зоны.
     * @param null $data_post JSON со структурой:
     *              zone:
     *                  zone_id           "3"                                         - ключ зоны
     *                  zone_title        "постоянная не актуальная "                 - наименование зоны
     *                  mine_id           "290"                                       - ключ шахтного поля в котором есть запрет
     *                  mine_title        "Заполярная-2"                              - наименование шахтного поля
     *                  zaprets                                                        - список запретов (хранит время)
     *                      {forbidden_zapret_id}                                       - ключ запрета
     *                          forbidden_zapret_id             "6"                         - ключ запрета
     *                          forbidden_zapret_status_id      "19"                        - статус запрета (1 актуальный или 19 нет)
     *                          forbidden_type_id               "2"                         - тип запрета (2 временный / 1 постоянный)
     *                          worker_id                       "2002270"                   - ключ работника создавшего запрет
     *                          full_name                       ""                          - ФИО создавшего статус
     *                          date_time_create                "2020-02-26 00:00:00"       - дата и время создания запрета
     *                          description                     "временный"                 - описание запрета
     *                          forbidden_zapret_last_date_time                             - дата создания текущего состояние запрета (поле снятие запрета)
     *                          forbidden_zapret_last_status_id                             - текущий ключ статуса запрета (должен совпадать с forbidden_zapret_status_id)
     *                          forbidden_zapret_last_status_title                          - текущее наименование статуса запрета (должен совпадать с forbidden_zapret_status_id)
     *                          forbidden_zapret_last_worker_id                             - кто изменил статус запрета (кем снят запрет)
     *                          forbidden_times                                             - список временных запретов
     *                              {forbidden_time_id}                                         - ключ времени запрета
     *                                  forbidden_time_id           "46"                            - ключ времени запрета
     *                                  date_start                  "2020-02-24 00:00:00"           - дата начала действия запрета
     *                                  date_end                    "2020-02-25 00:00:00"           - дата окончания действия запрета
     *                                  status_id                   "1"                             - статус временного запрета (действует или нет)
     *                  forbidden_zapret_statuses                                   - список истории изменения статуса запрета
     *                      {forbidden_zapret_status_id}                                - ключ истории изменения статуса запрета
     *                          forbidden_zapret_status_id                                  - ключ истории изменения статуса запрета
     *                          status_id                                                   - статус запрета
     *                          date_time_create                                            - дата изменения статуса
     *                          status_title                                                - наименование статуса
     *                          worker_id                                                   - ключ работника изменившего статус
     *                          forbidden_zapret_last_full_name                             - ФИО изменившего статус
     *                  edges
     *                      {forbidden_edge_id}                                         - ключ зоны запрета
     *                          forbidden_edge_id               "9"                         - ключ зоны запрета
     *                          edge_id                         "22141"                     - ключ выработки
     *                          place_id                        "6187"                      - ключ места
     *                          place_title                     "Груз. ветвь кл. ств.."     - наименование места
     *                                              status
     *                                              errors
     *                                              warnings
     * @return array  - возвращает стандартный массив
     *
     * @example  http://localhost/read-manager-amicum?controller=positioningsystem\ForbiddenZone&method=SaveZone&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 19.08.2019 10:20
     */
    public static function SaveZone($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                           // Промежуточный результирующий массив
        $warnings[] = 'SaveZone. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('SaveZone. Данные с фронта не получены');
            }
            $warnings[] = 'SaveZone. Данные успешно переданы';
            $warnings[] = 'SaveZone. Входной массив данных' . $data_post;

            $post_dec = json_decode($data_post);
            // Декодируем входной массив данных
            $warnings[] = 'SaveZone. Декодировал входные параметры';

            if (
            property_exists($post_dec, 'zone')
            ) {
                $warnings[] = "SaveZone. Ключ запретной зоны передано правильно";
            } else {
                throw new Exception('SaveZone. Ключ запретной зоны передано неправильно или пустое');
            }
            $zone = $post_dec->zone;
            $zone_id = $zone->zone_id;
            $forbidden_zone = ForbiddenZone::deleteAll(['id' => $zone_id]);
//            if (!$forbidden_zone) {
//                throw new Exception('SaveZone. Запретной зоны с таким ID не существует');
//            }
//            $warnings[] = "SaveZone. Запретная зона найдена в БД";
//            $warnings[] = 'SaveZone. Запретная зона и ее компоненты удалены в Базе Данных';

            $add_zona = new ForbiddenZone();
            if ($zone_id and $zone_id > 0) {
                $add_zona->id = $zone_id;
            }
            //добавляем в модель название запретной зоны
            $add_zona->title = $zone->zone_title;
            $add_zona->mine_id = $zone->mine_id;

            $warnings[] = 'SaveZone. Поля для таблицы зоны запрета заполнены';

            if ($add_zona->save()) {                                                                                    //сохранение
                $warnings[] = 'SaveZone. Успешное сохранение  данных о запретной зоне';
                $add_zona->refresh();
                $zone_id = $add_zona->id;
                $zone->zone_id = $zone_id;
            } else {
                $errors[] = $add_zona->errors;
                throw new Exception('SaveZone. Ошибка при сохранении данных запретной зоне');
            }
            $warnings[] = 'SaveZone. Таблица "зона запрета" сохранена';


            $warnings[] = 'SaveZone.Данные для таблице запрета переданы корректно';
            /****************** заполнение полей для таблицы запрет ******************/
            foreach ($zone->zaprets as $forbidden) {
                if (!$forbidden->date_time_create) {
                    $date_time_create = \backend\controllers\Assistant::GetDateNow();
                } else {
                    $date_time_create = $forbidden->date_time_create;
                }
                $add_zapret = new ForbiddenZapret();

                $add_zapret->forbidden_type_id = $forbidden->forbidden_type_id;
                $add_zapret->status_id = $forbidden->status_id;
                $add_zapret->forbidden_zone_id = $zone_id;
                $add_zapret->worker_id = $forbidden->worker_id;
                $add_zapret->date_time_create = $date_time_create;
                $add_zapret->description = $forbidden->description;

                $warnings[] = 'SaveZone. Таблица с информацией о запрете заполнена';
                if ($add_zapret->save()) {                                                                                  //сохранение запрета
                    $warnings[] = 'SaveZone. Успешное сохранение  данных о запрете';
                    $add_zapret->refresh();
                    $id_new_zapret = $add_zapret->id;
                    $forbidden->forbidden_zapret_id = $id_new_zapret;
                } else {
                    $errors[] = $add_zapret->errors;
                    throw new Exception('SaveZone. Ошибка при сохранении данных запрете');
                }
                $warnings[] = 'SaveZone. Таблица с инф-ей о запрете сохранена';

                foreach ($forbidden->forbidden_times as $forbidden_time) {
                    $new_forbidden_time = new ForbiddenTime();
                    $new_forbidden_time->forbidden_zapret_id = $id_new_zapret;
                    $new_forbidden_time->date_start = $forbidden_time->date_start;
                    $new_forbidden_time->date_end = $forbidden_time->date_end;
                    $new_forbidden_time->status_id = $forbidden_time->status_id;
                    if ($new_forbidden_time->save()) {
                        $new_forbidden_time->refresh();
                        $warnings[] = 'SaveZone. Успешное сохранение  данных в модель ForbiddenTime';
                        $forbidden_time->forbidden_time_id = $new_forbidden_time->id;
                    } else {
                        $errors[] = $new_forbidden_time->errors;
                        throw new Exception('SaveZone. Ошибка при сохранении time');
                    }
                }

                /****************** добавляем статусы запрета ******************/
                foreach ($zone->forbidden_zapret_statuses as $forbidden_zapret_status) {
                    $add_status[] = [$id_new_zapret, $forbidden_zapret_status['status_id'], $forbidden_zapret_status['worker_id'], $forbidden_zapret_status['date_time_create']];
                }
                $warnings[] = 'SaveZone. Таблица с информацией об истории статусов заполнена';
                /****************** Вставка в бд ******************/
                if (isset($add_status)) {
                    $result_add_zone_zapret_status = Yii::$app->db->createCommand()
                        ->batchInsert('forbidden_zapret_status', ['forbidden_zapret_id', 'status_id', 'worker_id', 'date_time_create'], $add_status)//массовая вставка в БД
                        ->execute();
                    if ($result_add_zone_zapret_status != 0) {
                        $warnings[] = 'SaveZone. Успешное сохранение истории статусов запр. зоне';
                    } else {
                        throw new Exception('SaveZone. Ошибка при добавлении истории статусов запретных зон');
                    }
                }
            }


            /****************** добавляем поля про выработки ******************/
            foreach ($zone->edges as $edge) {
                $add_edge[] = [$zone_id, $edge->edge_id];
            }
            $warnings[] = 'SaveZone. Таблица с информацией о выработках заполнена';
            /****************** Вставка в бд ******************/
            if (isset($add_edge)) {
                $result_add_zone_edges = Yii::$app->db->createCommand()
                    ->batchInsert('forbidden_edge', ['forbidden_zone_id', 'edge_id'], $add_edge)//массовая вставка в БД
                    ->execute();
                if ($result_add_zone_edges != 0) {
                    $warnings[] = 'SaveZone. Успешное сохранение выработок в запр. зоне';
                } else {
                    throw new Exception('SaveZone. Ошибка при добавлении выработок');
                }
            }


            $result = $zone;
        } catch (Throwable $exception) {
            $errors[] = 'SaveZone. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'SaveZone. Конец метода';

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetZones() - Получаем список всех зон и их запретов
     * @param null $data_post - JSON массив с данными по идентификатору звена
     * @return array Массив со следующей структурой:
     *
     * zone_id            "3"                                         - ключ зоны
     * zone_title        "постоянная не актуальная "                 - наименование зоны
     * mine_id            "290"                                       - ключ шахтного поля в котором есть запрет
     * mine_title        "Заполярная-2"                              - наименование шахтного поля
     * zaprets                                                        - список запретов (хранит время)
     * {forbidden_zapret_id}                                       - ключ запрета
     * forbidden_zapret_id                "6"                         - ключ запрета
     * forbidden_zapret_status_id        "19"                        - статус запрета (актуальный или нет)
     * forbidden_type_id                "2"                         - тип запрета (временный / постоянный)
     * worker_id                        "2002270"                   - ключ работника создавшего запрет
     * full_name                        ""                          - ФИО создавшего статус
     * date_time_create                "2020-02-26 00:00:00"       - дата и время создания запрета
     * description                        "временный"                 - описание запрета
     *                  forbidden_zapret_last_date_time                             - дата создания текущего состояние запрета (поле снятие запрета)
     *                  forbidden_zapret_last_status_id                             - текущий ключ статуса запрета (должен совпадать с forbidden_zapret_status_id)
     *                  forbidden_zapret_last_status_title                          - текущее наименование статуса запрета (должен совпадать с forbidden_zapret_status_id)
     *                  forbidden_zapret_last_worker_id                             - кто изменил статус запрета (кем снят запрет)
     * forbidden_times                                             - список временных запретов
     * {forbidden_time_id}                                         - ключ времени запрета
     * forbidden_time_id            "46"                            - ключ времени запрета
     * date_start                    "2020-02-24 00:00:00"           - дата начала действия запрета
     * date_end                    "2020-02-25 00:00:00"           - дата окончания действия запрета
     * status_id                    "1"                             - статус временного запрета (действует или нет)
     *                  forbidden_zapret_statuses                                   - список истории изменения статуса запрета
     *                      {forbidden_zapret_status_id}                                - ключ истории изменения статуса запрета
     * forbidden_zapret_status_id                                    - ключ истории изменения статуса запрета
     * status_id                                                    - статус запрета
     * date_time_create                                            - дата изменения статуса
     * status_title                                                - наименование статуса
     * worker_id                                                    - ключ работника изменившего статус
     * forbidden_zapret_last_full_name                                - ФИО изменившего статус
     * edges
     * {forbidden_edge_id}                                         - ключ зоны запрета
     * forbidden_edge_id                "9"                         - ключ зоны запрета
     * edge_id                            "22141"                     - ключ выработки
     * place_id                        "6187"                      - ключ места
     * place_title                        "Груз. ветвь кл. ств.."     - наименование места
     *
     * @package frontend\controllers\positioningsystem
     * @example http://localhost/read-manager-amicum?controller=positioningsystem\ForbiddenZone&method=GetZones&subscribe=&data={%22mine_id%22:290}
     *
     *
     * @author Якимов М.Н.
     * Created date: on 08.08.2019 16:00
     */

    public static function GetZones($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                         // Промежуточный результирующий массив
        $data_zapret = array();                                                                                         // Промежуточный результирующий массив
        $session = Yii::$app->session;
        $warnings[] = 'GetZones. Начало работы метода';
        if ($data_post !== NULL && $data_post !== '') {
            $warnings[] = 'GetZones. Данные успешно переданы';
            $warnings[] = 'GetZones. Входной массив данных' . $data_post;
            try {
                $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
                $warnings[] = 'GetZones. Декодировал входные параметры';
                if (
                property_exists($post_dec, 'mine_id')
                )                                                                                                    // Проверяем наличие в нем нужных нам полей
                {
                    $warnings[] = 'GetZones. Данные с фронта получены';
                    $mine_id = $post_dec->mine_id;                                                                         // Для получения параметра - $post_dec->имя параметра;
                } else {
                    $errors[] = 'GetZones. Переданы некорректные входные параметры';
                    $status *= 0;
                }
                /****************** Берем денные  по id запрета и связываем таблицы где будем брать данные******************/
                $found_zones = ForbiddenZone::find()//
                ->joinWith('forbiddenZaprets.forbiddenTimes')
                    ->joinWith('forbiddenZaprets.forbiddenType')
                    ->joinWith('forbiddenZaprets.worker1.employee1')
                    ->joinWith('forbiddenZaprets.forbiddenZapretStatuses.status')
                    ->joinWith('forbiddenZaprets.forbiddenZapretStatuses.worker.employee')
                    ->joinWith('forbiddenEdges.edge.place')
                    ->joinWith('mine')
                    ->Where(['forbidden_zone.mine_id' => $mine_id])
                    ->limit(5000)
                    ->asArray()
                    ->all();
//                $warnings[]=$found_zones;

                /****************** если запрет есть, то идет заполнение данных ******************/
                foreach ($found_zones as $zone) {
                    $zone_id = $zone['id'];
                    $data_zapret[$zone_id]['zone_id'] = $zone_id;
                    $data_zapret[$zone_id]['zone_title'] = $zone['title'];
                    $data_zapret[$zone_id]['mine_id'] = $zone['mine_id'];
                    $data_zapret[$zone_id]['mine_title'] = $zone['mine']['title'];
                    foreach ($zone['forbiddenZaprets'] as $zapret) {
                        $forbidden_zapret_id = $zapret['id'];
                        $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_zapret_id'] = $forbidden_zapret_id;
                        $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_zapret_status_id'] = $zapret['status_id'];
                        $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_type_id'] = $zapret['forbidden_type_id'];
                        $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_type_title'] = $zapret['forbiddenType']['title'];
                        $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['worker_id'] = $zapret['worker_id'];
                        $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['full_name'] = $zapret['worker1']['employee1']['last_name'] . ' ' . $zapret['worker1']['employee1']['first_name'] . ' ' . $zapret['worker1']['employee1']['patronymic'];
                        $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['date_time_create'] = $zapret['date_time_create'];
                        $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['description'] = $zapret['description'];

                        foreach ($zapret['forbiddenZapretStatuses'] as $forbedden_status) {
                            $forbidden_status_id = $forbedden_status['id'];
                            $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_zapret_statuses'][$forbidden_status_id]['forbidden_zapret_status_id'] = $forbedden_status['id'];
                            $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_zapret_statuses'][$forbidden_status_id]['status_id'] = $forbedden_status['status_id'];
                            $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_zapret_statuses'][$forbidden_status_id]['status_title'] = $forbedden_status['status']['title'];
                            $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_zapret_statuses'][$forbidden_status_id]['worker_id'] = $forbedden_status['worker_id'];
                            $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_zapret_statuses'][$forbidden_status_id]['full_name'] = $forbedden_status['worker']['employee']['last_name'] . ' ' . $forbedden_status['worker']['employee']['first_name'] . ' ' . $forbedden_status['worker']['employee']['patronymic'];
                            $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_zapret_statuses'][$forbidden_status_id]['date_time_create'] = $forbedden_status['date_time_create'];
                            $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_zapret_last_worker_id'] = $forbedden_status['worker_id'];
                            $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_zapret_last_date_time'] = $forbedden_status['date_time_create'];
                            $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_zapret_last_status_id'] = $forbedden_status['status_id'];
                            $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_zapret_last_status_title'] = $forbedden_status['status']['title'];
                            $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_zapret_last_full_name'] = $forbedden_status['worker']['employee']['last_name'] . ' ' . $forbedden_status['worker']['employee']['first_name'] . ' ' . $forbedden_status['worker']['employee']['patronymic'];

                        }

                        foreach ($zapret['forbiddenTimes'] as $forbedden_time) {
                            $forbidden_time_id = $forbedden_time['id'];
                            $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_times'][$forbidden_time_id]['forbidden_time_id'] = $forbedden_time['id'];
                            $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_times'][$forbidden_time_id]['date_start'] = $forbedden_time['date_start'];
                            $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_times'][$forbidden_time_id]['date_end'] = $forbedden_time['date_end'];
                            $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_times'][$forbidden_time_id]['status_id'] = $forbedden_time['status_id'];
                        }
                    }

                    foreach ($zone['forbiddenEdges'] as $forbidden_edge) {                  //заполнение индентификаторами выработок
                        $forbidden_edge_id = $forbidden_edge['id'];
                        $data_zapret[$zone_id]['edges'][$forbidden_edge_id]['forbidden_edge_id'] = $forbidden_edge_id;
                        $data_zapret[$zone_id]['edges'][$forbidden_edge_id]['edge_id'] = $forbidden_edge['edge_id'];
                        $data_zapret[$zone_id]['edges'][$forbidden_edge_id]['place_id'] = $forbidden_edge['edge']['place_id'];
                        $data_zapret[$zone_id]['edges'][$forbidden_edge_id]['place_title'] = $forbidden_edge['edge']['place']['title'];
                    }
                }

                foreach ($data_zapret as $zone) {
                    if (!isset($zone['edges'])) {
                        $data_zapret[$zone['zone_id']]['edges'] = (object)array();
                    }

                    if (!isset($zone['zaprets'])) {
                        $data_zapret[$zone['zone_id']]['zaprets'] = (object)array();
                    } else {
                        foreach ($zone['zaprets'] as $forbedden_time) {
                            if (!isset($forbedden_time['forbidden_times'])) {
                                $data_zapret[$zone['zone_id']]['zaprets'][$forbedden_time['forbidden_zapret_id']]['forbidden_times'] = (object)array();
                            }
                        }
                        if (!isset($forbedden_time['forbidden_zapret_statuses'])) {
                            $data_zapret[$zone['zone_id']]['zaprets'][$forbedden_time['forbidden_zapret_id']]['forbidden_zapret_statuses'] = (object)array();
                            $data_zapret[$zone['zone_id']]['zaprets'][$forbedden_time['forbidden_zapret_id']]['forbidden_zapret_last_worker_id'] = null;
                            $data_zapret[$zone['zone_id']]['zaprets'][$forbedden_time['forbidden_zapret_id']]['forbidden_zapret_last_date_time'] = "";
                            $data_zapret[$zone['zone_id']]['zaprets'][$forbedden_time['forbidden_zapret_id']]['forbidden_zapret_last_status_id'] = null;
                            $data_zapret[$zone['zone_id']]['zaprets'][$forbedden_time['forbidden_zapret_id']]['forbidden_zapret_last_status_title'] = "";
                        }
                    }

                }

                $result = $data_zapret;
            } catch (Throwable $exception) {
                $errors[] = 'GetZones. Исключение';
                $errors[] = $exception->getMessage();
                $errors[] = $exception->getLine();
                $status *= 0;
            }
        } else {
            $errors[] = 'GetZones. Данные с фронта не получены';
            $status = 0;
        }
        $warnings[] = 'GetZones. Конец работы метода';

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetZone() - Получаем конкретную зону
     * @param null $data_post - JSON массив с данными по идентификатору звена
     * @return array Массив со следующей структурой:
     *
     *              zone_id           "3"                                         - ключ зоны
     *              zone_title        "постоянная не актуальная "                 - наименование зоны
     *              mine_id           "290"                                       - ключ шахтного поля в котором есть запрет
     *              mine_title        "Заполярная-2"                              - наименование шахтного поля
     *              zaprets                                                        - список запретов (хранит время)
     *                  {forbidden_zapret_id}                                       - ключ запрета
     *                      forbidden_zapret_id             "6"                         - ключ запрета
     *                      forbidden_zapret_status_id      "19"                        - статус запрета (актуальный или нет)
     *                      forbidden_type_id               "2"                         - тип запрета (временный / постоянный)
     *                      worker_id                       "2002270"                   - ключ работника создавшего запрет
     *                      full_name                       ""                          - ФИО создавшего статус
     *                      date_time_create                "2020-02-26 00:00:00"       - дата и время создания запрета
     *                      description                     "временный"                 - описание запрета
     *                      forbidden_zapret_last_date_time                             - дата создания текущего состояние запрета (поле снятие запрета)
     *                      forbidden_zapret_last_status_id                             - текущий ключ статуса запрета (должен совпадать с forbidden_zapret_status_id)
     *                      forbidden_zapret_last_status_title                          - текущее наименование статуса запрета (должен совпадать с forbidden_zapret_status_id)
     *                      forbidden_zapret_last_worker_id                             - кто изменил статус запрета (кем снят запрет)
     *                      forbidden_times                                             - список временных запретов
     *                          {forbidden_time_id}                                         - ключ времени запрета
     *                              forbidden_time_id           "46"                            - ключ времени запрета
     *                              date_start                  "2020-02-24 00:00:00"           - дата начала действия запрета
     *                              date_end                    "2020-02-25 00:00:00"           - дата окончания действия запрета
     *                              status_id                   "1"                             - статус временного запрета (действует или нет)
     *              forbidden_zapret_statuses                                   - список истории изменения статуса запрета
     *                  {forbidden_zapret_status_id}                                - ключ истории изменения статуса запрета
     *                      forbidden_zapret_status_id                                    - ключ истории изменения статуса запрета
     *                      status_id                                                    - статус запрета
     *                      date_time_create                                            - дата изменения статуса
     *                      status_title                                                - наименование статуса
     *                      worker_id                                                    - ключ работника изменившего статус
     *                      forbidden_zapret_last_full_name                                - ФИО изменившего статус
     *              edges
     *                  {forbidden_edge_id}                                         - ключ зоны запрета
     *                      forbidden_edge_id               "9"                         - ключ зоны запрета
     *                      edge_id                         "22141"                     - ключ выработки
     *                      place_id                        "6187"                      - ключ места
     *                      place_title                     "Груз. ветвь кл. ств.."     - наименование места
     *
     * @package frontend\controllers\positioningsystem
     * @example http://localhost/read-manager-amicum?controller=positioningsystem\ForbiddenZone&method=GetZone&subscribe=&data={%22zone_id%22:290}
     *
     *
     * @author Якимов М.Н.
     * Created date: on 08.08.2019 16:00
     */

    public static function GetZone($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                         // Промежуточный результирующий массив
        $data_zapret = array();                                                                                         // Промежуточный результирующий массив
        $warnings[] = 'GetZone. Начало работы метода';
        if ($data_post !== NULL && $data_post !== '') {
            $warnings[] = 'GetZone. Данные успешно переданы';
            $warnings[] = 'GetZone. Входной массив данных' . $data_post;
            try {
                $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
                $warnings[] = 'GetZone. Декодировал входные параметры';
                if (
                !property_exists($post_dec, 'zone_id')
                ) {                                                                                                   // Проверяем наличие в нем нужных нам полей
                    throw new Exception('GetZone. Переданы некорректные входные параметры');
                }
                $zone_id = $post_dec->zone_id;
                /****************** Берем денные  по id запрета и связываем таблицы где будем брать данные******************/
                $zone = ForbiddenZone::find()//
                ->joinWith('forbiddenZaprets.forbiddenTimes')
                    ->joinWith('forbiddenZaprets.forbiddenType')
                    ->joinWith('forbiddenZaprets.worker1.employee1')
                    ->joinWith('forbiddenZaprets.forbiddenZapretStatuses.status')
                    ->joinWith('forbiddenZaprets.forbiddenZapretStatuses.worker.employee')
                    ->joinWith('forbiddenEdges.edge.place')
                    ->joinWith('mine')
                    ->Where(['forbidden_zone.id' => $zone_id])
                    ->limit(5000)
                    ->asArray()
                    ->one();
//                $warnings[]=$found_zones;

                if (!$zone) {
                    throw new Exception('GetZone. Запрашиваемая запретная зона отсутствует ' . $zone_id);
                }
                /****************** если запрет есть, то идет заполнение данных ******************/

                $zone_id = $zone['id'];
                $data_zapret[$zone_id]['zone_id'] = $zone_id;
                $data_zapret[$zone_id]['zone_title'] = $zone['title'];
                $data_zapret[$zone_id]['mine_id'] = $zone['mine_id'];
                $data_zapret[$zone_id]['mine_title'] = $zone['mine']['title'];
                foreach ($zone['forbiddenZaprets'] as $zapret) {
                    $forbidden_zapret_id = $zapret['id'];
                    $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_zapret_id'] = $forbidden_zapret_id;
                    $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_zapret_status_id'] = $zapret['status_id'];
                    $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_type_id'] = $zapret['forbidden_type_id'];
                    $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_type_title'] = $zapret['forbiddenType']['title'];
                    $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['worker_id'] = $zapret['worker_id'];
                    $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['full_name'] = $zapret['worker1']['employee1']['last_name'] . ' ' . $zapret['worker1']['employee1']['first_name'] . ' ' . $zapret['worker1']['employee1']['patronymic'];
                    $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['date_time_create'] = $zapret['date_time_create'];
                    $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['description'] = $zapret['description'];

                    foreach ($zapret['forbiddenZapretStatuses'] as $forbedden_status) {
                        $forbidden_status_id = $forbedden_status['id'];
                        $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_zapret_statuses'][$forbidden_status_id]['forbidden_zapret_status_id'] = $forbedden_status['id'];
                        $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_zapret_statuses'][$forbidden_status_id]['status_id'] = $forbedden_status['status_id'];
                        $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_zapret_statuses'][$forbidden_status_id]['status_title'] = $forbedden_status['status']['title'];
                        $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_zapret_statuses'][$forbidden_status_id]['worker_id'] = $forbedden_status['worker_id'];
                        $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_zapret_statuses'][$forbidden_status_id]['full_name'] = $forbedden_status['worker']['employee']['last_name'] . ' ' . $forbedden_status['worker']['employee']['first_name'] . ' ' . $forbedden_status['worker']['employee']['patronymic'];
                        $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_zapret_statuses'][$forbidden_status_id]['date_time_create'] = $forbedden_status['date_time_create'];
                        $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_zapret_last_worker_id'] = $forbedden_status['worker_id'];
                        $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_zapret_last_date_time'] = $forbedden_status['date_time_create'];
                        $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_zapret_last_status_id'] = $forbedden_status['status_id'];
                        $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_zapret_last_status_title'] = $forbedden_status['status']['title'];
                        $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_zapret_last_full_name'] = $forbedden_status['worker']['employee']['last_name'] . ' ' . $forbedden_status['worker']['employee']['first_name'] . ' ' . $forbedden_status['worker']['employee']['patronymic'];

                    }

                    foreach ($zapret['forbiddenTimes'] as $forbedden_time) {
                        $forbidden_time_id = $forbedden_time['id'];
                        $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_times'][$forbidden_time_id]['forbidden_time_id'] = $forbedden_time['id'];
                        $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_times'][$forbidden_time_id]['date_start'] = $forbedden_time['date_start'];
                        $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_times'][$forbidden_time_id]['date_end'] = $forbedden_time['date_end'];
                        $data_zapret[$zone_id]['zaprets'][$forbidden_zapret_id]['forbidden_times'][$forbidden_time_id]['status_id'] = $forbedden_time['status_id'];
                    }
                }

                foreach ($zone['forbiddenEdges'] as $forbidden_edge) {                  //заполнение индентификаторами выработок
                    $forbidden_edge_id = $forbidden_edge['id'];
                    $data_zapret[$zone_id]['edges'][$forbidden_edge_id]['forbidden_edge_id'] = $forbidden_edge_id;
                    $data_zapret[$zone_id]['edges'][$forbidden_edge_id]['edge_id'] = $forbidden_edge['edge_id'];
                    $data_zapret[$zone_id]['edges'][$forbidden_edge_id]['place_id'] = $forbidden_edge['edge']['place_id'];
                    $data_zapret[$zone_id]['edges'][$forbidden_edge_id]['place_title'] = $forbidden_edge['edge']['place']['title'];
                }


                foreach ($data_zapret as $zone) {
                    if (!isset($zone['edges'])) {
                        $data_zapret[$zone['zone_id']]['edges'] = (object)array();
                    }

                    if (!isset($zone['zaprets'])) {
                        $data_zapret[$zone['zone_id']]['zaprets'] = (object)array();
                    } else {
                        foreach ($zone['zaprets'] as $forbedden_time) {
                            if (!isset($forbedden_time['forbidden_times'])) {
                                $data_zapret[$zone['zone_id']]['zaprets'][$forbedden_time['forbidden_zapret_id']]['forbidden_times'] = (object)array();
                            }
                        }
                        if (!isset($forbedden_time['forbidden_zapret_statuses'])) {
                            $data_zapret[$zone['zone_id']]['zaprets'][$forbedden_time['forbidden_zapret_id']]['forbidden_zapret_statuses'] = (object)array();
                            $data_zapret[$zone['zone_id']]['zaprets'][$forbedden_time['forbidden_zapret_id']]['forbidden_zapret_last_worker_id'] = null;
                            $data_zapret[$zone['zone_id']]['zaprets'][$forbedden_time['forbidden_zapret_id']]['forbidden_zapret_last_date_time'] = "";
                            $data_zapret[$zone['zone_id']]['zaprets'][$forbedden_time['forbidden_zapret_id']]['forbidden_zapret_last_status_id'] = null;
                            $data_zapret[$zone['zone_id']]['zaprets'][$forbedden_time['forbidden_zapret_id']]['forbidden_zapret_last_status_title'] = "";
                        }
                    }

                }

                $result = $data_zapret;
            } catch (Throwable $exception) {
                $errors[] = 'GetZone. Исключение';
                $errors[] = $exception->getMessage();
                $errors[] = $exception->getLine();
                $status *= 0;
            }
        } else {
            $errors[] = 'GetZone. Данные с фронта не получены';
            $status = 0;
        }
        $warnings[] = 'GetZone. Конец работы метода';

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetGournalZapret -  получить журнал запретных зон
    // входящие параметры
    //      date_time_start         - дата с которой возвращаются данные
    //      date_time_end           - дата до которой возвращаются данные
    //      mine_id                 - ключ шахтного поля
    // возвращаемые данные:
    //  []
    //        summary_report_forbidden_zones_id         - ключ журнала запретных зон
//            date_work                                 - рабочая дата с учетом смена
//            shift                                     - смена
//            date_time_start                           - начало нахождения в запертной зоне
//            date_time_end                             - окончание нахождения в запретной зоне
//            main_title                                - наименование объекта
//            main_id                                   - ключ объекта
//            place_id                                  - ключ места
//            edge_id                                   - ключ выработки
//            place_title                               - наименование места
//            duration                                  - продолжительность нахождения в запретной зоне
//            company_department                        - ключ департамента
//            company_title                             - наименование департамента
//            position_title                            - наименование должности
    // пример: 127.0.0.1/read-manager-amicum?controller=positioningsystem\ForbiddenZone&method=GetGournalZapret&subscribe=&data={"date_time_start":"2020-02-23 01:00:00","date_time_end":"2020-02-25 01:00:00","mine_id":290}
    // Разработал: Якимов М.Н.
    // дата 24.02.2020
    public static function GetGournalZapret($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                         // Промежуточный результирующий массив
        $data_zapret = array();                                                                                         // Промежуточный результирующий массив

        $warnings[] = 'GetGournalZapret. Начало работы метода';

        try {
            if ($data_post !== NULL && $data_post !== '') {
                $warnings[] = 'GetGournalZapret. Данные успешно переданы';
                $warnings[] = 'GetGournalZapret. Входной массив данных' . $data_post;
            } else {
                $errors[] = 'GetGournalZapret. Данные с фронта не получены' . $data_post;

                throw new Exception('GetGournalZapret. Данные с фронта не получены');
            }
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = 'GetGournalZapret. Декодировал входные параметры';
//            $warnings[] = $post_dec;
            if (
                !property_exists($post_dec, 'date_time_start') ||
                !property_exists($post_dec, 'mine_id') ||
                !property_exists($post_dec, 'date_time_end')
            )                                                                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception('GetGournalZapret. Переданы некорректные входные параметры');
            }
            $mine_id = $post_dec->mine_id;
            $date_time_start = date('Y-m-d H:i:s', strtotime($post_dec->date_time_start));
            $date_time_end = date('Y-m-d H:i:s', strtotime($post_dec->date_time_end));
            $sql_filter = '';

            if ($date_time_start <= $date_time_end) {
                $sql_filter .= " date_time_start >= '" . $date_time_start . "' AND date_time_start <= '" . $date_time_end . "'";
            } else if ($date_time_start > $date_time_end) {
                $sql_filter .= " date_time_start >= '" . $date_time_end . "' AND date_time_start <= '" . $date_time_start . "'";
            }

            if ($mine_id != "") {
                $sql_filter .= ' and place.mine_id=' . $mine_id;
            }


            $result = (new Query())                                                                             //Запрос напрямую из базы по вьюшке view_personal_areas
            ->select([                                                                                                       //Обязательно сортируем по порядку
                'summary_report_forbidden_zones.id as summary_report_forbidden_zones_id',
                'date_work',
                'date_time_start',
                'date_time_end',
                'shift',
                'main_title',
                'main_id',
                'worker.tabel_number as tabel_number',
                'place_id',
                'edge_id',
                'place.title as place_title',
                'duration',
                'company_department.id as company_department_id',
                'company.title as company_title',
                'position.title as position_title',
            ])
                ->from('summary_report_forbidden_zones')
                ->leftJoin('place', 'place.id=summary_report_forbidden_zones.place_id')
                ->leftJoin('worker_object', 'summary_report_forbidden_zones.main_id=worker_object.id')
                ->leftJoin('worker', 'worker.id=worker_object.worker_id')
                ->leftJoin('position', 'worker.position_id=position.id')
                ->leftJoin('company_department', 'company_department.id=worker.company_department_id')
                ->leftJoin('company', 'company.id=company_department.company_id')
                ->where($sql_filter)
                ->andWhere(['summary_report_forbidden_zones.object_id' => 25])
                ->orderBy(['date_time_start' => SORT_DESC])
                ->all();
        } catch (Throwable $exception) {
            $errors[] = 'GetGournalZapret. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'GetGournalZapret. Конец работы метода';

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetActiveForbiddenZoneByEdge() - Выводит список названий активных запретных зон, которые находятся на определенной выработке
     * @param null edge_id - Параметр вырабоки (Edge) - ID
     * @return array Массив названий запретных зон на определенной выработке
     *          []
     *              zone_id         - ключ запретной зоны
     *              zone_title      - наименование запретной зоны
     * @package frontend\controllers\positioningsystem
     * @example
     *
     *
     *
     * @author Якимов М.Н.
     * Created date: on 16.08.2019 13:00
     */
    public static function GetActiveForbiddenZoneByEdge($edge_id)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();                                                                                              // Промежуточный результирующий массив
        $isForbidden = 0;                                                                                               // есть запреты на данной выработке или нет
        try {

            $warnings[] = 'GetActiveForbiddenZoneByEdge. Начало работы метода';
            $result = (new Query())
                ->select('
                    forbidden_zone.id as zone_id,
                    forbidden_zone.title as zone_title
                ')
                ->from('forbidden_edge')
                ->innerJoin('forbidden_zone', 'forbidden_zone.id=forbidden_edge.forbidden_zone_id')
                ->innerJoin('forbidden_zapret', 'forbidden_zapret.forbidden_zone_id=forbidden_zone.id')
                ->where(['forbidden_edge.edge_id' => $edge_id])
                ->andWhere(['forbidden_zapret.status_id' => self::STATUS_ACTIVE])
                ->limit(5000)
                ->groupBy('zone_id, zone_title')
                ->orderBy('forbidden_zone.title')
                ->all();

            $isForbidden = count($result);

//            $warnings[] = 'GetActiveForbiddenZoneByEdge. Выработка edge_id:';
//            $warnings[] = $edge_id;
//
//            $warnings[] = 'GetActiveForbiddenZoneByEdge. Количество запретов на данной выработке:';
//            $warnings[] = $isForbidden;
//
//            $warnings[] = 'GetActiveForbiddenZoneByEdge. Запреты:';
//            $warnings[] = $result;

            $warnings[] = 'GetActiveForbiddenZoneByEdge. Окончание работы метода';
        } catch (Throwable $exception) {
            $errors[] = 'GetForbiddenZoneByEdge. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        return array('Items' => $result, 'status' => $status, 'isForbidden' => $isForbidden, 'errors' => $errors, 'warnings' => $warnings);
    }
}


