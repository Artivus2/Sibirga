<?php
/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace backend\controllers\queuemanagers;

use yii\base\BaseObject;
use yii\queue\JobInterface;

class SynchronizationJobController extends BaseObject implements JobInterface
{
    /**
     * Контроллер для тестовой отладки очередей
     */
    public $text;
    public $file;

    public function execute($queue)
    {
        file_put_contents($this->file, $this->text);
    }
}