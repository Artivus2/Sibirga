<?php

namespace frontend\controllers\handbooks;

use backend\controllers\cachemanagers\SensorCacheController;
use backend\controllers\CoordinateController;
use backend\controllers\SensorBasicController;
use frontend\controllers\Assistant;
use frontend\controllers\positioningsystem\SpecificSensorController;
use frontend\models\AccessCheck;
use frontend\models\Asmtp;
use frontend\models\ConnectString;
use frontend\models\Main;
use frontend\models\Sensor;
use frontend\models\SensorConnectString;
use frontend\models\SensorParameter;
use frontend\models\SensorParameterHandbookValue;
use frontend\models\SensorType;
use frontend\models\TypicalObject;
use Yii;
use yii\db\Query;
use yii\web\Response;

class HandbookSensorController extends \yii\web\Controller
{
    // GetSensorType                        - Получение справочника типов сенсоров
    // SaveSensorType                       - Сохранение нового типа сенсора
    // DeleteSensorType                     - Удаление типа сенсора


    /**
     * Название метода: actionIndex()
     * Метод рендеринга на представление
     *
     * @return string
     * @package frontend\controllers\handbooks
     * @example http://amicum.web/handbooks/handbook-sensor
     *
     * Документация на портале:
     * @author Incognito
     * Transfer date: on 11.05.2019 12:13
     * @since ver
     */
    public function actionIndex()
    {
        $model = $this->HandbookSensor();
        $sensorTypes = (new Query())
            ->select('id, title')
            ->from('sensor_type')
            ->all();
        $typicalObjects = (new Query())
            ->select('object.id as id , object.title as title, view_typical_pattern.patter_value as patter_value')
            ->from('object')
            ->join('JOIN','object_type', 'object_type.id = object.object_type_id')
            ->join('JOIN','kind_object', 'kind_object.id = object_type.kind_object_id')
            ->leftJoin('view_typical_pattern','view_typical_pattern.object_id = object.id')
            ->where('kind_object.id = 4')
            ->all();

        $connection_string_list = (new Query())
            ->select([
                'sensor_id',
                'connection_string_id',
                'connection_string_title'
            ])
            ->from('view_all_conn_string_with_sensor')
            ->orderBy(['connection_string_title' => SORT_ASC])
            ->all();

        $index = 0;
        $connection_string_array = array();
        foreach ($connection_string_list as $connection_string)
        {
            $connection_string_array[$index]['id'] = $connection_string['connection_string_id'];
            $connection_string_array[$index]['title'] = $connection_string['connection_string_title'];
            if($connection_string['sensor_id'] == "")                                                    // если у строки подключкения нет сенсор id, то значит, что она не привязана к дачику
            {
                $connection_string_array[$index]['is_connected'] = 'false';                                             // флаг
            }
            else
            {
                $connection_string_array[$index]['is_connected'] = 'true';
            }
            $index++;
        }
        return $this->render('index', [
            'model' => $model,
            'sensorTypes' => $sensorTypes,
            'typicalObjects' => $typicalObjects,
            'connectStrings' => $connection_string_array
        ]);
    }

    /**
     * Название метода: HandbookSensor()
     * Функция построения массива данных
     *
     * Входные параметры отсутствуют.
     * Выходные параметры:
     * @return - $model - array - массив объектов систем автоматизации
     * |-- $model[id] - объект "система автоматизации"
     *    |-- [title] - string - название системы автоматизации
     *    |-- [sensor] - array - датчики внутри системы автоматизации (массив объектов "датчик")
     *        |--[id] - объект "датчик"
     *           |--[title] - string - название датчика
     *           |--[sensorTypeId] - id типа датчика
     *           |--[objectId] - id объекта
     * @package frontend\controllers\handbooks
     * @example
     *
     * Документация на портале:
     * @author Incognito
     * Transfer date: on 11.05.2019 12:13
     * @since ver
     */
    public function HandbookSensor()
    {
        $errors = array();
        $sensor_handbook = array();
        $sensor_handbook_list = (new Query())
            ->select([
                'asmtp_id',
                'asmtp_title',
                'sensor_id',
                'sensor_title',
                'object_id',
                'object_title',
                'sensor_type_id',
                'sensor_type_title',
                'connection_string_title',
                'connection_string_id',
                'source_parameter_title',
                'source_parameter_id',
                'parameter_id'

            ])
            ->from('view_sensor_handbook')
            ->orderBy(['asmtp_id' => SORT_ASC, 'sensor_title' => SORT_ASC])
            ->all();
        if($sensor_handbook_list)
        {
            $index = -1;
            $j = 0;
            $flag = false;
            foreach ($sensor_handbook_list as $sensor)
            {
                $smtp_id = $sensor['asmtp_id'];
                $smtp_title = $sensor['asmtp_title'];
                if ($index == -1 OR $sensor_handbook[$index]['asmtp_id'] != $smtp_id)
                {
                    $index++;
                    $sensor_handbook[$index]['asmtp_id'] = $smtp_id;
                    $sensor_handbook[$index]['asmtp_title'] = $smtp_title;
                    $j = 0;
                }
                if($sensor['sensor_id'] != '')
                {
                    $sensor_handbook[$index]['sensors'][$j]['sensor_id'] =  $sensor['sensor_id'];
                    $sensor_handbook[$index]['sensors'][$j]['sensor_title'] =  $sensor['sensor_title'];
                    $sensor_handbook[$index]['sensors'][$j]['object_id'] =  $sensor['object_id'];
                    $sensor_handbook[$index]['sensors'][$j]['object_title'] =  $sensor['object_title'];
                    $sensor_handbook[$index]['sensors'][$j]['sensor_type_id'] =  $sensor['sensor_type_id'];
                    $sensor_handbook[$index]['sensors'][$j]['sensor_type_title'] =  $sensor['sensor_type_title'];
                    $sensor_handbook[$index]['sensors'][$j]['connection_string_title'] =  $sensor['connection_string_title'];
                    $sensor_handbook[$index]['sensors'][$j]['connection_string_id'] =  $sensor['connection_string_id'];
                    $sensor_handbook[$index]['sensors'][$j]['source_parameter_title'] =  $sensor['source_parameter_title'];
                    $sensor_handbook[$index]['sensors'][$j]['source_parameter_id'] =  $sensor['source_parameter_id'];
                    $sensor_handbook[$index]['sensors'][$j]['parameter_id'] =  $sensor['parameter_id'];
                    $j++;
                }
            }
        }
        else{
            $errors[] = 'Ошибка загрузки данных из представления';
        }

        return $sensor_handbook;
    }

    /**
     * Название метода: actionAddAsmtp()
     * Метод добавления системы автоматизации
     *
     * @package frontend\controllers\handbooks
     * @example
     *
     * Документация на портале:
     * @author Incognito
     * Transfer date: on 11.05.2019 12:13
     * @since ver
     */
    public function actionAddAsmtp()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if(isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 48)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                $asmtp = Asmtp::findOne(['title'=>$post['title']]);
                if(!$asmtp){
                    $asmtp = new Asmtp();
                    $asmtp->title = $post['title'];
                    if($asmtp->save()){
                        $model = $this->HandbookSensor();
//                        echo json_encode($model);
                    }
                    else{
                        $errors[] = "Не удалось сохранить";
                        $model = $this->HandbookSensor();
                    }
                }
                else{
                    $errors[] = "Такая система автоматизации уже существует";
                    $model = $this->HandbookSensor();
                }
            }
            else{
                $errors[] = "У вас недостаточно прав для выполнения этого действия";
                $model = $this->HandbookSensor();
            }
        }
        else{
            $errors[] = "Сессия неактивна";
            $model = $this->HandbookSensor();
        }
        $result = array('errors' => $errors, 'model' => $model);
        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    /**
     * Название метода: actionMarkSearchSensor()
     * Метод поиска датчиков и строк подключения с выделением найденного
     *
     * @package frontend\controllers\handbooks
     * @example
     *
     * Документация на портале:
     * @author Одилов О.У. <ooy@pfsz.ru>
     * Created by: Одилов О.У. on 30.10.2018 11:28
     * @since ver
     */
    public function actionMarkSearchSensor()
    {
        $post = Yii::$app->request->post();
        $errors = array();
        $sensor_handbook = array();
        if(isset($post['search_title']))
        {
            $search_title = $post['search_title'];
            if(isset($search_title) and $search_title !="")
            {
                $sql_condition = "sensor_title like '%$search_title%' OR 
                                        object_title like '%$search_title%' OR sensor_type_title like '%$search_title%' OR 
                                        connection_string_title like '%$search_title%'";
            }
            else
            {
                $sql_condition = "";
            }

            $sensor_handbook_list = (new Query())
                ->select([
                    'asmtp_id',
                    'asmtp_title',
                    'sensor_id',
                    'sensor_title',
                    'object_id',
                    'object_title',
                    'sensor_type_id',
                    'sensor_type_title',
                    'connection_string_title',
                    'connection_string_id'
                ])
                ->from('view_sensor_handbook')
                ->where($sql_condition)
                ->orderBy(['asmtp_id' => SORT_ASC, 'sensor_title' => SORT_ASC])
                ->all();
            if($sensor_handbook_list)
            {
                $index = -1;
                $j = 0;
                $flag = false;
                foreach ($sensor_handbook_list as $sensor)
                {
                    $smtp_id = $sensor['asmtp_id'];
                    $smtp_title = $sensor['asmtp_title'];
                    if ($index == -1 OR $sensor_handbook[$index]['asmtp_id'] != $smtp_id)
                    {
                        $index++;
                        $sensor_handbook[$index]['asmtp_id'] = $smtp_id;
                        $sensor_handbook[$index]['asmtp_title'] = $smtp_title;
                        $j = 0;
                    }
                    if($sensor['sensor_id'] != '')
                    {
                        $sensor_handbook[$index]['sensors'][$j]['sensor_id'] =  $sensor['sensor_id'];
                        $sensor_handbook[$index]['sensors'][$j]['sensor_title'] =  Assistant::MarkSearched($search_title,$sensor['sensor_title']);
                        $sensor_handbook[$index]['sensors'][$j]['object_id'] =  $sensor['object_id'];
                        $sensor_handbook[$index]['sensors'][$j]['object_title'] =  Assistant::MarkSearched($search_title,$sensor['object_title']);
                        $sensor_handbook[$index]['sensors'][$j]['sensor_type_id'] =  $sensor['sensor_type_id'];
                        $sensor_handbook[$index]['sensors'][$j]['sensor_type_title'] =   Assistant::MarkSearched($search_title,$sensor['sensor_type_title']);
                        $sensor_handbook[$index]['sensors'][$j]['connection_string_title'] =  Assistant::MarkSearched($search_title,$sensor['connection_string_title']);
                        $sensor_handbook[$index]['sensors'][$j]['connection_string_id'] =  $sensor['connection_string_id'];
                        $j++;
                    }
                }
            }
        }
        else
        {
            $errors[] = "Параметры не переданы";
        }
        $result = array('errors' => $errors, 'sensors' => $sensor_handbook);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * Название метода: actionEditAsmtp()
     * Метод редактирования системы автоматизации
     *
     * @package frontend\controllers\handbooks
     * @example
     *
     * Документация на портале:
     * @author Incognito
     * Transfer date: on 11.05.2019
     * @since ver
     */
    public function actionEditAsmtp()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if(isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 49)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                $asmtp = Asmtp::findOne($post['id']);
                if($asmtp)
                {
                    $existingAsmtp = Asmtp::findOne(['title' => $post['title']]);
                    if(!$existingAsmtp)
                    {
                        $asmtp->title = $post['title'];
                        if($asmtp->save())
                        {
                            $model = $this->HandbookSensor();
                        }
                    }
                    else
                    {
                        $errors[] = "Система автоматизации с таким названием уже существует";
                        $model = $this->HandbookSensor();
                    }
                }
                else
                {
                    $errors[] = "Данной системы автоматизации не существует";
                    $model = $this->HandbookSensor();
                }
            }
            else{
                $errors[] = "У вас недостаточно прав для выполнения этого действия";
                $model = $this->HandbookSensor();
            }
        }
        else{
            $errors[] = "Сессия неактивна";
            $model = $this->HandbookSensor();
        }
        $result = array('errors' => $errors, 'model' => $model);
        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    /**
     * Название метода: actionDeleteAsmtp()
     * Метод удаления системы автоматизации
     *
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * Документация на портале:
     * @package frontend\controllers\handbooks
     * @example
     *
     * @author Incognito
     * Transfer date: on 11.05.2019
     * @since ver
     */
    public function actionDeleteAsmtp()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if(isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 50)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                $asmtp = Asmtp::findOne($post['id']);
                if($asmtp){
                    if($asmtp->delete()){
                        $model = $this->HandbookSensor();
//                        echo json_encode($model);
                    }
                    else{
                        $errors[] = "Ошибка удаления";
                        $model = $this->HandbookSensor();
                    }
                }
                else{
                    $errors[] = "Данной системы автоматизации не существует";
                    $model = $this->HandbookSensor();
                }
            }
            else{
                $errors[] = "У вас недостаточно прав для выполнения этого действия";
                $model = $this->HandbookSensor();
            }
        }
        else{
            $errors[] = "Сессия неактивна";
        }
        $result = array('errors' => $errors, 'model' => $model);
        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    /**
     * Название метода: actionAddSensor()
     * Метод добавления
     * @package frontend\controllers\handbooks
     * @example
     *
     * Документация на портале:
     * @author Incognito
     * Transfer date: on 11.05.2019
     * @since ver
     */
    public function actionAddSensor()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();

        if(isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 51)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                $sensor = Sensor::findOne(['title' => $post['title']]);
                if(!$sensor){
                    $response = SensorBasicController::addSensor($post['title'],$post['objectId'],$post['asmtpId']);
                    if($response['status']==1){
                        $sensor_id=$response['sensor_id'];
                        $sensor_parameter_handbook_value = $response['sensor_parameter_handbook_value'];
                        $response = (new SensorCacheController)->multiSetSensorParameterValueHash($sensor_parameter_handbook_value);
                        if($response['status']==1)
                        {
                            //$errors[] = $response['errors'];
                            //$errors[] = $response['warnings'];
                            $response=(new SensorCacheController)->initSensorMainHash(-1,$sensor_id);
                            //$errors[] = $response['errors'];
                            //$errors[] = $response['warnings'];
                        }
                    }
                    else{
                        $errors[] = $response['errors'];
                    }
                    $model = $this->HandbookSensor();
                }
                else{
                    $errors[] = "Датчик ".$post['title']." уже существует";
                    $model = $this->HandbookSensor();
                }
            }
            else{
                $errors[] = "У вас недостаточно прав для выполнения этого действия";
                $model = $this->HandbookSensor();
            }
        }
        else{
            $errors[] = "Сессия неактивна";
        }
        $result = array('errors' => $errors, 'model' => $model);
        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    /**
     * Название метода: actionEditSensor()
     * Метод редактирования сенсора
     *
     * @package frontend\controllers\handbooks
     * @example
     *
     * Документация на портале:
     * @author Incognito
     * Transfer date: on 11.05.2019
     * @since ver
     */
    public function actionEditSensor()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if(isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 52)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                $sensor = Sensor::findOne($post['id']);
                if($sensor){
                    $existingSensor = Sensor::findOne(['title' => $post['title']]);
                    if(!$existingSensor || $existingSensor->id === $sensor->id){
                        $sensor->title = $post['title'];
                        if(isset($post['sensorTypeId'])){
                            $sensorType = SensorType::findOne($post['sensorTypeId']);
                            if($sensorType){
                                $sensor->sensor_type_id = $post['sensorTypeId'];
                                //пишем новый 338 параметр (тип датчика) в справочник
                                $sensorParameter_id = SensorParameter::findOne([
                                    'parameter_id' => 338,
                                    'sensor_id' => $sensor->id,
                                ])->id;
                                if($sensorParameter_id)
                                {
                                    $specific_parameter_handbook_value = new SensorParameterHandbookValue();                //создать новое значение справочного параметра
                                    $specific_parameter_handbook_value->sensor_parameter_id = $sensorParameter_id;
                                    $specific_parameter_handbook_value->date_time = date("Y-m-d H:i:s");
                                    $specific_parameter_handbook_value->value = $post['sensorTypeId'];                      //сохранить новое значение, текущую метку времени, типовой параметр и статус
                                    $specific_parameter_handbook_value->status_id = 1;
                                    if (!$specific_parameter_handbook_value->save())                                         //если не сохранилась
                                    {
                                        $errors[] = "Справочное значение " . 338 . " параметра не сохранено. Идентификатор объекта " . $sensor->id;//сохранить соответствующую ошибку
                                    }
                                }

                            }
                        }
                        if(isset($post['objectId'])){
                            $object = TypicalObject::findOne($post['objectId']);
                            if($object){
                                $sensor->object_id = $post['objectId'];
                                //пишем новый 274 параметр (типовой объект) в справочник
                                $sensorParameter_id = SensorParameter::findOne([
                                    'parameter_id' => 274,
                                    'sensor_id' => $sensor->id,
                                ])->id;
                                if($sensorParameter_id)
                                {
                                    $specific_parameter_handbook_value = new SensorParameterHandbookValue();                      //создать новое значение справочного параметра
                                    $specific_parameter_handbook_value->sensor_parameter_id = $sensorParameter_id;
                                    $specific_parameter_handbook_value->date_time = date("Y-m-d H:i:s");
                                    $specific_parameter_handbook_value->value = $post['objectId'];                          //сохранить новое значение
                                    $specific_parameter_handbook_value->status_id = 1;
                                    if (!$specific_parameter_handbook_value->save())                                        //если не сохранилась
                                    {
                                        $errors[] = "Справочное значение " . 274 . " параметра не сохранено. Идентификатор объекта " . $sensor->id;//сохранить соответствующую ошибку
                                    }
                                }

                            }
                        }
                        if(isset($post['asmtpId'])){
                            $asmtp = Asmtp::findOne($post['asmtpId']);
                            if($asmtp){
                                $sensor->asmtp_id = $post['asmtpId'];
                            }
                        }
                        if($sensor->save()){
                            $model = $this->HandbookSensor();
//                            echo json_encode($model);
                        }
                    }
                    else{
                        $errors[] = "Датчик с таким названием уже существует";
                        $model = $this->HandbookSensor();
                    }
                }
                else{
                    $errors[] = "Данного датчика не существует";
                    $model = $this->HandbookSensor();
                }
            }
            else{
                $errors[] = "У вас недостаточно прав для выполнения этого действия";
                $model = $this->HandbookSensor();
            }
        }
        else{
            $errors[] = "Сессия неактивна";
        }
        $result = array('errors' => $errors, 'model' => $model);
        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    /**
     * Название метода: actionDeleteSensor()
     * Метод удаления сенсора
     *
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * Документация на портале:
     * @package frontend\controllers\handbooks
     * @example
     *
     * @author Incognito
     * Transfer date: on 11.05.2019
     * @since ver
     */
    public function actionDeleteSensor()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if(isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 53)) {                                      //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();                                                                     //получение данных от ajax-запроса
                if(isset($post['id']) AND !empty($post['id']))                                                          // Если данные получены
                {
                    $sensor_id = $post['id'];
                    $main = Main::findOne($sensor_id);                                                                  // Удаляем из Main данные о идентификаторе конкретного сенсора
                    $main->delete();
                    $errors = SpecificSensorController::methodDeleteSensor($sensor_id);                                 // Вызываем метод удаления сенсора и всех связанных с ним параметров
                    (new SensorCacheController())->delInSensorMineHash($sensor_id, AMICUM_DEFAULT_MINE);
                    (new CoordinateController())->delGraph($sensor_id);                                                 //Вызываем метод удаления графа для сенсора

                    $model = $this->HandbookSensor();                                                                   // Строим массив сенсоров для передачи JSON ответа
                }
            }
            else{
                $errors[] = "У вас недостаточно прав для выполнения этого действия";
                $model = $this->HandbookSensor();
            }
        }
        else{
            $errors[] = "Сессия неактивна";
        }
        $result = array('errors' => $errors, 'model' => $model);
        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    /**
     * Название метода: actionAddConnectString()
     * Метод добавления строки подключения
     *
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * Документация на портале:
     * @package frontend\controllers\handbooks
     * @example
     *
     * @author Incognito
     * Transfer date: on 11.05.2019
     * @since ver
     */
    public function actionAddConnectString()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();                                                                                              // пустой массив для хранения ошибок
        $model = array();
        $debug = false;
        if ($debug) {
            echo nl2br("entered function \n");
        }
        if(isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if ($debug) {
                echo nl2br("login in session \n");
            }
            if (AccessCheck::checkAccess($session['sessionLogin'], 12)) {                                                //если пользователю разрешен доступ к функции
                if ($debug) {
                    echo nl2br("user has right to add connectString \n");
                }
                $post = Yii::$app->request->post(); //Получаем данные
                if (isset($post['sensor_id']) && isset($post['connect_string_id'])) {   //Проверка получения данных
                    if ($debug) {
                        echo nl2br($post['sensor_id']."\n"."--".$post['connect_string_id']);
                    }
                    $connection_string_id = (int)$post['connect_string_id'];
                    $sensor_id = (int)$post['sensor_id'];
                    $sensor=Sensor::findOne($sensor_id);
                    if(!$sensor){                                                       //Проверка существования данных sensor_id
                        $errors[] = "Нет такого датчика";
                        $model = $this->HandbookSensor();
                        return;
                    }
                    $ConnectString=ConnectString::findOne($connection_string_id);
                    if(!$ConnectString){                                                 //Проверка существования данных в connect_string_id
                        $errors[] =  "Нет такой строки подключения";
                        $model = $this->HandbookSensor();
                        return;
                    }
                    /*$last_connect_sensor = (new Query())                                                                // находим последнюю привязку строки подключения по дате
                        ->select(['connect_string_id', 'max(date_time) as date_time'])
                        ->from('sensor_connect_string')
                        ->where(['connect_string_id' => $connection_string_id])
                        ->groupBy('sensor_id')
                        ->one();*/
                    $last_connect_sensor = SensorConnectString::find()                                                //НАДО ДОПИЛИТЬНАДО ДОПИЛИТЬНАДО ДОПИЛИТЬНАДО ДОПИЛИТЬНАДО ДОПИЛИТЬНАДО ДОПИЛИТЬ
                    ->where(['connect_string_id' => $connection_string_id])
                        ->groupBy('sensor_id')
                        ->orderBy('date_time DESC')
                        ->one();

                    if ($debug) {
                        echo "-------------------\n";
                        var_dump($last_connect_sensor);
                        echo "-------------------\n";
                    }
                    if ($last_connect_sensor)                                                                          // если нашлась последняя строка подключения, то удаляем
                    {
//                         SensorConnectString::deleteAll(['connect_string_id' => $last_connect_sensor->connect_string_id]); // удаляем строку подключения
                        if (!$last_connect_sensor->delete()) {
                            $errors[] = "Не удалось удалить строку подключения у другого датчика";
                            $model = $this->HandbookSensor();
                        }
                    }

                    $sensors_prev_for_delete = SensorConnectString::find()->where(['sensor_id' => $sensor_id])->all();          // получаю предыдущие сенсоры
                    if ($sensors_prev_for_delete)                                                                       // найдены предыдущиие сенсоры, то удаляем
                    {
                        foreach($sensors_prev_for_delete as $prev)
                        {
                            if (!$prev->delete()) {
                                $errors[] = "Не удалось удалить предыдущие строки подключения: ".$prev->id;
                                $model = $this->HandbookSensor();
                            }
                        }
                    }
                    if ($debug) {
                        echo nl2br("before add new sensor_connect_string \n");
                    }
                    $SensorConnectString = new SensorConnectString();                     //Создаем экземпляр модели (нужно для записи данных в БД)
                    $SensorConnectString->sensor_id = $sensor_id;                 //Записываем в sensor_id
                    $SensorConnectString->connect_string_id = $connection_string_id; //Записываем в connect_string_id
                    $SensorConnectString->date_time = date('Y-m-d H:i:s');          //Записываем в date_time
                    if ($SensorConnectString->save()){                                    //Сохраняем данные
                        $model = $this->HandbookSensor();
                        if ($debug) {
                            echo nl2br("model saved \n");
                        }
                    }
                    else{
                        $errors[] =  "Модель не сохранилась";
                        $model = $this->HandbookSensor();
                    }
                }
            }
            else{
                $errors[] =  "У вас недостаточно прав для выполнения этого действия";
                $model = $this->HandbookSensor();
            }
        }
        else{
            $errors[] =  "Сессия неактивна";
            $model = $this->HandbookSensor();
        }

        $result = array('errors' => $errors, 'model' => $model);
        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data  =  $result;
    }

    /**
     * Название метода: actionRemoveConnectString()
     * Метод удаления строки подключения у сенсора
     *
     * @throws \yii\db\Exception
     * Документация на портале:
     * @see
     * @example http://localhost/handbook-sensor/remove-connect-string?sensor_id=76766
     *
     * @package app\controllers
     *
     * Входные обязательные параметры:
     *
     * Входные необязательные параметры
     *
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 05.04.2019 16:00
     * @since ver
     */
    public function actionRemoveConnectString()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();                                                                                              // пустой массив для хранения ошибок
        $model = array();

        if(isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 12)) {                                      // если пользователю разрешен доступ к функции
                $post = Assistant::GetServerMethod();                                                                   //Получаем данные
                if (isset($post['sensor_id'])) {   //Проверка получения данных
                    $sensor_id = (int)$post['sensor_id'];
                    $sensor= Sensor::findOne($sensor_id);                                                                // Найти сенсор по идентификатору
                    if(!$sensor)
                    {                                                                                       //Проверка существования данных sensor_id
                        $errors[] = "Нет такого датчика";
                        $model = $this->HandbookSensor();
                        return;
                    }
                    $connect_sensor = $command = Yii::$app->db->createCommand("
                            SELECT id 
                            FROM sensor_connect_string 
                            WHERE sensor_id = :sensor_id")
                        ->bindValue(":sensor_id", $sensor_id)
                        ->queryAll();
                    if ($connect_sensor)                                                                                 // найдены предыдущиие сенсоры, то удаляем
                    {
                        foreach($connect_sensor as $item)
                        {
                            $delete_connect_sensor = $command = Yii::$app->db->createCommand("
                                    DELETE FROM sensor_connect_string 
                                    WHERE id = :id")
                                ->bindValue(":id", $item['id']);
                            if (!$delete_connect_sensor->execute()) {
                                $errors[] = "Не удалось удалить строку подключения: ".$item->id;
                            }
                            $model = $this->HandbookSensor();
                        }
                    }
                }
            }
            else{
                $errors[] =  "У вас недостаточно прав для выполнения этого действия";
                $model = $this->HandbookSensor();
            }
        }
        else{
            $errors[] =  "Сессия неактивна";
            $model = $this->HandbookSensor();
        }

        $result = array('errors' => $errors, 'model' => $model);
        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data  =  $result;
    }

    /**
     * Название метода: actionSearchSensorConnectionStrings()
     * Метод получения списка строк подключения и поиск строки подключения для конкретного сенсора
     * С проверкой на приязканности строки подключения к сенсору
     *
     * @package frontend\controllers\handbooks
     * @example
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 11.05.2019 13:33
     * @since ver
     */
    public function actionSearchSensorConnectionStrings()
    {
        $post = \Yii::$app->request->post();
        $find_sensor_conn = array();
        $errors = array();
        if(isset($post['sensor_id']) and $post['sensor_id'] != '')
        {
            $find_sensor_conn_string = (new Query())
                ->select([
                    'sensor_id',
                    'connection_string_id',
                    'connection_string_title'
                ])
                ->from('view_sensor_last_connection_string')
                ->where(['sensor_id' => $post['sensor_id']])
                ->one();
            if($find_sensor_conn_string)
            {
                $find_sensor_conn = $find_sensor_conn_string;
            }

        }
//        $connection_string_array = array();
        $connection_string_list = (new Query())
            ->select([
                'sensor_id',
                'connection_string_id',
                'connection_string_title'
            ])
            ->from('view_all_conn_string_with_sensor')
            ->orderBy(['connection_string_title' => SORT_ASC])
            ->all();
        $index = 0;
        foreach ($connection_string_list as $connection_string)
        {
            $connection_string_array[$index]['id'] = $connection_string['connection_string_id'];
            $connection_string_array[$index]['title'] = $connection_string['connection_string_title'];
            if($connection_string['sensor_id'] == "")                                                    // если у строки подключкения нет сенсор id, то значит, что она не привязана к дачику
            {
                $connection_string_array[$index]['is_connected'] = 'false';                                             // флаг
            }
            else
            {
                $connection_string_array[$index]['is_connected'] = 'true';
            }
            $index++;
        }
        $result = array('errors' => $errors, 'find_con_string' => $find_sensor_conn, 'connection_strings' => $connection_string_array);
        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    /**
     * Метод GetSensorType() - Получение справочника типов сенсоров
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * [
     *      "id": "10",                     // идентификатор типа сенсора
     *      "title": "CommTrac NODE"        // наименование типа сенсора
     * ]
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookSensor&method=GetSensorType&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 30.03.2020 08:53
     */
    public static function GetSensorType()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetSensorType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $sensor_type = SensorType::find()
                ->asArray()
                ->all();
            if(empty($sensor_type)){
                $warnings[] = $method_name.'. Справочник типов сенсоров пуст';
            }else{
                $result = $sensor_type;
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
     * Метод SaveSensorType() - Сохранение нового типа сенсора
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "sensor_type":
     *  {
     *      "sensor_type_id":-1,							// идентификатор типа сенсора (-1 = новый тип сенсора)
     *      "title":"SENSOR_TYPE_TEST"						// наименование типа сенсора
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "sensor_type_id":-1,							// идентификатор типа сенсора (-1 = новый тип сенсора)
     *      "title":"SENSOR_TYPE_TEST"						// наименование типа сенсора
     * }
     * warnings:{}                                          // массив предупреждений
     * errors:{}                                            // массив ошибок
     * status:1                                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookSensor&method=SaveSensorType&subscribe=&data={"sensor_type":{"sensor_type_id":-1,"title":"SENSOR_TYPE_TEST"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 30.03.2020 09:00
     */
    public static function SaveSensorType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveSensorType';
        $chat_type_data = array();																				// Промежуточный результирующий массив
        $warnings[] = $method_name.'. Начало метода';
        try
        {
            if ($data_post == NULL && $data_post == '')
            {
                throw new \Exception($method_name.'. Не переданы входные параметры');
            }
            $warnings[] = $method_name.'. Данные успешно переданы';
            $warnings[] = $method_name.'. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name.'. Декодировал входные параметры';
            if (!property_exists($post_dec, 'sensor_type'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $sensor_type_id = $post_dec->sensor_type->sensor_type_id;
            $title = $post_dec->sensor_type->title;
            $sensor_type = SensorType::findOne(['id'=>$sensor_type_id]);
            if (empty($sensor_type)){
                $sensor_type = new SensorType();
            }
            $sensor_type->title = $title;
            if ($sensor_type->save()){
                $sensor_type->refresh();
                $chat_type_data['sensor_type_id'] = $sensor_type->id;
                $chat_type_data['title'] = $sensor_type->title;
            }else{
                $errors[] = $sensor_type->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении нового типа сенсора');
            }
            unset($sensor_type);
        }
        catch (\Throwable $exception)
        {
            $errors[] = $method_name.'. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name.'. Конец метода';
        $result = $chat_type_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteSensorType() - Удаление типа сенсора
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "sensor_type_id":12                             // идентификатор удаляемого типа сенсора
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив данных)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookSensor&method=DeleteSensorType&subscribe=&data={"sensor_type_id":12}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 30.03.2020 09:10
     */
    public static function DeleteSensorType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteSensorType';
        $warnings[] = $method_name.'. Начало метода';
        try
        {
            if ($data_post == NULL && $data_post == '')
            {
                throw new \Exception($method_name.'. Не переданы входные параметры');
            }
            $warnings[] = $method_name.'. Данные успешно переданы';
            $warnings[] = $method_name.'. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name.'. Декодировал входные параметры';
            if (!property_exists($post_dec, 'sensor_type_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $sensor_type_id = $post_dec->sensor_type_id;
            $del_sensor_type = SensorType::deleteAll(['id'=>$sensor_type_id]);
        }
        catch (\Throwable $exception)
        {
            $errors[] = $method_name.'. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name.'. Конец метода';
        $result = $post_dec;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }
}
