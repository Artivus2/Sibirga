<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\const_amicum;

use yii\web\Controller;

/**
 * Константы, определяющие возможные типы инструктажей
 */
class TypeBriefingEnumController extends Controller
{
    const FIRST = 1;                        // Первичный по ОТ и ПБ
    const REPEAT = 2;                       // Повторный по ОТ и ПБ
    const UNPLANNED = 3;                    // Внеплановый по ОТ и ПБ
    const TARGET = 4;                       // Целевой по ОТ и ПБ
    const FIRE_FIGHTING = 5;                // Противопожарный
    const PREFATORY = 6;                    // Вводный


}