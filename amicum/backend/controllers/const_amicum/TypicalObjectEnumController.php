<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\const_amicum;

use yii\web\Controller;

/**
 * Class TypicalObjectEnumController
 * Константы, определяющие возможные типовые объекты
 * @package backend\controllers
 */
class TypicalObjectEnumController extends Controller
{
    const CONJUNCTION = 12;             // ключ типа поворота/сопряжения
    const PLACE = 10;                   // ключ типа места (капитальная пройденная по породе)
    const PLAST = 13;                   // ключ паста
}