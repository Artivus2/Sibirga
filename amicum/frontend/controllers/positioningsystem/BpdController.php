<?php

namespace frontend\controllers\positioningsystem;
//ob_start();

use backend\controllers\cachemanagers\SensorCacheController;
use frontend\controllers\Assistant;
use frontend\models\Place;
use Yii;
use yii\db\Query;
use yii\web\Response;

class BpdController extends \yii\web\Controller
{
    public function actionIndex()
    {
        $places = (new Query())
            ->select('title')
            ->from('place')
            ->orderBy('title ASC')
            ->all();
        return $this->render('index', [
            'places' => $places
        ]);
    }

    /**
     * Метод возвращает сведения о всех БПД, что есть в кеше для заданной шахты для страницы Контроль БПД-3
     * Метод так же принимает значения фильтров и строки поиска и отфильтровывает данные
     * Необязательные параметры(фильтры и строка поиска):
     * $post['charge'] - фильтр Уровень заряда батареи
     * $post['place'] - фильтр по названию места
     * $post['state'] - фильтр по значению состояния (0;1:2)
     * $post['search'] - фильтр по строке поиска
     * $mine_id берется из текущей сессии
     * Created by: Фидченко М.В. on 17.12.2018 16:19
     */
    public function actionGetMainBpdDataCache()
    {
        try {
            $post = Assistant::GetServerMethod();                                                                           //получение данных со стороны фронтэнда
            $errors = array();                                                                                              //массив ошибок
            $errors_debug = array();                                                                                              //массив ошибок
            $sensors_parameters_list = array();                                                                                              //массив ошибок
            $memCache = Yii::$app->cache;                                                                                  //инизиализируем кеш
            $session = Yii::$app->session;                                                                                  //инициализируем сессию
            $mine_id = $session['userMineId'];                                                                              //берем id шахты из текущей сессии
            $filter_charge = ''; //фильтр актуальности данных 0/1
            $filter_place = ''; //фильтр по названию места
            $filter_status = ''; //фильтр по значению состояния 0/1/2
            $filter_search = ''; //фильтр по строке поиска

            if (isset($post['charge']) && $post['charge'] != '') { //фильтр актуальности данных 0/1
                $filter_charge = $post['charge'];
            }

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
            $sensorCacheController = new SensorCacheController();
            $sensors = $sensorCacheController->getSensorMineHash($mine_id);                                                        //получаем список сенсоров
            $errors_debug[] = "actionGetMainBpdDataCache. Начал выполнять метод ";
            //$errors_debug[] = $sensors;
            $sensors_parameters_list = array();                                                                             //иницилизируем массив для отправки на фронт
            $i = 0;
            if ($sensors)                                                                                               //если в кеше есть сенсоры
            {
                $places = Place::find()->all();
                $place_array = [];
                foreach ($places as $place) {
                    $place_array[$place->id] = $place->title;
                }
//            print_r($place_array);
                foreach ($sensors as $sensor)                                                                               // для каждого найденного сенсоар получаем значения указанный параметров
                {
                    if ($sensor['object_id'] == 49)                                                                          //если текущий сенсор не БПД(БПД не должны выводится на данной странице)
                    {
                        $sensors_parameters_list[$i]['id'] = $sensor['sensor_id'];                                          //записываем его sensor_id
                        $sensors_parameters_list[$i]['title'] = $sensor['sensor_title'];                                    //записываемм его название
                        $sensors_parameters_list[$i]['object_id'] = $sensor['object_id'];                                    //записываемм object_id
                        $sensors_parameters_list[$i]['current_time'] = gmdate('d.m.Y H:i:s', strtotime(date('d.m.Y H:i:s') . '+3 hours'));//берем текущее время сервера относительно абсолютного времени нулевого чаосовго пояса, +3 прибавили, чтобы получить воркутинский часовой пояс
                        $parameter_type_id = 2;
                        $sensor_id = $sensor['sensor_id'];                                                                  //записываем в переменную sensor_id
                        $sensor_object_type_id = $sensor['object_type_id'];                                                 //получаем object_type_id текущего сенсора

                        if ($sensor_object_type_id == 22 || $sensor_object_type_id == 95 || $sensor_object_type_id == 96 || $sensor_object_type_id == 116 || $sensor_object_type_id == 28)                                                                   //если текущий object_type_id равен 22
                        {
                            $parameter_type_id = 1;                                                                         //то берем значения из handbook для этого $parameter_type_id = 1
                        }
                        /**************             ПОЛУЧАЕМ СТАТУС СЕНСОРА                          ***************/
                        $sensor_status = $sensorCacheController->getParameterValueHash($sensor_id, 164, 3);//получаем статус текущего сенсора
                        if ($sensor_status != -1)                                                                           //если статус получили
                        {
                            switch ($sensor_status['value'])                                                                 //на основе ее значение происходит конвертация данных в текст удобный для пользователя
                            {
                                case '0':
                                    $sensors_parameters_list[$i]['state'] = 'Отключен / неисправен';
                                    break;
                                case '1':
                                    $sensors_parameters_list[$i]['state'] = 'Включен / исправен';
                                    break;
                                case '2':
                                    $sensors_parameters_list[$i]['state'] = 'Включен / работает от аккумулятора';
                                    break;
                                default:
                                    $sensors_parameters_list[$i]['state'] = 'Неизвестно';
                                    break;
                            }
                            $sensors_parameters_list[$i]['state_date_time'] = $sensor_status['date_time'];             //записываем время статуса
                        } else {
                            $errors[] = "Для датчика с sensor_id = $sensor_id не найден параметр 164 - статус (нет такого ключа кэша)"; //если статуса нет для сенсора пишем ошибку
                            $sensors_parameters_list[$i]['state'] = null;                                                  //и в массив записываем для данного сенсора параметры статуса
                            $sensors_parameters_list[$i]['state_date_time'] = null;                                        // и время статуса null
                        }

                        /**************             ПОЛУЧАЕМ МЕСТОПОЛОЖЕНИЯ СЕНСОРА                      **************/
                        $sensor_place = $sensorCacheController->getParameterValueHash($sensor_id, 122, $parameter_type_id);//получаем параметры местоположения текущего сенсора из кеша
                        if ($sensor_place !== FALSE)                                                                            // если местоположение нашли
                        {
                            $place_id = $sensor_place['value'];
                            $sensors_parameters_list[$i]['place_title'] = (isset($place_array[$place_id])) ? $place_array[$place_id] : null;
                            $sensors_parameters_list[$i]['place_id'] = $place_id;
                            $sensors_parameters_list[$i]['place_date_time'] = $sensor_place['date_time']; //записываем в массив время местоположения
                        } else {
                            $sensors_parameters_list[$i]['place_id'] = null;
                            $sensors_parameters_list[$i]['place_title'] = null;                                             //если местоположения не нашли
                            $sensors_parameters_list[$i]['place_date_time'] = null;                                         //записываем в массив null
                            $errors_debug[] = "Для датчика с sensor_id = $sensor_id не найден параметр 122 - местоположение (нет такого ключа кэша)";//пишем ошибку
                        }

                        /**************             ПОЛУЧАЕМ IP-адрес СЕНСОРА                      **************/
                        $sensor_ip_addres = $sensorCacheController->getParameterValueHash($sensor_id, 318, 1);//получаем параметры местоположения текущего сенсора из кеша
                        if ($sensor_ip_addres !== FALSE)                                                                             // если местоположение нашли
                        {
                            $sensors_parameters_list[$i]['ip_address'] = $sensor_ip_addres['value'];               //записываем в массив в поле название ip_address
                            $sensors_parameters_list[$i]['ip_date_time'] = $sensor_ip_addres['date_time'];    //записываем в массив в поле  даты ip_address

                        } else {
                            $sensors_parameters_list[$i]['ip_address'] = null;                                             //если местоположения не нашли
                            $sensors_parameters_list[$i]['ip_date_time'] = null;
                            $errors_debug[] = "Для датчика с sensor_id = $sensor_id не найден параметр 318 - IP-адрес (нет такого ключа кэша)";//пишем ошибку
                        }
                        /**************             ПОЛУЧАЕМ НАПРЯЖЕНИЕ СЕНСОРА                      **************/
                        $sensor_voltage = $sensorCacheController->getParameterValueHash($sensor_id, 159, 2);//получаем параметры местоположения текущего сенсора из кеша
                        if ($sensor_voltage !== FALSE)                                                                            // если местоположение нашли
                        {
                            $sensors_parameters_list[$i]['voltage'] = $sensor_voltage['value'];                             //записываем в массив в поле напряжение
                            $sensors_parameters_list[$i]['voltage_date_time'] = $sensor_voltage['date_time'];          //записываем в массив в поле  даты напряжения

                        } else {
                            $sensors_parameters_list[$i]['voltage'] = null;                                             //если местоположения не нашли
                            $sensors_parameters_list[$i]['voltage_date_time'] = null;
                            $errors_debug[] = "Для датчика с sensor_id = $sensor_id не найден параметр 159 - напряжение (нет такого ключа кэша)";//пишем ошибку
                        }
                        /**************             ПОЛУЧАЕМ УРОВЕНЬ ЗАРЯДА СЕНСОРА                      **************/
                        $sensor_charge = $sensorCacheController->getParameterValueHash($sensor_id, 170, 3);//получаем параметры местоположения текущего сенсора из кеша
                        if ($sensor_charge !== FALSE)                                                                            // если местоположение нашли
                        {
                            $sensors_parameters_list[$i]['charge'] = $sensor_charge['value'];                             //записываем в массив в поле уровень заряда
                            $sensors_parameters_list[$i]['charge_date_time'] = $sensor_charge['date_time'];          //записываем в массив в поле  дату уровень заряда

                        } else {
                            $sensors_parameters_list[$i]['charge'] = null;                                             //если местоположения не нашли
                            $sensors_parameters_list[$i]['charge_date_time'] = null;
                            $errors_debug[] = "Для датчика с sensor_id = $sensor_id не найден параметр 170 - уровень заряда (нет такого ключа кэша)";//пишем ошибку
                        }
                        /**************             ПОЛУЧАЕМ ASMTP СЕНСОРА                      **************/
                        $sensor_asmtp = $sensorCacheController->getParameterValueHash($sensor_id, 338, 1);//получаем параметры местоположения текущего сенсора из кеша
                        if ($sensor_asmtp !== FALSE)                                                                        // если местоположение нашли
                        {
                            $sensors_parameters_list[$i]['asmtp_id'] = $sensor_asmtp['value'];                             //записываем в массив в поле уровень заряда

                        } else {
                            $sensors_parameters_list[$i]['asmtp_id'] = null;                                             //если местоположения не нашли
                            $errors_debug[] = "Для датчика с sensor_id = $sensor_id не найден параметр 170 - уровень заряда (нет такого ключа кэша)";//пишем ошибку
                        }
                        $i++;                                                                                               //увеличиваем счетик для массива
                    }
                }
                // поиск по фильтрам и строке поиска
                if ($filter_charge != '' || $filter_place != '' || $filter_status != '' || $filter_search != '') //если есть или фильтр или строка поиска
                {
                    foreach ($sensors_parameters_list as $j => $sensor) //начинаем перебирать массив
                    {
                        $flag_delete = 1; //флаг удаления сенсора из массива если он не подходит по критериям

                        if ($filter_charge != '') //если есть фильтр по актуальности
                        {
                            if (mb_strtolower($sensor['charge']) == mb_strtolower($filter_charge)) //если актуальность равна актуальности сенсора
                            {
                                if ($filter_search != '') //если есть строка поиска
                                {
                                    $pos = strpos(mb_strtolower($sensor['place_title']), mb_strtolower($filter_search)); //смотрим вхождение строки в название местоположения
                                    $pos2 = strpos(mb_strtolower($sensor['title']), mb_strtolower($filter_search)); //смотрим вхождение строки в название сенсора
                                    if ($pos === false && $pos2 === false) //если совпадений нет не названии местоположения не названии сенсора
                                    {
                                        $flag_delete = -1; //флаг переводим на удаление текущего сенсора из массива
                                    }
                                }
                            } else //если сенсор не подходит по актуальности
                            {
                                $flag_delete = -1; //флаг переводим на удаление текущего сенсора из массива
                            }
                        }

                        if ($filter_status != '' && $flag_delete == 1) //если есть фильтр по статусу и сенсор прошел первый фильтр
                        {
                            if ($filter_status == $sensor['state']) //если статус сенсора равен статусу фильтра
                            {
                                if ($filter_search != '') //если есть строка поиска
                                {
                                    $pos = strpos(mb_strtolower($sensor['place_title']), mb_strtolower($filter_search)); //смотрим вхождение строки в название местоположения
                                    $pos2 = strpos(mb_strtolower($sensor['title']), mb_strtolower($filter_search)); //смотрим вхождение строки в название сенсора
                                    if ($pos === false && $pos2 === false) //если совпадений нет не названии местоположения не названии сенсора
                                    {
                                        $flag_delete = -1; //флаг переводим на удаление текущего сенсора из массива
                                    }
                                }
                            } else //иначе сенсор не прошел фильтр статуса
                            {
                                $flag_delete = -1; //флаг переводим на удаление текущего сенсора из массива
                            }
                        }

                        if ($filter_place != '' && $flag_delete == 1) //если есть фильтр местоположения и сенсор прошел предыдущие фильтры
                        {
                            if ($sensor['place_title'] != $filter_place) //если местоположение фильтра не равно местоположению текущего сенсора
                            {
                                $flag_delete = -1; //флаг переводим на удаление текущего сенсора из массива
                            } else //иначе
                            {
                                if ($filter_search != '') //если есть строка поиска
                                {
                                    $pos = strpos(mb_strtolower($sensor['place_title']), mb_strtolower($filter_search)); //смотрим вхождение строки в название местоположения
                                    $pos2 = strpos(mb_strtolower($sensor['title']), mb_strtolower($filter_search)); //смотрим вхождение строки в название сенсора
                                    if ($pos === false && $pos2 === false) //если совпадений нет не названии местоположения не названии сенсора
                                    {
                                        $flag_delete = -1; //флаг переводим на удаление текущего сенсора из массива
                                    }
                                }
                            }
                        }

                        if ($filter_search != '' && ($filter_charge == '' && $filter_place == '' && $filter_status == ''))//если есть строка поиска и нет фильтров
                        {
                            $pos = strpos(mb_strtolower($sensor['place_title']), mb_strtolower($filter_search)); //смотрим вхождение строки в название местоположения
                            $pos2 = strpos(mb_strtolower($sensor['title']), mb_strtolower($filter_search)); //смотрим вхождение строки в название сенсора
                            if ($pos === false && $pos2 === false) //если совпадений нет не названии местоположения не названии сенсора
                            {
                                $flag_delete = -1; //флаг переводим на удаление текущего сенсора из массива
                            }
                        }

                        if ($flag_delete == -1) //если флаг удаления переключен в режим удаления сенсора из массива
                        {
                            unset($sensors_parameters_list[$j]); //удаляем текущий сенсор из массива
                        }
                    }
                    $sensors_parameters_list = array_values($sensors_parameters_list); //занового индексируем массив
                }
            } else                                                                                                            //иначе нет сенсоров в кеше текущей шахты
            {
                $errors[] = 'Для кеша  с ключом SensorMine_' . $mine_id . ' не найден';                                         //пишем ошибку
            }

            // Сортировка списка БПД для выдачи
            usort($sensors_parameters_list, 'self::sortBpdList');
        } catch (\Throwable $e) {
            $status = 0;
            $errors[] = "actionGetMainBpdDataCache. Исключение: ";
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }

        $result = array('bpds' => $sensors_parameters_list, 'bpds_count' => count($sensors_parameters_list), 'errors' => $errors, 'debug_info' => $errors_debug);
        Yii::$app->response->format = Response::FORMAT_JSON;                                                              //указываем формат данных ответа на запрос
        Yii::$app->response->data = $result;                                                                    //возвращаем данные на фронт
    }

    public static function sortBpdList($a, $b)
    {
        $sortMap = array(
            'Отключен / неисправен' => 0,
            'Включен / работает от аккумулятора' => 1,
            'Включен / исправен' => 2,
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

    public function findMatch($place, $state, $charge, $array)
    {
        $result = array();
        $debug = false;
        foreach ($array as $arr) {
            if (isset($arr['place']) && $arr['place'] === $place && $state == '' && $charge == '') {
                if ($debug) {
                    //echo "зашли в условие, когда передан только place \n";
                }
                $result[] = $arr;
            } else if (isset($arr['state']) && $arr['state'] === $state && $place == '' && $charge == '') {
                if ($debug) {
                    //echo "зашли в условие, когда передан только state \n";
                }
                $result[] = $arr;
            } else if (isset($arr['charge']) && $arr['charge'] === $charge && $place == '' && $state == '') {
                if ($debug) {
                    //echo "зашли в условие, когда передан только charge \n";
                }
                $result[] = $arr;
            } else if (isset($arr['place']) && $arr['place'] === $place && isset($arr['state']) && $arr['state'] === $state && $charge == '') {
                if ($debug) {
                    //echo "зашли в условие, когда передан place и state \n";
                }
                $result[] = $arr;
            } else if (isset($arr['place']) && $arr['place'] === $place && isset($arr['charge']) && $arr['charge'] === $charge && $state == '') {
                if ($debug) {
                    //echo "зашли в условие, когда передан place и charge\n";
                }
                $result[] = $arr;
            } else if (isset($arr['state']) && $arr['state'] === $state && isset($arr['charge']) && $arr['charge'] === $charge && $place == '') {
                if ($debug) {
                    //echo "зашли в условие, когда передан state и charge\n";
                }
                $result[] = $arr;
            } else if (isset($arr['state']) && $arr['state'] === $state && isset($arr['charge']) && $arr['charge'] === $charge && isset($arr['place']) && $arr['place'] === $place) {
                if ($debug) {
                    //echo "зашли в условие, когда переданы все критерии фильтра\n";
                }
                $result[] = $arr;
            }
        }
        return $result;
    }
}
