<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\const_amicum;

use yii\web\Controller;

/**
 * Константы, определяющие возможные причины проведения инструктажей
 */
class BriefingReasonEnumController extends Controller
{
    const DOUBLE_FAIL_EXAM = 1;                        // Не сдал предсменный экзаменатор более 2 раз подряд


}