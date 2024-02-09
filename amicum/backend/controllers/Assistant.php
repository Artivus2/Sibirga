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

namespace backend\controllers;

use DateTime;
use DateTimeZone;
use frontend\models\Main;
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
    // SecondsToTime            - Метод перевода секунд в формат H:i:s
    // RandomString             - Метод генерации случайных значений (паролей и тд)
    // GetMysqlTimeDifference   - Метод для нахождения разницы во времени между датами в формате MySQL.
    // PrintR                   - Метод вывода в нормальном виде массива
    // VarDump                  - Метод вывода в формате VarDump
    // GetServerMethod          - Функция определения метода получаемого запроса.
    // UploadPicture            - Метод сохранения изображения в папку
    // getDsnAttribute          - Метод возвращает названия БД
    // CallProcedure            - Метод вызова процедур Mysql
    // getCountWeek             - Метод возвращает количество недель в году
    // GetDateNow               - Метод получения текущей даты и времени до микросекунд
    // GetDateTimeNow           - Метод получения текущей даты и времени без микросекунд
    // GetDateFormatYMD         - Метод получения текущей даты и времени до микросекунд
    // MarkSearched             - Метод поиска с выделением найденного
    // jsonRecoveryByPhp        - Метод восстановления json строки при кодировании средствами php (json_encode)
    // jsonRecoveryBy1C         - Метод восстановления json строки при кодировании средствами 1c
    // jsonDecodeAmicum         - Метод декодирования json строки из смежных системы
    // addMain                  - Метод создания уникального айди для всей БД
    // GetShiftByDateTime       - Метод получения Смены по времени
    // GetCountShifts           - Метод получения текущей настройки количества смен на предприятии
    // GetFullName              - Метод получения Фамилии Имени Отчества
    // GetStartProdDateTime     - Метод получения даты и времени начала выборки производственной даты
    // GetEndProdDateTime       - Метод получения даты и времени окончания выборки производственной даты

    /**
     * SecondsToTime - Метод перевода секунд в формат H:i:s
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

    /**
     * RandomString - Метод генерации случайных значений (паролей и тд)
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

    // TODO: Проверки

    /**
     * GetMysqlTimeDifference - Метод для нахождения разницы во времени (в секундах) между датами в формате MySQL.
     * Никаких проверок не проводится!!
     * @param $timestamp_1 -   первая метка времени
     * @param $timestamp_2 -   Вторая метка времени
     * @return float|int    -   разница между датами в секундах
     */
    public static function GetMysqlTimeDifference($timestamp_1, $timestamp_2)
    {
        $timestamp_1_seconds = strtotime(explode('.', $timestamp_1)[0]);
        $timestamp_2_seconds = strtotime(explode('.', $timestamp_2)[0]);
        return abs($timestamp_1_seconds - $timestamp_2_seconds);
    }


    /**
     * PrintR - Метод вывода в нормальному виде массива
     * Использует функцию print_r()
     * @param $array - массив
     * Created by: Одилов О.У. on 25.10.2018 13:56
     */
    public static function PrintR($array, $die = false)
    {
        echo '<pre>';
        print_r($array);
        echo '</pre>';
        if ($die == true)
            die("\nОстановил выполнения метода\n");
    }

    public static function VarDump($obj)
    {
        echo '<pre>';
        var_dump($obj);
        echo '</pre>';
    }


    /**
     * GetServerMethod - Функция определения метода получаемого запроса.
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
        if ($_SERVER['REQUEST_METHOD'] == 'POST') return Yii::$app->request->post();
        else if ($_SERVER['REQUEST_METHOD'] == 'GET') return Yii::$app->request->get();
    }

    /**
     * UploadPicture - Метод сохранения изображения в папку
     * @param $file
     * @param $upload_dir
     * @param $object_title
     * @param $image_type
     * @return int|string
     */
    public static function UploadPicture($file, $upload_dir, $object_title, $image_type)
    {
        $upload_file = $upload_dir . date('d-m-Y H-i') . '_' . $object_title;
        if (move_uploaded_file($file['tmp_name'], $upload_file)) {
            return $upload_file;
        }

        return -1;
    }

    /**
     * getDsnAttribute - метод возврашает названия БД
     * @param $name - имя соединения
     * @param $dsn - dsn строка
     * @return mixed|null
     */
    public static function getDsnAttribute($name, $dsn)
    {
        if (preg_match('/' . $name . '=([^;]*)/', $dsn, $match)) {
            return $match[1];
        } else {
            return null;
        }
    }


    /**
     * Название метода: CallProcedure()
     * CallProcedure - Метод вызова процедур Mysql
     * @param $procedure_name - название процедуры
     * @return array - массив данных
     * @throws \yii\db\Exception
     * Created by: Одилов О.У. on 19.12.2018 14:09
     */
    public static function CallProcedure($procedure_name)
    {
        return Yii::$app->db->createCommand("CALL $procedure_name")->queryAll();
    }

    /**
     * getCountWeek - метод возвращает количество недель в году
     * @param $year - год, в котором получаем количество недель
     * @return false|int|string
     */
    public static function getCountWeek($year)
    {
        $date = date('w', mktime(0, 0, 0, 12, 31, $year));
        $day = ($date < 4 ? 31 - $date : 31);
        return date('W', mktime(0, 0, 0, 12, $day, $year)) + ($date < 4 ? 1 : 0);
    }

    /**
     * Название метода: GetDateNow()
     * GetDateNow - Метод получения текущей даты и времени до микросекунд
     * @return string - дата в виде строки
     * @example tag_date=Assistant::GetDateNow();
     * Created by: Якимов М.Н. on 08.06.2019
     */
    public static function GetDateNow()
    {
        //ВАЖНО!!! часовой пояс должен быть верно настроен по UTC это важно

        //Вариант 1 с получением часового пояса
        $time_zone = new DateTimeZone(AMICUM_TIME_ZONE);
        if ($time_zone) {
            $now = DateTime::createFromFormat('U.u', sprintf('%.f', microtime(true)))->setTimeZone($time_zone);
        } else {
            return false;
        }
        return $now->format('Y-m-d H:i:s.u');

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
     * Название метода: GetDateTimeNow()
     * GetDateTimeNow - Метод получения текущей даты и времени без микросекунд
     * @return string - дата в виде строки
     * @example tag_date=Assistant::GetDateNow();
     * Created by: Якимов М.Н. on 08.06.2019
     */
    public static function GetDateTimeNow($format_string = false)
    {
        //ВАЖНО!!! часовой пояс должен быть верно настроен по UTC это важно

        //Вариант 1 с получением часового пояса
        $time_zone = new DateTimeZone(AMICUM_TIME_ZONE);
        if ($time_zone) {
            $now = DateTime::createFromFormat('U.u', sprintf('%.f', microtime(true)))->setTimeZone($time_zone);
        } else {
            return false;
        }
        if ($format_string) {
            return $now->format('Y_m_d_H_i_s');
        } else {
            return $now->format('Y-m-d H:i:s');
        }

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
     * GetDateFormatYMD - Метод получения текущей даты и времени до микросекунд
     * @return string - дата в виде строки
     * @example tag_date=Assistant::GetDateNow();
     * Created by: Якимов М.Н. on 08.06.2019
     */
    public static function GetDateFormatYMD()
    {
        //ВАЖНО!!! часовой пояс должен быть верно настроен по UTC это важно

        $time_zone = new DateTimeZone(AMICUM_TIME_ZONE);
        if ($time_zone) {
            $now = DateTime::createFromFormat('U.u', sprintf('%.f', microtime(true)))->setTimeZone($time_zone);
        } else {
            return false;
        }
        return $now->format('Y-m-d');
    }

    /**
     * MarkSearched - Метод поиска с выделением найденного
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
     * jsonRecoveryByPhp - Метод восстановления json строки при кодировании средствами php (json_encode)
     * @param $json_raw - исходная строка
     * @return string - обработанная json строка
     */
    public static function jsonRecoveryByPhp($json_raw): string
    {

        $json_raw = str_replace("\\", "\\\\", $json_raw);
        $json_raw = str_replace('u0', "\u0", $json_raw);
        $json_raw = str_replace('"{', '{', $json_raw);
        $json_raw = str_replace('}"', '}', $json_raw);

        return $json_raw;
    }

    /**
     * jsonRecoveryBy1C - Метод восстановления json строки при кодировании средствами 1c
     * @param $json_raw - исходная строка
     * @return string - обработанная json строка
     */
    public static function jsonRecoveryBy1C($json_raw): string
    {
        $json_raw = str_replace("\r\n", "", $json_raw);
        $json_raw = str_replace('\\\"', "'", $json_raw);
        return $json_raw;
    }

    /**
     * jsonDecodeAmicum - Метод декодирования json строки из смежных системы, с обработкой ошибок десериализации
     * @param $json_raw - исходная строка
     */
    public static function jsonDecodeAmicum($json_raw)
    {
        $status = 0;
        $json = null;
        $method_name = "jsonDecodeAmicum. ";
        $errors = [];
        $warnings = [];
        try {
            $json = json_decode($json_raw);

            switch (json_last_error()) {
                case JSON_ERROR_NONE:
                    $status = 1;
                    $warnings[] = $method_name . 'Ошибок нет';
                    break;
                case JSON_ERROR_DEPTH:
                    $errors[] = $method_name . 'Достигнута максимальная глубина стека';
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    $errors[] = $method_name . 'Некорректные разряды или несоответствие режимов';
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    $errors[] = $method_name . 'Некорректный управляющий символ';
                    break;
                case JSON_ERROR_SYNTAX:
                    $errors[] = $method_name . 'Синтаксическая ошибка, некорректный JSON';
                    break;
                case JSON_ERROR_UTF8:
                    $errors[] = $method_name . 'Некорректные символы UTF-8, возможно неверно закодирован';
                    break;
                default:
                    $errors[] = $method_name . 'Неизвестная ошибка';
                    break;
            }

            if (!$status) {
                $errors[] = $json_raw;
                $errors[] = json_last_error_msg();
            }
        } catch (Throwable $ex) {
            $status = 0;
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
        }

        return array('Items' => $json, 'errors' => $errors, 'warnings' => $warnings, 'status' => $status);
    }

    /**
     * addMain - метод создания уникального айди для всей БД
     * @param $table_address
     * @return int
     */
    public static function addMain($table_address)
    {
        $main = new Main();
        $main->db_address = "amicum3";
        $main->table_address = $table_address;
        if ($main->save()) {
            return $main->id;
        } else {
            return -1;
        }
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
     * Метод преобразования даты в пакете службы сбора данных Горизонт
     * @param $date
     * @return string|null
     */
    public static function repairDate($date)
    {
        $date_repair = null;
        $date = str_replace("Z", "", $date);
        $date = str_replace("z", "", $date);
        $date_repair_array = explode("T", explode("+", explode(" ", $date)[0])[0]);

        if (count($date_repair_array) == 2) {
            $date_repair = $date_repair_array[0] . " " . $date_repair_array[1];
        }
        return $date_repair;
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
     * GetShiftByDateTime - Метод получения Смены по времени
     * @param $date_time    - календарное дата и время
     * @param $count_shifts - количество смен на предприятии
     * @return array
     */
    public static function GetShiftByDateTime($date_time, $count_shifts = null): array
    {
        $result = array(
            'shift_id' => null,
            'shift_title' => "",
            'date_work' => null,
        );

        if (!$count_shifts) {
            $count_shifts = self::GetCountShifts();
        }

        $hours = date('G', strtotime($date_time));

        if ($count_shifts == 3) {
            if ($hours < 8) {
                $result = array(
                    'shift_id' => 3,
                    'shift_title' => "Смена 3",
                    'date_work' => date("Y-m-d", strtotime($date_time . ' -1 day')),
                );
            } elseif ($hours < 16) {
                $result = array(
                    'shift_id' => 1,
                    'shift_title' => "Смена 1",
                    'date_work' => date("Y-m-d", strtotime($date_time))
                );
            } elseif ($hours < 24) {
                $result = array(
                    'shift_id' => 2,
                    'shift_title' => "Смена 2",
                    'date_work' => date("Y-m-d", strtotime($date_time))
                );
            }
        } else {
            if ($hours < 2) {
                $result = array(
                    'shift_id' => 3,
                    'shift_title' => "Смена 3",
                    'date_work' => date("Y-m-d", strtotime($date_time . ' -1 day')),
                );
            } elseif ($hours < 8) {
                $result = array(
                    'shift_id' => 4,
                    'shift_title' => "Смена 4",
                    'date_work' => date("Y-m-d", strtotime($date_time . ' -1 day'))
                );
            } elseif ($hours < 14) {
                $result = array(
                    'shift_id' => 1,
                    'shift_title' => "Смена 1",
                    'date_work' => date("Y-m-d", strtotime($date_time))
                );
            } elseif ($hours < 20) {
                $result = array(
                    'shift_id' => 2,
                    'shift_title' => "Смена 2",
                    'date_work' => date("Y-m-d", strtotime($date_time))
                );
            } elseif ($hours <= 24) {
                $result = array(
                    'shift_id' => 3,
                    'shift_title' => "Смена 3",
                    'date_work' => date("Y-m-d", strtotime($date_time))
                );
            }
        }

        return $result;
    }

    /**
     * GetShiftByDateTimeWorkingHours - Метод получения Смены по времени
     * @param $date_time    - календарное дата и время
     * @param $count_shifts - количество смен на предприятии
     * @return array
     */
    public static function GetShiftByDateTimeWorkingHours($date_time = null, $count_shifts = null): array
    {
        $result = array(
            'shift_id' => null,
            'shift_title' => "",
            'date_work' => null,
        );

        if (!$count_shifts) {
            $count_shifts = self::GetCountShifts();
        }
        if (!$date_time) {
            $date_time = self::GetDateTimeNow();
        }

        $hours = date('G', strtotime($date_time));

        if ($count_shifts == 3) {
            if ($hours < 6) {
                $result = array(
                    'shift_id' => 3,
                    'shift_title' => "Смена 3",
                    'date_work' => date("Y-m-d", strtotime($date_time . ' -1 day')),
                );
            } elseif ($hours < 14) {
                $result = array(
                    'shift_id' => 1,
                    'shift_title' => "Смена 1",
                    'date_work' => date("Y-m-d", strtotime($date_time))
                );
            } elseif ($hours < 22) {
                $result = array(
                    'shift_id' => 2,
                    'shift_title' => "Смена 2",
                    'date_work' => date("Y-m-d", strtotime($date_time))
                );
            }
        } else {
            if ($hours < 6) {
                $result = array(
                    'shift_id' => 4,
                    'shift_title' => "Смена 4",
                    'date_work' => date("Y-m-d", strtotime($date_time . ' -1 day'))
                );
            } elseif ($hours < 12) {
                $result = array(
                    'shift_id' => 1,
                    'shift_title' => "Смена 1",
                    'date_work' => date("Y-m-d", strtotime($date_time))
                );
            } elseif ($hours < 18) {
                $result = array(
                    'shift_id' => 2,
                    'shift_title' => "Смена 2",
                    'date_work' => date("Y-m-d", strtotime($date_time))
                );
            } elseif ($hours <= 24) {
                $result = array(
                    'shift_id' => 3,
                    'shift_title' => "Смена 3",
                    'date_work' => date("Y-m-d", strtotime($date_time))
                );
            }
        }

        return $result;
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