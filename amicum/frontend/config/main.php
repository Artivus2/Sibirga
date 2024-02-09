<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'frontend\controllers',
    'components' => [
        'request' => [
            'enableCsrfValidation' => false,
            'csrfParam' => '_csrf-frontend',
            'baseUrl' => '',
        ],
        'user' => [
            'identityClass' => 'frontend\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-frontend', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the frontend
            'name' => 'advanced-frontend',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],

        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '' => 'site/index',
//                '' => '/authorization',

//                'authorization-form' => 'site/index',

                /**
                 * Блок с роутингом для нарядной системы для Vue.js
                 **/
                '/login-form' => '/ordersystem/order-system/index',                                                     // страница навигации
                '/order-system' => '/ordersystem/order-system/index',                                                   // страница навигации
                '/order-system/test' => '/ordersystem/order-system/index',                                              // используется для тестовой страницы
                '/order-system/user-account' => '/ordersystem/order-system/index',                                      // используется личного кабинета пользователя

                /** Ссылки на модули **/
                '/order-system/order-system' => '/ordersystem/order-system/index',                                      // ЭКН
                '/order-system/book-directive' => '/ordersystem/order-system/index',                                    // КП
                '/order-system/control-danger-zone' => '/ordersystem/order-system/index',                               // Контроль Запретных Зон
                '/order-system/occupational_health_and_safety' => '/ordersystem/order-system/index',                    // ОТ и ПБ профзаболевания
                '/order-system/documents' => '/ordersystem/order-system/index',                                         // Документация
                '/order-system/passport-objects' => '/ordersystem/order-system/index',                                  // Паспорт объектов
                '/order-system/admin-panel' => '/ordersystem/order-system/index',                                       // Админка
                '/order-system/amicum-update' => '/ordersystem/order-system/index',                                     // модуль обновления (ахив обновлений)
                '/order-system/assigning-user-rights' => '/ordersystem/order-system/index',                             // выдача прав пользователя
                '/order-system/user-access-settings' => '/ordersystem/order-system/index',                              // настройка прав доступа
                '/order-system/order-route-map' => '/ordersystem/order-system/index',                                   // маршрутная карта выдачи нарядов
                '/order-system/order-route-map-mine' => '/ordersystem/order-system/index',                              // маршрутная карта выдачи нарядов c шахтами
                '/order-system/routes' => '/ordersystem/order-system/index',                                            // Маршруты
                '/order-system/amicum-archive-update' => '/ordersystem/order-system/index',                             // Модуль с архивными обновлениями АМИКУМ
                '/order-system/amicum-add-wish' => '/ordersystem/order-system/index',                                   // Модуль добавить пожелание
                '/order-system/amicum-arhive-update' => '/ordersystem/order-system/index',                              // Архив обновлений
                '/order-system/amicum-archive-wish-user' => '/ordersystem/order-system/index',                          // Архив пожеланий
                '/order-system/amicum-add-update' => '/ordersystem/order-system/index',                                 // Добавить пожелание
                '/order-system/amicum-add-works' => '/ordersystem/order-system/index',                                  // Добавить техработы
                '/order-system/amicum-archive-tech-works' => '/ordersystem/order-system/index',                         // Архив техработ

                /** СТАТИСТИКА И ОТЧЕТНОСТЬ */
                '/order-system/statistic-reports' => '/ordersystem/order-system/index',                                 // Статистическая отчетность
                '/order-system/report-start-end-shift' => '/ordersystem/order-system/index',                            // Журнал учета начала/окончания смены
                '/order-system/report-pred-exam' => '/ordersystem/order-system/index',                                  // Журнал прохождения предсменного экзаменатора
                '/order-system/summary-pred-exam' => '/ordersystem/order-system/index',                                 // Сводная аналитика прохождения предсменного экзаменатора

                /** ссылки модуля ЭКП **/
                '/order-system/downtime-accounting' => '/ordersystem/order-system/index',                               // Учет простоев
                '/order-system/previous-period-report' => '/ordersystem/order-system/index',                            // отчет за предыдущий период
                '/order-system/fill-report' => '/ordersystem/order-system/index',                                       // заполнить отчет
                '/order-system/os-shift-schedule' => '/ordersystem/order-system/index',                                 // график выходов
                '/order-system/os-shift-schedule-mine' => '/ordersystem/order-system/index',                            // график выходов с учетом шахт
                '/order-system/work-modes-history' => '/ordersystem/order-system/index',                                // история смены режимов работы
                '/order-system/table-form' => '/ordersystem/order-system/index',                                        // табличная форма выдачи нарядов
                '/order-system/table-form-mine' => '/ordersystem/order-system/index',                                   // табличная форма выдачи нарядов с учетом шахты
                '/order-system/interactive-form' => '/ordersystem/order-system/index',                                  // интерактивная форма выдачи нарядов
                '/order-system/routes-interactive' => '/ordersystem/order-system/index',                                // Интерактивная форма сравнения маршрутов
                '/order-system/routes-comparison' => '/ordersystem/order-system/index',                                 // Журнал сравнений маршрутов
                '/order-system/constructor-routes' => '/ordersystem/order-system/index',                                // Интерактивка создания шаблонов маршрута
                '/order-system/forbidden-zone-journal' => '/ordersystem/order-system/index',                            // Журнал запретных зон
                '/order-system/forbidden-zone-interactive' => '/ordersystem/order-system/index',                        // Интерактивка Запретных зон
                '/order-system/logbook-order' => '/ordersystem/order-system/index',                                     // Журнал регистрации наряд-допусков
                '/order-system/order-failure-reasons' => '/ordersystem/order-system/index',                             // Журнал регистрации наряд-допусков
                '/order-system/order-restrictions' => '/ordersystem/order-system/index',                                // Журнал регистрации наряд-допусков
//                '/order-system/workers-timetable' => '/ordersystem/order-system/index',
                '/order-system/accounting-for-materials' => '/ordersystem/order-system/index',                          // списание материалов
                '/order-system/order-ab' => '/ordersystem/order-system/index',                                          // наряды участка АБ/ВТБ
                '/order-system/order-ab-mine' => '/ordersystem/order-system/index',                                     // наряды участка АБ/ВТБ с учетом шахты
                '/order-system/order-ab-matching-mine' => '/ordersystem/order-system/index',                            // наряды участка АБ/ВТБ с учетом шахты
                '/order-system/order-ab-matching' => '/ordersystem/order-system/index',                                 // маршрутная карта выдачи наряда
                '/order-system/order-approving' => '/ordersystem/order-system/index',                                   // Общешахтная книга нарядов
                '/order-system/order-approving-mine' => '/ordersystem/order-system/index',                              // Общешахтная книга нарядов с учетом шахты
                '/order-system/modal-order-print' => '/ordersystem/order-system/index',                                 // универсальное модальное окно печатных форм
                '/order-system/printed-form-order' => '/ordersystem/order-system/index',                                // печатная форма книги нарядов версия 2
                '/order-system/printed-form-voucher' => '/ordersystem/order-system/index',                              // печатная форма наряд путевки версия 2
                '/order-system/printed-form-order-with-mine' => '/ordersystem/order-system/index',                      // печатная форма книги нарядов с учетом шахты
                '/order-system/printed-form-voucher-with-mine' => '/ordersystem/order-system/index',                    // печатная форма наряд путевки с учетом шахты

                /** ссылки модуля КП **/
                '/order-system/book-directive/fill-injunction-issue' => '/ordersystem/order-system/index',              // формирование предписания
                '/order-system/book-directive/fill-violation-issue' => '/ordersystem/order-system/index',               // формирование паб
                '/order-system/book-directive/fill-disconformity-issue' => '/ordersystem/order-system/index',           // формирование н/н
                '/order-system/book-directive/book-directive-archive' => '/ordersystem/order-system/index',             // архив предписаний / паб / нн
                '/order-system/book-directive/book-directive-statistic' => '/ordersystem/order-system/index',           // статистика по предписаниям / паб
                '/order-system/book-directive/book-directive-planned-audits' => '/ordersystem/order-system/index',      // график плановых аудитов
                '/order-system/book-directive/book-directive-print' => '/ordersystem/order-system/index',               // страница для печати предписания, паб, нн

                /** ссылки модуля анализа сравнения 2 газов**/
                '/order-system/methane-analysis' => '/ordersystem/order-system/index',
                '/order-system/methane-analysis/journal-event-ray' => '/ordersystem/order-system/index',
                '/order-system/methane-analysis/journal-event-methane' => '/ordersystem/order-system/index',
                '/order-system/methane-analysis/sensors-statistics' => '/ordersystem/order-system/index',
                '/order-system/methane-analysis/stationary-sensors-statistics' => '/ordersystem/order-system/index',
                '/order-system/methane-analysis/individual-sensors-statistics' => '/ordersystem/order-system/index',
                '/order-system/methane-analysis/situation-statistics' => '/ordersystem/order-system/index',
                '/order-system/methane-analysis/agk-operator-journal' => '/ordersystem/order-system/index',

                /** ссылка на СОУТ**/
                '/order-system/working-conditions-control' => '/ordersystem/order-system/index',
                '/order-system/working-conditions-planner' => '/ordersystem/order-system/index',

                /** Ссылки на Электронную библиотеку и паспорт объектов **/
                '/order-system/digital-library' => '/ordersystem/order-system/index',


                /** ссылки модуля СО **/

                /** ссылки модуля КЗЗ **/

                /** ссылки модуля ОТ и ПБ **/

                '/order-system/fire-safety-control' => '/ordersystem/order-system/index',
                '/order-system/registration-of-violations' => '/ordersystem/order-system/index',
                '/order-system/fire-safety-graph-security-control' => '/ordersystem/order-system/index',
                /** планирование МО **/
                '/order-system/medical-examination-control' => '/ordersystem/order-system/index',
                '/order-system/medical-examination-checkup' => '/ordersystem/order-system/index',
                '/order-system/medical-examination-graph' => '/ordersystem/order-system/index',
                '/order-system/medical-plan-schedule/' => '/ordersystem/order-system/index',                            // конструктор планирования МО
                '/order-system/medical-plan-schedule/print-form' => '/ordersystem/order-system/index',                  // печатная форма планирования МО
                '/order-system/medical-examination-plan-order' => '/ordersystem/order-system/index',                    // приказ на прохождение МО
                '/order-system/direction-for-medical-examination' => '/ordersystem/order-system/index',                 // страница печати направления на МО
                '/order-system/medical-assignment-list' => '/ordersystem/order-system/index',                           // страница печати листа ознакомления
                '/order-system/medical-assignment-list-factors' => '/ordersystem/order-system/index',                   // страница печати листа согласования c вредными факторами
                '/order-system/form-familiarization-direction-and-order' => '/ordersystem/order-system/index',          // компонент ознакомления с приказом

                '/order-system/medical-examination-control/payment-time-medical-examination' => '/ordersystem/order-system/index',

                // справочник контингента
                '/order-system/contingent-handbook/' => '/ordersystem/order-system/index',
                /** учет профзаболевании **/
                '/order-system/medical-disease-book' => '/ordersystem/order-system/index',

                /** ссылки модуля документации **/
                '/order-system/<controller>/<action>' => '/ordersystem/<controller>/<action>',

                /**
                 * Блок роутинга для справочников
                 */
                '/handbooks/<controller>/<action>' => '/handbooks/<controller>/<action>',
                /**
                 * Блок роутинга для раздела Информация по шахте
                 */
                '/unity' => '/positioningsystem/unity/index',
                '/unity/<action>' => '/positioningsystem/unity/<action>',
                '/unity-player/<action>' => '/positioningsystem/unity-player/<action>',
                '/worker-info' => '/positioningsystem/worker-info/index',
                '/worker-info/<action>' => '/positioningsystem/worker-info/<action>',
                '/bpd' => '/positioningsystem/bpd/index',
                '/bpd/<action>' => '/positioningsystem/bpd/<action>',
                '/equipment-info' => '/positioningsystem/equipment-info/index',
                '/equipment-info/<action>' => '/positioningsystem/equipment-info/<action>',
                '/event-journal' => '/positioningsystem/event-journal/index',
                '/event-journal/<action>' => '/positioningsystem/event-journal/<action>',
                '/archive-event' => '/positioningsystem/archive-event/index',
                '/archive-event/<action>' => '/positioningsystem/archive-event/<action>',
                '/sensor-info' => '/positioningsystem/sensor-info/index',
                '/sensor-info/<action>' => '/positioningsystem/sensor-info/<action>',
                /**
                 * Блок роутинга для раздела Конфигурирование
                 */
                'specific-object/' => '/positioningsystem/specific-object/index',
                'specific-equipment/<action>' => '/positioningsystem/specific-equipment/<action>',
                '/specific-mine/<action>' => '/positioningsystem/specific-mine/<action>',
                '/specific-object/<action>' => '/positioningsystem/specific-object/<action>',
                '/specific-place/<action>' => '/positioningsystem/specific-place/<action>',
                '/specific-edge/<action>' => '/positioningsystem/specific-edge/<action>',
                '/specific-conjunction/<action>' => '/positioningsystem/specific-conjunction/<action>',
                '/specific-massif/<action>' => '/positioningsystem/specific-massif/<action>',
                '/specific-sensor/<action>' => '/positioningsystem/specific-sensor/<action>',
                '/equipment-cache/<action>' => '/positioningsystem/equipment-cache/<action>',
                '/edge-cache/<action>' => '/positioningsystem/edge-cache/<action>',
                '/lamp-list' => '/positioningsystem/lamp-list/index',
                '/lamp-list/<action>' => '/positioningsystem/lamp-list/<action>',
                '/equipment-sensor' => 'positioningsystem/equipment-sensor/index',

                /**
                 * Блок актов по ПК и ОТ
                 */

                '/doc-blanks' => '/pb_acts/doc-blanks/index',
                '/accident-group-invest-act' => '/pb_acts/accident-group-invest-act/index',
                '/accident-group-notice' => '/pb_acts/accident-group-notice/index',
                '/accident-journal-at-danger-ind' => '/pb_acts/accident-journal-at-danger-ind/index',
                '/accident-operation-fatal-rep' => '/pb_acts/accident-operation-fatal-rep/index',
                '/accident-operation-rep' => '/pb_acts/accident-operation-rep/index',
                '/accident-place-insprec-rep' => '/pb_acts/accident-place-insprec-rep/index',
                '/accident-polling-rep' => '/pb_acts/accident-polling-rep/index',
                '/accident-reg-on-danger-obj-journal' => '/pb_acts/accident-reg-on-danger-obj-journal/index',
                '/accident-work-consequens-rep' => '/pb_acts/accident-work-consequens-rep/index',
                '/act-tech-inves-on-mat-loss' => '/pb_acts/act-tech-inves-on-mat-loss/index',
                '/application-investigation-accidents-dangerous' => '/pb_acts/application-investigation-accidents-dangerous/index',
                '/industrial-explosives-journal-reg' => '/pb_acts/industrial-explosives-journal-reg/index',
                '/insured-event-rep' => '/pb_acts/insured-event-rep/index',

                /**
                 * Блок ОТ и ПБ
                 */
                '/order-system/siz-control' => '/ordersystem/order-system/index',
                '/order-system/injury-accounting' => '/ordersystem/order-system/index',
                '/order-system/briefings-control' => '/ordersystem/order-system/index',
                '/order-system/checking-knowledge-protocol' => '/ordersystem/order-system/index',
                '/order-system/exemption-order' => '/ordersystem/order-system/index',
                '/order-system/traineeship-order' => '/ordersystem/order-system/index',
                '/order-system/work-permit-order' => '/ordersystem/order-system/index',
                '/order-system/siz-extend' => '/ordersystem/order-system/index',
                '/order-system/siz-write-off' => '/ordersystem/order-system/index',
                '/order-system/workers' => '/ordersystem/order-system/index',
                '/order-system/test-print' => '/ordersystem/order-system/index',
                '/order-system/accounting-expertise' => '/ordersystem/order-system/index',
                '/order-system/contracting-organization' => '/ordersystem/order-system/index',

                /**
                 * Акты списания/продления СИЗ
                 */
                '/order-system/write-off-act/:siz' => '/ordersystem/order-system/index',
                '/order-system/extension-act/:siz' => '/ordersystem/order-system/index',

                /**
                 * Справочники системы
                 **/

                '/order-system/handbook-handbooks' => '/ordersystem/order-system/index',
                '/order-system/handbook-roles' => '/ordersystem/order-system/index',
                '/order-system/handbook-positions' => '/ordersystem/order-system/index',
                '/order-system/handbook-classifier-diseases' => '/ordersystem/order-system/index',
                '/order-system/handbook-access' => '/ordersystem/order-system/index',
                '/order-system/handbook-check-knowledge' => '/ordersystem/order-system/index',
                '/order-system/handbook-diseases' => '/ordersystem/order-system/index',
                '/order-system/handbook-harmful-factors' => '/ordersystem/order-system/index',
                '/order-system/handbook-med-report-result' => '/ordersystem/order-system/index',
                '/order-system/handbook-chat-type' => '/ordersystem/order-system/index',
                '/order-system/handbook-chat-attachment-type' => '/ordersystem/order-system/index',
                '/order-system/handbook-sout' => '/ordersystem/order-system/index',
                '/order-system/handbook-brigade' => '/ordersystem/order-system/index',
                '/order-system/handbook-injury' => '/ordersystem/order-system/index',
                '/order-system/handbook-checking' => '/ordersystem/order-system/index',
                '/order-system/handbook-cyclegramm' => '/ordersystem/order-system/index',
                '/order-system/handbook-department' => '/ordersystem/order-system/index',
                '/order-system/handbook-edge' => '/ordersystem/order-system/index',
                '/order-system/handbook-situation' => '/ordersystem/order-system/index',
                '/order-system/handbook-internship' => '/ordersystem/order-system/index',
                '/order-system/handbook-kind-crash' => '/ordersystem/order-system/index',
                '/order-system/handbook-kind-document' => '/ordersystem/order-system/index',
                '/order-system/handbook-kind-direction-store' => '/ordersystem/order-system/index',
                '/order-system/handbook-fire-fighting' => '/ordersystem/order-system/index',
                '/order-system/handbook-stop-pb' => '/ordersystem/order-system/index',
                '/order-system/handbook-kind-mishap' => '/ordersystem/order-system/index',
                '/order-system/handbook-kind-fire-prevention-instruction' => '/ordersystem/order-system/index',
                '/order-system/handbook-repair-map' => '/ordersystem/order-system/index',
                '/order-system/handbook-kind-violation' => '/ordersystem/order-system/index',
                '/order-system/handbook-kind-parameter' => '/ordersystem/order-system/index',
                '/order-system/handbook-reason-occupational-illness' => '/ordersystem/order-system/index',
                '/order-system/handbook-kind-duration' => '/ordersystem/order-system/index',
                '/order-system/handbook-kind-incident' => '/ordersystem/order-system/index',
                '/order-system/handbook-kind-reason' => '/ordersystem/order-system/index',
                '/order-system/handbook-esmo' => '/ordersystem/order-system/index',
                '/order-system/handbook-esmo-allowance' => '/ordersystem/order-system/index',
                '/order-system/handbook-parameter' => '/ordersystem/order-system/index',
                '/order-system/handbook-place' => '/ordersystem/order-system/index',
                '/order-system/handbook-season' => '/ordersystem/order-system/index',
                '/order-system/handbook-sensor' => '/ordersystem/order-system/index',
                '/order-system/handbook-injury-outcome' => '/ordersystem/order-system/index',
                '/order-system/handbook-nomenclature' => '/ordersystem/order-system/index',
                '/order-system/handbook-group-operation' => '/ordersystem/order-system/index',
                '/order-system/handbook-operation-group' => '/ordersystem/order-system/index',
                '/order-system/handbook-operation-type' => '/ordersystem/order-system/index',
                '/order-system/handbook-operation-kind' => '/ordersystem/order-system/index',
                '/order-system/handbook-forbidden-type' => '/ordersystem/order-system/index',
                '/order-system/handbook-group-alarm' => '/ordersystem/order-system/index',
                '/order-system/handbook-instruction-pb' => '/ordersystem/order-system/index',
                '/order-system/handbook-instruction-type' => '/ordersystem/order-system/index',
                '/order-system/handbook-briefing-reason' => '/ordersystem/order-system/index',
                '/order-system/handbook-industrial-safety-object-type' => '/ordersystem/order-system/index',
                '/order-system/handbook-industrial-safety-object' => '/ordersystem/order-system/index',
                '/order-system/handbook-material' => '/ordersystem/order-system/index',
                '/order-system/handbook-research-type' => '/ordersystem/order-system/index',
                '/order-system/handbook-research-index' => '/ordersystem/order-system/index',
                '/order-system/handbook-shift' => '/ordersystem/order-system/index',
                '/order-system/handbook-work-modes' => '/ordersystem/order-system/index',
                '/order-system/handbook-kind-working-time' => '/ordersystem/order-system/index',
                '/order-system/handbook-type-operation' => '/ordersystem/order-system/index',
                '/order-system/handbook-kind-accident' => '/ordersystem/order-system/index',
                '/order-system/handbook-worker-type' => '/ordersystem/order-system/index',
                '/order-system/handbook-status-type' => '/ordersystem/order-system/index',
                '/order-system/handbook-paragraph-pb' => '/ordersystem/order-system/index',
                '/order-system/handbook-vid-document' => '/ordersystem/order-system/index',
                '/order-system/handbook-type-check-knowledge' => '/ordersystem/order-system/index',
                '/order-system/handbook-type-briefing' => '/ordersystem/order-system/index',
                '/order-system/handbook-type-shift' => '/ordersystem/order-system/index',
                '/order-system/handbook-type-accident' => '/ordersystem/order-system/index',
                '/order-system/handbook-operation' => '/ordersystem/order-system/index',
                '/order-system/handbook-chane-type' => '/ordersystem/order-system/index',
                '/order-system/handbook-chane' => '/ordersystem/order-system/index',
                '/order-system/handbook-kind-object' => '/ordersystem/order-system/index',
                '/order-system/handbook-object-type' => '/ordersystem/order-system/index',
                '/order-system/handbook-violation-type' => '/ordersystem/order-system/index',
                '/order-system/handbook-working-place' => '/ordersystem/order-system/index',
                '/order-system/handbook-kind-siz' => '/ordersystem/order-system/index',
                '/order-system/handbook-group-siz' => '/ordersystem/order-system/index',
                '/order-system/handbook-subgroup-siz' => '/ordersystem/order-system/index',
                '/order-system/handbook-siz' => '/ordersystem/order-system/index',
                '/order-system/handbook-xml-send-type' => '/ordersystem/order-system/index',
                '/order-system/handbook-xml-time-unit' => '/ordersystem/order-system/index',
                '/order-system/handbook-group-med-report-result' => '/ordersystem/order-system/index',
                '/order-system/handbook-classifier-diseases-kind' => '/ordersystem/order-system/index',
                '/order-system/handbook-classifier-diseases-type' => '/ordersystem/order-system/index',
                '/order-system/handbook-amicum-modules' => '/ordersystem/order-system/index',
                '/order-system/handbook-users-system' => '/ordersystem/order-system/index',
                '/order-system/handbook-pages' => '/ordersystem/order-system/index',
                '/order-system/handbook-workstation' => '/ordersystem/order-system/index',

                /**
                 * Прочее
                 */
                '/mine-node' => '/mine-node/index',
                '/equipment-sensor/<action>' => 'positioningsystem/equipment-sensor/<action>',
                '/worker-cache/<action>' => '/positioningsystem/worker-cache/<action>',
                '/synchronization-front' => 'synchronization-front/index',
                '/synchronization-front/<action>' => 'synchronization-front/<action>',

                /**
                 * Архив сообщений "Молния"
                 */
                '/order-system/archive-of-messages-lightning' => '/ordersystem/order-system/index',


                /**
                 * Блок СОУР
                 */
                '/risk-assessment-and-management-system' => '/risk-assessment-and-management-system/index',
                '/risk-assessment-and-management-system/regulation-constructor' => '/risk-assessment-and-management-system/index',
                '/risk-assessment-and-management-system/mine-statistics' => '/risk-assessment-and-management-system/index',
                '/risk-assessment-and-management-system/situation-elimination' => '/risk-assessment-and-management-system/index',
                '/risk-assessment-and-management-system/situation-control' => '/risk-assessment-and-management-system/index',               // оперативный журнал
                '/risk-assessment-and-management-system/print-injunction' => '/risk-assessment-and-management-system/index',
                '/risk-assessment-and-management-system/order-book' => '/risk-assessment-and-management-system/index',
                '/risk-assessment-and-management-system/equipment-inspection-schedule' => '/risk-assessment-and-management-system/index',   //График проверок оборудования
                '/risk-assessment-and-management-system/mine-injunctions' => '/risk-assessment-and-management-system/index',                // предписания по шахтам
                '/risk-assessment-and-management-system/modal-print' => '/risk-assessment-and-management-system/index',                     // печатная форма
                '/risk-assessment-and-management-system/elimination-history' => '/risk-assessment-and-management-system/index',             // история устранения ситуаций
                '/risk-assessment-and-management-system/archive-of-situations' => '/risk-assessment-and-management-system/index',           // архив ситуаций
                '/risk-assessment-and-management-system/arhis-modalprint' => '/risk-assessment-and-management-system/index',                // печатная форма для истории проверок
                '/risk-assessment-and-management-system/risks-level-mines' => '/risk-assessment-and-management-system/index',               // Уровень опасности(риска) по шахте
                '/risk-assessment-and-management-system/mine-information' => '/risk-assessment-and-management-system/index',                // информация по шахте
                '/risk-assessment-and-management-system/test-page' => '/risk-assessment-and-management-system/index',                       // тестовая страница с графиками


                /**
                 * Блок DASH BOARD
                 */
                '/dash-board' => '/dash-board/index',

                /**
                 * Предсменный экзаменатор
                 */
                '/shift-examiner' => '/shift-examiner/index',                                                           // Главная страница предсменного экзаменатора с меню модулей
                '/shift-examiner/login' => '/shift-examiner/index',                                                     // Страница авторизации Предсменного экзаменатора
                '/shift-examiner/control-knowledge' => '/shift-examiner/index',                                         // Начальная страница блока системы контроля знания
                '/shift-examiner/analytics' => '/shift-examiner/index',                                                 // Аналитика предсменного экзаменатора
                '/shift-examiner/test-builder' => '/shift-examiner/index',                                              // Главная страница конструктора тестов

                /**
                 * Модуль авторизации
                 */
                '/authorization' => '/authorization/index',                                                             // Авторизация по карте через терминал
                '/authorization-form' => '/authorization/index',                                                        // Авторизация по логину и паролю через терминал

                /**
                 * Блок Печатных форм
                 */
                '/order-system/module-print' => '/ordersystem/order-system/index',

                /**
                 * Модуль нарядной системы 2 версии
                 */
                '/order-system-version-two' => '/order-system-version-two/index',                                       // Страница меню модуля нарядной системы 2 версии
                '/order-system-version-two/issuance-order' => '/order-system-version-two/index',                        // Модуль выдачи нарядов
                '/order-system-version-two/order-restrictions' => '/order-system-version-two/index',                    // Модуль ограничений по наряду
                '/order-system-version-two/module-print' => '/order-system-version-two/index',                          // Модуль печати форм
                '/order-system-version-two/archive-of-messages-lightning' => '/order-system-version-two/index',         // Модуль сообщений "Молния" (общесистемные уведомления)

            ],
        ],

    ],
    'params' => $params,
];
