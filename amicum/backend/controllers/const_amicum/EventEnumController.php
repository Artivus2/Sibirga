<?php
/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\const_amicum;

use yii\web\Controller;

/**
 * Class EventEnumController
 * Константы, определяющие возможные идентификаторы событий
 * @package backend\controllers
 */
class EventEnumController extends Controller
{
    const MOVEMENT_ON_CONVEYOR = 7126;  // Движение человека по конвейеру
    const SOS = 7127;                   // Шахтёр подал SOS
    const WORKER_NOT_MOVING = 7129;     // Человек без движения
    const LAMP_LOW_BATTERY = 7137;      // Низкий уровень заряда батареи светильника
    const WORKER_DANGER_ZONE = 7139;    // Человек в опасной зоне
    const BPD_LOW_BATTERY = 7161;       // Низкий заряд АКБ БПД-3
    const LOW_BATTERY = 22408;          // Низкий заряд батареек

    const DUST_EXCESS_STAC = 7125;      // Превышение удельной массы пыли

    const CH4_EXCESS_STAC = 7130;       // Превышение концентрации газа CH4
    const CH4_EXCESS_LAMP = 22409;      // Превышение CH4 со светильника

    const O2_EXCESS_LAMP = 50;          // Низкая концентрация кислорода со светильника

    const CO_EXCESS_STAC = 7131;        // Превышение концентрации газа CO
    const CO_EXCESS_LAMP = 22410;       // Превышение CO со светильника

    const CO2_EXCESS_LAMP = 7115;       // Превышение CO2 со светильника

    const CH4_CRUSH_LAMP = 7163;        // Отказ CH4 со светильника
    const CH4_CRUSH_STAC = 7164;        // Отказ CH4 стационарный датчик

    const GAS_DIFFERENCE = 22411;       // Датчик требует проверки на поверочной смеси
    const IN_MINE_OVER_13_HOURS = 22412;// Шахтёр более 13 часов в шахте
    const POS_MARK_LOW_BATTERY = 22413; // Низкий заряд батареи метки позиционирования

    const BPD_STOP = 7160;              // Отказ ИБП3 Связь отсутствует
    const DCS_STOP = 7120;              // Отказ службы сбора данных
}