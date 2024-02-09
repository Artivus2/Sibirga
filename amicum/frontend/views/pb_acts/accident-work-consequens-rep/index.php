<?php
/* @var $this yii\web\View */

use frontend\assets\AppAsset;

$this->registerCssFile('css/operational-report-about-accident.css', ['depends' => [AppAsset::className()]]);
$this->title = "Сообщение о последствиях несчастного случая на производстве
                    и принятых мерах";
?>
<div class="blockText" style="display: inline-block; opacity: 0;"> </div>

<div class="container">
    <div class="row">
        <div class="caption-formEight col-xs-12">
            <h1><strong>СООБЩЕНИЕ</strong></h1>
            <h2><strong>о последствиях несчастного случая на производстве
                    и принятых мерах</strong></h2>
        </div>
        <div class="text-formEight col-xs-12">
            <div class="labelFlex containerInput">
                <label for="#"><span>Несчастный случай на производстве, происшедший  </span> </label> <input type="text" class="inputText">
                <p>(дата несчастного случая)</p>
                <label for="#">c</label><input type="text" class="inputText">
                <p>(фамилия, инициалы пострадавшего)</p>
                <label for="#">работающим (ей), работавшим (ей)</label><input type="text" class="inputText">
                <p>(профессия (должность) пострадавшего, место работы:</p>
                <input type="text" class="inputFull">
                <p>наименование, место нахождения и юридический адрес организации,</p>
                <input type="text" class="inputFull">
                <p>фамилия и инициалы  работодателя — физического лица и его регистрационные данные)</p>
            </div>

            <div class="labelFlex">
                <label for="#">Данный несчастный случай оформлен актом о несчастном случае на производстве № <input type="text">,</label>
                <label for="#">утвержденным "<input type="text" class="width20">" <input type="text" class="month">20 <input type="text" class="width20">г.</label>
                <input type="text">
                <input type="text" class="inputFull">
                <p>(должность, фамилия, инициалы лица, утвердившего акт о несчастном случае на производстве)</p>
            </div>

            <div class="text-formEight-caption">
                <p><strong>Последствия несчастного случая на производстве:</strong></p>
            </div>
            <div>
                <label for="#">1) пострадавший выздоровел; переведен на другую работу; установлена инвалидность III, II, I групп; умер (нужное подчеркнуть);</label>
            </div>
            <div class="labelFlex">
                <label for="#">2) окончательный диагноз по заключению (справке) лечебного учреждения</label>
                <input type="text" class="inputFull">
                <p>(при несчастном случае со смертельным исходом — по заключению органа судебно-медицинской экспертизы)</p>
                <input type="text" class="inputFull">
            </div>

            <div class="labelFlex">
                <label for="#">3) продолжительность временной нетрудоспособности пострадавшего<input type="text">дней.</label>
                <label for="#">Освобожден от работы с "<input type="text" class="width20">" <input type="text" class="month"> 20 <input type="text" class="width20">г. по"<input type="text" class="width20">" <input type="text" class="month"> 20 <input type="text" class="width20">г.</label>

                <label for="#">Продолжительность выполнения другой работы (в случае перевода пострадавшего на другую работу)
                    <input type="text">рабочих дней;</label>
            </div>

            <div>
                <label for="#">4) стоимость испорченного оборудования и инструмента в результате несчастного случая на производстве</label>
                <input type="text" class="fullWidth95"> <label for="#">руб.;</label>
            </div>
            <div>
                <label for="#">5) стоимость разрушенных зданий и сооружений в результате несчастного случая на производстве</label>
                <input type="text" class="fullWidth95"> <label for="#">руб.;</label>
            </div>
            <div>
                <label for="#">6) сумма прочих расходов (на проведение экспертиз, исследований, оформление материалов и др.)</label>
                <input type="text" class="fullWidth95"> <label for="#">руб.;</label>
            </div>
            <div>
                <label for="#">7) суммарный материальный ущерб от последствий несчастного случая на производстве</label>
                <input type="text" class="fullWidth95"> <label for="#">руб.;</label>
            </div>

            <div class="labelFlex">
                <label for="#">8) сведения о назначении сумм ежемесячных выплат пострадавшему в возмещение вреда</label>
                <input type="text" >
                <input type="text" class="inputFull">
                <p>(дата и номер приказа (распоряжения) страховщика о назначении указанных сумм, размер сумм)</p>
            </div>

            <div class="labelFlex">
                <label for="#">9) сведения о назначении сумм ежемесячных  выплат  лицам,  имеющим
                    право на их получение (в случае смерти пострадавшего)
                    <input type="text" class="width678"></label>
                <input type="text" class="inputFull test">
                <p>(дата и номер приказа (распоряжения) страховщика</p>
            </div>

            <div class="labelFlex">
                <label for="#">10) сведения о решении прокуратуры о возбуждении (отказе в возбуждении) уголовного дела по факту несчастного случая на производстве
                    <input type="text" class="width690"></label>
                <input type="text">
                <p>(дата, номер и краткое содержание решения прокуратуры по факту данного несчастного случая)</p>
                <input type="text"  class="inputFull">
                <input type="text"  class="inputFull">
            </div>

            <div class="text-formEight-caption">
                <p><strong>Принятые меры по устранению причин несчастного случая на производстве:</strong></p>
            </div>

            <div>
                <input type="text"  class="inputFull">
                <p>(излагается информация о реализации мероприятий по устранению причин несчастного случая,</p>
                <input type="text"  class="inputFull">
                <p>предусмотренных в акте о несчастном случае, предписании государственного инспектора труда и</p>
                <input type="text"  class="inputFull">
                <p>других документах, принятых по результатам расследования)</p>
                <input type="text"  class="inputFull">
                <input type="text"  class="inputFull">
                <input type="text"  class="inputFull">
                <input type="text"  class="inputFull">
                <input type="text"  class="inputFull">
            </div>

            <div class="labelFlex text-formEight-footer">
                <label for="#">Работодатель (его представитель)</label><input type="text">
                <p>(фамилия, инициалы, должность, подпись)</p>
                <label for="#">Главный бухгалтер</label><input type="text">
                <p>(фамилия, инициалы, подпись)</p>

            </div>

            <div class="text-formEight-date">
                <input type="text" class="month">
                <p>Дата</p>
            </div>
        </div>

    </div>

</div>