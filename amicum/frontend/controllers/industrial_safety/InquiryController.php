<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\industrial_safety;


use backend\controllers\Assistant as BackendAssistant;
use DateTime;
use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\handbooks\DepartmentController;
use frontend\models\Attachment;
use frontend\models\CasePb;
use frontend\models\DocumentEventPb;
use frontend\models\DocumentEventPbAttachment;
use frontend\models\DocumentEventPbStatus;
use frontend\models\EventPb;
use frontend\models\EventPbWorker;
use frontend\models\InquiryAttachment;
use frontend\models\InquiryDocument;
use frontend\models\InquiryPb;
use frontend\models\KindCrash;
use frontend\models\KindIncident;
use frontend\models\KindMishap;
use frontend\models\Mine;
use frontend\models\Outcome;
use frontend\models\VidDocument;
use frontend\models\Worker;
use Yii;
use yii\db\Query;

class InquiryController extends \yii\web\Controller
{
    // в других местах:
    // Метод GetCompanyList()   - метод получения списка компаний (справочник) 127.0.0.1/read-manager-amicum?controller=handbooks\HandbookEmployee&method=GetCompanyList&subscribe=&data={}
    // GetListPosition          - получить список должностей пример: http://127.0.0.1/read-manager-amicum?controller=handbooks\HandbookPosition&method=GetListPosition&subscribe=&data={}

    // контроллер травматизма и происшествий
    // GetJournalEventPb                - получение журнала регистрации несчастных случаем на производстве
    // GetJournalInquiry                - получение журнала расследований
    // GetListCasePb                    - получить список  обстоятельств
    // GetListOutcome                   - получить список Последствия несчастного случая
    // GetListVidDocument               - получить список видов документов ПБ в несчастных случаях
    // DeleteEventPb                    - удалить событие/несчастный случай на производстве
    // SaveJournalInquiry               - сохранение расследования события/несчастного случая на производстве
    // GetListKindIncident              - получить список видов инцидентов
    // GetListKindCrash                 - получить список видов аварий
    // GetListKindMishap                - получить список несчастных случаев
    // DeleteInquirePb                  - удалить расследование
    // DeleteDocumentInquiryPb          - удалить документ расследования
    // GetListMine                      - получить список ОПО - шахтных полей
    // GetMineDetail                    - метод получения конкретных данных по шахте
    // DeleteWorkerEventPb              - удалить пострадавшего из несчестного случая


    // GetMineDetail        - метод получения конкретных данных по шахте
    // структура входного массива:
    //            mine_id:      - ключ несчастного случая в журнале регистрации
    // выходные данные:
    //            Items             - количество удаленных записей
    // выходная структура, аналогична входной
    // пример: http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Inquiry&method=GetMineDetail&subscribe=&data={"mine_id":140388}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function GetMineDetail($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $result = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        try {
            $warnings[] = "GetMineDetail. Начал выполнять метод";
            if ($data_post !== NULL && $data_post !== '')                                                                   // Проверяем передана ли входная JSON строка
            {
                $warnings[] = 'GetMineDetail. Данные успешно переданы';
                $warnings[] = 'GetMineDetail. Входной массив данных' . $data_post;
            } else {
                throw new Exception('GetMineDetail. Не получена входная JSON строка');
            }
            $post_dec = json_decode($data_post);                                                                            // Декодируем входной массив данных
            $warnings[] = 'GetMineDetail. Декодировал входные параметры';
            if (
            property_exists($post_dec, 'mine_id')
            )                                                             // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'GetMineDetail. Данные с фронта получены';
            } else {
                throw new Exception('GetMineDetail. Переданы некорректные входные параметры');
            }

            $mine_id = $post_dec->mine_id;                                                                            // дата на которую выбираем данные

            $mine_handbook_list = (new Query())
                ->select([
                    'mine_id',
                    'mine_parameter_id',
                    'parameter_id',
                    'parameter_type_id',
                    'value',
                    'date_time'
                ])
                ->from('view_mine_parameter_handbook_value_last')
                ->indexBy('parameter_id')
                ->where(['mine_id' => $mine_id])
                ->all();

            if ($mine_handbook_list) {
                $result = $mine_handbook_list;
            } else {
                $result = (object)array();
            }
        } catch (\Throwable $exception) {
            $errors[] = "GetMineDetail. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $warnings[] = "GetMineDetail. Закончил выполнять метод";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }
    // DeleteEventPb - удалить событие/несчастный случай на производстве
    // структура входного массива:
    //            event_pb_id:      - ключ несчастного случая в журнале регистрации
    // выходные данные:
    //            Items             - количество удаленных записей
    // выходная структура, аналогична входной
    // пример: http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Inquiry&method=DeleteEventPb&subscribe=&data={"company_department_id":802,"date_time":"2019-09-24"}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function DeleteEventPb($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $result = array();                                                                                              // Массив предупреждений
        $delete_order = -1;                                                                                              // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $warnings[] = "DeleteEventPb. Начал выполнять метод";
            if ($data_post !== NULL && $data_post !== '')                                                                   // Проверяем передана ли входная JSON строка
            {
                $warnings[] = 'DeleteEventPb. Данные успешно переданы';
                $warnings[] = 'DeleteEventPb. Входной массив данных' . $data_post;
            } else {
                throw new Exception('DeleteEventPb. Не получена входная JSON строка');
            }
            $post_dec = json_decode($data_post);                                                                            // Декодируем входной массив данных
            $warnings[] = 'DeleteEventPb. Декодировал входные параметры';
            if (
            property_exists($post_dec, 'event_pb_id')
            )                                                             // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'DeleteEventPb. Данные с фронта получены';
            } else {
                throw new Exception('DeleteEventPb. Переданы некорректные входные параметры');
            }

            $event_pb_id = $post_dec->event_pb_id;                                                                            // дата на которую выбираем данные

            //$delete_order = Yii::$app->db->createCommand()->delete('event_pb', 'id=' . $event_pb_id)->execute();
            $delete_order = EventPb::deleteAll(['id' => $event_pb_id]);
            $warnings[] = "DeleteEventPb. Удалил событие - несчастный случай. Количество " . $delete_order;

            $transaction->commit();
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            $errors[] = "DeleteEventPb. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $warnings[] = "DeleteEventPb. Закончил выполнять метод";
        $result = $delete_order;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // DeleteInquiryPb - удалить расследование
    // структура входного массива:
    //            inquire_id:      - ключ несчастного случая в журнале регистрации
    // выходные данные:
    //            Items             - количество удаленных записей
    // выходная структура, аналогична входной
    // пример: http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Inquiry&method=DeleteInquiryPb&subscribe=&data={"company_department_id":802,"date_time":"2019-09-24"}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function DeleteInquiryPb($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $result = array();                                                                                              // Массив предупреждений
        $delete_order = -1;                                                                                              // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $warnings[] = "DeleteInquiryPb. Начал выполнять метод";
            if ($data_post !== NULL && $data_post !== '')                                                                   // Проверяем передана ли входная JSON строка
            {
                $warnings[] = 'DeleteInquiryPb. Данные успешно переданы';
                $warnings[] = 'DeleteInquiryPb. Входной массив данных' . $data_post;
            } else {
                throw new Exception('DeleteInquiryPb. Не получена входная JSON строка');
            }
            $post_dec = json_decode($data_post);                                                                            // Декодируем входной массив данных
            $warnings[] = 'DeleteInquiryPb. Декодировал входные параметры';
            if (
            property_exists($post_dec, 'inquiry_id')
            )                                                             // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'DeleteInquiryPb. Данные с фронта получены';
            } else {
                throw new Exception('DeleteInquiryPb. Переданы некорректные входные параметры');
            }

            $inquiry_id = $post_dec->inquiry_id;                                                                            // дата на которую выбираем данные

            //$delete_order = Yii::$app->db->createCommand()->delete('inquiry_pb', 'id=' . $inquiry_id)->execute();
            $delete_order = InquiryPb::deleteAll(['id' => $inquiry_id]);
            $warnings[] = "DeleteInquiryPb. Удалил событие - несчастный случай. Количество " . $delete_order;

            $transaction->commit();
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            $errors[] = "DeleteInquiryPb. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $warnings[] = "DeleteInquirePb. Закончил выполнять метод";
        $result = $delete_order;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // DeleteDocumentInquiryPb - удалить документ расследования
    // структура входного массива:
    //            inquiry_document_id:      - ключ несчастного случая в журнале регистрации
    // выходные данные:
    //            Items             - количество удаленных записей
    // выходная структура, аналогична входной
    // пример: http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Inquiry&method=DeleteDocumentInquiryPb&subscribe=&data={"company_department_id":802,"date_time":"2019-09-24"}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function DeleteDocumentInquiryPb($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $result = array();                                                                                              // Массив предупреждений
        $delete_order = -1;                                                                                              // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $warnings[] = "DeleteDocumentInquiryPb. Начал выполнять метод";
            if ($data_post !== NULL && $data_post !== '')                                                                   // Проверяем передана ли входная JSON строка
            {
                $warnings[] = 'DeleteDocumentInquiryPb. Данные успешно переданы';
                $warnings[] = 'DeleteDocumentInquiryPb. Входной массив данных' . $data_post;
            } else {
                throw new Exception('DeleteDocumentInquiryPb. Не получена входная JSON строка');
            }
            $post_dec = json_decode($data_post);                                                                            // Декодируем входной массив данных
            $warnings[] = 'DeleteDocumentInquiryPb. Декодировал входные параметры';
            if (
                property_exists($post_dec, 'inquiry_pb_id') &&
                property_exists($post_dec, 'document_event_pb_id')
            )                                                             // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'DeleteDocumentInquiryPb. Данные с фронта получены';
            } else {
                throw new Exception('DeleteDocumentInquiryPb. Переданы некорректные входные параметры');
            }

            $inquiry_pb_id = $post_dec->inquiry_pb_id;                                                                            // дата на которую выбираем данные
            $document_event_pb_id = $post_dec->document_event_pb_id;                                                                            // дата на которую выбираем данные

            //$delete_order = Yii::$app->db->createCommand()->delete('inquiry_document', 'inquiry_id=' . $inquiry_pb_id . ' and document_event_pb_id=' . $document_event_pb_id)->execute();
            $delete_order = InquiryDocument::deleteAll(['inquiry_id' => $inquiry_pb_id, 'document_event_pb_id' => $document_event_pb_id]);
            $warnings[] = "DeleteDocumentInquiryPb. Удалил вложение расследования. Количество " . $delete_order;

            $transaction->commit();
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            $errors[] = "DeleteDocumentInquiryPb. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $warnings[] = "DeleteDocumentInquiryPb. Закончил выполнять метод";
        $result = $delete_order;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // DeleteDocumentEventPbAttachment - удалить документ вложения расследования
    // структура входного массива:
    //            document_event_pb_attachment_id:      - ключ несчастного случая в журнале регистрации
    // выходные данные:
    //            Items             - количество удаленных записей
    // выходная структура, аналогична входной
    // пример: http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Inquiry&method=DeleteDocumentInquiryPb&subscribe=&data={"company_department_id":802,"date_time":"2019-09-24"}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function DeleteDocumentEventPbAttachment($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $result = array();                                                                                              // Массив предупреждений
        $delete_order = -1;                                                                                              // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $warnings[] = "DeleteDocumentEventPbAttachment. Начал выполнять метод";
            if ($data_post !== NULL && $data_post !== '')                                                                   // Проверяем передана ли входная JSON строка
            {
                $warnings[] = 'DeleteDocumentEventPbAttachment. Данные успешно переданы';
                $warnings[] = 'DeleteDocumentEventPbAttachment. Входной массив данных' . $data_post;
            } else {
                throw new Exception('DeleteDocumentEventPbAttachment. Не получена входная JSON строка');
            }
            $post_dec = json_decode($data_post);                                                                            // Декодируем входной массив данных
            $warnings[] = 'DeleteDocumentEventPbAttachment. Декодировал входные параметры';
            if (
            property_exists($post_dec, 'document_event_pb_attachment_id')
            )                                                             // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'DeleteDocumentEventPbAttachment. Данные с фронта получены';
            } else {
                throw new Exception('DeleteDocumentEventPbAttachment. Переданы некорректные входные параметры');
            }

            $document_event_pb_attachment_id = $post_dec->document_event_pb_attachment_id;                                                                            // дата на которую выбираем данные

            //$delete_order = Yii::$app->db->createCommand()->delete('document_event_pb_attachment', 'id=' . $document_event_pb_attachment_id)->execute();
            $delete_order = DocumentEventPbAttachment::deleteAll(['id' => $document_event_pb_attachment_id]);
            $warnings[] = "DeleteDocumentEventPbAttachment. Удалил вложение расследования. Количество " . $delete_order;

            $transaction->commit();
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            $errors[] = "DeleteDocumentEventPbAttachment. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $warnings[] = "DeleteDocumentEventPbAttachment. Закончил выполнять метод";
        $result = $delete_order;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // SaveJournalEventPb - сохранение события/несчастного случая на производстве
    // структура входного массива:
    //            event_pb_id:                                  - ключ несчастного случая в журнале регистрации
    //            place_name:                                   - место несчастного случая
    //            assessment_place_id:                          - ключ специальной оценки места
    //            case_pb_id:                                      - ключ обстоятельства
    //            date_time_event:                              - дата/время несчастного случая
    //            description_event_pb:                         - описание несчастного случая при котором произошел несчастный случай
    //            description_correct_measure:                  - корректирующие мероприятия - принятые меры по устранению причин несчастного случая
    //            company_department_id:                        - ключ департамента
    //            inquiry_pb_id:                                - ключ акта расследования
    //            workers:
    //                  [worker_id]                             - ключ работника с которым случилось чп
    //                        worker_id:                        - ключ работника с которым случилось чп
    //                        role_id:                          - ключ роли работника
    //                        position_id:                      - ключ должностей
    //                        outcome_id:                       - ключ последствия
    //                        value_day:                        - дней нетрудоспособности
    //                        birthday:                         - день рождения пострадавшего работника
    //                        experience:                       - опыт пострадавшего работника
    // выходная структура, аналогична входной
    // пример: http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Inquiry&method=SaveJournalEventPb&subscribe=&data={"company_department_id":802,"date_time":"2019-09-24"}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function SaveJournalEventPb($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $result = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $warnings[] = "SaveJournalEventPb. Начал выполнять метод";
            if ($data_post !== NULL && $data_post !== '')                                                                   // Проверяем передана ли входная JSON строка
            {
                $warnings[] = 'SaveJournalEventPb. Данные успешно переданы';
                $warnings[] = 'SaveJournalEventPb. Входной массив данных' . $data_post;
            } else {
                throw new Exception('SaveJournalEventPb. Не получена входная JSON строка');
            }
            $post_dec = json_decode($data_post);                                                                            // Декодируем входной массив данных
            $warnings[] = 'SaveJournalEventPb. Декодировал входные параметры';
            if (
            property_exists($post_dec, 'event_pb')
            )                                                             // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'SaveJournalEventPb. Данные с фронта получены';
            } else {
                throw new Exception('SaveJournalEventPb. Переданы некорректные входные параметры');
            }

            $event_pb = $post_dec->event_pb;                                                                            // дата на которую выбираем данные
            $new_event_pb = EventPb::findOne(['id' => $event_pb->event_pb_id]);
            if (!$new_event_pb) {
                $new_event_pb = new EventPb();
            } else {
                //$delete_order = Yii::$app->db->createCommand()->delete('event_pb_worker', 'event_pb_id=' . $event_pb->event_pb_id)->execute();
                $delete_order = EventPbWorker::deleteAll(['event_pb_id' => $event_pb->event_pb_id]);
                $warnings[] = "SaveJournalEventPb. Удалил всех потерпевших. Количество " . $delete_order;
            }
            $new_event_pb->place_name = $event_pb->place_name;
            if ($event_pb->assessment_place_id != -1) {
                $new_event_pb->assessment_place_id = $event_pb->assessment_place_id;
            }
            if ($event_pb->case_pb_id != -1) {
                $new_event_pb->case_pb_id = $event_pb->case_pb_id;
            }
            $new_event_pb->date_time_event = $event_pb->date_time_event;
            $new_event_pb->description_event_pb = $event_pb->description_event_pb;
            $new_event_pb->description_correct_measure = $event_pb->description_correct_measure;
            $new_event_pb->company_department_id = $event_pb->company_department_id;
            if ($event_pb->inquiry_pb_id != -1) {
                $new_event_pb->inquiry_pb_id = $event_pb->inquiry_pb_id;
            }
            $new_event_pb->kind_crash_id = $event_pb->kind_crash_id;
            $new_event_pb->kind_incident_id = $event_pb->kind_incident_id;
            $new_event_pb->kind_mishap_id = $event_pb->kind_mishap_id;
            $new_event_pb->kind_miscellaneous = $event_pb->kind_miscellaneous;
            $new_event_pb->description_incident = $event_pb->description_incident;
            $new_event_pb->description_committee = $event_pb->description_committee;
            $new_event_pb->description_crash = $event_pb->description_crash;
            $new_event_pb->economic_damage = str_replace(',', '.', $event_pb->economic_damage);
            $new_event_pb->lost_energy = str_replace(',', '.', $event_pb->lost_energy);
            $new_event_pb->duration_stop = str_replace(',', '.', $event_pb->duration_stop);
            $new_event_pb->exist_victim = $event_pb->exist_victim;
            $new_event_pb->description_measure = $event_pb->description_measure;
            $new_event_pb->date_time_direction_to_cop = $event_pb->date_time_direction_to_cop;
            $new_event_pb->status_id = $event_pb->status_id;
            $new_event_pb->status_date_time = $event_pb->status_date_time;
            $new_event_pb->mine_id = $event_pb->mine_id;

            $mine_handbook_list = (new Query())
                ->select([
                    'mine_id',
                    'mine_parameter_id',
                    'parameter_id',
                    'parameter_type_id',
                    'value',
                    'date_time'
                ])
                ->from('view_mine_parameter_handbook_value_last')
                ->indexBy('parameter_id')
                ->where(['mine_id' => $event_pb->mine_id])
                ->andWhere(['OR', ['parameter_id' => 697, 'parameter_type_id' => 1], ['parameter_id' => 16, 'parameter_type_id' => 1]])
                ->all();
            $mine_array = array();
            foreach ($mine_handbook_list as $mine_handbook) {
                $mine_array[$mine_handbook['mine_id']][$mine_handbook['parameter_id']] = $mine_handbook['value'];
            }

            if (isset($mine_array[$event_pb->mine_id][697])) {
                $event_pb->opo_date = $mine_array[$event_pb->mine_id][697];
            } else {
                $event_pb->opo_date = '';
            }
            if (isset($mine_array[$event_pb->mine_id][16])) {
                $event_pb->opo_number = $mine_array[$event_pb->mine_id][16];
            } else {
                $event_pb->opo_number = '';
            }

            if ($new_event_pb->save()) {
                $new_event_pb->refresh();
                $new_event_pb_id = $new_event_pb->id;
                $event_pb->event_pb_id = $new_event_pb_id;
//                $event_pb->kind_mishap_title =
                $warnings[] = 'SaveJournalEventPb. Данные успешно сохранены в модель EventPb';
            } else {
                $errors[] = $new_event_pb->errors;
                throw new Exception('SaveJournalEventPb. Ошибка сохранения модели EventPb');
            }
            $worker_ids = array();
            if ($event_pb->workers) {
                foreach ($event_pb->workers as $worker) {
                    if ($worker->worker_id != -1) {
                        $worker_ids[] = $worker->worker_id;
                    }
                }

                $get_workers = Worker::find()
                    ->select(['worker.id as id', 'date_start'])
                    ->innerJoinWith('employee')
                    ->where(['in', 'worker.id', $worker_ids])
                    ->indexBy('worker.id')
                    ->asArray()
                    ->all();
                $warnings['workers'] = $get_workers;
                $warnings['$worker_ids'] = $worker_ids;
                foreach ($event_pb->workers as $worker) {
                    if ($worker->worker_id != -1) {
                        $workers_item['event_pb_id'] = $new_event_pb_id;
                        $workers_item['worker_id'] = $worker->worker_id;
                        if ($worker->role_id != -1) {
                            $workers_item['role_id'] = $worker->role_id;
                        } else {
                            $workers_item['role_id'] = null;
                        }
                        $workers_item['position_id'] = $worker->position_id;
                        if ($worker->outcome_id != -1) {
                            $workers_item['outcome_id'] = $worker->outcome_id;
                        } else {
                            $workers_item['outcome_id'] = null;
                        }
                        $workers_item['value_day'] = $worker->value_day;
                        if (isset($get_workers[$worker->worker_id]['id'])) {
                            $workers_item['birthday'] = date('Y-m-d', strtotime($get_workers[$worker->worker_id]['employee']['birthday']));
                            $date_start = new DateTime ($get_workers[$worker->worker_id]['date_start']);
                            $date_event = new DateTime($event_pb->date_time_event);
                            $diff = $date_event->diff($date_start);
                            $exp = $diff->format('%y');
                        } else {
                            $workers_item['birthday'] = date('Y-m-d');
                            $exp = $worker->experience;
                        }
                        $workers_item['experience'] = $exp;
                        $worker_array[] = $workers_item;
                        unset ($workers_item);
                    }
                }
            }
            if (isset($worker_array)) {
                $insert_event_pb_worker = Yii::$app->db->createCommand()->batchInsert('event_pb_worker',//insert_or_op_wo_status - insert order operation worker status
                    ['event_pb_id', 'worker_id', 'role_id', 'position_id', 'outcome_id', 'value_day', 'birthday', 'experience'],
                    $worker_array)
                    ->execute();
                unset($worker_array);
                if ($insert_event_pb_worker !== 0) {
                    $warnings[] = 'SaveJournalEventPb. Список работников успешно сохранен. Количество вставленных записей ' . $insert_event_pb_worker;
                    $status *= 1;
                } else {
                    $errors[] = 'SaveJournalEventPb. Список работников не сохранене';
                    $status = 0;
                }
            }
            $transaction->commit();
            $journal_event_pb = $event_pb;
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            $errors[] = "SaveJournalEventPb. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        if (!isset($journal_event_pb)) {
            $journal_event_pb = (object)array();
        }
        $warnings[] = "SaveJournalEventPb. Закончил выполнять метод";
        $result = $journal_event_pb;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // DeleteWorkerEventPb - удалить пострадавшего из несчестного случая
    // выходная структура, аналогична входной
    // пример: http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Inquiry&method=DeleteWorkerEventPb&subscribe=&data={"company_department_id":802,"date_time":"2019-09-24"}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function DeleteWorkerEventPb($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $result = array();                                                                                              // Массив предупреждений
        $delete_order = 0;                                                                                              // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $warnings[] = "DeleteWorkerEventPb. Начал выполнять метод";
            if ($data_post !== NULL && $data_post !== '')                                                                   // Проверяем передана ли входная JSON строка
            {
                $warnings[] = 'DeleteWorkerEventPb. Данные успешно переданы';
                $warnings[] = 'DeleteWorkerEventPb. Входной массив данных' . $data_post;
            } else {
                throw new Exception('DeleteWorkerEventPb. Не получена входная JSON строка');
            }
            $post_dec = json_decode($data_post);                                                                            // Декодируем входной массив данных
            $warnings[] = 'DeleteWorkerEventPb. Декодировал входные параметры';
            if (
                property_exists($post_dec, 'event_pb_id') &&
                property_exists($post_dec, 'worker_id')
            )                                                             // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'DeleteWorkerEventPb. Данные с фронта получены';
            } else {
                throw new Exception('DeleteWorkerEventPb. Переданы некорректные входные параметры');
            }

            $event_pb_id = $post_dec->event_pb_id;                                                                            // дата на которую выбираем данные
            $worker_id = $post_dec->worker_id;                                                                            // дата на которую выбираем данные

            //$delete_order = Yii::$app->db->createCommand()->delete('event_pb_worker', 'event_pb_id=' . $event_pb_id . ' and worker_id=' . $worker_id)->execute();
            $delete_order = EventPbWorker::deleteAll(['event_pb_id' => $event_pb_id, 'worker_id' => $worker_id]);
            $warnings[] = "DeleteWorkerEventPb. Удалил всех потерпевших. Количество " . $delete_order;

            $transaction->commit();
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            $errors[] = "DeleteWorkerEventPb. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = "DeleteWorkerEventPb. Закончил выполнять метод";
        $result_main = array('Items' => $delete_order, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetJournalEventPb - получение журнала регистрации несчастных случаем на производстве
    // пример: http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Inquiry&method=GetJournalEventPb&subscribe=&data={"company_department_id":802,"date_time":"2019-09-24"}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function GetJournalEventPb($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $result = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок

        try {
            $warnings[] = "GetJournalEventPb. Начал выполнять метод";
            if ($data_post !== NULL && $data_post !== '')                                                                   // Проверяем передана ли входная JSON строка
            {
                $warnings[] = 'GetJournalEventPb. Данные успешно переданы';
                $warnings[] = 'GetJournalEventPb. Входной массив данных' . $data_post;
            } else {
                throw new Exception('GetJournalEventPb. Не получена входная JSON строка');
            }
            $post_dec = json_decode($data_post);                                                                            // Декодируем входной массив данных
            $warnings[] = 'GetJournalEventPb. Декодировал входные параметры';
            if (
                property_exists($post_dec, 'date_time') &&
                property_exists($post_dec, 'company_department_id')
            )                                                             // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'GetJournalEventPb. Данные с фронта получены';
            } else {
                throw new Exception('GetJournalEventPb. Переданы некорректные входные параметры');
            }

            $date_time = $post_dec->date_time;                                                                            // дата на которую выбираем данные
            $company_department_id = $post_dec->company_department_id;                                                    // участок на который выбираем все данные

            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_department_ids = $response['Items'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception('GetJournalEventPb. Ошибка получения вложенных департаментов' . $company_department_id);
            }
            $warnings[] = 'GetJournalEventPb. Массив вложенных подразделений';
            $warnings[] = $company_department_ids;
            // получить справчоник детальных сведений по ОПО
            $mine_handbook_list = (new Query())
                ->select([
                    'mine_id',
                    'mine_parameter_id',
                    'parameter_id',
                    'parameter_type_id',
                    'value',
                    'date_time'
                ])
                ->from('view_mine_parameter_handbook_value_last')
                ->indexBy('parameter_id')
                ->where(['parameter_id' => 16, 'parameter_type_id' => 1])
                ->orWhere(['parameter_id' => 697, 'parameter_type_id' => 1])
                ->all();
            $mine_array = array();
            foreach ($mine_handbook_list as $mine_handbook) {
                $mine_array[$mine_handbook['mine_id']][$mine_handbook['parameter_id']] = $mine_handbook['value'];
            }
            /**
             * получаем все участки с учетом вложенности
             */
            $warnings[] = (int)date("m", strtotime($date_time));
            $event_pbs = EventPb::Find()
                ->joinWith('eventPbWorkers')
                ->joinWith('kindMishap')
                ->andWhere(['in', 'company_department_id', $company_department_ids])
                ->andWhere('month(date_time_event)=' . (int)date("m", strtotime($date_time)))
                ->andWhere('year(date_time_event)=' . (int)date("Y", strtotime($date_time)))
                ->all();

            if ($event_pbs) {
                foreach ($event_pbs as $event_pb) {
                    $event_pb_id = $event_pb->id;
                    $case_pb_id = $event_pb['case_pb_id'];
                    $journal_event_pb[$case_pb_id][$event_pb_id]['event_pb_id'] = $event_pb_id;
                    $journal_event_pb[$case_pb_id][$event_pb_id]['place_name'] = $event_pb['place_name'];
                    $journal_event_pb[$case_pb_id][$event_pb_id]['assessment_place_id'] = $event_pb['assessment_place_id'];
                    $journal_event_pb[$case_pb_id][$event_pb_id]['case_pb_id'] = $event_pb['case_pb_id'];
                    $journal_event_pb[$case_pb_id][$event_pb_id]['date_time_event'] = $event_pb['date_time_event'];
                    $journal_event_pb[$case_pb_id][$event_pb_id]['description_event_pb'] = $event_pb['description_event_pb'];
                    $journal_event_pb[$case_pb_id][$event_pb_id]['description_correct_measure'] = $event_pb['description_correct_measure'];
                    $journal_event_pb[$case_pb_id][$event_pb_id]['company_department_id'] = $event_pb['company_department_id'];
                    $journal_event_pb[$case_pb_id][$event_pb_id]['inquiry_pb_id'] = $event_pb['inquiry_pb_id'];
                    $journal_event_pb[$case_pb_id][$event_pb_id]['kind_mishap_id'] = $event_pb['kind_mishap_id'];
                    if ($event_pb['kindMishap']) {
                        $journal_event_pb[$case_pb_id][$event_pb_id]['kind_mishap_title'] = $event_pb['kindMishap']['title'];
                    } else {
                        $journal_event_pb[$case_pb_id][$event_pb_id]['kind_mishap_title'] = "";
                    }
                    $journal_event_pb[$case_pb_id][$event_pb_id]['kind_crash_id'] = $event_pb['kind_crash_id'];
                    $journal_event_pb[$case_pb_id][$event_pb_id]['kind_incident_id'] = $event_pb['kind_incident_id'];
                    $journal_event_pb[$case_pb_id][$event_pb_id]['kind_miscellaneous'] = $event_pb['kind_miscellaneous'];
                    $journal_event_pb[$case_pb_id][$event_pb_id]['description_incident'] = $event_pb['description_incident'];
                    $journal_event_pb[$case_pb_id][$event_pb_id]['description_committee'] = $event_pb['description_committee'];
                    $journal_event_pb[$case_pb_id][$event_pb_id]['description_crash'] = $event_pb['description_crash'];
                    $journal_event_pb[$case_pb_id][$event_pb_id]['economic_damage'] = $event_pb['economic_damage'];
                    $journal_event_pb[$case_pb_id][$event_pb_id]['lost_energy'] = $event_pb['lost_energy'];
                    $journal_event_pb[$case_pb_id][$event_pb_id]['duration_stop'] = $event_pb['duration_stop'];
                    $journal_event_pb[$case_pb_id][$event_pb_id]['exist_victim'] = $event_pb['exist_victim'];
                    $journal_event_pb[$case_pb_id][$event_pb_id]['description_measure'] = $event_pb['description_measure'];
                    $journal_event_pb[$case_pb_id][$event_pb_id]['date_time_direction_to_cop'] = $event_pb['date_time_direction_to_cop'];
                    $journal_event_pb[$case_pb_id][$event_pb_id]['status_id'] = $event_pb['status_id'];
                    $journal_event_pb[$case_pb_id][$event_pb_id]['status_date_time'] = $event_pb['status_date_time'];
                    $journal_event_pb[$case_pb_id][$event_pb_id]['mine_id'] = $event_pb['mine_id'];
                    if (isset($mine_array[$event_pb['mine_id']][697])) {
                        $journal_event_pb[$case_pb_id][$event_pb_id]['opo_date'] = $mine_array[$event_pb['mine_id']][697];
                    } else {
                        $journal_event_pb[$case_pb_id][$event_pb_id]['opo_date'] = '';
                    }
                    if (isset($mine_array[$event_pb['mine_id']][16])) {
                        $journal_event_pb[$case_pb_id][$event_pb_id]['opo_number'] = $mine_array[$event_pb['mine_id']][16];
                    } else {
                        $journal_event_pb[$case_pb_id][$event_pb_id]['opo_number'] = '';
                    }

                    foreach ($event_pb->eventPbWorkers as $event_pb_worker) {
                        $journal_event_pb[$case_pb_id][$event_pb_id]['workers'][$event_pb_worker['worker_id']]['worker_id'] = $event_pb_worker->worker_id;
                        $journal_event_pb[$case_pb_id][$event_pb_id]['workers'][$event_pb_worker['worker_id']]['role_id'] = $event_pb_worker->role_id;
                        $journal_event_pb[$case_pb_id][$event_pb_id]['workers'][$event_pb_worker['worker_id']]['position_id'] = $event_pb_worker->position_id;
                        $journal_event_pb[$case_pb_id][$event_pb_id]['workers'][$event_pb_worker['worker_id']]['outcome_id'] = $event_pb_worker->outcome_id;
                        $journal_event_pb[$case_pb_id][$event_pb_id]['workers'][$event_pb_worker['worker_id']]['value_day'] = $event_pb_worker->value_day;
                        $journal_event_pb[$case_pb_id][$event_pb_id]['workers'][$event_pb_worker['worker_id']]['birthday'] = date('d.m.Y', strtotime($event_pb_worker['birthday']));
                        $journal_event_pb[$case_pb_id][$event_pb_id]['workers'][$event_pb_worker['worker_id']]['experience'] = $event_pb_worker->experience;
                    }
                    if (!isset($journal_event_pb[$case_pb_id][$event_pb_id]['workers'])) {
                        $journal_event_pb[$case_pb_id][$event_pb_id]['workers'] = (object)array();
                    }
                }
            }

        } catch (\Throwable $exception) {
            $errors[] = "GetJournalEventPb. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        if (!isset($journal_event_pb)) {
            $journal_event_pb = (object)array();
        }
        $warnings[] = "GetJournalEventPb. Закончил выполнять метод";
        $result = $journal_event_pb;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetJournalInquiry - получение журнала расследований
    // пример: http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Inquiry&method=GetJournalInquiry&subscribe=&data={"company_department_id":802,"date_time":"2019-09-24"}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function GetJournalInquiry($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $result = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок

        try {
            $warnings[] = "GetJournalInquiry. Начал выполнять метод";
            if ($data_post !== NULL && $data_post !== '')                                                                   // Проверяем передана ли входная JSON строка
            {
                $warnings[] = 'GetJournalInquiry. Данные успешно переданы';
                $warnings[] = 'GetJournalInquiry. Входной массив данных' . $data_post;
            } else {
                throw new Exception('GetJournalInquiry. Не получена входная JSON строка');
            }
            $post_dec = json_decode($data_post);                                                                            // Декодируем входной массив данных
            $warnings[] = 'GetJournalInquiry. Декодировал входные параметры';
            if (
                property_exists($post_dec, 'date_time') &&
                property_exists($post_dec, 'company_department_id')
            )                                                             // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'GetJournalInquiry. Данные с фронта получены';
            } else {
                throw new Exception('GetJournalInquiry. Переданы некорректные входные параметры');
            }

            $date_time = $post_dec->date_time;                                                                            // дата на которую выбираем данные
            $company_department_id = $post_dec->company_department_id;                                                    // участок на который выбираем все данные

            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_department_ids = $response['Items'];
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                $warnings[] = $response['warnings'];
                throw new Exception('GetJournalInquiry. Ошибка получения вложенных департаментов' . $company_department_id);
            }
            $warnings[] = 'GetJournalInquiry. Массив вложенных подразделений';
            $warnings[] = $company_department_ids;
            /**
             * получаем все участки с учетом вложенности
             */
            $warnings[] = (int)date("m", strtotime($date_time));
            $inquiry_pbs = InquiryPb::Find()
                ->select('
                inquiry_pb.*,

                month(date_time_event),
                year(date_time_event),
                ')
                ->joinWith('inquiryDocuments')
                ->joinWith('inquiryAttachments')
                ->joinWith('inquiryAttachments.attachment')
                ->joinWith('inquiryDocuments.documentEventPb')
                ->joinWith('inquiryDocuments.documentEventPb.documentEventPbStatuses')
                ->joinWith('inquiryDocuments.documentEventPb.documentEventPbAttachments')
                ->joinWith('inquiryDocuments.documentEventPb.documentEventPbAttachments.attachment')
                ->andWhere(['in', 'company_department_id', $company_department_ids])
                ->andWhere('month(date_time_event)=' . (int)date("m", strtotime($date_time)))
                ->andWhere('year(date_time_event)=' . (int)date("Y", strtotime($date_time)))
                ->all();

            if ($inquiry_pbs) {
                foreach ($inquiry_pbs as $inquiry_pb) {
                    // данные по расследованию
                    $inquiry_pb_id = $inquiry_pb->id;
                    $journal_inquiry_pb[$inquiry_pb_id]['inquiry_pb_id'] = $inquiry_pb->id;
                    $journal_inquiry_pb[$inquiry_pb_id]['date_time_create'] = $inquiry_pb->date_time_create;
                    $journal_inquiry_pb[$inquiry_pb_id]['case_pb_id'] = $inquiry_pb->case_pb_id;
                    $journal_inquiry_pb[$inquiry_pb_id]['description_event_pb'] = $inquiry_pb->description_event_pb;
                    $journal_inquiry_pb[$inquiry_pb_id]['worker_id'] = $inquiry_pb->worker_id;
                    $journal_inquiry_pb[$inquiry_pb_id]['date_time_event'] = $inquiry_pb->date_time_event;
                    $journal_inquiry_pb[$inquiry_pb_id]['company_department_id'] = $inquiry_pb->company_department_id;

                    //вложения расследований
                    foreach ($inquiry_pb->inquiryAttachments as $inquiryAttachment) {
                        $journal_inquiry_pb[$inquiry_pb_id]['attachments'][$inquiryAttachment->id]['inquiry_pb_attachment_id'] = $inquiryAttachment->id;
                        $journal_inquiry_pb[$inquiry_pb_id]['attachments'][$inquiryAttachment->id]['attachment_id'] = $inquiryAttachment->attachment_id;
                        if (!$inquiryAttachment->attachment) {
                            $journal_inquiry_pb[$inquiry_pb_id]['attachments'][$inquiryAttachment->id]['attachment_path'] = "";
                            $journal_inquiry_pb[$inquiry_pb_id]['attachments'][$inquiryAttachment->id]['attachment_blob'] = (object)array();
                            $journal_inquiry_pb[$inquiry_pb_id]['attachments'][$inquiryAttachment->id]['title'] = "";
                            $journal_inquiry_pb[$inquiry_pb_id]['attachments'][$inquiryAttachment->id]['attachment_type'] = "";
                            $journal_inquiry_pb[$inquiry_pb_id]['attachments'][$inquiryAttachment->id]['sketch'] = (object)array();
                            $journal_inquiry_pb[$inquiry_pb_id]['attachments'][$inquiryAttachment->id]['attachment_status'] = "";
                        } else {
                            $journal_inquiry_pb[$inquiry_pb_id]['attachments'][$inquiryAttachment->id]['attachment_path'] = $inquiryAttachment->attachment->path;
                            $journal_inquiry_pb[$inquiry_pb_id]['attachments'][$inquiryAttachment->id]['attachment_blob'] = (object)array();
                            $journal_inquiry_pb[$inquiry_pb_id]['attachments'][$inquiryAttachment->id]['title'] = $inquiryAttachment->attachment->title;
                            $journal_inquiry_pb[$inquiry_pb_id]['attachments'][$inquiryAttachment->id]['attachment_type'] = $inquiryAttachment->attachment->attachment_type;
                            $journal_inquiry_pb[$inquiry_pb_id]['attachments'][$inquiryAttachment->id]['sketch'] = $inquiryAttachment->attachment->sketch;
                            $journal_inquiry_pb[$inquiry_pb_id]['attachments'][$inquiryAttachment->id]['attachment_status'] = "";
                        }
                    }
                    if (!isset($journal_inquiry_pb[$inquiry_pb_id]['attachments'])) {
                        $journal_inquiry_pb[$inquiry_pb_id]['attachments'] = (object)array();
                    }

                    // данные по документам
                    foreach ($inquiry_pb->inquiryDocuments as $inquiryDocument) {
                        if ($inquiryDocument->documentEventPb) {
                            $vid_document_id = $inquiryDocument->documentEventPb['vid_document_id'];
                            $journal_inquiry_pb[$inquiry_pb_id]['documents'][$vid_document_id]['vid_document_id'] = $vid_document_id;
                            $journal_inquiry_pb[$inquiry_pb_id]['documents'][$vid_document_id]['document_event_pb_id'] = $inquiryDocument->documentEventPb['id'];
                            $journal_inquiry_pb[$inquiry_pb_id]['documents'][$vid_document_id]['number_document'] = $inquiryDocument->documentEventPb['number_document'];
                            $journal_inquiry_pb[$inquiry_pb_id]['documents'][$vid_document_id]['parent_document_id'] = $inquiryDocument->documentEventPb['parent_document_id'];
                            $journal_inquiry_pb[$inquiry_pb_id]['documents'][$vid_document_id]['document_title'] = $inquiryDocument->documentEventPb['title'];
                            $journal_inquiry_pb[$inquiry_pb_id]['documents'][$vid_document_id]['date_start'] = $inquiryDocument->documentEventPb['date_start'];
                            $journal_inquiry_pb[$inquiry_pb_id]['documents'][$vid_document_id]['date_end'] = $inquiryDocument->documentEventPb['date_end'];
                            $journal_inquiry_pb[$inquiry_pb_id]['documents'][$vid_document_id]['last_status_id'] = $inquiryDocument->documentEventPb['last_status_id'];
                            $journal_inquiry_pb[$inquiry_pb_id]['documents'][$vid_document_id]['last_worker_id'] = $inquiryDocument->documentEventPb['worker_id'];
                            $journal_inquiry_pb[$inquiry_pb_id]['documents'][$vid_document_id]['jsondoc'] = $inquiryDocument->documentEventPb['jsondoc'];
                            // статусы документов
                            foreach ($inquiryDocument->documentEventPb->documentEventPbStatuses as $documentEventPbStatus) {
                                $journal_inquiry_pb_item['document_event_pb_status_id'] = $documentEventPbStatus->id;
                                $journal_inquiry_pb_item['status_id'] = $documentEventPbStatus->status_id;
                                $journal_inquiry_pb_item['worker_id'] = $documentEventPbStatus->worker_id;
                                $journal_inquiry_pb_item['date_time_create'] = $documentEventPbStatus->date_time_create;
                                $journal_inquiry_pb[$inquiry_pb_id]['documents'][$vid_document_id]['statuses'][$documentEventPbStatus->id] = $journal_inquiry_pb_item;
                            }
                            if (!isset($journal_inquiry_pb[$inquiry_pb_id]['documents'][$vid_document_id]['statuses'])) {
                                $journal_inquiry_pb[$inquiry_pb_id]['documents'][$vid_document_id]['statuses'] = array();
                            }
                            // сами вложения документов
                            foreach ($inquiryDocument->documentEventPb->documentEventPbAttachments as $documentEventPbAttachment) {
                                $journal_inquiry_pb[$inquiry_pb_id]['documents'][$vid_document_id]['attachments'][$documentEventPbAttachment->id]['document_event_pb_attachment_id'] = $documentEventPbAttachment->id;
                                $journal_inquiry_pb[$inquiry_pb_id]['documents'][$vid_document_id]['attachments'][$documentEventPbAttachment->id]['attachment_id'] = $documentEventPbAttachment->attachment_id;
                                if ($documentEventPbAttachment->attachment) {
                                    $journal_inquiry_pb[$inquiry_pb_id]['documents'][$vid_document_id]['attachments'][$documentEventPbAttachment->id]['attachment_path'] = $documentEventPbAttachment->attachment->path;
                                    $journal_inquiry_pb[$inquiry_pb_id]['documents'][$vid_document_id]['attachments'][$documentEventPbAttachment->id]['attachment_blob'] = (object)array();
                                    $journal_inquiry_pb[$inquiry_pb_id]['documents'][$vid_document_id]['attachments'][$documentEventPbAttachment->id]['title'] = $documentEventPbAttachment->attachment->title;
                                    $journal_inquiry_pb[$inquiry_pb_id]['documents'][$vid_document_id]['attachments'][$documentEventPbAttachment->id]['attachment_type'] = $documentEventPbAttachment->attachment->attachment_type;
                                    $journal_inquiry_pb[$inquiry_pb_id]['documents'][$vid_document_id]['attachments'][$documentEventPbAttachment->id]['sketch'] = $documentEventPbAttachment->attachment->sketch;
                                    $journal_inquiry_pb[$inquiry_pb_id]['documents'][$vid_document_id]['attachments'][$documentEventPbAttachment->id]['attachment_status'] = "";
                                } else {
                                    $journal_inquiry_pb[$inquiry_pb_id]['documents'][$vid_document_id]['attachments'][$documentEventPbAttachment->id]['attachment_path'] = "";
                                    $journal_inquiry_pb[$inquiry_pb_id]['documents'][$vid_document_id]['attachments'][$documentEventPbAttachment->id]['attachment_blob'] = (object)array();
                                    $journal_inquiry_pb[$inquiry_pb_id]['documents'][$vid_document_id]['attachments'][$documentEventPbAttachment->id]['title'] = "";
                                    $journal_inquiry_pb[$inquiry_pb_id]['documents'][$vid_document_id]['attachments'][$documentEventPbAttachment->id]['attachment_type'] = "";
                                    $journal_inquiry_pb[$inquiry_pb_id]['documents'][$vid_document_id]['attachments'][$documentEventPbAttachment->id]['sketch'] = (object)array();
                                    $journal_inquiry_pb[$inquiry_pb_id]['documents'][$vid_document_id]['attachments'][$documentEventPbAttachment->id]['attachment_status'] = "";
                                }
                            }
                            if (!isset($journal_inquiry_pb[$inquiry_pb_id]['documents'][$vid_document_id]['attachments'])) {
                                $journal_inquiry_pb[$inquiry_pb_id]['documents'][$vid_document_id]['attachments'] = (object)array();
                            }
                        } else {
                            $journal_inquiry_pb[$inquiry_pb_id]['documents'] = (object)array();
                        }
                    }

                }
            }

        } catch (\Throwable $exception) {
            $errors[] = "GetJournalInquiry. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        if (!isset($journal_inquiry_pb)) {
            $journal_inquiry_pb = (object)array();
        }
        $warnings[] = "GetJournalInquiry. Закончил выполнять метод";
        $result = $journal_inquiry_pb;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }



    // GetListCasePb - получить список  обстоятельств
    // пример: http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Inquiry&method=GetListCasePb&subscribe=&data={}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function GetListCasePb($data_post = NULL)
    {
        $status = 1;                                                                                                 // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();                                                                                                // Массив ошибок

        try {
            $case_pb_list = CasePb::find()
                ->limit(20000)
                ->indexBy('id')
                ->asArray()
                ->all();

            if (!$case_pb_list) {
                $warnings[] = 'GetListCasePb. Справочник обстоятельств пуст';
                $result = (object)array();
            } else {
                $result = $case_pb_list;
            }
        } catch (\Throwable $exception) {
            $warnings[] = 'GetListCasePb. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetListOutcome - получить список Последствия несчастного случая
    // пример: http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Inquiry&method=GetListOutcome&subscribe=&data={}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function GetListOutcome($data_post = NULL)
    {
        $status = 1;                                                                                                 // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();                                                                                                // Массив ошибок

        try {
            $outcome_list = Outcome::find()
                ->limit(20000)
                ->indexBy('id')
                ->asArray()
                ->all();

            if (!$outcome_list) {
                $warnings[] = 'GetListOutcome. Справочник Последствия несчастного случая пуст';
                $result = (object)array();
            } else {
                $result = $outcome_list;
            }
        } catch (\Throwable $exception) {
            $warnings[] = 'GetListOutcome. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetListVidDocument - получить список видов документов ПБ в несчастных случаях
    // пример: http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Inquiry&method=GetListVidDocument&subscribe=&data={}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function GetListVidDocument($data_post = NULL)
    {
        $status = 1;                                                                                                 // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();                                                                                                // Массив ошибок

        try {
            $vid_document_list = VidDocument::find()
                ->limit(20000)
                ->indexBy('id')
                ->asArray()
                ->all();

            if (!$vid_document_list) {
                $warnings[] = 'GetListVidDocument. Справочник видов документов пуст';
                $result = (object)array();
            } else {
                $result = $vid_document_list;
            }
        } catch (\Throwable $exception) {
            $warnings[] = 'GetListVidDocument. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    // GetListKindIncident - получить список видов инцидентов
    // пример: http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Inquiry&method=GetListKindIncident&subscribe=&data={}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function GetListKindIncident($data_post = NULL)
    {
        $status = 1;                                                                                                 // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();                                                                                                // Массив ошибок

        try {
            $kind_incident_list = KindIncident::find()
                ->limit(20000)
                ->indexBy('id')
                ->asArray()
                ->all();

            if (!$kind_incident_list) {
                $warnings[] = 'GetListKindIncident. Справочник видов инцидентов';
                $result = (object)array();
            } else {
                $result = $kind_incident_list;
            }
        } catch (\Throwable $exception) {
            $warnings[] = 'GetListKindIncident. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetListKindMishap                - получить список несчастных случаев
    // пример: http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Inquiry&method=GetListKindMishap&subscribe=&data={}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function GetListKindMishap($data_post = NULL)
    {
        $status = 1;                                                                                                 // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();                                                                                                // Массив ошибок

        try {
            $kind_mishap_list = KindMishap::find()
                ->limit(20000)
                ->indexBy('id')
                ->asArray()
                ->all();

            if (!$kind_mishap_list) {
                $warnings[] = 'GetListKindMishap. Справочник видов несчастных случаев';
                $result = (object)array();
            } else {
                $result = $kind_mishap_list;
            }
        } catch (\Throwable $exception) {
            $warnings[] = 'GetListKindMishap. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetListMine                - получить список ОПО - шахтных полей
    // пример: http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Inquiry&method=GetListMine&subscribe=&data={}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function GetListMine($data_post = NULL)
    {
        $status = 1;                                                                                                 // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();                                                                                                // Массив ошибок

        try {
            $mine = Mine::find()
                ->limit(20000)
                ->indexBy('id')
                ->asArray()
                ->all();

            if (!$mine) {
                $warnings[] = 'GetListMine. Справочник ОПО/шахт';
                $result = (object)array();
            } else {
                $result = $mine;
            }
        } catch (\Throwable $exception) {
            $warnings[] = 'GetListMine. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetListKindCrash - получить список видов аварий
    // пример: http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Inquiry&method=GetListKindCrash&subscribe=&data={}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function GetListKindCrash($data_post = NULL)
    {
        $status = 1;                                                                                                 // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();                                                                                                // Массив ошибок

        try {
            $kind_crash_list = KindCrash::find()
                ->limit(20000)
                ->indexBy('id')
                ->asArray()
                ->all();

            if (!$kind_crash_list) {
                $warnings[] = 'GetListKindCrash. Справочник видов аварий';
                $result = (object)array();
            } else {
                $result = $kind_crash_list;
            }
        } catch (\Throwable $exception) {
            $warnings[] = 'GetListKindCrash. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // SaveJournalInquiry - сохранение расследования события/несчастного случая на производстве
    // структура входного массива:
    //            inquiry_pb_id:                                  - ключ несчастного случая в журнале регистрации
    //            date_time_create:                                     - Дата и время создания расследования
    //            case_pb_id:                                           - ключ обстоятельства
    //            description_event_pb:                                 - описание обстоятельства
    //            worker_id:                                            - ключ работника создавшего в БД данное сообщение о несчастном случае
    //            date_time_event:                                      - Дата и время события/несчастного случая
    //            company_department_id:                                - ключ департамента
    //            attachments:                                          - вложения расследования
    //            documents:                                            - список документов
    //                  [vid_document_id]                               - ключ вида документа
    //                        vid_document_id:                              - ключ вида документа
    //                        document_event_pb_id:                         - ключ документа
    //                        parent_document_id:                           - Вышестоящий(родительский) документ
    //                        document_title:                               - Название документа
    //                        date_start:                                   - Дата и время действительности
    //                        date_end:                                     - Дата и время окончания действительности
    //                        last_status_id:                               - Внешний идентификатор из списка статусов (актулальный, неактуальный) - последний статус
    //                        last_worker_id:                               - ключ последнего согласовавшего документ
    //                        jsondoc:                                      - сериализованная строка - хранит наполнение документа
    //                        statuses:                                     - история изменения статусов
    //                        attachments:                                  - вложения документов
    // выходная структура, аналогична входной
    // пример: http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Inquiry&method=SaveJournalInquiry&subscribe=&data={"company_department_id":802,"date_time":"2019-09-24"}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function SaveJournalInquiry($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $result = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $session = Yii::$app->session;
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $warnings[] = "SaveJournalInquiry. Начал выполнять метод";
            if ($data_post !== NULL && $data_post !== '')                                                                   // Проверяем передана ли входная JSON строка
            {
                $warnings[] = 'SaveJournalInquiry. Данные успешно переданы';
                $warnings[] = 'SaveJournalInquiry. Входной массив данных' . $data_post;
            } else {
                throw new Exception('SaveJournalInquiry. Не получена входная JSON строка');
            }
            $post_dec = json_decode($data_post);                                                                            // Декодируем входной массив данных
            $warnings[] = 'SaveJournalInquiry. Декодировал входные параметры';
            if (
            property_exists($post_dec, 'inquiry_pb')
            )                                                             // Проверяем наличие в нем нужных нам полей
            {
                $warnings[] = 'SaveJournalInquiry. Данные с фронта получены';
            } else {
                throw new Exception('SaveJournalInquiry. Переданы некорректные входные параметры');
            }

            $inquiry_pb = $post_dec->inquiry_pb;                                                                            // дата на которую выбираем данные
            $inquiry_pb_id = $inquiry_pb->inquiry_pb_id;

            /**
             * сохранение расследования
             */
            $new_inquiry_pb = InquiryPb::findOne(['id' => $inquiry_pb_id]);
            if (!$new_inquiry_pb) {
                $new_inquiry_pb = new InquiryPb();
            } else {
                $warnings[] = "SaveJournalInquiry. Расследование уже было ";
            }
            $new_inquiry_pb->date_time_create = $inquiry_pb->date_time_create;
            $new_inquiry_pb->case_pb_id = $inquiry_pb->case_pb_id;
            $new_inquiry_pb->description_event_pb = $inquiry_pb->description_event_pb;
            $new_inquiry_pb->worker_id = $inquiry_pb->worker_id;
            $new_inquiry_pb->date_time_event = $inquiry_pb->date_time_event;
            $new_inquiry_pb->company_department_id = $inquiry_pb->company_department_id;
            if ($new_inquiry_pb->save()) {
                $new_inquiry_pb->refresh();
                $new_inquiry_pb_id = $new_inquiry_pb->id;
                $inquiry_pb->inquiry_pb_id = $new_inquiry_pb_id;
                $warnings[] = 'SaveJournalInquiry. Данные успешно сохранены в модель InquiryPb';
            } else {
                $errors[] = $new_inquiry_pb->errors;
                throw new Exception('SaveJournalInquiry. Ошибка сохранения модели InquiryPb');
            }
            /**
             * сохранение вложений раследований
             */
            foreach ($inquiry_pb->attachments as $key => $inquiry_pb_attachment) {
                // проверяем статус вложения на удаление или на добавление
                if (isset($inquiry_pb_attachment->attachment_status) && $inquiry_pb_attachment->attachment_status === "del") {
//                    $delete_order = Yii::$app->db->createCommand()->delete('inquiry_attachment', 'id=' . $inquiry_pb_attachment->inquiry_pb_attachment_id)->execute();
                    $delete_order = InquiryAttachment::deleteAll(['id' => $inquiry_pb_attachment->inquiry_pb_attachment_id]);
                    unset($inquiry_pb->{"attachments"}->{$key});
                    $warnings[] = "SaveJournalInquiry. Удалил связку вложения $inquiry_pb_attachment->inquiry_pb_attachment_id. Количество " . $delete_order;
                } else {
                    /**
                     * сохраняем вложение в таблицу Attachment
                     **/
                    $attachment_id = $inquiry_pb_attachment->attachment_id;
                    $new_attachment = Attachment::findOne(['id' => $attachment_id]);
                    if (!$new_attachment) {
                        $new_attachment = new Attachment();
                        $path1 = Assistant::UploadFile($inquiry_pb_attachment->attachment_blob, $inquiry_pb_attachment->title, 'attachment', $inquiry_pb_attachment->attachment_type);
                        $new_attachment->path = $path1;
                        $new_attachment->date = BackendAssistant::GetDateFormatYMD();
                        $new_attachment->worker_id = $session['worker_id'];
                        $new_attachment->section_title = 'ОТ и ПБ/Учёт травматизма и происшествий';
                        $new_attachment->title = $inquiry_pb_attachment->title;
                        $new_attachment->attachment_type = $inquiry_pb_attachment->attachment_type;
                        $new_attachment->sketch = $inquiry_pb_attachment->sketch;
                        if ($new_attachment->save()) {
                            $new_attachment->refresh();
                            $new_attachment_id = $new_attachment->id;
                            $inquiry_pb_attachment->attachment_id = $new_attachment_id;
                            $inquiry_pb_attachment->attachment_blob = null;
                            $inquiry_pb_attachment->attachment_path = $path1;
                            $warnings[] = 'SaveJournalInquiry. Данные успешно сохранены в модель InquiryPb';
                        } else {
                            $errors[] = $new_attachment->errors;
                            throw new Exception('SaveJournalInquiry. Ошибка сохранения модели InquiryPb');
                        }
                    } else {
                        $warnings[] = "SaveJournalInquiry. вложение уже было ";
                        $new_attachment_id = $attachment_id;
                    }

                    /**
                     * сохраняем привязку вложения и расследования
                     **/
                    $inquiry_pb_attachment_id = $inquiry_pb_attachment->inquiry_pb_attachment_id;
                    $new_inquiry_pb_attachment = InquiryAttachment::findOne(['id' => $inquiry_pb_attachment_id]);
                    if (!$new_inquiry_pb_attachment) {
                        $new_inquiry_pb_attachment = new InquiryAttachment();
                    } else {
                        $warnings[] = "SaveJournalInquiry. Расследование уже было ";
                    }
                    $new_inquiry_pb_attachment->inquiry_id = $new_inquiry_pb_id;
                    $new_inquiry_pb_attachment->attachment_id = $new_attachment_id;
                    if ($new_inquiry_pb_attachment->save()) {
                        $new_inquiry_pb_attachment->refresh();
                        $new_inquiry_pb_attachment_id = (int)$new_inquiry_pb_attachment->id;
                        $inquiry_pb_attachment->inquiry_pb_attachment_id = $new_inquiry_pb_attachment_id;
                        /**
                         * следующий блок кода обновляет ключ привязки вложения и расследования в самом объекте
                         * для этого создаются новые обновленные ключи, а ключи, созданные на фронте, удаляются
                         * вся эта процедура нужна, чтобы работало удаление вложения после того, как сохранили
                         * расследование и открыли окно просмотра вложения, а уже там нажали удалить вложение.
                         **/
                        $inquiry_pb->{"attachments"}->{$new_inquiry_pb_attachment_id} = $inquiry_pb_attachment;//тут по новому ключу записываются все данные
                        $substr_key = mb_substr($key, 0, 21);// тут обрезаем ключ, если он меньше символов пофиг, будет меньше
                        if ($substr_key == 'inquiry_pb_attachment') { //смотрим если текст который мы обрезали = inquiry_pb_attachment
                            unset($inquiry_pb->{"attachments"}->{$key});
                        }

                        $warnings[] = 'SaveJournalInquiry. Данные успешно сохранены в модель InquiryAttachment';
                    } else {
                        $errors[] = $new_inquiry_pb_attachment->errors;
                        throw new Exception('SaveJournalInquiry. Ошибка сохранения модели InquiryAttachment');
                    }
                }
            }

            /**
             * сохранение документов и их вложений
             */
            if (isset($inquiry_pb->documents) && !empty($inquiry_pb->documents)) {
                foreach ($inquiry_pb->documents as $document) {
                    $document_event_pb_id = $document->document_event_pb_id;
                    $new_document_event_pb = DocumentEventPb::findOne(['id' => $document_event_pb_id]);
                    if (!$new_document_event_pb) {
                        $new_document_event_pb = new DocumentEventPb();
                    } else {
                        $warnings[] = "SaveJournalInquiry. Документ уже был ";
                    }
                    $new_document_event_pb->title = $document->document_title;
                    if (property_exists($document, "number_document")) {
                        $new_document_event_pb->number_document = (string)$document->number_document;
                    }
                    $new_document_event_pb->date_start = $document->date_start;
                    $new_document_event_pb->date_end = $document->date_end;
                    $new_document_event_pb->last_status_id = $document->last_status_id;
                    $new_document_event_pb->vid_document_id = $document->vid_document_id;
                    $new_document_event_pb->jsondoc = $document->jsondoc;
                    $new_document_event_pb->worker_id = $document->last_worker_id;
                    $new_document_event_pb->parent_document_id = $document->parent_document_id;
                    if ($new_document_event_pb->save()) {
                        $new_document_event_pb->refresh();
                        $new_document_event_pb_id = $new_document_event_pb->id;
                        $document->document_event_pb_id = $new_document_event_pb_id;
                        $warnings[] = 'SaveJournalInquiry. Данные успешно сохранены в модель DocumentEventPb';
                    } else {
                        $errors[] = $new_document_event_pb->errors;
                        throw new Exception('SaveJournalInquiry. Ошибка сохранения модели DocumentEventPb');
                    }
                    //                $warnings[]="SaveJournalInquiry ключ расследования";
                    //                $warnings[]=$new_inquiry_pb_id;
                    // блок сохранения привязки документа и расследования
                    $new_inquiry_document = InquiryDocument::findOne(['inquiry_id' => $new_inquiry_pb_id, 'document_event_pb_id' => $new_document_event_pb_id]);
                    if (!$new_inquiry_document) {
                        $new_inquiry_document = new InquiryDocument();
                    }
                    $new_inquiry_document->inquiry_id = $new_inquiry_pb_id;
                    $new_inquiry_document->document_event_pb_id = $new_document_event_pb_id;
                    if ($new_inquiry_document->save()) {
                        $new_inquiry_document->refresh();
                        $new_inquiry_document_id = $new_inquiry_document->id;
                        $warnings[] = 'SaveJournalInquiry. Данные успешно сохранены в модель InquiryDocument';
                    } else {
                        $errors[] = $new_inquiry_document->errors;
                        throw new Exception('SaveJournalInquiry. Ошибка сохранения модели InquiryDocument');
                    }


                    /**
                     * блок сохранения статуса документа
                     */
                    foreach ($document->statuses as $statusObj) {
                        //                    $warnings[] = "statusObj";
                        //                    $warnings[] = $statusObj;
                        $document_event_pb_status_id = $statusObj->document_event_pb_status_id;
                        $new_document_event_pb_status = DocumentEventPbStatus::findOne(['id' => $document_event_pb_status_id]);
                        if (!$new_document_event_pb_status) {
                            $new_document_event_pb_status = new DocumentEventPbStatus();
                        } else {
                            $warnings[] = "SaveJournalInquiry. Статус документа уже был ";
                        }
                        $new_document_event_pb_status->document_event_pb_id = $new_document_event_pb_id;
                        $new_document_event_pb_status->worker_id = $statusObj->worker_id;
                        $new_document_event_pb_status->status_id = $statusObj->status_id;
                        $new_document_event_pb_status->date_time_create = $statusObj->date_time_create;
                        if ($new_document_event_pb_status->save()) {
                            $new_document_event_pb_status->refresh();
                            $new_document_event_pb_status_id = $new_document_event_pb_status->id;
                            $statusObj->document_event_pb_status_id = $new_document_event_pb_status_id;
                            $statusObj->document_event_pb_id = $new_document_event_pb_id;
                            $warnings[] = 'SaveJournalInquiry. Данные успешно сохранены в модель DocumentEventPbStatus';
                        } else {
                            $errors[] = $new_document_event_pb_status->errors;
                            throw new Exception('SaveJournalInquiry. Ошибка сохранения модели DocumentEventPbStatus');
                        }
                    }

                    /**
                     * блок сохранения вложений документа
                     */
                    foreach ($document->attachments as $key_attach=>$docum_attachment) {
                        // проверяем статус вложения на удаление или на добавление
                        if (isset($docum_attachemnt->attachment_status) && $docum_attachment->attachment_status === "del") {
                            $delete_order = DocumentEventPbAttachment::deleteAll(['id' => $docum_attachment->document_event_pb_attachment_id]);
//                            $delete_order = Yii::$app->db->createCommand()->delete('document_event_pb_attachment', 'id=' . $docum_attachment->document_event_pb_attachment_id)->execute();
                            unset($document->{"attachments"}->{$key_attach});
                            $warnings[] = "SaveJournalInquiry. Удалил связку вложения $docum_attachment->document_event_pb_attachment_id. Количество " . $delete_order;
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
                                $new_docum_attachment->section_title = 'ОТ и ПБ/Учёт травматизма и происшествий';
                                $new_docum_attachment->title = $docum_attachment->title;
                                $new_docum_attachment->attachment_type = $docum_attachment->attachment_type;
                                $new_docum_attachment->sketch = $docum_attachment->sketch;
                                if ($new_docum_attachment->save()) {
                                    $new_docum_attachment->refresh();
                                    $new_docum_attachment_id = $new_docum_attachment->id;
                                    $docum_attachment->attachment_id = $new_docum_attachment_id;
                                    $docum_attachment->attachment_path = $path;
                                    $docum_attachment->attachment_blob = null;
                                    $warnings[] = 'SaveJournalInquiry. Данные успешно сохранены ВЛОЖЕНИЯ ДОКУМЕНТА в модель Attachment';
                                } else {
                                    $errors[] = $new_docum_attachment->errors;
                                    throw new Exception('SaveJournalInquiry. Ошибка сохранения ВЛОЖЕНИЯ ДОКУМЕНТА модели Attachment');
                                }
                            } else {
                                $warnings[] = "SaveJournalInquiry. вложение ДОКУМЕНТА уже было ";
                                $new_docum_attachment_id = $docum_attachment_id;
                            }

                            /**
                             * сохраняем привязку вложения и расследования
                             **/
                            $document_event_pb_attachment_id = $docum_attachment->document_event_pb_attachment_id;
                            $new_document_event_pb_attachment = DocumentEventPbAttachment::findOne(['id' => $document_event_pb_attachment_id]);
                            if (!$new_document_event_pb_attachment) {
                                $new_document_event_pb_attachment = new DocumentEventPbAttachment();
                            } else {
                                $warnings[] = "SaveJournalInquiry. Вложение документа уже было ";
                            }
                            $new_document_event_pb_attachment->document_event_pb_id = $new_document_event_pb_id;
                            $new_document_event_pb_attachment->attachment_id = $new_docum_attachment_id;
                            if ($new_document_event_pb_attachment->save()) {
                                $new_document_event_pb_attachment->refresh();
                                $document_event_pb_attachment_id = $new_document_event_pb_attachment->id;
                                $docum_attachment->document_event_pb_attachment_id = $document_event_pb_attachment_id;
                                $warnings[] = 'SaveJournalInquiry. Данные успешно сохранены в модель DocumentEventPbAttachment';
                            } else {
                                $errors[] = $new_document_event_pb_attachment->errors;
                                throw new Exception('SaveJournalInquiry. Ошибка сохранения модели DocumentEventPbAttachment');
                            }
                        }
                    }
                }
            }
            $transaction->commit();
        } catch (\Throwable $exception) {
            $transaction->rollBack();
            $errors[] = "SaveJournalInquiry. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        if (!isset($inquiry_pb)) {
            $inquiry_pb = (object)array();
        }
        $warnings[] = "SaveJournalInquiry. Закончил выполнять метод";
        $result = $inquiry_pb;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetInquiryList - получение журнала расследований
    // пример: http://127.0.0.1/read-manager-amicum?controller=industrial_safety\Inquiry&method=GetInquiryList&subscribe=&data={}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function GetInquiryList($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $result = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $inquiry_pb_array = array();                                                                                              // Массив ошибок

        try {
            $warnings[] = "GetInquiryList. Начал выполнять метод";

            $inquiry_pbs = InquiryPb::Find()
                ->joinWith('inquiryDocuments.documentEventPb')
                ->orderBy(['description_event_pb' => SORT_DESC])
                ->asArray()
                ->all();
            foreach ($inquiry_pbs as $inquiry_pb) {
                $inquiry_pb_item['inquiry_pb_id'] = $inquiry_pb['id'];
                $inquiry_pb_item['case_pb_id'] = $inquiry_pb['case_pb_id'];
                $inquiry_pb_item['description_event_pb'] = $inquiry_pb['description_event_pb'];
                $inquiry_pb_item['date_time_create'] = $inquiry_pb['date_time_create'];
                $inquiry_pb_item['worker_id'] = $inquiry_pb['worker_id'];
                $inquiry_pb_item['date_time_event'] = $inquiry_pb['date_time_event'];
                $inquiry_pb_item['company_department_id'] = $inquiry_pb['company_department_id'];
                foreach ($inquiry_pb['inquiryDocuments'] as $inquiryDocument) {
                    if ($inquiryDocument['documentEventPb']['vid_document_id'] == 5) {
                        $json_doc = json_decode($inquiryDocument['documentEventPb']['jsondoc']);
                        $inquiry_pb_item['act_number'] = $json_doc->item5;
                    }
                }
                $inquiry_pb_array[] = $inquiry_pb_item;
            }
        } catch (\Throwable $exception) {
            $errors[] = "GetJournalInquiry. Исключение";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $warnings[] = "GetJournalInquiry. Закончил выполнять метод";

        $result_main = array('Items' => $inquiry_pb_array, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }
}
