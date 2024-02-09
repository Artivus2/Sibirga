<?php
/* @var $this yii\web\View */

use frontend\assets\AppAsset;
$this->title = 'СООБЩЕНИЕ О СТРАХОВОМ СЛУЧАЕ ';
$this->registerCssFile('/css/insured-event-rep.css', ['depends' => [AppAsset::className()]]);
?>

<div class="container">
    <div class="row">
        <div class="note">
            <div>
                <p>
                    <strong>Приложение N 1</strong><br>
                    К приказу Фонда <br>
                    Социального страхования <br>
                    Российской Федерации <br>
                    от 24.08.2000 № 157
                </p>
            </div>
        </div>
        <div class="caption">
            <h1>СООБЩЕНИЕ О СТРАХОВОМ СЛУЧАЕ</h1><br>
            <p>( о несчастном случае на производстве, групповом несчастном случае,<br>
                тяжелом несчастном случае, несчастном случае со смертельным исходом, о<br>
                впервые выявленном профзаболевании)</p>
        </div>
        <div class="content">
            <ol>
                <li>
                    <input type="text">
                    <p><sup>(Наименование организации, её адрес, телеФон(факс), ОКНХ и регистрационный № в исполнительном органе Фонда )</sup></p>
                    <input type="text">
                    <p><sup> форма собственности, вид производства</sup></p>
                    <input type="text">
                    <p><sup>ведомственная подчиненность (при её наличии)</sup></p>
                </li>
                <li>
                    <input type="text">
                    <p><sup>(дата, время(местное). место происшествия </sup></p>
                    <input type="text">
                    <p><sup>выполняемая работа и краткое описание обстоятельств,</sup></p>
                    <input type="text">
                    <p><sup>при которых произошел несчатсный случай(профзаболевание))</sup></p>
                    <input type="text">
                    <p class="beffo"></p>
                </li>
                <li>
                    <input type="text">
                    <p><sup>(число пострадавших, в том числе погибших(при групповом случае)) </sup></p>
                </li>
                <li>
                    <input type="text">
                    <p><sup>(фамилия, имя, отчество, возвраст, профессия(должность) </sup></p>
                    <input type="text">
                    <p><sup>пострадавшего (пострадавших), в том числе</sup></p>
                    <input type="text">
                    <p><sup>погибшего (погибших))</sup></p>
                </li>
                <li>
                    <input type="text">
                    <p><sup>(Вид трудовых отношений (трудовой договор (котракт), гражданско-правовой договор)</sup></p>
                </li>
                <li>
                    <label for="">Лицо, передавшее сообщение</label><input type="text">
                    <input type="text" class="row-six mini">
                    <p><sup>(фамилия, имя, отчество. должность)</sup></p>
                </li>
            </ol>
            <p>
                Сообщение направляется в течении суток исполнительному органу Фонда по месту регистрации страхователя в соответствии с п.п. 6 п. 2 ст. 17 Федерального
                закона от 24.07.1998 № 125-ФЗ "Об обязательном социальном страховании от несчастных случаев на производстве и профзаболеваний".
            </p>
        </div>
    </div>
</div>