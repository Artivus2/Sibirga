<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace backend\models;


/**
 * Description of ErrorAmicum
 *
 * @author Ingener401
 */
class ErrorAmicum
{
    /*
     * Входные параметры:
     * - $script - (string) название контроллера/скрипта, вызвавшего обработчик
     * - $action - (string) название фукнции, вызвавшей обработчик
     * - $exception - (Exception) вызванное исключение
     */
    public static function errorHandlerAmicum($exception, $script = null, $action = null)
    {
        header("Content-Type: text/html;charset=UTF-8");
        $file = fopen("log/errors_back.log.txt", 'a');
        $line = $exception->getLine();

        fwrite($file, date('Y-m-d H:i:s') . " : " . $exception->getPrevious() . "---" . $exception->getFile() . "---" . $exception->getCode() . " - " . $exception->getMessage() . " Controller '" . json_encode($script) . "', Line '$line'\r\n");
        fclose($file);
    }
}
