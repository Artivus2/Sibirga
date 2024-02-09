<?php
/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\const_amicum;

use yii\web\Controller;

/**
 * Константы, определяющие идентификаторы типов параметров
 */
class ParameterTypeEnumController extends Controller
{
    const REFERENCE = 1;            // Справочный
    const MEASURED = 2;             // Измеренный
    const CALCULATED = 3;           // Вычисленный
}