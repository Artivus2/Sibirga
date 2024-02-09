<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\horizon;

use backend\controllers\Assistant;
use backend\controllers\cachemanagers\LogCacheController;
use Exception;
use frontend\controllers\system\LogAmicumFront;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\Factory;
use RuntimeException;
use Throwable;
use WebSocket\Client;
use WebSocket\ConnectionException;
use function property_exists;
use function strtolower;

/**
 * Класс работы с системой Горизонт фирмы Талнах
 *
 * !!! Данный класс оставлен в качестве пробного и не используется в работе системы
 */
class Horizon extends \inpassor\daemon\Worker
{

    // метод подключения к вебсокету
    // метод получения данных с вебсокета
    // метод записи данных в очередь по id считывателя
    // метод инициализации вебсокет клиента
    // метод проверки статуса вебсокет клиента

    // init()                   - Метод инициализации веб-сокет клиента
    // getClient()              - Метод получения экземпляра веб-сокет клиента
    // setClient()              - Метод установки веб-сокет клиента
    // closeClient              - Метод закрытия соединения с веб-сокетом
    // getIsСonnect()           - метод получения текущего состояния клиента

    public $active = true;                                                                                              // разрешение на запуск службы
    public $maxProcesses = 1;                                                                                           // максимальное количество процессов службы
    public $delay = 60;                                                                                                 // задержка при перезапуске службы
    private $client;                                                                                                    // веб-сокет клиент
    private $stop_receive = false;                                                                                      // флаг остановки опроса веб-сервера клиентом
    private $is_connect = null;                                                                                         // подключен или нет веб-сокет клиент


    /** ПОПЫТКА РАЗРАБОТАТЬ ПОДПИСКУ НА СОБЫТИЯ С СИГНАЛ Р - проблема нет вебсокетов у Горизонта */

    private $base_url;                                                                                                  // базовый url: ws://horizon/hardware
    private $hubs;                                                                                                      //
    private $loop;                                                                                                      // фабрика соединений
    private $connectionToken;                                                                                           // токен соединения
    private $connectionId;                                                                                              // id соединения
    private $transport;                                                                                                 // транспорт LongPolling, ServerSentEvents
    private $callbacks;                                                                                                 // обратный колбек
    private $channels;                                                                                                  //
    private $messageId = 1000;                                                                                          //

    /**
     * Метод класса
     * @param $base_url - базовый url
     * @param $hubs
     */
    public function initClientR($base_url, $hubs)
    {
        $this->base_url = $base_url;
        $this->hubs = $hubs;
        $this->callbacks = [];
    }

    /**
     * Инициализация соединения к серверу горизонта
     * Метод запуска демона
     * /var/www/html/amicum/vendor/inpassor/yii2-daemon/yiid start
     * /var/www/html/amicum/vendor/inpassor/yii2-daemon/yiid stop
     * @return array|void
     */
    public function run()
    {
        $log = new LogAmicumFront("Horizon run");
        try {
            $response = $this->negotiate();                                                                             // рукопожатие к серверу горизонт получение первичных настроек сервера
            $log->addLogAll($response);

            if (!$response['Items']) {
                throw new RuntimeException("Не смог сделать рукопожатие");
            }

            $response = $this->connect();
            $log->addLogAll($response);

            $response = $this->start();
            $log->addLogAll($response);

            if (!$response['Items']) {
                throw new RuntimeException("Cannot start");
            }

            //$this->loop->run();
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        return array_merge(["Items" => []], $log->getLogAll());
    }

    /**
     * создание url для рукопожатия
     * @return string
     */
    private function buildNegotiateUrl()
    {
        $base = str_replace("ws://", "http://", $this->base_url);
        return $base . "/hardware/negotiate";
    }

    /**
     * Метод выполнения рукопожатия и получения первичных данных для установки последующих соединений
     * @return array|bool[]
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function negotiate()
    {
        $status = false;
        $log = new LogAmicumFront("negotiate");
        try {
            $url = $this->buildNegotiateUrl();

            $log->addData($url, '$url', __LINE__);
            $client = new \GuzzleHttp\Client();
            $res = $client->request('POST', $url);
            $log->addData($res->getBody(), '$res->getBody', __LINE__);

            $body = json_decode($res->getBody());
            $log->addData($body, '$body', __LINE__);

            //$this->connectionToken = $body->ConnectionToken;
            $this->connectionId = $body->connectionId;
            $this->transport = $body->availableTransports[0]->transport;

            $log->addData($this->connectionToken, 'connectionToken', __LINE__);
            $log->addData($this->connectionId, 'connectionId', __LINE__);
            $log->addData($this->transport, 'transport', __LINE__);

            $status = true;
        } catch (Exception $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(["Items" => $status], $log->getLogAll());
    }

    private function buildConnectUrl()
    {
        $hubs = [];
        foreach ($this->hubs as $hubName) {
            $hubs[] = (object)["name" => $hubName];
        }

        $query = [
            "id" => $this->connectionId,
            "connectionData" => json_encode($hubs)
//            "transport" => $this->transport,
//            "clientProtocol" => 1.5,
        ];

        return $this->base_url . "/hardware?" . http_build_query($query);
    }

    private function connect()
    {
        $log = new LogAmicumFront("connect");

        $this->loop = Factory::create();
        $connector = new Connector($this->loop);
        $url = $this->buildConnectUrl();
        $log->addData($url, '$url', __LINE__);
        $connector($url)
            ->then(
                function (WebSocket $conn) {
//                    $this->subscribe($conn);
                    LogCacheController::setLogValue("message", [
                        "message" => "fff"
                    ]);
                    $conn->on('message',
                        function (MessageInterface $msg) use ($conn) {
                            LogCacheController::setLogValue("message", [
                                "message" => "fff"
                            ]);
                            $data = json_decode($msg);
                            if (property_exists($data, "M")) {
                                foreach ($data->M as $message) {
                                    $hub = $message->H;
                                    $method = $message->M;
                                    $callback = strtolower($hub . "." . $method);
                                    if (array_key_exists($callback, $this->callbacks)) {
                                        foreach ($message->A as $payload) {
                                            $this->callbacks[$callback]($payload);
                                        }
                                    }
                                }
                            }
                        });
                },
                function (Exception $ex) {
                    LogCacheController::setLogValue("connect", [
                        "error" => $ex->getMessage()
                    ]);
                    Assistant::PrintR($ex->getLine());
                    Assistant::PrintR($ex->getMessage());
                    $this->loop->stop();
                }
            );

        return array_merge(["Items" => []], $log->getLogAll());
    }

    public function on($hub, $method, $function)
    {
        $this->callbacks[strtolower($hub . "." . $method)] = $function;
    }

    private function subscribe(WebSocket $conn)
    {
        foreach ($this->hubs as $hub) {
            foreach ($this->channels as $channel) {
                $subscribeMsg = json_encode([
                    'H' => 'CoreHub',
                    'M' => 'SubscribeToSummaryDeltas',
                    'A' => [],
                    'I' => $this->messageId
                ]);

                $conn->send($subscribeMsg);
            }
        }
    }

    private function buildStartUrl()
    {
        $base = str_replace("ws://", "http://", $this->base_url);

        $hubs = [];
        foreach ($this->hubs as $hubName) {
            $hubs[] = (object)["name" => $hubName];
        }

        $query = [
            "id" => $this->connectionId,
//            "transport" => "webSockets",
//            "clientProtocol" => 1.5,
            "connectionData" => json_encode($hubs)
        ];

        return $base . "/start?" . http_build_query($query);
    }

    private function start()
    {
        $state = false;
        $log = new LogAmicumFront("start");
        try {
            $url = $this->buildStartUrl();
            $log->addData($url, '$url', __LINE__);
            $client = new \GuzzleHttp\Client();
            $res = $client->request('POST', $url);
//            Assistant::PrintR($res);
            $log->addData($res, '$res', __LINE__);
            $log->addData($res->getBody(), '$res->getBody', __LINE__);

            $body = json_decode($res->getBody());

            $log->addData($res->getBody(), '$res->getBody', __LINE__);
            $log->addData($body, '$body', __LINE__);

            $state = true;
        } catch (Exception $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        return array_merge(["Items" => $state], $log->getLogAll());
    }


    public function setChannels($channels)
    {
        $this->channels = $channels;
    }






    /** ПОПЫТКА ПРЯМОГО ВЕБСОКЕТА */

    /**
     * Метод запуска демона
     * /var/www/html/amicum/vendor/inpassor/yii2-daemon/yiid start
     * /var/www/html/amicum/vendor/inpassor/yii2-daemon/yiid stop
     */
//    public function run()
//    {
//        $this->initClient();
//        $this->receiveMessage();
//    }

    /**
     * init - Метод инициализации веб-сокет клиента
     */
    public function initClient()
    {
        $address = 'ws://' . HORIZON_ADDRESS . '/hardware?id=AMICUM';
        $this->client = new Client($address);
        LogCacheController::setLogValue("initClient", [
            "Инициализация запуска службы произведена" => Assistant::GetDateTimeNow(),
            "HORIZON_ADDRESS" => $address
        ]);
        LogCacheController::setLogValue("initClient", [
            "message" => $this->client->receive()
        ]);
    }

    /**
     * Метод получения данных с веб-сокета горизонта
     */
    public function receiveMessage()
    {
//        ini_set('max_execution_time', -1);

        $this->is_connect = false;
        $log = new LogAmicumFront("receiveMessage");
        $date_time_last = Assistant::GetDateTimeNow();
        LogCacheController::setLogValue("receiveMessage", ["Начал слушать Сервер Горизонт" => Assistant::GetDateTimeNow()]);

        while (!$this->stop_receive) {
            try {
                $message_json = $this->client->receive();
                $this->is_connect = true;
                $message = json_decode($message_json);

                $log->addData($message, '$message', __LINE__);

                $response = StrataQueueController::PushToQuery("", $message);

                if ($response['status'] != 1) {
                    $log->addLogAll($response);
                    throw new Exception("Ошибка в укладывании данных в очередь");
                }

                $date_time_now = Assistant::GetDateTimeNow();
                if (Assistant::GetMysqlTimeDifference($date_time_last, $date_time_now) > 60) {
                    $date_time_last = $date_time_now;
                    // TODO сделать запрос к редису для проверки разрешения на работу службы
                }

            } catch (ConnectionException $ex) {
                $this->stop_receive = true;
                LogCacheController::setLogValue("receiveMessage", "Ошибка подключения к веб-сокету сервера Горизонт");
                sleep(10);
            } catch (Exception $ex) {
                $this->stop_receive = true;
                LogCacheController::setLogValue("receiveMessage", $ex->getMessage());
            }
        }
        return array_merge(["Items" => []], $log->getLogAll());
    }

    /**
     * getIsСonnect - метод получения текущего состояния клиента
     * @return null
     */
    public function getIsConnect()
    {
        return $this->is_connect;
    }

    /**
     * getClient - Метод получения экземпляра веб-сокет клиента
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * setClient - Метод установки веб-сокет клиента
     * @param mixed $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * closeClient - Метод закрытия соединения с веб-сокетом
     */
    public function closeClient()
    {
        $this->client->close();
    }
}