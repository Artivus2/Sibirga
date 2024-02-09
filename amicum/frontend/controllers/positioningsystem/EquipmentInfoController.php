<?php
/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\controllers\positioningsystem;
//ob_start();

use Exception;
use frontend\controllers\Assistant;
use frontend\controllers\handbooks\DepartmentController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\OrderRouteWorker;
use frontend\models\Place;
use frontend\models\Worker;
use Throwable;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\Response;

class EquipmentInfoController extends Controller
{
    // actionGetEquipmentsParameters    - Метод возврата данных последних по оборудованию из базы данных
    // EquipmentStatuses                - Метод возврата статусов оборудования
    // actionGetEquipmentList           - Метод возврата списка оборудования его местоположения и статуса
    // sortEquipmentList                - Метод сортировки списка оборудования по состояниям
    // RoundFloat                       - Метод округляет числа с плавающей точкой типа float, double
    // actionGetDepartments             - Метод получения списка департаментов

    const CHECK_WAIT = ['id' => 120, 'title' => "Ожидается проверка"];          // ожидается проверка
    const CHECK_DONE = ['id' => 121, 'title' => "Проверка проведена"];          // проверка проведена
    const CHECK_NOT = ['id' => 122, 'title' => "Нет данных о проверке"];        // нет данных о проверке

    const SITUATION_KIND_REASON = ['id' => 20, 'title' => "Проверка датчика"];  // проверка датчика как вид ситуации

    const STATIONARY_SENSOR = 116;                                              // тип типового объекта 116 стационарные датчики

    /**
     * @return string - объект возвращаемый на страницу со списком оборудования
     */
    public function actionIndex()
    {
        $status = $this->EquipmentStatuses();
        $this->view->registerJsVar('statuses', $status);
        return $this->render('index');
    }

    /**
     * /**
     * Название метода: actionEquipmentStatuses()
     * Метод возврата статусов оборудования
     * Входные параметры:
     * @return array - массив со статусами
     * @author Озармехр Одилов
     * Created date: on 24.12.2018 15:51
     */
    public function EquipmentStatuses(): array
    {
        $status[0] = ['id' => 1, 'title' => "Зарегистрировался/В ламповой"];
        $status[1] = ['id' => 2, 'title' => "Зарегистрировался/В шахте"];
        $status[2] = ['id' => 3, 'title' => "Разрядился/В шахте/Ошибка"];
        $status[3] = ['id' => 4, 'title' => "Разрядился/В ламповой"];

        return $status;
    }

    // actionGetEquipmentList - Метод возврата списка оборудования его местоположения и статуса
    // http://127.0.0.1/positioningsystem/equipment-info/get-equipment-list?mine_id=290
    public function actionGetEquipmentList()
    {
        $post = Assistant::GetServerMethod();                                                                           //получение данных со стороны фронтэнда
        $errors = array();                                                                                              //массив ошибок
        $warnings = array();                                                                                            //массив ошибок
        $session = Yii::$app->session;                                                                                  //инициализируем сессию
        $mine_id = $session['userMineId'];                                                                              //берем id шахты из текущей сессии
        $filter_place = ''; //фильтр по названию места
        $filter_status = ''; //фильтр по значению состояния 0/1/2
        $filter_search = ''; //фильтр по строке поиска


        if (isset($post['place']) && $post['place'] != '') { //фильтр по названию места
            $filter_place = $post['place'];
        }

        if (isset($post['state']) && $post['state'] != '') { //фильтр по значению состояния 0/1/2
            $filter_status = $post['state'];
        }

        if (isset($post['search']) && $post['search'] != null) //фильтр по строке поиска
        {
            $filter_search = $post['search'];

        }
        $equipmentCacheController = new \backend\controllers\cachemanagers\EquipmentCacheController();
        $equipments = $equipmentCacheController->getEquipmentMine($mine_id);                                            // получаем список сенсоров
        $equipment_parameters_list = array();                                                                           // иницилизируем массив для отправки на фронт
        $i = 0;
        if ($equipments)                                                                                                // если в кеше есть сенсоры
        {
            $places = Place::find()->all();
            $place_array = [];
            foreach ($places as $place) {
                $place_array[$place->id] = $place->title;
            }
//            print_r($place_array);
            foreach ($equipments as $equipment)                                                                         // для каждого найденного сенсоар получаем значения указанный параметров
            {
                if ($equipment['object_id'] != 119) {                                                                   // исключаем оборудование TORO
                    $equipment_parameters_list[$i]['id'] = $equipment['equipment_id'];                                  // записываем его sensor_id
                    $equipment_parameters_list[$i]['title'] = $equipment['equipment_title'];                            // записываемм его название
                    $equipment_parameters_list[$i]['object_id'] = $equipment['object_id'];                              // записываемм object_id
                    $equipment_parameters_list[$i]['current_time'] = \backend\controllers\Assistant::GetDateNow();
                    $equipment_id = $equipment['equipment_id'];                                                         // записываем в переменную sensor_id

                    /**************             ПОЛУЧАЕМ СТАТУС оборудования                          ***************/

                    $equipment_status = $equipmentCacheController->getParameterValue($equipment_id, 164, 3);//получаем статус текущего сенсора
                    if ($equipment_status != -1)                                                                        //если статус получили
                    {
                        switch ($equipment_status['value'])                                                             //на основе ее значение происходит конвертация данных в текст удобный для пользователя
                        {
                            case '0':
                                $equipment_parameters_list[$i]['state'] = 'Отключен / неисправен';
                                break;
                            case '1':
                                $equipment_parameters_list[$i]['state'] = 'Включен / исправен';
                                break;
                            default:
                                $equipment_parameters_list[$i]['state'] = 'Неизвестно';
                                break;
                        }
                        $equipment_parameters_list[$i]['state_date_time'] = $equipment_status['date_time'];             // записываем время статуса
                    } else {
                        $errors[] = "Для датчика с equipment_id = $equipment_id не найден параметр 164 - статус (нет такого ключа кэша)"; // если статуса нет для сенсора пишем ошибку
                        $equipment_parameters_list[$i]['state'] = null;                                                 // и в массив записываем для данного сенсора параметры статуса
                        $equipment_parameters_list[$i]['state_date_time'] = null;                                       // и время статуса null
                    }

                    /**************             ПОЛУЧАЕМ МЕСТОПОЛОЖЕНИЯ СЕНСОРА                      **************/
                    $equipment_place = $equipmentCacheController->getParameterValue($equipment_id, 122, 2);// получаем параметры местоположения текущего сенсора из кеша
                    if ($equipment_place !== FALSE)                                                                     // если местоположение нашли
                    {
                        $place_id = $equipment_place['value'];
                        $equipment_parameters_list[$i]['place_title'] = (isset($place_array[$place_id])) ? $place_array[$place_id] : null;
                        $equipment_parameters_list[$i]['place_id'] = $place_id;
                        $equipment_parameters_list[$i]['place_date_time'] = $equipment_place['date_time'];              // записываем в массив время местоположения
                    } else {
                        $equipment_parameters_list[$i]['place_id'] = null;
                        $equipment_parameters_list[$i]['place_title'] = null;                                           // если местоположения не нашли
                        $equipment_parameters_list[$i]['place_date_time'] = null;                                       // записываем в массив null
                        $warnings[] = "Для датчика с equipment_id = $equipment_id не найден параметр 122 - местоположение (нет такого ключа кэша)";//пишем ошибку
                    }
                    $i++;                                                                                               // увеличиваем счетик для массива
                }
            }
            // поиск по фильтрам и строке поиска
            if ($filter_place != '' || $filter_status != '' || $filter_search != '')                                    // если есть или фильтр или строка поиска
            {
                foreach ($equipment_parameters_list as $j => $equipment)                                                // начинаем перебирать массив
                {
                    $flag_delete = 1;                                                                                   // флаг удаления сенсора из массива если он не подходит по критериям
                    if ($filter_status != '' && $flag_delete == 1)                                                      // если есть фильтр по статусу и сенсор прошел первый фильтр
                    {
                        if ($filter_status == $equipment['state'])                                                      // если статус сенсора равен статусу фильтра
                        {
                            if ($filter_search != '')                                                                   // если есть строка поиска
                            {
                                $pos = strpos(mb_strtolower($equipment['place_title']), mb_strtolower($filter_search)); // смотрим вхождение строки в название местоположения
                                $pos2 = strpos(mb_strtolower($equipment['title']), mb_strtolower($filter_search));      // смотрим вхождение строки в название сенсора
                                if ($pos === false && $pos2 === false)                                                  // если совпадений нет не названии местоположения не названии сенсора
                                {
                                    $flag_delete = -1;                                                                  // флаг переводим на удаление текущего сенсора из массива
                                }
                            }
                        } else                                                                                          // иначе сенсор не прошел фильтр статуса
                        {
                            $flag_delete = -1;                                                                          // флаг переводим на удаление текущего сенсора из массива
                        }
                    }

                    if ($filter_place != '' && $flag_delete == 1)                                                       // если есть фильтр местоположения и сенсор прошел предыдущие фильтры
                    {
                        if ($equipment['place_title'] != $filter_place)                                                 // если местоположение фильтра не равно местоположению текущего сенсора
                        {
                            $flag_delete = -1;                                                                          // флаг переводим на удаление текущего сенсора из массива
                        } else                                                                                          // иначе
                        {
                            if ($filter_search != '')                                                                   // если есть строка поиска
                            {
                                $pos = strpos(mb_strtolower($equipment['place_title']), mb_strtolower($filter_search)); // смотрим вхождение строки в название местоположения
                                $pos2 = strpos(mb_strtolower($equipment['title']), mb_strtolower($filter_search));      // смотрим вхождение строки в название сенсора
                                if ($pos === false && $pos2 === false)                                                  // если совпадений нет не названии местоположения не названии сенсора
                                {
                                    $flag_delete = -1;                                                                  // флаг переводим на удаление текущего сенсора из массива
                                }
                            }
                        }
                    }

                    if ($filter_search != '' && ($filter_place == '' && $filter_status == ''))                          // если есть строка поиска и нет фильтров
                    {
                        $pos = strpos(mb_strtolower($equipment['place_title']), mb_strtolower($filter_search));         // смотрим вхождение строки в название местоположения
                        $pos2 = strpos(mb_strtolower($equipment['title']), mb_strtolower($filter_search));              // смотрим вхождение строки в название сенсора
                        if ($pos === false && $pos2 === false)                                                          // если совпадений нет не названии местоположения не названии сенсора
                        {
                            $flag_delete = -1;                                                                          // флаг переводим на удаление текущего сенсора из массива
                        }
                    }

                    if ($flag_delete == -1)                                                                             // если флаг удаления переключен в режим удаления сенсора из массива
                    {
                        unset($equipment_parameters_list[$j]);                                                          // удаляем текущий сенсор из массива
                    }
                }
                $equipment_parameters_list = array_values($equipment_parameters_list);                                  // занового индексируем массив
            }
        } else                                                                                                          // иначе нет сенсоров в кеше текущей шахты
        {
            $errors[] = 'Для кеша  с ключом EquipmentMine:' . $mine_id . ' не найден';                                  // пишем ошибку
        }

        // Сортировка списка БПД для выдачи
        usort($equipment_parameters_list, 'self::sortEquipmentList');

        $result = array('equipments' => $equipment_parameters_list, 'equipment_count' => count($equipment_parameters_list), 'errors' => $errors, 'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            // указываем формат данных ответа на запрос
        Yii::$app->response->data = $result;                                                                            // возвращаем данные на фронт
    }

    // sortEquipmentList - Метод сортировки списка оборудования по состояниям
    public static function sortEquipmentList($a, $b): int
    {
        $sortMap = array(
            'Отключен / неисправен' => 0,
            'Включен / исправен' => 1,
            'Неизвестно' => 3
        );

        if (!isset($a['state'])) {
            return 1;
        }

        if (!isset($b['state'])) {
            return 0;
        }

        if ($sortMap[$a['state']] === $sortMap[$b['state']]) {
            return 0;
        }
        return $sortMap[$a['state']] < $sortMap[$b['state']] ? -1 : 1;
    }

    /**
     * actionGetEquipmentsParameters - Метод возврата данных последних по оборудованию из базы данных
     * http://127.0.0.1/positioningsystem/equipment-info/get-equipments-parameters?equipment_id=2072
     */

    //вызываемую вьюшку исправил Якимов М.Н. 18.12.2018
    //

    public function actionGetEquipmentsParameters()
    {

        $post = Assistant::GetServerMethod();                                                                           //получение данных от ajax-запроса
        $sql_filter = '';                                                                                                 //переменная для создания фильтра в MySQL запросе
        $equipment_parameter_list_result = array();                                                                        //созадем пустой массив результирующих значений
        $errors = array();                                                                                              //массив ошибок для передачи во фронтэнж
        if (isset($post['equipment_id']) && $post['equipment_id'] != "") {
            $sql_filter .= ' equipment_id=' . $post['equipment_id'] . '';                                                     //создание фильтра для вьюшки  по конкретному сенсору, если сенсор не задан то возвращается пустой массив с ошибкой
            $flag_filter = 1;                                                                                             //условие фильтрациии есть, запрос может выполняться
        } else {
            $errors[] = "не задан конкретный equipment_id, запрос не выполнялся";                                             //запись массива ошибок для передачи на фронтэнд
            $flag_filter = 0;                                                                                             //обнуление флага фильтра для обработки случая когда не задан фильтр с фронтэнда
        }

        if ($flag_filter == 1) {
            try {
                $equipment_parameter_list = (new Query())//запрос напрямую из базы по вьюшке view_personal_areas
                ->select(
                    [
                        'parameter_id',
                        'parameter_title',      //название параметра справочного
                        'parameter_type_id',    //тип параметра конкретного
                        'unit_title',           //единицы измерения параметра
                        'date_time_work',       //время измерения или вычисления значения конкретного параметра
                        'value',                //значение измеренного или вычисленного конкретного параметра крайнее
                        'handbook_value',       //значение справочного конкретного параметра крайнее
                        'handbook_date_time_work'//время создания справочного конкретного параметра крайнее
                    ])
                    ->from(['view_equipment_parameter_value_detail_main'])//представление с крайними значениями конкретного параметра конкретного сенсора
                    ->where($sql_filter)
                    ->orderBy(['parameter_id' => SORT_DESC, 'parameter_type_id' => SORT_DESC])
                    ->all();
                if (!$equipment_parameter_list) {
                    $errors[] = "Запрос выполнился, нет данных по запрошенному сенсору в БД";                           //запрос не выполнился по той или иной причине
                } else {
                    $j = -1;                                                                                               //индекс создания результирующего запроса
                    $parameter_id_tek = 0;                                                                                //текущий параметер айди
                    $parameter_value_array = array();                                                                     //массив значений параметров по типам
                    $parameter_date_array = array();                                                                      //массив дат параметров по типам
                    $equipment_parameter_tek = array();                                                                      //списко текущих значений полей сенсора

                    foreach ($equipment_parameter_list as $equipment_parameter_row) {
                        if ($parameter_id_tek != $equipment_parameter_row['parameter_id']) {
                            if ($j != -1) {
                                $equipment_parameter_list_result[$j]['parameter_id'] = $equipment_parameter_tek['parameter_id'];
                                $equipment_parameter_list_result[$j]['parameter_title'] = $equipment_parameter_tek['parameter_title'];
                                $equipment_parameter_list_result[$j]['unit_title'] = $equipment_parameter_tek['unit_title'];
                                $equipment_parameter_list_result[$j]['value'] = $parameter_value_array;
                                $equipment_parameter_list_result[$j]['date_time'] = $parameter_date_array;
                            }

                            $j++;

                            $equipment_parameter_tek['parameter_id'] = $equipment_parameter_row['parameter_id'];
                            $equipment_parameter_tek['parameter_title'] = $equipment_parameter_row['parameter_title'];
                            $equipment_parameter_tek['unit_title'] = $equipment_parameter_row['unit_title'];

                            $type_parameter_id_tek = $equipment_parameter_row['parameter_type_id'];
                            $parameter_id_tek = $equipment_parameter_row['parameter_id'];

                            $parameter_value_array[0] = -1;                                                               //справочное значение
                            $parameter_value_array[1] = -1;                                                               //измеренное значение
                            $parameter_value_array[2] = -1;                                                               //вычисленное значение
                            $parameter_date_array[0] = "-1";                                                                //дата ввода справочного значения
                            $parameter_date_array[1] = "-1";                                                                //дата измерения значения
                            $parameter_date_array[2] = "-1";                                                                //дата вычисления значения

                            if ($type_parameter_id_tek == 2) {
                                if ($comp = (float)$equipment_parameter_row['value']) {                                          // проверка на тип данных
                                    $parameter_value_array[1] = $this->RoundFloat($equipment_parameter_row['value'], 2);
                                    if ($equipment_parameter_row['date_time_work'] != -1) $parameter_date_array[1] = date('H:i:s d.m.Y', strtotime($equipment_parameter_row['date_time_work']));
                                } else {
                                    $parameter_value_array[1] = $equipment_parameter_row['value'];
                                    if ($equipment_parameter_row['date_time_work'] != -1) $parameter_date_array[1] = date('H:i:s d.m.Y', strtotime($equipment_parameter_row['date_time_work']));
                                }
                            } elseif ($type_parameter_id_tek == 1) {
                                $parameter_value_array[0] = $equipment_parameter_row['handbook_value'];
                                if ($equipment_parameter_row['handbook_date_time_work'] != -1) $parameter_date_array[0] = date('H:i:s d.m.Y', strtotime($equipment_parameter_row['handbook_date_time_work']));
                            } elseif ($type_parameter_id_tek == 3) {
                                $parameter_value_array[2] = $equipment_parameter_row['value'];
                                if ($equipment_parameter_row['date_time_work'] != -1) $parameter_date_array[2] = date('H:i:s d.m.Y', strtotime($equipment_parameter_row['date_time_work']));
                            } else {
                                $errors[] = "Недокументированный тип параметра";
                            }
                        } else {
                            $type_parameter_id_tek = $equipment_parameter_row['parameter_type_id'];
                            $parameter_id_tek = $equipment_parameter_row['parameter_id'];
                            if ($type_parameter_id_tek == 2) {
                                $parameter_value_array[1] = $this->RoundFloat($equipment_parameter_row['value'], 2);
                                if ($equipment_parameter_row['date_time_work'] != -1) $parameter_date_array[1] = date('H:i:s d.m.Y', strtotime($equipment_parameter_row['date_time_work']));
                            } elseif ($type_parameter_id_tek == 1) {
                                $parameter_value_array[0] = $equipment_parameter_row['handbook_value'];
                                if ($equipment_parameter_row['handbook_date_time_work'] != -1) $parameter_date_array[0] = date('H:i:s d.m.Y', strtotime($equipment_parameter_row['handbook_date_time_work']));
                            } elseif ($type_parameter_id_tek == 3) {
                                $parameter_value_array[2] = $equipment_parameter_row['value'];
                                if ($equipment_parameter_row['date_time_work'] != -1) $parameter_date_array[2] = date('H:i:s d.m.Y', strtotime($equipment_parameter_row['date_time_work']));
                            } else {
                                $errors[] = "Недокументированный тип параметра";
                            }

                        }
                    }
                    //запись последнего значения по строкам
                    $equipment_parameter_list_result[$j]['parameter_id'] = $equipment_parameter_row['parameter_id'];
                    $equipment_parameter_list_result[$j]['parameter_title'] = $equipment_parameter_row['parameter_title'];
                    $equipment_parameter_list_result[$j]['unit_title'] = $equipment_parameter_row['unit_title'];
                    $equipment_parameter_list_result[$j]['value'] = $parameter_value_array;
                    $equipment_parameter_list_result[$j]['date_time'] = $parameter_date_array;
                }
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
        ArrayHelper::multisort($equipment_parameter_list_result, 'parameter_title', SORT_ASC);
        $result = array('equipment_list' => $equipment_parameter_list_result, 'errors' => $errors, 'flag_filter' => $flag_filter);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * RoundFloat - Метод округляет числа с плавающей точкой типа float, double
     * @param $input_value
     * @param $precision - точность (количестко округляемых чисел после запятой)
     * @return float|string
     */
    public function RoundFloat($input_value, $precision)
    {
        $res = "";
        if ($input_value)                                                                                               //проверяем пришла ли нам строка
        {
            $values_round = explode(",", $input_value);                                                         //разбиваем строку на подстроки
            for ($i = 0; $i < count($values_round); $i++)                                                               //проходим по всем строкам
            {
                if ($i != count($values_round) && $i != 0)                                                              //ставим запятую кроме начала и конца возвращаемой строки
                {
                    $res .= ", ";
                }
                if (is_float((float)$values_round[$i]))                                                                 //если входящее число имеет тип с плавающей точкой
                {
                    $value = round($values_round[$i], $precision);                                                      // то округляем число до нужных нам знаков
                    $res .= $value;
                } else {
                    $res .= $values_round[$i];                                                                          //добавляем тоже число что и было
                }
            }
        }
        return $res;
    }

    // actionGetDepartments - Метод получения списка департаментов
    public function actionGetDepartments()
    {
        $departments = (new Query())
            ->select('id, title')
            ->from('department')
            ->orderBy('title')
            ->all();
        Yii::$app->response->format = Response::FORMAT_JSON;                                                   // формат json
        Yii::$app->response->data = $departments;
    }


    /**
     * PlanFactCheckEquipments - метод получения графика проверок оборудования АГК
     * Описание:
     * Метод получает данные из нарядов за заданных промежуток времени по конкретному подразделению - это план
     * Затем метод получает данные из Журнала ситуаций, берутся значения датчиков по событиям превышений, которые были помечены оператором как проверка. берутся данные только за первую смену.
     * чтобы проверка состоялась  - необходимо, что бы в этот день в первую смену на этом датчике оператор отметил ситуацию о превышении как проверка датчика
     * @param null $data_post - входной объект метода
     * Входной объект:
     *      company_department_id   - ключ подразделения по которому получаем проверки оборудования
     *      date_time_start         - начало проведения проверок
     *      date_time_end           - окончание проведения проверок
     * Выходной объект:
     *         equipmentInspectionScheduleItem: {
     *              eis_id: null,                   - ключ журнала проверки оборудования
     *              equipment_id: null,             - ключ оборудования
     *              equipment_title: "",            - название оборудования
     *              data_check: "",                 - дата проверки оборудования
     *              shift_id: null,                 - ключ смены
     *              route_id: null,                 - ключ маршрута
     *              route_title: "",                - наименование маршрута
     *              place_id: null,                 - ключ места
     *              place_title: "",                - наименование места
     *              status_id: null,                - ключ статуса
     *              status_title: "",               - название статуса
     *              value_fact: null,               - фактическое значение
     *              statuses: {                     - статус проверки
     *                  status_id: null,                - ключ статуса
     *                  status_title: "",               - название статуса
     *                  date_time: "",                  - дата изменения статуса
     *                  worker_id: null                 - ключ работника изменившего статус
     *              },
     *              auditor: {                      - проверяющий оборудование
     *                  table_number: null,             - табельный номер работника
     *                  worker_id: null,                - ключ работника
     *                  full_name: "",                  - полное имя работника
     *                  position_id: null,              - ключ должности
     *                  position_title: "",             - название должности
     *              },
     *          },
     * @return array
     * @example http://127.0.0.1/read-manager-amicum?controller=positioningsystem\EquipmentInfo&method=PlanFactCheckEquipments&subscribe=&data={%22company_department_id%22:4029938,%22date_time_start%22:%222019-06-27%22,%22date_time_end%22:%222021-06-27%22}
     */
    public static function PlanFactCheckEquipments($data_post = NULL)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("PlanFactCheckEquipments");

        try {
            $log->addLog("Начало выполнение метода");

            /**
             * блок проверки наличия входных даных от readManager
             */
            if (!is_null($data_post) and $data_post != "") {
                $post = json_decode($data_post);
            } else {
                throw new Exception("Входной массив данных post не передан");
            }
            if (
                !property_exists($post, 'company_department_id') or
                !property_exists($post, 'date_time_start') or
                !property_exists($post, 'date_time_end') or
                $post->company_department_id == '' or
                $post->date_time_start == '' or
                $post->date_time_end == ''
            ) {
                throw new Exception("PlanFactCheckEquipments. Входные параметры не переданы");
            }

            $company_department_id = $post->company_department_id;
            $date_time_start = date("Y-m-d", strtotime($post->date_time_start));
            $date_time_end = date("Y-m-d", strtotime($post->date_time_end));

            $response = DepartmentController::FindDepartment($company_department_id);
            if ($response['status'] == 1) {
                $company_departments = $response['Items'];
            } else {
                $log->addLogAll($response);
                throw new Exception('Ошибка получения списка департаментов');
            }

            // eis- equipment inspection schedule
            $plan_eis = OrderRouteWorker::find()
                ->select('order_route_esp_json, order.date_time_create as date_time_create')
                ->innerJoin('order_place', 'order_place.id=order_route_worker.order_place_id')
                ->innerJoin('order', 'order_place.order_id=order.id')
                ->where(['shift_id' => 1])
                ->andWhere(["is not", 'order_route_esp_json', null])
                ->andWhere(['company_department_id' => $company_departments])
                ->andWhere([">=", 'date_time_create', $date_time_start])
                ->andWhere(["<=", 'date_time_create', $date_time_end])
                ->asArray()
                ->all();

//            $log->addData($plan_eis, 'plan_eis', __LINE__);
            $log->addLog("Получил плановый график проверки оборудования", count($plan_eis));

            if ($plan_eis) {
                $reports_esp = [];
                $workers_esp = [];
                foreach ($plan_eis as $json) {
                    $report_esp = json_decode(json_decode($json['order_route_esp_json']));
                    $report_esp->date_time_create = $json['date_time_create'];
                    $reports_esp[] = $report_esp;
                    $workers_esp[]=$report_esp->esp_worker_id;
                }

                $worker_handbook = Worker::find()->indexBy('id')->where(['id'=>$workers_esp])->asArray()->all();

                $log->addData($reports_esp, 'reports_esp', __LINE__);
                $date_now = Assistant::GetDateNow();

//            справочник должностей и работников
                $worker_position_handbook = (new Query())
                    ->select('
                    worker.id as worker_id,
                    position.id as position_id,
                    position.title as position_title
                ')
                    ->from('worker')
                    ->innerJoin('position', 'position.id=worker.position_id')
                    ->indexBy('worker_id')
                    ->all();
//            $log->addData($worker_position_handbook, '$worker_position_handbook', __LINE__);
                $log->addLog("Получил справочник должностей и работников", count($worker_position_handbook));

                $date_time_start = Assistant::GetEndShiftDateTime($date_time_start)['date_start'];
                $date_time_end = Assistant::GetEndShiftDateTime($date_time_end)['date_end'];

                $log->addData($date_time_start, '$date_time_start', __LINE__);
                $log->addData($date_time_end, '$date_time_end', __LINE__);

//                получаем фактическое состояние
                $fact_events = (new Query())
                    ->select('
                        object_title as sensor_title,
                        object_id,
                        parameter_id,
                        xyz,
                        object_type_id,
                        object.title as object_title,
                        event_journal.main_id as sensor_id,
                        DATE(event_journal.date_time) as event_date_time,
                        TIME(event_journal.date_time) as event_time,
                        event_journal.value as sensor_value
                    ')
                    ->from('situation_status')
                    ->innerJoin("(select situation_journal_id, max(date_time) as max_date_time from situation_status group by situation_journal_id) as `max_situation_status`",
                        'max_situation_status.situation_journal_id=situation_status.situation_journal_id and max_situation_status.max_date_time=situation_status.date_time')
                    ->innerJoin('situation_journal', 'situation_journal.id=situation_status.situation_journal_id')
                    ->innerJoin('event_journal_situation_journal', 'situation_journal.id=event_journal_situation_journal.situation_journal_id')
                    ->innerJoin('event_journal', 'event_journal.id=event_journal_situation_journal.event_journal_id')
                    ->innerJoin('object', 'event_journal.object_id=object.id')
                    ->where(['situation_status.kind_reason_id' => self::SITUATION_KIND_REASON['id']])
                    ->andWhere([">=", 'situation_journal.date_time', $date_time_start])
                    ->andWhere(["object.object_type_id" => self::STATIONARY_SENSOR])
                    ->andWhere(["<=", 'situation_journal.date_time', $date_time_end])
                    ->having([">=", 'event_time', "07:00:00"])
                    ->andHaving(["<=", 'event_time', "15:00:00"])
                    ->all();

                $log->addData($fact_events, '$fact_events', __LINE__);

                $sensor_values = [];
                foreach ($fact_events as $fact_event) {
                    $sensor_values[$fact_event['sensor_id']][$fact_event['event_date_time']] = $fact_event['sensor_value'];
                }

                $log->addData($sensor_values, '$sensor_values', __LINE__);

                foreach ($reports_esp as $report_esp) {

                    $position_id = null;
                    $position_title = "";

                    if (isset($worker_position_handbook[$report_esp->esp_worker_id])) {
                        $position_id = $worker_position_handbook[$report_esp->esp_worker_id]['position_id'];
                        $position_title = $worker_position_handbook[$report_esp->esp_worker_id]['position_title'];
                    }
                    $log->addData($report_esp->date_time_create, '$current_date_time', __LINE__);
                    foreach ($report_esp->places as $place) {
                        $count_record++;
                        if ($place->sensor_id) {
                            $current_date_time = date("Y-m-d", strtotime($report_esp->date_time_create));

                            if (isset($sensor_values[$place->sensor_id][$current_date_time])) {
                                $status = self::CHECK_DONE;
                                $sensor_value = $sensor_values[$place->sensor_id][$current_date_time];
                            } else {
                                $sensor_value = $place->sensor_value;
                                if ($date_now < Assistant::GetDateTimeByShift($report_esp->date_time_create, $report_esp->shift_id)['date_end']) {
                                    $status = self::CHECK_WAIT;
                                } else {
                                    $status = self::CHECK_NOT;
                                }
                            }

                            $eises[] = [
                                'eis_id' => null,
                                'equipment_id' => $place->sensor_id,
                                'equipment_title' => $place->kind_place_title,
                                'data_check' => $report_esp->date_time_create,
                                'shift_id' => $report_esp->shift_id,
                                'route_id' => $report_esp->route_template_id,
                                'route_title' => $report_esp->route_template_title,
                                'place_id' => $place->place_id,
                                'place_title' => $place->place_title,
                                'status_id' => $status['id'],
                                'status_title' => $status['title'],
                                'value_fact' => $sensor_value,
                                'statuses' => (object)array(),
                                'auditor' => [
                                    'tabel_number' => isset($worker_handbook[$report_esp->esp_worker_id]) ? $worker_handbook[$report_esp->esp_worker_id]['tabel_number'] : " - ",
                                    'worker_id' => $report_esp->esp_worker_id,
                                    'full_name' => $report_esp->esp_full_name,
                                    'position_id' => $position_id,
                                    'position_title' => $position_title,
                                ],
                            ];
                        }
                    }
                }
            }

            if (isset($eises)) {
                $result = $eises;
            } else {
                $result = (object)array();
            }

            $log->addLog("Окончание выполнения метода", $count_record);
            /** Метод окончание */

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(['Items' => $result], $log->getLogAll());
    }
}
