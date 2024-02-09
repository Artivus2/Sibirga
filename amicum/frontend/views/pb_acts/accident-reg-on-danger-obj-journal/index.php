<?php
/* @var $this yii\web\View */

use frontend\assets\AppAsset;

$this->registerCssFile('css/incident-logbook.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('css/accident.css', ['depends' => [AppAsset::className()]]);
$this->title = "ЖУРНАЛ УЧЕТА ИНЦИДЕНТОВ, ПРОИСШЕДШИХ НА ОПАСНЫХ ПРОИЗВОДСТВЕННЫХ
                ОБЪЕКТАХ, ГИДРОТЕХНИЧЕСКИХ СООРУЖЕНИЯХ";
?>

<div class="container">
    <div class="row">
        <div class="note">
            <div>
                <p>
                    <strong>Приложение N 5</strong>
                    к Порядку проведения технического
                    расследования причин аварий,
                    инцидентов и случаев утраты
                    взрывчатых материалов промышленного
                    назначения на объектах, поднадзорных
                    Федеральной службе по экологическому,
                    технологическому и атомному надзору,
                    утвержденному Приказом Федеральной
                    службы по экологическому,
                    технологическому и атомному надзору
                    от 19 августа 2011 г. N 480
                </p>
                <p>
                    (рекомендуемый образец)
                </p>
            </div>
        </div>

        <div class="caption">
            <h1>ЖУРНАЛ <br>
                УЧЕТА ИНЦИДЕНТОВ, ПРОИСШЕДШИХ НА ОПАСНЫХ ПРОИЗВОДСТВЕННЫХ
                ОБЪЕКТАХ, ГИДРОТЕХНИЧЕСКИХ СООРУЖЕНИЯХ
            </h1>
            <div class="header">
                <div class="header-block">
                    <p class="header-block-p"><input type="text" class="width530">,</p>
                    <p class="textOne"><sup>(полное название организации, эксплуатирующей объект)</sup></p>
                </div>

                <div class="header-block">
                    <p class="header-block-p">надзорный орган: <input type="text" class="width485">,</p>
                    <p class="textTwo"><sup>(название территориального органа Службы)</sup></p>
                </div>

                <div class="accident-log-header-block">
                    <p>за <input type="text" class="width100"> квартал <input type="text" class="width40" maxlength="4"> года</p>
                </div>
            </div>
        </div>

        <div class="table-content">
            <div class="table-header">
                <div class="table-column col-w-1"><span>№ п/п</span></div>
                <div class="table-column col-w-3"><span>Место инцидента, название объекта, регистрационный номер и дата его регистрации</span></div>
                <div class="table-column col-w-2"><span>Дата и время инцидента</span></div>
                <div class="table-column col-w-2"><span>Вид инцидента</span></div>
                <div class="table-column col-w-2"><span>Причины инцидента</span></div>
                <div class="table-column col-w-3"><span>Продолжительность простоя, часов</span></div>
                <div class="table-column col-w-2"><span>Недоотпуск энергии, кВт-ч</span></div>
                <div class="table-column col-w-3"><span>Экономический ущерб <*>, тыс. руб.</span></div>
                <div class="table-column col-w-3"><span>Мероприятия предложенные комиссией по расследованию причин инцидента</span></div>
                <div class="table-column col-w-3"><span>Отметка о выполнении мероприятий</span></div>
            </div>
            <div class="table-number">
                <div class="table-column-number col-w-1"><span>1</span></div>
                <div class="table-column-number col-w-3"><span>2</span></div>
                <div class="table-column-number col-w-2"><span>3</span></div>
                <div class="table-column-number col-w-2"><span>4</span></div>
                <div class="table-column-number col-w-2"><span>5</span></div>
                <div class="table-column-number col-w-3"><span>6</span></div>
                <div class="table-column-number col-w-2"><span>7</span></div>
                <div class="table-column-number col-w-3"><span>8</span></div>
                <div class="table-column-number col-w-3"><span>9</span></div>
                <div class="table-column-number col-w-3"><span>10</span></div>
            </div>
            <div class="table-row">
                <div class="table-column col-w-1"><span>1</span></div>
                <div class="table-column col-w-3"><span>2</span></div>
                <div class="table-column col-w-2"><span>3</span></div>
                <div class="table-column col-w-2"><span>4</span></div>
                <div class="table-column col-w-2"><span>5</span></div>
                <div class="table-column col-w-3"><span>6</span></div>
                <div class="table-column col-w-2"><span>7</span></div>
                <div class="table-column col-w-3"><span>8</span></div>
                <div class="table-column col-w-3"><span>9</span></div>
                <div class="table-column col-w-3"><span>10</span></div>
            </div>
        </div>
        <div class="comment_line"></div>
        <div class="explanation">
            <p><*> Экономический ущерб от аварии (инцидента) включает в себя прямой и экологический ущербы.</p>
        </div>
    </div>
</div>