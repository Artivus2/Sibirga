<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers;

use Exception;
use frontend\models\Main;
use Throwable;

class MainBasicController
{
    // addMain - метод создания главного айди в БД
    public static function addMain($table_address, $db_address = 'amicum2')
    {
        $result = array();                                                                                              // промежуточный результирующий массив
        $errors = array();                                                                                              // массив ошибок
        $warnings = array();                                                                                            // массив предупреждений
        $status = 1;
        $main_id = false;
        $warnings[] = 'addMain. Зашел в метод';
        try {
            $main = new Main();
            $main->table_address = $table_address;
            $main->db_address = $db_address;
            if (!$main->save()) {
                $errors[] = $main->errors;
                throw new Exception('addMain. Ошибка создания главного ключа');
            }

            $main_id = $main->id;
            $warnings[] = "addMain. Главный ключ сохранен и равен $main_id";
        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'addMain. Исключение';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        unset($main);
        $warnings[] = 'addMain. Вышел с метода';

        return array('Items' => $result, 'main_id' => $main_id, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

}
