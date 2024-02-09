<?php


namespace backend\controllers;


use Exception;
use frontend\controllers\EdsFrontController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\EventSituation;
use frontend\models\Situation;
use frontend\models\SituationJournal;
use frontend\models\SituationJournalSendStatus;
use frontend\models\SituationJournalZone;
use frontend\models\SituationStatus;
use Throwable;
use Yii;

class SituationBasicController
{
    // Базовый контроллер по записи Ситуаций и ее свойств в БД

    // createSituationStatusEntry           - создание записи в таблице situation_status
    // createSituationJournal               - создание записи в таблице situation_journal
    // createSendStatus                     - создание записи в таблице situation_journal_send_status
    // getSituationByEventId                - получить ситуацию с минимальным количеством событий в ней, содержащую передоваемое событие
    // createSituationJournalZone           - Создание опасных зон ситуации в БД (массовая вставка)
    // addSituationJournalZone              - Добавление опасной зоны ситуации в БД (разовая вставка)
    // updateSituationJournalDateTimeEnd    - обновление времени окончания ситуации в таблице situation_journal
    // updateSendStatus                     - обновление времени отправки сообщения
    // getSituationByEvents                 - получить ситуацию с минимальным количеством событий в ней, содержащую передоваемые события

    /**
     * Создание записи в таблице situation_status
     * @param $situation_journal_id - идентификатор записи из таблицы situation_journal
     * @param $status_id - идентификатор статуса события
     * @param $date_time - дата и время события
     * @return array
     */
    public static function createSituationStatusEntry($situation_journal_id, $status_id, $date_time)
    {
        $errors = array();
        $status = 1;
        $result = null;
        $warnings = array();
        $warnings[] = 'createSituationStatusEntry. Начало метода';

        try {
            $situation_status = new SituationStatus();
            $situation_status->situation_journal_id = $situation_journal_id;
            $situation_status->status_id = $status_id;
            $situation_status->date_time = $date_time;
            if ($situation_status->save()) {
                $warnings[] = 'createSituationStatusEntry. Событие добавлено в таблицу situation_status';
            } else {
                $errors[] = $situation_status->errors;
                throw new Exception('createSituationStatusEntry. Ошибка при сохранении в таблицу situation_status');
            }
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'createSituationStatusEntry.Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'createSituationStatusEntry. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * createSituationJournal - Создание записи в таблице situation_journal
     * алгоритм:
     * создаем ситуацию в журнале ситуаций
     * создаем статус ситуации как неподтвержденная - т.к. неизвестно, является она штатной или внештатной
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
    public static function createSituationJournal($situation_id, $date_time, $main_id, $situation_status_id,
                                                  $danger_level_id, $company_department_id, $mine_id, $date_time_start, $date_time_end = NULL)
    {
        $errors = array();
        $status = 1;
        $result = null;
        $warnings = array();
        $warnings[] = 'createSituationJournal. Начало метода';

        $situation_journal_id = -1;
        try {
            $situation_journal = new SituationJournal();

            $situation_journal->situation_id = (int)$situation_id;
            $situation_journal->date_time = $date_time;
            $situation_journal->main_id = $main_id;
            $situation_journal->status_id = $situation_status_id;
            $situation_journal->danger_level_id = $danger_level_id;
            $situation_journal->company_department_id = $company_department_id;
            $situation_journal->mine_id = $mine_id;
            $situation_journal->date_time_start = $date_time_start;
            $situation_journal->date_time_end = $date_time_end;

            if (!$situation_journal->save()) {
                $errors[] = $situation_journal->errors;
                throw new Exception('createSituationJournal. Ошибка при сохранении в таблицу situation_journal');
            }

            $situation_journal_id = $situation_journal->id;

            $warnings[] = 'createSituationJournal. Ситуация добавлена в таблицу situation_journal. id = ' . $situation_journal_id;

            $situation_status = new SituationStatus();
            $situation_status->situation_journal_id = $situation_journal_id;
            $situation_status->date_time = $date_time;
            $situation_status->status_id = $situation_status_id;                                                    // неподтвержденная ситуация 34
            if (!$situation_status->save()) {
                throw new Exception('createSituationJournal. Ошибка при сохранении в таблицу situation_status');
            }

            $warnings[] = 'createSituationJournal. Статус ситуации добавлен в таблицу situation_status';

            EdsFrontController::CreateSolutionBySituation((int)$situation_id, $situation_journal_id);
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'createSituationJournal.Исключение: ';
            $errors[] = '$mine_id:' . $mine_id;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'createSituationJournal. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings,
            'situation_journal_id' => $situation_journal_id);
        return $result_main;
    }

    /**
     * createSendStatus - Создание записи в таблице situation_journal_send_status
     * алгоритм:
     * @param $situation_journal_id -   идентификатор журнала ситуаций
     * @param $xml_send_type_id -   ключ типа отправки сообщения
     * @param $date_time -   дата и время отправки сообщения
     * @param $status_id -   статус отправки сообщения
     * @return array
     */
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
    public static function createSendStatus($situation_journal_id, $xml_send_type_id, $status_id, $date_time)
    {
        $errors = array();
        $status = 1;
        $result = null;
        $warnings = array();
        $warnings[] = 'createSendStatus. Начало метода';

        $situation_journal_send_status_id = -1;
        try {
            $send_status = new SituationJournalSendStatus();

            $send_status->situation_journal_id = (int)$situation_journal_id;
            $send_status->xml_send_type_id = $xml_send_type_id;
            $send_status->date_time = $date_time;
            $send_status->status_id = $status_id;

            if ($send_status->save()) {
                $situation_journal_send_status_id = $send_status->id;
                $warnings[] = 'createSendStatus. Статус отправки добавлен в таблицу SituationJournalSendStatus. id = ' . $situation_journal_send_status_id;

            } else {
                $errors[] = $send_status->errors;
                throw new Exception('createSendStatus. Ошибка при сохранении в таблицу SituationJournalSendStatus');
            }
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'createSendStatus.Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'createSendStatus. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings,
            'situation_journal_send_status_id' => $situation_journal_send_status_id);
        return $result_main;
    }

    /**
     * updateSituationJournalDateTimeEnd - обновление времени окончания ситуации в таблице situation_journal
     * алгоритм:
     * создаем ситуацию в журнале ситуаций
     * создаем статус ситуации как неподтвержденная - т.к. неизвестно, является она штатной или внештатной
     * @param $situation_journal_id -   ключ журанла ситуации
     * @param $date_time_end -   дата окончания ситуации
     * @return array
     */
    public static function updateSituationJournal($situation_journal_id, $date_time_end)
    {
        $log = new LogAmicumFront('updateSituationJournal');
        $result = null;

        try {
            $log->addLog("Начало метода");

            $situation_journal = SituationJournal::findOne(['id' => $situation_journal_id]);
            if (!$situation_journal) {
                throw new Exception('Конкретная ситуация не найдена в журнале ситуаций');
            }

            $situation_journal->date_time_end = $date_time_end;

            if (!$situation_journal->save()) {
                $log->addData($situation_journal->errors, '$situation_journal->errors', __LINE__);
                throw new Exception('Время окончания ситуации не обновлено в таблице situation_journal');
            }
            $situation_journal_id = $situation_journal->id;
            $log->addLog("Окончание метода");

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $result, 'situation_journal_id' => $situation_journal_id], $log->getLogAll());
    }

    /**
     * updateSendStatus - обновление времени отправки сообщения ситуации
     * алгоритм:
     * создаем ситуацию в журнале ситуаций
     * создаем статус ситуации как неподтвержденная - т.к. неизвестно, является она штатной или внештатной
     * @param $situation_journal_send_status_id -   ключ отправки сообщения ситуации
     * @param $date_time -   дата отправки сообщения
     * @param $status_id -   статус отправки сообщения
     * @return array
     */
    public static function updateSendStatus($situation_journal_send_status_id, $status_id, $date_time)
    {
        $errors = array();
        $status = 1;
        $result = null;
        $warnings = array();
        $warnings[] = 'updateSendStatus. Начало метода';

        try {
            $send_status = SituationJournalSendStatus::findOne(['id' => $situation_journal_send_status_id]);
            if (!$send_status) {
                throw new Exception('updateSendStatus. Конкретная ситуация не найдена в журнале ситуаций');
            }

            $send_status->date_time = $date_time;
            $send_status->status_id = $status_id;

            if ($send_status->save()) {
                $situation_journal_send_status_id = $send_status->id;
                $warnings[] = 'updateSendStatus. Время окончания ситуации обновлено в таблице SituationJournalSendStatus. id = ' . $situation_journal_send_status_id;

            } else {
                $errors[] = $send_status->errors;
                throw new Exception('updateSendStatus. Время окончания ситуации не обнавлено в таблице SituationJournalSendStatus');
            }
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'updateSendStatus.Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'updateSendStatus. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings,
            'situation_journal_send_status_id' => $situation_journal_send_status_id);
        return $result_main;
    }

    /**
     * updateSituationJournalDateTimeEndStatus - обновление времени  окончания и события ситуации в таблице situation_journal
     * алгоритм:
     * создаем ситуацию в журнале ситуаций
     * создаем статус ситуации как неподтвержденная - т.к. неизвестно, является она штатной или внештатной
     * @param $situation_journal_id -   ключ журанла ситуации
     * @param $date_time_end -   дата окончания ситуации
     * @return array
     */
    public static function updateSituationJournalDateTimeEndStatus($situation_journal_id, $date_time_end, $situation_status_id)
    {
        $errors = array();
        $status = 1;
        $result = null;
        $warnings = array();
        $warnings[] = 'updateSituationJournal. Начало метода';

        try {
            $situation_journal = SituationJournal::findOne(['id' => $situation_journal_id]);
            if (!$situation_journal) {
                throw new Exception('updateSituationJournal. Конкретная ситуация не найдена в журнале ситуаций');
            }

            $situation_journal->date_time_end = $date_time_end;
            $situation_journal->status_id = $situation_status_id;

            if ($situation_journal->save()) {
                $situation_journal_id = $situation_journal->id;
                $warnings[] = 'createSituationJournalEntry. Ситуация добавлена в таблицу situation_journal. id = ' . $situation_journal_id;

                $situation_status = new SituationStatus();
                $situation_status->situation_journal_id = $situation_journal_id;
                $situation_status->date_time = $date_time_end;
                $situation_status->status_id = $situation_status_id;                                                    // неподтвержденная ситуация 34
                if ($situation_status->save()) {
                    $warnings[] = 'createSituationJournalEntry. Статус ситуации добавлен в таблицу situation_status';
                } else {
                    $errors[] = $situation_status->errors;
                    throw new Exception('createSituationJournalEntry. Ошибка при сохранении в таблицу situation_status');
                }
                $warnings[] = 'updateSituationJournal. Время окончания ситуации обновлено в таблице situation_journal. id = ' . $situation_journal_id;

            } else {
                $errors[] = $situation_journal->errors;
                throw new Exception('updateSituationJournal. Время окончания ситуации не обнавлено в таблице situation_journal');
            }
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'updateSituationJournal.Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'updateSituationJournal. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings,
            'situation_journal_id' => $situation_journal_id);
        return $result_main;
    }

    /**
     * getSituationByEventId - получить ситуацию с минимальным количеством событий в ней, содержащую передоваемое событие
     * алгоритм:
     * получить  все ситуации, содержащие запрашиваемый event_id
     * получить для найденных ситуаций количество событий в них
     * отсортировать по возрастанию
     * взять первое событие и вернуть его
     * @param $event_id -   идентификатор ситуации
     * @return array
     */
    public static function getSituationByEventId($event_id)
    {
        $errors = array();
        $status = 1;
        $result = null;
        $warnings = array();
        $warnings[] = 'getSituationByEventId. Начало метода';

        try {
            // получить  все ситуации, содержащие запрашиваемый event_id
            $warnings[] = 'getSituationByEventId. Искомый event_id ' . $event_id;
            $situation_ids = EventSituation::find()
                ->select('situation_id')
                ->where(['event_id' => $event_id])
                ->column();
            $warnings[] = 'getSituationByEventId. Все Ситуации содержащий данное событие';
            $warnings[] = $situation_ids;
            if ($situation_ids) {
                // получить для найденных ситуаций количество событий в них
                // отсортировать по возрастанию
                // взять первое событие и вернуть его
                $situation_id = EventSituation::find()
                    ->select('situation_id, count(event_id) as count_event')
                    ->where(['situation_id' => $situation_ids])
                    ->groupBy('situation_id')
                    ->orderBy(['count_event' => SORT_ASC])
                    ->scalar();
                $warnings[] = 'getSituationByEventId. Выбранная ситуация с наименьшим количеством событий';
                // получаем саму ситуацию
                $situation = Situation::findOne(['id' => $situation_id]);
                $result = $situation;
            } else {
                $warnings[] = 'getSituationByEventId. По искомому event_id нет ситуаций';
            }
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'getSituationByEventId.Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'getSituationByEventId. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * getSituationByEvents - получить ситуацию с минимальным количеством событий в ней, содержащую передоваемые события
     * алгоритм:
     * получить  все ситуации, содержащие запрашиваемые $events
     * получить для найденных ситуаций количество событий в них
     * отсортировать по возрастанию
     * взять первое событие и вернуть его
     * @param $events -  весь список событий в
     * @param $event_id -  ключ события которое произовшло в данный момент
     * @param $event_key -  составной ключ из событий
     * @return array
     */
    public static function getSituationByEvents($events, $event_id, $event_key)
    {
        $log = new LogAmicumFront("getSituationByEvents");
        $result = null;

        try {
            $log->addLog("Начало метода");
//            $log->addData($events, '$events Искомые events', __LINE__);
            // получить  все ситуации, содержащие запрашиваемый event_id

            $situation_ids = EventSituation::find()
                ->select('situation_id')
                ->where(['event_id' => $events])
                ->column();

//            $log->addData($situation_ids, 'Все Ситуации содержащий данное событие', __LINE__);

            if ($situation_ids) {
                // получить для найденных ситуаций количество событий в них
                // отсортировать по возрастанию
                // взять первое событие и вернуть его
//                $find_situation = EventSituation::find()
//                    ->select('situation_id, count(event_id) as key_event_id')
//                    ->where(['situation_id' => $situation_ids])
//                    ->groupBy('situation_id')
//                    ->having(['key_event_id' => $event_key])
//                    ->asArray()
//                    ->all();
                $find_situations = EventSituation::find()
                    ->select('situation_id, event_id')
                    ->where(['situation_id' => $situation_ids])
                    ->orderBy('event_id')
                    ->asArray()
                    ->all();

                $log->addLog("Выбранная ситуация с запрашиваемыми событиями");

                if (!$find_situations) {
                    throw new Exception('Ситуации не найдены');
                }

                foreach ($find_situations as $sit_item) {
                    if (!isset($sit_group[$sit_item['situation_id']])) {
                        $sit_group[$sit_item['situation_id']]['situation_id'] = $sit_item['situation_id'];
                        $sit_group[$sit_item['situation_id']]['event_ids'] = "";
                    }
                    $sit_group[$sit_item['situation_id']]['event_ids'] .= $sit_item['event_id'];
                }

//                $log->addData($sit_group, 'Сгруппировал в ситуации', __LINE__);

                foreach ($sit_group as $sit_item) {
                    $event_group[$sit_item['event_ids']]['situation_id'] = $sit_item['situation_id'];
                    $event_group[$sit_item['event_ids']]['event_ids'] = $sit_item['event_ids'];
                    $event_group[$sit_item['event_ids']]['event_length'] = strlen($sit_item['event_ids']);
                }

//                $log->addData($event_group, 'Сгруппировал в события', __LINE__);
//                $log->addData($event_key, 'составной ключ из событий, который ищем в группе ситуаций', __LINE__);

                foreach ($event_group as $event_item) {
                    if (strripos($event_item['event_ids'], $event_key) !== false) {
                        $sit_group_final[] = array('situation_id' => $event_item['situation_id'],
                            'event_ids' => $event_item['event_ids'],
                            'event_length' => $event_item['event_length']);
                    }
                }

                // получаем саму ситуацию
                if (isset($sit_group_final)) {
                    $situation = Situation::findOne(['id' => $sit_group_final[0]['situation_id']]);
//                    $log->addData($sit_group_final, 'Ситуации на выходе', __LINE__);
//                    $log->addData($situation, 'Ситуации на выходе', __LINE__);
                } else {
                    $situation_ids = EventSituation::find()
                        ->select('situation_id, count(event_id) as count_event_id')
                        ->where(['event_id' => $events])
                        ->orderBy('count_event_id')
                        ->groupBy(['situation_id'])
                        ->asArray()
                        ->all();

//                    $log->addData($situation_ids, 'иду в обход. Получил список ситуаций', __LINE__);

                    $situation = Situation::findOne(['id' => $situation_ids[0]['situation_id']]);
                    if (!$situation) {
                        $situation = false;
                    }

//                    $log->addData($situation, 'Нет такой ситуации с совпадением на данный ключ', __LINE__);
                }

                $result = $situation;
            } else {
                $log->addLog("По искомому event_id нет ситуаций");
            }
            $log->addLog("Конец метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * createSituationJournalZone - Создание опасных зон ситуации в БД (массовая вставка)
     * @param $zones - массив зон для вставки
     * @param $situation_journal_id - идентификатор объекта с которым связано событие
     * @param $mine_id - ключ шахтного поля
     * @param $situation_id - ключ типа ситуации
     * алгоритм:
     * 1. готовим данные для массовой вставки
     * 2. вставляем в БД
     * @return array
     */
    public static function createSituationJournalZone($situation_journal_id, $zones, $mine_id = null, $situation_id = null)
    {
        $errors = array();
        $status = 1;
        $result = null;
        $warnings = array();
        $warnings[] = 'createSituationJournalZone. Начало метода';

        try {

            // готовим массив для массовой вставки, а так же для вставки его в кеш
            foreach ($zones as $edge_id) {
                $situation_journal_zone[] = array(
                    'edge_id' => $edge_id,
                    'situation_journal_id' => $situation_journal_id,
                    'mine_id' => $mine_id,
                    'situation_id' => $situation_id
                );
                $situation_journal_zone_for_insert[] = array(
                    'edge_id' => $edge_id,
                    'situation_journal_id' => $situation_journal_id
                );
            }

//            $insert_result_to_MySQL = Yii::$app->db->createCommand()->batchInsert('situation_journal_zone', ['edge_id', 'situation_journal_id'], $situation_journal_zone_for_insert)->execute();

            $insert_param_val = Yii::$app->db->queryBuilder->batchInsert('situation_journal_zone', ['edge_id', 'situation_journal_id'], $situation_journal_zone_for_insert);
            $insert_result_to_MySQL = Yii::$app->db->createCommand($insert_param_val . " ON DUPLICATE KEY UPDATE `edge_id` = VALUES (`edge_id`), `situation_journal_id` = VALUES (`situation_journal_id`)")->execute();

            $warnings[] = "закончил вставку данных в worker_object";
//            if (!$insert_result_to_MySQL) {
//                throw new Exception('createSituationJournalZone. Ошибка массовой вставки зон ситуаций в БД ' . $insert_result_to_MySQL);
//            }

        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'createSituationJournalZone.Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'createSituationJournalZone. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings,
            'situation_journal_zone' => $situation_journal_zone);
        return $result_main;
    }

    /**
     * addSituationJournalZone - Добавление опасной зоны ситуации в БД (разовая вставка)
     * @param $zones - добавляемые выработки в БД
     * @param $situation_journal_id - идентификатор объекта с которым связано событие
     * алгоритм:
     * 1. готовим данные для массовой вставки
     * 2. вставляем в БД
     * @return array
     */
    public static function addSituationJournalZone($situation_journal_id, $zones)
    {
        $errors = array();
        $status = 1;
        $result = null;
        $warnings = array();
        $warnings[] = 'addSituationJournalZone. Начало метода';

        try {
            $find_situation_journal_zone = SituationJournalZone::find()->where(['situation_journal_id' => $situation_journal_id])->asArray()->indexBy('edge_id')->all();
            foreach ($zones as $edge_id) {
                if (!isset($find_situation_journal_zone[$edge_id])) {
                    $add_zone = new SituationJournalZone();
                    $add_zone->edge_id = $edge_id;
                    $add_zone->situation_journal_id = $situation_journal_id;
                    if ($add_zone->save()) {
                        $warnings[] = 'addSituationJournalZone. Записал выработку в зону в таблицу SituationJournalZone';
                    } else {
                        $errors[] = $add_zone->errors;
                        throw new Exception('addSituationJournalZone. Ошибка записи выработки в зону в таблицу SituationJournalZone');
                    }
                }
            }

        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'addSituationJournalZone.Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'addSituationJournalZone. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }
}