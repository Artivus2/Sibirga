<?php
/* @var $this yii\web\View */

use frontend\assets\AppAsset;

$this->registerCssFile('css/accident-polling-rep.css', ['depends' => [AppAsset::className()]]);
$this->title = "Протоокол опроса пострадавшего при несчастном случае (очевидца несчастного случая, должностного лица)";
?>
<div class="container">
    <div class="header">
        <div class="note">
            <p>Форма 6</p>
        </div>
        <div class="caption">
            <h1>ПРОТОКОЛ</h1>
            <p>опроса пострадавшего при несчастном случае (очевидца несчастного случая, должностного лица)</p>
        </div>
        <div class="caption-input">
            <div class="input-left">
                <input class="width300">
                <p class="semi-left"><sup>(место составления протокола)</sup></p>
            </div>
            <div class="input-right">
                <p>"<input class="width20">" <input class="width100"> 20 <input class="width20"> г.</p>
            </div>
        </div>
    </div>
    <div class="right-block-polling">
        <p>Опрос начат в <input class="width40"> час. <input class="width40"> мин.</p>
        <p>Опрос окончен в <input class="width40"> час. <input class="width40"> мин.</p>
    </div>
    <div class="content">
        <p class="indent">Мною, председателем /членом/ комиссии по расследованию несчастного случая, образованной приказом</p>
        <input class="widthFull">
        <p class="center_p"><sup>(фамилия, инициалы работодателя -физического лица либо наименование</sup></p>
        <p><input class="width60p"> от  "<input class="width20">" <input class="width100"> 20 <input class="width20">г. № <input class="width40">,</p>
        <p class="semi-left"><sup>организации)</sup></p>
        <input class="widthFull">
        <p class="center_p"><sup>(должность, фамилия, инициалы председателя комиссии/члена комиссии/, производившего опрос)</sup></p>
        <p>в помещении <input class="width70p"> произведен опрос</p>
        <p class="center_p"><sup>(указать место проведения опроса)</sup></p>
        <p>пострадавшего (очевидца несчастного случая на производстве, должностного лица организации):</p>
        <input type="text" class="widthFull">
        <p class="center_p"><sup>(нужное подчеркнуть)</sup></p>
        <!-- profile -->
        <p>1) фамилия, имя, отчество <input type="text" class="width586"></p>
        <p>2) дата рождения <input type="text" class="width641"></p>
        <p>3) место рождения <input type="text" class="width632"></p>
        <p>4) место жительства и (или) регистрации <input type="text" class="width501"></p>
        <p>телефон <input type="text" class="width697"></p>
        <p>5) гражданство <input type="text" class="width654"></p>
        <p>6) образование <input type="text" class="width650"></p>
        <p>7) семейное положение, состав семьи <input type="text" class="width512"></p>
        <p>8) место работы или учебы <input type="text" class="width582"></p>
        <p>9) профессия, должность <input type="text" class="width596"></p>
        <p>10) иные данные о личности опрашиваемого <input type="text" class="width471"></p>
        <input class="widthFull">
        <input class="width60p right-input">
        <p class="right_p"><sup>(подпись, фамилия, инициалы опрашиваемого)</sup></p>
        <!-- Опрос других людей -->
        <p>Иные лица, участвовавшие в опросе <input class="width60p"></p>
        <p class="right_p"><sup>(процессуальное положение, фамилия, инициалы лиц, участвовавших в опросе:)</sup></p>
        <input class="widthFull">
        <p class="center_p"><sup>другие члены комиссии по расследованию несчастного случая, доверенное лицо пострадавшего, адвокат и др.)</sup></p>
        <input class="widthFull">
        <input class="widthFull">
        <p>Участвующим в опросе лицам объявлено о применении технических средств <input class="width278"></p>
        <p class="right_p"><sup>(каких именно,</sup></p>
        <input class="widthFull">
        <p class="center_p"><sup>кем именно)</sup></p>
        <p class="indent">По существу несчастного случая, происшедшего "<input class="width20">" <input class="width200"> 20 <input class="width20"> г. </p>
        <p><input type="text" class="width570">, могу показать следующее:</p>
        <p class="semi-left"><sup>(фамилия, инициалы, профессия, должность пострадавшего)</sup></p>
        <input class="widthFull">
        <p class="center_p"><sup>(излагаются показания опрашиваемого, а также поставленные перед ним вопросы и ответы на них)</sup></p>
        <!-- ручной текст -->
        <input class="widthFull">
        <input class="widthFull">
        <input class="widthFull">
        <input class="widthFull">
        <input class="widthFull">
        <input class="widthFull">
        <input class="widthFull">
        <input class="widthFull">
        <input class="widthFull">
        <input class="widthFull">

        <input class="widthFull">
        <input class="widthFull">
        <input class="widthFull">
        <input class="widthFull">
        <input class="widthFull">
        <input class="widthFull">
        <input class="widthFull">
        <input class="widthFull">
        <input class="widthFull">
        <input class="widthFull">

        <input class="width60p right-input">
        <p class="right_p"><sup>(подпись, фамилия, инициалы опрашиваемого, дата)</sup></p>
        <!-- окончание опроса -->
        <p>Перед началом, в ходе либо по окончании опроса от участвующих в опросе лиц <input class="width263"></p>
        <input class="widthFull">
        <p class="center_p"><sup>(их процессуальное положение , фамилии, инициалы)</sup></p>
        <p>заявления <input class="width263">. Содержание заявлений: <input type="text" class="width263"></p>
        <p class="left_15p"><sup>(поступили, не поступили)</sup></p>
        <input class="widthFull">
        <input class="widthFull">
        <input class="widthFull">
        <input class="widthFull">
        <input type="text" class="width60p right-input">
        <p class="right_p"><sup>(подпись, фамилия, инициалы опрашиваемого, дата)</sup></p>
        <input type="text" class="width60p right-input">
        <p class="right_p"><sup>(подписи, фамилии, инициалы иных лиц, участвовавших в опросе, дата)</sup></p>
        <input type="text" class="width60p right-input">
        <p>С настоящим протоколом ознакомлен <input class="width516"></p>
        <p class="semi-right_p"><sup>(подпись, фамилия, инициалы опрашиваемого, дата)</sup></p>
        <p>Протокол прочитан вслух <input class="width594"></p>
        <p class="semi-right_p"><sup>(подпись, фамилия, инициалы лица, проводившего опрос, дата)</sup></p>
        <p>Замечания к протоколу <input type="text" class="width605"></p>
        <p class="semi-right_p"><sup>(содержание замечаний либо указание на их отсутствие)</sup></p>

        <input class="widthFull">
        <input class="widthFull">
        <p><input class="widthFull"></p>

        <p>Протокол составлен <input class="width626"></p>
        <p class="semi-left_p"><sup>(должность, фамилия, инициалы предстедателя комиссии или иного лица, проводившего опрос, подпись, дата)</sup></p>
    </div>
</div>