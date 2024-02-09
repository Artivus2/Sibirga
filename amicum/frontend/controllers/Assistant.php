<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

/**
 * Created by PhpStorm.
 * User: Ingener401
 * Date: 25.10.2018
 * Time: 13:49
 */

namespace frontend\controllers;

use DateTime;
use DateTimeZone;
use Exception;
use frontend\controllers\system\LogAmicumFront;
use RuntimeException;
use Throwable;
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
    // cmpDate                  - Функция сравнения дат в объекте
    // GetDateTimeByShift       - Метод получения массива календарных даты и времени на основе смены и производственной даты
    // GetCountShifts           - Метод получения текущей настройки количества смен на предприятии
    // GetShortFullName         - Метод получения Фамилии И.О.
    // GetFullName              - Метод получения Фамилии Имени Отчества
    // GetShiftByDateTime       - Метод получения Смены по времени
    // jsonDecodeAmicum         - Метод декодирования json строки из смежных системы, с обработкой ошибок десериализации
    // GetStartProdDateTime     - Метод получения даты и времени начала выборки производственной даты
    // GetEndProdDateTime       - Метод получения даты и времени окончания выборки производственной даты

    /**
     * Метод перевода секунд в формат H:i:s
     * @param $seconds -   Число секунд
     * @return string   -   Строка с отформатированным временем
     */
    public static function SecondsToTime($seconds): string
    {
        $hours = floor($seconds / 3600);
        $mins = floor($seconds / 60 % 60);
        $secs = floor($seconds % 60);
        return implode(':', [$hours, $mins, $secs]);
    }

    // перевод из Римских чисел в Латинские
    public static function int2roman($n, $prefix = '***'): string
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
    public static function GetMysqlTimeDifference($timestamp_1, $timestamp_2): float|int
    {
        $timestamp_1_seconds = strtotime(explode('.', $timestamp_1)[0]);
        $timestamp_2_seconds = strtotime(explode('.', $timestamp_2)[0]);
        return abs($timestamp_1_seconds - $timestamp_2_seconds);
    }


    /**
     * Метод вывода в нормальном виде массива
     * @param $array - массив
     * Created by: Одилов О.У. on 25.10.2018 13:56
     */
    public static function PrintR($array, $die = false): void
    {
        echo '<pre>';
        print_r($array);
        echo '</pre>';
        if ($die) {
            die("Остановил выполнение метода");
        }
    }

    public static function VarDump($obj): void
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
    public static function MarkSearched($needle, $string): string
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
     * @param string $return - указывает что нужно возвращать
     * @return string - возвращает строку в виде 2д 01:20:30
     * @author Created by: Одилов О.У. on 09.11.2018 10:57
     */
    public static function DateTimeDiff($start_date_time, $end_date_time, string $return = ''): string
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
        return match ($return) {
            'y' => $years,
            'm' => $months,
            'd' => $diff->format('%a'),
            'h' => $hours,
            's' => $seconds,
            default => $dat_diff . date_format($date, "H:i:s"),
        };
    }

    /**
     * Метод поиска значения в массиве.
     * 1. Если найдет значение в массиве, то вернет ключ к массиву
     * 2. Если не найдет, то вернет значение -1
     * @param $array - массив
     * @param $needle - значение, которого нужно найти в указанном массиве
     * @param string $array_column_name - название колонки ассоциативного массива (по умолчанию его нет)
     * @return bool|false|int|string
     * Created by: Одилов О.У. on 18.10.2018
     */
    public static function SearchInArray($array, $needle, string $array_column_name = 'not'): bool|int|string
    {
        $key = false;
        if ($array_column_name != 'not') {
            $key = array_search($needle, array_column($array, $array_column_name));                                     // находим в обычном массиве желаемое значение
            if ($key !== FALSE)                                                                                         // если нашли, то проверяем, совпадают ли значения
            {
                if ($array[$key][$array_column_name] == $needle)                                                        // если нашли и значения совпадают, то вернем ключ к массиву
                {
                    return $key;
                }
            } else {
                return -1;
            }
        } else {
            $key = array_search($needle, $array);                                                                       // находим в обычном массиве желаемое значение
            if ($key !== FALSE)                                                                                         // если нашли, то проверяем, совпадают ли значения
            {
                if ($array[$key] == $needle)                                                                            // если нашли и значения совпадают, то вернем ключ к массиву
                {
                    return $key;
                }
            } else {
                return -1;
            }
        }
        return -1;
    }


    /**
     * Функция определения метода получаемого запроса.
     * Функция проверяет, какой метод был отправлен с сервера
     * Если POST, то возвращает данные из POST запроса.
     * Если GET, то возвращает данные из GET запроса.
     * Этот метод необходимо использовать для всех методов. Исключается использование конкретных методов получения запросов,
     * так как это метод сам определяет какой метод был отправлен со фронта
     * Пример вызова с других методов или классов: Assistant::GetServerMethod();
     * @return array|mixed - возвращает массив данных из POST/GET запроса
     * Created by: Одилов О.У. on 29.11.2018 15:01
     */
    public static function GetServerMethod(): mixed
    {
        return match ($_SERVER['REQUEST_METHOD']) {
            'POST' => Yii::$app->request->post(),
            'GET' => Yii::$app->request->get(),
            default => null,
        };
    }

    /**
     * Метод сохранения изображения в папку
     * @param $file
     * @param $upload_dir
     * @param $object_title
     * @param $image_type
     * @return int|string
     */
    public static function UploadPicture($file, $upload_dir, $object_title, $image_type): int|string
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
    public static function CallProcedure($procedure_name): array
    {
        return Yii::$app->db->createCommand("CALL $procedure_name")->queryAll();
    }


    /**
     * Название метода: GetDateWithMicroseconds()
     * Метод получения  даты до миллисекунд
     * @param $date_time - дата/время до миллисекунд
     * @return string - дата в виде строки
     * Created by: Одилов О.У. on 19.12.2018 14:08
     * @throws Exception
     */
    public static function GetDateWithMicroseconds($date_time): string
    {
        $date = new DateTime($date_time);
        return $date->format('Y-m-d H:i:s.u');
    }

    /**
     * Название метода: AddConditionForParameters()
     * Процедура создания из типов параметров и параметров условия поиска.
     * В основном используется для передачи данных в процедуре (GetSensorsParametersLastValuesOptimized) по историческим данным.
     * Например, нам пришли параметры вида "2-122, 3-83, 2-164" и мы должны условие поиска создать.
     * Результат будет таковым: (parameter_type_id = 2 AND parameter_type_id = 122) AND (parameter_type_id = 3 AND parameter_type_id = 83)
     * @param $parameters_with_parameter_types - параметры в виде "2-122, 3-83, 2-164". Обязательно, чтоб тип параметры был первым, а потом сам параметр
     * @param string $return - что возвращать, то есть результат, или с каких таблиц выборку сделать.
     * @param string $delimiter
     * @return array|string - возвращает массив.
     * Если возвращает '' - то выборка должна произойти из parameter_value и parameter_handbook_value
     * Если возвращает v - то выборка должна произойти только из parameter_value
     * Если возвращает h - то выборка должна произойти только из parameter_handbook_value
     * Пример вызова : AddConditionForParameters("2-122, 3-83")
     * Возвращает Array ( [parameter_type_value] => v [parameters] => (parameter_type_id = 2 AND parameter_type_id = 122)
     *  AND (parameter_type_id = 3 AND parameter_type_id = 83)
     * Обязательно нужно указать тип параметра, иначе не сработает метод!
     * Created by: Одилов О.У. on 14.12.2018 14:17
     */
    public static function AddConditionForParameters($parameters_with_parameter_types, string $return = '', string $delimiter = '-'): array|string
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
            $flag_handbook_value = 0;                                                                                   // переменная указывает на то, что данные нужно выбрать из таблицы handbook, так как тип параметра 1
            $flag_value = 0;                                                                                            // переменная указывает на то, что данные нужно выбрать из таблицы parameter_value, так как тип параметра 2 или 3
            $parameters_sum = '';
            $parameters = explode(',', $parameters_with_parameter_types);                                       // строку вида "1-122, 3-164, 2-83" разбиваем и добавим в массив
            $index = 0;
            foreach ($parameters as $parameter_type_id_parameter_id) {
                $parameter_types = explode($delimiter, $parameter_type_id_parameter_id);                                  // получаем данные [0] => 1, [1] => 122
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
     * @throws \yii\db\Exception
     */
    public static function GetSensorsParametersValuesPeriod($sensor_condition, $parameter_condition, $date_time_start, $date_time_end): array
    {
        $parameters = self::AddConditionForParameters($parameter_condition);
        $parameter_condition = $parameters['parameters'];
        $parameter_type_table = $parameters['parameter_type_table'];
        return self::CallProcedure("GetSensorsParametersValuesOptimizedPeriod('$sensor_condition', '$parameter_condition', '$date_time_start',  '$date_time_end' ,'$parameter_type_table')");
    }

    /**
     * @throws \yii\db\Exception
     */
    public static function GetEquipmentsParametersValuesPeriod($equipment_condition, $parameter_condition, $date_time_start, $date_time_end): array
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
     * @throws \yii\db\Exception
     */
    public static function GetSensorsParametersLastValues($sensor_condition, $parameter_condition, $date_time): array
    {
        $parameters = self::AddConditionForParameters($parameter_condition, '', ':');
        $parameter_condition = $parameters['parameters'];
        $parameter_type_table = $parameters['parameter_type_table'];
        return self::CallProcedure("GetSensorsParametersLastValuesOptimized('$sensor_condition', '$parameter_condition', '$date_time', '$parameter_type_table')");
    }

    /**
     * @throws \yii\db\Exception
     */
    public static function GetEquipmentsParametersLastValues($equipment_condition, $parameter_condition, $date_time): array
    {
        $parameters = self::AddConditionForParameters($parameter_condition, '', ':');
        $parameter_condition = $parameters['parameters'];
        $parameter_type_table = $parameters['parameter_type_table'];
        return self::CallProcedure("GetEquipmentsParametersLastValuesOptimized('$equipment_condition', '$parameter_condition', '$date_time', '$parameter_type_table')");
    }

    /**
     * Название метода: AddConditionOperator()
     * Метод добавления условий (операторы MYSQL) для строки (операторы and, or и тд для Mysql запроса)
     * Например, нам нужно добавить условие для строки, то есть добавить условие и добавить оператор AND. Этот метод автоматически
     * добавляет такие операторы.
     * @param $condition_variable - переменная в которой уже есть или нет условия
     * @param $condition - условие, которое хотим добавить
     * @param string $operator - оператор, которого хотим добавить. По умолчанию указано AND
     * @return string
     * Created by: Одилов О.У. on 19.12.2018 11:18
     */
    public static function AddConditionOperator($condition_variable, $condition, string $operator = ""): string
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
     * Метод фильтрации массива по конкретному полю.
     * Например необходимо вывести всех работников, у находящихся в ламповой. Для таких целей можно использовать.
     *
     * Входные параметры:
     * @param $array - ассоциативный массив
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
    public static function ArrayFilter($array, $array_column_name, $needle): array
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
    public static function RandomString($limit): string
    {
        return substr(rand(1000, 50000) . base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, $limit);
    }

    /**
     * Метод UploadFile() - загрузка файлов на сервер
     * @param $blob - blob файл с типом файла
     * @param $file_name - наименование файла
     * @param $table - таблица
     * @return string - возвращает строку которую необходимо записать в БД
     *
     * @package frontend\controllers
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.08.2019 17:04
     */
    public static function UploadFile($blob, $file_name, $table, $extension = Null): string
    {
        $file_path = "";
        $data = explode(',', $blob);
        $intermediate = explode(';', $data[0]);
        $type = explode('/', $intermediate[0]);
        if (isset($data[1])) {
            $content = base64_decode($data[1]);
            if (!file_exists(Yii::getAlias('@app') . '/web/img/' . $table)) {
                if (!mkdir($concurrentDirectory = Yii::getAlias('@app') . '/web/img/' . $table) && !is_dir($concurrentDirectory)) {
                    throw new RuntimeException(sprintf('UploadFile. Directory "%s" was not created', $concurrentDirectory));
                }
            }
            $date_now = date('d-m-Y H-i-s.U');
            $uploaded_file = Yii::getAlias('@app') . '/web/img/' . $table . '/' . $date_now . '_' . $file_name;                              //объявляем и инициируем переменную для хранения названия файла, состоящего из
            $file_path = '/img/' . $table . '/' . $date_now . '_' . $file_name;
            file_put_contents($uploaded_file, $content);
        } else {
            throw new RuntimeException(sprintf('UploadFile. Данных для сохранения нет. data[1] пуст'));
        }
        return $file_path;
    }

    /**
     * Метод UploadFileChat() - загрузка файлов на сервер с модуля Чат (отличия от обычного - вложение определяется в данном методе)
     * @param $blob - blob файл с типом файла
     * @param $file_name - наименование файла
     * @param $table - таблица
     * @return string - возвращает путь к файлу которую необходимо записать в БД
     *
     * @package frontend\controllers
     *
     * @author Якимов М.Н.
     * Created date: on 29.01.2020 17:04
     */
    public static function UploadFileChat($blob, $file_name, $table): string
    {
        $data = explode(',', $blob);
        $intermediate = explode(';', $data[0]);
        $type = explode('/', $intermediate[0])[1];

        $content = base64_decode($data[1]);
        if (!file_exists(Yii::getAlias('@app') . '/web/img/' . $table)) {
            if (!mkdir($concurrentDirectory = Yii::getAlias('@app') . '/web/img/' . $table) && !is_dir($concurrentDirectory)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }
        $date_now = date('d-m-Y H-i-s.U');
        $uploaded_file = Yii::getAlias('@app') . '/web/img/' . $table . '/' . $date_now . '_' . $file_name . ($type ? '.' . $type : '');
        $file_path = '/img/' . $table . '/' . $date_now . '_' . $file_name . ($type ? '.' . $type : '');
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
    public static function upload_mobile_file($blob, $file_name, $table, $extension): string
    {
        $content = base64_decode($blob);
        if (!file_exists(Yii::getAlias('@app') . '/web/img/' . $table)) {
            if (!mkdir($concurrentDirectory = Yii::getAlias('@app') . '/web/img/' . $table) && !is_dir($concurrentDirectory)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
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
     * @return bool|string - дата в виде строки
     * @example tag_date=Assistant::GetDateTimeNow();
     * Created by: Якимов М.Н. on 08.06.2019
     */
    public static function GetDateTimeNow(): bool|string
    {
        //ВАЖНО!!! часовой пояс должен быть верно настроен по UTC это важно

        //Вариант 1 с получением часового пояса
        $time_zone = new DateTimeZone(AMICUM_TIME_ZONE);
        if ($time_zone) {
            $now = DateTime::createFromFormat('U.u', sprintf('%.f', microtime(true)))->setTimeZone($time_zone);
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
     * @return bool|string - дата в виде строки
     * @example tag_date=Assistant::GetDateNow();
     * Created by: Якимов М.Н. on 08.06.2019
     */
    public static function GetDateNow(): bool|string
    {
        $time_zone = new DateTimeZone(AMICUM_TIME_ZONE);
        if ($time_zone) {
            $now = DateTime::createFromFormat('U.u', sprintf('%.f', microtime(true)))->setTimeZone($time_zone);
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
    public static function GetEndShiftDateTime($date, bool $with_time = false): array
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
    public static function cmpDate($o1, $o2): int
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
     * @throws Exception
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
                throw new Exception("На данном предприятии нет данной смены");
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
    public static function GetCountShifts(): int
    {
        if (defined('AMICUM_DEFAULT_SHIFTS')) {
            return AMICUM_DEFAULT_SHIFTS;
        } else {
            return 4;
        }
    }


    /**
     * GetShortFullName - Метод получения Фамилии И.О.
     * @param $first_name
     * @param $patronymic
     * @param $last_name
     * @return int|string
     */
    public static function GetShortFullName($first_name, $patronymic, $last_name): int|string
    {
        $name = mb_substr($first_name, 0, 1);
        $patronymic = mb_substr($patronymic, 0, 1);

        return $last_name . " " . ($name ? $name . "." : "") . ($patronymic ? $patronymic . "." : "");
    }

    /**
     * GetFullName - Метод получения Фамилии Имени Отчества
     * @param $first_name
     * @param $patronymic
     * @param $last_name
     * @return int|string
     */
    public static function GetFullName($first_name, $patronymic, $last_name): int|string
    {
        return $last_name . " " . ($first_name ? $first_name : "") . " " . ($patronymic ? $patronymic : "");
    }

    /**
     * GetShiftByDateTime - Метод получения Смены по времени
     * @param $date_time - календарное дата и время
     * @param $count_shifts - количество смен на предприятии
     * @return array
     */
    public static function GetShiftByDateTime($date_time = null, $count_shifts = null): array
    {
        $result = array(
            'shift_id' => null,
            'shift_title' => "",
            'date_work' => null,
            'shift_id_next' => null,
            'date_work_next' => null,
            'shift_id_last' => null,
            'date_work_last' => null,
        );

        if (!$count_shifts) {
            $count_shifts = self::GetCountShifts();
        }
        if ($date_time == null) {
            $date_time = self::GetDateTimeNow();
        }

        $hours = date('G', strtotime($date_time));

        if ($count_shifts == 3) {
            if ($hours < 8) {
                $result = array(
                    'shift_id' => 3,
                    'shift_title' => "Смена 3",
                    'date_work' => date("Y-m-d", strtotime($date_time . ' -1 day')),
                    'shift_id_next' => 1,
                    'date_work_next' => date("Y-m-d", strtotime($date_time)),
                    'shift_id_last' => 2,
                    'date_work_last' => date("Y-m-d", strtotime($date_time . ' -1 day')),
                );
            } elseif ($hours < 16) {
                $result = array(
                    'shift_id' => 1,
                    'shift_title' => "Смена 1",
                    'date_work' => date("Y-m-d", strtotime($date_time)),
                    'shift_id_next' => 2,
                    'date_work_next' => date("Y-m-d", strtotime($date_time)),
                    'shift_id_last' => 3,
                    'date_work_last' => date("Y-m-d", strtotime($date_time . ' -1 day')),
                );
            } elseif ($hours < 24) {
                $result = array(
                    'shift_id' => 2,
                    'shift_title' => "Смена 2",
                    'date_work' => date("Y-m-d", strtotime($date_time)),
                    'shift_id_next' => 3,
                    'date_work_next' => date("Y-m-d", strtotime($date_time)),
                    'shift_id_last' => 1,
                    'date_work_last' => date("Y-m-d", strtotime($date_time)),
                );
            }
        } else {
            if ($hours < 2) {
                $result = array(
                    'shift_id' => 3,
                    'shift_title' => "Смена 3",
                    'date_work' => date("Y-m-d", strtotime($date_time . ' -1 day')),
                    'shift_id_next' => 4,
                    'date_work_next' => date("Y-m-d", strtotime($date_time . ' -1 day')),
                    'shift_id_last' => 2,
                    'date_work_last' => date("Y-m-d", strtotime($date_time . ' -1 day')),
                );
            } elseif ($hours < 8) {
                $result = array(
                    'shift_id' => 4,
                    'shift_title' => "Смена 4",
                    'date_work' => date("Y-m-d", strtotime($date_time . ' -1 day')),
                    'shift_id_next' => 1,
                    'date_work_next' => date("Y-m-d", strtotime($date_time)),
                    'shift_id_last' => 3,
                    'date_work_last' => date("Y-m-d", strtotime($date_time . ' -1 day')),
                );
            } elseif ($hours < 14) {
                $result = array(
                    'shift_id' => 1,
                    'shift_title' => "Смена 1",
                    'date_work' => date("Y-m-d", strtotime($date_time)),
                    'shift_id_next' => 2,
                    'date_work_next' => date("Y-m-d", strtotime($date_time)),
                    'shift_id_last' => 4,
                    'date_work_last' => date("Y-m-d", strtotime($date_time . ' -1 day')),
                );
            } elseif ($hours < 20) {
                $result = array(
                    'shift_id' => 2,
                    'shift_title' => "Смена 2",
                    'date_work' => date("Y-m-d", strtotime($date_time)),
                    'shift_id_next' => 3,
                    'date_work_next' => date("Y-m-d", strtotime($date_time)),
                    'shift_id_last' => 1,
                    'date_work_last' => date("Y-m-d", strtotime($date_time)),
                );
            } elseif ($hours <= 24) {
                $result = array(
                    'shift_id' => 3,
                    'shift_title' => "Смена 3",
                    'date_work' => date("Y-m-d", strtotime($date_time)),
                    'shift_id_next' => 4,
                    'date_work_next' => date("Y-m-d", strtotime($date_time)),
                    'shift_id_last' => 2,
                    'date_work_last' => date("Y-m-d", strtotime($date_time)),
                );
            }
        }

        return $result;
    }

    /**
     * jsonDecodeAmicum - Метод декодирования json строки из смежных системы, с обработкой ошибок десериализации
     * @param $json_raw - исходная строка
     */
    public static function jsonDecodeAmicum($json_raw)
    {
        $log = new LogAmicumFront("jsonDecodeAmicum");

        $json = null;

        try {
            $json = json_decode($json_raw);

            switch (json_last_error()) {
                case JSON_ERROR_NONE:
                    $log->addLog("Ошибок нет");
                    break;
                case JSON_ERROR_DEPTH:
                    $log->addData(json_last_error_msg(), 'json_last_error_msg', __LINE__);
                    throw new Exception("Достигнута максимальная глубина стека");
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    $log->addData(json_last_error_msg(), 'json_last_error_msg', __LINE__);
                    throw new Exception("Некорректные разряды или несоответствие режимов");
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    $log->addData(json_last_error_msg(), 'json_last_error_msg', __LINE__);
                    throw new Exception("Некорректный управляющий символ");
                    break;
                case JSON_ERROR_SYNTAX:
                    $log->addData(json_last_error_msg(), 'json_last_error_msg', __LINE__);
                    throw new Exception("Синтаксическая ошибка, некорректный JSON");
                    break;
                case JSON_ERROR_UTF8:
                    $log->addData(json_last_error_msg(), 'json_last_error_msg', __LINE__);
                    throw new Exception("Некорректные символы UTF-8, возможно неверно закодирован");
                    break;
                default:
                    $log->addData(json_last_error_msg(), 'json_last_error_msg', __LINE__);
                    throw new Exception("Неизвестная ошибка");
                    break;
            }

        } catch (Throwable $ex) {
            $log->addData($json_raw, '$json_raw', __LINE__);
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $json], $log->getLogAll());
    }

    /** GetStartProdDateTime - Метод получения даты и времени начала выборки производственной даты
     * @param $date - дата на которую нужно получить начало выборки по производственной дате
     * @param null $start_hour - час начала смены
     * @return string
     */
    public static function GetStartProdDateTime($date, $start_hour = null)
    {

        if (!$start_hour) {
            $start_hour = AMICUM_DEFAULT_START_HOUR - 3;
        }

        if ($start_hour < 0) {
            $start_hour = 24 + $start_hour;
            $date =  strtotime($date . " -1day");
        } else {
            $date = strtotime($date);
        }

        $start_hour = $start_hour < 10 ? "0" . $start_hour : $start_hour;

        return date("Y-m-d", $date) . " " . $start_hour . ":00:00";
    }

    /** GetEndProdDateTime - Метод получения даты и времени окончания выборки производственной даты
     * @param $date - дата на которую нужно получить начало выборки по производственной дате
     * @param null $end_hour - час окончания смены
     * @return string
     */
    public static function GetEndProdDateTime($date, $end_hour = null)
    {

        if (!$end_hour) {
            $end_hour = AMICUM_DEFAULT_START_HOUR;
        }

        $date =  strtotime($date . " +1day");

        $end_hour = $end_hour < 10 ? "0" . $end_hour : $end_hour;

        return date("Y-m-d", $date) . " " . $end_hour . ":00:00";
    }

}