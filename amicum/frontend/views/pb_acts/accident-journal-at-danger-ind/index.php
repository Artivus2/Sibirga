<?php
/* @var $this yii\web\View */

use frontend\assets\AppAsset;

$this->registerCssFile('css/accident.css', ['depends' => [AppAsset::className()]]);
$this->title = "Журнал учета аварий, происшедших на опасных производственных объектах, повреждений гидротехничсеких сооружений";
?>
<div class="container">
    <div class="row">
        <div class="note">
            <div>
                <p>
                    <strong>Приложение N 4</strong>
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
                УЧЕТА АВАРИЙ, ПРОИСШЕДШИХ НА ОПАСНЫХ ПРОИЗВОДСТВЕННЫХ
                ОБЪЕКТАХ, ПОВРЕЖДЕНИЙ ГИДРОТЕХНИЧЕСКИХ СООРУЖЕНИЙ
            </h1>
            <div class="accident-log-header">
                <div class="accident-log-header-block">
                    <input type="text" class="mounth"><label for="#">,</label>
                </div>
                <div class="accident-log-header-text">
                    <p class="textOne"> (полное название организации, эксплуатирующей объект)</p>
                </div>
                <div class="accident-log-header-block">
                    <label for="#">надзорный орган:</label><input type="text"><label for="#">,</label>
                </div>
                <div class="accident-log-header-text">
                    <p class="textTwo">(название территориального органа Службы)</p>
                </div>
                <div class="accident-log-header-block">
                    <label for="">за <input type="text"> полугодие <input type="text" class="width40"> года</label>
                </div>
            </div>
        </div>
        <div class="table-content">
            <div class="table-header">
                <div class="table-column col-w-1"><span>№ п/п</span></div>
                <div class="table-column col-w-2"><span>Место аварии повреждения ГТС название объекта, регистрационный номер и дата его регистрации</span>
                </div>
                <div class="table-column col-w-2"><span>Дата и время аварии, повреждения ГТС</span></div>
                <div class="table-column col-w-2"><span>Вид аварии, повреждения ГТС</span></div>
                <div class="table-column col-w-3"><span>Краткое описание возникновения, развития, ликвидации, аварии, повреждения ГТС, причины, какие пункты действующих правил и требований были нарушены</span>
                </div>
                <div class="table-column col-w-2"><span>Наличие пострадавших</span></div>
                <div class="table-column col-w-2">
                    <span>Экономический ущерб от аварии, поврежденияГТС <*>, тыс.руб.</span></div>
                <div class="table-column col-w-2"><span>Недоотпуск энергии, тыс.Квт*ч</span></div>
                <div class="table-column col-w-2"><span>Продолжительность простоя до отпуска объекта в эксплуатацию, часов (суток)</span>
                </div>
                <div class="table-column col-w-2"><span>Лица, ответственные за допущенныю аварию, повреждение ГТС и принятые к ним меры воздействи(наказания)</span>
                </div>
                <div class="table-column col-w-2"><span>Дата направления материалов раследования в прокуратуру</span>
                </div>
                <div class="table-column col-w-2"><span>Мероприятия предложенные комиссией по техническому расследованию причин аварии, повреждения ГТС</span>
                </div>
                <div class="table-column col-w-2"><span>Отметка о выполнении мероприятий</span></div>
            </div>
            <div class="table-number">
                <div class="table-column-number col-w-1"><span>1</span></div>
                <div class="table-column-number col-w-2"><span>2</span></div>
                <div class="table-column-number col-w-2"><span>3</span></div>
                <div class="table-column-number col-w-2"><span>4</span></div>
                <div class="table-column-number col-w-3"><span>5</span></div>
                <div class="table-column-number col-w-2"><span>6</span></div>
                <div class="table-column-number col-w-2"><span>7</span></div>
                <div class="table-column-number col-w-2"><span>8</span></div>
                <div class="table-column-number col-w-2"><span>9</span></div>
                <div class="table-column-number col-w-2"><span>10</span></div>
                <div class="table-column-number col-w-2"><span>11</span></div>
                <div class="table-column-number col-w-2"><span>12</span></div>
                <div class="table-column-number col-w-2"><span>13</span></div>
            </div>
            <div class="table-row">
                <div class="table-column col-w-1"><span>1</span></div>
                <div class="table-column col-w-2"><span>2</span></div>
                <div class="table-column col-w-2"><span>3</span></div>
                <div class="table-column col-w-2"><span>4</span></div>
                <div class="table-column col-w-3"><span>5</span></div>
                <div class="table-column col-w-2"><span>6</span></div>
                <div class="table-column col-w-2"><span>7</span></div>
                <div class="table-column col-w-2"><span>8</span></div>
                <div class="table-column col-w-2"><span>9</span></div>
                <div class="table-column col-w-2"><span>10</span></div>
                <div class="table-column col-w-2"><span>11</span></div>
                <div class="table-column col-w-2"><span>12</span></div>
                <div class="table-column col-w-2"><span>13</span></div>
            </div>
        </div>
        <div class="comment_line"></div>
        <div class="explanation">
            <p><*> Экономический ущерб от аварии (инцидента) включает в себя прямой и экологический ущербы.</p>
        </div>
    </div>
</div>