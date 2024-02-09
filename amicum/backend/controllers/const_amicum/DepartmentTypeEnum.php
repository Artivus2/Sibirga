<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\const_amicum;

use yii\web\Controller;

/**
 * Константы, определяющие идентификаторы параметров объектов
 */
class DepartmentTypeEnum extends Controller
{
    const LAVA = 1;                                 // Очистной участок
    const PROHODKA = 2;                             // Подготовительный участок
    const OTHER = 5;                                // Прочее
    const TRANSPORT = 4;                            // Участок транспорта
    const VSPOMOGAT = 3;                            // Вспомогательный участок
}