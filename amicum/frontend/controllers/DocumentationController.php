<?php

namespace frontend\controllers;

use backend\controllers\Assistant as BackendAssistant;
use frontend\models\Attachment;
use frontend\models\Document;
use frontend\models\DocumentAttachment;
use frontend\models\DocumentEventPb;
use frontend\models\DocumentEventPbAttachment;

class DocumentationController extends \yii\web\Controller
{

    // GetDocuments                     - Метод получения списка всех документов (document)
    // GetDocumentOTandPB               - Метод получения списка всех документов ОТ и ПБ (document_event_pb)
    // GetAttchments                    - Метод получения списка всех вложений (attachment)
    // DeleteDocument                   - Метод удаления документа
    // SaveDocument                     - Сохранение документа и вложения документа
    // DeleteDocumentOTandPB            - Удаление документа ОТ и ПБ
    // DeleteAttchment                  - Удаление вложения
    // ChangeAttchment                  - Метод редактирования вложения
    // DeleteAttachmentFromDocument     - Метод редактирования вложения

    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Метод () - Метод получения списка всех документов
     * @return array
     *
     * @package frontend\controllers
     *
     * @example http://amicum/read-manager-amicum?controller=Documentation&method=GetDocuments&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 30.12.2019 8:36
     */
    public static function GetDocuments()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetDocuments';
        $warnings[] = $method_name . '. Начало метода';
        try {
            /**
             * Выгружаем докуенты с вложениями и информацией о том кто создал
             */
            $documents = Document::find()
                ->joinWith('documentAttachments.attachment')
                ->joinWith('vidDocument')
                ->joinWith('worker.employee')
                ->joinWith('worker.position')
                ->orderBy('document.date_start DESC')
                ->all();
            /******************** Перебор документов ********************/
            foreach ($documents as $document) {
                $document_id = $document->id;
                $result[$document_id]['document_id'] = $document_id;
                $result[$document_id]['vid_document_id'] = $document->vid_document_id;
                $result[$document_id]['vid_document_title'] = $document->vidDocument->title;
                $result[$document_id]['document_title'] = $document->title;
                $result[$document_id]['number_document'] = $document->number_document;
                $result[$document_id]['jsondoc'] = $document->jsondoc;
                if ($document->worker) {
                    $name = mb_substr($document->worker->employee->first_name, 0, 1);                                         // обрезаем имя до одной буквы
                    if (!empty($document->worker->employee->patronymic)) {                                                  // если есть отчество то обрезаем его и добавляем к ФИО
                        $patronymic = mb_substr($document->worker->employee->patronymic, 0, 1);
                        $full_name = "{$document->worker->employee->last_name} {$name}. {$patronymic}.";
                    } else {
                        $full_name = "{$document->worker->employee->last_name} {$name}.";
                    }
                    $position_id = $document->worker->position_id;
                    $position_title = $document->worker->position->title;
                } else {
                    $full_name = "";
                    $position_id = -1;
                    $position_title = "";
                }
                $result[$document_id]['full_name'] = $full_name;
                $result[$document_id]['position_id'] = $position_id;
                $result[$document_id]['position_title'] = $position_title;
                $result[$document_id]['date_start'] = $document->date_start;
                $result[$document_id]['date_start_format'] = date('d.m.Y', strtotime($document->date_start));
                $result[$document_id]['date_end'] = $document->date_end;
                $result[$document_id]['date_end_format'] = date('d.m.Y', strtotime($document->date_end));
                $result[$document_id]['attachment'] = array();
                /******************** Перебор вложений в документы (по факту оно одно) ********************/
                foreach ($document->documentAttachments as $documentAttachment) {
                    $document_attachment_id = $documentAttachment->id;
                    $result[$document_id]['attachment']['document_attachment_id'] = $document_attachment_id;
                    $result[$document_id]['attachment']['attachment_id'] = $documentAttachment->attachment_id;
                    if ($documentAttachment->attachment) {
                        $result[$document_id]['attachment']['attachment_title'] = $documentAttachment->attachment->title;
                        $result[$document_id]['attachment']['attachment_type'] = $documentAttachment->attachment->attachment_type;
                        $result[$document_id]['attachment']['attachment_path'] = $documentAttachment->attachment->path;
                    } else {
                        $result[$document_id]['attachment']['attachment_title'] = "";
                        $result[$document_id]['attachment']['attachment_type'] = "";
                        $result[$document_id]['attachment']['attachment_path'] = "";
                    }

                }
                if (empty($result[$document_id]['attachment'])) {
                    $result[$document_id]['attachment'] = (object)array();
                }
                $result[$document_id]['note'] = $document->note;
                $result[$document_id]['full_name'] = $full_name;
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
     * Метод GetDocumentOTandPB() - Метод получения списка всех документов ОТ и ПБ
     * @return array
     *
     * @package frontend\controllers
     *
     * @example http://amicum/read-manager-amicum?controller=Documentation&method=GetDocumentOTandPB&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 30.12.2019 8:52
     */
    public static function GetDocumentOTandPB()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetDocumentOTandPB';
        $warnings[] = $method_name . '. Начало метода';
        try {
            /**
             * Выгружаем документы ОТ и ПБ с вложениями и информацией о создавшем
             */
            $documents_event_pb = DocumentEventPb::find()
                ->joinWith('vidDocument')
                ->joinWith('worker.employee')
                ->joinWith('worker.position')
                ->joinWith('lastStatus')
                ->joinWith('documentEventPbStatuses')
                ->joinWith('documentEventPbAttachments')
                ->all();
            /******************** Перебор документов ********************/
            foreach ($documents_event_pb as $document_event_pb) {
                $document_event_pb_id = $document_event_pb->id;
                $result['documents_event_pb'][$document_event_pb_id]['document_event_pb_id'] = $document_event_pb_id;
                $result['documents_event_pb'][$document_event_pb_id]['document_event_pb_title'] = $document_event_pb->title;
                $result['documents_event_pb'][$document_event_pb_id]['number_document'] = $document_event_pb->number_document;
                $result['documents_event_pb'][$document_event_pb_id]['jsondoc'] = $document_event_pb->jsondoc;
                $result['documents_event_pb'][$document_event_pb_id]['vid_document_id'] = $document_event_pb->vid_document_id;
                $result['documents_event_pb'][$document_event_pb_id]['vid_document_title'] = $document_event_pb->vidDocument->title;
                $result['documents_event_pb'][$document_event_pb_id]['attachments'] = array();
                /******************** Перебор вложений ********************/
                foreach ($document_event_pb->documentEventPbAttachments as $documentEventPbAttachment) {
                    $document_event_pb_attachment_id = $documentEventPbAttachment->id;
                    $result['documents_event_pb'][$document_event_pb_id]['attachments'][$document_event_pb_attachment_id]['document_event_pb_attachment_id'] = $document_event_pb_attachment_id;
                    $result['documents_event_pb'][$document_event_pb_id]['attachments'][$document_event_pb_attachment_id]['attachment_id'] = $documentEventPbAttachment->attachment_id;
                    if ($documentEventPbAttachment->attachment) {
                        $result['documents_event_pb'][$document_event_pb_id]['attachments'][$document_event_pb_attachment_id]['attachment_title'] = $documentEventPbAttachment->attachment->title;
                        $result['documents_event_pb'][$document_event_pb_id]['attachments'][$document_event_pb_attachment_id]['attachment_type'] = $documentEventPbAttachment->attachment->attachment_type;
                        $result['documents_event_pb'][$document_event_pb_id]['attachments'][$document_event_pb_attachment_id]['attachment_path'] = $documentEventPbAttachment->attachment->path;
                    } else {
                        $result['documents_event_pb'][$document_event_pb_id]['attachments'][$document_event_pb_attachment_id]['attachment_title'] = "";
                        $result['documents_event_pb'][$document_event_pb_id]['attachments'][$document_event_pb_attachment_id]['attachment_type'] = "";
                        $result['documents_event_pb'][$document_event_pb_id]['attachments'][$document_event_pb_attachment_id]['attachment_path'] = "";
                    }
                }
                if (empty($result['documents_event_pb'][$document_event_pb_id]['attachments'])) {
                    $result['documents_event_pb'][$document_event_pb_id]['attachments'] = (object)array();
                }

                /**
                 * Берём дату и время первого статуса
                 */
                if (isset($document_event_pb->documentEventPbStatuses) && !empty($document_event_pb->documentEventPbStatuses[0]->date_time_create)) {
                    $date_time_create = $document_event_pb->documentEventPbStatuses[0]->date_time_create;
                    $date_time_create_format = date('d.m.Y', strtotime($document_event_pb->documentEventPbStatuses[0]->date_time_create));
                } else {
                    $date_time_create = null;
                    $date_time_create_format = null;
                }

                $result['documents_event_pb'][$document_event_pb_id]['date_create'] = $date_time_create;
                $result['documents_event_pb'][$document_event_pb_id]['date_create_format'] = $date_time_create_format;
                /**
                 * Обрезаем имя до одной буквы
                 * Если есть отчество добавляем его в ФИО
                 */
                $name = mb_substr($document_event_pb->worker->employee->first_name, 0, 1);
                if (!empty($document_event_pb->worker->employee->patronymic)) {
                    $patronymic = mb_substr($document_event_pb->worker->employee->patronymic, 0, 1);
                    $full_name = "{$document_event_pb->worker->employee->last_name} {$name}. {$patronymic}.";
                } else {
                    $full_name = "{$document_event_pb->worker->employee->last_name} {$name}.";
                }
                $result['documents_event_pb'][$document_event_pb_id]['full_name'] = $full_name;
                $result['documents_event_pb'][$document_event_pb_id]['position_id'] = $document_event_pb->worker->position_id;
                $result['documents_event_pb'][$document_event_pb_id]['position_title'] = $document_event_pb->worker->position->title;
                $result['documents_event_pb'][$document_event_pb_id]['status_id'] = $document_event_pb->last_status_id;
                $result['documents_event_pb'][$document_event_pb_id]['status_title'] = $document_event_pb->lastStatus->title;
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
     * Метод GetAttchments() - Метод получения списка всех вложений
     * @return array
     *
     * @package frontend\controllers
     *
     * @example http://amicum/read-manager-amicum?controller=Documentation&method=GetAttchments&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 30.12.2019 9:25
     */
    public static function GetAttchments()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetAttchments';
        $warnings[] = $method_name . '. Начало метода';
        try {
            /**
             * Выгружаем вложения с информацией о создавшем
             */
            $attachments = Attachment::find()
                ->joinWith('worker.employee')
                ->joinWith('worker.position')
                ->all();
            foreach ($attachments as $attachment) {
                $attachment_id = $attachment->id;
                $result[$attachment_id]['attachment_id'] = $attachment->id;
                $result[$attachment_id]['attachment_title'] = $attachment->title;
                $result[$attachment_id]['attachment_path'] = $attachment->path;
                $result[$attachment_id]['attachment_type'] = $attachment->attachment_type;
                if (isset($attachment->date) && !empty($attachment->date)) {
                    $date = $attachment->date;
                    $date_format = date('d.m.Y', strtotime($attachment->date));
                } else {
                    $date = null;
                    $date_format = null;
                }
                $result[$attachment_id]['date'] = $date;
                $result[$attachment_id]['date_format'] = $date_format;
                /**
                 * Если работник заполнен то берём его данные, иначе null
                 */
                if (!empty($attachment->worker_id)) {
                    $name = mb_substr($attachment->worker->employee->first_name, 0, 1);
                    if (!empty($attachment->worker->employee->patronymic)) {
                        $patronymic = mb_substr($attachment->worker->employee->patronymic, 0, 1);
                        $full_name = "{$attachment->worker->employee->last_name} {$name}. {$patronymic}.";
                    } else {
                        $full_name = "{$attachment->worker->employee->last_name} {$name}.";
                    }
                    $position_id = $attachment->worker->position_id;
                    $position_title = $attachment->worker->position->title;
                } else {
                    $full_name = null;
                    $position_id = null;
                    $position_title = null;
                }
                $result[$attachment_id]['full_name'] = $full_name;
                $result[$attachment_id]['position_id'] = $position_id;
                $result[$attachment_id]['position_title'] = $position_title;
                $result[$attachment_id]['section_title'] = $attachment->section_title;

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
     * Метод SaveDocument() - Сохранение документа и вложения документа
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers
     *
     * @example http://amicum/read-manager-amicum?controller=GetAttchments&method=SaveDocument&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 30.12.2019 14:08
     */
    public static function SaveDocument($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveDocument';
        $result = array();
        $document_attachments = array();
        $warnings[] = $method_name . '. Начало метода';
        $session = \Yii::$app->session;
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'document_id') ||
                !property_exists($post_dec, 'document_title') ||
                !property_exists($post_dec, 'vid_document_id') ||
                !property_exists($post_dec, 'number_document') ||
                !property_exists($post_dec, 'jsondoc') ||
                !property_exists($post_dec, 'date_start') ||
                !property_exists($post_dec, 'date_end') ||
                !property_exists($post_dec, 'note') ||
                !property_exists($post_dec, 'attachment')
            ) {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $document_id = $post_dec->document_id;
            $document_title = $post_dec->document_title;
            $vid_document_id = $post_dec->vid_document_id;
            $number_document = $post_dec->number_document;
            $jsondoc = $post_dec->jsondoc;
            $date_start = date('Y-m-d', strtotime($post_dec->date_start));
            $date_end = date('Y-m-d', strtotime($post_dec->date_start));
            $note = $post_dec->note;
            $attachment = $post_dec->attachment;
            $document = Document::findOne(['id' => $document_id]);
            if (empty($document)) {
                $document = new Document();
            }
            $document->title = $document_title;
            $document->date_start = $date_start;
            $document->date_end = $date_end;
            $document->date_end = $date_end;
            $document->number_document = $number_document;
            $document->vid_document_id = $vid_document_id;
            $document->jsondoc = $jsondoc;
            $document->note = $note;
            $document->status_id = 1;
            $document->worker_id = $session['worker_id'];
            if ($document->save()) {
                $warnings[] = $method_name . '. Документ успешно сохранён';
                $document_id = $document->id;
                $post_dec->full_name = $session['userName'];
                $post_dec->document_id = $document_id;
            } else {
                $errors[] = $document->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении документа');
            }
            if (!empty($attachment)) {
                /**
                 * Если пришёл статус new - сохраняем вложение
                 *                    update - удаляем прошлое вложение и сохраняем новое
                 *                    del - удаляем вложение
                 */
                if ($attachment->attachment_status == 'new') {
                    if (empty($attachment->attachment_id)) {
                        $normalize_path = Assistant::UploadFile($attachment->attachment_path, $attachment->attachment_title, 'attachment', $attachment->attachment_type);
                        $add_attachment = new Attachment();
                        $add_attachment->title = $attachment->attachment_title;
                        $add_attachment->path = $normalize_path;
                        $add_attachment->attachment_type = $attachment->attachment_type;
                        $add_attachment->date = BackendAssistant::GetDateFormatYMD();
                        $add_attachment->worker_id = $session['worker_id'];
                        $add_attachment->section_title = 'Документация';
                        if ($add_attachment->save()) {
                            $warnings[] = $method_name . '. Вложения успешно сохранено';
                            $add_attachment->refresh();
                            $attachment_id = $add_attachment->id;
                            $attachment->attachment_id = $add_attachment->id;
                            $post_dec->attachment->attachment_path = $normalize_path;
                            $post_dec->attachment->attachment_id = $add_attachment->id;
                        } else {
                            $errors[] = $add_attachment->errors;
                            throw new \Exception($method_name . '. Ошибка при сохранении вложения');
                        }
                    }
                    $document_attachment = new DocumentAttachment();
                    $document_attachment->attachment_id = $attachment_id;
                    $document_attachment->document_id = $document_id;
                    if ($document_attachment->save()) {
                        $warnings[] = $method_name . '. Связка документа и вложения успешно сохраненна';
                        $document_attachment->refresh();
                        $post_dec->document_attachment_id = $document_attachment->id;
                    } else {
                        $errors[] = $document_attachment->errors;
                        throw new \Exception($method_name . '. Ошибка при сохранении свзяки документа и вложения');
                    }
                } elseif ($attachment->attachment_status == 'update') {
                    $del = DocumentAttachment::deleteAll(['id' => $attachment->document_attachment_id]);
                    $normalize_path = Assistant::UploadFile($attachment->attachment_path, $attachment->attachment_title, 'attachment', $attachment->attachment_type);
                    $add_attachment = new Attachment();
                    $add_attachment->title = $attachment->attachment_title;
                    $add_attachment->path = $normalize_path;
                    $add_attachment->attachment_type = $attachment->attachment_type;
                    $add_attachment->date = BackendAssistant::GetDateFormatYMD();
                    $add_attachment->worker_id = $session['worker_id'];
                    $add_attachment->section_title = 'Документация';
                    if ($add_attachment->save()) {
                        $warnings[] = $method_name . '. Вложения успешно сохранено';
                        $add_attachment->refresh();
                        $attachment_id = $add_attachment->id;
                        $attachment->attachment_id = $add_attachment->id;
                        $post_dec->attachment->attachment_path = $normalize_path;
                        $post_dec->attachment->attachment_id = $add_attachment->id;
                    } else {
                        $errors[] = $add_attachment->errors;
                        throw new \Exception($method_name . '. Ошибка при сохранении вложения');
                    }
                    $document_attachment = new DocumentAttachment();
                    $document_attachment->attachment_id = $attachment_id;
                    $document_attachment->document_id = $document_id;
                    if ($document_attachment->save()) {
                        $warnings[] = $method_name . '. Связка документа и вложения успешно сохраненна';
                        $document_attachment->refresh();
                        $post_dec->document_attachment_id = $document_attachment->id;
                    } else {
                        $errors[] = $document_attachment->errors;
                        throw new \Exception($method_name . '. Ошибка при сохранении свзяки документа и вложения');
                    }
                } elseif ($attachment->attachment_status == 'del') {
                    $del = DocumentAttachment::deleteAll(['id' => $attachment->document_attachment_id]);
                }
            }
//            if (!empty($document_attachments)){
//                $document_attachments_inserted = \Yii::$app->db->createCommand()
//                    ->batchInsert('document_attachment',['document_id','attachment_id'],$document_attachments)
//                    ->execute();
//                if ($document_attachments_inserted != 0){
//                    $warnings[] = $method_name.'. Связка вложений и документов успешно установлена';
//                }else{
//                    throw new \Exception($method_name . '. Ошика при сохранении связки вложений и документов');
//                }
//            }
        } catch (\Throwable $exception) {
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
     * Метод DeleteDocument() - Метод удаления документа
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers
     *
     * @example http://amicum/read-manager-amicum?controller=Documentation&method=DeleteDocument&subscribe=&data={"document_id":20080}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 30.12.2019 14:29
     */
    public static function DeleteDocument($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteDocument';
        $result = array();
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'document_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $document_id = $post_dec->document_id;
            $del_document = Document::deleteAll(['id' => $document_id]);
        } catch (\Throwable $exception) {
            $error_code = $exception->getCode();
            if ($error_code == 23000) {
                $errors[] = $method_name . ". Удаление документа не возможно. Данный документ используется.";
            } else {
                $errors[] = $method_name . '. Исключение';
                $errors[] = $exception->getMessage();
                $errors[] = $exception->getLine();
            }
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteDocumentOTandPB() - Удаление документа ОТ и ПБ
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers
     *
     * @example http://amicum/read-manager-amicum?controller=Documentation&method=DeleteDocumentOTandPB&subscribe=&data={"document_event_pb_id":20080}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 30.12.2019 14:38
     */
    public static function DeleteDocumentOTandPB($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteDocument';
        $result = array();
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'document_event_pb_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $document_event_pb_id = $post_dec->document_event_pb_id;
            $delete_doc_attachment = DocumentEventPbAttachment::deleteAll(['document_event_pb_id' => $document_event_pb_id]);
            $del_document = DocumentEventPb::deleteAll(['id' => $document_event_pb_id]);
        } catch (\Throwable $exception) {
            $error_code = $exception->getCode();
            if ($error_code == 23000) {
                $errors[] = $method_name . ". Удаление документа не возможно. Данный документ используется.";
            } else {
                $errors[] = $method_name . '. Исключение';
                $errors[] = $exception->getMessage();
                $errors[] = $exception->getLine();
            }
        }
        $warnings[] = $method_name . '. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteAttchment() - Удаление вложения
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers
     *
     * @example http://amicum/read-manager-amicum?controller=Documentation&method=DeleteAttchment&subscribe=&data={"attachment_id":20080}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 30.12.2019 14:40
     */
    public static function DeleteAttachment($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteAttachment';
        $result = array();
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'attachment_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $attachment_id = $post_dec->attachment_id;
            $document = DocumentAttachment::findOne(['attachment_id' => $attachment_id]);
            $document_event_pb = DocumentEventPbAttachment::findOne(['attachment_id' => $attachment_id]);
            if (empty($document) && empty($document_event_pb)) {
                $del_attachment = Attachment::deleteAll(['id' => $attachment_id]);
            } else {
                $status = 0;
                if (!empty($document)) {
                    $errors[] = 'Документ используется в документе: ' . $document->document->title;
                } elseif (!empty($document_event_pb)) {
                    $errors[] = 'Документ используется в документе: ' . $document_event_pb->documentEventPb->title;
                }
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
     * Метод ChangeAttchment() - Метод редактирования вложения
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers
     *
     * Входные обязательные параметры:
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 30.12.2019 14:46
     */
    public static function ChangeAttchment($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'ChangeAttchment';
        $result = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'attachment_id') ||
                !property_exists($post_dec, 'attachment_status') ||
                !property_exists($post_dec, 'attachment_path') ||
                !property_exists($post_dec, 'attachment_type') ||
                !property_exists($post_dec, 'attachment_title'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $attachment_id = $post_dec->attachment_id;
            $attachment_status = $post_dec->attachment_status;
            $attachment_title = $post_dec->attachment_title;
            $attachment_type = $post_dec->attachment_type;
            $attachment_path = $post_dec->attachment_path;
            $attachment = Attachment::findOne(['id' => $attachment_id]);
            if (!empty($attachment)) {
                $attachment->title = $attachment_title;
                /**
                 * Если пришёл статус change - значит поменяли файл, и сохраняем новый файл
                 */
                if ($attachment_status == 'change') {
                    $nomalize_path = Assistant::UploadFile($attachment_path, $attachment_title, 'attachment', $attachment_type);
                    $attachment->path = $nomalize_path;
                    $attachment->attachment_type = $attachment_type;
                    $attachment->title = $attachment_title;
                }
                if ($attachment->save()) {
                    $warnings[] = $method_name . '. Вложение успешно изменено';
                    $attachment->refresh();
                    $post_dec->attachment_id = $attachment->id;
                    $post_dec->attachment_path = $attachment->path;
                } else {
                    $errors[] = $attachment->errors;
                    throw new \Exception($method_name . '. Ошибка при изменении вложения');
                }
            }
        } catch (\Throwable $exception) {
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
     * Метод DeleteAttachmentFromDocument() - Удаление вложения в документах ОТ и ПБ
     * @param null $data_post - json с идентификатором связки которую необходимо удалить
     * @return array
     *
     * @package frontend\controllers
     *
     * @example http://amicum/read-manager-amicum?controller=Documentation&method=DeleteAttachmentFromDocumentEventPb&subscribe=&data={"document_event_pb_attachment_id":5}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 28.01.2020 15:57
     */
    public static function DeleteAttachmentFromDocumentEventPb($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteAttachmentFromDocumentEventPb';
        $result = array();
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'document_event_pb_attachment_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $document_event_pb_attachment_id = $post_dec->document_event_pb_attachment_id;
            $del = DocumentEventPbAttachment::deleteAll(['id' => $document_event_pb_attachment_id]);                      //удаляем связку вложения и документа по идентификатору связки
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
}
