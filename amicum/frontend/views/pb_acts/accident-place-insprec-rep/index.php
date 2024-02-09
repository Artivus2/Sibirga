<?php
/* @var $this yii\web\View */

use frontend\assets\AppAsset;

$this->registerCssFile('css/operational-report-about-accident.css', ['depends' => [AppAsset::className()]]);
$this->title = "Протокол осмотра места несчастного случая";
?>
<div class="container">
    <div class="row">
        <div class="caption-formSeven col-xs-12">
            <h1><strong>ПРОТОКОЛ</strong></h1>
            <h2><strong>осмотра места несчастного случая, происшедшего</strong></h2>
            <div class="col-xs-6 caption-formSeven-block" >
                <label for="#">"<input type="text" class="width20">"<input type="text" class="width200"> 20 <input type="text" class="width20">г.</label>
                <input type="text"  class="width350">
                <p>(место составления протокола)</p>
            </div>
            <div class="col-xs-6 caption-formSeven-block">
                <div>
                    <label for="#">c</label><input type="text" class="width350">
                    <p>(фамилия, инициалы, профессия (должность) пострадавшего)</p>
                </div>

                <div class="caption-formSeven-block-date">
                    <label for="#">"<input type="text" class="width20">"<input type="text"> 20 <input type="text" class="width20">г.</label>
                </div>

                <div>
                    <label for="#">Осмотр начат   в <input type="text" class="width40"> час. <input type="text" class="width40"> мин.</label>
                    <label for="#"> Осмотр окончен в<input type="text" class="width40"> час. <input type="text" class="width40"> мин.</label>
                </div>
            </div>
        </div>
        <div class="text-formSeven col-xs-12">
            <div>
                <label for="#">Мною, председателем   (членом)   комиссии   по   расследованию
                    несчастного случая на производстве, образованной приказом </label>
                <input type="text" class="inputFull">
                <p>(фамилия, инициалы работодателя - физического лица
                    либо наименование</p>
            </div>
            <div class="labelFlex">
                <input type="text"><label for="#">от "<input type="text" class="width20">" <input type="text"> 20 <input type="text" class="width20">г. №
                    <input type="text" class="width40">,</label>
                <p>организации)</p>
            </div>
            <div>
                <input type="text" class="inputFull">
                <p> (должность, фамилия, инициалы председателя
                    (члена комиссии), производившего опрос)</p>
            </div>
            <div class="labelFlex">
                <label for="#">произведен осмотр места несчастного случая, происшедшего в </label>
                <input type="text">
                <input type="text" class="inputFull">
                <p>(наименование организации и ее структурного подразделения либо фамилия и инициалы работодателя - физического лица; дата несчастного случая)</p>
            </div>
            <div class="labelFlex">
                <label for="#">c</label> <input type="text">
                <p> (профессия (должность), фамилия, инициалы пострадавшего)</p>
            </div>
            <div class="labelFlex">
                <label for="#">Осмотр проводился в присутствии</label><input type="text">
                <p>(процессуальное положение, фамилии, инициалы других лиц, </p>
                <input type="text" class="inputFull">
                <p>участвовавших в осмотре: другие члены комиссии по расследованию несчастного случая, доверенное лицо пострадавшего, адвокат и др.)</p>
                <input type="text" class="inputFull">
            </div>
        </div>

        <div class="caption-formSeven col-xs-12">
            <h2><strong>В ходе осмотра установлено:</strong></h2>
        </div>

        <div class="text-formSeven col-xs-12">
            <div>
                <label for="#">1) обстановка и состояние места происшествия несчастного случая на момент осмотра</label>
                <input type="text" class="inputFull">
                <p> (изменилась или нет по свидетельству пострадавшего
                    или очевидцев несчастного случая, краткое изложение существа изменений)</p>
                <input type="text" class="inputFull">
            </div>
            <div >
                <label for="#">2) описание    рабочего    места    (агрегата,   машины,   станка,
                    транспортного средства  и  другого  оборудования),  где  произошел
                    несчастный случай <input type="text" class="wight660"></label>
                <p> (точное указание рабочего места, тип (марка),</p>
                <input type="text" class="inputFull">
                <p> инвентарный хозяйственный номер агрегата, машины,
                    станка, транспортного средства и другого оборудования)</p>
                <input type="text" class="inputFull">
            </div>
            <div>
                <label for="#">2.1.  Сведения  о  проведении  специальной  оценки  условий  труда
                    (аттестации   рабочих   мест   по   условиям  труда)  с  указанием
                    индивидуального номера рабочего места и класса (подкласса) условий
                    труда  <input type="text" class="width280"><*></label>
            </div>
            <div>
                <label for="#">2.2.  Сведения  об  организации,  проводившей  специальную  оценку
                    условий   труда   (аттестацию  рабочих  мест  по  условиям  труда)
                    (наименование, ИНН)<input type="text" class="width610"><**></label>
                <input type="text" class="inputFull">
            </div>
            <div>
                <label for="#">3) описание части оборудования (постройки, сооружения), материала,
                    инструмента,  приспособления и  других  предметов,  которыми  была
                    нанесена травма <input type="text" class="width570"></label>

                <p>(указать конкретно их наличие и состояние)</p>
                <input type="text" class="inputFull">
            </div>
            <div  class="labelFlex">
                <label for="#">4) наличие  и  состояние  защитных  ограждений  и  других  средств
                    безопасности </label>
                <input type="text" >
                <p>(блокировок, средств</p>
                <input type="text" class="inputFull">
                <p>сигнализации, защитных экранов, кожухов, заземлений
                    (занулений), изоляции проводов и т.д.)</p>
            </div>
            <div>
                <label for="#">5) наличие  и  состояние  средств индивидуальной защиты,  которыми
                    пользовался пострадавший</label>
                <input type="text" class="inputFull">
                <p> (наличие сертифицированной спецодежды, спецобуви
                    и других средств индивидуальной защиты, их соответствие</p>
                <input type="text" class="inputFull">
                <p>нормативным требованиям)</p>
                <input type="text" class="inputFull">
            </div>
            <div class="labelFlex">
                <label for="#">6) наличие общеобменной и местной вентиляции и ее состояние</label>
                <input type="text">
                <input type="text" class="inputFull">
                <input type="text" class="inputFull">
            </div>
            <div class="labelFlex">
                <label for="#">7) состояние освещенности и температуры</label><input type="text">
                <p>  (наличие приборов</p>
                <input type="text" class="inputFull">
                <p>   освещения и обогрева помещений и их состояние)</p>
                <input type="text" class="inputFull">
            </div>
            <div class="labelFlex">
                <label for="#">8)</label>
                <input type="text" >
                <input type="text" class="inputFull">
            </div>
            <div class="labelFlex">
                <label for="#">В ходе осмотра проводилась</label><input type="text">
                <p>(фотосъемка, видеозапись и т.п.)</p>
            </div>
            <div class="labelFlex">
                <label for="#">С места происшествия изъяты</label><input type="text">
                <p>(перечень и индивидуальные характеристики изъятых предметов)</p>
            </div>
            <div class="labelFlex">
                <label for="#">С места происшествия изъяты</label><input type="text">
                <p>(схема места происшествия, фотографии и т.п.)</p>
            </div>
            <div class="labelFlex">
                <label for="#">Перед началом,  в ходе либо по окончании осмотра от участвующих  в осмотре лиц</label><input type="text">
                <input type="text" class="inputFull">
                <p>(их процессуальное положение, фамилия, инициалы)</p>
            </div>
            <div class="labelFlex">
                <label for="#">заявления </label><input type="text"> <label for="#">. Содержание заявлений: </label><input type="text">
                <input type="text" class="inputFull">
                <input type="text" class="inputFull">
                <input type="text" class="inputFull">
            </div>
            <div class="text-formSeven-padleft">
                <input type="text " class="inputFull">
                <p>
                    (подпись, фамилия, инициалы лица,
                    проводившего осмотр места происшествия)
                </p>
            </div>
            <div class="text-formSeven-padleft">
                <input type="text" class="inputFull">
                <p>
                    (подписи, фамилии, инициалы иных лиц,
                    участвовавших в осмотре
                    места происшествия
                </p>
            </div>
            <div class="labelFlex">
                <label for="#">С настоящим протоколом ознакомлены</label><input type="text">
                <p>
                    (подписи, фамилии, инициалы
                    участвовавших в осмотре
                    лиц, дата
                </p>
            </div>
            <div class="labelFlex">
                <label for="#">Протокол прочитан вслух</label><input type="text">
                <p>
                    (подпись, фамилия, инициалы лица,
                    проводившего осмотр, дата)
                </p>
            </div>
            <div class="labelFlex">
                <label for="#">Замечания к протоколу</label><input type="text">
                <p>
                    (содержание замечаний либо указание
                    на их отсутствие)
                </p>
                <input type="text" class="inputFull">
                <input type="text" class="inputFull">
            </div>
            <div class="labelFlex">
                <label for="#">Протокол составлен</label><input type="text">
                <p>
                    (должность, фамилия, инициалы председателя
                    (члена) комиссии, проводившего осмотр,
                    подпись, дата)
                </p>

            </div>
        </div>
        <div class="dotted  col-xs-12"> </div>
        <div class="explanation  col-xs-12">
            <p><*> Если специальная оценка условий труда (аттестация рабочих мест по условиям труда) не проводилась, в пункте 2.1 указывается "не проводилась", пункт 2.2 не заполняется.
            </p>
        </div>
    </div>
</div>