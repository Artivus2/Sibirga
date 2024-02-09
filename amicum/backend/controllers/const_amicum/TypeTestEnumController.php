<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\const_amicum;

use yii\web\Controller;

/**
 * Константы, определяющие возможные типы экзаменов
 */
class TypeTestEnumController extends Controller
{
    const STUDY = 3;                    // обучение
    const TEST = 2;                     // тест
    const PRED_SHIFT_EXAM = 1;          // предсменный тест


}