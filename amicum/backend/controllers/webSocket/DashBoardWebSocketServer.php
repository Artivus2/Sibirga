<?php
/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\webSocket;

use backend\controllers\Assistant;
use Exception;
use Ratchet\ConnectionInterface;
use SplObjectStorage;


/**
 * Веб сокет сервер для интерактивного рабочего места
 */
class DashBoardWebSocketServer extends AmicumBaseWebSocket
{
    protected $clients;         // массив клиентов, подключенных к веб сокету
    protected $subscribers = [];     // список клиентов и их подписок
    protected $subscribes = [];      // список подписок и клиентов в них
    protected $clientsHand = [];     // словарь клиентов

    public function __construct()
    {
        $this->clients = new SplObjectStorage();
        echo "DashBoardWebSocketServer Закончил инициализацию сервера\n";
    }

    public function onOpen(ConnectionInterface $conn)
    {
        parent::onOpen($conn);
        $this->clients->attach($conn);
        $this->clientsHand[$conn->resourceId] = $conn;
//        echo "DashBoardWebSocketServer Новое соединение: ({$conn->resourceId})\n";
    }

    /**
     * Структура пакета
     * sourceClientId       - ключ отправителя сообщения
     * destinationClientId  - ключ получателя сообщения,
     * subscribes           - массив подписок, на которые надо слать сообщение
     * data                 - полезные данные
     * clientType           - тип клиента (server, unity, front)
     * requestId            - ключ запроса
     * actionType:          - тип действия по сообщению
     *      subscribe       - подписаться
     *      get             - запросить данные
     *      post            - отправить данные на сервер
     *      send            - отослать данные другим участникам
     *      key             - отослать источнику запроса его ключ соединения
     *      broadcast       - широковещательная рассылка
     *      stateWebSocket  - состояние веб сокет сервера
     * @param ConnectionInterface $from
     * @param $msg
     * @return void
     * @throws Exception
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        parent::onMessage($from, $msg);
        $numRecv = count($this->clients) - 1;
        echo sprintf('DashBoardWebSocketServer Соединение %d отправило сообщение "%s" на %d других соединения' . "\n", $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');
        $message = json_decode($msg);


        switch ($message->actionType) {
            case "key":                                                                                                 // получение ключа клиента
                $message->data = $from->resourceId;
                $from->send(json_encode($message));
                break;
            case "subscribe":                                                                                           // добавление подписок клиента
                $date_time = Assistant::GetDateNow();
                foreach ($message->subscribes as $subscribe) {
                    /**
                     * subscribers:                 - подписчики
                     *      {resourceId}:           -    номер клиента
                     *          "$subscribe":       -    название подписки
                     *                client        -    вебсокет клиент
                     *                lastDateTime  -    время добавления клиента
                     */
                    $this->subscribers[$from->resourceId][$subscribe] = array(
                        "client" => $from,
                        "lastDateTime" => $date_time
                    );

                    /**
                     * subscribes:                  - подписки
                     *      "$subscribe":           -    название подписки
                     *          {resourceId}:       -    номер клиента
                     *                client        -    вебсокет клиент
                     *                lastDateTime  -    время добавления клиента
                     */
                    $this->subscribes[$subscribe][$from->resourceId] = array(
                        "client" => $from,
                        "lastDateTime" => $date_time
                    );
                }
                break;
            case "send":
                $destinationsClient = [];
                if ($message->destinationClientId) {                                                                    // Рассылка конкретному клиенту
                    if (isset($this->clientsHand[$message->destinationClientId])) {
                        $destinationsClient[$message->destinationClientId] = $this->clientsHand[$message->destinationClientId];
//                        echo "Рассылка клиенту\n";
                    } else {
                        echo "Такого клиента уже нет\n";
                    }


                } else if ($message->subscribes and !empty($message->subscribes)) {
                    foreach ($message->subscribes as $subscribe) {
                        if (isset($this->subscribes[$subscribe])) {
                            foreach ($this->subscribes[$subscribe] as $key => $client) {
                                $destinationsClient[$key] = $client['client'];
                            }
                        }
                    }
//                    echo "Рассылка по подпискам\n";

                } else {
                    echo "Нет оснований для рассылки - нет ни конкретного клиента, ни подписок\n";
                }

                foreach ($destinationsClient as $client) {
                    if ($from != $client) {
                        $client->send($msg);
                    }
                }

                break;
            case "broadcast":                                                                                           // широковещательная рассылка всем без исключения
                foreach ($this->clients as $client) {
                    $client->send($msg);
                }
                break;
            case "stateWebSocket":                                                                                      // статистика вебсокета - его состояние
                $count_subscribe = [];
                foreach ($this->subscribes as $key_subscribe => $subscribe) {
                    $count_subscribe[$key_subscribe] = count($subscribe);
                }

                $count_client = [];
                foreach ($this->subscribers as $key_client => $client_s) {
                    foreach ($client_s as $key_subscribe => $subscribe) {
                        $count_client[$key_client][] = $key_subscribe;
                    }
                }
                $msg = json_encode(array(
                    'count_client' => count($this->clients),                                                            // массив клиентов, подключенных к веб сокету
                    'count_subscribers' => count($this->subscribers),                                                   // список клиентов и их подписок
                    'count_subscribes' => count($this->subscribes),                                                     // список подписок и клиентов в них
                    'statistic_subscribe' => $count_subscribe,                                                          // детализация подписок
                    'statistic_client' => $count_client,                                                                // детализация клиентов
                    'count_clientsHand' => count($this->clientsHand)                                                    // словарь клиентов
                ));
                $from->send($msg);

                break;
            default:
                break;
        }


    }

    public function onClose(ConnectionInterface $conn)
    {
        parent::onClose($conn);
        unset($this->clientsHand[$conn->resourceId]);

        /**
         * subscribers:                 - подписки
         *      {resourceId}:           -    номер клиента
         *          "$subscribe":       -    название подписки
         *                client        -    вебсокет клиент
         *                lastDateTime  -    время добавления клиента
         */

        /**
         * subscribes:                  - подписки
         *      "$subscribe":           -    название подписки
         *          {resourceId}:       -    номер клиента
         *                client        -    вебсокет клиент
         *                lastDateTime  -    время добавления клиента
         */

        if (isset($this->subscribers[$conn->resourceId])) {
            foreach ($this->subscribers[$conn->resourceId] as $subscribes) {
                foreach ($subscribes as $key_subscribe => $subscribe) {
                    if (isset($this->subscribes[$key_subscribe])) {
                        unset($this->subscribes[$key_subscribe][$conn->resourceId]);
                    }
                }
            }
        }

        unset($this->subscribers[$conn->resourceId]);

        $this->clients->detach($conn);
//        echo "DashBoardWebSocketServer Соединение {$conn->resourceId} закрыто\n";
    }

    public function onError(ConnectionInterface $conn, Exception $e)
    {
        parent::onError($conn, $e);
        echo "DashBoardWebSocketServer Ошибка: {$e->getMessage()}\n";
        echo "DashBoardWebSocketServer Строка: {$e->getLine()}\n";
        $conn->close();
    }

}