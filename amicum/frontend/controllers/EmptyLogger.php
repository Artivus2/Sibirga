<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers;

use yii\log\Logger;


class EmptyLogger extends Logger
{

    public function log($message, $level, $category = 'application')
    {
        return false;
    }
}

