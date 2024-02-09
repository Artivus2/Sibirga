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
class ParamEnum extends Controller
{
    const TITLE = 162;                              // Название
    const COORD = 83;                               // Координаты
    const VOLTAGE = 95;                             // Напряжение
    const GAS_LEVEL_O2 = 20;                        // Концентрация кислорода (O2)
    const GAS_LEVEL_CO2 = 30;                       // Концентрация углекислого газа CO2
    const GAS_LEVEL_CO = 98;                        // Концентрация угарного газа (CO)
    const GAS_LEVEL_CH4 = 99;                       // Концентрация метана (CH4)
    const PLACE_ID = 122;                           // Местоположение
    const CHECKIN = 158;                            // Статус спуска
    const STATE = 164;                              // Состояние
    const EDGE_ID = 269;                            // Ветвь/ребро схемы
    const ROUTING_PARENT_ID = 310;                  // Ближайший сосед маршрутизации
    const NUMBERS_OF_HEARTBEAT = 311;               // Количество отправленных heartbeat-сообщений с момента перезагрузки
    const RSSI_TO_ROUTING_PARENT = 312;             // Уровень сигнала до ближайшего соседа маршрутизации
    const ROUTING_GATEWAY_ID = 313;                 // Шлюз маршрутизации
    const TIMING_PARENT_ID = 314;                   // Ближайший сосед синхронизации
    const RSSI_TO_TIMING_PARENT = 315;              // Уровень сигнала до ближайшего соседа синхронизации
    const TIMING_GATEWAY_ID = 316;                  // Шлюз синхронизации
    const RSSI = 321;                               // Уровень сигнала до ближайшего узла связи
    const SOS = 323;                                // Флаг сигнала SOS
    const TEXT_MSG_FLAG = 324;                      // Флаг "Текстовое сообщение"
    const MINE_ID = 346;                            // Идентификатор шахты
    const PIC_VERSION = 354;                        // Версия PIC
    const CC1110_VERSION = 355;                     // Версия CC1110
    const NOT_MOVING = 356;                         // Флаг "Человек без движения"
    const DURATION_WITHOUT_MOVING = 100;            // продолжительность без движения
    const ALARM_SIGNAL_FLAG = 357;                  // Флаг сигнал об аварии
    const SURFACE_MOVING = 358;                     // Статус движения и местонахождения человека
    const HOPS_TO_ROUTING_GATEWAY = 359;            // Количество транзитных узлов до шлюза маршрутизации
    const HOPS_TO_TIMING_GATEWAY = 360;             // Количество транзитных узлов до шлюза синхронизации
    const NEIGHBOUR_TABLE_FULL = 361;               // Таблица соседних узлов заполнена
    const NEIGHBOUR_COUNT = 362;                    // Количество соседних узлов
    const TIMING_PARENT_LOST = 363;                 // Количество потерянных родителей синхронизации
    const ROUTING_PARENT_LOST = 364;                // Количество потерянных родителей маршрутизации
    const TIMING_PARENT_CHANGED = 365;              // Количество смененных родителей синхронизации
    const ROUTING_PARENT_CHANGED = 366;             // Количество смененных родителей маршрутизации
    const ROUTING_PARENT_ABOVE_RSSI = 367;          // Родитель маршрутизации выше индикатора мощности принятого сигнала
    const TIMING_PARENT_ABOVE_RSSI = 368;           // Родитель синхронизации выше индикатора мощности принятого сигнала
    const QUEUE_OVERFLOW_COUNT = 369;               // Значение переполнения очереди
    const NET_ENTRY_COUNT = 370;                    // Количество входов в сеть
    const MIN_NUMBER_IDLE_SLOTS = 371;              // Минимальное количество свободных интервалов
    const LISTEN_DURING_TRANSMIT = 372;             // Прослушивание во время передачи
    const NET_ENTRY_REASON = 373;                   // Причина входа в сеть
    const GRANDPARENT_BLOCKED = 374;                // Прародитель заблокирован
    const PARENT_TIMEOUT_EXPIRED = 375;             // Время ожидания родителя превышено
    const CYCLE_DETECTION = 376;                    // Обнаружен цикл
    const NO_IDLE_SLOTS = 377;                      // Нет свободных интервалов
    const GAS_EXCESS_O2 = 21;                       // Превышение концентрации О2
    const GAS_EXCESS_CH4 = 386;                     // Превышение концентрации CH4
    const GAS_EXCESS_CO = 387;                      // Превышение концентрации CO
    const GAS_EXCESS_CO2 = 31;                      // Превышение концентрации CO2
    const GAS_EXCESS_H2 = 228;                      // Превышение концентрации H2
    const WORKER_SPEED = 390;                       // Скорость движения человека
    const COMMNODE_BATTERY_PERCENT = 447;           // Процент уровня заряда батареи узла связи
    const MINER_BATTERY_PERCENT = 448;              // Процент заряда светильника
    const MINER_LAMP_FAILURE = 234;                 // Отказ светильника
    const ALARM_GROUP = 523;                        // Группа оповещения
    const PREDPRIYATIE = 18;                        // Предприятие (18 параметр) 1 - Заполярная/ 2 Воркутинская
    const EDGE_TYPE_ID = 449;                       // Ключ типа ветви
    const PLAST_ID = 347;                           // Ключ пласта
    const SECTION = 130;                            // Сечение ветви
    const LEVEL_CH4 = 263;                          // Концентрация метана
    const LEVEL_CO = 264;                           // Концентрация СО
    const HEIGHT = 128;                             // Высота ветви
    const WIDTH = 129;                              // Ширина ветви
    const LENGTH = 151;                             // Длина ветви
    const WEIGHT = 3;                               // ВЕС ветви
    const TEXTURE = 132;                            // Текстура модели id
    const ANGLE = 123;                              // Угол наклона ветви
    const DANGER_ZONA = 131;                        // Опасная зона (да/нет)
    const SHAPE_EDGE_ID = 150;                      // Форма выработки
    const CONVEYOR = 442;                           // Конвейер ветви
    const CONVEYOR_TAG = 389;                       // Тег конвейера ветви
    const TYPE_SHIELD_ID = 125;                     // Крепь выработки
    const COLOR_HEX = 124;                          // Цвет ветви
    const COMPANY_ID = 186;                         // Ответственное подразделение за ветвь
    const MO = 928;                                 // Статус прохождения работником медицинского осмотра
    const TEMP_EXPIRED = 229;                       // Превышение температуры
    const GAS_LOW_O2 = 230;                         // Понижение кислорода (O2)
    const CHARGE_BATTERY_LOW = 231;                 // Низкий заряд батареи
    const WORKER_SPEED_EXPIRED = 232;               // Превышение скорости передвижения
    const GAS_INDICATORS_DISCREPANCY = 233;         // Расхождение показателей газов
    const WORKER_IN_MINE_MORE_8H = 235;             // Работник больше 8 часов в шахте
}