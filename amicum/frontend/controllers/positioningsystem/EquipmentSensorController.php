<?php
/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\positioningsystem;

//ob_start();

use frontend\controllers\Assistant;
use frontend\models\EquipmentParameter;
use frontend\models\EquipmentParameterSensor;
use Yii;
use yii\db\Query;
use yii\web\Controller;
use yii\web\Response;

class EquipmentSensorController extends Controller
{
    /** Контроллер для страницы Привязка метки к оборудованию
     * Class EquipmentSensorController
     * @package app\controllers
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /** Функция выдачи на фронт списка всего оборудования
     * Принимает методом POST параметр search для выдачи списка оборудования с учетом поиска (задается опционально)
     * Created by: Курбанов И. С. on 18.04.2019
     */
    public function actionGetEquipmentsSensors()
    {
        $post = Assistant::GetServerMethod();                                                                           //объявляем переменную для хранения массива принмаемых с фронта данных, может быть как POST, а может быть как GET
        $equipments_sensor = array();                                                                                   //объявляем пустой массив для хранения списка оборудования
        if (isset($post['search'])) {                                                                                   //если передали параметры search для поиска конкретного оборудования
            $sql_query = (string)$post['search'];                                                                       //записываем в переменную $sql_query преобразованное в строку значение параметра $post['search']
            $equipments_sensor = self::getEquipmentsSensors($sql_query);                                                //записываем в массив $equipments_sensor список оборудования, вызвав функцию получения списка оборудования из view БД getEquipmentsSensors
        }

        Yii::$app->response->format = Response::FORMAT_JSON;                                                            //устанавливаем формат отправляемых данных как json
        Yii::$app->response->data = $equipments_sensor;                                                                 //отправляем на фронт массив оборудования
        unset($equipments_sensor);                                                                                      //очищаем переменную, освободив область памяти, которую занимал массив
    }

    /**Функция получения списка оборудования из БД из view view_all_equipments_sensors_metka
     * @param string $sql_query (string) - условие выборки данных, по умолчанию равен пустой строке
     * @return array массив всего оборудования
     * Created by: Курбанов И. С. on 18.04.2019
     */
    public static function getEquipmentsSensors($sql_query = "")
    {
        $sql_filter = "";                                                                                               //объявляем переменную для хрвнения строки с фильтром
        if ($sql_query != "") {                                                                                         //если условие поиска было задано
            $sql_filter = "equipment_title like '%$sql_query%' or inventory_number like '%$sql_query%'"                 //записываем условия фильтра в переменную sql_filter, где указываем, по каким поляи производить поиск
                . "or sensor_title like '%$sql_query%' or factory_number like '%$sql_query%'"                           //совпадение подстроки ищется по следующим полям: наименование оборудования, инвентарный номер, наименование сенсора,
                . "or place_title like '%$sql_query%' or department_title like '%$sql_query%'";                         //наименование выработки, заводской номер и наименование подразщделения
        }
        return (new Query())//возвращаем массив оборудования с учетом поиска
        ->select([                                                                                                      //пишем запрос на выборку данных из вьшки view_all_equipments_sensors_metka с учетом поиска
            'equipment_id',
            'equipment_title',
            'inventory_number',
            'sensor_id',
            'sensor_title',
            'place_title',
            'factory_number',
            'department_title'
        ])
            ->from('view_all_equipments_sensors_metka')
            ->where($sql_filter)
            ->andWhere('object_id!=119')                                                                        // исключаем оборудование ТОРО
            ->all();
    }

    /**Функция отвязки метки у оборудования
     * Created by: Курбанов И. С. on 18.04.2019
     */
    public function actionUnbindSensor()
    {
        $post = Assistant::GetServerMethod();                                                                           //объявляем переменную для хранения массива принмаемых с фронта данных, может быть как POST, а может быть как GET
        $equipments = array();                                                                                          //объявляем пустой массив для хранения списка оборудования
        $errors = array();                                                                                              //объявляем пустой массив для хранения ошибок, которые могут возникнуть при запросе в БД
        if (isset($post['equipment_id']) and $post['equipment_id'] != "") {                                             //если был передан идентификатор оборудования equipment_id, и он не равен пустой строке
            $equipmentId = (int)$post['equipment_id'];                                                                  //записываем в переменную $equipmnentId преобразованное к числу значение переменной $post['equipment_id']
            $equipmentParameter = EquipmentParameter::findOne(['equipment_id' => $equipmentId,                          //объявляем переменную для хранения привязи параметра Местоположеие (XYZ) с тдентификатором  83
                'parameter_id' => 83, 'parameter_type_id' => 2]);                                                       //с типом параметра Измеряемый с идентификатором 2
            if ($equipmentParameter) {                                                                                  //если есть привязка в базе
                $equipmentParameterSensor = new EquipmentParameterSensor();                                             //создаем экземпляр класса модели EquipmentParameterSensor, EquipmentParameterSensor - таблица в БД, в которой храним все привязки сенсров к оборудованию
                $equipmentParameterSensor->equipment_parameter_id = $equipmentParameter->id;                            //в поле equipment_parameter_id записываем идентификатор привязки параметра 83 к оборудованию из переменной $equipmentParameter
                $equipmentParameterSensor->sensor_id = -1;                                                              //записываем в поле sensor_id значение -1, тем самым указываем, что метка была отвязана
                $equipmentParameterSensor->date_time = date('Y-m-d H:i:s');                                       //записываем в поле date_time текущее время с точностью до секунды
                if (!$equipmentParameterSensor->save()) {                                                               //если не удалось сохранить новую запись по отвязке метки
                    $errors[] = "Не удалось отвязать метку";                                                            //то записываем в массив ошибок текст ошибки, что не удалоь отвязяать метку
                } else {                                                                                                //иначе
                    (new \backend\controllers\cachemanagers\EquipmentCacheController())->delSensorEquipment($equipmentId);
                    if (isset($post['search'])) {                                                                       //если передан параметр поиска
                        $equipments = self::getEquipmentsSensors((string)$post['search']);                              //в переменную $equipments записываем результат вызова функции получения списка оборудования
                    }
                }

            } else {
                $errors[] = "Нету привязки метки к оборудованию";                                                       //иначе записываем в массив ошибок текст ошибки о том, что нет привязки к оборудованию
            }
        } else {
            $errors[] = "Не передан идентификатор оборудования на сервер";                                              //иначе записываем в массив ошибок текст ошибки о том, что нет привязки к оборудованию
        }
        $result = array('equipments' => $equipments, 'errors' => $errors);
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            //устанавливаем формат отправляемых данных как json
        Yii::$app->response->data = $result;                                                                            //отправляем на фронт массив оборудования и массив ошибок
        unset($result);                                                                                                 //очищаем переменную, освободив область памяти, которую занимал массив
    }
}
