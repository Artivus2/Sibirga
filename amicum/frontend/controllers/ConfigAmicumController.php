<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers;

use Exception;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\ConfigAmicum;
use Throwable;
use Yii;
use yii\web\Controller;

class ConfigAmicumController extends Controller
{
    // SaveParametersConfigAmicum         - метод сохранения параметров конфигурации Amicum
    // GetParametersConfigAmicum          - метод получения значений параметров конфигурации Amicum
    // OffOnParametersConfigAmicum        - метод изменения состояния параметров конфигурации Amicum
    // SaveUserThemeAmicum                - метод сохранения темы пользователя

    /**
     * SaveParametersConfigAmicum - метод сохранения параметров конфигурации Amicum
     * @param $data_post - {"parameters":{"parameter":"value",...}}
     * @return array - сохранённые параметры в виде объекта ConfigAmicum
     * @example 127.0.0.1/read-manager-amicum?controller=ConfigAmicum&method=SaveParametersConfigAmicum&subscribe=&data={"parameters":{"parameter":"value","parameter2":"value2"}}
     */
    public static function SaveParametersConfigAmicum($data_post = NULL)
    {
        $log = new LogAmicumFront("SaveParametersConfigAmicum");

        $result = null;

        try {
            $log->addLog("Начало выполнения метода");

            if (is_null($data_post) or $data_post == "") {
                throw new Exception("Входной массив данных post не передан");
            }

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];

            if (
                !property_exists($post, 'parameters') || $post->parameters == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $parameters = $post->parameters;

            $configs = ConfigAmicum::find()
                ->where(['parameter' => array_keys((array)$parameters)])
                ->indexBy('parameter')
                ->all();

            foreach ($parameters as $parameter => $value) {
                if (!isset($configs[$parameter])) {
                    $configs[$parameter] = new ConfigAmicum();
                    $configs[$parameter]->parameter = $parameter;
                    $configs[$parameter]->on_off = 1;
                }
                $configs[$parameter]->value = $value;
                if (!$configs[$parameter]->save()) {
                    $log->addData($configs[$parameter]->errors, '$configs[$parameter]->errors', __LINE__);
                    throw new Exception("Ошибка сохранения конфигурации. Модели ConfigAmicum");
                }
            }

            $result = $configs;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetParametersConfigAmicum - метод получения значений параметров конфигурации Amicum
     * @param $data_post - {"parameters":["parameter",...]}
     * @return array - параметры в виде объекта ConfigAmicum
     * @example 127.0.0.1/read-manager-amicum?controller=ConfigAmicum&method=GetParametersConfigAmicum&subscribe=&data={"parameters":["parameter","parameter2"]}
     */
    public static function GetParametersConfigAmicum($data_post = NULL)
    {
        $log = new LogAmicumFront("GetParametersConfigAmicum");

        $result = null;

        try {
            $log->addLog("Начало выполнения метода");

            if (is_null($data_post) or $data_post == "") {
                throw new Exception("Входной массив данных post не передан");
            }

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];

            if (
                !property_exists($post, 'parameters') || $post->parameters == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $parameters = $post->parameters;

            $configs = ConfigAmicum::find()
                ->where(['parameter' => $parameters])
                ->indexBy('parameter')
                ->all();

            $result = $configs;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * OffOnParametersConfigAmicum - метод изменения состояния параметров конфигурации Amicum
     * @param $data_post - {"parameters":{"parameter":"on_off",...}}
     * @return array - сохранённые параметры в виде объекта ConfigAmicum
     * @example 127.0.0.1/read-manager-amicum?controller=ConfigAmicum&method=OffOnParametersConfigAmicum&subscribe=&data={"parameters":{"parameter":"0","parameter2":"0"}}
     */
    public static function OffOnParametersConfigAmicum($data_post = NULL)
    {
        $log = new LogAmicumFront("OffOnParametersConfigAmicum");

        $result = null;

        try {
            $log->addLog("Начало выполнения метода");

            if (is_null($data_post) or $data_post == "") {
                throw new Exception("Входной массив данных post не передан");
            }

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];

            if (
                !property_exists($post, 'parameters') || $post->parameters == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $parameters = $post->parameters;

            $configs = ConfigAmicum::find()
                ->where(['parameter' => array_keys((array)$parameters)])
                ->indexBy('parameter')
                ->all();

            foreach ($parameters as $parameter => $on_off) {
                if (isset($configs[$parameter])) {
                    $configs[$parameter]->on_off = $on_off;
                    if (!$configs[$parameter]->save()) {
                        $log->addData($configs[$parameter]->errors, '$configs[$parameter]->errors', __LINE__);
                        throw new Exception("Ошибка сохранения конфигурации. Модели ConfigAmicum");
                    }
                } else {
                    $log->addLog("Параметр $parameter не найден");
                }
            }

            $result = $configs;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * SaveUserThemeAmicum - метод сохранения темы пользователя
     * @param $data_post - {"userTheme":"light"}
     * @return array - сохранённые параметры в виде объекта ConfigAmicum
     * @example 127.0.0.1/read-manager-amicum?controller=ConfigAmicum&method=SaveUserThemeAmicum&subscribe=&data={"userTheme":"light"}
     */
    public static function SaveUserThemeAmicum($data_post = NULL)
    {
        $log = new LogAmicumFront("SaveUserThemeAmicum");

        $result = null;

        try {
            $log->addLog("Начало выполнения метода");

            if (is_null($data_post) or $data_post == "") {
                throw new Exception("Входной массив данных post не передан");
            }

            $response = Assistant::jsonDecodeAmicum($data_post);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new Exception("Ошибка десериализации входных данных");
            }
            $post = $response['Items'];

            if (
                !property_exists($post, 'userTheme') || $post->userTheme == ''
            ) {
                throw new Exception("Входные параметры не переданы");
            }

            $log->addLog("Получил все входные параметры");

            $userTheme = $post->userTheme;
            $session = Yii::$app->session;

            $response = self::SaveParametersConfigAmicum(json_encode(['parameters' => ['session_amicum_userTheme_'.$session['user_id'] => $userTheme]]));
            $log->addLogAll($response);
            $result = $response['Items'];

            $session['userTheme'] = $userTheme;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }
}
