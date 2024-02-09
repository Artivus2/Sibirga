<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers;

//ob_start();

use backend\controllers\cachemanagers\LogCacheController;
use backend\controllers\cachemanagers\ServiceCache;
use backend\models\StrataPackageSource;
use frontend\models\SettingsDCS;
use Throwable;
use Yii;
use yii\db\Query;
use yii\httpclient\Exception;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;

// actionSavePackToBaseSource                       - Функция записи пакета в отладочную таблицу в БД
// actionGetStrataSettings                          - Функция для получения настроек подключения Strata.
// actionGetEdges                                   - Функция получения информации по ветвям схемы.
// actionGiveMessages                               - Метод отдает из кеша 5 сообщений которые нужно отправить в службу
// actionWriteDbLog                                 - Записывает в таблицу strata_action_log сообщения при исключениях от службы

/**
 * Class StrataController
 * @package app\controllers
 * Контроллер службы сбора данных Strata. Реализует методы для обработки полученных
 * данных и записи в БД и кеш.
 */
class StrataController extends Controller
{
    /**
     * Вызывается перед запуском любого action метода
     * @param $action
     * @return bool
     * @throws BadRequestHttpException
     */
    public function beforeAction($action)
    {
        if ($action->id === 'get-edges') {
            $this->enableCsrfValidation = false;
        }

        if (!parent::beforeAction($action)) {
            return false;
        }

        return true;
    }

    /**
     * Функция записи пакета в отладочную таблицу в БД
     * Сохраняет данные в таблицу strata_package_source
     * Принимает необработанные данные, как они есть от шлюза
     */
    // пример: 127.0.0.1/admin/strata/save-pack-to-base-source?ip=172.16.52.5&date_time=2020-06-08 15:00:00&mineId=270&bytes=8fea040d3c00400500bbaa004007be200500bbaa004007be0005010100c007880104030201010023fd86c2
    // пример: 127.0.0.1/admin/strata/save-pack-to-base-source?ip=172.16.59.42&date_time=2020-06-08 15:00:00&mineId=290&bytes=8fea040d3c00400500bbaa004007be200500bbaa004007be0005010100c007880104030201010023fd86c2
    public function actionSavePackToBaseSource()
    {
        $errors = array();                                                                                                //массив ошибок
        $debug = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();
//        ini_set('max_execution_time', 60000);
//        ini_set('memory_limit', "20500M");
        //$warnings[] = 'actionSavePackToBaseSource. Начал выполнять метод';
        try {

            $post = Assistant::GetServerMethod();

            $date_time = $post['date_time'];
            $mine_id = $post['mineId'];
//            $date_time = Assistant::GetDateNow();
//            если нет разрешения на запись, то метод не выполняется

            $newInfo = new StrataPackageSource();
            $newInfo->bytes = $post['bytes'];
            $newInfo->ip = $post['ip'];
            $newInfo->mine_id = $mine_id;
            $newInfo->date_time = $date_time;
            if (!$newInfo->save()) {
                $errors[] = $newInfo->errors;
                throw new Exception('Ошибка при сохранении пакета в БД');
            }

            if (!(new ServiceCache())->CheckDcsStatus($mine_id, 'strataStatus')) {
                throw new Exception("Нет разрешения на запись");
            }

//            $response = self::TranslatePackage($post['bytes'], $date_time, $post['ip'], $mine_id);
            $json_pack = json_encode([
                'ip' => $post['ip'],
                'date_time' => $post['date_time'],
                'mine_id' => $post['mineId'],
                'bytes' => $post['bytes'],
            ]);
            $response = StrataQueueController::PushToQuery($post['ip'], $json_pack);

            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $debug[] = $response['debug'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                //throw new \Exception("actionSavePackToBaseSource. Ошибка в расчете контролльной суммы");
            }

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'actionSavePackToBaseSource. Исключение: ';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
            $data_to_log_cache = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'pack' => $post);
            LogCacheController::setStrataLogValue('actionSavePackToBaseSource', $data_to_log_cache);
        }
        return $this->asJson(array('Items' => 'actionSavePackToBaseSource', 'errors' => $errors, 'status' => $status, 'warnings' => $warnings));
    }

    /**
     * Функция для получения настроек подключения Strata.
     * Метод возвращает все узлы связи для опроса
     *
     * @example
     * http://127.0.0.1/admin/strata/get-strata-settings?setting=ССД Strata_Заполярная
     */
    public function actionGetStrataSettings()
    {
        $connects_array = [];
        setlocale(LC_ALL, array('ru_RU.CP1251', 'ru_SU.CP1251', 'ru_RU', 'russian', 'ru_SU', 'ru'));           //установка настроек локали
        $post = Assistant::GetServerMethod();
        $setting_title = $post['setting'];
        if (isset($setting_title) && $setting_title != '') {
            $setting = SettingsDCS::findOne(['title' => $setting_title]);                                               //получаем название конфигурации службы сбора данных (ССД)
            if ($setting) {                                                                                             //если конфигурация не найдена
                $connects_array = array();                                                                              //временный массив для хранения данных о подключениях
                $i = 0;                                                                                                 //порядковый номер (индекс) подключения
                if ($connects = $setting->getConnectStrings()
                    ->where([
                        'source_type' => 'Strata'])
                    ->all())  //если есть подключение
                    foreach ($connects as $connect) {                                                                   //перебираем все подключения
                        $connects_array[$i] = array();
                        $connects_array[$i]['ip'] = $connect->ip;                                                       //сохраняем значения ip-адреса
                        $connects_array[$i]['connectStr'] = $connect->connect_string;                                   //и строки подключения
                        if ($setting = $connect->settingsDCS) {                                                         //если есть значение настроек ССД
                            //$connects_array[$i]['setting'] = $setting->title;                                           //сохраняем её название
                            $connects_array[$i]['setting'] = 'Not_used';
                        } else {
                            $connects_array[$i]['setting'] = '';
                        }
                        $i++;
                    }
            }
        }
        return $this->asJson($connects_array);                                                                          //выводим json-представление данных о подключениях
    }

    /**
     * Функция получения информации по ветвям схемы.
     * Возвращает в формате json информацию о ветви,
     * координатах начала и конца (и соответствующих сопряжений),
     * всех узлах связи в ветви
     */
    public function actionGetEdges()
    {
        $post = Assistant::GetServerMethod();                                                                             //получить id шахты методом POST
        if (isset($post['id']) && (string)$post['id'] !== '') {                                                                   //Если id шахты задан
            $sql_filter = 'mine_id=' . $post['id'];                                                                     //Добавить его в фильтр
            $edges = (new Query())//запросить данные из представления view_edge_conjunction_nodes
            ->select(
                [
                    'edge_id',
                    'start',
                    'end',
                    'nodes',
                    'length',
                    'type'
                ]
            )->from('view_graph_nodes_edges_main')
                ->where($sql_filter)
                ->all();
            return $this->asJson($edges);                                                                                       //Вернуть данные по ветвям
        } else {
            exit ("Set mine id in config file!\n");
        }
    }

    /**
     * Название метода: actionGiveMessages()
     * Метод отдает из кеша 5 сообщений которые нужно отправить в службу
     * @package backend\controllers
     *
     * @see
     * @example
     *
     * @author fidchenkoM
     * Created date: on 21.05.2019 17:16
     * @since ver
     */
    public static function actionGiveMessages()
    {
        try {
            $cache = Yii::$app->redis_service;
            $pack = $cache->lrange('packages', 0, 5);                                                                     //получаем 5 сообщений из кеша
            for ($i = 0; $i < count($pack); $i++)                                                                              //перебираем их
            {
                $cache->lset('packages', 0, 'del');                                                                       //устанавливаем текущему элементу что он будет удален
                $cache->lrem('packages', 0, 'del');                                                                       //удаляем
            }
            Yii::$app->response->format = Response::FORMAT_JSON;                                                        //отдаем рузультат в json
            Yii::$app->response->data = $pack;
        } catch (Throwable $ex)                                                                                          //если есть ошибка то отдаем ее
        {
            Yii::$app->response->format = Response::FORMAT_JSON;
            Yii::$app->response->data = $ex->getMessage();
        }
    }

    /**
     * Записывает в таблицу strata_action_log сообщения при исключениях от службы
     * Принимаемые POST параметры:
     *  method  - где возникло исключение
     *  message - сообщение исключения
     * @return Response
     */
    public function actionWriteDbLog()
    {
        $post = Assistant::GetServerMethod();

        $status = 1;                                                                                                      // статус выполнения метода приравниваем к 1, по мере выполнения может обнулятся, в случае если подметоды возвращают 0, притом сам метод может выполняться полностью, но с логическими ошибками
        $warnings = array();                                                                                              // массив предупреждений
        $errors = array();                                                                                                // массив ошибок

        try {
            $method_name = $post['method'];
            $err_msg = $post['message'];

            $my_encoding_list = [
                'UCS-2',
                'UCS-4',
                "UTF-8",
                "UTF-7",
                "UTF-16",
                "UTF-32",
                "ISO-8859-16",
                "ISO-8859-15",
                "ISO-8859-10",
                "ISO-8859-1",
                "Windows-1254",
                "Windows-1252",
                "Windows-1251",
                "ASCII",
                //add yours preferred
            ];

            //remove unsupported encodings
            $encoding_list = array_intersect($my_encoding_list, mb_list_encodings());

            //detect 'finally' the encoding
            $encoding = mb_detect_encoding($err_msg, $encoding_list, true);

            $err_msg = mb_convert_encoding($err_msg, 'UTF-8', $encoding);
            $response = LogAmicum::LogEventStrata( $err_msg, Assistant::GetDateNow(), 0, '', '', $method_name);
            if ($response['status'] != 1) {
                $errors[] = $response['errors'];
                throw new \Exception('Ошибка при сохранении лога');
            }
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = $exception->getMessage();
        }

        return $this->asJson(array('Items' => 'actionWriteDbLog', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings));
    }


}
