<?php
/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\controllers;

use frontend\models\Event;
use frontend\models\GroupAlarm;
use frontend\models\Main;
use frontend\models\XmlConfig;
use frontend\models\XmlModel;
use frontend\models\XmlSendType;
use frontend\models\XmlTimeUnit;
use Throwable;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\web\Response;

//use frontend\commands\XmlServiceController;
//use SimpleXMLElement;

class XmlController extends \yii\web\Controller
{

    // getEmailRepeatSendingList        - Получение списка актуальных email адресов для повторной рассылки (этапное оповещение)

    // GetXmlSendType()      - Получение справочника типа XML выгрузки
    // SaveXmlSendType()     - Сохранение справочника типа XML выгрузки
    // DeleteXmlSendType()   - Удаление справочника типа XML выгрузки

    // GetXmlTimeUnit()      - Получение справочника единиц измерения времени
    // SaveXmlTimeUnit()     - Сохранение справочника единиц измерения времени
    // DeleteXmlTimeUnit()   - Удаление справочника единиц измерения времени

    // SendSafetyEmail       - Рассылка email сообщений о произошедшем событии

    public function actionIndex()
    {
        $debug_flag = 0;

        if ($debug_flag == 1) {
            $this->actionXml();
//        $arrayName = $this->actionGetNameFilesModels();
            return $this->render('index', [
//            'arrayName' => $arrayName
            ]);
        }

        $xml_send_type = XmlSendType::find()
            ->orderBy(['title' => SORT_ASC])
            ->asArray()
            ->all();
        $events = Event::find()
            ->orderBy(['title' => SORT_ASC])
            ->asArray()
            ->all();
        $xml_models = XmlModel::find()
            ->orderBy(["title" => SORT_ASC])
            ->asArray()
            ->all();
        $xml_time_unit = XmlTimeUnit::find()
//            ->orderBy(["title" => SORT_ASC])
            ->asArray()
            ->all();
        $alarm_groups = GroupAlarm::find()
            ->orderBy(["title" => SORT_ASC])
            ->asArray()
            ->all();

//        $db_tables = (new Query())
//                ->select(
//                    'table_name'
//                )
//                ->from(['information_schema.tables'])
//                ->where (['table_schema' => 'amicum2', 'table_type' => 'base table'])
////                ->asArray()
//                ->all();
        $db_tables = self::actionGetNameFilesModels();
        $this->view->registerJsVar('xml_send_type', $xml_send_type);
        $this->view->registerJsVar('xml_models', $xml_models);
        $this->view->registerJsVar('events', $events);
        $this->view->registerJsVar('db_models', $db_tables);
        $this->view->registerJsVar('xml_time_unit', $xml_time_unit);
        $this->view->registerJsVar('alarmGroups', $alarm_groups);
        return $this->render('index');
    }

    /**
     * Получение списка актуальных телефонных номеров для СМС рассылки по
     * данному идентификатору события
     * @param int $event_id идентификатор события
     * @param null $alarm_group_id идентификатор группы оповещения
     * @return array|bool массив номеров для рассылки.
     * Если номеров, удовлетворяющих условию нет, то возвращает false
     */
    public static function getSmsSendingList($event_id, $alarm_group_id = null)
    {
        $active_query = XmlConfig::find()
            ->where([
                'xml_send_type_id' => XmlSendTypeEnum::SMS,
                'event_id' => $event_id
            ])
            ->andWhere(':cur_date between date_start and date_end')
            ->andWhere(['or',
                ['=', 'position', 0],
                ['is', 'position', null]
            ])
            ->params([':cur_date' => \backend\controllers\Assistant::GetDateNow()]);
        if ($alarm_group_id !== null) {
            $active_query->andWhere([
                'xml_send_type_id' => $alarm_group_id
            ]);
        }
        $numbers = $active_query->asArray()->all();

        if ($numbers) {
            return ArrayHelper::getColumn($numbers, 'address');
        }

        return false;
    }

    /**
     * Получение списка актуальных email адресов для рассылки по данному
     * идентификатору события
     * @param int $event_id идентификатор события
     * @param null $alarm_group_id идентификатор группы оповещения
     * @return array|bool массив адресов для рассылки.
     * Если адресов, удовлетворяющих условию нет, то возвращает false
     */
    public static function getEmailSendingList($event_id, $alarm_group_id = null)
    {
        $active_query = XmlConfig::find()
            ->where([
                'xml_send_type_id' => XmlSendTypeEnum::EMAIL,
                'event_id' => $event_id
            ])
            ->andWhere(':cur_date between date_start and date_end')
            ->andWhere(['or',
                ['=', 'position', 0],
                ['is', 'position', null]
            ])
            ->params([':cur_date' => \backend\controllers\Assistant::GetDateNow()]);
        if ($alarm_group_id !== null) {
            $active_query->andWhere([
                'xml_send_type_id' => $alarm_group_id
            ]);
        }
        $addresses = $active_query->asArray()->all();

        if ($addresses) {
            return ArrayHelper::getColumn($addresses, 'address');
        }

        return false;
    }

    /**
     * getEmailRepeatSendingList - Получение списка актуальных email адресов для повторной рассылки (этапное оповещение)
     * @param array $event_ids массив идентификаторов события
     * @param array $xml_send_type_ids массив идентификаторо группы оповещения
     * @param int $position номер рассылки
     * @return array|bool массив адресов для рассылки.
     * Если адресов, удовлетворяющих условию нет, то возвращает false
     */
    public static function getEmailRepeatSendingList($event_ids, $xml_send_type_ids, $position)
    {
        $active_query = XmlConfig::find()
            ->where([
                'xml_send_type_id' => XmlSendTypeEnum::EMAIL
            ])
            ->where(['event_id' => $event_ids])
            ->andWhere(':cur_date between date_start and date_end')
            ->andWhere(['or',
                ['=', 'position', $position],
                ['is', 'position', null]
            ])
            ->params([':cur_date' => \backend\controllers\Assistant::GetDateNow()]);
        if ($xml_send_type_ids !== null) {
            $active_query->andWhere([
                'xml_send_type_id' => $xml_send_type_ids
            ]);
        }
        $addresses = $active_query->asArray()->all();

        if ($addresses) {
            return ArrayHelper::getColumn($addresses, 'address');
        }

        return false;
    }

    /**
     * SendSafetyEmail - Рассылка email сообщений о произошедшем событии
     * @param string $message - текст сообщения
     * @param array|string $addresses - адреса, на которые отправляем сообщение
     * @return array
     */
    public static function SendSafetyEmail($message, $addresses)
    {
        $status = 1;
        $warnings[] = array();
        $errors[] = array();

        $warnings[] = 'SendSafetyEmail. Начало метода';

        try {
            Yii::$app->mailer->compose()
                ->setFrom('support@amicum.ru')
                ->setTo($addresses)
                ->setSubject('Событие')
                ->setTextBody($message)
                ->send();

        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'SendSafetyEmail. Конец метода';

        return array('status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    public static function actionGetNameFilesModels()
    {
        $arrayName = array();
        $arrayModels = FileHelper::findFiles(str_replace('controllers', 'models', __DIR__));
//        $arrayDirName = dirname("$arrayModels[0]");
        for ($i = 0; $i < count($arrayModels); $i++) {
            $arrayName[$i] = basename("$arrayModels[$i]", ".php");
        }
        return $arrayName;
    }

    public function actionGetXmlConfig()
    {
        $post = Assistant::GetServerMethod();
        $errors = array();
        $debug_flag = 0;
        $configs = array();
        $config_array_front = array();
        if (isset($post['model_id']) and $post['model_id'] != "") {
            $model_id = $post['model_id'];
            $config_array_front = $this->buildXmlConfigs($model_id);
        } else {
            $errors[] = "не передан идентифкатор модели";
        }
        $result = array('errors' => $errors, 'configs' => $config_array_front);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    private function buildXmlConfigs($model_id)
    {
        $config_array_front = array();
        $configs = XmlConfig::find()
            ->joinWith('xmlSendType')
            ->joinWith('timeUnit')
            ->joinWith('event')
            ->joinWith('groupAlarm')
            ->where(['xml_model_id' => $model_id])
            ->all();
        $i = 0;
        foreach ($configs as $config_xml) {
            $config_array_front[$i]['id'] = $config_xml->id;
            $config_array_front[$i]['model_id'] = $config_xml->xml_model_id;
            $config_array_front[$i]['xml_send_type_id'] = $config_xml->xml_send_type_id;
            if ($config_xml->xml_send_type_id) {
                $config_array_front[$i]['xml_send_type_title'] = $config_xml->xmlSendType->title;
            } else {
                $config_array_front[$i]['xml_send_type_title'] = "";
            }
            $config_array_front[$i]['xml_address'] = $config_xml->address;
            $config_array_front[$i]['xml_time_period'] = $config_xml->time_period;
            $config_array_front[$i]['xml_time_unit_id'] = $config_xml->time_unit_id;
            if ($config_xml->time_unit_id) {
                $config_array_front[$i]['xml_time_unit_title'] = $config_xml->timeUnit->title;
            } else {
                $config_array_front[$i]['xml_time_unit_title'] = "";
            }
            $config_array_front[$i]['xml_date_start'] = date('d.m.Y H:i:s', strtotime($config_xml->date_start));
            $config_array_front[$i]['xml_date_end'] = date('d.m.Y H:i:s', strtotime($config_xml->date_end));
            $config_array_front[$i]['xml_event_id'] = $config_xml->event_id;
            if ($config_xml->event_id) {
                $config_array_front[$i]['xml_event_title'] = $config_xml->event->title;
            } else {
                $config_array_front[$i]['xml_event_title'] = "";
            }
            $config_array_front[$i]['xml_send_type_id'] = $config_xml->xml_send_type_id;
            if ($config_xml->groupAlarm) {
                $config_array_front[$i]['xml_send_type_title'] = $config_xml->groupAlarm->title;
            } else {
                $config_array_front[$i]['xml_send_type_title'] = "";
            }
            $config_array_front[$i]['description'] = $config_xml->description;
            $config_array_front[$i]['position'] = $config_xml->position;
            $i++;
        }
        return $config_array_front;
    }

    /*
     * функция определения действия над данными
     * входные данные принимаем по ajax
     * table_name - имя модели
     * action_type - действие над данными
     * */
    public function actionXml()
    {
        $debug_flag = 0;
        $errors = array();//создаем массив ошибок
        $post = Yii::$app->request->post(); //получение данных от ajax-запроса
//        if(isset($post['model_name']) && isset($post['xml_sent_type_id']) &&
//            isset($post['address']) && isset($post['time_period']) &&
//            isset($post['event_id'])){//проверка что все данные были переданы
        if (!$debug_flag) {
            echo nl2br("режим работы\n");
            $tableName = $post['model_name'];//название модели
            $xmlSentTypeId = $post['xml_sent_type_id'];//вид действия
            $address = $post['address'];//адрес действия
            $timePeriod = $post['time_period'];//временной интервал
            $eventId = $post['event_id'];//идентификатор ситуации
        } else {
            echo nl2br("режим дебага\n");
            $tableName = 'equipment';//название модели
            $xmlSentTypeId = 2;//вид действия
            $address = "example_address@gmail.com";//адрес действия
            $timePeriod = "2018-05-07";//временной интервал
            $eventId = 81;//идентификатор ситуации
        }
        $xmlSentTypeTitle = XmlSendType::find()->where(['id' => $xmlSentTypeId])->one()->title;//находим вид действия
        $modelName = "frontend\\models\\" . ucfirst($tableName);//название модели
        if (!$xmlModel = XmlModel::find()->where([
            'title' => $tableName
        ])->one()) {
            $model = new XmlModel();//создаем новую запись в xml_model
            $model->title = (string)$tableName;//заполняем поле title
            if (!$model->save()) {//если сохранить не удалось
                $errors[] = "Не удалось сохранить данные в xml_model";//записываем соответствую ошибку
            } else {
                $xmlModel = $model;
            }
        }
        $modelConf = new XmlConfig();//создаем новую запись в xml_config
        $modelConf->xml_model_id = (int)$xmlModel->id;//заполняем поле идентификатора модели
        $modelConf->xml_send_type_id = (int)$xmlSentTypeId;//заполняем поле вида действия с данными
        $modelConf->address = (string)$address;//заполняем поле адреса
        $modelConf->time_period = (string)$timePeriod;//заполняем поле время действия
        $modelConf->event_id = (int)$eventId;//заполняем поле идентификатор ситуации
        if ($modelConf->save()) {//если сохранить удалось
            $data = $modelName::find()->asArray()->all();//выдергиваем данные
            if ($data) {//если данные есть
                $xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><data></data>");
                switch ($xmlSentTypeTitle) {//смотрим что надо сделать с данными
                    case 'email'://отправка на почту
                        $this->sendMail($data, $modelName, $xml, $address);//вызываем метод отправки на почту
                        break;
                    case 'local'://выгрузка файла
                        $this->uploadFileXml($tableName);//вызываем метод выгрузки файла
                        break;
                    case 'server'://сохранение на сервер
                        $this->saveServerXml($data, $tableName, $xml);//вызываем метод сохранения файла на сервере
                        break;
                    default:
                        $errors[] = "Неправильно выбран вид действия";
                        break;
                }
            } else {
                $errors[] = "Не удалось сохранить данные в xml_config";
            }
        }
//        }
    }

    /*
     * функция построения xml документа
     * //Создает XML-строку и XML-документ при помощи DOM
     * */

    public static function buildXmlFromArray($data, $rootName = 'data', $xml = null)
    {
//
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {//если число, то добавляем имя таблицы перед индексом
                $key = "$rootName$key";
            }
//            $key = preg_replace('/[^a-z0-9]/i', '', $key);//убирает все спецсимволы
            if (is_array($value)) {// если массив, перебираем еще раз данные
                $node = $xml->addChild($key);//добавляем дочерний элемент
                self::buildXmlFromArray($value, $rootName, $node);//заходим в рекурсию
            } else {//иначе делаем запись в дочернем элементе
                $value = htmlentities($value);
                $xml->addChild($key, $value);
            }
        }
//        $xml->formatOutput = true;
        return $xml->asXML();//возвращаем данные как xml
    }

    /*
     * функция отправки xml разметки по почте
     * входные данные
     * data - данные из модели в виде массива
     * */
    public static function sendMail($data, $tableName, $xml, $emailAddress)
    {
        $modelName = "frontend\\models\\" . ucfirst($tableName);                                                        //название модели
        self::saveServerXml($data, $tableName, $xml);                                                                   //генерируем файл на сервер
        $data = self::buildXmlFromArray($data, $modelName, $xml);                                                       //вызываем функцию построения xml разметки из массива
        Yii::$app->mailer->compose()
            ->setFrom('root@pfsz.ru')                                                                               //адрес отправителя
            ->setTo($emailAddress)                                                                                      //адрес получателя
            ->setSubject('theme subject')                                                                        //тема сообщения
            ->attach('..\web\xml-files\tmp\\' . $tableName . '.xml')                                            //прикрепляем файл с xml разметкой
            ->setTextBody(serialize($data))
//            ->setHtmlBody($data)
            ->send();
    }

    public function actionSaveXmlLocal()
    {
        $debug_flag = 0;
        $errors = array();//создаем массив ошибок
        $post = Yii::$app->request->post(); //получение данных от ajax-запроса
//        echo "test0\n";
//        echo "------".$post['model_id']."--------\n";
        if ($debug_flag == 1) {
            $model_title = 'equipment';
            self::uploadFileXml($model_title);
        } else {
            if (isset($post['model_id']) and $post['model_id'] != "") {
                $modelTitle = XmlModel::findOne((int)$post['model_id']);
//                echo $modelTitle->title;
                if ($modelTitle) {
                    self::uploadFileXml($modelTitle->title);
                } else {
                    $errors[] = "не найдена модель с таким id = " . $post['model_id'];
                }
            } else {
                $errors[] = "не передан id модели";
            }
        }

//        echo json_encode($errors);
    }

    /*
     * функция выгрузки файлов на ftp сервер
     * */
    public static function uploadFileXml($tableName)

    {
//        $ftp = new \yii2mod\ftp\FtpClient();
//        $host = 'ftp.example.com';
//        $ftp->connect($host, true, 22);
//        $ftp->login($login, $password);
//        echo $tableName;
        $xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><data></data>");

        $model = "frontend\\models\\" . $tableName;
//        echo $modelName." and model is ".$model;
        $arr = $model::find()->asArray()->all();
        $xml_file = self::buildXmlFromArray($arr, 'data', $xml);
//        $xml_file = self::buildXmlFromArray($arr, $tableName);
        $result = array('xml' => $xml_file, 'model_title' => $tableName);
        echo json_encode($result);
    }

    function actionSaveXmlOnServer()
    {
        $errors = array();                                                                                              //создаем массив для ошибок
        $post = Yii::$app->request->post();
        $result = "";
        $type = "danger";
        if (isset($post['xml_model_id']) and $post['xml_model_id'] != "") {
            $xml_model_id = $post['xml_model_id'];
            if ($xml_model_title = XmlModel::findOne(['id' => $xml_model_id])) {
                $original_title = $xml_model_title->title;
                $xml_model_title = self::camelCase($xml_model_title->title);
                $modelName = "frontend\\models\\" . $xml_model_title;
                $xml_arr = $modelName::find()->asArray()->all();                                                   //если метод пост задан и есть его конкретное значение, то мы ищем по нему
                $xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><data></data>");
                $saving_flag = self::saveServerXml($xml_arr, $original_title, $xml);
                if ($saving_flag == 1) {
                    $result = "Файл " . $original_title . ".xml был успешно создан";
                    $type = "success";
                } else {
                    $result = "Возникла ошибка при создании файла XML";
                    $type = "danger";
                }
            } else {
                $errors[] = "не найдена запись с id модели = " . $xml_model_id;
            }

        } else {
            $errors[] = "не передан id модели " . $post['xml_model_id'];
        }
        $response = array('errors' => $errors, 'response' => $result, 'type' => $type);
        echo json_encode($response);
    }

    public function camelCase($str)
    {
        $words = explode('_', $str);
        $newStr = '';
        foreach ($words as $key => $word) {
            $newStr .= $key == 0 ? ucfirst($word) : mb_convert_case($word, MB_CASE_TITLE, "UTF-8");
        }
        return $newStr;
    }

    /*
     * функция сохранения файла на сервере
     * входные параметры:
     * $data -  массив данных
     * $tableName - имя таблицы
     * $xml - создание нового дочернего элемента
     * */
    public static function saveServerXml($data, $tableName, $xml)
    {
        self::buildXmlFromArray($data, $tableName, $xml);//вызываем функцию построения xml разметки из массива
        $xml_file = $xml->asXML('../web/xml-files/' . $tableName . '.xml');//сохраняем файл на сервере
        if ($xml_file) {
            return 1;
        } else {
            return -1;
        }
    }

    /*
     * метод отправки смс сообщений
     * */
    public static function sendSms()
    {
        //пока пустой надо узнать, что отправлять
        //SmsSendController::actionSendSmsMessage();
    }

    //метод добавления моделей xml  в базу
    public function actionAddXmlFront()
    {
        $debug_flag = 0;
        $errors = array();                                                                                              //создаем массив для ошибок
        $post = Yii::$app->request->post();                                                                             //получение данных от ajax-запроса
        $xml_model_id = null;
        if (isset($post['xml_model_title']) and $post['xml_model_title'] != "") {
            $xml_model_title = $post['xml_model_title'];
            $xml_models = XmlModel::findAll(['title' => $xml_model_title]);                                             //если метод пост задан и есть его конкретное значение, то мы ищем по нему
            if ($xml_models) $xml_models = -1;
            else $xml_models = 0;
        } else $xml_models = -1;                                                                                          //иначе ищем все модели какие есть и заполняем по ним свойства

        if (!$xml_models == -1) {

            //создаем новый айдишник в главной таблице айдишников
            $main_id = new Main();
            $main_id->table_address = 'xml_model';                                                                      //адрес таблицы в которой искать главный айди
            $main_id->db_address = 'amicum2';                                                                           //имя базы данных в которой лежит таблица
//            $main_id->save();

            //создаем новую xml модель как элемент, но пустое
            if ($main_id->save()) {
                $xml_model_id = $main_id->id;
                $xml_models = new XmlModel();
                $xml_models->id = $xml_model_id;
                $xml_models->title = strval($xml_model_title);                                                          //название модели

                if ($debug_flag == 1) echo nl2br("айди xml модели " . $xml_model_id . "\n");
                if ($debug_flag == 1) echo nl2br("название модели xml " . $xml_model_title . "\n");

                if (!$xml_models->save()) $errors[] = "Объекта с названием " . $xml_model_title . " не создан";             //Сохранить модель
            } else $errors[] = "Главный айди " . $xml_model_title . " не создан";
        } else {
            $errors[] = "Записи в БД с таким именем уже есть";
        }
        $xmlModelArray = XmlModel::find()->orderBy(['title' => SORT_ASC])->asArray()->all();
//        $someArray = $this->actionBuildXmlFront();
        $result = array('xmlModelArray' => $xmlModelArray, 'errors' => $errors, 'xml_model_id' => $xml_model_id);                         //получает массив моделей xml из базы - все какие есть
        echo json_encode($result);
    }

    public function actionEditXmlModel()
    {
        $post = \backend\controllers\Assistant::GetServerMethod();
        $errors = array();
        $warnings = array();
        $status = 1;

        $warnings[] = 'actionEditXmlModel. Начало метода';

        try {
            if (isset($post['title']) && $post['title'] != '' &&
                isset($post['id']) && $post['id'] != '')                                                                      //проверка на передачу данных
            {
                $xml_model_id = $post['id'];
                $xml_model_title = $post['title'];

                $xml_models = XmlModel::findOne($xml_model_id);                                                             //найти объект по id
                if ($xml_models) {                                                                                            //если объект существует
                    $existingObject = XmlModel::findOne(['title' => $xml_model_title]);                                     //найти объект по названию, чтобы не было дублирующих
                    if (!$existingObject) {                                                                                   //если не найден
                        $xml_models->title = $xml_model_title;                                                              //сохранить в найденный по id параметр название
                        if (!$xml_models->save()) $errors[] = 'Ошибка сохранения';
                    } else $errors[] = 'Объект с таким названием уже существует';                                             //если найден объект по названию, сохранить соответствующую ошибку
                } else $errors[] = 'Объекта с id ' . $xml_model_id . ' не существует';                                            //если не найден объект по id, сохранить соответствующую ошибку
            } else $errors[] = 'Данные не переданы';                                                                          //если не заданы входные параметры сохранить соответствующую ошибку
            $xml_models_array = XmlModel::find()
                ->orderBy(['title' => SORT_ASC])
                ->asArray()
                ->all();
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'actionEditXmlModel.Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings[] = 'actionEditXmlModel. Конец метода';

        $result = array('warnings' => $warnings, 'status' => $status,
            'xmlModelArray' => $xml_models_array, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * Редактирование конкретной конфигурации с списке рассылок
     */
    public function actionEditConfigXmlModel()
    {
        $post = Yii::$app->request->post(); //получение данных от ajax-запроса
        $errors = array();
        if (isset($post['xml_config_id']) && $post['xml_config_id'] != '' &&
            isset($post['xml_model_id']) && $post['xml_model_id'] != '' &&
            isset($post['xml_send_type_id']) && $post['xml_send_type_id'] != '' &&
            isset($post['xml_time_period']) && $post['xml_time_period'] != '' &&
            isset($post['xml_time_unit_id']) && $post['xml_time_unit_id'] != '' &&
            isset($post['xml_date_start']) && $post['xml_date_start'] != '' &&
            isset($post['xml_date_end']) && $post['xml_date_end'] != '' &&
            isset($post['event_id']) && $post['event_id'] != '' &&
            isset($post['xml_address']) &&
            isset($post['description']) &&
            isset($post['position']) &&
            isset($post['xml_send_type_id'])) {
            $xml_config_id = $post['xml_config_id'];
            $xml_send_type_id = $post['xml_send_type_id'];
            $address = $post['xml_address'];
            $time_period = $post['xml_time_period'];
            $event_id = $post['event_id'];
            $xml_time_unit_id = $post['xml_time_unit_id'];
            $date_start = $post['xml_date_start'];
            $date_end = $post['xml_date_end'];
            $xml_send_type_id = $post['xml_send_type_id'];
            $position = $post['position'];
            $description = $post['description'];

            $xml_config = XmlConfig::findOne($xml_config_id);                                                                     //найти объект по id
            if ($xml_config) {                                                                                                //если объект существует
                $xml_config->xml_send_type_id = $xml_send_type_id;
                $xml_config->address = $address == '' ? '-' : $address;
                $xml_config->time_period = $time_period;
                $xml_config->time_unit_id = $xml_time_unit_id;
                $xml_config->date_start = date('Y-m-d H:i:s', strtotime($date_start));
                $xml_config->date_end = date('Y-m-d H:i:s', strtotime($date_end));
                $xml_config->event_id = $event_id;
                $xml_config->description = $description;
                $xml_config->position = $position;
                if (!empty($xml_send_type_id) and $xml_send_type_id and $xml_send_type_id!="" and $xml_send_type_id!="null") {
                    $xml_config->xml_send_type_id = $xml_send_type_id;
                }
                if (!$xml_config->save()) {
                    $errors[] = $xml_config->errors;
                    $errors[] = 'Ошибка сохранения';
                }
            } else {
                $errors[] = 'Объекта с id ' . $xml_config_id . ' не существует';
            }
        } else {
            $errors[] = 'actionEditConfigXmlModel. Не переданы необходимые параметры';
        }
        $configs_array = $this->buildXmlConfigs($post['xml_model_id']);
        $result = array('configs_array' => $configs_array, 'errors' => $errors);
        echo json_encode($result);                                                                                      //вернуть AJAX-запросу данные и ошибки
    }

    //метод удаления xml модели
    public function actionDeleteXmlModel()
    {
        $post = Yii::$app->request->post(); //получение данных от ajax-запроса
        $errors = array();

        if (isset($post['xml_model_id']) && $post['xml_model_id'] != "")                                                   //проверка на передачу данных
        {
            $xml_model_id = $post['xml_model_id'];
            XmlConfig::deleteAll('xml_model_id=:xml_model_id', [':xml_model_id' => $xml_model_id]);               //удаляем конфигурации xml модели
            XmlModel::deleteAll('id=:id', [':id' => $xml_model_id]);                                              //удаляем саму xml модель
        } else $errors[] = 'Данные не переданы';                                                                           //если не заданы входные параметры сохранить соответствующую ошибку
        $xml_models = XmlModel::find()
            ->orderBy(['title' => SORT_ASC])
            ->asArray()
            ->all();
        $result = array('xmlModelArray' => $xml_models, 'errors' => $errors);
        echo json_encode($result);                                                                                      //вернуть AJAX-запросу данные и ошибки
    }

    //метод удаления конфигурации xml модели
    public function actionDeleteConfigXmlModel()
    {
        $post = Yii::$app->request->post(); //получение данных от ajax-запроса
        $errors = array();

        if (isset($post['xml_config_id']) && $post['xml_config_id'] != '' && isset($post['xml_model_id']) && $post['xml_model_id'] != '')                                                                      //проверка на передачу данных
        {
            $xml_config_id = $post['xml_config_id'];
            XmlConfig::deleteAll('id=:xml_config_id', [':xml_config_id' => $xml_config_id]);                                      //удаляем конфигурации xml модели
        } else {
            $errors[] = 'Данные не переданы';
        }                                                                           //если не заданы входные параметры сохранить соответствующую ошибку
        $configs = self::buildXmlConfigs($post['xml_model_id']);
        $result = array('configs_array' => $configs, 'errors' => $errors);
        echo json_encode($result);                                                                                      //вернуть AJAX-запросу данные и ошибки
    }

    /**
     * Добавление конкретной конфигурации для рассылки
     * @return Response
     */
    public function actionAddXmlConfig()
    {
        $status = 1;
        $errors = array();
        $configs_array = array();

        try {
            $post = \backend\controllers\Assistant::GetServerMethod(); //получение данных от ajax-запроса

            /**=================================================================
             * Валидация входных данных
             * ===============================================================*/
            $post_valid = isset($post['xml_model_id'], $post['xml_send_type_id'],
                $post['xml_time_period'], $post['xml_time_unit_id'],
                $post['xml_date_start'], $post['xml_date_end'],
                $post['event_id'], $post['xml_address'], $post['xml_send_type_id']);
            if (!$post_valid) {
                throw new \Exception('actionAddXmlConfig. Не все входные параметры инициализированы');
            }

            if ($post['xml_model_id'] != ''
                && $post['xml_send_type_id'] != ''
                && $post['xml_time_period'] != ''
                && $post['xml_time_unit_id'] != ''
                && $post['xml_date_start'] != ''
                && $post['xml_date_end'] != ''
                && $post['description'] != ''
                && $post['xml_send_type_id'] != ''
                && $post['position'] != ''
                && $post['event_id'] != '') {
                $config = new XmlConfig();
                $config->xml_model_id = $post['xml_model_id'];
                $config->xml_send_type_id = $post['xml_send_type_id'];
                $config->address = $post['xml_address'] == '' ? '-' : $post['xml_address'];
                $config->time_period = $post['xml_time_period'];
                $config->time_unit_id = $post['xml_time_unit_id'];
                $config->date_start = date('Y-m-d H:i:s', strtotime($post['xml_date_start']));
                $config->date_end = date('Y-m-d H:i:s', strtotime($post['xml_date_end']));
                $config->event_id = $post['event_id'];
                $config->description = $post['description'];
                $config->position = $post['position'];
                $config->xml_send_type_id = $post['xml_send_type_id'];
                if (!$config->save()) {
                    $errors[] = 'не удалось добавить конфигурацию модели';
                }
                $configs_array = $this->buildXmlConfigs($post['xml_model_id']);
            } else {
                $errors[] = 'actionAddXmlConfig. Не переданы необходимые параметры';
            }
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $result = array('status' => $status, 'configs_array' => $configs_array, 'errors' => $errors);
        return $this->asJson($result);
    }

    /**
     * Функция поиска по моделям в XmlModel
     */
    public function actionSearchXml()
    {
        $post = \Yii::$app->request->post();                                                                            // переменная для получения ajax-запросов
        $errors[] = array();                                                                                            // пустой массив дя хранения ошибок
        $post['xml_title'] = 'emp';
        if (isset($post['xml_title']) and $post['xml_title'] != "")                                                     // если передан параметр для поиска, и параметр не имеет пустое значение
        {
            $xml_list = XmlModel::find('title LIKE "%' . $post['xml_title'] . '%"')->all();                                 // находим модель по названию
            if ($xml_list)                                                                                              // если найдены данные
            {
                Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;                                           // формат json
                Yii::$app->response->data = $xml_list;                                                                // отправляем обратно ввиде FORMAT_JSON
            } else {
                $errors[] = 'По Вашему запросу ничего не найдено';
            }
        } else {
            $errors[] = "Параметр xml_title не передан или имеет пустое значение";
        }
        //print_r($errors);
    }


    // GetXmlSendType()      - Получение справочника типа XML выгрузки
    // SaveXmlSendType()     - Сохранение справочника типа XML выгрузки
    // DeleteXmlSendType()   - Удаление справочника типа XML выгрузки

    /**
     * Метод GetXmlSendType() - Получение справочника типа XML выгрузки
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                        // ключ справочника типа XML выгрузки
     *      "title":"ACTION",               // название типа XML выгрузки
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=Xml&method=GetXmlSendType&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetXmlSendType()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetXmlSendType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_xml_send_type = XmlSendType::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_xml_send_type)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник типа XML выгрузки пуст';
            } else {
                $result = $handbook_xml_send_type;
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
     * Метод SaveXmlSendType() - Сохранение справочника типа XML выгрузки
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "xml_send_type":
     *  {
     *      "xml_send_type_id":-1,           // ключ справочника типа XML выгрузки
     *      "title":"ACTION",                // название типа XML выгрузки
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "xml_send_type_id":-1,           // ключ справочника типа XML выгрузки
     *      "title":"ACTION",                // название типа XML выгрузки
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=Xml&method=SaveXmlSendType&subscribe=&data={"xml_send_type":{"xml_send_type_id":-1,"title":"ACTION"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveXmlSendType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveXmlSendType';
        $handbook_xml_send_type_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'xml_send_type'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_xml_send_type_id = $post_dec->xml_send_type->xml_send_type_id;
            $title = $post_dec->xml_send_type->title;
            $new_handbook_xml_send_type_id = XmlSendType::findOne(['id' => $handbook_xml_send_type_id]);
            if (empty($new_handbook_xml_send_type_id)) {
                $new_handbook_xml_send_type_id = new XmlSendType();
            }
            $new_handbook_xml_send_type_id->title = $title;
            if ($new_handbook_xml_send_type_id->save()) {
                $new_handbook_xml_send_type_id->refresh();
                $handbook_xml_send_type_data['xml_send_type_id'] = $new_handbook_xml_send_type_id->id;
                $handbook_xml_send_type_data['title'] = $new_handbook_xml_send_type_id->title;
            } else {
                $errors[] = $new_handbook_xml_send_type_id->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении справочника типа XML выгрузки');
            }
            unset($new_handbook_xml_send_type_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_xml_send_type_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteXmlSendType() - Удаление справочника типа XML выгрузки
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "xml_send_type_id": 98             // идентификатор справочника типа XML выгрузки
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=Xml&method=DeleteXmlSendType&subscribe=&data={"xml_send_type_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteXmlSendType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteXmlSendType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'xml_send_type_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_xml_send_type_id = $post_dec->xml_send_type_id;
            $del_handbook_xml_send_type = XmlSendType::deleteAll(['id' => $handbook_xml_send_type_id]);
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


    // GetXmlTimeUnit()      - Получение справочника единиц измерения времени
    // SaveXmlTimeUnit()     - Сохранение справочника единиц измерения времени
    // DeleteXmlTimeUnit()   - Удаление справочника единиц измерения времени

    /**
     * Метод GetXmlTimeUnit() - Получение справочника единиц измерения времени
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                        // ключ справочника единиц измерения времени
     *      "title":"ACTION",               // название единицы измерения времени
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=Xml&method=GetXmlTimeUnit&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetXmlTimeUnit()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetXmlTimeUnit';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_xml_time_unit = XmlTimeUnit::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_xml_time_unit)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник единиц измерения времени пуст';
            } else {
                $result = $handbook_xml_time_unit;
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
     * Метод SaveXmlTimeUnit() - Сохранение справочника единиц измерения времени
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "xml_time_unit":
     *  {
     *      "xml_time_unit_id":-1,           // ключ справочника единиц измерения времени
     *      "title":"ACTION",                // название единицы измерения времени
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "xml_time_unit_id":-1,           // ключ справочника единиц измерения времени
     *      "title":"ACTION",                // название единицы измерения времени
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=Xml&method=SaveXmlTimeUnit&subscribe=&data={"xml_time_unit":{"xml_time_unit_id":-1,"title":"ACTION"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveXmlTimeUnit($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveXmlTimeUnit';
        $handbook_xml_time_unit_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'xml_time_unit'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_xml_time_unit_id = $post_dec->xml_time_unit->xml_time_unit_id;
            $title = $post_dec->xml_time_unit->title;
            $new_handbook_xml_time_unit_id = XmlTimeUnit::findOne(['id' => $handbook_xml_time_unit_id]);
            if (empty($new_handbook_xml_time_unit_id)) {
                $new_handbook_xml_time_unit_id = new XmlTimeUnit();
            }
            $new_handbook_xml_time_unit_id->id = $handbook_xml_time_unit_id;
            $new_handbook_xml_time_unit_id->title = $title;
            if ($new_handbook_xml_time_unit_id->save()) {
                $new_handbook_xml_time_unit_id->refresh();
                $handbook_xml_time_unit_data['xml_time_unit_id'] = $new_handbook_xml_time_unit_id->id;
                $handbook_xml_time_unit_data['title'] = $new_handbook_xml_time_unit_id->title;
            } else {
                $errors[] = $new_handbook_xml_time_unit_id->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении справочника единиц измерения времени');
            }
            unset($new_handbook_xml_time_unit_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_xml_time_unit_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteXmlTimeUnit() - Удаление справочника единиц измерения времени
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "xml_time_unit_id": 98             // идентификатор справочника единиц измерения времени
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=Xml&method=DeleteXmlTimeUnit&subscribe=&data={"xml_time_unit_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteXmlTimeUnit($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteXmlTimeUnit';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'xml_time_unit_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_xml_time_unit_id = $post_dec->xml_time_unit_id;
            $check_xml_config_exist = XmlConfig::findAll(['time_unit_id' => $handbook_xml_time_unit_id]);
            if ($check_xml_config_exist) {
                throw new \Exception('Удаление единицы времени невозможно, т.к. она используется в конфигурациях оповещения');
            }
            $del_handbook_xml_time_unit = XmlTimeUnit::deleteAll(['id' => $handbook_xml_time_unit_id]);
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

}

class XmlSendTypeEnum
{
    const EMAIL = 1;
    const SMS = 5;
}
