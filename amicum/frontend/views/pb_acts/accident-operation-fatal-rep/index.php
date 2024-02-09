<?php
/* @var $this yii\web\View */

use frontend\assets\AppAsset;

$this->registerCssFile('css/operational-report-about-accident-damage.css', ['depends' => [AppAsset::className()]]);
$this->title = "ОПЕРАТИВНОЕ СООБЩЕНИЕ (ИНФОРМАЦИЯ) О НЕСЧАСТНОМ СЛУЧАЕ, 
                (ТЯЖЕЛОМ, ГРУППОВОМ, СО СМЕРТЕЛЬНЫМ ИСХОДОМ), ПРОИЗОШЕДШЕМ В РЕЗУЛЬТАТЕ АВАРИИ, 
                ИНЦИДЕНТА, УТРАТЫ ВЗРЫВЧАТЫХ МАТЕРИАЛОВ ПРОМЫШЛЕННОГО НАЗНАЧЕНИЯ";
?>

<div class="container">
    <div class="row">
        <div class="note">
            <div>
                <p>
                    <strong>Приложение N 2</strong>
                    к Порядку проведения технического расследования причин аварий,
                    инцидентов и случаев утраты взрывчатых материалов промышленного
                    назначения на объектах, поднадзорных Федеральной службе по экологическому,
                    технологическому и атомному надзору, утвержденному Приказом Федеральной
                    службы по экологическому, технологическому и атомному надзору
                    от 19 августа 2011 г. N 480
                </p>
                <p>
                    (рекомендуемый образец)
                </p>
            </div>
        </div>
    </div>

    <div class="caption">
        <h1>ОПЕРАТИВНОЕ СООБЩЕНИЕ (ИНФОРМАЦИЯ) О НЕСЧАСТНОМ СЛУЧАЕ,
            (ТЯЖЕЛОМ, ГРУППОВОМ, СО СМЕРТЕЛЬНЫМ ИСХОДОМ),
            ПРОИЗОШЕДШЕМ В РЕЗУЛЬТАТЕ АВАРИИ, ИНЦИДЕНТА,
            УТРАТЫ ВЗРЫВЧАТЫХ МАТЕРИАЛОВ
            ПРОМЫШЛЕННОГО НАЗНАЧЕНИЯ
        </h1>
    </div>

    <div class="message">
        <div class="message-view">
            <p class="message-view-text"><span> Вид несчастного случая </span> (необходимую информацию отметить знаком <span class="checktext">x</span> )</p>
            <p> <input class="checkbox" type="checkbox" id="lethal"> <label for="lethal">- со смертельным исходом</label></p>
            <p> <input class="checkbox" type="checkbox" id="group"> <label for="group">- групповой несчастный случай</label></p>
            <p> <input class="checkbox" type="checkbox" id="heavy"> <label for="heavy">- тяжелый несчастный случай</label></p>
        </div>
    </div>

    <div class="form">
        <form class="information">

            <p>Дата и время (московское) несчастного случая <input class="width50p"></p>
            <p>Хозяйственное образование (хозяйствующий субъект),</p>
            <p>вертикально-интегрированная структура</label><input class="width50p"></p>
            <input class="widthFull">

            <p>Территориальный орган, вид надзора, курирующий его отдел <input class="width50p" type="text"></p>
            <input class="widthFull">

            <p>Организация<input class="width80p" type="text"> </p>
            <input class="widthFull">

            <p>Место нахождения организации (субъект Российской Федерации, город, поселок и т.п.)<input class="width20p"></p>
            <input class="widthFull">
            <input class="widthFull">

            <p>Место происшествия (производство, участок, цех, координаты по трассе с привязкой к ближайшему населенному пункту и т.п.)<input class="width90p"></p>

            <input class="widthFull">
            <input class="widthFull">

            <p>Обстоятельства, при которых произошел несчастный случай<input class="width50p"></p>
            <input class="widthFull">
            <input class="widthFull">

            <p>Сведения о пострадавших (фамилия, инициалы, должность, возраст) <*><input class="width40p"></p>
            <input class="widthFull">

            <p>Характер и тяжесть повреждения здоровья, полученных пострадавшими<input class="width40p"></p>
            <input class="widthFull">
        </form>

        <form class="transfer_out">
            <p>Передал(а): фамилия, инициалы, должность лица, имеющего право внешней переписки, телефон,</p>
            <p class="sign_field">подпись<input class="width20p" type="text"></p>
        </form>

        <form class="transfer_in">
            <p>Принял(а): фамилия, инициалы, должность,</p>
            <p class="sign_field">подпись<input class="width20p" type="text"></p>
        </form>

        <form class="date_stamp">
            <p>Дата и время (московское) приема <input class="width70p" type="text"></p>
        </form>

        <form class="date_exp">
            <p>Причина задержки передачи информации в установленный срок (указать при задержке более 24 часов)</p>
            <input class="widthFull" type="text">
            <input class="widthFull" type="text">
        </form>
    </div>




</div>