<?php
/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\controllers\dashboard;

use backend\controllers\Assistant;
use backend\controllers\webSocket\AmicumWebSocketClient;
use Exception;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\DashboardConfig;
use Throwable;
use Yii;
use yii\web\Controller;

class ConfigController extends Controller
{
    // GetConfigDashBoard       - метод получения конфигурации конкретного интерактивного места
    // SaveConfigDashBoard      - метод сохранения конфигурации конкретного интерактивного места

    /**
     * GetConfigDashBoard - метод получения конфигурации конкретного интерактивного места
     * Входной объект:
     *      sourceClientId - ключ Web Socket клиента
     *      requestId - ключ запроса
     * @param $data_post
     * @return null[]
     * @example 127.0.0.1/read-manager-amicum?controller=dashboard\config&method=GetConfigDashBoard&subscribe=&data={"sourceClientId":1,"requestId":1}
     */
    public static function GetConfigDashBoard($data_post = null)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("GetConfigDashBoard", true);
        try {

            $log->addLog("Начал выполнять метод");

            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'sourceClientId') ||
                !property_exists($post_dec, 'requestId')) {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $session = Yii::$app->session;
            $session->open();
            $user_id = $session['user_id'];

            $config = DashboardConfig::find()
                ->where(['user_id' => $user_id])
                ->asArray()
                ->one();
            if (!$config) {
                $result = (object)array();
            } else {
                $result['sourceClientId'] = $post_dec->sourceClientId;
                $result['requestId'] = $post_dec->requestId;
                $result['user_id'] = $user_id;
                $result['date_time'] = $config['date_time'];
                $result['config_json'] = json_decode($config['config_json']);
            }
            $log->addData($result, '$result', __LINE__);

            (new AmicumWebSocketClient)->sendDataClient($result, $post_dec->sourceClientId);


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Закончил выполнять метод");
        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * SaveConfigDashBoard - метод сохранения конфигурации конкретного интерактивного места
     * Входной объект:
     *      sourceClientId - ключ Web Socket клиента
     *      requestId - ключ запроса
     *      config_json - конфигурация json
     * @param $data_post
     * @return null[]
     * @example 127.0.0.1/read-manager-amicum?controller=dashboard\config&method=SaveConfigDashBoard&subscribe=&data={"sourceClientId":1,"requestId":1,"config_json":"привет"}
     */
    public static function SaveConfigDashBoard($data_post = null)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("SaveConfigDashBoard", true);
        try {
            $log->addLog("Начал выполнять метод");

            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'sourceClientId') ||
                !property_exists($post_dec, 'requestId') ||
                !property_exists($post_dec, 'config_json')) {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $session = Yii::$app->session;
            $session->open();

            $user_id = $session['user_id'];
            $config_json = json_encode($post_dec->config_json);

            if ($user_id == null or $user_id == "") {
                throw new Exception('Ключ пользователя не задан');
            }

            $config = DashboardConfig::findOne(['user_id' => $user_id]);
            if (!$config) {
                $config = new DashboardConfig();
            }

            $config->user_id = $user_id;
            $config->date_time = Assistant::GetDateNow();
            $config->config_json = $config_json;

            if (!$config->save()) {
                $log->addData($config->errors, '$config->errors', __LINE__);
                throw new Exception('Не удалось сохранить конфигурацию интерактивного стола в модель DashboardConfig');
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Закончил выполнять метод");

        return array_merge(['Items' => $result], $log->getLogAll());
    }
}
