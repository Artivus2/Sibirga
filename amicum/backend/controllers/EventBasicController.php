<?php


namespace backend\controllers;


use backend\controllers\cachemanagers\LogCacheController;
use Exception;
use frontend\models\EventJournal;
use frontend\models\EventJournalSituationJournal;
use frontend\models\EventStatus;
use Throwable;
use Yii;

class EventBasicController
{
    // createEventStatusEntry                   - создание записи в таблице event_status
    // createEventJournalEntry                  - создание записи в таблице event_journal
    // createEventJournalWithStatus             - Создание записи в таблице event_journal со статусом
    // createEventJournalSituationJournal       - Создание связки журналов ситуаций и событий
    // updateEventJournalWithStatus             - обновление записи в таблице event_journal со статусом и статусом значения события
    // createEventJournalSituationJournalBatch  - Массовое создание связки журналов ситуаций и событий


    /**
     * Создание записи в таблице event_status
     * @param $event_journal_id - идентификатор записи из таблицы event_journal
     * @param $status_id - идентификатор статуса события
     * @param $date_time - дата и время события
     * @return array
     */
    public static function createEventStatusEntry($event_journal_id, $status_id, $date_time)
    {
        $errors = array();
        $status = 1;
        $result = null;
        $warnings = array();
        $warnings[] = 'createEventStatusEntry. Начало метода';

        try {
            $event_status = new EventStatus();
            $event_status->event_journal_id = $event_journal_id;
            $event_status->status_id = $status_id;
            $event_status->datetime = $date_time;
            if ($event_status->save()) {
                $warnings[] = 'createEventStatusEntry. Событие добавлено в таблицу event_status';
            } else {
                $errors[] = $event_status->errors;
                throw new Exception('createEventStatusEntry. Ошибка при сохранении в таблицу event_status');
            }
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'createEventStatusEntry.Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $data_to_cache_log = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
            LogCacheController::setEventLogValue('createEventStatusEntry', $data_to_cache_log, '2');
        }

        $warnings[] = 'createEventStatusEntry. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Создание записи в таблице event_journal
     * @param $event_id - идентификатор события
     * @param $main_id - идентификатор объекта с которым связано событие
     * @param $edge_id - идентификатор выработки где произошло событие
     * @param $value - значение параметра, вызвавшего событие
     * @param $date_time - дата и время параметра
     * @param $xyz - координаты в которых произошло событие
     * @param $status_id - идентификатор статуса параметра (нормальное, аварийное)
     * @param $event_status_id - идентификатор статуса события (устранено, получено, устраняется)
     * @param $parameter_id - идентификатор параметра
     * @param $object_id - идентификатор объекта
     * @param $mine_id - идентификатор шахты
     * @param $object_title - наименование объекта (название сенсора, ФИО воркера и т.д.)
     * @param $object_table - таблица с данными о объекте
     * @param $group_alarm_id - ключ группы оповещения
     * @return array
     */
    public static function createEventJournalEntry($event_id, $main_id, $edge_id, $value, $date_time, $xyz, $status_id, $parameter_id, $object_id, $mine_id, $object_title, $object_table, $event_status_id, $group_alarm_id = NULL)
    {
        $errors = array();
        $status = 1;
        $result = null;
        $warnings = array();
        $warnings[] = 'createEventJournalEntry. Начало метода';
        $warnings[] = 'createEventJournalEntry. Для отадки';
        $warnings[] = 'createEventJournalEntry. Полученные параметры на сохранение в таблицу event_journal:';
        $warnings[] = array(
            'event_id' => $event_id,
            'main_id' => $main_id,
            'edge_id' => $edge_id,
            'value' => $value,
            'date_time' => $date_time,
            'xyz' => $xyz,
            'status_id' => $status_id,
            'event_status_id' => $event_status_id,
            'parameter_id' => $parameter_id,
            'object_id' => $object_id,
            'mine_id' => $mine_id,
            'object_title' => $object_title,
            'object_table' => $object_table
        );
        $event_journal_id = -1;
        try {
            $event_journal = new EventJournal();
            $event_journal->event_id = $event_id;
            $event_journal->main_id = $main_id;
            $event_journal->edge_id = $edge_id;
            $event_journal->value = (string)$value;
            $event_journal->date_time = $date_time;
            $event_journal->xyz = (string)$xyz;
            $event_journal->status_id = $status_id;
            $event_journal->event_status_id = $event_status_id;
            $event_journal->parameter_id = $parameter_id;
            $event_journal->object_id = $object_id;
            $event_journal->mine_id = $mine_id;
            $event_journal->object_title = $object_title;
            $event_journal->object_table = $object_table;
            $event_journal->group_alarm_id = $group_alarm_id;
            if ($event_journal->save()) {
                $event_journal_id = $event_journal->id;
                $warnings[] = 'createEventJournalEntry. Событие добавлено в таблицу event_journal';
            } else {
                $errors[] = $event_journal->errors;
                throw new Exception('createEventJournalEntry. Ошибка при сохранении в таблицу event_journal');
            }
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'createEventJournalEntry.Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $data_to_cache_log = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings,
                'event_journal_id' => $event_journal_id);
            LogCacheController::setEventLogValue('createEventJournalEntry', $data_to_cache_log, '2');
        }

        $warnings[] = 'createEventJournalEntry. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings,
            'event_journal_id' => $event_journal_id);
        return $result_main;
    }

    /**
     * createEventJournalWithStatus - Создание записи в таблице event_journal со статусом
     * @param $event_id - идентификатор события
     * @param $main_id - идентификатор объекта с которым связано событие
     * @param $edge_id - идентификатор выработки где произошло событие
     * @param $value - значение параметра, вызвавшего событие
     * @param $date_time - дата и время параметра
     * @param $xyz - координаты в которых произошло событие
     * @param $status_id - идентификатор статуса параметра
     * @param $parameter_id - идентификатор параметра
     * @param $object_id - идентификатор объекта
     * @param $mine_id - идентификатор шахты
     * @param $object_title - наименование объекта (название сенсора, ФИО воркера и т.д.)
     * @param $object_table - таблица с данными о объекте
     * @param $event_status_id - Статус события
     * @param $group_alarm_id - группа оповещения
     * @return array
     */
    public static function createEventJournalWithStatus($event_id, $main_id, $edge_id,
                                                        $value, $date_time, $xyz,
                                                        $status_id, $parameter_id, $object_id,
                                                        $mine_id, $object_title, $object_table, $event_status_id, $group_alarm_id)
    {
        $errors = array();
        $status = 1;
        $result = null;
        $warnings = array();
        $warnings[] = 'createEventJournalWithStatus. Начало метода';

        $event_journal_id = -1;
        try {
            $event_journal = new EventJournal();
            $event_journal->event_id = $event_id;
            $event_journal->main_id = $main_id;
            $event_journal->edge_id = $edge_id;
            $event_journal->value = (string)$value;
            $event_journal->date_time = $date_time;
            $event_journal->xyz = (string)$xyz;
            $event_journal->status_id = $status_id;
            $event_journal->event_status_id = $event_status_id;
            $event_journal->parameter_id = $parameter_id;
            $event_journal->object_id = $object_id;
            $event_journal->mine_id = $mine_id;
            $event_journal->object_title = $object_title;
            $event_journal->object_table = $object_table;
            $event_journal->group_alarm_id = $group_alarm_id;
            if ($event_journal->save()) {
                $event_journal_id = $event_journal->id;
                $warnings[] = 'createEventJournalWithStatus. Событие добавлено в таблицу event_journal';
                $event_status = new EventStatus();
                $event_status->event_journal_id = $event_journal_id;
                $event_status->datetime = $date_time;
                $event_status->status_id = $event_status_id;
                if ($event_status->save()) {
                    $warnings[] = 'createEventJournalWithStatus. Статус события добавлен в таблицу event_status';
                } else {
                    $errors[] = $event_status->errors;
                    throw new Exception('createEventJournalWithStatus. Ошибка при сохранении в таблицу event_status');
                }
            } else {
                $errors[] = $event_journal->errors;
                throw new Exception('createEventJournalWithStatus. Ошибка при сохранении в таблицу event_journal');
            }
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'createEventJournalWithStatus.Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'createEventJournalWithStatus. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings,
            'event_journal_id' => $event_journal_id);
        return $result_main;
    }

    /**
     * updateEventJournalWithStatus - обновление записи в таблице event_journal со статусом и статусом значения события
     * @param $event_journal_id - идентификатор журнала событий
     * @param $event_status_id - Статус события
     * @param $value_status_id - идентификатор статуса параметра
     * @param $date_time - Время изменения статуса событий
     * @param $value_from_cache - актуальное значение из кеша
     * @return array
     */
    public static function updateEventJournalWithStatus($event_journal_id, $event_status_id, $value_status_id, $date_time, $value_from_cache)
    {
        $errors = array();
        $status = 1;
        $result = null;
        $warnings = array();
        $warnings[] = 'updateEventJournalWithStatus. Начало метода';

        try {
            $event_journal = EventJournal::findOne(['id' => $event_journal_id]);
            if (!$event_journal) {
                throw new Exception('updateEventJournalWithStatus. Данного ключа журнала событий не существует = ' . $event_journal_id);
            }

            $event_journal->status_id = $value_status_id;
            $event_journal->event_status_id = $event_status_id;
            $event_journal->value = (string)$value_from_cache;

            if (!$event_journal->save()) {
                $errors[] = $event_journal->errors;
                throw new Exception('updateEventJournalWithStatus. Ошибка при сохранении в таблицу event_journal');
            }

            $event_journal_id = $event_journal->id;
            $warnings[] = 'updateEventJournalWithStatus. Событие обновлено в таблицу event_journal';
            $event_status = new EventStatus();
            $event_status->event_journal_id = $event_journal_id;
            $event_status->datetime = $date_time;
            $event_status->status_id = $event_status_id;
            if (!$event_status->save()) {
                $errors[] = $event_status->errors;
                throw new Exception('updateEventJournalWithStatus. Ошибка при сохранении в таблицу event_status');
            }

        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'updateEventJournalWithStatus.Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'updateEventJournalWithStatus. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings,
            'event_journal_id' => $event_journal_id);
        return $result_main;
    }

    /**
     * createEventJournalSituationJournal - Создание связки журналов ситуаций и событий
     * @param $event_journal_id - идентификатор события
     * @param $situation_journal_id - идентификатор объекта с которым связано событие
     * @return array
     */
    public static function createEventJournalSituationJournal($event_journal_id, $situation_journal_id)
    {
        $errors = array();
        $status = 1;
        $result = null;
        $warnings = array();
        $warnings[] = 'createEventJournalSituationJournal. Начало метода';

        $event_journal_situation_journal_id = -1;
        try {
            $event_situation_journal = new EventJournalSituationJournal();
            $event_situation_journal->event_journal_id = $event_journal_id;
            $event_situation_journal->situation_journal_id = $situation_journal_id;
            if ($event_situation_journal->save()) {
                $event_journal_situation_journal_id = $event_situation_journal->id;
                $warnings[] = 'createEventJournalSituationJournal. Событие добавлено в таблицу EventJournalSituationJournal';
            } else {
                $errors[] = $event_situation_journal->errors;
                throw new Exception('createEventJournalSituationJournal. Ошибка при сохранении в таблицу EventJournalSituationJournal');
            }
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'createEventJournalSituationJournal.Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'createEventJournalSituationJournal. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings,
            'event_journal_situation_journal_id' => $event_journal_situation_journal_id);
        return $result_main;
    }

    /**
     * createEventJournalSituationJournalBatch - Массовое создание связки журналов ситуаций и событий
     * @param $event_journal_ids - массив ключей журнала событий
     * @param $situation_journal_id - идентификатор объекта с которым связано событие
     * @return array
     */
    public static function createEventJournalSituationJournalBatch($event_journal_ids, $situation_journal_id)
    {
        $errors = array();
        $status = 1;
        $result = null;
        $warnings = array();
        $warnings[] = 'createEventJournalSituationJournalBatch. Начало метода';

        try {

            if (empty($event_journal_ids)) {
                throw new Exception('createEventJournalSituationJournalBatch. входной массив списка журнала событий пуст ' . $event_journal_ids);
            }

            foreach ($event_journal_ids as $event_journal_id) {
                $event_journal[] = array(
                    'event_journal_id' => $event_journal_id,
                    'situation_journal_id' => $situation_journal_id

                );
            }

            $insert_result_to_MySQL = Yii::$app->db->createCommand()->batchInsert('event_journal_situation_journal', ['event_journal_id', 'situation_journal_id'], $event_journal)->execute();

//            $insert_param_val = Yii::$app->db->queryBuilder->batchInsert('event_journal_situation_journal', ['event_journal_id', 'situation_journal_id'], $event_journal);
//            $insert_result_to_MySQL = Yii::$app->db->createCommand($insert_param_val . " ON DUPLICATE KEY UPDATE `event_journal_id` = VALUES (`event_journal_id`), `situation_journal_id` = VALUES (`situation_journal_id`)")->execute();

            $warnings[] = "закончил вставку данных в event_journal_situation_journal";
            if (!$insert_result_to_MySQL) {
                throw new Exception('createEventJournalSituationJournalBatch. Ошибка массовой вставки событий ситуации в БД ' . $insert_result_to_MySQL);
            }

        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'createEventJournalSituationJournalBatch.Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'createEventJournalSituationJournalBatch. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }
}