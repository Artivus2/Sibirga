<?php
/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\controllers\handbooks;
//ob_start();

//классы и контроллеры yii2
use backend\controllers\Assistant as BackendAssistant;
use frontend\controllers\Assistant;
use frontend\models\Attachment;
use frontend\models\OrderPlace;
use frontend\models\OrderTemplatePlace;
use frontend\models\Passport;
use frontend\models\PassportAttachment;
use frontend\models\PassportOperation;
use frontend\models\PassportOperationMaterial;
use frontend\models\PassportParameter;
use frontend\models\PassportSection;
use Yii;
use yii\db\Exception;
use yii\web\Controller;

//модели без БД


class HandbookPassportController extends Controller
{

    // GetPassports             - получить список паспортов
    // GetPassport              - получить паспорт
    // SavePassport             - метод сохранения/редактирования паспорта в бд
    // deletePassport           - метод удаления паспорта из бд

    // GetPlacePassports - получить список мест и пасспортов в них

    // GetPassports - получить список паспорт
    //      [id]
    //              id              - ключ паспорта
    //              title           - наименование паспорта
    //              place_id        - ключ места
    //              place_title     - наименование места
    // пример: http://127.0.0.1/read-manager-amicum?controller=handbooks\HandbookPassport&method=GetPassports&subscribe=&data={}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function GetPassports($data_post = NULL)
    {
        $status = 1;                                                                                                 // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();                                                                                                // Массив ошибок

        try {
            $passport_list = Passport::find()
                ->joinWith('place')
                ->limit(20000)
                ->asArray()
                ->all();

            foreach ($passport_list as $passport) {
                $result[$passport['id']]['id'] = $passport['id'];
                $result[$passport['id']]['title'] = $passport['title'];
                if ($passport['place']) {
                    $result[$passport['id']]['place_id'] = $passport['place']['id'];
                    $result[$passport['id']]['place_title'] = $passport['place']['title'];
                } else {
                    $result[$passport['id']]['place_id'] = null;
                    $result[$passport['id']]['place_title'] = "";
                }
            }

            if (!$passport_list) {
                $warnings[] = 'GetPassports. Справочник паспортов';
                $result = (object)array();
            }
        } catch (\Throwable $exception) {
            $warnings[] = 'GetPassports. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetPlacePassports - получить список мест и пасспортов в них
    //      [place_id]
    //              place_id                - ключ места
    //              passport_title          - наименование паспорта
    //              passport_id             - ключ паспорта

    // пример: http://127.0.0.1/read-manager-amicum?controller=handbooks\HandbookPassport&method=GetPlacePassports&subscribe=&data={}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function GetPlacePassports($data_post = NULL)
    {
        $status = 1;                                                                                                 // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();                                                                                                // Массив ошибок

        try {
            $passport_list = Passport::find()
                ->joinWith('place')
                ->joinWith('passportAttachments.attachment')
                ->limit(20000)
                ->asArray()
                ->all();

            foreach ($passport_list as $passport) {
                if ($passport['place']) {
                    $place_id = $passport['place']['id'];
                    $result[$place_id]['place_id'] = $place_id;
                    $result[$place_id]['passport_id'] = $passport['id'];
                    $result[$place_id]['passport_title'] = $passport['title'];

                    $result[$place_id]['passport_attachments'] = array();
                    if (isset($passport['passportAttachments'])) {
                        foreach ($passport['passportAttachments'] as $passport_attachment) {
                            if ($passport_attachment['attachment']) {
                                $result[$place_id]['passport_attachments'][] = array(
                                    'passport_attachment_id' => $passport_attachment['id'],
                                    'attachment_path' => $passport_attachment['attachment']['path'],
                                    'attachment_title' => $passport_attachment['attachment']['title'],
                                );
                            }
                        }
                    }
                }
            }

            if (!$passport_list) {
                $warnings[] = 'GetPlacePassports. Справочник мест паспортов';
                $result = (object)array();
            }
        } catch (\Throwable $exception) {
            $warnings[] = 'GetPlacePassports. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // deletePassport - метод удаления паспорта из бд
    // входные параметры:
    //  passport_id - ключ паспорта
    // выходной массив:
    //  стандартный набор данных
    // http://127.0.0.1/read-manager-amicum?controller=handbooks\HandbookPassport&method=deletePassport&subscribe=login&data={"passport_id":"1"}
    //
    public static function deletePassport($data_post = null)
    {
        $method_name = "deletePassport";                                                                                             //уникальный идентификатор сессии
        $status = 1;                                                                                                      //флаг успешного выполнения метода
        $warnings = array();                                                                                              // массив предупреждений
        $errors = array();                                                                                                // массив ошибок
        $result = array();                                                                                                // промежуточный результирующий массив
        try {
            if (!is_null($data_post) and $data_post != "") {
                $warnings[] = "deletePassport. данные успешно переданы";
                $warnings[] = "deletePassport. Входной массив данных" . $data_post;
            } else {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }

            $data_post = json_decode($data_post);                                                                      //декодируем входной массив данных
            $warnings[] = "deletePassport. декодировал входные параметры";
            if (!property_exists($data_post, 'passport_id')) {
                throw new \Exception($method_name . '.  Ошибка в наименование параметра во входных данных');
            }

            $warnings[] = "deletePassport. Проверил входные данные";
            $passport_id = $data_post->passport_id;

            if (
                OrderPlace::findOne(['passport_id' => $passport_id]) or
                OrderTemplatePlace::findOne(['passport_id' => $passport_id])
            ) {
                throw new Exception("Паспорт используется в системе, удаление невозможно.");
            }

            $result = Passport::deleteAll(['id' => $passport_id]);

        } catch (\Exception $e) {
            $status = 0;
            $errors[] = $e->getMessage();
        }


        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    // SavePassport             - метод сохранения/редактирования паспорта в бд
    // входные параметры:
    //      passport - объект паспорта
    // выходной массив:
    //              passport_id             -   ключ паспорта
    //              passport_title          -   наименование паспорта
    //              place_id	            -   ключ места
    //              place_title	            -   наименование места
    //              passport_sections       -   вложения паспорта
    //                  {passport_section_id}
    //                      passport_section_id     -   ключ раздела паспорта
    //                      passport_section_title	-   наименование раздела паспорта
    //                      attachments
    //                          {passport_attachment_id}
    //                                  passport_attachment_id          - ключ вложения паспорта
    //                                  passport_document_title         - название пасспорта вложений
    //                                  attachment_id	                - ключ вложения
    //                                  attachment_path	                - путь вложения
    //                                  attachment_title                - наименование вложения
    //                                  attachment_type                 - тип вложения
    //                                  attachment_status               - статус вложения (del - удалить)
    //                                  attachment_blob                 - вложение блоб
    //                                  attachment_sketch               - эскиз вложения
    //              parameters              -   список параметров паспорта
    //                  {passport_parameter_id}
    //                      passport_parameter_id       - ключ параметра паспорта
    //                      parameter_id                - ключ параметра
    //                      parameter_title             - наименование параметра
    //                      unit_id                     - ключ единицы измерения
    //                      unit_title                  - наименование единицы измерения
    //                      short_title                 - сокращенное наименование единицы измерения
    //                      value                       - значение параметра паспорта
    //              shifts	                -   смены паспорта (график)
    //                  {shift_id}
    //                      shift_id            - ключ смены
    //                      operations	        - список операций на смене
    //                          {passport_operation_id}
    //                                  passport_operation_id           -   ключ операции паспорта
    //                                  parrent_passport_operation_id   -   ключ вышестоящей операции паспорта
    //                                  plan_value	                    -   плановое значение
    //                                  date_time_start	                -   время начала операции
    //                                  date_time_end	                -   время окончания операции
    //                                  operation_id                    -   ключ операции
    //                                  operation_title                 -   наименование операции
    //                                  line_in_grafic                  -   позиция в графике - для сортировки и перетаскивания
    //                                  materials	                    -   список материала
    //                                      {passport_operation_material_id}
    //                                              passport_operation_material_id      -   ключ конкретного материала конкретной операции
    //                                              material_id                         -   ключ материала
    //                                              value                               -   количество материала
    //                                              unit_id                             -   ключ единицы измерения материала
    //                                              nomenclature_id                     -   ключ номенклатуры
    //                                              nomenclature_title                  -   наименование номенклатуры
    //  стандартный набор данных
    // http://127.0.0.1/read-manager-amicum?controller=handbooks\HandbookPassport&method=SavePassport&subscribe=login&data={"passport":{}}
    //
    public static function SavePassport($data_post = null)
    {
        $method_name = "SavePassport";                                                                                             //уникальный идентификатор сессии
        $status = 1;                                                                                                      //флаг успешного выполнения метода
        $warnings = array();                                                                                              // массив предупреждений
        $errors = array();                                                                                                // массив ошибок
        $result = array();                                                                                                // промежуточный результирующий массив
        $session = Yii::$app->session;
        try {
            //$data_post = "{\"passport\":{\"passport_id\":\"1\",\"passport_title\":\"Паспорт 1\",\"place_id\":\"6181\",\"place_title\":\"Порож. уг. ветвь ск. ств. 3 гор.\",\"passport_sections\":{\"10\":{\"passport_section_id\":\"10\",\"passport_section_title\":\"ГРАФИЧЕСКАЯ ЧАСТЬ\",\"attachments\":{\"2\":{\"passport_attachment_id\":\"2\",\"attachment_id\":\"9331\",\"attachment_path\":\"/img/attachment/26-02-2020 04-30-19.1582680619_Инструкция по электроснабжению  в проветриваемых ВМП тупиковых выработках шахт, опасных по газу.docx\",\"attachment_title\":\"Инструкция по электроснабжению  в проветриваемых ВМП тупиковых выработках шахт, опасных по газу.docx\",\"attachment_type\":null,\"attachment_sketch\":null,\"attachment_blob\":\"\"}}},\"20\":{\"passport_section_id\":\"20\",\"passport_section_title\":\"Горно-геологический прогноз\"},\"30\":{\"passport_section_id\":\"30\",\"passport_section_title\":\"Проведение, крепление и ремонт подготовительный выработок\"},\"40\":{\"passport_section_id\":\"40\",\"passport_section_title\":\"Выемка угля, крепление и управление кровлей в очистном забое\"},\"50\":{\"passport_section_id\":\"50\",\"passport_section_title\":\"Мероприятия по охране труда и безопасности работ\"},\"60\":{\"passport_section_id\":\"60\",\"passport_section_title\":\"Энергоснабжение\"},\"70\":{\"passport_section_id\":\"70\",\"passport_section_title\":\"Транспортирование угля, породы, материалов, оборудования и перевозка людей\"},\"80\":{\"passport_section_id\":\"80\",\"passport_section_title\":\"ПОЯСНИТЕЛЬНАЯ ЗАПИСКА\"}},\"parameters\":{\"1\":{\"passport_parameter_id\":\"1\",\"parameter_id\":\"1\",\"parameter_title\":\"Рост\",\"unit_id\":\"1\",\"unit_title\":\"Сантиметры\",\"short_title\":\"см\",\"value\":\"1\"},\"2\":{\"passport_parameter_id\":\"2\",\"parameter_id\":\"2\",\"parameter_title\":\"Номер пропуска\",\"unit_id\":\"79\",\"unit_title\":\"-\",\"short_title\":\"-\",\"value\":\"2\"},\"3\":{\"passport_parameter_id\":\"3\",\"parameter_id\":\"3\",\"parameter_title\":\"Фотография\",\"unit_id\":\"79\",\"unit_title\":\"-\",\"short_title\":\"-\",\"value\":\"3\"}},\"shifts\":{\"1\":{\"shift_id\":\"1\",\"operations\":{\"1\":{\"passport_operation_id\":\"1\",\"parrent_passport_operation_id\":null,\"plan_value\":\"5\",\"line_in_grafic\":null,\"date_time_start\":\"2020-03-20 14:05:00\",\"date_time_end\":\"2020-03-20 15:05:00\",\"operation_id\":\"1\",\"operation_title\":\"Проведение и крепление выработки по паспорту, пог. м\",\"materials\":{\"1\":{\"passport_operation_material_id\":\"1\",\"material_id\":\"1\",\"value\":\"1\",\"unit_id\":\"9\",\"nomenclature_id\":\"1\",\"nomenclature_title\":\"Анкер болтовой\"},\"2\":{\"passport_operation_material_id\":\"2\",\"material_id\":\"2\",\"value\":\"2\",\"unit_id\":\"94\",\"nomenclature_id\":\"2\",\"nomenclature_title\":\"Сетка полимерная\"}}},\"2\":{\"passport_operation_id\":\"2\",\"parrent_passport_operation_id\":\"1\",\"plan_value\":\"5\",\"line_in_grafic\":null,\"date_time_start\":\"2020-03-20 16:05:00\",\"date_time_end\":\"2020-03-20 17:05:00\",\"operation_id\":\"1\",\"operation_title\":\"Проведение и крепление выработки по паспорту, пог. м\",\"materials\":{}}}}}}}";
            if (!is_null($data_post) and $data_post != "") {
                $warnings[] = $method_name . ". данные успешно переданы";
                $warnings[] = $method_name . ". Входной массив данных" . $data_post;
            } else {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }

            $passport = json_decode($data_post);                                                                      //декодируем входной массив данных
            $data_post = json_decode($data_post);                                                                      //декодируем входной массив данных
            $warnings[] = $method_name . ". декодировал входные параметры";
            if (!property_exists($data_post, 'passport')) {
                throw new \Exception($method_name . '.  Ошибка в наименование параметра во входных данных');
            }

            $warnings[] = $method_name . ". Проверил входные данные";
            $passport = $passport->passport;

            // сохраняем паспорт
            $save_passport = Passport::findOne(['id' => $passport->passport_id]);
            if (!$save_passport) {
                $save_passport = new Passport();
            }

            $save_passport->title = $passport->passport_title;
            $save_passport->place_id = $passport->place_id;

            if (!$save_passport->save()) {
                $errors[] = $save_passport->errors;
                throw new \Exception($method_name . '.  Ошибка сохранения модели Passport');
            }

            $save_passport->refresh();
            $passport_id = $save_passport->id;
            $data_post->passport->passport_id = $save_passport->id;

            // сохраняем параметры паспорта
            PassportParameter::deleteAll(['passport_id' => $passport->passport_id]);
            foreach ($passport->parameters as $key_param => $parameter) {
                if (!$parameter->value) {
                    $parameter->value = "0";
                }
                $passport_parameter = new PassportParameter();
                $passport_parameter->passport_id = $passport_id;
                $passport_parameter->parameter_id = $parameter->parameter_id;
                $passport_parameter->value = $parameter->value;
                if (!$passport_parameter->save()) {
                    $errors[] = $passport_parameter->errors;
                    throw new \Exception($method_name . '.  Ошибка сохранения модели PassportParameter');
                }
                $passport_parameter->refresh();
                $data_post->passport->parameters->{$key_param}->passport_parameter_id = $passport_parameter->id;
            }

            // сохраняем планограмму
            PassportOperation::deleteAll(['passport_id' => $passport->passport_id]);
            foreach ($passport->shifts as $key_shift => $shift) {
                foreach ($shift->operations as $key_operation => $operation) {
                    $passport_operation = new PassportOperation();
                    $passport_operation->passport_id = $passport_id;
                    $passport_operation->operation_id = $operation->operation_id;
                    $passport_operation->shift_id = $shift->shift_id;
                    if ($operation->date_time_start) {
                        $passport_operation->date_time_start = date("Y-m-d H:i:s", strtotime($operation->date_time_start));
                    }
                    if ($operation->date_time_end) {
                        $passport_operation->date_time_end = date("Y-m-d H:i:s", strtotime($operation->date_time_end));
                    }
                    $passport_operation->plan_value = $operation->plan_value;
                    $passport_operation->passport_operation_id = $operation->parrent_passport_operation_id;
                    $passport_operation->line_in_grafic = $operation->line_in_grafic;
                    if (!$passport_operation->save()) {
                        $errors[] = $passport_operation->errors;
                        throw new \Exception($method_name . '.  Ошибка сохранения модели PassportOperation');
                    }
                    $passport_operation->refresh();
                    $passport_operation_id = $passport_operation->id;
                    $data_post->passport->shifts->{$key_shift}->operations->{$key_operation}->passport_operation_id = $passport_operation->id;

                    foreach ($operation->materials as $key_material => $material) {
                        $passport_material = new PassportOperationMaterial();
                        $passport_material->passport_operation_id = $passport_operation_id;
                        $passport_material->material_id = $material->material_id;
                        $passport_material->value = $material->value;
                        if (!$passport_material->save()) {
                            $errors[] = $passport_material->errors;
                            throw new \Exception($method_name . '.  Ошибка сохранения модели PassportOperationMaterial');
                        }
                        $passport_material->refresh();
                        $data_post->passport->shifts->{$key_shift}->operations->{$key_operation}->materials->{$key_material}->passport_operation_material_id = $passport_material->id;
                    }
                }
            }

            foreach ($passport->passport_sections as $key_section => $passport_section) {
                if (property_exists($passport_section, "attachments")) {
                    foreach ($passport_section->attachments as $key_attach => $passport_attachment) {
                        // проверяем статус вложения на удаление или на добавление
                        if (isset($passport_attachment->attachment_status) && $passport_attachment->attachment_status === "del") {
                            //$delete_passport_attachment = Yii::$app->db->createCommand()->delete('passport_attachment', 'id=' . $passport_attachment->passport_attachment_id)->execute();
                            $delete_passport_attachment = PassportAttachment::deleteAll(['id' => $passport_attachment->passport_attachment_id]);
//                            unset($passport_section->attachments[$key_attach]);
                            unset($data_post->passport->passport_sections->{$key_section}->{"attachments"}->{$key_attach});
                            $warnings[] = $method_name . ". Удалил связку вложения $passport_attachment->passport_attachment_id. Количество " . $delete_passport_attachment;
                        } else {
                            /**
                             * сохраняем вложение документа в таблицу Attachment
                             **/
                            $docum_attachment_id = $passport_attachment->attachment_id;
                            $passport_document_title = $passport_attachment->passport_document_title;
                            $new_docum_attachment = Attachment::findOne(['id' => $docum_attachment_id]);
                            $warnings[] = $method_name . '. объект на сохранение:';
//                            $warnings[] = $passport_attachment;
                            if (
                                isset($passport_attachment->attachment_status) &&
                                ($passport_attachment->attachment_status === "new" or $passport_attachment->attachment_status === "updateAttachment")
                            ) {
                                $new_docum_attachment = new Attachment();
                                $path = Assistant::UploadFile($passport_attachment->attachment_blob, $passport_attachment->attachment_title, 'attachment', $passport_attachment->attachment_type);
                                $warnings[] = $method_name . '. Сохраненный путь:';
//                                $warnings[] = $path;
                                $new_docum_attachment->path = $path;
                                $new_docum_attachment->date = BackendAssistant::GetDateFormatYMD();
                                $new_docum_attachment->worker_id = $session['worker_id'];
                                $new_docum_attachment->section_title = 'Паспорт';
                                $new_docum_attachment->title = $passport_attachment->attachment_title;
                                $new_docum_attachment->attachment_type = $passport_attachment->attachment_type;
                                $new_docum_attachment->sketch = $passport_attachment->attachment_sketch;
                                if ($new_docum_attachment->save()) {
                                    $new_docum_attachment->refresh();
                                    $new_docum_attachment_id = $new_docum_attachment->id;
                                    $passport_attachment->attachment_id = $new_docum_attachment_id;
                                    $passport_attachment->attachment_path = $path;
                                    $passport_attachment->attachment_blob = null;
                                    $passport_attachment->attachment_status = null;
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
                            $passport_attachment_id = $passport_attachment->passport_attachment_id;
                            $new_passport_attachment = PassportAttachment::findOne(['id' => $passport_attachment_id]);
                            if (!$new_passport_attachment) {
                                $new_passport_attachment = new PassportAttachment();
                            } else {
                                $warnings[] = $method_name . ". Вложение документа уже было ";
                            }
                            $new_passport_attachment->passport_id = $passport_id;
                            $new_passport_attachment->passport_section_id = $passport_section->passport_section_id;
                            $new_passport_attachment->title = $passport_document_title;
                            $new_passport_attachment->attachment_id = $new_docum_attachment_id;
                            if ($new_passport_attachment->save()) {
                                $new_passport_attachment->refresh();
                                $passport_attachment_id = $new_passport_attachment->id;
                                $passport_attachment->passport_attachment_id = $passport_attachment_id;
                                $data_post->passport->passport_sections->{$key_section}->{"attachments"}->{$passport_attachment_id} = $passport_attachment;
                                if ($key_attach < 0) {
                                    unset($data_post->passport->passport_sections->{$key_section}->{"attachments"}->{$key_attach});
                                }
                                $warnings[] = $method_name . '. Данные успешно сохранены в модель PassportAttachment';
                            } else {
                                $errors[] = $new_passport_attachment->errors;
                                throw new \Exception($method_name . '. Ошибка сохранения модели PassportAttachment');
                            }
                        }
                    }
                }
            }


            $result = $data_post->passport;

        } catch
        (\Exception $e) {
            $status = 0;
            $errors[] = $method_name . ". Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    // GetPassport - метод получения паспорта из бд
    // входные параметры:
    //  passport_id - ключ паспорта
    // выходной массив:
    //      {passport_id}
    //              passport_id             -   ключ паспорта
    //              passport_title          -   наименование паспорта
    //              place_id	            -   ключ места
    //              place_title	            -   наименование места
    //              passport_sections       -   вложения паспорта
    //                  {passport_section_id}
    //                      passport_section_id     -   ключ раздела паспорта
    //                      passport_section_title	-   наименование раздела паспорта
    //                      attachments
    //                          {passport_attachment_id}
    //                                  passport_attachment_id          - ключ вложения паспорта
    //                                  passport_document_title         - название документа в паспорте
    //                                  attachment_id	                - ключ вложения
    //                                  attachment_path	                - путь вложения
    //                                  attachment_title                - наименование вложения
    //                                  attachment_type                 - тип вложения
    //                                  attachment_status               - статус вложения (del - удалить)
    //                                  attachment_blob                 - вложение блоб
    //                                  attachment_sketch               - эскиз вложения
    //              parameters              -   список параметров паспорта
    //                  {passport_parameter_id}
    //                      passport_parameter_id       - ключ параметра паспорта
    //                      parameter_id                - ключ параметра
    //                      parameter_title             - наименование параметра
    //                      unit_id                     - ключ единицы измерения
    //                      unit_title                  - наименование единицы измерения
    //                      short_title                 - сокращенное наименование единицы измерения
    //                      value                       - значение параметра паспорта
    //              shifts	                -   смены паспорта (график)
    //                  {shift_id}
    //                      shift_id            - ключ смены
    //                      operations	        - список операций на смене
    //                          {passport_operation_id}
    //                                  passport_operation_id           -   ключ операции паспорта
    //                                  parrent_passport_operation_id   -   ключ вышестоящей операции паспорта
    //                                  plan_value	                    -   плановое значение
    //                                  date_time_start	                -   время начала операции
    //                                  date_time_end	                -   время окончания операции
    //                                  operation_id                    -   ключ операции
    //                                  operation_title                 -   наименование операции
    //                                  line_in_grafic                  -   позиция в графике - для сортировки и перетаскивания
    //                                  materials	                    -   список материала
    //                                      {passport_operation_material_id}
    //                                              passport_operation_material_id      -   ключ конкретного материала конкретной операции
    //                                              material_id                         -   ключ материала
    //                                              value                               -   количество материала
    //                                              unit_id                             -   ключ единицы измерения материала
    //                                              nomenclature_id                     -   ключ номенклатуры
    //                                              nomenclature_title                  -   наименование номенклатуры
    //  стандартный набор данных
    // http://127.0.0.1/read-manager-amicum?controller=handbooks\HandbookPassport&method=GetPassport&subscribe=login&data={"passport_id":"1"}
    //
    public static function GetPassport($data_post = null)
    {
        $method_name = "GetPassport";                                                                                             //уникальный идентификатор сессии
        $status = 1;                                                                                                      //флаг успешного выполнения метода
        $warnings = array();                                                                                              // массив предупреждений
        $errors = array();                                                                                                // массив ошибок
        $result = array();                                                                                                // промежуточный результирующий массив
        try {
            if (!is_null($data_post) and $data_post != "") {
                $warnings[] = "GetPassport. данные успешно переданы";
                $warnings[] = "GetPassport. Входной массив данных" . $data_post;
            } else {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }

            $data_post = json_decode($data_post);                                                                      //декодируем входной массив данных
            $warnings[] = "GetPassport. декодировал входные параметры";
            if (!property_exists($data_post, 'passport_id')) {
                throw new \Exception($method_name . '.  Ошибка в наименование параметра во входных данных');
            }

            $warnings[] = "GetPassport. Проверил входные данные";
            $passport_id = $data_post->passport_id;

            $passport = Passport::find()
                ->select(
                    'passport.id as id,
                            passport.title as title,
                            place_id as place_id'
                )
                ->joinWith('place')
                ->joinWith('passportParameters.parameter.unit')
                ->joinWith('passportOperations.operation')
                ->joinWith('passportAttachments.attachment')
                ->joinWith('passportOperations.passportOperationMaterials.material.nomenclature')
                ->where(['passport.id' => $passport_id])
                ->asArray()
                ->one();

            if ($passport) {
                $passport_id = $passport['id'];
                $result[$passport_id]['passport_id'] = $passport_id;
                $result[$passport_id]['passport_title'] = $passport['title'];
                if ($passport['place']) {
                    $result[$passport_id]['place_id'] = $passport['place']['id'];
                    $result[$passport_id]['place_title'] = $passport['place']['title'];
                } else {
                    $result[$passport_id]['place_id'] = null;
                    $result[$passport_id]['place_title'] = "";
                }

                $passport_sections = PassportSection::find()->asArray()->all();

                if ($passport_sections) {
                    foreach ($passport_sections as $passport_section) {
                        $result[$passport_id]['passport_sections'][$passport_section['id']]['passport_section_id'] = $passport_section['id'];
                        $result[$passport_id]['passport_sections'][$passport_section['id']]['passport_section_title'] = $passport_section['title'];
                    }
                } else {
                    $result[$passport_id]['passport_sections'] = (object)array();
                }

                if ($passport['passportAttachments']) {
                    foreach ($passport['passportAttachments'] as $passport_attachment) {
                        $passport_attachment_id = $passport_attachment['id'];
                        $passport_section_id = $passport_attachment['passport_section_id'];
                        $result[$passport_id]['passport_sections'][$passport_section_id]['passport_section_id'] = $passport_section_id;
                        $result[$passport_id]['passport_sections'][$passport_section_id]['attachments'][$passport_attachment_id]['passport_attachment_id'] = $passport_attachment_id;
                        $result[$passport_id]['passport_sections'][$passport_section_id]['attachments'][$passport_attachment_id]['passport_document_title'] = $passport_attachment['title'];
                        $result[$passport_id]['passport_sections'][$passport_section_id]['attachments'][$passport_attachment_id]['attachment_id'] = $passport_attachment['attachment_id'];
                        $result[$passport_id]['passport_sections'][$passport_section_id]['attachments'][$passport_attachment_id]['attachment_path'] = $passport_attachment['attachment']['path'];
                        $result[$passport_id]['passport_sections'][$passport_section_id]['attachments'][$passport_attachment_id]['attachment_title'] = $passport_attachment['attachment']['title'];
                        $result[$passport_id]['passport_sections'][$passport_section_id]['attachments'][$passport_attachment_id]['attachment_type'] = $passport_attachment['attachment']['attachment_type'];
                        $result[$passport_id]['passport_sections'][$passport_section_id]['attachments'][$passport_attachment_id]['attachment_sketch'] = $passport_attachment['attachment']['sketch'];
                        $result[$passport_id]['passport_sections'][$passport_section_id]['attachments'][$passport_attachment_id]['attachment_blob'] = "";
                        $result[$passport_id]['passport_sections'][$passport_section_id]['attachments'][$passport_attachment_id]['attachment_status'] = "";
                    }
                }

                if ($passport['passportParameters']) {
                    foreach ($passport['passportParameters'] as $passport_parameter) {
                        $passport_parameter_id = $passport_parameter['id'];
                        $result[$passport_id]['parameters'][$passport_parameter_id]['passport_parameter_id'] = $passport_parameter_id;
                        $result[$passport_id]['parameters'][$passport_parameter_id]['parameter_id'] = $passport_parameter['parameter_id'];
                        $result[$passport_id]['parameters'][$passport_parameter_id]['parameter_title'] = $passport_parameter['parameter']['title'];
                        $result[$passport_id]['parameters'][$passport_parameter_id]['unit_id'] = $passport_parameter['parameter']['unit']['id'];
                        $result[$passport_id]['parameters'][$passport_parameter_id]['unit_title'] = $passport_parameter['parameter']['unit']['title'];
                        $result[$passport_id]['parameters'][$passport_parameter_id]['short_title'] = $passport_parameter['parameter']['unit']['short'];
                        $result[$passport_id]['parameters'][$passport_parameter_id]['value'] = $passport_parameter['value'];
                    }
                } else {
                    $result[$passport_id]['parameters'] = (object)array();
                }

                if ($passport['passportOperations']) {
                    foreach ($passport['passportOperations'] as $passport_operation) {
                        $passport_operation_id = $passport_operation['id'];
                        $shift_id = $passport_operation['shift_id'];
                        $result[$passport_id]['shifts'][$shift_id]['shift_id'] = $shift_id;
                        $result[$passport_id]['shifts'][$shift_id]['operations'][$passport_operation_id]['passport_operation_id'] = $passport_operation_id;
                        $result[$passport_id]['shifts'][$shift_id]['operations'][$passport_operation_id]['parrent_passport_operation_id'] = $passport_operation['passport_operation_id'];
                        $result[$passport_id]['shifts'][$shift_id]['operations'][$passport_operation_id]['plan_value'] = $passport_operation['plan_value'];
                        $result[$passport_id]['shifts'][$shift_id]['operations'][$passport_operation_id]['line_in_grafic'] = $passport_operation['line_in_grafic'];
                        $result[$passport_id]['shifts'][$shift_id]['operations'][$passport_operation_id]['date_time_start'] = $passport_operation['date_time_start'];
                        $result[$passport_id]['shifts'][$shift_id]['operations'][$passport_operation_id]['date_time_end'] = $passport_operation['date_time_end'];
                        $result[$passport_id]['shifts'][$shift_id]['operations'][$passport_operation_id]['operation_id'] = $passport_operation['operation_id'];
                        if ($passport_operation['operation_id']) {
                            $result[$passport_id]['shifts'][$shift_id]['operations'][$passport_operation_id]['operation_title'] = $passport_operation['operation']['title'];
                            $result[$passport_id]['shifts'][$shift_id]['operations'][$passport_operation_id]['unit_id'] = $passport_operation['operation']['unit_id'];
                        } else {
                            $result[$passport_id]['shifts'][$shift_id]['operations'][$passport_operation_id]['operation_title'] = "";
                            $result[$passport_id]['shifts'][$shift_id]['operations'][$passport_operation_id]['unit_id'] = null;
                        }
                        if ($passport_operation['passportOperationMaterials']) {
                            foreach ($passport_operation['passportOperationMaterials'] as $operation_material) {
                                $operation_material_id = $operation_material['id'];
                                $result[$passport_id]['shifts'][$shift_id]['operations'][$passport_operation_id]['materials'][$operation_material_id]['passport_operation_material_id'] = $operation_material_id;
                                $result[$passport_id]['shifts'][$shift_id]['operations'][$passport_operation_id]['materials'][$operation_material_id]['material_id'] = $operation_material['material_id'];
                                $result[$passport_id]['shifts'][$shift_id]['operations'][$passport_operation_id]['materials'][$operation_material_id]['value'] = $operation_material['value'];
                                if ($operation_material['material_id']) {
                                    $result[$passport_id]['shifts'][$shift_id]['operations'][$passport_operation_id]['materials'][$operation_material_id]['unit_id'] = $operation_material['material']['unit_id'];
                                    $result[$passport_id]['shifts'][$shift_id]['operations'][$passport_operation_id]['materials'][$operation_material_id]['nomenclature_id'] = $operation_material['material']['nomenclature_id'];
                                    $result[$passport_id]['shifts'][$shift_id]['operations'][$passport_operation_id]['materials'][$operation_material_id]['nomenclature_title'] = $operation_material['material']['nomenclature']['title'];
                                } else {
                                    $result[$passport_id]['shifts'][$shift_id]['operations'][$passport_operation_id]['materials'][$operation_material_id]['unit_id'] = null;
                                    $result[$passport_id]['shifts'][$shift_id]['operations'][$passport_operation_id]['materials'][$operation_material_id]['nomenclature_id'] = null;
                                    $result[$passport_id]['shifts'][$shift_id]['operations'][$passport_operation_id]['materials'][$operation_material_id]['nomenclature_title'] = "";
                                }
                            }
                        } else {
                            $result[$passport_id]['shifts'][$shift_id]['operations'][$passport_operation_id]['materials'] = (object)array();
                        }
                    }
                } else {
                    $result[$passport_id]['shifts'] = (object)array();
                }

            } else {
                $result = (object)array();
            }


        } catch (\Exception $e) {
            $status = 0;
            $errors[] = "GetPassport. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }
}
