<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\const_amicum;

use yii\web\Controller;

/**
 * Константы, определяющие возможные статусы значений параметров
 */
class StatusEnumController extends Controller
{
    const ACTUAL = 1;                           // Актуально
    const NOT_ACTUAL = 19;                      // Не Актуально
    const FORBIDDEN = 15;                       // Запрет/запретная
    const PERMITTED = 16;                       // Разрешенная
    const CALCULATED_VALUE = 25;                // Вычисленное значение
    const MSG_DELIVERED = 28;                   // Сообщение доставлено
    const MSG_SENDED = 29;                      // Сообщение отправлено
    const MSG_READED = 30;                      // Сообщение прочитано
    const FORCED = 35;                          // Задано принудительно
    const EVENT_RECEIVED = 38;                  // Событие получено
    const EVENT_ELIMINATED_BY_SUPERVISOR = 40;  // Событие устранено диспетчером
    const ALARM_SENDED = 41;                    // Послал SOS
    const ALARM_READED = 42;                    // Подтвердил получение SOS
    const ALARM_DELIVERED = 43;                 // Доставлен сигнал SOS
    const EMERGENCY_VALUE = 44;                 // Аварийное значение
    const NORMAL_VALUE = 45;                    // Нормальное значение
    const EVENT_ELIMINATED_BY_SYSTEM = 52;      // Событие снято системой
    const DONE = 1;                                                                                                     // Выполнено
    const NOT_DONE = 0;                                                                                                 // Взято в работу, но не выполнено
    const SET_NULL = NULL;                                                                                              // Не выполнялось
    const SAP_TYPICAL_OBJECT = 119;                                                                                     // ключ типового обхекта для синхронизации САМ и АМИКУМ

    const EXAM_START = 130;                     // Начал сдавать тест
    const EXAM_END = 131;                       // Закончил сдавать тест
    const EXAM_NOT_START = 129;                 // Тест создан, но сдавать не начал
    const EXAM_NOT_DONE = 132;                  // Тест не сдан
    const EXAM_DONE = 133;                      // Тест успешно сдан


    const BRIEFING_CREATED = 67;                // Инструктаж создан
    const BRIEFING_CONDUCTED = 68;              // Инструктаж проведен
    const BRIEFING_FAMILIAR = 69;               // Инструктаж ознакомлен
}