<?php
/* @var $this yii\web\View */

use frontend\assets\AppAsset;

$this->registerCssFile('css/application-inv-accidents-dangerous.css', ['depends' => [AppAsset::className()]]);
$this->title = "АКТ ТЕХНИЧЕСКОГО РАССЛЕДОВАНИЯ ПРИЧИН АВАРИИ
                НА ОПАСНЫХ ПРОИЗВОДСТВЕННЫХ ОБЪЕКТАХ. ПОВРЕЖДЕНИЯ
                ИДРОТЕХНИЧЕСКОГО СООРУЖЕНИЯ";
?>
<div class="container">
    <div class="row">
        <div class="note">
            <div>
                <p>
                    <strong>Приложение N 1</strong>
                    к Порядку проведения технического<br>
                    расследования причин аварий,<br>
                    инцидентов и случаев утраты<br>
                    взрывчатых материалов промышленного<br>
                    назначения на объектах, поднадзорных<br>
                    Федеральной службе по экологическому,<br>
                    технологическому и атомному надзору,<br>
                    утвержденному Приказом Федеральной<br>
                    службы по экологическому,<br>
                    технологическому и атомному надзору<br>
                    от 19 августа 2011 г. N 480
                </p>
                <p>
                    (рекомендуемый образец)
                </p>
            </div>
        </div>
        <div class="caption">
            <h1>АКТ<br>
                ТЕХНИЧЕСКОГО РАССЛЕДОВАНИЯ ПРИЧИН АВАРИИ <br>
                НА ОПАСНЫХ ПРОИЗВОДСТВЕННЫХ ОБЪЕКТАХ. ПОВРЕЖДЕНИЯ<br>
                ИДРОТЕХНИЧЕСКОГО СООРУЖЕНИЯ, ПРОИСШЕДШЕЙ (-ГО)
                <div class="accident-log-header">
                    <div class="accident-log-header-block">
                        <label for="#">"</label><input type="text" class="field-day" maxlength="2"><label
                                for="#">"</label>
                        <input type="text" class="field-month" maxlength="8">
                        <label for="#">20</label><input type="text" class="field-year" maxlength="2"><label
                                for="#">ГОДА</label>
                    </div>
                </div>
            </h1>
        </div>
        <div class="content">
            <ol>
                <li>
                    <p> Реквизиты организации ( название организации, её организационно-правовая<br>
                        форма, форма собственности, адрес, фамилия и инициалы руководителя организации,<br>
                        толефон, факс с указанием когда, адрес электронной почты): <input class="inp-one" type="text" maxlength="">
                        <input type="text" class="inp-two" maxlength=""></p>
                </li>
                <li>
                    <p>Состав комиссии технического расследования причин аварии Повреждения ГТС:<br></p>
                    <label for="#">Председатель : </label><input type="text" class="inp-three" maxlength="80">
                    <p><sup>(должность, фамилия, инициалы)</sup></p>
                    <label for="#">Члены комиссии : </label><input type="text" class="inp-four" maxlength="80">
                    <p><sup>(должность, фамилия, инициалы)</sup></p>
                </li>
                <li>
                    <p>Характеристика организации (объекта, участка) и места аварии, Повреждения ГТС.<br></p>
                    <p>В этом разделе наряду с данными о времени ввода объекта в эксплуатацию,
                        его местоположение необходимо указать регистрационный номер <*> объекта и дату его регистрации,
                        наличик договора страхования риска ответственности за причинение вреда при эусплуатации объекта,
                        проектные даныне и соответствие проект;
                        указать изменения проекта и из причины; дать заключение о состоянии объекта перед аварией,
                        повреждением ГТС;
                        режим работы объекта (оборудование) до аварии, повреждени ГТС(утвержденный, фактический,
                        проектный); указать, были ли ранее на данном участке
                        (объекте) аналогичные аварии, повреждения ГТС; отразит, как соблюдались лицензионные требования
                        и условия, замечания и рекомендации заключений экспертизы,
                        положения декларации промышленной безопасности ( при наличии).
                    </p>
                </li>
                <li>
                    <p>
                        Квалификация обслуживающего персонала, руководителей и специалистов объекта, ответственных лиц,
                        причастных к аварии, повреждению ГТС (где и когда проходил обучение,
                        инструктажи по промвшленной безопасности, проверку знаний в квалификационной комиссии)
                    </p>
                </li>
                <li>
                    <p>Обстоятельства аварии, повреждения ГТС, допущенные нарушения требовоний законодательства</p>
                    <p>Описываются обстоятельства аварии, поврежден ГТС исценарий их развития, информация о постродавших, указывается, какие факторы привели к
                        аварийной ситуации, её последствия (допущенные нарушения законодательства, установленных правил и требований к обеспечению безопасностии др.).
                        Описываются технологические процессы и процесс труда, действия обслуживающего персонала и должностных лиц. Излагается последовательность развития событий.</p>
                </li>

            </ol>
        </div>
    </div>
</div>
