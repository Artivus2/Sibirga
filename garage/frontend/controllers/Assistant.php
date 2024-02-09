<?php
/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

/**
 * Created by PhpStorm.
 * User: Ingener401
 * Date: 25.10.2018
 * Time: 13:49
 */

namespace frontend\controllers;

use Yii;

/**
 * Класс для создания универсальных методов
 * Например: поиск в массиве, вывод массива, и другие методы, которых можно создавать как универсальные методы
 * Class Assistant
 * @package app\controllers
 */
class Assistant
{
    // GetDateNow               - Метод получения текущей даты
    // GetEndShiftDateTime      - Получение производственной даты и времени окончания 4 смены по календарной дате
    // cmpDate                  - функция сравнения дат в объекте
    // GetDateTimeByShift       - Метод получения массива календарных даты и времени на основе смены и производственной даты
    // GetCountShifts           - Метод получения текущей настройки количества смен на предприятии
    // GetShortFullName         - Метод получения Фамилии И.О.
    // GetFullName              - Метод получения Фамилии Имени Отчества

    /**
     * Метод перевода секунд в формат H:i:s
     * @param $seconds -   Число секунд
     * @return string   -   Строка с отформатированным временем
     */
    public static function SecondsToTime($seconds)
    {
        $hours = floor($seconds / 3600);
        $mins = floor($seconds / 60 % 60);
        $secs = floor($seconds % 60);
        return implode(':', [$hours, $mins, $secs]);
    }

    // перевод из римских чисел в латинские
    public static function int2roman($n, $prefix = '***')
    {
        $M = ['', 'M', 'MM', 'MMM'];
        $C = ['', 'C', 'CC', 'CCC', 'CD', 'D', 'DC', 'DCC', 'DCCC', 'CM'];
        $X = ['', 'X', 'XX', 'XXX', 'XL', 'L', 'LX', 'LXX', 'LXXX', 'XC'];
        $I = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX'];
        return ($n > 3999 ? $prefix : '') . ($M[($n % 10000) / 1000] ?? '') . $C[($n % 1000) / 100] . $X[($n % 100) / 10] . $I[($n % 10)];
    }

    // TODO: Проверки

    /**
     * Метод для нахождения разницы во времени между датами в формате MySQL.
     * Никаких проверок не проводится!!
     *
     * @param $timestamp_1 -   первая метка времени
     * @param $timestamp_2 -   Вторая метка времени
     * @return float|int    -   разница между датами в секундах
     * @example
     * $delta = Assistant::GetMysqlTimeDifference(date('Y-m-d H:i:s.U'), '2019-06-10 11:51:30.123');
     *
     */
    public static function GetMysqlTimeDifference($timestamp_1, $timestamp_2)
    {
        $timestamp_1_seconds = strtotime(explode('.', $timestamp_1)[0]);
        $timestamp_2_seconds = strtotime(explode('.', $timestamp_2)[0]);
        return abs($timestamp_1_seconds - $timestamp_2_seconds);
    }


    /**
     * Метод вывода в нормальному виде массива
     * Использует функцию print_r()
     * @param $array - массив
     * Created by: Одилов О.У. on 25.10.2018 13:56
     */
    public static function PrintR($array, $die = false)
    {
        echo '<pre>';
        print_r($array);
        echo '</pre>';
        if ($die) {
            die("Остановил выполнение метода");
        }
    }

    public static function VarDump($obj)
    {
        echo '<pre>';
        var_dump($obj);
        echo '</pre>';
    }


    /**
     * Метод поиска с выделением найденного
     * @param $needle - что нужно найти
     * @param $string - переменная в котором есть искомого ($needle)
     * @return string - $string c выделением найденного
     * Created by: Одилов О.У. on 30.10.2018 11:24
     */
    public static function MarkSearched($needle, $string)
    {
        $title = "";
        if ($needle != "") {
            // echo $search;
            $titleParts = explode(mb_strtolower($needle), mb_strtolower($string));
            $titleCt = count($titleParts);
            $startIndex = 0;
            $title .= substr($string, $startIndex, strlen($titleParts[0]));
            $startIndex += strlen($titleParts[0] . $needle);
            for ($j = 1; $j < $titleCt; $j++) {
                $title .= "<span class='searched'>" .
                    substr($string, $startIndex - strlen($needle), strlen
                    ($needle)) . "</span>" .
                    substr($string, $startIndex, strlen
                    ($titleParts[$j]));
                $startIndex += strlen($titleParts[$j] . $needle);
            }
        } else {
            $title .= $string;
        }
        return $title;
    }

    /**
     * Метод вычитания даты
     * @param $start_date_time - дата начало
     * @param $end_date_time - дата конец
     * @param $return - указывает что нужно возвращать
     * @return string возвращает строку в виде 2д 01:20:30
     * @author Created by: Одилов О.У. on 09.11.2018 10:57
     */
    public static function DateTimeDiff($start_date_time, $end_date_time, $return = '')
    {
        $date_time_diff = "";
        $dat_diff = "";
        $date_format = "Y-m-d H:i:s";

        $start_date_time = date_create($start_date_time);
        $start_date_time->format($date_format);

        $end_date_time = date_create($end_date_time);
        $end_date_time->format($date_format);

        $diff = date_diff($start_date_time, $end_date_time);
        $years = $diff->y;
        $months = $diff->m;
        $days = $diff->d;
        $hours = $diff->h;
        $minutes = $diff->i;
        $seconds = $diff->s;
        if ($days != 0) {
            $dat_diff = $days . "д ";
        }
        $date_time_diff .= "$hours:$minutes:$seconds";
        $date = date_create($date_time_diff);
        switch ($return) {
            case 'y' :
                return $years;
                break;
            case 'm' :
                return $months;
                break;
            case 'd' :
                return $diff->format('%a');
                break;
            case 'h' :
                return $hours;
                break;
            case 's' :
                return $seconds;
                break;
            default:
                return $dat_diff . date_format($date, "H:i:s");
                break;
        }
    }

    /**
     * Метод поиска значения в массиве.
     * 1. Если найдет значение в массиве, то вернет ключ к массиву
     * 2. Если не найдет, то вернет значение -1
     * @param $array - массив
     * @param $needle - значение, которого нужно найти в указанном массиве
     * @param string $array_column_name - название колонки асоц.массива(по умолчанию его нет)
     * @return bool|false|int|string
     * Created by: Одилов О.У. on 18.10.2018
     */
    public static function SearchInArray($array, $needle, $array_column_name = 'not')
    {
        $key = false;
        if ($array_column_name != 'not') {
            $key = array_search($needle, array_column($array, $array_column_name));                                     // находим в обычном массиве желаемое значение
            if ($key !== FALSE)                                                                                          // если нашли, то проверяем, совпадают ли значения
            {
                if ($array[$key][$array_column_name] == $needle)                                                         // если нашли и значения совпадают, то вернум ключ к массиву
                {
                    return $key;
                }
            } else {
                return -1;
            }
        } else {
            $key = array_search($needle, $array);                                                                       // находим в обычном массиве желаемое значение
            if ($key !== FALSE)                                                                                          // если нашли, то проверяем, совпадают ли значения
            {
                if ($array[$key] == $needle)                                                                             // если нашли и значения совпадают, то вернем ключ к массиву
                {
                    return $key;
                }
            } else {
                return -1;
            }
        }
    }


    /**
     * Функция определения метода получаемого запроса.
     * Функция проверяет, какой метод был отправлен с сервера
     * Если POST то возвращает данные из POST запроса.
     * Если GET то возвращает данные из GET запроса.
     * Этот метод необходимо использовать для всех методов. Исключается использовании конкретных методов получения запросов,
     * так как это метод сам определяет какой метод был отправлен со фронта
     * Пример вызова с других методов или классов: Assistant::GetServerMethod();
     * @return array|mixed - возвращает массив данных из POST/GET запроса
     * Created by: Одилов О.У. on 29.11.2018 15:01
     */
    public static function GetServerMethod()
    {
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'POST' :
                return \Yii::$app->request->post();
            case 'GET' :
                return \Yii::$app->request->get();
            default:
                return null;
        }
    }

    /**
     * Метод сохранения изображения в папку
     * @param $file
     * @param $upload_dir
     * @param $object_title
     * @param $image_type
     * @return int|string
     */
    public static function UploadPicture($file, $upload_dir, $object_title, $image_type)
    {
        $upload_file = $upload_dir . $object_title . '_' . date('d-m-Y H-i') . '.' . $image_type;
        if (move_uploaded_file($file['tmp_name'], $upload_file)) {
            return $upload_file;
        }

        return -1;
    }


    /**
     * Название метода: CallProcedure()
     * Метод вызова процедур Mysql
     * @param $procedure_name - название процедуры
     * @return array - массив данных
     * @throws \yii\db\Exception
     * Created by: Одилов О.У. on 19.12.2018 14:09
     */
    public static function CallProcedure($procedure_name)
    {
        return \Yii::$app->db->createCommand("CALL $procedure_name")->queryAll();
    }


    /**
     * Название метода: GetDateWithMicroseconds()
     * Метод получения  даты до миллисекунд
     * @param $date_time - дата/время до миллисекунд
     * @return string - дата в виде строки
     * Created by: Одилов О.У. on 19.12.2018 14:08
     */
    public static function GetDateWithMicroseconds($date_time)
    {
        $date = new \DateTime($date_time);
        return $date->format('Y-m-d H:i:s.u');
    }

    /**
     * Название метода: AddConditionForParameters()
     * Процедура создания из типов параметров и параметров условия поска.
     * В основном используется для передачи данных в процедуре (GetSensorsParametersLastValuesOptimized) по историческим данным.
     * Например, нам пришли параметры вида "2-122, 3-83, 2-164" и мы должны условие поиска создать.
     * Результат будет таковым: (parameter_type_id = 2 AND parameter_type_id = 122) AND (parameter_type_id = 3 AND parameter_type_id = 83)
     * @param $parameters_with_parameter_types - параметры в виде "2-122, 3-83, 2-164". Обязательно, чтоб тип парметры был первым а потом сам параметр
     * @param string $return - что возвращать, то есть результать, или с каких таблиц выборку сделать.
     * @param string $deliiter
     * @return array - возвращает массив.
     * Если возвращает '' - то выборка должна произайти из parameter_value и parameter_handbook_value
     * Если возвращает v - то выборка должна произайти только из parameter_value
     * Если возвращает h - то выборка должна произайти только из parameter_handbook_value
     * Пример вызова : AddConditionForParameters("2-122, 3-83")
     * Возвращает Array ( [parameter_type_value] => v [parameters] => (parameter_type_id = 2 AND parameter_type_id = 122)
     *  AND (parameter_type_id = 3 AND parameter_type_id = 83)
     * Обязательно нужно указать тип параметра, иначе не сработает метод!
     * Created by: Одилов О.У. on 14.12.2018 14:17
     */
    public static function AddConditionForParameters($parameters_with_parameter_types, $return = '', $deliiter = '-')
    {
        if ($parameters_with_parameter_types == '') {
            $result = array('parameter_type_table' => '', 'parameters' => $parameters_with_parameter_types);
            if ($return == 'parameters') {
                return $parameters_with_parameter_types;
            } else {
                return $result;
            }
        } else {
            $parameter_value = '';
            $flag_handbook_value = 0;                                                                                       // переменная указывает на то, что данные нужно выбрать из таблицы handbook, так как тип параметра 1
            $flag_value = 0;                                                                                                // переменная указывает на то, что данные нужно выбрать из таблицы parameter_value, так как тип параметра 2 или 3
            $parameters_sum = '';
            $parameters = explode(',', $parameters_with_parameter_types);                                           // разбивает по , и добавим в новый массив, то есть строку вида "1-122, 3-164, 2-83" разбваем и добавим в массив
            $index = 0;
            foreach ($parameters as $parameter_type_id_parameter_id) {
                $parameter_types = explode($deliiter, $parameter_type_id_parameter_id);                                  // получаем данные [0] => 1, [1] => 122
                $parameter_type_id = str_replace('"', '', $parameter_types[0]);
                $parameter_id = str_replace('"', '', $parameter_types[1]);
                switch ($parameter_type_id) {
                    case 1 :
                        $flag_handbook_value = 1;
                        break;
                    case 2 or 3:
                        $flag_value = 1;
                        break;
                }
                if ($index == 0) {
                    $parameters_sum .= '(parameter_type_id = ' . $parameter_type_id . ' AND parameter_id = ' . $parameter_id . ') ';
                } else {
                    $parameters_sum .= ' OR (parameter_type_id = ' . $parameter_type_id . ' AND parameter_id = ' . $parameter_id . ') ';
                }
                $index++;
            }
            if ($flag_handbook_value == 1) $parameter_value = 'h';                                                           // если нашли тип параметра 1, то данные берем из таблицы object_parameter_handbook_value). object- это edge, sensor, equipment  чо угодно
            if ($flag_value == 1) $parameter_value = 'v';                                                                    // если нашли тип параметра 2 или 3, то данные берем из таблицы object_parameter_value). object- это edge, sensor, equipment  чо угодно
            if ($flag_handbook_value + $flag_value == 2) $parameter_value = '';                                            // если нашли все параметры, то указываем, чтобы выборка была из всех таблиц, то есть из object_parameter_value и object_parameter_handbook_value

            $result = array('parameter_type_table' => $parameter_value, 'parameters' => $parameters_sum);
            if ($return == 'parameters') {
                return $parameters_sum;
            } else {
                return $result;
            }

        }
    }

    /**
     * Название метода: GetSensorsParametersValuesPeriod()
     * @param $sensor_condition - условие поиска сенсоров. Можно найти конкретного сенсора по условии sensor.id = 310.
     * Примеры использования переменной:
     *      sensor.id = 310 and object_id = 49 OR object_type_id = 22
     * В этой переменной можно писать любые фильтры которых можно сделать по табличке sensors и object
     * @param $parameter_condition - условия параметра поиска. Если указать -1, то возвращает все параметры.
     *      Если есть конкретные параметры, то нужно указать в виде: виде "1-122, 2-83, 3-164, 1-105"
     * @param $date_time_start - дата/время начало
     * @param $date_time_end - конец даты и времени
     * @return array
     * Created by: Одилов О.У. on 14.12.2018 15:56
     */
    public static function GetSensorsParametersValuesPeriod($sensor_condition, $parameter_condition, $date_time_start, $date_time_end)
    {
        $parameters = self::AddConditionForParameters($parameter_condition);
        $parameter_condition = $parameters['parameters'];
        $parameter_type_table = $parameters['parameter_type_table'];
        return self::CallProcedure("GetSensorsParametersValuesOptimizedPeriod('$sensor_condition', '$parameter_condition', '$date_time_start',  '$date_time_end' ,'$parameter_type_table')");
    }

    public static function GetEquipmentsParametersValuesPeriod($equipment_condition, $parameter_condition, $date_time_start, $date_time_end)
    {
        $parameters = self::AddConditionForParameters($parameter_condition);
        $parameter_condition = $parameters['parameters'];
        $parameter_type_table = $parameters['parameter_type_table'];
        return self::CallProcedure("GetEquipmentsParametersValuesOptimizedPeriod('$equipment_condition', '$parameter_condition', '$date_time_start',  '$date_time_end' ,'$parameter_type_table')");
    }

    /**
     * Название метода: GetSensorsParametersLastValues()
     * @param $sensor_condition - условие поиска сенсоров. Можно найти конкретного сенсора по условии sensor.id = 310.
     * Примеры использования переменной:
     *      sensor.id = 310 and object_id = 49 OR object_type_id = 22
     * В этой переменной можно писать любые фильтры которых можно сделать по табличке sensors и object
     * @param $parameter_condition - условия параметра поиска.
     *      Если есть конкретные параметры, то нужно указать в виде: виде "1-122, 2-83, 3-164, 1-105"
     * @param $date_time - дата/время начало
     * @return array
     * Created by: Одилов О.У. on 14.12.2018 14:35
     */
    public static function GetSensorsParametersLastValues($sensor_condition, $parameter_condition, $date_time)
    {
        $parameters = self::AddConditionForParameters($parameter_condition, '', ':');
        $parameter_condition = $parameters['parameters'];
        $parameter_type_table = $parameters['parameter_type_table'];
        return self::CallProcedure("GetSensorsParametersLastValuesOptimized('$sensor_condition', '$parameter_condition', '$date_time', '$parameter_type_table')");
    }

    public static function GetEquipmentsParametersLastValues($equipment_condition, $parameter_condition, $date_time)
    {
        $parameters = self::AddConditionForParameters($parameter_condition, '', ':');
        $parameter_condition = $parameters['parameters'];
        $parameter_type_table = $parameters['parameter_type_table'];
        return self::CallProcedure("GetEquipmentsParametersLastValuesOptimized('$equipment_condition', '$parameter_condition', '$date_time', '$parameter_type_table')");
    }

    /**
     * Название метода: AddConditionOperator()
     * Метод добавления условий (операторы MYSQL ) для строки (операторы and, or и тд для Mysql запроса)
     * Например, нам нужно добавить условие для строки, то есть добавить условие и добавить оператор AND. Этот метод автоматически
     * добавляет такие операторы.
     * @param $condition_variable - переменная в которой уже есть или нет условия
     * @param $condition - условие которое хотим добавить
     * @param $operator - оператор, которого хотим добавить. По умолчанию указано AND
     * @return string
     * Created by: Одилов О.У. on 19.12.2018 11:18
     */
    public static function AddConditionOperator($condition_variable, $condition, $operator = "")
    {
        if ($condition_variable == "") {
            $condition_variable = $condition;
        } else {
            $condition_variable .= " " . $operator . " " . $condition;
        }
        return $condition_variable;
    }

    /**
     * Название метода: ArrayFilter()
     * Метод фильтрации массива по конкретому полю.
     * Например необходимо вывести всех работников, у находящихся в ламповой. Для таких целей можно использовать.
     *
     * Входные параметры:
     * @param $array - ассаоциативный массив
     * @param $array_column_name - название поля массива
     * @param $needle - значение фильтра.
     * @return array - массив
     *
     * Пример вызова:
     * $workers - массив списка работников
     * из текущего класса: self::ArrayFilter($workers, "place_id", 60156)
     * из других классов: Assistant::ArrayFilter($workers, 'charge_id', 2);
     * @author Озармехр Одилов
     * Created date: on 25.12.2018 14:52
     */
    public static function ArrayFilter($array, $array_column_name, $needle)
    {
        $array_filter = array();
        foreach ($array as $item) {
            if ($item[$array_column_name] == $needle and $needle != "") {
                $array_filter[] = $item;
            }
        }
        return $array_filter;
    }

    /**
     * Метод генерации случайных значений (паролей и тд)
     * Входные обязательные параметры:
     * @param $limit - длина возвращаемого значения
     * @return string - зашифрованное значение
     * @author Озармехр Одилов <ooy@pfsz.ru>
     * Created date: on 18.04.2019 15:05
     */
    public static function RandomString($limit)
    {
        return substr(rand(1000, 50000) . base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, $limit);
    }

    /**
     * Метод UploadFile() - загрузка файлов на сервер
     * @param $blob - блоб файл с типом файла
     * @param $file_name - наименование файла
     * @param $table - таблица
     * @return string - возвращает строку которую необходимо записать в БД
     *
     * @package frontend\controllers
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.08.2019 17:04
     */
    public static function UploadFile($blob, $file_name, $table, $extension = Null)
    {
        $file_path = "";
        $data = explode(',', $blob);
        $intermediate = explode(';', $data[0]);
        $type = explode('/', $intermediate[0]);
        if (isset($data[1])) {
            $content = base64_decode($data[1]);
            if (!file_exists(Yii::getAlias('@app') . '/web/img/' . $table)) {
                if (!mkdir($concurrentDirectory = Yii::getAlias('@app') . '/web/img/' . $table) && !is_dir($concurrentDirectory)) {
                    throw new \RuntimeException(sprintf('UploadFile. Directory "%s" was not created', $concurrentDirectory));
                }
            }
            $date_now = date('d-m-Y H-i-s.U');
            $uploaded_file = Yii::getAlias('@app') . '/web/img/' . $table . '/' . $date_now . '_' . $file_name;                              //объявляем и инициируем переменную для хранения названия файла, состоящего из
            $file_path = '/img/' . $table . '/' . $date_now . '_' . $file_name;
            file_put_contents($uploaded_file, $content);
        } else {
            throw new \RuntimeException(sprintf('UploadFile. Данных для сохранения нет. data[1] пуст'));
        }
        return $file_path;
    }

    /**
     * Метод UploadFileChat() - загрузка файлов на сервер с модуля Чат (отличия от обычного - вложение определяется в данном методе)
     * @param $blob - блоб файл с типом файла
     * @param $file_name - наименование файла
     * @param $table - таблица
     * @return string - возвращает путь к фаайлу которую необходимо записать в БД
     *
     * @package frontend\controllers
     *
     * @author Якимов М.Н.
     * Created date: on 29.01.2020 17:04
     */
    public static function UploadFileChat($blob, $file_name, $table)
    {
        $data = explode(',', $blob);
        $intermediate = explode(';', $data[0]);
        $type = explode('/', $intermediate[0]);
        $content = base64_decode($data[1]);
        if (!file_exists(Yii::getAlias('@app') . '/web/img/' . $table)) {
            if (!mkdir($concurrentDirectory = Yii::getAlias('@app') . '/web/img/' . $table) && !is_dir($concurrentDirectory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }
        $date_now = date('d-m-Y H-i-s.U');
        $uploaded_file = Yii::getAlias('@app') . '/web/img/' . $table . '/' . $date_now . '_' . $file_name;                              //объявляем и инициируем переменную для хранения названия файла, состоящего из
        $file_path = '/img/' . $table . '/' . $date_now . '_' . $file_name;
        file_put_contents($uploaded_file, $content);
        return $file_path;
    }

    /**
     * Метод upload_mobile_file() - загрузка файлов на сервер c мобильной версии
     * @param $blob
     * @param $file_name
     * @param $table
     * @param $extension
     * @return string
     *
     * @package frontend\controllers
     *
     * Входные обязательные параметры:
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 11.12.2019 13:21
     */
    public static function upload_mobile_file($blob, $file_name, $table, $extension)
    {
        $content = base64_decode($blob);
        if (!file_exists(Yii::getAlias('@app') . '/web/img/' . $table)) {
            if (!mkdir($concurrentDirectory = Yii::getAlias('@app') . '/web/img/' . $table) && !is_dir($concurrentDirectory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }
        $date_now = date('d-m-Y H-i-s.U');
        $uploaded_file = Yii::getAlias('@app') . '/web/img/' . $table . '/' . $date_now . '_' . $file_name;                              //объявляем и инициируем переменную для хранения названия файла, состоящего из
        $file_path = '/img/' . $table . '/' . $date_now . '_' . $file_name;
        file_put_contents($uploaded_file, $content);
        return $file_path;
    }

    /**
     * Название метода: GetDateTimeNow()
     * Метод получения текущей даты и времени без микросекунд
     * @return string - дата в виде строки
     * @example tag_date=Assistant::GetDateTimeNow();
     * Created by: Якимов М.Н. on 08.06.2019
     */
    public static function GetDateTimeNow()
    {
        //ВАЖНО!!! часовой пояс должен быть верно настроен по UTC это важно

        //Вариант 1 с получением часового пояса
        $time_zone = new \DateTimeZone(AMICUM_TIME_ZONE);
        if ($time_zone) {
            $now = \DateTime::createFromFormat('U.u', sprintf('%.f', microtime(true)))->setTimeZone($time_zone);
        } else {
            return false;
        }
        return $now->format('Y-m-d H:i:s');

        //Вариант 2 с жестко заданным часовым поясом
//        $now = \DateTime::createFromFormat('U.u', microtime(true))->setTimeZone(new \DateTimeZone('Asia/Krasnoyarsk'));
//        $now = \DateTime::createFromFormat('U.u', microtime(true))->setTimeZone(new \DateTimeZone("Europe/Moscow"));
//        return $now->format('Y-m-d H:i:s.u');

        //Вариант 3 с получением микросекунд по UTC
//        $now = \DateTime::createFromFormat('U.u', microtime(true));
//        return $now->format("Y-m-d H:i:s.u");

//        return date("Y-m-d H:i:s.U");

    }

    /**
     * Название метода: GetDateNow()
     * GetDateNow - Метод получения текущей даты
     * @return string - дата в виде строки
     * @example tag_date=Assistant::GetDateNow();
     * Created by: Якимов М.Н. on 08.06.2019
     */
    public static function GetDateNow()
    {
        $time_zone = new \DateTimeZone(AMICUM_TIME_ZONE);
        if ($time_zone) {
            $now = \DateTime::createFromFormat('U.u', sprintf('%.f', microtime(true)))->setTimeZone($time_zone);
        } else {
            return false;
        }
        return $now->format('Y-m-d');
    }


    /**
     * Метод GetEndShiftDateTime() - Получение производственной даты и времени окончания 4 смены по календарной дате
     * @param $date - дата
     * @param bool $with_time - флаг дата со временем или без
     * @return array -  массив:  date_start - дата и время начала 1 смены, date_end - дата и время окончания 4 смены
     *
     * @package frontend\controllers
     *
     * @author Якимов М.Н.
     * Created date: on 05.02.2021 23:57
     */
    public static function GetEndShiftDateTime($date, $with_time = false)
    {
        if ($with_time) {
            $hours = (int)date("H", strtotime($date));
            if ($hours < 8) {
                $date_start = date('Y-m-d', strtotime($date . '-1 day')) . ' 08:00:00';
                $date_end = date('Y-m-d', strtotime($date)) . ' 07:59:59';
            } else {
                $date_start = date('Y-m-d', strtotime($date)) . ' 08:00:00';
                $date_end = date('Y-m-d', strtotime($date . '+1 day')) . ' 07:59:59';
            }
        } else {
            $date_start = $date . ' 08:00:00';
            $date_end = date('Y-m-d', strtotime($date . '+1 day')) . ' 07:59:59';
        }
        return array('date_start' => $date_start, 'date_end' => $date_end);
    }

    // cmpDate - функция сравнения дат в объекте,
    // используется для сортировки массива истории местоположения людей
    public static function cmpDate($o1, $o2)
    {
        $a = strtotime($o1['date_time']);
        $b = strtotime($o2['date_time']);
        if ($a == $b) {
            return 0;
        }
        return ($a < $b) ? -1 : 1;
    }

    /**
     * GetDateTimeByShift - Метод получения массива календарных даты и времени на основе смены и производственной даты
     * @param $date - производственная дата
     * @param $shift_id - ключ смены
     * @param $count_shifts - количество смен на предприятии
     * @return array
     */
    public static function GetDateTimeByShift($date, $shift_id, $count_shifts = null): array
    {
        $date = date("Y-m-d", strtotime($date));
        $dateNext = date("Y-m-d", strtotime($date . ' +1 day'));

        $result = array(
            'shift_id' => $shift_id,
            'timeStart' => "",
            'timeEnd' => "",
            'date_time_start' => "",
            'date_time_end' => "",
            'date_start' => "",
            'date_end' => "",
        );

        if (!$count_shifts) {
            $count_shifts = self::GetCountShifts();
        }

        if ($count_shifts == 3) {
            if ($shift_id == 2) {
                $result['timeStart'] = " 16:00:00";
                $result['timeEnd'] = " 00:00:00";
                $result['date_time_start'] = $date . $result['timeStart'];
                $result['date_time_end'] = $dateNext . $result['timeEnd'];
            } else if ($shift_id == 3) {
                $result['timeStart'] = " 00:00:00";
                $result['timeEnd'] = " 08:00:00";
                $result['date_time_start'] = $dateNext . $result['timeStart'];
                $result['date_time_end'] = $dateNext . $result['timeEnd'];
            } else if ($shift_id == 1) {
                $result['timeStart'] = " 08:00:00";
                $result['timeEnd'] = " 16:00:00";
                $result['date_time_start'] = $date . $result['timeStart'];
                $result['date_time_end'] = $date . $result['timeEnd'];
            } else if ($shift_id == 5) {                                                                                // без смены
                $result['timeStart'] = " 08:00:00";
                $result['timeEnd'] = " 08:00:00";
                $result['date_time_start'] = $date . $result['timeStart'];
                $result['date_time_end'] = $dateNext . $result['timeEnd'];
            } else {
                throw new \Exception("На данном предприятии нет данной смены");
            }
        } else {
            if ($shift_id == 2) {
                $result['timeStart'] = " 14:00:00";
                $result['timeEnd'] = " 20:00:00";
                $result['date_time_start'] = $date . $result['timeStart'];
                $result['date_time_end'] = $date . $result['timeEnd'];
            } else if ($shift_id == 3) {
                $result['timeStart'] = " 20:00:00";
                $result['timeEnd'] = " 02:00:00";
                $result['date_time_start'] = $date . $result['timeStart'];
                $result['date_time_end'] = $dateNext . $result['timeEnd'];
            } else if ($shift_id == 4) {
                $result['timeStart'] = " 02:00:00";
                $result['timeEnd'] = " 08:00:00";
                $result['date_time_start'] = $dateNext . $result['timeStart'];
                $result['date_time_end'] = $dateNext . $result['timeEnd'];
            } else if ($shift_id == 1) {
                $result['timeStart'] = " 08:00:00";
                $result['timeEnd'] = " 14:00:00";
                $result['date_time_start'] = $date . $result['timeStart'];
                $result['date_time_end'] = $date . $result['timeEnd'];
            } else {                                                                                                        // без смены
                $result['timeStart'] = " 08:00:00";
                $result['timeEnd'] = " 08:00:00";
                $result['date_time_start'] = $date . $result['timeStart'];
                $result['date_time_end'] = $dateNext . $result['timeEnd'];
            }
        }

        $result['date_start'] = $result['date_time_start'];
        $result['date_end'] = $result['date_time_end'];

        return $result;
    }

    /**
     * GetCountShifts - Метод получения текущей настройки количества смен на предприятии
     * @return int
     */
    public static function GetCountShifts()
    {
        if (defined('AMICUM_DEFAULT_SHIFTS')) {
            return AMICUM_DEFAULT_SHIFTS;
        } else {
            return 4;
        }
    }


    /**
     * GetShortFullName - Метод получения Фамилии И.О.
     * @return int
     */
    public static function GetShortFullName($first_name, $patronymic, $last_name)
    {
        $name = mb_substr($first_name, 0, 1);
        $patronymic = mb_substr($patronymic, 0, 1);

        $full_name = $last_name . " " . ($name ? $name . "." : "") . " " . ($patronymic ? $patronymic . "." : "");

        return $full_name;
    }

    /**
     * GetFullName - Метод получения Фамилии Имени Отчества
     * @return int
     */
    public static function GetFullName($first_name, $patronymic, $last_name)
    {
        $full_name = $last_name . " " . ($first_name ? $first_name : "") . " " . ($patronymic ? $patronymic : "");

        return $full_name;
    }
}