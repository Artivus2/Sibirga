<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\const_amicum;

use yii\web\Controller;

/**
 * Константы, определяющие возможные типы крепи
 */
class TypeShieldEnumController extends Controller
{
    const WOOD = 1;                             // Деревянная
    const METAL = 2;                            // Металлическая
    const ANCHOR = 3;                           // Анкерная
    const STONE = 4;                            // Каменная

}