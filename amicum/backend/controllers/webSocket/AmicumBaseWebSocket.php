<?php
/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\webSocket;

use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

/**
 * Базовый класс ВебСокет Сервера
 */
class AmicumBaseWebSocket implements MessageComponentInterface
{

    function onOpen(ConnectionInterface $conn)
    {
    }

    function onClose(ConnectionInterface $conn)
    {
    }

    function onError(ConnectionInterface $conn, Exception $e)
    {
    }

    function onMessage(ConnectionInterface $from, $msg)
    {
    }
}