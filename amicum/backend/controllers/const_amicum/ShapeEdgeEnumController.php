<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\const_amicum;

use yii\web\Controller;

/**
 * Константы, определяющие возможные формы выработки
 */
class ShapeEdgeEnumController extends Controller
{
    const RECTANGLE = 1;                            // Прямоугольная
    const TRAPEZOID = 2;                            // Трапециевидная
    const POLYGONAL = 3;                            // Полигональная
    const VAULTED = 4;                              // Сводчатая
    const ARCHED = 5;                               // Арочная
    const ROUND = 6;                                // Круглая

}