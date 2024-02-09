<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

/* @var $this \yii\web\View */
/* @var $content string */

use backend\assets\AppAsset;
use yii\helpers\Html;
use yii\web\View;

AppAsset::register($this);
$session = Yii::$app->session;
////var_dump($session['userWorkstation']);
//if ($session->has('sessionLogin')) {
//    if ($_SERVER['REQUEST_URI'] == '/') {                                                                                 // Если пользователь пытается перейти на страницу авторизации
//        $redirect_url = "/unity";                                                                               // Редиректить на главную страницу
//        header('HTTP/1.1 200 OK');
//        header('Location: http://' . $_SERVER['HTTP_HOST'] . $redirect_url);
//        exit();
//    }
//} else

//if ($_SERVER['REQUEST_URI'] != '/') {
//    $redirect_url = '/';
//    header('HTTP/1.1 200 OK');
//    header('Location: http://' . $_SERVER['HTTP_HOST'] . $redirect_url);
//    exit();
//}
//
//$sess_id = session_id();
//if (!isset($sess_id) or $sess_id === "") {
//    $redirect_url = '/';
//    header('HTTP/1.1 200 OK');
//    header('Location: http://' . $_SERVER['HTTP_HOST'] . $redirect_url);
//    exit();
//}
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/css/animate.css">
    <link rel="stylesheet" href="/css/jquery.datetimepicker.min.css">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
    <div class="system__header hidden-print">
    <div class="system__logo" onclick="document.location.href='/order-system'">
        <div class="logo-container">
            <a href="/order-system"><img src="/img/header_icons/Logo.png" alt="Амикум"></a>
        </div>
    </div>
    <div class="system__menu">
        <div class="top__menu">
            <div class="header-department-item">
                <span></span>
            </div>
            <div class="header-mine-item">
                <span class="small-text">шахта по умолчанию: &nbsp;</span>
                <span class="mine-title"></span>
            </div>
            <div class="header-company-item">
                <span></span>
            </div>
            <div class="header-workstation-item">
                <span></span>
            </div>
            <div class="header-worker-full-name">
                <span></span>
            </div>
            <div class="cabinet">
                <a href="/order-system/user-account">
                    <img src="/img/header_icons/Worker.png" alt="Личный кабинет">
                </a>
            </div>
            <div class="exit">
                <img src="/img/header_icons/Close_grey.png" alt="Выход из учетной записи">
            </div>
        </div>
        <div class="bottom__menu">
            <div class="date__container">
                <div class="date">
                    <div>
                        <img src="/img/header_icons/Date.png">
                        <span class="date__block"></span>
                    </div>
                </div>
            </div>
            <div class="mainPage">
                <img class="menu__img" src="/img/header_icons/Main_page_grey.png">
                <span class="menu__title">Главная</span>

                <div class="submenu">
                    <div class="submenu__item">
                        <a href="/order-system/order-system">
                            <div class="submenu__title">
                                <div>Модуль ЭКН</div>
                            </div>
                        </a>

                        <div class="subsubmenu">
                            <div>
                                <a href="/order-system/os-shift-schedule-mine">
                                    <div class="submenu__title">
                                        <div>График выходов</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/table-form-mine">
                                    <div class="submenu__title">
                                        <div>Табличная форма выдачи наряда</div>
                                    </div>
                                </a>
                            </div>
                            <!--                            <div>-->
                            <!--                                <a href="/order-system/interactive-form">-->
                            <!--                                    <div class="submenu__title">-->
                            <!--                                        <div>Интерактивная форма выдачи наряда</div>-->
                            <!--                                    </div>-->
<!--                                </a>-->
<!--                            </div>-->
                            <div>
                                <a href="/order-system/previous-period-report">
                                    <div class="submenu__title">
                                        <div>Отчет за предыдущий период</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/fill-report">
                                    <div class="submenu__title">
                                        <div>Заполнить отчет</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/order-route-map-mine">
                                    <div class="submenu__title">
                                        <div>Маршрутная карта выдачи нарядов</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/order-restrictions">
                                    <div class="submenu__title">
                                        <div>Ограничения по наряду</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/order-failure-reasons">
                                    <div class="submenu__title">
                                        <div>Причины невыполнения наряда</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/order-ab-mine">
                                    <div class="submenu__title order-ab">
                                        <div>Выдача наряда на производство работ по линии АБ (ВТБ)</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/order-ab-matching-mine">
                                    <div class="submenu__title">
                                        <div>Согласование наряда на производство работ</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/order-approving-mine">
                                    <div class="submenu__title">
                                        <div>Общешахтная книга нарядов</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/accounting-for-materials">
                                    <div class="submenu__title">
                                        <div>Учет материалов</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/downtime-accounting">
                                    <div class="submenu__title">
                                        <div>Учет простоев</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/briefings-control">
                                    <div class="submenu__title briefings">
                                        <div>Автоматизированный контроль прохождения инструктажей, проверок знаний, аттестаций</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/logbook-order">
                                    <div class="submenu__title">
                                        <div>Журнал регистрации наряд-допусков</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="submenu__item">
                        <a href="/order-system/book-directive">
                            <div class="submenu__title">
                                <div>Модуль Книга предписаний</div>
                            </div>
                        </a>

                        <div class="subsubmenu">
                            <div>
                                <a onclick="openBookDirectiveModules('modalAudit')">
                                    <div class="submenu__title">
                                        <div>Оформить проверку/аудит</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a onclick="openBookDirectiveModules('modalBsa')">
                                    <div class="submenu__title">
                                        <div>Сформировать ПАБ</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a onclick="openBookDirectiveModules('modalNonobservance')">
                                    <div class="submenu__title">
                                        <div>Оформить нарушение / несоответствие</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/book-directive/book-directive-statistic">
                                    <div class="submenu__title">
                                        <div>Статистика предписаний и ПАБ</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/book-directive/book-directive-archive">
                                    <div class="submenu__title">
                                        <div>Архив предписаний и ПАБ</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/book-directive/book-directive-planned-audits">
                                    <div class="submenu__title">
                                        <div>График плановых аудитов</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="submenu__item">
                        <a href="/order-system/methane-analysis">
                            <div class="submenu__title">
                                <div>Модуль Анализ показателей дублирующего контроля газов</div>
                            </div>
                        </a>

                        <div class="subsubmenu">
                            <div>
                                <a href="/order-system/methane-analysis/journal-event-ray">
                                    <div class="submenu__title">
                                        <div>Журнал событий по индивидуальным датчикам</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/methane-analysis/journal-event-methane">
                                    <div class="submenu__title">
                                        <div>Журнал событий по стационарным датчикам CH<sub>4</sub></div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/methane-analysis/agk-operator-journal">
                                    <div class="submenu__title">
                                        <div>Оперативный журнал инженера-оператора АГК (АБ)</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/methane-analysis/sensors-statistics">
                                    <div class="submenu__title">
                                        <div>Сводная статистика датчиков CH<sub>4</sub></div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/methane-analysis/situation-statistics">
                                    <div class="submenu__title">
                                        <div>Сводная статистика по ситуациям</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/methane-analysis/individual-sensors-statistics">
                                    <div class="submenu__title">
                                        <div>Статистика по расхождениям индивидуальных датчиков</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/methane-analysis/stationary-sensors-statistics">
                                    <div class="submenu__title">
                                        <div>Статистика по расхождениям стационарных датчиков</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="submenu__item">
                        <a href="/order-system/occupational_health_and_safety">
                            <div class="submenu__title">
                                <div>Модуль ОТ и ПБ</div>
                            </div>
                        </a>

                        <div class="subsubmenu">
                            <div>
                                <a href="/order-system/medical-examination-checkup">
                                    <div class="submenu__title">
                                        <div>Учет медосмотров</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/medical-examination-control">
                                    <div class="submenu__title">
                                        <div>Планирование и контроль медосмотров</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/contingent-handbook">
                                    <div class="submenu__title">
                                        <div>Список контингента работников</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/medical-examination-control/payment-time-medical-examination">
                                    <div class="submenu__title">
                                        <div>Журнал учета оплаты времени прохождения предсменных МО</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/medical-disease-book">
                                    <div class="submenu__title">
                                        <div>Учет профзаболеваний</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/injury-accounting">
                                    <div class="submenu__title">
                                        <div>Учет травматизма и происшествий</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/working-conditions-control">
                                    <div class="submenu__title">
                                        <div>Специальная оценка условий труда и санитарный производственный
                                            контроль
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/briefings-control">
                                    <div class="submenu__title">
                                        <div>Автоматизированный контроль прохождения инструктажей, проверок знаний,
                                            аттестаций
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/fire-safety-control">
                                    <div class="submenu__title">
                                        <div>Контроль наличия средств пожарной безопасности</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/accounting-expertise">
                                    <div class="submenu__title">
                                        <div>Учет, проведение и планирование экспертизы промышленной безопасности
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/siz-control">
                                    <div class="submenu__title">
                                        <div>Контроль наличия СИЗ</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/contracting-organization">
                                    <div class="submenu__title">
                                        <div>Подрядные организации</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/registration-of-violations">
                                    <div class="submenu__title">
                                        <div>Учет нарушений и иных сведений о персонале</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="submenu__item">
                        <a href="/order-system/statistic-reports">
                            <div class="submenu__title">
                                <div>Модуль Статистическая отчетность</div>
                            </div>
                        </a>

                        <div class="subsubmenu">
                            <div>
                                <a onclick="openReportStatistic(1)">
                                    <div class="submenu__title">
                                        <div>Отчеты по подразделению за предыдущий период</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a onclick="openReportStatistic(2)">
                                    <div class="submenu__title">
                                        <div>Статистика предписаний и ПАБ</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a onclick="openReportStatistic(3)">
                                    <div class="submenu__title">
                                        <div>Статистика профзаболеваний</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a onclick="openReportStatistic(4)">
                                    <div class="submenu__title">
                                        <div>Статистика нарушений и иных сведений о персонале</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a onclick="openReportStatistic(5)">
                                    <div class="submenu__title">
                                        <div>Статистика медосмотров</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a onclick="openReportStatistic(6)">
                                    <div class="submenu__title">
                                        <div>Статистика прохождения инструктажей, проверок знаний и
                                            аттестаций</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a onclick="openReportStatistic(7)">
                                    <div class="submenu__title">
                                        <div>Статистика травматизма и происшествий</div>
                                    </div>
                                </a>
                            </div>

                            <div>
                                <a onclick="openReportStatistic(8)">
                                    <div class="submenu__title">
                                        <div>Статистика наличия и состояния средств пожарной безопасности</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a onclick="openReportStatistic(9)">
                                    <div class="submenu__title">
                                        <div>Статистика наличия и состояния СИЗ</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a onclick="openReportStatistic(10)">
                                    <div class="submenu__title">
                                        <div>Статистика проведения и планирования ЭПБ</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a onclick="openReportStatistic(11)">
                                    <div class="submenu__title">
                                        <div>Статистика СОУТ / ПК</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a onclick="openReportStatistic(12)">
                                    <div class="submenu__title">
                                        <div>Производственная статистика</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/report-start-end-shift">
                                    <div class="submenu__title">
                                        <div>Журнал учета начала/окончания смены</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/report-pred-exam">
                                    <div class="submenu__title">
                                        <div>Журнал прохождения предсменного экзаменатора</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/summary-pred-exam">
                                    <div class="submenu__title">
                                        <div>Сводная аналитика прохождения предсменного экзаменатора</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="submenu__item">
                        <a href="/order-system/routes">
                            <div class="submenu__title">
                                <div>Модуль Маршруты</div>
                            </div>
                        </a>

                        <div class="subsubmenu">
                            <div>
                                <a href="/order-system/forbidden-zone-interactive">
                                    <div class="submenu__title">
                                        <div>Контроль запретных зон</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/forbidden-zone-journal">
                                    <div class="submenu__title">
                                        <div>Журнал запретных зон</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/constructor-routes">
                                    <div class="submenu__title">
                                        <div>Конструктор шаблонов маршрутов</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/routes-interactive">
                                    <div class="submenu__title">
                                        <div>Сравнение маршрутов</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/routes-comparison">
                                    <div class="submenu__title">
                                        <div>Журнал сравнения маршрутов</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="submenu__item">
                        <a href="#">
                            <div class="submenu__title">
                                <div>Модуль Документация</div>
                            </div>
                        </a>

                        <div class="subsubmenu">
                            <div>
                                <a href="/order-system/passport-objects">
                                    <div class="submenu__title">
                                        <div>Паспорта объектов</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/digital-library">
                                    <div class="submenu__title">
                                        <div>Электронная библиотека</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="workersGear">
                <img class="menu__img" src="/img/header_icons/Workers_and_equipment_grey.png">
                <span class="menu__title">Сотрудники и оборудование</span>

                <div class="submenu">
                    <div class="submenu__item">
                        <a href="/handbooks/handbook-employee">
                            <div class="submenu__title">
                                <div>Список сотрудников</div>
                            </div>
                        </a>
                    </div>
                    <div class="submenu__item">
                        <a href="/lamp-list">
                            <div class="submenu__title">
                                <div>Список шахтеров и их лампы</div>
                            </div>
                        </a>
                    </div>
                    <div class="submenu__item">
                        <a href="/bind-miner-to-lantern">
                            <div class="submenu__title">
                                <div>Привязка лампы к сотруднику</div>
                            </div>
                        </a>
                    </div>
                    <div class="submenu__item">
                        <a href="/equipment-sensor">
                            <div class="submenu__title">
                                <div>Привязка метки к оборудованию</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <div class="reports">
                <img class="menu__img" src="/img/header_icons/Reports_grey.png">
                <span class="menu__title">Отчеты</span>

                <div class="submenu">
                    <div>
                        <a href="/event-journal">
                            <div class="submenu__title">
                                <div>Журнал событий</div>
                            </div>
                        </a>
                    </div>
                    <div>
                        <a href="/archive-event">
                            <div class="submenu__title">
                                <div>Архив событий</div>
                            </div>
                        </a>
                    </div>
                    <div>
                        <a href="/reports/summary-report-employee-and-transport-zones">
                            <div class="submenu__title">
                                <div>История местоположения персонала и транспорта</div>
                            </div>
                        </a>
                    </div>
                    <div>
                        <a href="/reports/summary-report-employee-forbidden-zones">
                            <div class="submenu__title">
                                <div>Нахождение персонала в запрещенных зонах</div>
                            </div>
                        </a>
                    </div>
                    <div>
                        <a href="/reports/summary-report-end-of-shift">
                            <div class="submenu__title">
                                <div>Время выхода персонала по окончанию смены</div>
                            </div>
                        </a>
                    </div>
                    <div>
                        <a href="/reports/summary-report-motionless-people">
                            <div class="submenu__title">
                                <div>Нахождение персонала без движения (события)</div>
                            </div>
                        </a>
                    </div>
                    <div>
                        <a href="/reports/summary-report-motionless-people-general">
                            <div class="submenu__title">
                                <div>Нахождение персонала без движения (общее время)</div>
                            </div>
                        </a>
                    </div>
                    <div>
                        <a href="/reports/summary-report-excess-density-gas">
                            <div class="submenu__title">
                                <div>Превышение концентрации газа</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <div class="objectsControl">
                <img class="menu__img" src="/img/header_icons/Map_grey.png">
                <span class="menu__title">Схема шахты и контроль объектов</span>

                <div class="submenu">
                    <div class="submenu__item">
                        <a href="/unity">
                            <div class="submenu__title">
                                <div>Схема шахты</div>
                            </div>
                        </a>

                        <!--                            <div class="subsubmenu">-->
                        <!--                                <div>-->
                        <!--                                    <a href="#">-->
                        <!--                                        <div class="submenu__title">-->
                        <!--                                            <div>Информация по шахте</div>-->
                        <!--                                        </div>-->
                        <!--                                    </a>-->
                        <!--                                </div>-->
                        <!--                                <div>-->
                        <!--                                    <a href="#">-->
                        <!--                                        <div class="submenu__title">-->
                        <!--                                            <div>Исторический режим</div>-->
                        <!--                                        </div>-->
                        <!--                                    </a>-->
                        <!--                                </div>-->
                        <!--                            </div>-->
                    </div>
                    <div class="submenu__item">
                        <a href="#">
                            <div class="submenu__title">
                                <div>Контроль объектов и персонала</div>
                            </div>
                        </a>

                        <div class="subsubmenu">
                            <div>
                                <a href="/positioningsystem/bpd">
                                    <div class="submenu__title">
                                        <div>Контроль БПД-3</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/sensor-info">
                                    <div class="submenu__title">
                                        <div>Контроль объектов АС</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/equipment-info">
                                    <div class="submenu__title">
                                        <div>Контроль оборудования</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/worker-info">
                                    <div class="submenu__title">
                                        <div>Контроль работников</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="notification">
                <img src="/img/header_icons/Notification.png" id="headerNotificationBtn">
            </div>
            <div class="hamburger">
                <img class="menu__img" src="/img/header_icons/Hamburger_grey.png">

                <div class="submenu">
<!--                    <div class="submenu__item hidden" id="selectMineForUnity">-->
                    <div class="submenu__item">
                        <a href="#">
                            <div class="submenu__title">
                                <div>Выбор схемы шахты</div>
                            </div>
                        </a>
                        <div class="subsubmenu" id="mineList"></div>
                    </div>
                    <div class="submenu__item">
                        <a href="#">
                            <div class="submenu__title">
                                <div>Обновление Амикум</div>
                            </div>
                        </a>

                        <div class="subsubmenu">
                            <div>
                                <a href="/order-system/amicum-archive-update">
                                    <div class="submenu__title">
                                        <div>Архив обновлений</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/amicum-archive-wish-user">
                                    <div class="submenu__title">
                                        <div>Архив пожеланий</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/amicum-archive-tech-works">
                                    <div class="submenu__title">
                                        <div>Архив тех. работ</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="submenu__item">
                        <a href="#">
                            <div class="submenu__title">
                                <div>Конфигурирование системы</div>
                            </div>
                        </a>

                        <div class="subsubmenu">
                            <div class="subsubmenu__item">
                                <a href="#">
                                    <div class="submenu__title">
                                        <div>Справочники</div>
                                    </div>
                                </a>
                                <div class="subsubsubmenu">
                                    <div>
                                        <a href="/order-system/handbook-handbooks">
                                            <div class="submenu__title">
                                                <div>Базовые справочники</div>
                                            </div>
                                        </a>
                                    </div>
                                    <div>
                                        <a href="/handbooks/handbook-sensor">
                                            <div class="submenu__title">
                                                <div>Датчики, системы автоматизации</div>
                                            </div>
                                        </a>
                                    </div>
                                    <div>
                                        <a href="/handbooks/handbook-unit">
                                            <div class="submenu__title">
                                                <div>Единицы измерения</div>
                                            </div>
                                        </a>
                                    </div>
                                    <div>
                                        <a href="/handbooks/handbook-parameter">
                                            <div class="submenu__title">
                                                <div>Параметры</div>
                                            </div>
                                        </a>
                                    </div>
                                    <div>
                                        <a href="/handbooks/handbook-shift">
                                            <div class="submenu__title">
                                                <div>Режимы работы</div>
                                            </div>
                                        </a>
                                    </div>
                                    <div>
                                        <a href="/handbooks/handbook-situation">
                                            <div class="submenu__title">
                                                <div>Ситуации</div>
                                            </div>
                                        </a>
                                    </div>
                                    <div>
                                        <a href="/handbooks/handbook-event">
                                            <div class="submenu__title">
                                                <div>События</div>
                                            </div>
                                        </a>
                                    </div>
                                    <div>
                                        <a href="/handbooks/handbook-connect-string">
                                            <div class="submenu__title">
                                                <div>Строка подключения</div>
                                            </div>
                                        </a>
                                    </div>
                                    <div>
                                        <a href="/handbooks/handbook-status">
                                            <div class="submenu__title">
                                                <div>Статусы</div>
                                            </div>
                                        </a>
                                    </div>
                                    <div>
                                        <a href="/handbooks/handbook-texture">
                                            <div class="submenu__title">
                                                <div>Текстуры</div>
                                            </div>
                                        </a>
                                    </div>
                                    <div>
                                        <a href="/handbooks/handbook-mine">
                                            <div class="submenu__title">
                                                <div>Шахты</div>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="subsubmenu__item">
                                <a href="#">
                                    <div class="submenu__title">
                                        <div>Объекты</div>
                                    </div>
                                </a>

                                <div class="subsubsubmenu">
                                    <div>
                                        <a href="/handbooks/handbook-typical-object">
                                            <div class="submenu__title">
                                                <div>Типовые объекты</div>
                                            </div>
                                        </a>
                                    </div>
                                    <div>
                                        <a href="/specific-object">
                                            <div class="submenu__title">
                                                <div>Конкретные объекты</div>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <a href="/xml">
                                    <div class="submenu__title">
                                        <div>Модуль выгрузки данных в XML</div>
                                    </div>
                                </a>
                            </div>
                            <!--                                <div>-->
                            <!--                                    <a href="/risk-assessment-and-management-system/regulation-constructor">-->
                            <!--                                        <div class="submenu__title">-->
                            <!--                                            <div>Конструктор регламентов</div>-->
                            <!--                                        </div>-->
                            <!--                                    </a>-->
                            <!--                                </div>-->
                        </div>
                    </div>
                    <div class="submenu__item">
                        <a href="#">
                            <div class="submenu__title">
                                <div>Администрирование</div>
                            </div>
                        </a>

                        <div class="subsubmenu">
                            <div>
                                <a href="/admin/users">
                                    <div class="submenu__title">
                                        <div>Создание пользователей</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/user-access-settings">
                                    <div class="submenu__title">
                                        <div>Настройка прав доступа</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/assigning-user-rights">
                                    <div class="submenu__title">
                                        <div>Выдача прав пользователя</div>
                                    </div>
                                </a>
                            </div>
                            <div>
                                <a href="/order-system/admin-panel">
                                    <div class="submenu__title">
                                        <div>Управление системой</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <div class="exit__block">
        <div class="exit__header">
            <div class="exit__button" onclick="closeExitBlock()">
                <img src="/img/header_icons/Close_grey.png">
            </div>
        </div>
        <div class="exit__content">
            <div class="exit__text">
                <span>Выйти из учетной записи?</span>
            </div>
        </div>
        <div class="exit__footer">
            <div class="exit__no" onclick="closeExitBlock()">
                <div>
                    <span>Нет</span>
                </div>
            </div>
            <div class="exit__yes" onclick="logOut()">
                <div>
                    <span>Да</span>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <?= $content ?>
    </div>

    <footer>
        <div>
            <div class="circle"></div>
            <div id="text_f">
                <span>Разработчик программы ООО «Профсоюз» | <?php echo date ( 'Y' ) ; ?></span>
            </div>
            <div class="circle"></div>
        </div>
    </footer>
    <?php

$script = <<< JS
    let headerDepartmentItem = document.querySelector('.header-department-item > span'),
        headerMineItem = document.querySelector('.header-mine-item > .mine-title'),
        headerCompanyItem = document.querySelector('.header-company-item > span'),
        headerWorkstationItem = document.querySelector('.header-workstation-item > span'),
        headerWorkerFullName = document.querySelector('.header-worker-full-name > span');

    const parsedUserData = JSON.parse(localStorage.getItem('serialWorkerData'));
    headerDepartmentItem.textContent = parsedUserData.mainCompanyTitle;
    headerMineItem.textContent = parsedUserData.userMineTitle;
    headerCompanyItem.textContent = parsedUserData.userCompany;
    headerWorkstationItem.textContent = parsedUserData.userWorkstation;
    headerWorkerFullName.textContent = parsedUserData.userName;

JS;

    $this->registerJs($script, yii\web\View::POS_READY);
    $this->registerJsFile('/js/index.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
    $this->registerJsFile('/js/main.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
    $this->registerJsFile('/js/clicksOutMenu.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
    ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
