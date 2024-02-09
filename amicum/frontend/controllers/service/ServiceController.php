<?php
/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\controllers\service;


use backend\controllers\Assistant;
use frontend\controllers\Assistant as AssistantFront;
use frontend\controllers\system\LogAmicumFront;
use Throwable;
use yii\web\Controller;

/**
 * Class ServiceController - класс содержащий общие вспомогательные методы АМИКУМ
 * @package frontend\controllers\system
 */
class ServiceController extends Controller
{
    // GetCurrentShift      - Метод получения текущей смены на основе текущего времени
    // GetOldShift          - Метод получения предыдущей смены на основе текущего времени
    // GetCountShift        - Метод получения количества смен по умолчанию
    // GetDateTimeCurrent   - Метод получения даты и времени сервера, через ReadManager

    /**
     * GetCurrentShift - Метод получения текущей смены на основе текущего времени
     * @param date_time - дата и время, на которое вычисляется смена
     * @return array
     * @example http://127.0.0.1/read-manager-amicum?controller=service\Service&method=GetCurrentShift&subscribe=&data={}
     * @example http://127.0.0.1/read-manager-amicum?controller=service\Service&method=GetCurrentShift&subscribe=&data={%22date_time%22:%222023-03-21%2016:05:22%22}
     */
    public static function GetCurrentShift($data_post = NULL): array
    {
        $chosenShift = array(
            "id" => null,
            "title" => "Выберите смену",
        );

        $transfer_date = null;

        $result = array(
            "chosen_shift" => $chosenShift,
            "chosen_date" => null,
            "current_date_time" => Assistant::GetDateTimeNow(),
            "transfer_date_time" => $transfer_date
        );

        $log = new LogAmicumFront("GetCurrentShift");

        try {
            $log->addLog("Начало выполнения метода");

            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                property_exists($post_dec, "date_time") and $post_dec->date_time != ""
            ) {
                $calcDate = $post_dec->date_time;
                $transfer_date = $post_dec->date_time;
            } else {
                $calcDate = Assistant::GetDateTimeNow();
            }


            $currentHours = date("H", strtotime($calcDate));

            if (AssistantFront::GetCountShifts() == 3) {
                if ($currentHours >= 0 && $currentHours < 5) {
                    $chosenShift = array(
                        "id" => 3,
                        "title" => "Смена 3",
                        "title_short" => "III"
                    );
                    $calcDate = date("Y-m-d", strtotime($calcDate . " -1 day"));
                } else if ($currentHours >= 5 && $currentHours < 13) {
                    $chosenShift = array(
                        "id" => 1,
                        "title" => "Смена 1",
                        "title_short" => "I"
                    );
                    $calcDate = date("Y-m-d", strtotime($calcDate));
                } else if ($currentHours >= 13 && $currentHours < 19) {
                    $chosenShift = array(
                        "id" => 2,
                        "title" => "Смена 2",
                        "title_short" => "II"
                    );
                    $calcDate = date("Y-m-d", strtotime($calcDate));
                } else if ($currentHours >= 19) {
                    $chosenShift = array(
                        "id" => 3,
                        "title" => "Смена 3",
                        "title_short" => "III"
                    );
                    $calcDate = date("Y-m-d", strtotime($calcDate));
                }
            } else {
                if ($currentHours >= 0 && $currentHours < 5) {
                    $chosenShift = array(
                        "id" => 4,
                        "title" => "Смена 4",
                        "title_short" => "IV"
                    );
                    $calcDate = date("Y-m-d", strtotime($calcDate . " -1 day"));
                } else
                    if ($currentHours >= 5 && $currentHours < 11) {
                        $chosenShift = array(
                            "id" => 1,
                            "title" => "Смена 1",
                            "title_short" => "I"
                        );
                        $calcDate = date("Y-m-d", strtotime($calcDate));
                    } else if ($currentHours >= 11 && $currentHours < 17) {
                        $chosenShift = array(
                            "id" => 2,
                            "title" => "Смена 2",
                            "title_short" => "II"
                        );
                        $calcDate = date("Y-m-d", strtotime($calcDate));
                    } else if ($currentHours >= 17 && $currentHours < 23) {
                        $chosenShift = array(
                            "id" => 3,
                            "title" => "Смена 3",
                            "title_short" => "III"
                        );
                        $calcDate = date("Y-m-d", strtotime($calcDate));
                    } else
                        if ($currentHours >= 23) {
                            $chosenShift = array(
                                "id" => 4,
                                "title" => "Смена 4",
                                "title_short" => "IV"
                            );
                            $calcDate = date("Y-m-d", strtotime($calcDate));
                        }
            }
            $result = array(
                "chosen_shift" => $chosenShift,
                "chosen_date" => $calcDate,
                "current_date_time" => Assistant::GetDateTimeNow(),
                "transfer_date_time" => $transfer_date
            );
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");


        return array_merge(["Items" => $result], $log->getLogAll());
    }

    /**
     * GetOldShift - Метод получения предыдущей смены на основе текущего времени
     * @param date_time - дата и время, на которое вычисляется смена
     * @return array
     * @example http://127.0.0.1/read-manager-amicum?controller=service\Service&method=GetOldShift&subscribe=&data={}
     * @example http://127.0.0.1/read-manager-amicum?controller=service\Service&method=GetOldShift&subscribe=&data={%22date_time%22:%222023-03-21%2016:05:22%22}
     */
    public static function GetOldShift($data_post = NULL): array
    {
        $log = new LogAmicumFront("GetOldShift");

        $chosenShift = array(
            "id" => null,
            "title" => "Выберите смену",
        );

        $transfer_date = null;

        $result = array(
            "chosen_shift" => $chosenShift,
            "chosen_date" => null,
            "current_date_time" => Assistant::GetDateTimeNow(),
            "transfer_date_time" => $transfer_date
        );

        try {
            $log->addLog("Начало выполнения метода");

            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                property_exists($post_dec, "date_time") and $post_dec->date_time != ""
            ) {
                $calcDate = $post_dec->date_time;
                $transfer_date = $post_dec->date_time;
            } else {
                $calcDate = Assistant::GetDateTimeNow();
            }


            $currentHours = date("H", strtotime($calcDate));

            if (AssistantFront::GetCountShifts() == 3) {
                if ($currentHours >= 0 && $currentHours < 5) {
                    $chosenShift = array(
                        "id" => 2,
                        "title" => "Смена 2",
                        "title_short" => "II"
                    );
                    $calcDate = date("Y-m-d", strtotime($calcDate . " -1 day"));
                } else if ($currentHours >= 5 && $currentHours < 13) {
                    $chosenShift = array(
                        "id" => 3,
                        "title" => "Смена 3",
                        "title_short" => "III"
                    );
                    $calcDate = date("Y-m-d", strtotime($calcDate . " -1 day"));
                } else if ($currentHours >= 13 && $currentHours < 19) {
                    $chosenShift = array(
                        "id" => 1,
                        "title" => "Смена 1",
                        "title_short" => "I"
                    );
                    $calcDate = date("Y-m-d", strtotime($calcDate));
                } else if ($currentHours >= 19) {
                    $chosenShift = array(
                        "id" => 2,
                        "title" => "Смена 2",
                        "title_short" => "II"
                    );
                    $calcDate = date("Y-m-d", strtotime($calcDate));
                }
            } else {
                if ($currentHours >= 0 && $currentHours < 5) {
                    $chosenShift = array(
                        "id" => 3,
                        "title" => "Смена 3",
                        "title_short" => "III"
                    );
                    $calcDate = date("Y-m-d", strtotime($calcDate . " -1 day"));
                } else
                    if ($currentHours >= 5 && $currentHours < 11) {
                        $chosenShift = array(
                            "id" => 4,
                            "title" => "Смена 4",
                            "title_short" => "IV"
                        );
                        $calcDate = date("Y-m-d", strtotime($calcDate . " -1 day"));
                    } else if ($currentHours >= 11 && $currentHours < 17) {
                        $chosenShift = array(
                            "id" => 1,
                            "title" => "Смена 1",
                            "title_short" => "I"
                        );
                        $calcDate = date("Y-m-d", strtotime($calcDate));
                    } else if ($currentHours >= 17 && $currentHours < 23) {
                        $chosenShift = array(
                            "id" => 2,
                            "title" => "Смена 2",
                            "title_short" => "II"
                        );
                        $calcDate = date("Y-m-d", strtotime($calcDate));
                    } else
                        if ($currentHours >= 23) {
                            $chosenShift = array(
                                "id" => 3,
                                "title" => "Смена 3",
                                "title_short" => "III"
                            );
                            $calcDate = date("Y-m-d", strtotime($calcDate));
                        }
            }
            $result = array(
                "chosen_shift" => $chosenShift,
                "chosen_date" => $calcDate,
                "current_date_time" => Assistant::GetDateTimeNow(),
                "transfer_date_time" => $transfer_date
            );
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");


        return array_merge(["Items" => $result], $log->getLogAll());
    }

    /**
     * GetCountShift - Метод получения количества смен по умолчанию
     * @return array
     * @example http://127.0.0.1/read-manager-amicum?controller=service\Service&method=GetCountShift&subscribe=&data={}
     */
    public static function GetCountShift($data_post = NULL): array
    {
        $log = new LogAmicumFront("GetCountShift");
        $result['count_shift'] = null;

        try {
            $log->addLog("Начало выполнения метода");
            $result['count_shift'] = AssistantFront::GetCountShifts();
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(["Items" => $result], $log->getLogAll());
    }

    /**
     * GetDateTimeCurrent - Метод получения даты и времени сервера, через ReadManager
     * @example http://127.0.0.1/read-manager-amicum?controller=service\Service&method=GetDateTimeCurrent&subscribe=&data={}
     */
    public static function GetDateTimeCurrent($data_post = NULL): array
    {
        $log = new LogAmicumFront("GetDateTimeCurrent");
        $result = "";

        try {

            $result = Assistant::GetDateTimeNow();

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }
}