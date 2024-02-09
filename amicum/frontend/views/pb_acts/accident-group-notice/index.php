<?php
/* @var $this yii\web\View */

use frontend\assets\AppAsset;
$this->title = "Извещение о групповом несчастном случае (тяжелом несчастном случае, несчастном случае со смертельным исходом)";
$this->registerCssFile('css/group-accident-announcement.css', ['depends' => [AppAsset::className()]]);
?>

<div class="container">
    <div class="row">
        <div class="note">
            <div>
                <p>Форма 1</p>
                <p>(в ред. Приказа Минтруда России от 20.02.2014 № 103н) </p>
            </div>
        </div>

        <div class="caption">
            <h1>ИЗВЕЩЕНИЕ</h1>
            <p>о групповом несчастном случае (тяжелом несчастном случае, несчастном случае со смертельным исходом)*</p>
        </div>
        <div class="block_one">
            <p>1. <input class="widthBlock"></p>
            <p class="center_p"><sup>(наименование организации, ее ведомственная и отраслевая принадлежность/код основного вида</sup></p>
            <input class="widthFull">
            <p class="center_p"><sup>экономической деятельности по ОКВЭД/, место нахождения и юридический адрес; фамилия и инициалы</sup></p>
            <input class="widthFull">
            <p class="center_p"><sup>работодателя - физического лица, его регистрационные данные, вид производства, адрес,</sup></p>
            <input class="widthFull">
            <p class="center_p"><sup>телефон, факс)</sup></p>
        </div>
        <div class="block_two">
            <p>2. <input type="text" class="widthBlock"></p>
            <p class="center_p"><sup>(дата и время/местное/несчастного случая, выполнявшаяся работа**, краткое описание места</sup></p>
            <input type="text" class="widthFull">
            <p class="center_p"><sup>происшествия и обстоятельств, при которых произошел несчастный случай)</sup></p>
            <input type="text" class="widthFull">
            <input type="text" class="widthFull">
            <input type="text" class="widthFull">
        </div>
        <div class="block_three">
            <p>3. <input type="text" class="widthBlock"></p>
            <p class="center_p"><sup>(число пострадавших, в том числе погибших)</sup></p>
        </div>
        <div class="block_three">
            <p>4. <input type="text" class="widthBlock"></p>
            <p class="center_p"><sup>(фамилия, инициалы и профессиональный статус** пострадавшего/пострадавших/, профессия</sup></p>
            <input type="text" class="widthFull">
            <p class="center_p"><sup>/должность/**, возраст - при групповых несчастных случаях указывается для каждого</sup></p>
            <input type="text" class="widthFull">
            <p class="center_p"><sup>пострадавшего отдельно)</sup></p>
            <input type="text" class="widthFull">
            <input type="text" class="widthFull">
        </div>
        <div class="block_three">
            <p>5. <input type="text" class="widthBlock"></p>
            <p class="center_p"><sup>(характер** и тяжесть повреждений здоровья, полученных пострадавшим/пострадавшими/)</sup></p>
            <input type="text" class="widthFull">
            <p class="center_p"><sup>- при групповых несчастных случаях указывается для каждого пострадавшего отдельно)</sup></p>
            <input type="text" class="widthFull">
            <input type="text" class="widthFull">
        </div>
        <div class="block_six">
            <p>6. <input type="text" class="widthBlock"></p>
            <p class="center_p"><sup>(фамилия, инициалы лица, передавшего извещение, дата и время получения извещения)</sup></p>
        </div>
        <div class="block_seven">
            <p>7. <input type="text" class="widthBlock"></p>
            <p class="center_p"><sup>(фамилия, инициалы лица, принявшего извещение, дата и время получения извещения)</sup></p>
        </div>

        <div class="annotation">
            <label>_________________________________</label>
            <p>* Передается в течение суток после происшествия несчастного сулчая в органы и организации, указанные в
                статье 228 Трудового кодекса Российской Федерации, по телефону, факсом, телеграфом и другими имеющимися
                средствами связи.
            </p>
            <p>** При передаче извещения отмеченные сведения указываются и кодируются в соответствии с установленной
                классификацией.
            </p>
        </div>
    </div>
</div>