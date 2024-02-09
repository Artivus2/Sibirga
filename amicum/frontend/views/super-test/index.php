<?php
/* @var $this yii\web\View */
?>
<h1>super-test/index</h1>

<h2>Hello World</h2>

<script>
    console.log("Start test WebSocket")
    let web = new WebSocket('ws://127.0.0.1:8283'); 		                                                            // установка соединения
    web.onopen = function (e) {
        getResourceId();
        subscribe(["PersonnelShifts", "DownTime", "MineAlarm"]);
        getDataFromServer("GetPersonnelShifts", {"mine_id": 12}, "shift123")
        sendMessageToChanel({"mine_id": 290}, "MineAlarm")
        sendMessageToChanels({"mine_id": 12}, ["MineAlarm", "DownTime"])
        console.log("onOpen");
    };				                                                                                                    // открытия соединения
    web.onmessage = function (e) {      						                                                        // получение сообщения с бэка
        console.log(`Получили сообщение`)
        // console.log(e.ports)
        // console.log(e.source)
        console.log(e.data)
        console.log(JSON.parse(e.data))
    };

    web.onerror = function (e) {
        console.log(e)
    };				                                                                                                    // если возникла ошибка

    web.onclose = function (e) {
        console.log(e)
    };				                                                                                                    // закрытие соединения


    /**
     * Метод отправки сообщения по номеру клиента
     * @param payload - данные
     * @param destinationClientId - ключ клиента, которому шлем
     */
    function sendMessageToClient(payload, destinationClientId) {
        let message = buildMessage(payload, [], "send", null, destinationClientId);
        web.send(message);
        console.log("Отправил сообщение sendMessageToChanel");
    }

    /**
     * Метод отправки сообщений на канал
     * @param payload - данные
     * @param chanel - канал (не массив)
     */
    function sendMessageToChanel(payload, chanel) {
        let message = buildMessage(payload, [chanel], "send", null);
        web.send(message);
        console.log("Отправил сообщение sendMessageToChanel");
    }

    /**
     * Метод отправки сообщений на каналы
     * @param payload - данные
     * @param chanels - канал (массив)
     */
    function sendMessageToChanels(payload, chanels) {
        let message = buildMessage(payload, chanels, "send");
        web.send(message);
        console.log("Отправил сообщение sendMessageToChanels");
    }

    /**
     * Метод получения статистики по веб сокет серверу
     */
    function getStatisticWebSocket() {
        let message = buildMessage(null, null, "stateWebSocket");
        web.send(message);
        console.log("getStatisticWebSocket");
    }

    /**
     * Метод подписки на данные
     * @param subscribes - подписки (массив)
     */
    function subscribe(subscribes) {
        let message = buildMessage(null, subscribes, "subscribe")
        web.send(message);
        console.log("subscribe");
    }

    /**
     * Метод запроса данных с сервера
     * @param method - метод
     * @param params - параметры запроса
     * @param requestId - ключ запроса
     */
    function getDataFromServer(method, params, requestId) {
        let message = buildMessage(params, method, "get", requestId)
        web.send(message);
        console.log("getDataFromServer");
    }

    /**
     * Метод запроса ключа соединения
     */
    function getResourceId() {
        let message = buildMessage(null, null, "key")
        web.send(message);
        console.log("getResourceId");
    }

    /**
     * Метод формирования сообщения Web Socket
     * @param payload - данные
     * @param subscribes - подписки (массив)
     * @param actionType - тип сообщения (subscribe - подписаться, get - запросить данные, post - отправить данные на сервер, send - отослать данные другим участникам)
     * @param requestId - ключ запроса
     * @param clientId - ключ клиента, которому шлем
     * @returns {string} - json строка
     */
    function buildMessage(payload, subscribes, actionType, requestId = null, destinationClientId = null) {
        return JSON.stringify({
            'sourceClientId': null,
            'destinationClientId': destinationClientId,
            'actionType': actionType,
            'subscribes': subscribes,
            'data': payload,
            'clientType': "DashBoard",
            'requestId': requestId
        })
    }

</script>