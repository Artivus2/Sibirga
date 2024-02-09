<?php

/* @var $this View */

use yii\helpers\Html;
use frontend\assets\AppAsset;
use yii\web\View;

/*<?= Yii::$app->language ?>*/
AppAsset::register($this);
$session = Yii::$app->session;
if ($session->has('sessionLogin')) {
    if ($_SERVER['REQUEST_URI'] == '/') {                                                                                 // Если пользователь пытается перейти на страницу авторизации
        $redirect_url = "/order-system";                                                                                         // Редиректить на главную страницу
//        $redirect_url = "/order-system/methane-analysis/agk-operator-journal";                                                                                         // ToDo: ВРЕМЕННО!!!
        header('HTTP/1.1 200 OK');
        header('Location: http://' . $_SERVER['HTTP_HOST'] . $redirect_url);
        exit();
    }
} else {
    if ($_SERVER['REQUEST_URI'] != '/') {
        $redirect_url = '/';
        header('HTTP/1.1 200 OK');
        header('Location: http://' . $_SERVER['HTTP_HOST'] . $redirect_url);
        exit();
    }
}
$sess_id = session_id();
if (!isset($sess_id) or $sess_id === "") {
    $redirect_url = '/';
    header('HTTP/1.1 200 OK');
    header('Location: http://' . $_SERVER['HTTP_HOST'] . $redirect_url);
    exit();
}

?>
<?php $this->beginPage() ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="<?= Yii::$app->charset ?>">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?= Html::csrfMetaTags() ?>
        <!--    <meta http-equiv='cache-control' content='no-cache'>-->
        <!--    <meta http-equiv='expires' content='0'>-->
        <!--    <meta http-equiv='pragma' content='no-cache'>-->
        <link rel="stylesheet" href="/css/animate.css">
        <link rel="stylesheet" href="/css/jquery.datetimepicker.min.css">
       <title><?= Html::encode($this->title) ?></title>
        <?php $this->head() ?>
    </head>
    <body>
    <?php $this->beginBody() ?>
    <div id="navbar">
        <div class="blocks" id="block1">
            <div class="top-middle">
                <div id="logo-container">
                    <div id="logo-trapezoid-background">
                        <img src="/img/amicum-logo.png" alt="Логотип АМИКУМ" id="logo_image"/>
                    </div>
                </div>
            </div>
            <div class="bottom">
                <div id="employee_page" class="col-b">
                    <a href="/handbooks/handbook-employee" id="employees"><span id="employee_page_title">сотрудники</span></a>
                </div>
            </div>
            <div class="selected-option">
                <div class="col-b hidden-item" id="first_element">
                    <div></div>
                </div>
            </div>
            <!-- id block1-->
        </div>
        <div class="blocks" id="block2">
            <div class="top">

                <div class="center-menu-items">
                    <div class="col-b">
                        <div class="transform-rotate-back" id="half"></div>
                    </div>
                    <div class="col-b">
                        <div class="transform-rotate-back" id="choose-date">
                            <div id="selectDateTime" class="date-time-selector" title="Выберите дату">
                                <img src="/img/calendar.png">
                                <span class="date-field span-date"></span>
                                <img src="/img/dataTime.png">
                                <span class="time-field span-date"></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-b">
                        <div class="transform-rotate-back" id="current-date"></div>
                    </div>
                    <div class="col-b">
                        <div class="transform-rotate-back"></div>
                    </div>
                </div>
            </div>
            <div class="middle">
                <div class="center-menu-items">

                    <div class="col-b" id="order">
                        <div class="transform-rotate-back">
                            <!--                            --><?php //if (isset($session['userShift'])) {
                            //                                echo $session['userShift'];
                            //                            } else {
                            //                                echo "";
                            //                            } ?>
                            <a href="/order-system" title="Переход в Нарядную систему">Нарядная система</a>
                        </div>
                    </div>

                    <div class="col-b">
                        <div class="transform-rotate-back"><?php if (isset($session['userWorkstation'])) {
                                echo $session['userWorkstation'];
                            } else {
                                echo "";
                            } ?></div>
                    </div>

                    <div class="col-b">
                        <div class="transform-rotate-back user-mine"><?php if (isset($session['userMineTitle']) and $session['userMineTitle'] != "-1") {
                                echo '&laquo;' . $session['userMineTitle'] . '&raquo;' ;
                            } else {
                                echo "";
                            } ?></div>
                    </div>
                    <div class="col-b">
                        <div class="transform-rotate-back"><?php if (isset($session['userName'])) {
                                echo $session['userName'];
                            } else {
                                echo "";
                            } ?></div>
                    </div>
                </div>
            </div>
            <div class="bottom">
                <div class="center-menu-items">
                    <div class="col-b dropdown" id="reports">
                        <a href="/summary-report-employee-and-transport-zones" class="dropdown-toggle"
                           data-toggle="dropdown">
                            <span class="transform-rotate-back">сводная статистика</span>
                        </a>
                    </div>
                    <div class="col-b" id="unity_button">
                        <a href="#">
                            <span class="transform-rotate-back">информация по шахте</span>
                        </a>
                    </div>
                    <div class="col-b" id="configurators">
                        <a href="#"><span class="transform-rotate-back">конфигурирование</span></a>
                    </div>
                    <?php if ($_SERVER['REQUEST_URI'] == '/handbook') echo 'active' ?>
                    <div class="col-b dropdown" id="handbook_button"><a href="#" class="dropdown-toggle"
                                                                        data-toggle="dropdown">
                            <span class="transform-rotate-back">справочники</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="selected-option">
                <div class="center-menu-items">
                    <div class="col-b hidden-item" id="second_element">
                        <div>
                            feature
                        </div>
                    </div>
                    <div class="col-b hidden-item" id="third_element">
                        <div>
                            feature
                        </div>
                    </div>
                    <div class="col-b hidden-item" id="fourth_element">
                        <div>
                            feature
                        </div>
                    </div>
                    <div class="col-b hidden-item" id="fifth_element">
                        <div>
                            feature
                        </div>
                    </div>
                </div>
            </div>
            <!-- id block2-->
        </div>
        <div class="blocks" id="block3">
            <div class="top"></div>
            <div class="middle">
                <div class="dropdown">
                    <div id="block3-hidden">
                        <div id="config" class="col-b"><a id="config_btn">
                                <div>
                                    <i class="icon-menu"></i>
                                </div>
                            </a></div>
                    </div>
                </div>
            </div>
            <div class="bottom">
                <div class="col-b" id="logout" title="Выйти из системы">
                    <a id="close_btn">
                        <i class="glyphicon glyphicon-remove"></i>
                    </a>
                </div>
            </div>

            <div class="selected-option">
                <div class="col-b hidden-item" id="last-element">
                    <div class="">feature</div>
                </div>
            </div>
            <!-- id block3-->
        </div>

        <ul class="dropdown-menu" role="menu" id="statistics">
            <li role="presentation">
                <a href="/reports/summary-report-employee-and-transport-zones" id="statistics1" >История местоположения персонала
                    и транспорта</a>
            </li>
                <li role="presentation">
                    <a href="/reports/summary-report-employee-forbidden-zones" id="statistics2">Нахождение персонала в запрещенных зонах</a>
                </li>
<!--            <li role="presentation">-->
<!--                <a href="/summary-report-time-spent" id="statistics3">Время нахождения персонала по зонам</a>-->
<!--            </li>-->
<!--            <li role="presentation">-->
<!--                <a href="/summary-report-transport-history" id="statistics4">История нахождения транспорта в шахте</a>-->
<!--            </li>-->
<!--            <li role="presentation">-->
<!--                <a href="/summary-report-time-table-report" id="statistics5">Табельный отчет</a>-->
<!--            </li>-->
            <li role="presentation">
                <a href="/reports/summary-report-end-of-shift" id="statistics6">Время выхода персонала по окончанию смены</a>
            </li>
<!--            <li role="presentation">-->
<!--                <a href="/summary-report-people-in-zones" id="statistics7">Нахождение людей по зоабоям</a>-->
<!--            </li>-->
<!--            <li role="presentation">-->
<!--                <a href="/summary-report-gaz-concentration" id="statistics7">Риски, связанные с концентрацией газов</a>-->
<!--            </li>-->
            <li role="presentation">
                <a href="/reports/summary-report-motionless-people" id="statistics8">Персонал без движения (события)</a>
            </li>
            <li role="presentation">
                <a href="/reports/summary-report-motionless-people-general" id="statistics9">Персонал без движения (общее
                    время)</a>
            </li>
            <li role="presentation">
                <a href="/reports/summary-report-excess-density-gas" id="statistics10">Превышение концентрации газа</a>
            </li>
<!--            <li role="presentation">-->
<!--                <a href="/doc-blanks" id="statistics11">Бланки документов</a>-->
<!--            </li>-->
        </ul>




        <ul class="dropdown-menu" role="menu" id="handbooks">
            <li role="presentation" class="hidden">
                <a href="/handbooks/handbook-sensor" id="sensor">Бригады</a>
            </li>
            <li role="presentation">
                <a href="/handbooks/handbook-sensor" id="sensor">Датчики, системы автоматизации</a>
            </li>
            <li>
                <a href="/handbooks/handbook-position" id="positions">Должности</a>
            </li>
            <li role="presentation">
                <a href="/handbooks/handbook-unit" id="units">Единицы измерения</a>
            </li>
            <li role="presentation" class="hidden">
                <a href="/handbooks/handbook-sensor" id="sensor">Забои</a>
            </li>
            <li role="presentation" class="hidden">
                <a href="/handbooks/handbook-sensor" id="sensor">Запретные зоны</a>
            </li>
            <li role="presentation" class="hidden">
                <a href="/handbooks/handbook-sensor" id="sensor">Звенья</a>
            </li>
            <li role="presentation">
                <a href="/handbooks/handbook-place" id="place">Места</a>
            </li>
            <li role="presentation" class="hidden">
                <a href="/handbooks/handbook-sensor" id="sensor">Операции</a>
            </li>
            <li role="presentation">
                <a href="/handbooks/handbook-parameter" id="parameters">Параметры</a>
            </li>
            <li role="presentation">
                <a href="/handbooks/handbook-department" id="departments">Подразделения</a>
            </li>
            <li role="presentation" class="hidden">
                <a href="/handbooks/handbook-sensor" id="sensor">Производственный учет материалов</a>
            </li>
            <li role="presentation">
                <a href="/handbooks/handbook-shift" id="workmodes">Режимы работы</a>
            </li>
            <li role="presentation">
                <a href="/handbooks/role" id="workmodes">Роли</a>
            </li>
            <li role="presentation">
                <a href="/handbooks/handbook-situation" id="sit">Ситуации</a>
            </li>
            <li role="presentation">
                <a href="/handbooks/handbook-event" id="events">События</a>
            </li>
            <li role="presentation" class="hidden">
                <a href="/handbooks/handbook-employee" id="employees">Сотрудники</a>
            </li>
            <li role="presentation">
                <a href="/handbooks/handbook-status" id="status">Статусы</a>
            </li>
            <li role="presentation">
                <a href="/handbooks/handbook-connect-string" id="connectStrings">Строка подключения</a>
            </li>
            <li role="presentation" class="hidden">
                <a href="/handbooks/handbook-sensor" id="sensor">Технологии</a>
            </li>
            <li role="presentation">
                <a href="/handbooks/handbook-texture" id="texture">Текстуры</a>
            </li>
            <li role="presentation">
                <a href="/handbooks/handbook-status-type" id="status-type">Типы статусов</a>
            </li>
            <li role="presentation">
                <a href="/handbooks/handbook-function" id="functions">Функции</a>
            </li>
            <li role="presentation">
                <a href="/handbooks/handbook-mine" id="mines">Шахты</a>
            </li>
        </ul>
        <ul class="dropdown-menu" role="menu" id="configurations">
            <!--        <li role="presentation">-->
            <!--            <a href="/handbook-regulation" id="reg">Регламенты</a>-->
            <!--        </li>-->
            <li role="presentation">
                <a href="/handbooks/handbook-typical-object" id="typical_objects">Типовые объекты</a>
            </li>
            <li role="presentation">
                <a href="/specific-object" id="specific_objects">Конкретные объекты</a>
            </li>
            <li role="presentation">
                <a href="/xml" id="xml">Модуль выгрузки данных в XML</a>
            </li>
            <li role="presentation">
                <a href="/bind-miner-to-lantern" id="lantern">Привязка лампы к сотруднику</a>
            </li>
            <li role="presentation">
                <a href="/lamp-list" id="lamp_list">Список шахтёров и их лампы</a>
            </li>
            <li role="presentation">
                <a href="/equipment-sensor" id="equipment_sensor">Привязка метки к оборудованию</a>
            </li>
        </ul>

        <ul class="dropdown-menu menu3" role="menu" id="menu3">
            <li role="presentation" class="hidden">
                <a href="#">Цветовая схема</a>
            </li>
            <li role="presentation" class="hidden">
                <a href="#">Масштабирование блоков</a>
            </li>
            <li role="presentation" class="hidden"><a href="#">ЕДБ</a></li>
            <li role="presentation" class="hidden"><a href="#">Система учета времени</a></li>
<!--            <li role="presentation" id="rabochie_mesta" class="hidden"><a href="#">Рабочие места</a>-->
<!--                <ul class="submenu work-place">-->
<!--                    <li role="presentation" id="arm_gornogo_mastera">-->
<!--                        <a href="/">АРМ ГМ</a>-->
<!--                    </li>-->
<!--                    <li role="presentation" id="arm_glavnogo_energetika">-->
<!--                        <a href="#">АРМ ГЭ</a>-->
<!--                    </li>-->
<!--                    <li role="presentation" id="arm_dispetchera_po_bezopasosti">-->
<!--                        <a href="/edb">АРМ Диспетчера по безопасности</a>-->
<!--                    </li>-->
<!--                    <li role="presentation" id="arm_buhgaltera">-->
<!--                        <a href="#">АРМ Бухгалтера</a>-->
<!--                    </li>-->
<!--                </ul>-->
<!--            </li>-->
            <li role="presentation" id="chooseMine"><a>Выбор шахты</a>
                <ul class="submenu mines">
                    <li role="presentation" class="click-to-pick-mine" id="mine-93"><span>Воргашорская</span></li>
                    <li role="presentation" class="click-to-pick-mine" id="mine-2470"><span>Воркутинская</span></li>
                    <li role="presentation" class="click-to-pick-mine" id="mine-94"><span>Заполярная</span></li>
                    <li role="presentation" class="click-to-pick-mine" id="mine-95"><span>Комсомольская</span></li>
                </ul>
            </li>
<!--            <li id="acts" role="presentation"><a>Для Кричигина</a>-->
<!--                <ul class="submenu" role="menu">-->
<!--                    <li role="presentation">-->
<!--                        <a href="/accident-group-invest-act" id="statistics18">Акт о расследовании группового несчастного случая (тяжелого несчастного случая, несчастного случая со смертельным исходом)</a>-->
<!--                    </li>-->
<!--                    <li role="presentation">-->
<!--                        <a href="/act-tech-inves-on-mat-loss" id="statistics14">Акт технического расследования случая утраты взрывчатых материалов</a>-->
<!--                    </li>-->
<!--                    <li role="presentation">-->
<!--                        <a href="/industrial-explosives-journal-reg" id="statistics17">Журнал регистрации случаев утраты взрывчатых материалов промышленного назначения</a>-->
<!--                    </li>-->
<!--                    <li role="presentation">-->
<!--                        <a href="/accident-journal-at-danger-ind" id="statistics15">Журнал учета аварий, происшедших на опасных производственных объектах, повреждений гидротехнических сооружений</a>-->
<!--                    </li>-->
<!--                    <li role="presentation">-->
<!--                        <a href="/accident-reg-on-danger-obj-journal" id="statistics20">Журнал учета инцидентов, происшедших на опасных производственных объектах, гидротехнических сооружениях</a>-->
<!--                    </li>-->
<!--                    <li role="presentation">-->
<!--                        <a href="/accident-group-notice" id="statistics19">Извещение о групповом несчастном случае (тяжелом несчастном случае, несчастном случае со смертельным исходом)</a>-->
<!--                    </li>-->
<!--                    <li role="presentation">-->
<!--                        <a href="/accident-operation-rep" id="statistics11">Оперативное сообщение о несчастном случае</a>-->
<!--                    </li>-->
<!--                    <li role="presentation">-->
<!--                        <a href="/accident-operation-fatal-rep" id="statistics12">Оперативное сообщение о несчастном случае (тяжелом, групповом, со смертельным исходом)</a>-->
<!--                    </li>-->
<!--                    <li role="presentation">-->
<!--                        <a href="/accident-place-insprec-rep" id="statistics13">Протокол осмотра места несчастного случая</a>-->
<!--                    </li>-->
<!--                    <li role="presentation">-->
<!--                        <a href="/accident-polling-rep" id="statistics21">Протокол опроса пострадавшего при несчастном случае (очевидца несчастного случая, должностного лица)</a>-->
<!--                    </li>-->
<!--                    <li role="presentation">-->
<!--                        <a href="/accident-work-consequens-rep" id="statistics16">Сообщения о последствиях несчастного случая на производстве и принятых мерах</a>-->
<!--                    </li>-->
<!--                    <li role="presentation">-->
<!--                        <a href="/insured-event-rep" id="statistics16">Сообщение о страховом случае</a>-->
<!--                    </li>-->
<!--                </ul></li>-->
            <?php
            if ($session['sessionLogin'] === "admin") {
                echo "<li role='presentation' id='admin_cabinet'><a href='/admin/users'>Администрирование</a></li>";
//                echo "<li role='presentation' id='file_uploader'><a href='/admin/file-uploader'>Документы</a></li>";
//                echo '<li role="presentation" id="testRestrictedUnity"><a href="/third-unity">Запретные зоны</a></li>';
//                echo "<li role='presentation' id='synchronization_sap'><a href='/synchronization-front'>Синхронизация справочников SAP</a></li>";
//                echo '<li role="presentation" id="testUnity"><a href="/secondary-unity">Тестовая схема шахты</a></li>';
                echo "<li role='presentation' id='cache_cabinet'><a href='/order-system/admin-panel'>Управление системой</a></li>";
            }
            ?>

<!--            <li role="presentation" id=""><a href="/order-system/list-handbooks">Справочники</a>-->
            <li role="presentation" id=""><a href="/order-system/handbook-handbooks">Справочники</a>
            <li role="presentation" id=""><a href="/order-system/user-account">Личный кабинет</a>

        </ul>

        <ul class="dropdown-menu" id="menu4">
            <li><a href="/unity" id="mine_scheme">Схема шахты</a></li>
            <li><a href="/positioningsystem/bpd" id="bpd">Контроль БПД-3</a></li>
            <li><a href="/sensor-info" id="sensor_info">Контроль объектов АС</a></li>
            <li><a href="/equipment-info" id="equipment_info">Контроль оборудования</a></li>
            <li><a href="/worker-info" id="worker_info">Контроль работников</a></li>
            <li><a href="/event-journal" id="event_journal">Журнал событий</a></li>
            <li><a href="/archive-event" id="archive_event">Архив событий</a></li>
        </ul>

        <!-- id navbar-->
    </div>
    <div class="modal fade" id="closing_tab">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header navbar-page">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h3>Вы уверены, что хотите закрыть это окно?</h3>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <div class="col-xs-2">
                            <button class='btn btn-primary' data-dismiss='modal' aria-hidden='true'
                                    onclick='close_window()'>Да
                            </button>
                        </div>
                        <div class="col-xs-2  col-xs-offset-8">
                            <button class="btn btn-default" data-dismiss="modal" aria-hidden="true">
                                Отмена
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ПРЕЛОАДЕР -->
    <div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel" id="preloader"
         data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="circle-container">
                <div id="circle_preload"></div>
                <h4 class="preload-title" style="padding: 77px 50px;">Загрузка</h4>
            </div>
        </div>
    </div>

    <div class="modal fade" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel" id="preloader2"
         data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="circle-container">
                <div id="circle_preload"></div>
                <h4 class="preload-title" style="padding: 77px 10px;">Выполняется выход</h4>
            </div>
        </div>
    </div>

<?php
	$this->registerJsFile('/js/index.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
    ?>
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

    <?php $this->endBody() ?>
    </body>
    </html>
<?php $this->endPage() ?>
