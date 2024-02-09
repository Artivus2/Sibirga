<?php

namespace frontend\controllers\positioningsystem;
//ob_start();

use backend\controllers\cachemanagers\SensorCacheController;
use frontend\controllers\Assistant;
use frontend\controllers\handbooks\PlaceController;
use frontend\models\Place;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\web\Response;


class SensorInfoController extends \yii\web\Controller
{
    public function actionIndex()
    {
        $session = Yii::$app->session;
        $mine_id = $session['userMineId'];
        return $this->render('index', [
            'mine' => $mine_id
        ]);
    }

    /**
     * Метод возвращает сведения о всех сенсорах, что есть в кеше для заданной шахты для страницы Контроль объектов АС
     * Метод так же принимает значения фильтров и строки поиска и отфильтровывает данные
     * Необязательные параметры(фильтры и строка поиска):
     * $post['up_to_date'] - фильтр актуальности данных (0;1)
     * $post['place'] - фильтр по названию места
     * $post['status'] - фильтр по значению состояния (0;1:2)
     * $post['search'] - фильтр по строке поиска
     * $mine_id берется из текущей сессии
     * http://192.168.1.5/sensor-info/get-data
     * http://192.168.1.5/sensor-info/get-data?status=&place=&up_to_date=&search=
     * Created by: Фидченко М.В. on 10.12.2018 17:57
     */
    public function actionGetData()
    {
        $post = Assistant::GetServerMethod();                                                                           //получение данных со стороны фронтэнда
        $errors = array();                                                                                              //массив ошибок
        $errors_debug = array();                                                                                              //массив ошибок
        $session = Yii::$app->session;                                                                                  //инициализируем сессию
        $mine_id = $session['userMineId'];                                                                              //берем id шахты из текущей сессии
        $filter_up_to_date = '';                                                                                        //фильтр актуальности данных 0/1
        $filter_place = '';                                                                                             //фильтр по названию места
        $filter_status = '';                                                                                            //фильтр по значению состояния 0/1/2
        $filter_search = '';                                                                                            //фильтр по строке поиска
        $filter_object_id = '';
        if (isset($post['up_to_date']) && $post['up_to_date'] != '') {                                                     //фильтр актуальности данных 0/1
            $filter_up_to_date = $post['up_to_date'];
        }

        if (isset($post['place']) && $post['place'] != '') {                                                               //фильтр по названию места
            $filter_place = $post['place'];
        }

        if (isset($post['status']) && $post['status'] != '') {                                                             //фильтр по значению состояния 0/1/2
            $filter_status = $post['status'];
        }
        if (isset($post['object_id']) && $post['object_id'] != '') {                                                             //фильтр по значению состояния 0/1/2
            $filter_object_id = $post['object_id'];
        }

        if (isset($post['search']) && $post['search'] != null)                                                        //фильтр по строке поиска
        {
            $filter_search = $post['search'];

        }
        $sensorCacheController = new SensorCacheController();
        $sensors = $sensorCacheController->getSensorMineHash($mine_id);                                                        //получаем список сенсоров
        $sensors_parameters_list = array();                                                                             //иницилизируем массив для отправки на фронт
        $i = 0;


        if ($sensors !== FALSE)                                                                                               //если в кеше есть сенсоры
        {
            $places = Place::find()->all();
            $place_array = [];
            foreach ($places as $place) {
                $place_array[$place->id] = $place->title;
            }
            $network_ids = (new Query())
                ->select(['value','sensor_id'])
                ->from('view_initSensorParameterHandbookValue')
                ->where(['parameter_id' => 88, 'parameter_type_id' => 1])
                ->all();
            foreach ($network_ids as $network_id)
            {
                $hand_net_ids[$network_id['sensor_id']] = $network_id['value'];
            }
            unset($network_ids);
            foreach ($sensors as $sensor)                                                                               // для каждого найденного сенсоар получаем значения указанный параметров
            {
                if ($sensor['object_id'] != 49)                                                                          //если текущий сенсор не БПД(БПД не должны выводится на данной странице)
                {

                    $sensors_parameters_list[$i]['sensor_id'] = $sensor['sensor_id'];                                   //записываем его sensor_id
                    $sensors_parameters_list[$i]['sensor_title'] = $sensor['sensor_title'];                             //записываемм его название
                    $sensors_parameters_list[$i]['object_type_id'] = $sensor['object_type_id'];                         //записываемм его типовой объект
                    $sensors_parameters_list[$i]['object_id'] = $sensor['object_id'];                                   //записываемм его объект
                    $sensors_parameters_list[$i]['network_id'] = isset($hand_net_ids[$sensor['sensor_id']]) ? $hand_net_ids[$sensor['sensor_id']] : null; //получем network_id по sensor_id
                    $sensor_id = $sensor['sensor_id'];                                                                  //записываем в переменную sensor_id

                    $sensor_object_type_id = $sensor['object_type_id'];                                                 //получаем object_type_id текущего сенсора
                    $parameter_type_id = sensorCacheController::isStaticSensor($sensor_object_type_id);
                    /**************             ПОЛУЧАЕМ СТАТУС СЕНСОРА                          ***************/
                    $sensor_status = $sensorCacheController->getParameterValueHash($sensor_id, 164, 3);//получаем статус текущего сенсора
                    if ($sensor_status)                                                                           //если статус получили
                    {
                        switch ($sensor_status['value'])                                                                 //на основе ее значение происходит конвертация данных в текст удобный для пользователя
                        {
                            case '0':
                                $sensors_parameters_list[$i]['status'] = 'Отключен / неисправен';
                                break;
                            case '1':
                                $sensors_parameters_list[$i]['status'] = 'Включен / исправен';
                                break;
                            case '2':
                                $sensors_parameters_list[$i]['status'] = 'Включен / работает от аккумулятора';
                                break;
                            default:
                                $sensors_parameters_list[$i]['status'] = 'Неизвестно';
                                break;
                        }
                        //TODO сделал проверку на правильность даты. Скорее всего несамое лучшее решение, так как в базе может быть не только -1
                        if ($sensor_status['date_time'] != -1)                                                      //если дата корректная
                        {
                            $sensors_parameters_list[$i]['status_date_time'] = $sensor_status['date_time'];            //записываем время статуса
                            $date_time = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' - 600 sec'));                   //получаем дату равную текущую дату минус 800 секунд
                            if (strtotime($sensor_status['date_time']) >= strtotime($date_time))                        //если дата статуса больше или равна дате выше
                            {
                                $sensors_parameters_list[$i]['up_to_date'] = 1;                                             //тогда статус актуально
                            } else {
                                $sensors_parameters_list[$i]['up_to_date'] = 0;                                             //иначе данные не актуальны
                            }
                        } else                                                                                            //если не верный формат даты
                        {
                            $sensors_parameters_list[$i]['up_to_date'] = 0;                                             //иначе данные не актуальны
                        }
                    } else {
                        $errors_debug[] = "Для датчика с sensor_id = $sensor_id не найден параметр 164 - статус (нет такого ключа кэша)"; //если статуса нет для сенсора пишем ошибку
                        $sensors_parameters_list[$i]['status'] = '-1';                                                  //и в массив записываем для данного сенсора параметры статуса
                        $sensors_parameters_list[$i]['up_to_date'] = '';
                        $sensors_parameters_list[$i]['status_date_time'] = '-1';                                        // и время статуса null
                        $sensors_parameters_list[$i]['up_to_date'] = 0;
                    }

                    /**************             ПОЛУЧАЕМ МЕСТОПОЛОЖЕНИЯ СЕНСОРА                      **************/
                    $sensor_place = $sensorCacheController->getParameterValueHash($sensor_id, 122, $parameter_type_id);//получаем параметры местоположения текущего сенсора из кеша
                    if ($sensor_place !== FALSE)                                                                            // если местоположение нашли
                    {
                        $sensors_parameters_list[$i]['place_date_time'] = $sensor_place['date_time']; //записываем в массив null
                        $sensors_parameters_list[$i]['date_time'] = $sensor_place['date_time'];
                        $place_id = $sensor_place['value'];
                        $sensors_parameters_list[$i]['place_id'] = $place_id; //записываем в массив null
                        $sensors_parameters_list[$i]['place_title'] = (isset($place_array[$place_id])) ? $place_array[$place_id] : null; //записываемм его объект
                    } else {
                        $sensors_parameters_list[$i]['place_title'] = null;                                             //если местоположения не нашли
                        $sensors_parameters_list[$i]['place_date_time'] = null;                                         //записываем в массив null
                        $sensors_parameters_list[$i]['date_time'] = null;
                        $errors_debug[] = "Для датчика с sensor_id = $sensor_id не найден параметр 122 - местоположение (нет такого ключа кэша)";//пишем ошибку
                    }

                    /**************             ПОЛУЧАЕМ ЗАРЯД СЕНСОРА(ТОЛЬКО ДЛЯ OBJECT_ID = 46,105,47,48,104)                          ***************/
                    if ($sensor['object_id'] == 46 or $sensor['object_id'] == 105)                                      //если сенсор Узел связи C или Узел связи C прочее то параметр заряда батареи 447(Процент уровня заряда батареи узла связи)
                    {
//                        $sensor_charge = CacheGetterController::GetSensorParameterValues($memCache, $sensor_id, 2, 447, '-');//получаем заряд текущего сенсора
                        $sensor_charge = $sensorCacheController->getParameterValueHash($sensor_id, 447, 2);//получаем заряд текущего сенсора
                        if ($sensor_charge !== FALSE) {
                            $sensors_parameters_list[$i]['charge_value'] = $sensor_charge['value'];                     //записываем в массив значение заряда
                            $sensors_parameters_list[$i]['charge_date_time'] = explode('.', $sensor_charge['date_time'])[0];        //записываем в массив дату заряда.При этом отрезаем миллисекунды
                        } else {
                            $sensors_parameters_list[$i]['charge_value'] = '-1';                                        //если не нашли значение то пишем -1;
                            $sensors_parameters_list[$i]['charge_date_time'] = null;                                    //записываем в массив дату null
                            $errors_debug[] = "Для датчика с sensor_id = $sensor_id не найден параметр 447 - заряд батареи (нет такого ключа кэша)";//пишем ошибку
                        }
                    } elseif ($sensor['object_id'] == 47 or $sensor['object_id'] == 48 or $sensor['object_id'] == 104)    //если сенсор Светильник ЛУЧ-4 или Коммуникатор или Метка Strata прочее то параметр заряда батареи 448(Процент уровня заряда батареи метки)
                    {
                        $sensor_charge = $sensorCacheController->getParameterValueHash($sensor_id, 448, 2);//получаем заряд текущего сенсора
                        if ($sensor_charge !== FALSE) {
                            $sensors_parameters_list[$i]['charge_value'] = $sensor_charge['value'];                     //записываем в массив значение заряда
                            $sensors_parameters_list[$i]['charge_date_time'] = explode('.', $sensor_charge['date_time'])[0];        //записываем в массив дату заряда. При этом отрезаем миллисекунды
                        } else {
                            $sensors_parameters_list[$i]['charge_value'] = '-1';                                        //если не нашли значение то пишем -1;
                            $sensors_parameters_list[$i]['charge_date_time'] = null;                                    //записываем в массив дату null
                            $errors_debug[] = "Для датчика с sensor_id = $sensor_id не найден параметр 447 - заряд батареи (нет такого ключа кэша)";//пишем ошибку
                        }
                    } else                                                                                                //иначе те сенсоры у которых нет вприципе заряда батаерии пишем в значение -1
                    {
                        $sensors_parameters_list[$i]['charge_value'] = '-1';                                            //записываем в значение заряда равное -1
                        $sensors_parameters_list[$i]['charge_date_time'] = null;                                         //записываем в массив дату null
                    }
                    $i++;                                                                                               //увеличиваем счетик для массива
                }
            }
            unset($hand_net_ids);

            if ($filter_status != '')                                                                                    //если фильтр по статусу есть то переводим фильтр в текст
            {
                switch ($filter_status) {
                    case '0':                                                                                           //на основе ее значение происходит конвертация данных в текст удобный для пользователя
                        $filter_status = 'Отключен / неисправен';
                        break;
                    case '1':
                        $filter_status = 'Включен / исправен';
                        break;
                    case '2':
                        $filter_status = 'Включен / работает от аккумулятора';
                        break;
                    default:
                        $filter_status = 'Неизвестно';
                        break;
                }
            }
//            print_r($sensors_parameters_list);
            // поиск по фильтрам и строке поиска
            if ($filter_up_to_date !== '' || $filter_place !== '' || $filter_status !== '' || $filter_search !== '' || $filter_object_id !== '')         //если есть или фильтр или строка поиска
            {
                foreach ($sensors_parameters_list as $sensor)                                                           //начинаем перебирать массив
                {
                    $flag_delete = 1;                                                                                   //флаг удаления сенсора из массива если он не подходит по критериям
                    if ($filter_object_id !== '')                                                                        //если есть фильтр по актуальности
                    {
//                        print_r($sensor);
                        if ($sensor['object_id'] === $filter_object_id)                                                 //если актуальность равна актуальности сенсора
                        {
                            $flag_delete = 1;                                                                           //флаг переводим в не удалять
                            if ($filter_search !== '')                                                                    //если есть строка поиска
                            {
                                $pos = strpos(mb_strtolower($sensor['place_title']), mb_strtolower($filter_search));                                  //смотрим вхождение строки в название местоположения
                                $pos2 = strpos(mb_strtolower($sensor['sensor_title']), mb_strtolower($filter_search));                                //смотрим вхождение строки в название сенсора
                                if ($pos === false && $pos2 === false)                                                 //если совпадений нет не названии местоположения не названии сенсора
                                {
                                    $flag_delete = -1;                                                                  //флаг переводим на удаление текущего сенсора из массива
                                }
                            }
                        } else                                                                                            //если сенсор не подходит по актуальности
                        {
                            $flag_delete = -1;                                                                          //флаг переводим на удаление текущего сенсора из массива
                        }
                    }
                    if ($filter_up_to_date != '')                                                                        //если есть фильтр по актуальности
                    {
//                        print_r($sensor);
                        if ($sensor['up_to_date'] == $filter_up_to_date)                                                 //если актуальность равна актуальности сенсора
                        {
                            $flag_delete = 1;                                                                           //флаг переводим в не удалять
                            if ($filter_search != '')                                                                    //если есть строка поиска
                            {
                                $pos = strpos(mb_strtolower($sensor['place_title']), mb_strtolower($filter_search));                                  //смотрим вхождение строки в название местоположения
                                $pos2 = strpos(mb_strtolower($sensor['sensor_title']), mb_strtolower($filter_search));                                //смотрим вхождение строки в название сенсора
                                if ($pos === false && $pos2 === false)                                                 //если совпадений нет не названии местоположения не названии сенсора
                                {
                                    $flag_delete = -1;                                                                  //флаг переводим на удаление текущего сенсора из массива
                                } else                                                                                    //иначе
                                {
//                                    $sensor['place_title'] = Assistant::MarkSearched($filter_search, $sensor['place_title']);//подсвечиваем вхождение строки в название местоположения
//                                    $sensor['sensor_title'] = Assistant::MarkSearched($filter_search, $sensor['sensor_title']);//подсвечиваем вхождение строки в название сенсора
                                }
                            }
                        } else                                                                                            //если сенсор не подходит по актуальности
                        {
                            $flag_delete = -1;                                                                          //флаг переводим на удаление текущего сенсора из массива
                        }
                    }
                    if ($filter_status != '' && $flag_delete == 1)                                                      //если есть фильтр по статусу и сенсор прошел первый фильтр
                    {
                        if ($filter_status == $sensor['status'])                                                         //если статус сенсора равен статусу фильтра
                        {
                            if ($filter_search != '')                                                                    //если есть строка поиска
                            {
                                $pos = strpos(mb_strtolower($sensor['place_title']), mb_strtolower($filter_search));    //смотрим вхождение строки в название местоположения
                                $pos2 = strpos(mb_strtolower($sensor['sensor_title']), mb_strtolower($filter_search));                                //смотрим вхождение строки в название сенсора
                                if ($pos === false && $pos2 === false)                                                 //если совпадений нет не названии местоположения не названии сенсора
                                {
                                    $flag_delete = -1;                                                                  //флаг переводим на удаление текущего сенсора из массива
                                } else                                                                                     //иначе
                                {
//                                    $sensor['place_title'] = Assistant::MarkSearched($filter_search, $sensor['place_title']);//подсвечиваем вхождение строки в название местоположения
//                                    $sensor['sensor_title'] = Assistant::MarkSearched($filter_search, $sensor['sensor_title']);//подсвечиваем вхождение строки в название сенсора
                                }
                            }
                        } else                                                                                            //иначе сенсор не прошел фильтр статуса
                        {
                            $flag_delete = -1;                                                                          //флаг переводим на удаление текущего сенсора из массива
                        }
                    }

                    if ($filter_place != '' && $flag_delete == 1)                                                       //если есть фильтр местоположения и сенсор прошел предыдущие фильтры
                    {
                        if ($sensor['place_title'] != $filter_place)                                                     //если местоположение фильтра не равно местоположению текущего сенсора
                        {
                            $flag_delete = -1;                                                                          //флаг переводим на удаление текущего сенсора из массива
                        } else                                                                                            //иначе
                        {                                                                        //не удаляем текущий сенсор
                            if ($filter_search != '')                                                                    //если есть строка поиска
                            {
                                $pos = strpos(mb_strtolower($sensor['place_title']), mb_strtolower($filter_search));    //смотрим вхождение строки в название местоположения
                                $pos2 = strpos(mb_strtolower($sensor['sensor_title']), mb_strtolower($filter_search));  //смотрим вхождение строки в название сенсора
                                if ($pos === false && $pos2 === false)                                                  //если совпадений нет не названии местоположения не названии сенсора
                                {
                                    $flag_delete = -1;                                                                  //флаг переводим на удаление текущего сенсора из массива
                                }
                            }
                        }
                    }
                    if ($filter_search !== '' && ($filter_up_to_date === '' && $filter_place === '' && $filter_status === '' && $filter_object_id === ''))//если есть строка поиска и нет фильтров
                    {
                        $pos = strpos(mb_strtolower($sensor['place_title']), mb_strtolower($filter_search));             //смотрим вхождение строки в название местоположения
                        $pos2 = strpos(mb_strtolower($sensor['sensor_title']), mb_strtolower($filter_search));          //смотрим вхождение строки в название сенсора
                        if ($pos === false && $pos2 === false)                                                         //если совпадений нет не названии местоположения не названии сенсора
                        {
                            $flag_delete = -1;                                                                          //флаг переводим на удаление текущего сенсора из массива
                        } else                                                                                             //иначе
                        {
                            $flag_delete = 1;
                        }
                    }
                    if ($flag_delete == -1)                                                                              //если флаг удаления переключен в режим удаления сенсора из массива
                    {
                        $key = array_search($sensor, $sensors_parameters_list);                                         //ищем ключ текущего сенсора в массиве
                        unset($sensors_parameters_list[$key]);                                                          //удаляем текущий сенсор из массива
                    }
                }
                if (empty($sensors_parameters_list) === TRUE) {
                    $errors[] = 'По заданному условию ничего не найдено';
                } else {
                    $sensors_parameters_list = array_values($sensors_parameters_list);                                      //занового индексируем массив
                }
            }
        } else                                                                                                            //иначе нет сенсоров в кеше текущей шахты
        {
            $errors[] = 'Нет данных с списке сенсоров по шахте в кэше SensorMine';                                         //пишем ошибку
        }
        ArrayHelper::multisort($sensors_parameters_list, ['object_type_id', 'object_id'], SORT_ASC);
        $result = array('errors' => $errors, 'model' => $sensors_parameters_list, 'debug_info' => $errors_debug);                                         //формируем массив для фронта
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            // формат json
        Yii::$app->response->data = $result;
    }


    public function actionGetPlaces()
    {
        $places = PlaceController::GetPlaces();
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            // формат json
        Yii::$app->response->data = $places;
    }

    public function actionGetSensorTypicalObjects()
    {
        $objects = (new Query())
            ->select('id, title')
            ->from('object')
            ->where('object_type_id in (95, 12, 22)')
            ->andWhere('id != 49')
            ->orderBy('title ASC')
            ->all();
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $objects;
    }
}
