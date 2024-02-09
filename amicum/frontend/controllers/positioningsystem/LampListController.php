<?php

namespace frontend\controllers\positioningsystem;


use frontend\models\ViewPositioningSensorsWithWorkers;
use frontend\models\Worker;
use frontend\models\Employee;
use frontend\models\WorkerParameter;
use frontend\models\WorkerObject;
use frontend\models\WorkerParameterSensor;
use frontend\models\WorkerParameterHandbookValue;
use frontend\models\Sensor;
use yii\caching\MemCache;
use yii\db\Query;
use Yii;
use yii\web\Response;
use frontend\controllers\Assistant;

class LampListController extends \yii\web\Controller
{

    public function actionIndex()
    {
        $departments = (new Query())
            ->select('id, title')
            ->from('department')
            ->orderBy('title ASC')
            ->all();
        $companies = (new Query())
            ->select('id, title')
            ->from('company')
            ->orderBy('title ASC')
            ->all();
        return $this->render('index', [
            'departments' => $departments,
            'companies' => $companies
        ]);
    }

    public function actionSendWorkers()
    {
        $post = Assistant::GetServerMethod();
        $sql_filter = '';
        $filter_flag = true;
        $search = '';
        $sensors_count_departments = (new Query())
            ->select('*')
            ->from('view_sensors_departments_count')
            ->all();

//      фильтр по подразделению
        if (isset($post['department_id'])  && $post['department_id']!="") {
            $departmentId = (int)$post['department_id'];
            $sql_filter = Assistant::AddConditionOperator($sql_filter, "department_id = $departmentId", 'AND');
        }
//      фильтр по предприятию
        if (isset($post['company_id'])  && $post['company_id']!="") {
            $companyId = (int)$post['company_id'];
            $sql_filter = Assistant::AddConditionOperator($sql_filter, "company_id = $companyId", "AND");
        }
        if (isset($post['search'])) {
            $search = (string)$post['search'];
            $sql_filter = Assistant::AddConditionOperator($sql_filter, "(staff_number like '%$search%' or 
            position_title like '%$search%' or full_name like '%$search%' or sensor_title like '%$search%' 
            or network_id like '%$search%' or position_title like '%$search%' or company_title like '%$search%' or department_title like '%$search%')", 'AND');
        }
        $post[] = $sql_filter;
        $sensors = (new Query())
            ->select('*')
            ->from('view_positioning_sensors_with_workers')
            ->where($sql_filter)
            ->all();
        for ($i = 0; $i < count($sensors); $i++)
        {
            if($sensors[$i]['staff_number'] == null)
            {
                $sensors[$i]['staff_number'] = "-";
            } else {
                $sensors[$i]['staff_number'] = Assistant::MarkSearched($search, $sensors[$i]['staff_number']);
            }
            if($sensors[$i]['worker_id'] == null)
            {
                $sensors[$i]['worker_id'] = "-1";
            }
            if($sensors[$i]['position_title'] == null)
            {
                $sensors[$i]['position_title'] = "-";
            } else {
                $sensors[$i]['position_title'] = Assistant::MarkSearched($search, $sensors[$i]['position_title']);
            }
            if($sensors[$i]['full_name'] == null)
            {
                $sensors[$i]['full_name'] = "<span class='not-bound-lamp'>"."Лампа не привязана"."</span>";
            } else {
                $sensors[$i]['full_name'] = Assistant::MarkSearched($search, $sensors[$i]['full_name']);
            }
            if($sensors[$i]['company_title'] == null)
            {
                $sensors[$i]['company_title'] = "-";
            } else {
                $sensors[$i]['company_title'] = Assistant::MarkSearched($search, $sensors[$i]['company_title']);
            }
            if($sensors[$i]['department_title'] == null)
            {
                $sensors[$i]['department_title'] = "-";
            } else {
                $sensors[$i]['department_title'] = Assistant::MarkSearched($search, $sensors[$i]['department_title']);
            }
            if($sensors[$i]['sensor_title'] == null)
            {
                $sensors[$i]['sensor_title'] = "-";
            } else {
                $sensors[$i]['sensor_title'] = Assistant::MarkSearched($search, $sensors[$i]['sensor_title']);
            }
            if($sensors[$i]['network_id'] == null)
            {
                $sensors[$i]['network_id'] = "-";
            } else {
                $sensors[$i]['network_id'] = Assistant::MarkSearched($search, $sensors[$i]['network_id']);
            }

        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array('sensors' => $sensors,'$sensors_count_departments'=>$sensors_count_departments, 'debug_info' => $post);
    }

    public function actionLampSensorWorkerInfo()
    {
        $debug_flag=0;                                                                                                  //флаг отладки
        if($debug_flag==1) $errors[]="ОТЛАДКА ВКЛЮЧЕНА";
        $cache = Yii::$app->cache;                                                                                        //объявляем переменную для работы с кешем
        $network_id="";                                                                                                 //объявляем пустой network_id
        $network_idDBQuery = new Query;
        $post = Assistant::GetServerMethod();                                                                           //объявляем метод получения данных пост  взависимости от того как пришли данные гет или пост
        $errors = array();                                                                                              //объявляем массив ошибок
        $input_parameters=array();                                                                                      //объявляем массив входных параметров
        $items=array();                                                                                                 //объявляем массив результатов обработки на возврат во фронт
        if (isset($post['type_info']) and $post['type_info'] != "") {
            $type_info = $post['type_info'];
            switch ($type_info) {
                case '1':                                                                                               //выборка списка мест для параметра Местоположение (place) id = 122
                    if (isset($post['object_id']) and $post['object_id'] != "") {                                       //проверяем наличие в методе пост ключа работника
                        $worker_id = $post['object_id'];                                                                //переопределяем из переменной пост в переменную worker_id ключ работника
                        $input_parameters[] = "тип метода = ".$type_info;                                               //заполняем входные параметры для тестирования на всякий случай тип вызываемого метода
                        $input_parameters[] = "работник = ".$worker_id;                                                 //заполняем входные параметры для тестирования на всякий случай ключ работника
                        $items_without_net_id = (new Query())                                                           //запуск готовой вьюшки view_lamp_sensor_info_worker_info - возвращает историю всех сенсоров и воркеров
                        ->select([
                            'sensor_id',
                            'date_time',
                            'sensor_title'
                        ])
                            ->from('view_lamp_sensor_info_worker_info')
                            -> where('worker_id = '.$worker_id)
                            ->orderBy(['date_time' => SORT_ASC])
                            ->all();
                        //получаем для сенсоров сетевые идентификаторы
                        $i=0;
                        foreach($items_without_net_id as $item_without_net_id)
                        {
                            if ($cache->exists('SensorParameter_' . $item_without_net_id["sensor_id"] . '_1-88'))  //проверяем наличие данных в кеше по заданной шахте - людей которые зачекинились/зарядились в ламповой
                            {                                                                                           //1-88   -  1 справочный параметр, 88 - сетевой идентификатор
                                $sensor_parameters = $cache->get('SensorParameter_' . $item_without_net_id["sensor_id"] . '_1-88'); //получаем network_id из кеша параметров сенсора

                                $network_id=$sensor_parameters['handbook_value'] == "-1" ? "не задан" : $sensor_parameters['handbook_value'];                                       //получаем network_id из кеша

                                if($debug_flag==1) $errors[]=("------Start -------".$i);                                //отладка
                                if($debug_flag==1) $errors[]=("данные из кеша");
                                if($debug_flag==1) $errors[]=($network_id);
                                if($debug_flag==1) $errors[]=($item_without_net_id["sensor_id"]);
                                if($debug_flag==1) $errors[]=("-------");
                            }
                            else                                                                                        //если network_id нет в кеше, то запрашиваем данные из БД
                            {
                                $network_idDB =($network_idDBQuery)                                                     //запрос на поиск network_id  в базе данных. Возвращает последний нетворк айди привязанный к этому сенсору
                                ->select([
                                    'sensor_id',
                                    'network_id',
                                ])
                                    ->from('view_lamp_sensor_net_id')
                                    ->where('sensor_id = '.$item_without_net_id["sensor_id"])
                                    ->one();
                                if(!empty($network_idDB)) {                                                             //проверяем на наличие в БД хоть одной записи
                                    if($debug_flag==1) $errors[]=("------Start -------".$i);                            //отладка
                                    if($debug_flag==1) $errors[]=("данные из базы данных");
                                    if($debug_flag==1) $errors[]=($network_idDB["network_id"]);
                                    if($debug_flag==1) $errors[]=($network_idDB["sensor_id"]);
                                    if($debug_flag==1) $errors[]=("-------");
                                    $network_id = $network_idDB["network_id"];
                                }
                                else {                                                                                  //в том случае если его даже в базе данных нет, то должен хоть, что то вернуть
                                    $network_id = "не задан";
                                    if($debug_flag==1) $errors[]=("------Start -------".$i);                            //отладка
                                    if($debug_flag==1) $errors[]=("не задан сетевой айди для ".$item_without_net_id["sensor_id"]);
                                    if($debug_flag==1) $errors[]=("-------");
                                }
                            }
                            $items[$i]["sensor_id"]=$item_without_net_id["sensor_id"];                                  //формируем новый массив с сенсор_айди
                            $items[$i]["sensor_title"]=$item_without_net_id["sensor_title"];                            //формируем новый массив с сенсор_тайтл
                            $items[$i]["date_time"]=$item_without_net_id["date_time"];                                  //формируем новый массив с сенсор_дата тайм
                            $items[$i]["network_id"]=$network_id;                                                       //формируем новый массив с сетевым идентификатором
                            $i++;                                                                                       //увеличиваем счетчик
                        }
                    }
                    else
                    {
                        $errors[] = "код работника worker_id не задан";                                                 //проверка на входные параметры
                    }
                    break;
                case '2':
                    if (isset($post['object_id']) and $post['object_id'] != "") {                                       //проверяем наличие в методе пост ключа сенсора
                        $sensor_id = $post['object_id'];                                                                //переопределяем из переменной пост в переменную sensor_id ключ секнсора/лампы
                        $input_parameters[] = "тип метода = ".$type_info;                                               //заполняем входные параметры для тестирования на всякий случай тип вызываемого метода
                        $input_parameters[] = "работник = ".$sensor_id;                                                 //заполняем входные параметры для тестирования на всякий случай ключ сенсора/лампы
                        $items = (new Query())                                                                          //запуск готовой вьюшки view_lamp_sensor_info_worker_info - возвращает историю всех сенсоров и воркеров
                        ->select([
                            'worker_id',
                            'FIO',
                            'tabel_number',
                            'date_time'
                        ])
                            ->from('view_lamp_sensor_info_worker_info')
                            -> where('sensor_id = '.$sensor_id)
                            ->orderBy(['date_time' => SORT_ASC])
                            ->all();
                    }
                    else
                    {
                        $errors[] = "код сенсора sensor_id не задан";                                                   //проверка на входные параметры
                    }
                    break;
                default:
                    $errors[] = "Неизвестнй тип обработки type_info = ".$type_info;                                     //проверка на входные параметры
            }
        } else {
            $errors[] = "Не задан тип type_info";                                                                       //проверка на входные параметры
        }
        $result = array("errors" => $errors, "items" => $items,"input_parameters" =>$input_parameters);                 //формируем выходной массив на передачу на фронт энд
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            //передача данных на фронт энд
        Yii::$app->response->data = $result;
    }
}
