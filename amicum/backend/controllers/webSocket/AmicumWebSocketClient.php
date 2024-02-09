<?php
/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\webSocket;

use Exception;
use WebSocket\Client;

/**
 * Класс Веб Сокет клиента для отправки данных
 */
class AmicumWebSocketClient
{
    // send                     - Базовый метод отправки сообщения
    // sendDataClient           - метод отправки данных клиенту
    // buildMessage             - метод подготовки структуры для отправки сообщения
    // sendDataBroadcast        - метод широковещательной рассылки
    // sendDataClients          - метод отправки данных клиентам по подписке
    // sendDataClient           - метод отправки данных клиенту
    // getSourceClientId        - Метод получения ключа клиента, отправляющего запрос
    // disconnect               - отключить соединение
    // close                    - закрытие вебсокета на совсем

    protected $client;              // сам клиент
    protected $ws_url;              // адрес веб сокет сервера
    protected $sourceClientId;      // ключ клиента

    public function __construct()
    {
        $a = func_get_args();
        $i = func_num_args();

        switch ($i) {
            case 1:
                $this->ws_url = $a[0];
                break;
            default:
//                $this->ws_url = "ws://" . AMICUM_DASH_BOARD_WEBSOCKET_ADDRESS . ':' . AMICUM_DASH_BOARD_WEBSOCKET_PORT;
                $this->ws_url = "ws://" . "192.168.1.192" . ':' . "8282/ws";
        }
        $this->client = new Client($this->ws_url);
        $this->sourceClientId = "serverId";
    }

    /**
     * close - закрытие вебсокета на совсем
     * @return void
     */
    public function close()
    {
        $this->client->close();
    }

    /**
     * disconnect - отключить соединение
     * @return void
     */
    public function disconnect()
    {
        $this->client->disconnect();
    }

    /**
     * getSourceClientId - Метод получения ключа клиента, отправляющего запрос
     * @return string|null
     */
    public function getSourceClientId()
    {
        return $this->sourceClientId;
    }

    /**
     * sendDataClient - метод отправки данных клиенту
     * @param $payload - данные для отправки
     * @param $destinationClientId - ключ клиента, которому шлем
     * @return void
     */
    public function sendDataClient($payload, $destinationClientId)
    {
        $message = $this->buildMessage($payload, null, $destinationClientId, "send");
        $this->send($message);
    }

    /**
     * buildMessage - метод подготовки структуры для отправки сообщения
     * @param $clientType - тип клиента (сервер, фронт, юнити)
     * @param $payload - данные для отправки
     * @param $subscribes - массив подписок
     * @param $destinationClientId - ключ клиента, которому шлем
     * @param $actionType - дип действия (subscribe - подписаться, get - запросить данные, post - отправить данные на сервер, send - отослать данные другим участникам)
     * @return false|string
     */
    private function buildMessage($payload, $subscribes, $destinationClientId, $actionType = "", $clientType = "server")
    {
        return json_encode(
            array(
                'sourceClientId' => $this->sourceClientId,
                'destinationClientId' => $destinationClientId,
                'actionType' => $actionType,
                'subscribes' => $subscribes,
                'data' => $payload,
                'clientType' => $clientType,
                'requestId' => null,
            )
        );
    }

    /**
     * send - Базовый метод отправки сообщения
     * @param string $msg - текст сообщения
     * @throws \WebSocket\BadOpcodeException
     */
    private function send($msg)
    {
        if ($this->client) {
            $this->client->send($msg);
        } else {
            throw new Exception('AmicumWebSocketClient.send Не смог подключиться к: ' . $this->ws_url . '. Проверьте доступ к WebSocket');
        }
    }

    /**
     * sendDataClients - метод отправки данных клиентам по подписке
     * @param $payload - данные для отправки
     * @param $subscribes - массив подписок
     * @param $destinationClientId - ключ клиента, которому шлем
     * @return void
     */
    public function sendDataClients($payload, $subscribes, $destinationClientId)
    {
        $message = $this->buildMessage($payload, $subscribes, $destinationClientId, "send");
        $this->send($message);
    }

    /**
     * sendDataBroadcast - метод широковещательной рассылки
     * @param $payload - данные для отправки
     * @param $subscribes - массив подписок
     * @return void
     */
    public function sendDataBroadcast($payload, $subscribes = null)
    {
        $message = $this->buildMessage($payload, $subscribes, null, "broadcast");
        $this->send($message);
    }
}