<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\handbooks;

use backend\controllers\Assistant as BackendAssistant;
use backend\controllers\const_amicum\StatusEnumController;
use backend\controllers\const_amicum\VidDocumentEnumController;
use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\HandbookCachedController;
use frontend\controllers\industrial_safety\CheckingController;
use frontend\controllers\positioningsystem\ObjectFunctions;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Attachment;
use frontend\models\CheckingPlace;
use frontend\models\CheckingWorkerType;
use frontend\models\CorrectMeasures;
use frontend\models\Document;
use frontend\models\DocumentEventPb;
use frontend\models\IndustrialSafetyObject;
use frontend\models\IndustrialSafetyObjectType;
use frontend\models\Injunction;
use frontend\models\InjunctionAttachment;
use frontend\models\InjunctionStatus;
use frontend\models\InjunctionViolation;
use frontend\models\InjunctionViolationStatus;
use frontend\models\KindDocument;
use frontend\models\KindViolation;
use frontend\models\Operation;
use frontend\models\OperationGroup;
use frontend\models\OrderItem;
use frontend\models\OrderOperation;
use frontend\models\ParagraphPb;
use frontend\models\Place;
use frontend\models\PlaceCompanyDepartment;
use frontend\models\ResearchIndex;
use frontend\models\ResearchType;
use frontend\models\Status;
use frontend\models\StopPb;
use frontend\models\VidDocument;
use frontend\models\Violation;
use frontend\models\ViolationType;
use frontend\models\Violator;
use frontend\models\Worker;
use Throwable;
use Yii;
use yii\db\Query;
use yii\web\Controller;

/**
 * Class InjunctionController
 * Базоывй контроллер для работы с предписаниями
 *
 * @package frontend\controllers\handbooks
 */
class InjunctionController extends Controller
{
    const OPERATION_TYPE_DOP_WORK = 10;                                                                                 // Тип операций - Вспомогательные работы
    const STATUS_ACTUAL = 1;
    const MAIN_STOP_PB_TYPE = 1;                                                                                        // Приостановка работ Административная
    const DURATION_TYPE = 1;
    const STATUS_NEW = 57;
    const WORKER_TYPE_VIOLATOR = 4;

    // GetInjunctionStatus          - Метод получения статусов предписаний из справочника статусов
    // SaveStatusInjunctionFromOrder- метод сохранения статусов предписаний из наряда
    // GetRtnStatus                 - Метод получения статусов РТН из справочника статусов
    // AddInjunction                - добавление предписания и свяызанных с ним данных
    // GetPlaceByTitle              - Получить идентификатор места по наименованию, если такого наименования не существует создаст место
    // NewStatusForInjunction       - Cмена статуса на основе фактической и плановой даты
    // ViolationType                - Добавдение нового типа нарушения
    // GetDocumentByTitle           - Получить документ по наименованию, если такого документа не существет создат такой документ
    // GetParagraphPbByText         - Получение Пункта документа по тексту пункта, если такого не существует, создаёт новый пункт документа
    // GetOperationByTitle          - Получить идентификатор операции по наименованию, если такой операции не существует то создаёт
    // GetStatusForNewInjunction    - Установка статуса предписания при его создании по плановой и фактической дате
    // AddViolationDisconformity    - Сохранение нарушения несоответствия
    // GetKindDocument              - Получение справочника видов документов
    // SaveKindDocument             - Сохранение нового вида документа
    // DeleteKindDocument           - Удаление типа вида документа
    // GetKindViolation             - Получение справочника видов нарушений
    // SaveKindViolation            - Сохранение нового вида нарушения
    // DeleteKindViolation          - Удаление вида нарушения

    // GetParagraphPb               - Получение справочника пунктов нормативных документов
    // SaveParagraphPb              - Сохранение нового пункта нормативного документа
    // DeleteParagraphPb            - Удаление пункта нормативного документа

    // GetIndustrialSafetyObject()      - Получение справочника объектов промышленной безопасности
    // SaveIndustrialSafetyObject()     - Сохранение справочника объектов промышленной безопасности
    // DeleteIndustrialSafetyObject()   - Удаление справочника объектов промышленной безопасности

    // GetIndustrialSafetyObjectType()      - Получение справочника типов объектов промышленной безопасности
    // SaveIndustrialSafetyObjectType()     - Сохранение справочника типов объектов промышленной безопасности
    // DeleteIndustrialSafetyObjectType()   - Удаление справочника типов объектов промышленной безопасности

    // GetResearchIndex()      - Получение справочника параметров исследований
    // SaveResearchIndex()     - Сохранение справочника параметров исследований
    // DeleteResearchIndex()   - Удаление справочника параметров исследований

    // GetResearchType()        - Получение справочника типов исследований
    // SaveResearchType()       - Сохранение справочника типов исследований
    // DeleteResearchType()     - Удаление справочника типов исследований

    // GetVidDocument()         - Получение справочника видов документов ПБ
    // SaveVidDocument()        - Сохранение справочника видов документов ПБ
    // DeleteVidDocument()      - Удаление справочника видов документов ПБ

    // GetDocument()            - Получение справочника названий документов

    // GetViolationType()      - Получение справочника направлений нарушений
    // SaveViolationType()     - Сохранение справочника направлений нарушений
    // DeleteViolationType()   - Удаление справочника направлений нарушений

    const VIOLATION_DISMATCH = 4;

    /**
     * Метод AddInjunction() - добавление предписания и свяызанных с ним данных
     *
     *
     * УРЕЗАННАЯ СТРУКТУРА ИЗ CHECKINGCONTROLLER: checking                                                              -проверка
     *              [checking_id]                                                                                       -идентификатор проверки
     *                      checking_id                                                                                 -идентификатор проверки
     *                      checking_title                                                                              -наименование проверки
     *                      date_time_start                                                                             -дата начала проверки
     *                      date_time_end                                                                               -дата окончания проверки
     *                      crew_member                                                                                 -все люди которые участвовали в проверки
     *                            [checking_worker_type_id] (crew_member_id)                                            -идентификатор списка работинков участвующих в роверке
     *                                      crew_member_id                                                              -идентификатор списка работинков участвующих в роверке
     *                                      worker_id                                                                   -идентификатор работника
     *                                      worker_type_id                                                              -тип работника
     *                      injunction                                                                                  -предписание
     *                              [injunction_id]                                                                     -идентификатор предписания
     *                                          attachments                                                             -вложения
     *                                                  [attachment_id]                                                 -идентификатор вложения
     *                                                              attachment_id                                       -идентификатор вложения
     *                                         injunction_id                                                            -идентификатор предписания
     *                                         place_id                                                                 -идентификатор места
     *                                         worker_id (аудитор, первый из всех аудиторов)                            -идентификатор аудитора первого из всех аудиторов
     *                                         kind_document_id                                                         -документ
     *                                         rtn_statistis_status                                                     -статус РТН
     *                                         injunction_description                                                   -описание предписания
     *                              [injunction_status]                                                                 -статус предписания
     *                                      [injunction_status_id]                                                      -идентификатор статуса предписания
     *                                                  injunction_status_id                                            -идентификатор статуса предписания
     *                                                  worker_id                                                       -идентификатор работника (не знаю зачем он тут нужен)
     *                                                  date_time                                                       -дата и время смены статуса
     *                                                  status_id                                                       -статус
     *                              [injunction_violation]                                                               -предписание нарушения
     *                                          [injunction_violation_id]                                               -идентификатор предписание нарушения
     *                                                             injunction_violation_id                              -идентификатор предписания нарушения
     *                                                             probability                                          -вероятность
     *                                                             gravity                                              -тяжесть
     *                                                             correct_peroiod                                      -срок устранения нарушения
     *                                                             violation_id                                         -идентификатор нарушения
     *                                                             paragraph_pb                                         -пункт Персональной Безопасности
     *                                                             document_id                                          -ид нормативного документа
     *                                                             violation_type_id                                    -ид типа нарушения
     *                                                             injunction_img                                       -изображения нарушения
     *                                                                      [injunction_img]                            -идентификатор изображения нарушения
     *                                                                                  injunction_img_id               -идентификатор изображения нарушения
     *                                                                                  injunction_img_path             -путь до изображения
     *                                                             paragraph_injunction_description                     -описание пункта Персональной Безопасности
     *                                                             correct_measures                                     -корректирующие мероприятия
     *                                                                      [correct_measures_id]                       -идентификатор корректирующего мероприятия
     *                                                                                  correct_measures_id             -идентификатор корректирующего мероприятия
     *                                                                                  operation_id                    -идентификатор опреации
     *                                                                                  operation_description           -описание операции
     *                                                                                  correct_measures_description    -описание корректирующего мероприятия
     *                                                                                  operation_unit                  -единица измерения операции
     *                                                                                  operation_value                 -объём операции
     *                                                                                  worker_id                       -идентификатор работника ответственного за операцию
     *                                                                                  date_plan                       -планируемая дата выполнения корректирющего мероприятия
     *                                                             stop_pb                                              -Простои ПБ
     *                                                                  [stop_pb_id]                                    -идентификатор простоя ПБ
     *                                                                          stop_pb_id                              -идентификатор ПБ
     *                                                                          date_time_start                         -дата и время начала простоя
     *                                                                          date_time_end                           -дата и время окончания простоя
     *                                                                          equipment_id                            -идентификатор оборудования
     *                                                                          place_id                                -идентификатор места
     *                                                                          kind_stop_pb                            -вид простоя
     *                                                                          kind_duration                           -длительность простоя
     *
     * @param $post_array - данные проверки
     * @return array            - идентификатор нового предписания
     * @package frontend\controllers\handbooks
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 21.06.2019 12:46
     * @since ver
     */
    public static function AddInjunction($post_array)
    {
        $log = new LogAmicumFront("AddInjunction");

        // Сохранение нарушений
        // Сохранение корректирующих мероприятий
        // Сохранение ответственных корректир, меропр
        // Сохранение приостановки работ, необязательное
        // Сохранение вложений для предписания inj_attachment
        // Сохранение фотографий нарушения
        $new_checking = array();                                                                                   // Промежуточный результирующий массив
        $new_stop_pbs = array();
        $new_stop_pbs_equipment = array();
        $new_correct_measures = array();
        $new_injunction_images = array();
        $checking_place_to_delete = array();
        $checking_place_to_insert = array();
        $arr_inj = array();
        $add_violators = array();
        $places = array();
        $found_company_department = null;
        $found_violator_from_checking_worker_type = array();
        $new_checking_id = null;
        $injunction_id = null;
        $old_checking_id = null;
        $date_time_end = null;
        $correct_measures_value = 0;
        $checking_id = null;
        $new_paragraph_pb_id = null;

        try {
            $log->addLog("Начал выполнять метод");

            $date_time_now = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));
            $session = Yii::$app->session;
            $post_array['status_id'] = StatusEnumController::ACTUAL;

            $checking_id = $post_array['checking_id'];
            $injunctions_new = $post_array['injunction'];


            $log->addLog("Проверяю наличие предписаний в нарядах, в т.ч. атомарных");

            $injunction_id_for_delete = [];
            $injunction_violation_id_for_delete = [];
            $correct_measures_id_for_delete = [];

            $injunction_violations_last = (new Query())
                ->select('injunction.id as injunction_id,injunction_violation.id as injunction_violation_id,correct_measures.id as correct_measures_id')
                ->from('injunction_violation')
                ->innerJoin('injunction', 'injunction.id=injunction_violation.injunction_id')
                ->leftJoin('correct_measures', 'injunction_violation.id=correct_measures.injunction_violation_id')
                ->where(['checking_id' => $checking_id])
                ->all();


            foreach ($injunction_violations_last as $injunction_violation_last) {
                $injunction_id_for_delete[$injunction_violation_last['injunction_id']] = $injunction_violation_last['injunction_id'];
                $injunction_violation_id_for_delete[$injunction_violation_last['injunction_violation_id']] = $injunction_violation_last['injunction_violation_id'];
                $correct_measures_id_for_delete[$injunction_violation_last['correct_measures_id']] = $injunction_violation_last['correct_measures_id'];
            }

            if (!empty($injunction_violation_id_for_delete)) {
                InjunctionStatus::deleteAll(['injunction_id' => $injunction_id_for_delete]);
            }
            $log->addLog("Удалил InjunctionStatus");

            if (!empty($injunction_violation_id_for_delete)) {
                InjunctionViolationStatus::deleteAll(['id' => $injunction_violation_id_for_delete]);
            }
            $log->addLog("Удалил InjunctionViolationStatus");

            $find_order_operations1 = OrderOperation::find()
                ->select('injunction_id, injunction_violation_id, correct_measures_id')
//                ->where(['injunction_id' => $injunction_id_for_delete])
//                ->orWhere(['injunction_violation_id' => $injunction_violation_id_for_delete])
                ->where(['correct_measures_id' => $correct_measures_id_for_delete])
                ->asArray()
                ->all();

            $find_order_operations2 = OrderItem::find()
                ->select('injunction_id, injunction_violation_id, correct_measures_id')
//                ->where(['injunction_id' => $injunction_id_for_delete])
//                ->orWhere(['injunction_violation_id' => $injunction_violation_id_for_delete])
                ->where(['correct_measures_id' => $correct_measures_id_for_delete])
                ->asArray()
                ->all();


            $find_order_operations = array_merge($find_order_operations1, $find_order_operations2);

            foreach ($find_order_operations as $find_order_operation) {
                unset($injunction_violation_id_for_delete[$find_order_operation['injunction_violation_id']]);
                unset($injunction_id_for_delete[$find_order_operation['injunction_id']]);
                unset($correct_measures_id_for_delete[$find_order_operation['correct_measures_id']]);
            }

            if (!empty($correct_measures_id_for_delete)) {
                CorrectMeasures::deleteAll(['id' => $correct_measures_id_for_delete]);
                $log->addLog("Удалил CorrectMeasures");
            }

            if (!empty($injunction_violation_id_for_delete)) {
                InjunctionViolation::deleteAll(['id' => $injunction_violation_id_for_delete]);
//                $injunction_violation_id_for_delete_str = implode(',', $injunction_violation_id_for_delete);
//                $sql_parameters = "DELETE FROM injunction_violation  where id in ($injunction_violation_id_for_delete_str)";
//                Yii::$app->db->createCommand($sql_parameters)->execute();
                $log->addLog("Удалил InjunctionViolation");
            }

            if (!empty($injunction_id_for_delete)) {
                Injunction::deleteAll(['id' => $injunction_id_for_delete]);
//                $injunction_id_for_delete_str = implode(',', $injunction_id_for_delete);
//                $sql_parameters = "DELETE FROM injunction  where id in ($injunction_id_for_delete_str)";
//                Yii::$app->db->createCommand($sql_parameters)->execute();
                $log->addLog("Удалил injunction");
            }

            CheckingWorkerType::deleteAll(['checking_id' => $checking_id]);

            foreach ($post_array['crew_member'] as $crew_member) {
                $add_worker_type[] = [(int)$crew_member['worker_id'], $crew_member['worker_type_id'], $checking_id];
            }
//
            if (isset($add_worker_type)) {
                Yii::$app->db->createCommand()->batchInsert('checking_worker_type', ['worker_id', 'worker_type_id', 'checking_id'], $add_worker_type)->execute();
            }

            $found_violator_from_checking_worker_type = CheckingWorkerType::find()
                ->where(['id' => $checking_id])
                ->andWhere(['worker_type_id' => self::WORKER_TYPE_VIOLATOR])
                ->all();


            $log->addLog("Начинаю сохранять нарушения");
            foreach ($injunctions_new as $injunction) {
                /******************** ДОБАВЛЕНИЕ НОВОГО ПРЕДПИСАНИЯ ********************/

                $new_injunction = Injunction::find()
                    ->where(['id' => $injunction['injunction_id']])
                    ->orWhere(['place_id' => $injunction['place_id'], 'worker_id' => $injunction['worker_id'], "checking_id" => $checking_id])
                    ->one();
                if (!$new_injunction) {
                    $new_injunction = new Injunction();                                                                     // Создаем экземпляр новой проверки
                }

                $new_injunction->rtn_statistic_status_id = $injunction['rtn_statistic_status'];
                $new_injunction->checking_id = $checking_id;
                if (isset($injunction['status_id']) && $injunction['status_id']) {
                    $injunction_status_id = $injunction['status_id'];
                } else {
                    $injunction_status_id = self::STATUS_NEW;
                }
                $new_injunction->status_id = $injunction_status_id;
                $company_department_id = PlaceCompanyDepartment::find()
                    ->select(['company_department_id'])
                    ->where(['place_id' => $injunction['place_id']])
                    ->scalar();
                if ($company_department_id) {
                    $new_injunction->company_department_id = (int)$company_department_id;
                } else {
                    $found_company_department = Worker::find()
                        ->select(['company_department_id'])
                        ->where(['id' => $injunction['worker_id']])
                        ->scalar();
                    $new_injunction->company_department_id = (int)$found_company_department;
                }
                $injunction_description = "";
                if (isset($injunction['injunction_description'])) {
                    $injunction_description = $injunction['injunction_description'];
                } else if (isset($injunction['description'])) {
                    $injunction_description = $injunction['description'];
                }

                $new_injunction->place_id = $injunction['place_id'];
                $new_injunction->worker_id = $injunction['worker_id'];
                $new_injunction->kind_document_id = $injunction['kind_document_id'];
                $new_injunction->description = $injunction_description;
                $new_injunction->observation_number = $injunction['observation_number'];

                if (!$new_injunction->save()) {
                    $log->addData($new_injunction->errors, '$new_injunction->errors', __LINE__);
                    throw new Exception('Ошибка сохранения модели Injunction');       // Проверка не удалась кидаем исключение
                }
                $new_injunction->refresh();
                $injunction_id = $new_injunction->id;
                $places[] = $injunction['place_id'];


                /******************** СОХРАНЕНИЕ ВЛОЖЕНИЯ ********************/
                foreach ($injunction['attachments'] as $attachment) {
                    if (!empty($attachment['attachment_src'])) {
                        if ($attachment['attachment_flag'] == 'new') {
                            $file_path_attachment = Assistant::UploadFile($attachment['attachment_src'], $attachment['attachment_name'], 'attachment', $attachment['attachment_type']);
                            $add_attachment = new Attachment();
                            $add_attachment->path = $file_path_attachment;
                            $add_attachment->date = BackendAssistant::GetDateFormatYMD();
                            $add_attachment->worker_id = $session['worker_id'];
                            $add_attachment->section_title = 'Книга предписаний';
                            if (!$add_attachment->save()) {
                                $log->addData($add_attachment->errors, '$add_attachment->errors', __LINE__);
                                throw new Exception('Во время добавления вложения произошла ошибка.');
                            }
                            $add_attachment->refresh();
                            $attachment_id = $add_attachment->id;
                        } elseif ($attachment['attachment_flag'] == null) {
                            $attachment_id = $attachment['attachment_id'];
                        }
                        if (!empty($attachment_id)) {
                            $add_injunction_attachment = new InjunctionAttachment();
                            $add_injunction_attachment->injunction_id = $injunction_id;
                            $add_injunction_attachment->attachment_id = (int)$attachment_id;
                            if (!$add_injunction_attachment->save()) {
                                $log->addData($add_injunction_attachment->errors, '$add_injunction_attachment->errors', __LINE__);
                                throw new Exception('Во время добавления связки вложения и нарушения произошла ошибка.');
                            }
                        }
                    }
                }
                /******************** ДОБАВЛЕНИЕ СТАТУСА ДЛЯ ПРЕДПИСАНИЯ ********************/
                if (isset($injunction['injunction_status_all']) && !empty($injunction['injunction_status_all'])) {
                    foreach ($injunction['injunction_status_all'] as $inj_status_item) {
                        $date_status = date('Y-m-d H:i:s', strtotime($inj_status_item['date_time']));
                        $inj_statuses[$date_status . "-" . $injunction_id . "-" . $inj_status_item['worker_id']] = [
                            $injunction_id,
                            $inj_status_item['worker_id'],
                            $inj_status_item['status_id'],
                            $date_status
                        ];
                    }
                }
                $status_worker_id = Yii::$app->session['worker_id'];
                $inj_statuses[$date_time_now . "-" . $injunction_id . "-" . $status_worker_id] = [
                    $injunction_id,
                    $status_worker_id,
                    self::STATUS_NEW,
                    $date_time_now
                ];

                $log->addLog("Сохранил нарушение");

                foreach ($injunction['injunction_violation'] as $injunction_violation)                                             // Добавляем нарушения
                {

                    $injunction_violation['correct_period'] = NULL;
                    /******************** ДОБАВЛЕНИЕ НАРУШЕНИЯ ********************/
                    $new_violation = new Violation();                                                                   // Создаем экземпляр новой проверки
                    $violation_add['title'] = $injunction_violation['injunction_description'];
                    $violation_add['violation_type_id'] = (int)$injunction_violation['violation_type_id'];
                    $new_violation->attributes = $violation_add;
                    if (!$new_violation->validate() || !$new_violation->save())                                         // Проверяем данные по правилам описанным в модели и выполняем сохранение в случаи успешной валидации
                    {
                        $log->addData($new_violation->errors, '$new_violation->errors', __LINE__);
                        throw new Exception('Во время добавления опасного действия возникло исключение.');       // Проверка не удалась кидаем исключение
                    }
                    $new_violation->refresh();
                    $new_violation_id = $new_violation->id;

                    /******************** ДОБАВЛЕНИЕ ПУНКТА ПРОИЗВОДСТВЕННОЙ БЕЗОПАСНОСТИ ********************/
                    $new_paragraph_pb_id = null;
                    if (!empty($injunction_violation['paragraph_injunction_description'])) {
                        if (!empty($injunction_violation['paragraph_injunction_description'])) {
                            $trim_text = trim($injunction_violation['paragraph_injunction_description']);
                            if (!empty($trim_text)) {
                                $paragraph_pb['text'] = $injunction_violation['paragraph_injunction_description'];
                                $paragraph_pb['document_id'] = $injunction_violation['document_id'];
                                $new_paragraph_pb = new ParagraphPb();
                                $new_paragraph_pb->attributes = $paragraph_pb;
                                if (!$new_paragraph_pb->validate() || !$new_paragraph_pb->save())                                   // Проверяем данные по правилам описанным в модели и выполняем сохранение в случаи успешной валидации
                                {
                                    $log->addData($new_paragraph_pb->errors, '$new_paragraph_pb->errors', __LINE__);
                                    throw new Exception('Во время добавления нарушения возникло исключение.'); // Проверка не удалась кидаем исключение
                                }
                                $new_paragraph_pb->refresh();
                                $new_paragraph_pb_id = $new_paragraph_pb->id;
                            }
                        }

                    }


                    /******************** ДОБАВЛЕНИЕ НАРУШЕНИЙ ПРЕДПИСАНИЯ ********************/
                    $new_injunction_violation = InjunctionViolation::findOne(['id' => $injunction_violation['injunction_violation_id']]);

                    if (!$new_injunction_violation) {
                        $new_injunction_violation = new InjunctionViolation();
                    }

                    $new_injunction_violation->violation_id = $new_violation_id;
                    $new_injunction_violation->injunction_id = $injunction_id;
                    $new_injunction_violation->place_id = (int)$injunction['place_id'];
                    $new_injunction_violation->paragraph_pb_id = $new_paragraph_pb_id;
                    $new_injunction_violation->probability = $injunction_violation['probability'];
                    $new_injunction_violation->gravity = $injunction_violation['dangerous'];
                    $new_injunction_violation->correct_period = $injunction_violation['correct_period'];
                    $new_injunction_violation->reason_danger_motion_id = $injunction_violation['reason_danger_motion_id'];
                    $new_injunction_violation->document_id = $injunction_violation['document_id'];

                    if (!$new_injunction_violation->save()) {
                        $log->addData($new_injunction_violation->errors, '$new_injunction_violation->errors', __LINE__);
                        throw new Exception('AddInjunction. Во время добавления нарушения возникло исключение.'); // Проверка не удалась кидаем исключение
                    }
                    $new_injunction_violation->refresh();
                    $new_injunction_violation_id = $new_injunction_violation->id;

                    /******************** ДОБАВЛЕНИЕ СТАТУСА ДЛЯ НАРУШЕНИЯ ПРЕДПИСАНИЯ ********************/
                    if (isset($injunction_violation['injunction_violation_statuses']) && !empty($injunction_violation['injunction_violation_statuses'])) {
                        foreach ($injunction_violation['injunction_violation_statuses'] as $injunction_violation_status_item) {
                            if(isset($injunction_violation_status_item['date_time'])) {
                                $date_status = date('Y-m-d H:i:s', strtotime($injunction_violation_status_item['date_time']));
                                $worker_id=$injunction_violation_status_item['worker_id'];
                                $status_id=$injunction_violation_status_item['status_id'];
                            } else {
                                $date_status = BackendAssistant::GetDateFormatYMD();
                                $worker_id = $session['worker_id'];
                                $status_id = self::STATUS_NEW;
                            }
                            $inj_viol_statuses[$date_status . "-" . $new_injunction_violation_id . "-" . $worker_id] = [
                                $new_injunction_violation_id,
                                $worker_id,
                                $status_id,
                                $date_status
                            ];
                        }
                    }
                    $status_worker_id = Yii::$app->session['worker_id'];
                    $inj_viol_statuses[$date_time_now . "-" . $new_injunction_violation_id . "-" . $status_worker_id] = [
                        $new_injunction_violation_id,
                        $status_worker_id,
                        self::STATUS_NEW,
                        $date_time_now
                    ];

                    foreach ($post_array['crew_member'] as $crew_member) {
                        if ($crew_member['worker_id'] == $injunction['worker_id']) {
                            $worker_id = (int)$crew_member['worker_id'];
                            if ($crew_member['worker_type_id'] == CheckingController::WORKER_TYPE_NARUSHITEL) {
                                $add_violators[] = [$new_injunction_violation_id, $worker_id];
                            }
                        }
                    }

                    foreach ($found_violator_from_checking_worker_type as $violtors) {
                        $injunction_violation['violators'][$violtors->worker_id]['worker_id'] = $violtors->worker_id;
                        $injunction_violation['violators'][$violtors->worker_id]['injunction_violation_id'] = $new_injunction_violation_id;
                    }

                    /******************** ДОБАВЛЕНИЕ НАРУШИТЕЛЕЙ ********************/
                    foreach ($injunction_violation['violators'] as $violator) {
                        if (!empty($violator['worker_id'])) {
                            $add_new_violator = new Violator();
                            $add_new_violator->injunction_violation_id = $new_injunction_violation_id;
                            $add_new_violator->worker_id = $violator['worker_id'];
                            if (!$add_new_violator->save()) {
                                throw new Exception('Ошибка при добавлении нарушителя');
                            }
                        }
                    }
                    /******************** ФОРМИРОВАНИЕ МАССИВА НА ДОБАВЛЕНИЕ КОРРЕКТИРУЮЩИХ МЕРОПРИЯТИЙ ********************/

                    if (isset($injunction_violation['correct_measures']) && !empty($injunction_violation['correct_measures'])) {
                        foreach ($injunction_violation['correct_measures'] as $correct_measures)                                           // Для каждого нарушения формируется приостановки работ и корректирующие мероприятия
                        {
                                $correct_measures_value = !$correct_measures['operation_value'] ? 0 : $correct_measures['operation_value'];
                                $date_plan = date('Y-m-d H:i:s', strtotime($correct_measures['date_plan']));
                                if (isset($correct_measures['correct_measures_description'])) {
                                    $correct_measures_description = $correct_measures['correct_measures_description'];
                                } else {
                                    $correct_measures_description = null;
                                }
                                $new_correct_measures[] = [$new_injunction_violation_id, $correct_measures['operation_id'], $date_plan, self::STATUS_NEW, $correct_measures_value, $correct_measures_description];           // Массив корректирующих мероприятий
                        }
                    }

                    /******************** ФОРМИРОВАНИЕ МАССИВА НА ДОБАВЛЕНИЕ ПРОСТОЕВ ********************/
                    if (isset($injunction_violation['stop_pb']) && !empty($injunction_violation['stop_pb'])) {
                        foreach ($injunction_violation['stop_pb'] as $stop)                                                                // Перебор массива приостановки работ
                        {
                            if ($stop['active']) {
                                $kind_duration_id = ($stop['kind_duration']) ? 1 : 2;                                           // Если стоит галочка до устранения передаем kind_duration_id - id до устранения
                                $date_time_start = date('Y-m-d H:i:s', strtotime($stop['date_time_start']));
                                $date_time_end = null;
                                if ($stop['until_complete_flag'] == false and $stop['date_time_end'] !== null) {
                                    $date_time_end = date('Y-m-d H:i:s', strtotime($stop['date_time_end']));
                                }

                                $add_stop_pb = StopPb::findOne(['id' => $stop['stop_pb_id']]);
                                if (!$add_stop_pb) {
                                    $add_stop_pb = new StopPb();
                                }
                                $add_stop_pb->injunction_violation_id = $new_injunction_violation_id;
                                $add_stop_pb->kind_stop_pb_id = self::MAIN_STOP_PB_TYPE;
                                $add_stop_pb->kind_duration_id = $kind_duration_id;
                                $add_stop_pb->place_id = $stop['place_id'];
                                $add_stop_pb->date_time_start = $date_time_start;
                                $add_stop_pb->date_time_end = $date_time_end;

                                if (!$add_stop_pb->save()) {
                                    throw new Exception('Ошибка при сохранении простоя');
                                }
                                $add_stop_pb->refresh();
                                $new_stop_pb_id = $add_stop_pb->id;

                                foreach ($stop['equipment'] as $equipment) {
                                    $new_stop_pbs_equipment[] = [$new_stop_pb_id, $equipment['equipment_id']];
                                }

                            } else if (isset($stop['stop_pb_id']) and (int)$stop['stop_pb_id'] > 0) {
                                StopPb::deleteAll(['id' => $stop['stop_pb_id']]);
                            }

                        }
                    }

                    // Сохранение фотографий нарушения не обязательное

                    /******************** ФОРМИРОВАНИЕ МАССИВА НА ДОБАВЛЕНИЕ ИЗОБРАЖЕНИЙ ПРЕДПИСАНИЯ ********************/
                    foreach ($injunction_violation['injunction_img'] as $injunction_img)                                               // Перебор массива приостановки работ
                    {
                        if (isset($injunction_img['injunction_img_path'])) {
                            if ($injunction_img['injunction_img_path'] !== null) {
                                if ($injunction_img['injunction_img_flag_status'] == 'new') {
                                    $file_path_there = Assistant::UploadFile(
                                        $injunction_img['injunction_img_path'],
                                        $injunction_img['injunction_img_name'],
                                        'injunction',
                                        $injunction_img['injunction_img_type']);
                                    $new_injunction_images[] = [$file_path_there, $new_injunction_violation_id];
                                } elseif ($injunction_img['injunction_img_flag_status'] == null) {
                                    $file_path_there = $injunction_img['injunction_img_path'];
                                    $new_injunction_images[] = [$file_path_there, $new_injunction_violation_id];
                                }
                            }
                        }

                    }
                }
            }
            $log->addLog("Сохранил нарушения");

            $checking_places = CheckingPlace::find()
                ->where(['checking_id' => $checking_id])
                ->indexBy('place_id')
                ->all();
            foreach ($checking_places as $checking_place) {
                if (!in_array($checking_place->place_id, $places)) {
                    $checking_place_to_delete[] = $checking_place->id;
                }
            }
            foreach ($places as $place) {
                if (!isset($checking_places[$place])) {
                    $checking_place_to_insert[] = ['checking_id' => $checking_id, 'place_id' => $place];
                }
            }

            if (!empty($checking_place_to_delete)) {
                $delete_checking_place = CheckingPlace::deleteAll(['in', 'id', $checking_place_to_delete]);
                if (!$delete_checking_place) {
                    throw new Exception('При удалении связки места и проверки произошла ошибка');
                }
            }

            if (!empty($add_violators)) {
                $insert_violators = Yii::$app->db->createCommand()->batchInsert('violator', ['injunction_violation_id', 'worker_id'], $add_violators)->execute();
                if (!$insert_violators) {
                    throw new Exception('При добавлении связки проверки и места произошла ошибка');
                }
            }
            if (isset($inj_statuses) && !empty($inj_statuses)) {
                $insert_injunstion_statuses = Yii::$app->db->createCommand()->batchInsert('injunction_status', ['injunction_id', 'worker_id', 'status_id', 'date_time'], $inj_statuses)->execute();
                if (!$insert_injunstion_statuses) {
                    throw new Exception('Ошибка при добавлении статусов предписаниям');
                }
            }

            if (isset($inj_viol_statuses) && !empty($inj_viol_statuses)) {
                $insert_injunstion_violation_statuses = Yii::$app->db->createCommand()->batchInsert('injunction_violation_status', ['injunction_violation_id', 'worker_id', 'status_id', 'date_time'], $inj_viol_statuses)->execute();
                if (!$insert_injunstion_violation_statuses) {
                    throw new Exception('Ошибка при добавлении статусов нарушений предписаниям');
                }
            }

            if (!empty($checking_place_to_insert)) {
                $insert_checking_place = Yii::$app->db->createCommand()->batchInsert('checking_place', ['checking_id', 'place_id'], $checking_place_to_insert)->execute();
                if (!$insert_checking_place) {
                    throw new Exception('При добавлении связки проверки и места произошла ошибка');
                }
            }

            if (isset($new_correct_measures) && !empty($new_correct_measures))                                                                                // Если массив с корректирующими мероприятиями сформирован, добавляем их в БД
            {
                Yii::$app->db->createCommand()->batchInsert('correct_measures', ['injunction_violation_id', 'operation_id', 'date_time', 'status_id', 'correct_measures_value', 'correct_measures_description'], $new_correct_measures)->execute();
//                $insert_param_val = Yii::$app->db->queryBuilder->batchInsert('correct_measures', ['injunction_violation_id', 'operation_id', 'date_time', 'status_id', 'correct_measures_value'], $new_correct_measures);
//                $insert_result_to_MySQL = Yii::$app->db->createCommand($insert_param_val . " ON DUPLICATE KEY UPDATE `operation_id` = VALUES (`operation_id`), `date_time` = VALUES (`date_time`), `status_id` = VALUES (`status_id`), `correct_measures_value` = VALUES (`correct_measures_value`)")->execute();
            }

            if (isset($new_stop_pbs_equipment) && !empty($new_stop_pbs_equipment))                                                                                        // Если массив с корректирующими мероприятиями сформирован, добавляем их в БД
            {
                Yii::$app->db->createCommand()->batchInsert('stop_pb_equipment', ['stop_pb_id', 'equipment_id'], $new_stop_pbs_equipment)->execute();
            }

            if (isset($new_injunction_images))                                                                               // Если массив с корректирующими мероприятиями сформирован, добавляем их в БД
            {
                Yii::$app->db->createCommand()->batchInsert('injunction_img', ['img_path', 'injunction_violation_id'], $new_injunction_images)->execute();
            } else {
                throw new Exception('Не передан массив изображений предписаний');
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $new_checking], $log->getLogAll());
    }

    /**
     * Метод GetInjunctionStatus() - Метод получения статусов предписаний из справочника статусов
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\Injunction&method=GetInjunctionStatus&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.11.2019 8:41
     */
    public static function GetInjunctionStatus()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetInjunctionStatus. Начало метода';
        try {
            $result = Status::find()
                ->select(['id', 'title'])
                ->where(['status_type_id' => 11])
                ->indexBy('id')
                ->asArray()
                ->all();
        } catch (Throwable $exception) {
            $errors[] = 'GetInjunctionStatus. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetInjunctionStatus. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetRtnStatus() - Метод получения статусов РТН из справочника статусов
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\Injunction&method=GetRtnStatus&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.11.2019 8:44
     */
    public static function GetRtnStatus()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $warnings[] = 'GetRtnStatus. Начало метода';
        try {
            $result = Status::find()
                ->select(['id', 'title'])
                ->where(['status_type_id' => 12])
                ->indexBy('id')
                ->asArray()
                ->all();
        } catch (Throwable $exception) {
            $errors[] = 'GetRtnStatus. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = 'GetRtnStatus. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetPlaceByTitle() - Получить идентификатор места по наименованию, если такого наименования не существует создаст место
     * @param string $place_title - JSON с данными: place_title - Наименование места
     * @return array -  стандартный массив возвращаемых данных: Items - идентификатор места, warnings - предупреждения (ход выполнения метода), errors - ошибки
     *
     * @package frontend\controllers\handbooks
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\Injunction&method=GetPlaceByTitle&subscribe=&data={"place_title":"Порож. обгонная кл. ств. 3 гор."}
     *
     * АЛГОРИТМ:
     * 1. Удалить пробелы в полученном наименовании места
     * 2. Выполнить запрос на получение места по наименованию
     *      нашли?      Взять идентификатор места
     *      не нашли? Проверить на пустоту наименование места
     *                      пустое?         Взять идентификатор места по умолчанию (Не задано = 1)
     *                      не пустое?      Создать новый экземпляр объекта (Main)
     *                                      Проверить длину строки наименования места
     *                                              больше 255?     обрезать до 255 символов
     *                                              не больше 255?  оставить как есть
     *                                      Создать новое место (place) с идентификатором экземпляра объекта (main)
     *
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.02.2020 8:26
     */
    public static function GetPlaceByTitle($place_title)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'GetPlace';
        $place_id = null;                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {

            /**
             * Удаляем пробелы с начала и конца строки
             */
//            $warnings[] = $method_name . '. Строка на входе';
//            $warnings[] = $place_title;
            $str_len_place = strlen($place_title);                                            // вычисляем длину нарушения

            if ($str_len_place > 250) {
                $place_name = mb_substr($place_title, 0, 250);                       // если длина больше 250 символов, то мы ее обрезаем
            } else {
                $place_name = $place_title;                                           // иначе оставляем так как есть
            }
            $place_name = trim($place_name);

//            $warnings[] = $method_name . '. Строка на выходе';
//            $warnings[] = $place_name;
            /**
             * Ищем место по наименованию
             */
            $place = Place::findOne(['title' => $place_name]);
            if (!$place) {
                /**
                 * Если наименование не пустое, создаём новый main (экземпляры объектов), и с таким же идентификатором создаём место
                 */
                if (!empty($place_name)) {
                    $place = new Place();
                    $place->title = $place_name;
                    $place->object_id = 180;                                                                            // идентификатор объекта = PLACE (место)
                    $place->mine_id = 1;                                                                                // шахта по умолчанию
                    $place->plast_id = 2109;                                                                            // 2109 - прочее
                    if (!$place->save()) {
                        $errors[] = $place->errors;
                        throw new Exception($method_name . '. Ошибка сохранения справочника мест Place');
                    }
                    $place_id = $place->id;
                    HandbookCachedController::clearPlaceCache();
//                    $warnings[] = $method_name . '. Место Создал. Его айди: ' . $place_id;
                    unset($main, $place);
                } else {
                    $place_id = 1;
//                    $warnings[] = $method_name . '. Место пустое. айди по умолчанию: ' . $place_id;
                }
                unset($trim_place);
            } else {
                $place_id = $place->id;
//                $warnings[] = $method_name . '. Место нашел. Его айди: ' . $place_id;
            }
            unset($place);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';

        return array('Items' => $place_id, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод NewStatusForInjunction() - смена статуса на основе фактической и плановой даты
     * @param $inj_status_id - старый статус предписания
     * @param $date_plan - плановая дата устранения
     * @param $date_fact - фактическая дата устранения
     * @return int status_id - идентификатор нового статуса
     *
     * @package frontend\controllers\handbooks
     *
     * @example
     *
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.02.2020 8:53
     */
    public static function NewStatusForInjunction($date_plan, $date_fact, $inj_status_id = null)
    {
        $currrent_date = BackendAssistant::GetDateNow();

        if (!empty($inj_status_id)) {                                                                              // блок изменения статуса предписания
            if (empty($date_fact) && !empty($date_plan) && ($inj_status_id == 57 or $inj_status_id == 58)) {            //есть план, но нет факта (не исполнено) и оно или новое или в работе, то проверяем истек или нет срок
                if ($date_plan < $currrent_date) {                                                                          // если срок истек от текущей даты, то оно просрочено
                    $status_id = 60;
                } else {                                                                                                    // иначе сохраняем статус какой был
                    $status_id = $inj_status_id;
                }
            } else {                                                                                                    // во всех остальных случаях предписание устранено
                $status_id = 59;
            }
        } else {                                                                                                   // блок создания статуса
            if (empty($date_fact) and !empty($date_plan)) {                                                          // если есть плановая но нет фактической даты устранения нарушения
                if ($date_plan < $currrent_date) {                                                                      // если предписание просрочено от текущей даты, то оно просрочено
                    $status_id = 60;                                                                                        // 60 - просрочено
                } else {
                    $status_id = 57;                                                                                        // 57 - новое
                }
            } else {
                $status_id = 59;                                                                                            // 59 - устранено
            }
        }
        return $status_id;
    }

    /**
     * Метод ViolationType() - Добавдение нового типа нарушения
     * @param null $data_post - JSON с массивом данных: kind_violation_id - идентификатор вида нарушения
     *                                                  kind_violation_title - наименование вида нарушения
     *                                                  ref_error_direction_id - идентификатор нарушения из справочника оракл
     *                                                  date_time_sync - дата и время синхронизации (добавления)
     *
     * @return array violation_type_id - идентификатор нового типа нарушения
     *
     * @package frontend\controllers\handbooks
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\Injunction&method=NewStatusForInjunction&subscribe=&data={"kind_violation_id":20,"kind_violation_title":"Инструкции (должностные, производственные, по ОТ и т.д.)","ref_error_direction_id":20,"date_time_sync":"2018-10-23 00:00:00"}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.02.2020 9:07
     */
    public static function ViolationType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'ViolationType';
        $violation_type_id = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_violation_id') ||
                !property_exists($post_dec, 'kind_violation_title') ||
                !property_exists($post_dec, 'ref_error_direction_id') ||
                !property_exists($post_dec, 'date_time_sync'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $kind_violation_id = $post_dec->kind_violation_id;
            $kind_violation_title = $post_dec->kind_violation_title;
            $ref_error_direction_id = $post_dec->ref_error_direction_id;
            $date_time_sync = $post_dec->date_time_sync;

            $add_viol_type = new ViolationType();
            $add_viol_type->kind_violation_id = $kind_violation_id;
            $add_viol_type->title = $kind_violation_title;
            $add_viol_type->ref_error_direction_id = $ref_error_direction_id;
            $add_viol_type->date_time_sync = $date_time_sync;
            if ($add_viol_type->save()) {
                $warnings[] = "$method_name. Сохранил violation_type, так как такого небыло";
                $add_viol_type->refresh();
                $violation_type_id = $add_viol_type->id;
            } else {
                $errors[] = $add_viol_type->errors;
                throw new Exception($method_name . '.Ошибка при сохранении Violation_type');
            }

        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $violation_type_id;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод ViolationTypeWithoutJson() - Добавдение нового типа нарушения
     * @param null $data_post - JSON с массивом данных: kind_violation_id - идентификатор вида нарушения
     *                                                  kind_violation_title - наименование вида нарушения
     *                                                  ref_error_direction_id - идентификатор нарушения из справочника оракл
     *                                                  date_time_sync - дата и время синхронизации (добавления)
     *
     * @return array violation_type_id - идентификатор нового типа нарушения
     *
     * @package frontend\controllers\handbooks
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.02.2020 9:07
     */
    public static function ViolationTypeWithoutJson($kind_violation_id, $kind_violation_title, $ref_error_direction_id, $date_time_sync)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'ViolationType';
        $violation_type_id = array();                                                                                // Промежуточный результирующий массив
//        $warnings[] = $method_name . '. Начало метода';
        try {
            $add_viol_type = new ViolationType();
            $add_viol_type->kind_violation_id = $kind_violation_id;
            $add_viol_type->title = $kind_violation_title;
            $add_viol_type->ref_error_direction_id = $ref_error_direction_id;
            $add_viol_type->date_time_sync = $date_time_sync;
            if ($add_viol_type->save()) {
                $warnings[] = "$method_name. Сохранил violation_type, так как такого небыло";
                $add_viol_type->refresh();
                $violation_type_id = $add_viol_type->id;
            } else {
                $errors[] = $add_viol_type->errors;
                throw new Exception($method_name . '.Ошибка при сохранении Violation_type');
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
//        $warnings[] = $method_name . '. Конец метода';
        return array('Items' => $violation_type_id, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetDocumentByTitle() - Получить документ по наименованию, если такого документа не существет создат такой документ
     * @param null $data_post - JSON с данными: document_title - наименование документа
     *                                          worker_id - идентификатор работника создающего документ (берётся только при создании нового документа)
     * @return array -  стандартный массив возвращаемых данных: Items - идентификатор документа, warnings - предупреждения (ход выполнения метода), errors - ошибки
     *
     * @package frontend\controllers\handbooks
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\Injunction&method=GetDocumentByTitle&subscribe=&data={"document_title":"ФЗ N 2395-1 О недрах","worker_id":1}
     *
     * АЛГОРИТМ:
     * 1. Удалить пробелы в полученном наименовании документа
     * 2. Выполнить запрос на получение документа по наименованию
     *      нашли?      Взять идентификатор документа
     *      не нашли? Проверить на пустоту наименование документа
     *                      пустое?         Взять идентификатор места по умолчанию (Без документа = 20000)
     *                      не пустое?      Сохранить новый документ
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.02.2020 9:38
     */
    public static function GetDocumentByTitle($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'GetDocumentByTitle';
        $document_id = null;                                                                                // Промежуточный результирующий массив
//        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
//            $warnings[] = $method_name . '. Данные успешно переданы';
//            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
//            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'document_title') ||
                !property_exists($post_dec, 'worker_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
//            $warnings[] = $method_name . '. Данные с фронта получены';
            $document_title = $post_dec->document_title;
            $worker_id = $post_dec->worker_id;

            $currrent_date = BackendAssistant::GetDateNow();
            /**
             * Удаляем пробелы с начала и конца строки наименования документа
             */
            $trim_doc_title = trim($document_title);
            /**
             * Получить документ по наименованию
             */
            $document = Document::findOne(['title' => $trim_doc_title]);
            if (!$document) {
                /**
                 * Если документ не найден и название не пустое, тогда создать новый документв
                 */
                if ($document_title and $document_title != "" and !empty($trim_doc_title)) {
                    $document = new Document();
                    $document->title = $trim_doc_title;
                    $document->worker_id = $worker_id;
//                    $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';              // перечисление всех символов английского алфавита и цифр
//                    $document->number_document = substr(str_shuffle($permitted_chars), 0, 6);                         // заполнить номер документа из переменашшной строки выше взяв первые 6 символов
                    $document->date_start = $currrent_date;
                    $document->date_end = $currrent_date;
                    $document->status_id = 1;                                                                           // по умолчанию статус активный (status_id = 1)
                    $document->vid_document_id = VidDocumentEnumController::NORMATIVE_DOCUMENT;                                                                    // по умолчания вид документа: Нормативные документы (vid_document_id = 21)
                    if ($document->save()) {
                        $document->refresh();
                    } else {
                        $errors[] = $document->errors;
                        throw new Exception($method_name . '. Ошибка сохранения параграфа документа Document');
                    }
                    $document_id = $document->id;
                } else {
                    /**
                     * Если наименование документа оказалось пустым, возвращаем идентификатор документа по умолчанию
                     */
                    $document_id = 20000;
                }
                unset($trim_doc_title);
            } else {
                /**
                 * Если нашли документ по наименованию, взять его идентификатор
                 */
                $document_id = $document->id;
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
//        $warnings[] = $method_name . '. Конец метода';
        return array('Items' => $document_id, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetDocumentByTitleWitoutJson() - Получить документ по наименованию, если такого документа не существет создат такой документ
     * @param $document_title - название документа
     * @param $worker_id - ключ работника
     * @return array -  стандартный массив возвращаемых данных: Items - идентификатор документа, warnings - предупреждения (ход выполнения метода), errors - ошибки
     *
     * @package frontend\controllers\handbooks
     *
     * АЛГОРИТМ:
     * 1. Удалить пробелы в полученном наименовании документа
     * 2. Выполнить запрос на получение документа по наименованию
     *      нашли?      Взять идентификатор документа
     *      не нашли? Проверить на пустоту наименование документа
     *                      пустое?         Взять идентификатор места по умолчанию (Без документа = 20000)
     *                      не пустое?      Сохранить новый документ
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.02.2020 9:38
     */
    public static function GetDocumentByTitleWitoutJson($document_title, $worker_id)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'GetDocumentByTitleWitoutJson';
        $document_id = null;                                                                                // Промежуточный результирующий массив
//        $warnings[] = $method_name . '. Начало метода';
        try {
            $currrent_date = BackendAssistant::GetDateNow();
            /**
             * Удаляем пробелы с начала и конца строки наименования документа
             */
            $trim_doc_title = trim($document_title);
            /**
             * Получить документ по наименованию
             */
            $document = Document::findOne(['title' => $trim_doc_title]);
            if (!$document) {
                /**
                 * Если документ не найден и название не пустое, тогда создать новый документв
                 */
                if ($document_title and $document_title != "" and !empty($trim_doc_title)) {
                    $document = new Document();
                    $document->title = $trim_doc_title;
                    $document->worker_id = $worker_id;
//                    $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';              // перечисление всех символов английского алфавита и цифр
//                    $document->number_document = substr(str_shuffle($permitted_chars), 0, 6);                         // заполнить номер документа из переменашшной строки выше взяв первые 6 символов
                    $document->date_start = $currrent_date;
                    $document->date_end = $currrent_date;
                    $document->status_id = 1;                                                                           // по умолчанию статус активный (status_id = 1)
                    $document->vid_document_id = VidDocumentEnumController::NORMATIVE_DOCUMENT;                                                                    // по умолчания вид документа: Нормативные документы (vid_document_id = 21)
                    if ($document->save()) {
                        $document->refresh();
                    } else {
                        $errors[] = $document->errors;
                        throw new Exception($method_name . '. Ошибка сохранения параграфа документа Document');
                    }
                    $document_id = $document->id;
                } else {
                    /**
                     * Если наименование документа оказалось пустым, возвращаем идентификатор документа по умолчанию
                     */
                    $document_id = 20000;
                }
                unset($trim_doc_title);
            } else {
                /**
                 * Если нашли документ по наименованию, взять его идентификатор
                 */
                $document_id = $document->id;
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
//        $warnings[] = $method_name . '. Конец метода';
        return array('Items' => $document_id, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetParagraphPbByText() - Получение Пункта документа по тексту пункта, если такого не существует, создаёт новый пункт документа
     * @param null $data_post - JSON  с данными: paragraph_pb_title - текст пункта документа
     *                                           document_id - внешний идентификатор документа
     * @return array -  стандартный массив возвращаемых данных: Items - идентификатор пункта документа, warnings - предупреждения (ход выполнения метода), errors - ошибки
     *
     * @package frontend\controllers\handbooks
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\Injunction&method=GetParagraphPbByText&subscribe=&data={"paragraph_pb_title":"Нарушение: п.66","document_id":706}
     *
     * АЛГОРИТМ:
     * 1. Удалить пробелы в полученном наименовании пункта документа
     * 2. Выполнить запрос на получение пункта документа по тексту
     *      нашли?      Взять идентификатор пункта документа
     *      не нашли? Проверить на пустоту пункт документа
     *                      пустое?         Взять идентификатор места по умолчанию (Без пункта = 347)
     *                      не пустое?      Сохранить новый пункт документа
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.02.2020 10:37
     */
    public static function GetParagraphPbByText($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'GetParagraphPbByText';
        $paragraph_pb_id = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'paragraph_pb_title') ||
                !property_exists($post_dec, 'document_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $paragraph_pb_title = $post_dec->paragraph_pb_title;
            $document_id = $post_dec->document_id;
            /**
             * Убираем пробелы в начале и конце строки
             */
            $trim_place_title = trim($paragraph_pb_title);
            /**
             * ищмем пункт документа по тексту
             */
            $paragraphPb = ParagraphPb::findOne(['text' => $trim_place_title]);
            if (!$paragraphPb) {
                if ($trim_place_title and $trim_place_title != "" and !empty($trim_place_title)) {
                    /**
                     * Если пункт не найден и текст не пуст тогда создаём новый пункт документа
                     */
                    $paragraphPb = new ParagraphPb();
                    $paragraphPb->text = $trim_place_title;
                    $paragraphPb->document_id = $document_id;
                    if ($paragraphPb->save()) {
                        $paragraphPb->refresh();
                    } else {
                        $errors[] = $paragraphPb->errors;
                        throw new Exception($method_name . '. Ошибка сохранения параграфа документа ParagraphPb');
                    }
                    $paragraph_pb_id = $paragraphPb->id;
                    unset($trim_text);
                } else {
                    /**
                     * Если пункт не найден и текст пустой возвращаем Пункт документа по умолчанию
                     */
                    $paragraph_pb_id = 347;
                }
            } else {
                $paragraph_pb_id = $paragraphPb->id;
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $paragraph_pb_id;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetParagraphPbByTextWithoutJson() - Получение Пункта документа по тексту пункта, если такого не существует, создаёт новый пункт документа
     * @param $paragraph_pb_title - название параграфа ПБ
     * @param $document_id - ключ документа
     * @return array -  стандартный массив возвращаемых данных: Items - идентификатор пункта документа, warnings - предупреждения (ход выполнения метода), errors - ошибки
     *
     * @package frontend\controllers\handbooks
     *
     * АЛГОРИТМ:
     * 1. Удалить пробелы в полученном наименовании пункта документа
     * 2. Выполнить запрос на получение пункта документа по тексту
     *      нашли?      Взять идентификатор пункта документа
     *      не нашли? Проверить на пустоту пункт документа
     *                      пустое?         Взять идентификатор места по умолчанию (Без пункта = 347)
     *                      не пустое?      Сохранить новый пункт документа
     *
     * Created date: on 17.02.2020 10:37
     */
    public static function GetParagraphPbByTextWithoutJson($paragraph_pb_title, $document_id)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'GetParagraphPbByText';
        $paragraph_pb_id = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            /**
             * Убираем пробелы в начале и конце строки
             */
            $trim_place_title = trim($paragraph_pb_title);
            /**
             * ищмем пункт документа по тексту
             */
            $paragraphPb = ParagraphPb::findOne(['text' => $trim_place_title]);
            if (!$paragraphPb) {
                if ($trim_place_title and $trim_place_title != "" and !empty($trim_place_title)) {
                    /**
                     * Если пункт не найден и текст не пуст тогда создаём новый пункт документа
                     */
                    $paragraphPb = new ParagraphPb();
                    $paragraphPb->text = $trim_place_title;
                    $paragraphPb->document_id = $document_id;
                    if ($paragraphPb->save()) {
                        $paragraphPb->refresh();
                    } else {
                        $errors[] = $paragraphPb->errors;
                        throw new Exception($method_name . '. Ошибка сохранения параграфа документа ParagraphPb');
                    }
                    $paragraph_pb_id = $paragraphPb->id;
                    unset($trim_text);
                } else {
                    /**
                     * Если пункт не найден и текст пустой возвращаем Пункт документа по умолчанию
                     */
                    $paragraph_pb_id = 347;
                }
            } else {
                $paragraph_pb_id = $paragraphPb->id;
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
//        $warnings[] = $method_name . '. Конец метода';
        return array('Items' => $paragraph_pb_id, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    /**
     * Метод GetOperationByTitle() - Получить идентификатор операции по наименованию, если такой операции не существует то создаёт
     * @param null $data_post - JSON  с данными: operation_title - наименование операции
     * @return array -  стандартный массив возвращаемых данных: Items - идентификатор операции, warnings - предупреждения (ход выполнения метода), errors - ошибки
     *
     * @package frontend\controllers\handbooks
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\Injunction&method=GetOperationByTitle&subscribe=&data={"operation_title":"Устранить"}
     *
     * АЛГОРИТМ:
     * 1. Удалить пробелы в полученном наименовании операции
     * 2. Выполнить запрос на получение операции по наименованию
     *      нашли?      Взять идентификатор операции
     *      не нашли? Проверить на пустоту наименование операции
     *                      пустое?         Взять идентификатор места по умолчанию (Устранить = 26)
     *                      не пустое?      Проверить длину строки наименования операции
     *                                              больше 255?     обрезать до 255 символов
     *                                              не больше 255?  оставить как есть
     *                                      Создать новую операцию
     *                                      Добавить новую операцию в группу операций "Работы по линии ПК"
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.02.2020 11:24
     */
    public static function GetOperationByTitle($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'GetOperationByTitle';
        $operation_id = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'operation_title'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $operation_title = $post_dec->operation_title;
            /**
             * Убираем пробелы с названия операции
             */
            $trim_operation_title = trim($operation_title);
            /**
             * Поиск операции по наименованию
             */
            $operation = Operation::findOne(['title' => $operation_title]);
            if (!$operation) {
                if (!empty($trim_operation_title)) {
                    /**
                     * Если операция не найдена и наименование операции не пустое, создаём новую операцию
                     */
                    $operation = new Operation();
                    /**
                     * Проверяем длину строки наименования, если больше 255 обрезаем до 255 символов
                     */
                    $str_len = strlen($operation_title);
                    if ($str_len > 255) {
                        $operation_saving_title = mb_substr($operation_title, 0, 255);
                    } else {
                        $operation_saving_title = $operation_title;
                    }
                    unset($str_len);
                    $operation->title = $operation_saving_title;                                                        // название операции
                    $operation->unit_id = 79;                                                                           // изиницы измерения 79 - прочее
                    $operation->operation_type_id = 22;                                                                 // ППК ПАБ (5) - все подряд (22)
                    $operation->value = 0;                                                                              // количество операций на нагрузку
                    $operation->description = " - ";                                                                    // описание операции
                    $operation->operation_load_value = 0;                                                               // нагрузка
                    $operation->short_title = " - ";                                                                    // сокращенное название
                    if (!$operation->save()) {
                        $errors[] = "$method_name. Не смог создать запись Operation";
                        $errors[] = $operation_title;
                        $errors[] = $operation->errors;
                        throw new Exception($method_name . '. Ошибка сохранения операции Operation');
                    } else {
                        $operation->refresh();
                    }
                    $operation_id = $operation->id;
                    unset($operation);
                    /**
                     * Добавляем операцию в группу операций
                     */
                    $new_group_operation = new OperationGroup();
                    $new_group_operation->operation_id = $operation_id;                                                // идентификаор только что созданной операции
                    $new_group_operation->group_operation_id = 2;                                                       // группа операций "Работы по линии ПК"
                    if (!$new_group_operation->save()) {
                        $errors[] = "$method_name. Не смог создать связь операции и группы операции OperationGroup";
                        $errors[] = $operation_title;
                        $errors[] = $new_group_operation->errors;
                        throw new Exception($method_name . '. Ошибка сохранения связи операции и группы операций OperationGroup');
                    }
                    unset($new_group_operation);
                    HandbookCachedController::clearOperationCache();
                } else {
                    /**
                     * Если передалось пустое наименование тогда возвращаем идентификатор операции по умолчанию (Устранить)
                     */
                    $operation_id = 26;
                }
                unset($trim_operation_title);
            } else {
                $operation_id = $operation->id;
            }
            $result = $operation_id;
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);;
    }

    /**
     * Метод ChangeOrSaveInjunction() - Изменение или сохранение предписания
     * @param $sync_from - из какой синхронизации пришли
     * @param $change - флаг изменять или сохранять предписание
     * @param $injunction_data - данные предписания
     * @param array $injunction_model - модель предписания
     * @param int $observation_number - номер наблюдения
     * @return array
     * ВХОДНЫЕ ПАРАМЕТРЫ:
     * sync_from - из какой синхронизации пришли
     *  change - флаг изменять или сохранять предписание
     *  injunction_data - данные предписания
     *  injunction_model - модель предписания (по умолчанию пуста)
     *  observation_number - номер наблюдения (по умолчанию пуст)
     *
     * ВЫХОДНЫЕ ПАРАМЕТРЫ:
     * {
     *      “Items”:  1,                    // идентификатор предписания
     *      “ injunction_status_id”: 1,        // статус сохранённого предписания
     *      “errors”: [],                    // массив ошибок
     *      “warnings”: []                    // массив предупреждений (ход выполнения метода)
     * }
     *
     * @package frontend\controllers\handbooks
     * АЛГОРИТМ:
     * 1. Проверить пустой ли номер наблюдения
     *      пустой?     Делаем 0 по умолчанию
     *      не пустой?  Делаем инкремент
     * 2. В зависимости от того из какой синхронизации вызвано изменение/добавление предписания
     *      Выбираем поля для заполнения модели injunction
     *              2.1. Если внутренние предписания: Заполнить instruct_id_ip, date_time_sync
     *                      Для заполнения instruct_id_ip взять поле INSTRUCTION_POINT_ID
     *                      Для заполнения rtn_statistic_status_id установить статус = 56
     *                      Для заполнения вида документа установить идентификатор = 1 (Предписание)
     *             2.2. Если РТН: Заполнять instruct_rtn_id, date_time_sync_rostex
     *                      Для заполнения instruct_rtn_id взять поле INSTRUCTION_ROSTEX_ID
     *                      Для заполнения rtn_statistic_status_id установить статус = 55
     *                      Для заполнения вида документа установить идентификатор = 3 (Предписание РТН)
     * 3. Проверям переменную $change (флаг: true - Изменить, false - создать)
     *      true?   Изменить модель ($injunction_data)
     *              Изменяем данные переданной модели
     *              Для изменения статуса вызываем базовый метод изменения/создания статуса (NewStatusForInjunction)
     *      false?  Добавляем новое предписание на основе полученных данных ($injunction_data)
     *              Для установки статуса вызываем базовый метод изменения/создания статуса (NewStatusForInjunction)
     * 4. Возвращаем массив:
     *                      warnings - предупреждения,
     *                      errors - ошибки,
     *                      injunction_id - идентификатор предписания,
     *                      injunction_status_id - статус предписания
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 19.02.2020 9:38
     */
    public static function ChangeOrSaveInjunction($sync_from, $change, $injunction_data, $injunction_model, $observation_number = null)
    {
        $method = "ChangeOrSaveInjunction. ";
        $errors = array();
        $warnings = array();
        $status = 1;
        $injunction_id = -1;
        $injunction_status_id = -1;
        $method_name = 'ChangeOrSaveInjunction';
        try {
            /**
             * Если номер наблюдения не передан задаём 0 по умрочанию
             */
            if ($observation_number === null) {
                $observation_number = 0;
            } else {
                $observation_number++;
            }
            /**
             * В зависимости от того из какой синхронизации пришли, те данные и заполняем
             */
            if ($sync_from == 'InnerInjunction') {
                $inj_sync_field_instruction = 'instruct_id_ip';                                                             // какое поле обновиться или добавиться в таблице injunction
                $inj_sync_field_date_time = 'date_time_sync';
                $data_filed = 'INSTRUCTION_POINT_ID';
                $kind_document_id = 1;                                                                                      // вид документа 1 - Предписания
                $rtn_statistic_status_id = 56;
            } elseif ($sync_from == 'RTNInjunction') {
                $inj_sync_field_instruction = 'instruct_rtn_id';
                $inj_sync_field_date_time = 'date_time_sync_rostex';
                $data_filed = 'INSTRUCTION_ROSTEX_ID';                                                                      // задаётся поле из которого брать данные
                $kind_document_id = 3;                                                                                      // вид документа 3 - РТН
                $rtn_statistic_status_id = 55;
            }
            /**
             * Изменение
             *      да?     Обновляем данные предписания
             *      нет?    Создаём предписание на основе полученных данных
             */
            if ($change) {
//            $warnings[] = 'Зашёл на изменение. injunction_id = '.$injunction_model->id;
                $injunction_model->worker_id = $injunction_data['worker_id'];
                $injunction_model->observation_number = $observation_number;
                $injunction_model->status_id = self::NewStatusForInjunction($injunction_data['date_plan'], $injunction_data['date_fact'], $injunction_model->status_id);
                /**
                 * Указывается ссылка на переменную к какому полю таблицы injunction нам обращаться ($inj_sync_field_instruction, $inj_sync_field_date_time)
                 * В скобках указывается поле к которому надо обращаться для записи в поле базы ($injunction_data[$data_filed])
                 */
                $injunction_model->$inj_sync_field_instruction = $injunction_data[$data_filed];
                $injunction_model->$inj_sync_field_date_time = $injunction_data['DATE_MODIFIED'];
                if (!$injunction_model->save()) {
                    $errors[] = $injunction_model->errors;
                    throw new Exception($method . '. ошибка сохранения модели Injunction');
                }
                $injunction_model->refresh();
                $injunction_id = $injunction_model->id;
                $injunction_status_id = $injunction_model->status_id;
//            $warnings[] = 'Изменил предписание. injunction_id = '.$injunction_id;
            } else {
//            $warnings[] = 'Зашёл на добавление. ROSTEX_NOMER = '.$injunction_data[$data_filed];
                $injunction = new Injunction();
                $injunction->place_id = $injunction_data['place_id'];                                                       // место где производилась проверка и выписано предписание
                $injunction->worker_id = $injunction_data['worker_id'];                                                     // ключ работника проводившего проверку
                $injunction->kind_document_id = $kind_document_id;                                                                          // Ви проверки 1 - предписание, 2 ПАБ, 3 - Предписание РТН
                $injunction->rtn_statistic_status_id = $rtn_statistic_status_id;                                                                  // статус отображения или нет предписания в статистике 55 - да, 56 - нет
                $injunction->checking_id = $injunction_data['checking_id'];                                                 // ключ проверки
                $injunction->description = null;                                                                            // описание проверки
                $injunction->status_id = self::NewStatusForInjunction($injunction_data['date_plan'], $injunction_data['date_fact']);
                $injunction->observation_number = 0;// номер наблюдения
                $injunction->company_department_id = $injunction_data['company_department_id'];                             // департамент в котором производилась проверка
                /**
                 * Указывается ссылка на переменную к какому полю таблицы injunction нам обращаться ($inj_sync_field_instruction, $inj_sync_field_date_time)
                 * В скобках указывается поле к которому надо обращаться для записи в поле базы ($injunction_data[$data_filed])
                 */
                $injunction->$inj_sync_field_instruction = $injunction_data[$data_filed];
                $injunction->$inj_sync_field_date_time = $injunction_data['DATE_MODIFIED'];
                if (!$injunction->save()) {
                    $errors[] = $injunction->errors;
                    throw new Exception($method . '. ошибка сохранения модели Injunction');
                }
                $injunction->refresh();
                $injunction_id = $injunction->id;
                $injunction_status_id = $injunction->status_id;
//            $warnings[] = 'Добавил предписание. injunction_id = '.$injunction_id;
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        return array('errors' => $errors, 'injunction_id' => $injunction_id, 'injunction_status_id' => $injunction_status_id, 'warnings' => $warnings, 'status' => $status);
    }

    /**
     * Метод AddViolationDisconformity() - Сохранение нарушения несоответствия
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * Входные обязательные параметры:
     * @example http://amicum/read-manager-amicum?controller=handbooks\Injunction&method=AddViolationDisconformity&subscribe=&data={}
     *
     * АЛГОРИТМ работы метода:
     * 1. Получить: идентификатор проверки, идентификатор участка, НН (нарушения несоответствия)
     * 2. Перебор всех нарушений несоответствий
     *      2.1  Проверить по идентификатору предписание (injunction)
     *              найдено?        редактируем найденное предписание
     *              не найдено?     создаём новое предписание
     *      2.2  Сохранить статус нового/обновлённого предписания
     *      2.3  Добавить в массив мест (для сохранения мест проверок)
     *      2.4  Если было передано вложение со статусом
     *              'new'?          сохранить новое вложение
     *              'del'?          удалить вложение по идентификатору проверки
     *      2.5  Добавляем новое нарушение (violation)
     *      2.6  Передан ли идентификатор документа?
     *              да?             присваиваем переменной идентификатор документа
     *              нет?            идентификатор документа по умолчанию
     *      2.7  Передан ли пункт нарушения?
     *              да?             сохраняем пункт нарушения
     *              нет?            ставиться null
     *      2.8  Передано ли вероятность и тяжесть нарушения?
     *              да?             Заполняем переменные значениями тяжести и вероятности
     *              нет?            Заполняем переменные по умолчанию (вероятность = 1, тяжесть = 1)
     *      2.9  Передан идентификатор нарушения предписания?
     *              да?             Редактируем нарушение предписания
     *              нет?            Создаём новое нарушение предписания
     *      2.10 Массив нарушителей не пуст?
     *              да?             добавляем в массив на сохранение нарушителей
     *              нет?            пропускаем
     *      2.11 Переданы корректирующие мероприятия?
     *              да?             Добавляем в массив на сохранение корректирующих мероприятий
     *              нет?            пропускаем
     *      2.12 Переданы приостановки работ?
     *              да?             Добавляем в массив на сохранение приостановок работ
     *              нет?            пропускаем
     * 3. Конец перебора
     * 4. Массив на сохранение мест проверок не пуст?
     *      да?     Удаляем все прошлые места проверок
     *              Массово сохраняем все новые места этой проверки
     *      нет?    пропускаем
     * 5. Массив на сохранение нарушителей не пуст?
     *      да?     Удаляем всех нарушителей
     *              Массово сохраняем нарушителей
     *      нет?    пропускаем
     * 6. Массив на сохранение статусов нарушений предписаний не пуст?
     *      да?     Массово сохраняем статусы нарушений предписаний
     *      нет?    пропускаем
     * 7. Массив на сохранение связки изображения и предписания не пуст?
     *      да?     Массово сохранить связку изображения и предписания
     *      нет?    пропускаем
     * 8. Массив на сохранение корректирующих мероприятий не пуст?
     *      да?     Массово сохранить корректирующие меропртиятия
     *      нет?    пропускаем
     * 9. Массив на сохранение приостановок работ не пуст?
     *      да?     Массово сохранить приостановоки работ
     *      нет?    пропускаем
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.02.2020 11:00
     */
    public static function AddViolationDisconformity($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'AddViolationDisconformity';
        $saving_nn = array();                                                                                // Промежуточный результирующий массив
        $places = array();
        $inj_viol_violators = array();
        $violators_to_delete = array();
        $new_paragraph_pb_id = null;
        $session = Yii::$app->session;
//        $data_post = '{"checking_id":985302,"company_department_id":20028748,"injunction":{"new_injunction_0":{"attachments":{},"injunction_id":-1,"observation_number":0,"place_id":6183,"worker_id":70000536,"kind_document_id":1,"rtn_statistic_status":56,"injunction_violation":{"new_injunction_violation":{"injunction_violation_id":-1,"reason_danger_motion_id":null,"reason_danger_motion_title":null,"probability":null,"dangerous":null,"correct_period":null,"violation_id":-1,"violation_type_id":null,"violation_type_title":null,"document_id":null,"document_title":null,"paragraph_pb_id":null,"injunction_img":{},"injunction_description":"fgdfgdfg111","paragraph_injunction_description":null,"correct_measures":{},"stop_pb":{},"violators":{}}}}}}';
        $date_time_now = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $post_dec;
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'injunction') ||
                !property_exists($post_dec, 'checking_id') ||
                !property_exists($post_dec, 'kind_document_id') ||
                !property_exists($post_dec, 'company_department_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $injunctions_data = $post_dec->injunction;
            $checking_id = $post_dec->checking_id;
            $kind_document_id = $post_dec->kind_document_id;
            $company_department_id = $post_dec->company_department_id;

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

            InjunctionViolation::deleteAll(['id' => $injunction_violation_ids]);
            Injunction::deleteAll(['id' => $injunction_ids]);


            foreach ($injunctions_data as $injunction_item) {
                $injunction = Injunction::findOne([
                    'place_id' => $injunction_item->place_id,
                    'kind_document_id' => $kind_document_id,
                    'checking_id' => $checking_id,
                    'observation_number' => $injunction_item->observation_number,
                    'worker_id' => $injunction_item->worker_id
                ]);

                if (!$injunction) {
                    $injunction = new Injunction();
                }

                if (!is_string($injunction_item->injunction_id) or is_integer((int)$injunction_item->injunction_id)) {
                    $injunction->id = $injunction_item->injunction_id;
                    $warnings[] = $method_name . '. injunction_id существовал: ' . $injunction_item->injunction_id;
                } else {
                    $warnings[] = $method_name . '. injunction_id был новый: ' . $injunction_item->injunction_id;
                }
                $injunction->place_id = $injunction_item->place_id;
                $injunction->kind_document_id = $kind_document_id;
                $injunction->checking_id = $checking_id;
                $injunction->observation_number = $injunction_item->observation_number;
                $injunction->worker_id = $injunction_item->worker_id;
                $injunction->rtn_statistic_status_id = $injunction_item->rtn_statistic_status;
                /**
                 * Статус не пустой?
                 *      да?     Взять статус из поля status_id
                 *      нет?    Установить статус 57 (Новое)
                 */
                if (isset($injunction_item->status_id) && !empty($injunction_item->status_id)) {
                    $status_id = $injunction_item->status_id;
                } else {
                    $status_id = 57;
                }
                $injunction->status_id = $status_id;
                $injunction->company_department_id = $company_department_id;
                if (!$injunction->save()) {
                    $errors[] = $injunction->errors;
                    throw new Exception($method_name . '. Ошибка при сохранении нарушения несоответствия');
                }
                $injunction->refresh();
                $injunction_id = $injunction->id;
                $injunction_status_id = $injunction->status_id;
                unset($injunction);
                $places[] = [$checking_id, $injunction_item->place_id];
                /******************** СОХРАНЕНИЕ СТАТУСОВ ********************/
                if (isset($injunction_item->injunction_status_all) && !empty($injunction_item->injunction_status_all)) {
                    $del_statuses = InjunctionStatus::deleteAll(['injunction_id' => $injunction_id]);
                    foreach ($injunction_item->injunction_status_all as $inj_status_item) {
                        $inj_statuses[] = [
                            $injunction_id,
                            $inj_status_item->worker_id,
                            $inj_status_item->status_id,
                            date('Y-m-d H:i:s', strtotime($inj_status_item->date_time))
                        ];
                    }
                }
                $inj_statuses[] = [
                    $injunction_id,
                    Yii::$app->session['worker_id'],
                    $injunction_status_id,
                    $date_time_now
                ];
                /******************** СОХРАНЕНИЕ ВЛОЖЕНИЙ ********************/
                if (isset($injunction_item->attachments) && !empty($injunction_item->attachments)) {
                    foreach ($injunction_item->attachments as $attachment) {
                        if (!empty($attachment->attachment_src)) {
                            if ($attachment->attachment_flag == 'new') {
                                $file_path_attachment = Assistant::UploadFile($attachment->attachment_src, $attachment->attachment_name, 'attachment', $attachment->attachment_type);
                                $add_attachment = new Attachment();
                                $add_attachment->path = $file_path_attachment;
                                $add_attachment->date = BackendAssistant::GetDateFormatYMD();
                                $add_attachment->worker_id = $session['worker_id'];
                                $add_attachment->section_title = 'Книга предписаний';
                                if (!$add_attachment->save()) {
                                    $errors[] = $add_attachment->errors;
                                    throw new Exception($method_name . '. Во время добавления вложения произошла ошибка.');
                                }
                                $add_attachment->refresh();
                                $attachment_id = $add_attachment->id;
                            } elseif ($attachment->attachment_flag == 'del') {
                                $del_inj_attachment = InjunctionAttachment::deleteAll(['injunction_id' => $injunction_id, 'attachment_id' => $attachment->attachment_id]);
                            }
                            if (isset($attachment_id) && !empty($attachment_id)) {
                                $add_injunction_attachment = new InjunctionAttachment();
                                $add_injunction_attachment->injunction_id = $injunction_id;
                                $add_injunction_attachment->attachment_id = $attachment_id;
                                if (!$add_injunction_attachment->save()) {
                                    $errors[] = $add_injunction_attachment->errors;
                                    throw new Exception($method_name . '. Во время добавления связки вложения и нарушения произошла ошибка.');
                                }
                            }
                        }
                    }
                }

                foreach ($injunction_item->injunction_violation as $injunction_violation) {
                    /******************** ДОБАВЛЕНИЕ НАРУШЕНИЯ ********************/
                    if (isset($injunction_violation->violation_type_id) && !empty($injunction_violation->violation_type_id)) {
                        $violation_type_id = (int)$injunction_violation->violation_type_id;
                    } else {
                        $violation_type_id = 128;
                    }
                    $new_violation = new Violation();
                    $new_violation->title = $injunction_violation->injunction_description;
                    $new_violation->violation_type_id = $violation_type_id;
                    if (!$new_violation->save()) {
                        $errors[] = $new_violation->errors;
                        throw new Exception($method_name . '. Ошибка при сохранении нарушения');
                    }
                    $new_violation->refresh();
                    $violation_id = $new_violation->id;
                    unset($new_violation);

                    if (isset($injunction_violation->document_id) && !empty($injunction_violation->document_id)) {
                        $document_id = $injunction_violation->document_id;
                    } else {
                        $document_id = 20079;
                    }
                    /******************** ДОБАВЛЕНИЕ ПУНКТА ПРОИЗВОДСТВЕННОЙ БЕЗОПАСНОСТИ ********************/
                    $new_paragraph_pb_id = null;
                    if (!empty($injunction_violation->paragraph_injunction_description)) {
                        if (!empty($injunction_violation->paragraph_injunction_description)) {
                            $trim_text = trim($injunction_violation->paragraph_injunction_description);
                            if (!empty($trim_text)) {
                                $new_paragraph_pb = new ParagraphPb();
                                $new_paragraph_pb->text = $injunction_violation->paragraph_injunction_description;
                                $new_paragraph_pb->document_id = $document_id;
                                if (!$new_paragraph_pb->validate() || !$new_paragraph_pb->save())                                   // Проверяем данные по правилам описанным в модели и выполняем сохранение в случаи успешной валидации
                                {
                                    $errors[] = $new_paragraph_pb->errors;
                                    throw new Exception($method_name . '. Во время добавления нарушения возникло исключение.'); // Проверка не удалась кидаем исключение
                                }
                                $new_paragraph_pb->refresh();
                                $new_paragraph_pb_id = $new_paragraph_pb->id;
                                unset($new_paragraph_pb);
                            }
                        }
                    }
                    /******************** ДОБАВЛЕНИЕ НАРУШЕНИЙ ПРЕДПИСАНИЙ ********************/
                    if (isset($injunction_violation->probability, $injunction_violation->dangerous) && (!empty($injunction_violation->probability) && !empty($injunction_violation->dangerous))) {
                        $probability = $injunction_violation->probability;
                        $gravity = $injunction_violation->dangerous;
                    } else {
                        $probability = 1;
                        $gravity = 1;
                    }


                    $new_injunction_violation = new InjunctionViolation();
                    if (!is_string($injunction_violation->injunction_violation_id) or is_integer((int)$injunction_violation->injunction_violation_id)) {
                        $new_injunction_violation->id = $injunction_violation->injunction_violation_id;
                        $warnings[] = $method_name . '. injunction_violation_id существовал: ' . $injunction_violation->injunction_violation_id;
                    } else {
                        $warnings[] = $method_name . '. injunction_violation_id был новый: ' . $injunction_violation->injunction_violation_id;
                    }
                    $new_injunction_violation->violation_id = $violation_id;
                    $new_injunction_violation->injunction_id = $injunction_id;
                    $new_injunction_violation->place_id = (int)$injunction_item->place_id;
                    $new_injunction_violation->paragraph_pb_id = $new_paragraph_pb_id;
                    $new_injunction_violation->probability = $probability;
                    $new_injunction_violation->gravity = $gravity;
                    $new_injunction_violation->correct_period = $injunction_violation->correct_period;
//                    $new_injunction_violation->reason_danger_motion_id = $injunction_violation->reason_danger_motion_id;
                    $new_injunction_violation->document_id = $document_id;
                    if (!$new_injunction_violation->save()) {
                        $errors[] = $new_injunction_violation->errors;
                        throw new Exception($method_name . '. Ошибка при сохранении нарушения предписания');
                    }
                    $new_injunction_violation->refresh();
                    $injunction_violation_id = $new_injunction_violation->id;
                    unset($new_injunction_violation);
                    /******************** ДОБАВЛЕНИЕ НАРУШИТЕЛЕЙ ********************/
                    if (isset($injunction_violation->violators) && !empty($injunction_violation->violators)) {
                        foreach ($injunction_violation->violators as $violator) {
                            if (!empty($violator->worker_id)) {
                                $inj_viol_violators[] = [$injunction_violation_id, $violator->worker_id];
                                $violators_to_delete[] = [$injunction_violation_id];
                            }
                        }
                    }
                    /******************** ФОРМИРОВАНИЕ МАССИВА НА ДОБАВЛЕНИЕ КОРРЕКТИРУЮЩИХ МЕРОПРИЯТИЙ ********************/
                    if (isset($injunction_violation->correct_measures) && !empty($injunction_violation)) {
                        foreach ($injunction_violation->correct_measures as $correct_measures)                                           // Для каждого нарушения формируется приостановки работ и корректирующие мероприятия
                        {
                            if (!empty($correct_measures->operation_id)) {
                                $del_correct = CorrectMeasures::deleteAll(['injunction_violation_id' => $injunction_violation_id]);
                                if ($correct_measures->operation_value == '' || $correct_measures->operation_value == "") {
                                    $correct_measures_value = 0;
                                } else {
                                    $correct_measures_value = $correct_measures->operation_value;
                                }
                                $date_plan = date('Y-m-d H:i:s.U', strtotime($correct_measures->date_plan));
                                $new_correct_measures[] = [$injunction_violation_id, $correct_measures->operation_id,
                                    $date_plan, self::STATUS_NEW, $correct_measures_value];// Массив корректирующих мероприятий
                            }
                        }
                    }

                    /******************** ФОРМИРОВАНИЕ МАССИВА НА ДОБАВЛЕНИЕ ПРОСТОЕВ ********************/
                    if (isset($injunction_violation->stop_pb) && !empty($injunction_violation->stop_pb)) {
                        $del_stop_pb = StopPb::deleteAll(['injunction_violation_id' => $injunction_violation_id]);
                        foreach ($injunction_violation->stop_pb as $stop)                                                                // Перебор массива приостановки работ
                        {


                            if ($stop->active == true) {
                                $kind_duration_id = ($stop->kind_duration) ? 1 : 2;                                           // Если стоит галочка до устранения передаем kind_duration_id - id до устранения
                                $date_time_start = date('Y-m-d H:i:s', strtotime($stop->date_time_start));
                                if ($stop->until_complete_flag == false) {
                                    if ($stop->date_time_end !== null) {
                                        $date_time_end = date('Y-m-d H:i:s', strtotime($stop->date_time_end));
                                    }
                                } else {
                                    $date_time_end = null;
                                }
                                $stop_pb_insert['injunction_violation_id'] = $injunction_violation_id;
                                $stop_pb_insert['kind_stop_pb_id'] = self::MAIN_STOP_PB_TYPE;
                                $stop_pb_insert['kind_duration_id'] = $kind_duration_id;
                                $stop_pb_insert['place_id'] = $stop->place_id;
                                $stop_pb_insert['date_time_start'] = $date_time_start;
                                $stop_pb_insert['date_time_end'] = $date_time_end;
                                $add_stop_pb = new StopPb();
                                $add_stop_pb->attributes = $stop_pb_insert;
                                if (!$add_stop_pb->save()) {
                                    $errors[] = $add_stop_pb->errors;
                                    throw new Exception($method_name . '. Ошибка при сохранении простоя');
                                }
                                $new_stop_pb_id = $add_stop_pb->id;
                                foreach ($stop->equipment as $equipment) {
                                    $new_stop_pbs_equipment[] = [$new_stop_pb_id, $equipment->equipment_id];
                                }
                            }

                        }
                    }
                    /******************** ФОРМИРОВАНИЕ МАССИВА НА ДОБАВЛЕНИЕ ИЗОБРАЖЕНИЙ ПРЕДПИСАНИЯ ********************/
                    if (isset($injunction_violation->injunction_img) && !empty($injunction_violation->injunction_img)) {
                        foreach ($injunction_violation->injunction_img as $injunction_img)                                               // Перебор массива приостановки работ
                        {
                            if (isset($injunction_img->injunction_img_path)) {
                                if ($injunction_img->injunction_img_path !== null) {
                                    if ($injunction_img->injunction_img_flag_status == 'new') {
                                        $file_path_there = Assistant::UploadFile(
                                            $injunction_img->injunction_img_path,
                                            $injunction_img->injunction_img_name,
                                            'injunction',
                                            $injunction_img->injunction_img_type);
                                        $new_injunction_images[] = [$file_path_there, $injunction_violation_id];
                                    } elseif ($injunction_img->injunction_img_flag_status == null) {
                                        $file_path_there = $injunction_img->injunction_img_path;
                                        $new_injunction_images[] = [$file_path_there, $injunction_violation_id];
                                    }
                                }
                            }

                        }
                    }
                }
            }
            /******************** СОХРАНЕНИЕ МЕСТ ПРОВЕРОК ********************/
            if (isset($places) && !empty($places)) {
                $del_places = CheckingPlace::deleteAll(['checking_id' => $checking_id]);
                $insert_places = Yii::$app->db->createCommand()->batchInsert('checking_place',
                    ['checking_id', 'place_id'], $places)
                    ->execute();
                if ($insert_places != 0) {
                    $warnings[] = $method_name . '. Связка проверки и места успешно добавлена';
                } else {
                    throw new Exception($method_name . '. При добавлении связки проверки и места произошла ошибка');
                }
            }
            /******************** СОХРАНЕНИЕ НАРУШИТЕЛЕЙ ********************/
            if (isset($violators_to_delete) && !empty($violators_to_delete)) {
                $del_violators = Violator::deleteAll(['in', 'injunction_violation_id', $violators_to_delete]);
            }

            if (isset($inj_viol_violators) && !empty($inj_viol_violators)) {
                $insert_violators = Yii::$app->db->createCommand()->batchInsert('violator',
                    ['injunction_violation_id', 'worker_id'], $inj_viol_violators)
                    ->execute();
                if ($insert_violators != 0) {
                    $warnings[] = $method_name . '. Связка проверки иметса успешно добавлена';
                } else {
                    throw new Exception($method_name . '. При добавлении связки проверки и места произошла ошибка');
                }
            }
            /******************** СОХРАНЕНИЕ СТАТУСОВ НАРУШЕНИЙ НЕСООТВЕТСТВИЙ ********************/
            if (isset($inj_statuses) && !empty($inj_statuses)) {
                $insert_injunstion_statuses = Yii::$app->db->createCommand()
                    ->batchInsert('injunction_status', ['injunction_id', 'worker_id', 'status_id', 'date_time'], $inj_statuses)
                    ->execute();
                if ($insert_injunstion_statuses != 0) {
                    $warnings[] = 'AddInjunction. Статусы предписаний успешно добавлены';
                } else {
                    throw new Exception('AddInjunction. Ошибка при добалвении статусов предписаниям');
                }
            }
            /******************** СОХРАНЕНИЕ ИЗОБРАЖЕНИЙ НАРУШЕНИЯ НЕСООТВЕТСТВИЯ ********************/
            if (isset($new_injunction_images) && !empty($new_injunction_images)) {
                Yii::$app->db->createCommand()->batchInsert('injunction_img',
                    ['img_path', 'injunction_violation_id'],
                    $new_injunction_images)->execute();
            }
            /******************** СОХРАНЕНИЕ КОРРЕКТИРУЮЩИХ МЕРОПРИЯТИЙ ********************/
            if (isset($new_correct_measures) && !empty($new_correct_measures))                                                                                // Если массив с корректирующими мероприятиями сформирован, добавляем их в БД
            {

                Yii::$app->db->createCommand()->batchInsert('correct_measures',
                    ['injunction_violation_id', 'operation_id', 'date_time', 'status_id',
                        'correct_measures_value'],
                    $new_correct_measures)->execute();
            }
            $warnings[] = 'AddInjunction. Добавляются приостановки работ';
            /******************** СОХРАНЕНИЕ ПРИОСТАНОВОК РАБОТ ********************/
            if (isset($new_stop_pbs_equipment) && !empty($new_stop_pbs_equipment))                                                                                        // Если массив с корректирующими мероприятиями сформирован, добавляем их в БД
            {
                Yii::$app->db->createCommand()->batchInsert('stop_pb_equipment',
                    ['stop_pb_id', 'equipment_id'],
                    $new_stop_pbs_equipment)->execute();
            }
            $warnings[] = 'AddInjunction. Добавляются изображения нарушений';

        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $saving_nn;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetKindDocument() - Получение справочника видов документов
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,                            // идентификатор вида документа
     *      "title":"Предписание"                // наименование вида документа
     * ]
     * warnings:{}                              // массив предупреждений
     * errors:{}                                // массив ошибок
     * status:1                                 // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Injunction&method=GetKindDocument&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 24.03.2020 08:33
     */
    public static function GetKindDocument()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetKindDocument';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $kind_document = KindDocument::find()
                ->asArray()
                ->all();
            if (empty($kind_document)) {
                $warnings[] = $method_name . '. Справочник видов документов';
            } else {
                $result = $kind_document;
            }
        } catch (Throwable $exception) {
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
     * Метод SaveKindDocument() - Сохранение нового вида документа
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "kind_document":
     *  {
     *      "kind_document_id":-1,                                        // идентификатор вида документа (-1 = новый вид документа)
     *      "title":"KIND_DOCUMENT_TEST"                                // наименование вида документа
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "kind_document_id":5,                                        // идентификатор сохранённого вида документа
     *      "title":"KIND_DOCUMENT_TEST"                                // сохранённое наименование вида документа
     * }
     * warnings:{}                                                      // массив предупреждений
     * errors:{}                                                        // массив ошибок
     * status:1                                                         // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Injunction&method=SaveKindDocument&subscribe=&data={"kind_document":{"kind_document_id":-1,"title":"KIND_DOCUMENT_TEST"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 24.03.2020 08:37
     */
    public static function SaveKindDocument($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveKindDocument';
        $chat_type_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_document'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $kind_document_id = $post_dec->kind_document->kind_document_id;
            $title = $post_dec->kind_document->title;
            $kind_document = KindDocument::findOne(['id' => $kind_document_id]);
            if (empty($kind_document)) {
                $kind_document = new KindDocument();
            }
            $kind_document->title = $title;
            if ($kind_document->save()) {
                $kind_document->refresh();
                $chat_type_data['kind_document_id'] = $kind_document->id;
                $chat_type_data['title'] = $kind_document->title;
            } else {
                $errors[] = $kind_document->errors;
                throw new Exception($method_name . '. Ошибка при сохранении нового вида документа');
            }
            unset($kind_document);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $chat_type_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteKindDocument() - Удаление типа вида документа
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "kind_document_id": 5             // идентификатор вида документа
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Injunction&method=DeleteKindDocument&subscribe=&data={"kind_document_id":5}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 24.03.2020 08:47
     */
    public static function DeleteKindDocument($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteKindDocument';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_document_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $kind_document_id = $post_dec->kind_document_id;
            $del_kind_document = KindDocument::deleteAll(['id' => $kind_document_id]);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $post_dec;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetKindViolation() - Получение справочника видов нарушений
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,                                            // идентификатор вида нарушения
     *      "title":"Аэрогазовый контроль"                        // наименование вида нарушения
     *      "date_time_sync":"2018-10-05 00:00:00"                // дата и время синхронизации
     *      "ref_error_direction_id":"1"                        // идентификатор справочника нарушений (нужно для синхронизации)
     * ]
     * warnings:{}                                              // массив предупреждений
     * errors:{}                                                // массив ошибок
     * status:1                                                 // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Injunction&method=GetKindViolation&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 16:38
     */
    public static function GetKindViolation()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetKindMishap';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $kind_violation = KindViolation::find()
                ->asArray()
                ->all();
            if (empty($kind_violation)) {
                $warnings[] = $method_name . '. Справочник видов нарушений пуст';
            } else {
                $result = $kind_violation;
            }
        } catch (Throwable $exception) {
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
     * Метод SaveKindViolation() - Сохранение нового вида нарушения
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "kind_violation":
     *  {
     *      "kind_violation_id":-1,                                        // идентификатор вида нарушений (-1 =  новый вид нарушения)
     *      "title":"KIND_VIOLATION_TEST"                                // наименование вида нарушения
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "kind_violation_id":129,                                        // идентификатор сохранённого вида нарушения
     *      "title":"KIND_VIOLATION_TEST"                                // сохранённое наименование вида нарушения
     * }
     * warnings:{}                                                      // массив предупреждений
     * errors:{}                                                        // массив ошибок
     * status:1                                                         // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Injunction&method=SaveKindViolation&subscribe=&data={"kind_violation":{"kind_violation_id":-1,"title":"KIND_VIOLATION_TEST"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 16:53
     */
    public static function SaveKindViolation($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveKindViolation';
        $chat_type_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_violation'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $kind_violation_id = $post_dec->kind_violation->kind_violation_id;
            $title = $post_dec->kind_violation->title;
            $kind_violation = KindViolation::findOne(['id' => $kind_violation_id]);
            if (empty($kind_violation)) {
                $kind_violation = new KindViolation();
            }
            $kind_violation->title = $title;
            if ($kind_violation->save()) {
                $kind_violation->refresh();
                $chat_type_data['kind_violation_id'] = $kind_violation->id;
                $chat_type_data['title'] = $kind_violation->title;
            } else {
                $errors[] = $kind_violation->errors;
                throw new Exception($method_name . '. Ошибка при сохранении нового вида нарушения');
            }
            unset($kind_violation);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $chat_type_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteKindViolation() - Удаление вида нарушения
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "kind_violation_id": 129             // идентификатор удаляемого вида нарушения
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Injunction&method=DeleteKindViolation&subscribe=&data={"kind_violation_id":129}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 16:58
     */
    public static function DeleteKindViolation($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteKindViolation';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_violation_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $kind_violation_id = $post_dec->kind_violation_id;
            $del_kind_violation = KindViolation::deleteAll(['id' => $kind_violation_id]);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $post_dec;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetParagraphPb() - Получение справочника пунктов нормативных документов
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,                                // идентификатор пункта нормативного документа
     *      "text":"п.14",                            // пункт нормативного документа
     *      "document_id": "702"                    // идентификатор документа
     * ]
     * warnings:{}                                  // массив предупреждений
     * errors:{}                                    // массив ошибок
     * status:1                                     // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Injunction&method=GetParagraphPb&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.03.2020 11:31
     */
    public static function GetParagraphPb()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetParagraphPb';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $paragraph_pb = ParagraphPb::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($paragraph_pb)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник пунктов нормативных документов пуст';
            } else {
                $result = $paragraph_pb;
            }
        } catch (Throwable $exception) {
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
     * Метод SaveParagraphPb() - Сохранение нового пункта нормативного документа
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "paragraph_pb":
     *  {
     *      "paragraph_pb_id":-1,                                    // идентификатор пункта нормативного документа (-1 = при добавлении нового пункта нормативного документа)
     *      "text":"PARAGRAPH_PB_TEST",                                // пункт нормативного документа
     *      "document_id":"702"                                        // идентификатор документа
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "paragraph_pb_id":16696,                                // идентификатор сохранённого пункта нормативного документа
     *      "text":"PARAGRAPH_PB_TEST",                                // сохранённый пункт нормативного документа
     *      "document_id":"702"                                        // идентификатор документа
     * }
     * warnings:{}                                                  // массив предупреждений
     * errors:{}                                                    // массив ошибок
     * status:1                                                     // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Injunction&method=SaveParagraphPb&subscribe=&data={"paragraph_pb":{"paragraph_pb_id":-1,"text":"PARAGRAPH_PB_TEST","document_id":704}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.03.2020 11:33
     */
    public static function SaveParagraphPb($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveOutcome';
        $chat_type_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'paragraph_pb'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $paragraph_pb_id = $post_dec->paragraph_pb->paragraph_pb_id;
            $document_id = $post_dec->paragraph_pb->document_id;
            $text = $post_dec->paragraph_pb->text;
            $paragraph_pb = ParagraphPb::findOne(['id' => $paragraph_pb_id]);
            if (empty($paragraph_pb)) {
                $paragraph_pb = new ParagraphPb();
            }
            $paragraph_pb->text = $text;
            $paragraph_pb->document_id = $document_id;
            if ($paragraph_pb->save()) {
                $paragraph_pb->refresh();
                $chat_type_data['paragraph_pb_id'] = $paragraph_pb->id;
                $chat_type_data['document_id'] = $paragraph_pb->document_id;
                $chat_type_data['text'] = $paragraph_pb->text;
            } else {
                $errors[] = $paragraph_pb->errors;
                throw new Exception($method_name . '. Ошибка при сохранении пункта нормативного документа');
            }
            unset($paragraph_pb);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $chat_type_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteParagraphPb() - Удаление пункта нормативного документа
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "paragraph_pb_id": 16696             // идентификатор удаляемого пункта нормативного документа
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Injunction&method=DeleteParagraphPb&subscribe=&data={"paragraph_pb_id":16696}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.03.2020 11:38
     */
    public static function DeleteParagraphPb($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteOutcome';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'paragraph_pb_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $paragraph_pb_id = $post_dec->paragraph_pb_id;

            if (!InjunctionViolation::findOne(['paragraph_pb_id' => $paragraph_pb_id])) {
                $del_paragraph_pb = ParagraphPb::deleteAll(['id' => $paragraph_pb_id]);
            } else {
                throw new Exception('Удаление невозможно. Пункт используется в предписании');
            }
        } catch (Throwable $exception) {
//            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $post_dec;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    // GetIndustrialSafetyObject()      - Получение справочника объектов промышленной безопасности
    // SaveIndustrialSafetyObject()     - Сохранение справочника объектов промышленной безопасности
    // DeleteIndustrialSafetyObject()   - Удаление справочника объектов промышленной безопасности

    /**
     * Метод GetIndustrialSafetyObject() - Получение справочника объектов промышленной безопасности
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                                                // ключ справочника
     *      "title":"ACTION",                                       // название справочника
     *      "industrial_safety_object_type_id":"-1",                // Внешний идентификатор типа объекта ЭПБ
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Injunction&method=GetIndustrialSafetyObject&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetIndustrialSafetyObject()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetIndustrialSafetyObject';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_industrial_safety_object = IndustrialSafetyObject::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_industrial_safety_object)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник объектов промышленной безопасности пуст';
            } else {
                $result = $handbook_industrial_safety_object;
            }
        } catch (Throwable $exception) {
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
     * Метод SaveIndustrialSafetyObject() - Сохранение справочника объектов промышленной безопасности
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "industrial_safety_object":
     *  {
     *      "industrial_safety_object_id":-1,                       // ключ справочника
     *      "title":"ACTION",                                       // название справочника
     *      "industrial_safety_object_type_id":"-1",                // Внешний идентификатор типа объекта ЭПБ
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "industrial_safety_object_id":-1,                       // ключ справочника
     *      "title":"ACTION",                                       // название справочника
     *      "industrial_safety_object_type_id":"-1",                // Внешний идентификатор типа объекта ЭПБ
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Injunction&method=SaveIndustrialSafetyObject&subscribe=&data={"industrial_safety_object":{"industrial_safety_object_id":-1,"title":"ACTION","industrial_safety_object_type_id":"-1"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveIndustrialSafetyObject($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveIndustrialSafetyObject';
        $handbook_industrial_safety_object_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'industrial_safety_object'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_industrial_safety_object_id = $post_dec->industrial_safety_object->industrial_safety_object_id;
            $title = $post_dec->industrial_safety_object->title;
            $industrial_safety_object_type_id = $post_dec->industrial_safety_object->industrial_safety_object_type_id;
            $new_handbook_industrial_safety_object_id = IndustrialSafetyObject::findOne(['id' => $handbook_industrial_safety_object_id]);
            if (empty($new_handbook_industrial_safety_object_id)) {
                $new_handbook_industrial_safety_object_id = new IndustrialSafetyObject();
            }
            $new_handbook_industrial_safety_object_id->title = $title;
            $new_handbook_industrial_safety_object_id->industrial_safety_object_type_id = $industrial_safety_object_type_id;
            if ($new_handbook_industrial_safety_object_id->save()) {
                $new_handbook_industrial_safety_object_id->refresh();
                $handbook_industrial_safety_object_data['industrial_safety_object_id'] = $new_handbook_industrial_safety_object_id->id;
                $handbook_industrial_safety_object_data['title'] = $new_handbook_industrial_safety_object_id->title;
                $handbook_industrial_safety_object_data['industrial_safety_object_type_id'] = $new_handbook_industrial_safety_object_id->industrial_safety_object_type_id;
            } else {
                $errors[] = $new_handbook_industrial_safety_object_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника объектов промышленной безопасности');
            }
            unset($new_handbook_industrial_safety_object_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_industrial_safety_object_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteIndustrialSafetyObject() - Удаление справочника объектов промышленной безопасности
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "industrial_safety_object_id": 98             // идентификатор справочника объектов промышленной безопасности
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Injunction&method=DeleteIndustrialSafetyObject&subscribe=&data={"industrial_safety_object_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteIndustrialSafetyObject($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteIndustrialSafetyObject';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'industrial_safety_object_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_industrial_safety_object_id = $post_dec->industrial_safety_object_id;
            $del_handbook_industrial_safety_object = IndustrialSafetyObject::deleteAll(['id' => $handbook_industrial_safety_object_id]);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $post_dec;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetIndustrialSafetyObjectType()      - Получение справочника типов объектов промышленной безопасности
    // SaveIndustrialSafetyObjectType()     - Сохранение справочника типов объектов промышленной безопасности
    // DeleteIndustrialSafetyObjectType()   - Удаление справочника типов объектов промышленной безопасности

    /**
     * Метод GetIndustrialSafetyObjectType() - Получение справочника типов объектов промышленной безопасности
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                        // ключ справочника
     *      "title":"ACTION",                // название справочника
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Injunction&method=GetIndustrialSafetyObjectType&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetIndustrialSafetyObjectType()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetIndustrialSafetyObjectType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_industrial_safety_object_type = IndustrialSafetyObjectType::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_industrial_safety_object_type)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник типов объектов промышленной безопасности пуст';
            } else {
                $result = $handbook_industrial_safety_object_type;
            }
        } catch (Throwable $exception) {
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
     * Метод SaveIndustrialSafetyObjectType() - Сохранение справочника типов объектов промышленной безопасности
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "industrial_safety_object_type":
     *  {
     *      "industrial_safety_object_type_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "industrial_safety_object_type_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Injunction&method=SaveIndustrialSafetyObjectType&subscribe=&data={"industrial_safety_object_type":{"industrial_safety_object_type_id":-1,"title":"ACTION"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveIndustrialSafetyObjectType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveIndustrialSafetyObjectType';
        $handbook_industrial_safety_object_type_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'industrial_safety_object_type'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_industrial_safety_object_type_id = $post_dec->industrial_safety_object_type->industrial_safety_object_type_id;
            $title = $post_dec->industrial_safety_object_type->title;
            $new_handbook_industrial_safety_object_type_id = IndustrialSafetyObjectType::findOne(['id' => $handbook_industrial_safety_object_type_id]);
            if (empty($new_handbook_industrial_safety_object_type_id)) {
                $new_handbook_industrial_safety_object_type_id = new IndustrialSafetyObjectType();
            }
            $new_handbook_industrial_safety_object_type_id->title = $title;
            if ($new_handbook_industrial_safety_object_type_id->save()) {
                $new_handbook_industrial_safety_object_type_id->refresh();
                $handbook_industrial_safety_object_type_data['industrial_safety_object_type_id'] = $new_handbook_industrial_safety_object_type_id->id;
                $handbook_industrial_safety_object_type_data['title'] = $new_handbook_industrial_safety_object_type_id->title;
            } else {
                $errors[] = $new_handbook_industrial_safety_object_type_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника типов объектов промышленной безопасности');
            }
            unset($new_handbook_industrial_safety_object_type_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_industrial_safety_object_type_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteIndustrialSafetyObjectType() - Удаление справочника типов объектов промышленной безопасности
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "industrial_safety_object_type_id": 98             // идентификатор справочника типов объектов промышленной безопасности
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Injunction&method=DeleteIndustrialSafetyObjectType&subscribe=&data={"industrial_safety_object_type_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteIndustrialSafetyObjectType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteIndustrialSafetyObjectType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'industrial_safety_object_type_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_industrial_safety_object_type_id = $post_dec->industrial_safety_object_type_id;
            $del_handbook_industrial_safety_object_type = IndustrialSafetyObjectType::deleteAll(['id' => $handbook_industrial_safety_object_type_id]);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $post_dec;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetResearchIndex()      - Получение справочника параметров исследований
    // SaveResearchIndex()     - Сохранение справочника параметров исследований
    // DeleteResearchIndex()   - Удаление справочника параметров исследований

    /**
     * Метод GetResearchIndex() - Получение справочника параметров исследований
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                        // ключ справочника
     *      "title":"ACTION",                // название справочника
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Injunction&method=GetResearchIndex&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetResearchIndex()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetResearchIndex';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_research_index = ResearchIndex::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_research_index)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник параметров исследований пуст';
            } else {
                $result = $handbook_research_index;
            }
        } catch (Throwable $exception) {
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
     * Метод SaveResearchIndex() - Сохранение справочника параметров исследований
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "research_index":
     *  {
     *      "research_index_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     *      "research_type_id":"-1",        // ключ типа иссследований
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "research_index_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Injunction&method=SaveResearchIndex&subscribe=&data={"research_index":{"research_index_id":-1,"title":"ACTION","research_type_id":"-1"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveResearchIndex($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveResearchIndex';
        $handbook_research_index_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'research_index'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_research_index_id = $post_dec->research_index->research_index_id;
            $title = $post_dec->research_index->title;
            $research_type_id = $post_dec->research_index->research_type_id;
            $new_handbook_research_index_id = ResearchIndex::findOne(['id' => $handbook_research_index_id]);
            if (empty($new_handbook_research_index_id)) {
                $new_handbook_research_index_id = new ResearchIndex();
            }
            $new_handbook_research_index_id->title = $title;
            $new_handbook_research_index_id->research_type_id = $research_type_id;
            if ($new_handbook_research_index_id->save()) {
                $new_handbook_research_index_id->refresh();
                $handbook_research_index_data['research_index_id'] = $new_handbook_research_index_id->id;
                $handbook_research_index_data['title'] = $new_handbook_research_index_id->title;
                $handbook_research_index_data['research_type_id'] = $new_handbook_research_index_id->research_type_id;
            } else {
                $errors[] = $new_handbook_research_index_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника параметров исследований');
            }
            unset($new_handbook_research_index_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_research_index_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteResearchIndex() - Удаление справочника параметров исследований
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "research_index_id": 98             // идентификатор справочника параметров исследований
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Injunction&method=DeleteResearchIndex&subscribe=&data={"research_index_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteResearchIndex($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteResearchIndex';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'research_index_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_research_index_id = $post_dec->research_index_id;
            $del_handbook_research_index = ResearchIndex::deleteAll(['id' => $handbook_research_index_id]);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $post_dec;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetResearchType()      - Получение справочника типов исследований
    // SaveResearchType()     - Сохранение справочника типов исследований
    // DeleteResearchType()   - Удаление справочника типов исследований

    /**
     * Метод GetResearchType() - Получение справочника типов исследований
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                        // ключ справочника
     *      "title":"ACTION",                // название справочника
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Injunction&method=GetResearchType&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetResearchType()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetResearchType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_research_type = ResearchType::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_research_type)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник типов исследований пуст';
            } else {
                $result = $handbook_research_type;
            }
        } catch (Throwable $exception) {
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
     * Метод SaveResearchType() - Сохранение справочника типов исследований
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "research_type":
     *  {
     *      "research_type_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "research_type_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Injunction&method=SaveResearchType&subscribe=&data={"research_type":{"research_type_id":-1,"title":"ACTION"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveResearchType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveResearchType';
        $handbook_research_type_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'research_type'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_research_type_id = $post_dec->research_type->research_type_id;
            $title = $post_dec->research_type->title;
            $new_handbook_research_type_id = ResearchType::findOne(['id' => $handbook_research_type_id]);
            if (empty($new_handbook_research_type_id)) {
                $new_handbook_research_type_id = new ResearchType();
            }
            $new_handbook_research_type_id->title = $title;
            if ($new_handbook_research_type_id->save()) {
                $new_handbook_research_type_id->refresh();
                $handbook_research_type_data['research_type_id'] = $new_handbook_research_type_id->id;
                $handbook_research_type_data['title'] = $new_handbook_research_type_id->title;
            } else {
                $errors[] = $new_handbook_research_type_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника типов исследований');
            }
            unset($new_handbook_research_type_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_research_type_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteResearchType() - Удаление справочника типов исследований
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "research_type_id": 98             // идентификатор справочника типов исследований
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Injunction&method=DeleteResearchType&subscribe=&data={"research_type_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteResearchType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteResearchType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'research_type_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_research_type_id = $post_dec->research_type_id;
            $del_handbook_research_type = ResearchType::deleteAll(['id' => $handbook_research_type_id]);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $post_dec;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    // GetVidDocument()      - Получение справочника видов документов ПБ
    // SaveVidDocument()     - Сохранение справочника видов документов ПБ
    // DeleteVidDocument()   - Удаление справочника видов документов ПБ

    /**
     * Метод GetVidDocument() - Получение справочника видов документов ПБ
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                        // ключ справочника
     *      "title":"ACTION",                // название справочника
     *      "prefics":"ACTI",                // префикс документаа
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Injunction&method=GetVidDocument&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetVidDocument()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetVidDocument';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_vid_document = VidDocument::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_vid_document)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник видов документов ПБ пуст';
            } else {
                $result = $handbook_vid_document;
            }
        } catch (Throwable $exception) {
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
     * Метод SaveVidDocument() - Сохранение справочника видов документов ПБ
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "vid_document":
     *  {
     *      "vid_document_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     *      "prefics":"ACTI",                // префикс документаа
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "vid_document_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     *      "prefics":"ACTI",                // префикс документаа
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Injunction&method=SaveVidDocument&subscribe=&data={"vid_document":{"vid_document_id":-1,"title":"ACTION","prefics":"ACTI"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveVidDocument($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveVidDocument';
        $handbook_vid_document_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'vid_document'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_vid_document_id = $post_dec->vid_document->vid_document_id;
            $title = $post_dec->vid_document->title;
            $prefics = $post_dec->vid_document->prefics;
            $new_handbook_vid_document_id = VidDocument::findOne(['id' => $handbook_vid_document_id]);
            if (empty($new_handbook_vid_document_id)) {
                $new_handbook_vid_document_id = new VidDocument();
            }
            $new_handbook_vid_document_id->title = $title;
            $new_handbook_vid_document_id->prefics = $prefics;
            if ($new_handbook_vid_document_id->save()) {
                $new_handbook_vid_document_id->refresh();
                $handbook_vid_document_data['vid_document_id'] = $new_handbook_vid_document_id->id;
                $handbook_vid_document_data['title'] = $new_handbook_vid_document_id->title;
                $handbook_vid_document_data['prefics'] = $new_handbook_vid_document_id->prefics;
            } else {
                $errors[] = $new_handbook_vid_document_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника видов документов ПБ');
            }
            unset($new_handbook_vid_document_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_vid_document_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteVidDocument() - Удаление справочника видов документов ПБ
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "vid_document_id": 98             // идентификатор справочника видов документов ПБ
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Injunction&method=DeleteVidDocument&subscribe=&data={"vid_document_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteVidDocument($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $post_dec = null;                                                                                              // Массив ошибок
        $method_name = 'DeleteVidDocument';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'vid_document_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_vid_document_id = $post_dec->vid_document_id;

            if (!DocumentEventPb::findOne(['vid_document_id' => $handbook_vid_document_id])) {
                $del_handbook_vid_document = VidDocument::deleteAll(['id' => $handbook_vid_document_id]);
            } else {
                throw new Exception('Удаление невозможно. Есть связанные данные в Нормативных документах');
            }

        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        return array('Items' => $post_dec, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }



    // GetDocument()      - Получение справочника названий документов


    /**
     * Метод GetDocument() - Получение справочника названий документов
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                        // ключ документа
     *      "title":"ACTION",               // название документа
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Injunction&method=GetDocument&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetDocument()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetDocument';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_violation_type = Document::find()
                ->select('id,title')
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_violation_type)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник названий документов пуст';
            } else {
                $result = $handbook_violation_type;
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    // GetViolationType()      - Получение справочника направлений нарушений
    // SaveViolationType()     - Сохранение справочника направлений нарушений
    // DeleteViolationType()   - Удаление справочника направлений нарушений

    /**
     * Метод GetViolationType() - Получение справочника направлений нарушений
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                                // ключ типа направления нарушения
     *      "title":"ACTION",                       // название типа направления нарушения
     *      "kind_violation_id":"ACTION",           // название вид направления нарушения
     *      "date_time_sync":"ACTION",              // дата синхронизации
     *      "ref_error_direction_id":"ACTION",      // ключ направления нарушения из ППК ПАБ
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Injunction&method=GetViolationType&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetViolationType()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetViolationType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_violation_type = ViolationType::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_violation_type)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник направлений нарушений пуст';
            } else {
                $result = $handbook_violation_type;
            }
        } catch (Throwable $exception) {
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
     * Метод SaveViolationType() - Сохранение справочника направлений нарушений
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "violation_type":
     *  {
     *      "violation_type_id":-1,                 // ключ типа направления нарушений
     *      "title":"ACTION",                       // название типа направления нарушения
     *      "kind_violation_id":"ACTION",           // название вид направления нарушения
     *      "date_time_sync":"ACTION",              // дата синхронизации
     *      "ref_error_direction_id":"ACTION",      // ключ направления нарушения из ППК ПАБ
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "violation_type_id":-1,                 // ключ типа направления нарушений
     *      "title":"ACTION",                       // название типа направления нарушения
     *      "kind_violation_id":"ACTION",           // название вид направления нарушения
     *      "date_time_sync":"ACTION",              // дата синхронизации
     *      "ref_error_direction_id":"ACTION",      // ключ направления нарушения из ППК ПАБ
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Injunction&method=SaveViolationType&subscribe=&data={"violation_type":{"violation_type_id":-1,"title":"ACTION"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveViolationType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveViolationType';
        $handbook_violation_type_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'violation_type'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_violation_type_id = $post_dec->violation_type->violation_type_id;
            $title = $post_dec->violation_type->title;
            $kind_violation_id = $post_dec->violation_type->kind_violation_id;
            $date_time_sync = $post_dec->violation_type->date_time_sync;
            $ref_error_direction_id = $post_dec->violation_type->ref_error_direction_id;
            $new_handbook_violation_type_id = ViolationType::findOne(['id' => $handbook_violation_type_id]);
            if (empty($new_handbook_violation_type_id)) {
                $new_handbook_violation_type_id = new ViolationType();
            }
            $new_handbook_violation_type_id->title = $title;
            $new_handbook_violation_type_id->kind_violation_id = $kind_violation_id;
            $new_handbook_violation_type_id->date_time_sync = $date_time_sync;
            $new_handbook_violation_type_id->ref_error_direction_id = $ref_error_direction_id;
            if ($new_handbook_violation_type_id->save()) {
                $new_handbook_violation_type_id->refresh();
                $handbook_violation_type_data['violation_type_id'] = $new_handbook_violation_type_id->id;
                $handbook_violation_type_data['title'] = $new_handbook_violation_type_id->title;
                $handbook_violation_type_data['kind_violation_id'] = $new_handbook_violation_type_id->kind_violation_id;
                $handbook_violation_type_data['date_time_sync'] = $new_handbook_violation_type_id->date_time_sync;
                $handbook_violation_type_data['ref_error_direction_id'] = $new_handbook_violation_type_id->ref_error_direction_id;
            } else {
                $errors[] = $new_handbook_violation_type_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника направлений нарушений');
            }
            unset($new_handbook_violation_type_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';

        return array('Items' => $handbook_violation_type_data, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод DeleteViolationType() - Удаление справочника направлений нарушений
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "violation_type_id": 98             // идентификатор справочника направлений нарушений
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Injunction&method=DeleteViolationType&subscribe=&data={"violation_type_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteViolationType($data_post = NULL)
    {
        $status = 1;
        $result = null;
        $warnings = array();
        $errors = array();
        $method_name = 'DeleteViolationType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'violation_type_id')) {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_violation_type_id = $post_dec->violation_type_id;
            $del_handbook_violation_type = ViolationType::deleteAll(['id' => $handbook_violation_type_id]);
            $result = $post_dec;
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    /**
     * SaveStatusInjunctionFromOrder - метод сохранения статусов предписаний из наряда
     * @param $injunctions - список предписаний из наряда
     * @param $date_time_now - текущая дата
     * @return array
     */
    public static function SaveStatusInjunctionFromOrder($injunctions, $date_time_now = null): array
    {
        $log = new LogAmicumFront("SaveStatusInjunctionFromOrder");
        $result = null;

        try {
            $log->addLog("Начало выполнения метода");
            $session = Yii::$app->session;
            $worker_id = $session['worker_id'];

            if (!$date_time_now) {
                $date_time_now = Assistant::GetDateTimeNow();
            }

            foreach ($injunctions as $injunction) {
                $injunction_id = $injunction->injunction_id;
                // сохранили статус предписания
                $injunction_modal = Injunction::findOne(['id' => $injunction_id]);
                if ($injunction_modal) {
                    $injunction_modal->status_id = $injunction->injunction_status_id;
                    if (!$injunction_modal->save()) {
                        $log->addData($injunction_modal->errors, '$injunction_modal->errors', __LINE__);
                        throw new Exception('Ошибка сохранения статуса предписания в модели Injunction');
                    }
                }
                $injunction_statuses[] = array(
                    'injunction_id' => $injunction_id,
                    'worker_id' => $worker_id,
                    'status_id' => $injunction->injunction_status_id,
                    'date_time' => $date_time_now,
                );

                foreach ($injunction->correct_measure as $correct_measure_item) {
                    // сохранили статус предписаний - история
                    $correct_measure = CorrectMeasures::findOne(['id' => $correct_measure_item->correct_measure_id]);
                    if ($correct_measure) {
                        $correct_measure->status_id = $correct_measure_item->correct_measure_status_id;
                        if (!$correct_measure->save()) {
                            $log->addData($correct_measure->errors, '$correct_measure->errors', __LINE__);
                            throw new Exception('Ошибка сохранения модели статуса CorrectMeasures');
                        }
                        $correct_measure->refresh();
                        $injunction_violation_id = $correct_measure->injunction_violation_id;
                        unset($correct_measure);

                        $inj_viol_statuses[] = array(
                            'injunction_violation_id' => $injunction_violation_id,
                            'worker_id' => $worker_id,
                            'status_id' => $injunction->injunction_status_id,
                            'date_time' => $date_time_now,
                        );

                    }

                }
                unset($injunction_modal);
            }

            $log->addLog("Сохранил основные статусы");

            if (isset($injunction_statuses)) {
                $insert_injunstion_statuses = Yii::$app->db->createCommand()->batchInsert('injunction_status', ['injunction_id', 'worker_id', 'status_id', 'date_time'], $injunction_statuses)->execute();
                if (!$insert_injunstion_statuses) {
                    throw new Exception('Ошибка при добавлении статусов предписаниям');
                }
            }
            $log->addLog("Сохранил массово статусы предписаний");

            if (isset($inj_viol_statuses)) {
                $insert_injunstion_violation_statuses = Yii::$app->db->createCommand()->batchInsert('injunction_violation_status', ['injunction_violation_id', 'worker_id', 'status_id', 'date_time'], $inj_viol_statuses)->execute();
                if (!$insert_injunstion_violation_statuses) {
                    throw new Exception('Ошибка при добавлении статусов нарушений предписаниям');
                }
            }
            $log->addLog("Сохранил массово статусы нарушений предписаний");

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");
        return array_merge(['Items' => []], $log->getLogAll());
    }
}
