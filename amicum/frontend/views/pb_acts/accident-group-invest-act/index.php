<?php
/* @var $this yii\web\View */

use frontend\assets\AppAsset;
use yii\web\View;
//$workersInfo = "let model = ".json_encode($model).";";
//$this->registerJs($workersInfo, View::POS_HEAD, 'accident-group-invest-act.js');
$this->registerCssFile('/css/pickmeup.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('css/accident-group-invest-act.css', ['depends' => [AppAsset::className()]]);

$this->registerJsFile('/js/pickmeup.min.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/anime.min.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
//$this->registerJsFile('/js/accident-group-invest-act.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
$this->title = "Акт о расследовании группового несчастного случая (тяжелого несчастного случая, несчастного случая со смертельным исходом)";
?>

<!-- код для выпадающего списка полей ФИО -->
<!--<div class="selector" id="select">-->
<!--    <span class="textIn">Нажмите для выбора</span>-->
<!--</div>-->
<!---->
<!--<div class="select-container" id="selectContainer">-->
<!--    <!--  контейнер для поиска  -->
<!--    <div class="select-search-container">-->
<!--        <input class="select-search" id="selectSearch" type="search" placeholder="Введите запрос...">-->
<!--        <button id="clearSelectSearch" class="clearSearchButton"></button>-->
<!--        <button class="searchButton"></button>-->
<!--    </div>-->
<!--    <!--  end  -->
<!--    <!--  контейнер со списком  -->
<!--    <div class="select-list-container" id="list-container">-->
<!--        <p class="textIn">Список фамилии для выбора</p>-->
<!--    </div>-->
<!--</div>-->
<!---->


<datalist id="type_investigation">
    <option value="группового"></option>
    <option value="тяжелого"></option>
    <option value="со смертельным исходом"></option>
</datalist>

<datalist id="month">
    <option value="Января"></option>
    <option value="Февраля"></option>
    <option value="Марта"></option>
    <option value="Апреля"></option>
    <option value="Мая"></option>
    <option value="Июня"></option>
    <option value="Июля"></option>
    <option value="Августа"></option>
    <option value="Сентября"></option>
    <option value="Октября"></option>
    <option value="Ноября"></option>
    <option value="Декабря"></option>
</datalist>

<datalist id="briefing">
    <option value="первичный"></option>
    <option value="повторный"></option>
    <option value="внеплановый"></option>
    <option value="целевой"></option>
</datalist>

<div class="container" xmlns="http://www.w3.org/1999/html">
    <div class="row">
        <div class="note">
            <p>Форма 4</p>
            <p><small>(в ред. Приказа Минтруда России от 20.02.2014 № 103н)</small></p>
        </div>

        <div class="caption">
            <h1><strong>АКТ</strong>
                <p><strong>О РАССЛЕДОВАНИИ ГРУППОВОГО НЕСЧАСТНОГО СЛУЧАЯ (ТЯЖЕЛОГО НЕСЧАСТНОГО СЛУЧАЯ, НЕСЧАСТНОГО СЛУЧАЯ СО СМЕРТЕЛЬНЫМ ИСХОДОМ)</strong></p>
            </h1>
        </div>

        <div class="investigation_form">
            <p>Расследование <input class="width530 type_invest" list="type_investigation"> несчастного случая,</p>
            <p class="center_p sub_p"><sup>(группового, тяжелого, со смертельным исходом)</sup></p>
            <p>происшедшего  "<input class="width20" maxlength="2">" <input list="month" class="width150 center_p" maxlength="8"> 20 <input class="width20" maxlength="2">г. в
                <input class="width40" maxlength="2"> час. <input class="width40" maxlength="2"> мин.</p>
            <input class="inputFull"> <p class="center_p"><sup>наименование, место нахождения, юридический адрес организации, отраслевая принадлежность</sup></p>
            <input class="inputFull"><p class="center_p"><sup>/код сновного вида экономической деятельности по ОКВЭД/,наименование вышестоящего федерального органа</sup></p>
            <input class="inputFull"> <p class="center_p"><sup>иcполнительной власти; фамилия, инициалы работодателя - физического лица</sup></p>
            <p>проведено в период с "<input class="width20" maxlength="2">" <input list="month" class="width150 center_p" maxlength="8"> 20 <input class="width20" maxlength="2"> г.  по "<input class="width20" maxlength="2">" <input list="month" class="width150 center_p" maxlength="8">20 <input class="width20" maxlength="2">г.</p>
            <p>Лица, проводившие расследование несчастного случая: </p>
            <input class="inputFull">
            <p class="center_p"><sup>(фамилии, инициалы, должности, место работы</sup></p>
            <input class="inputFull">
            <input class="inputFull">
            <input class="inputFull">
            <p>Лица, принимавшие участие в расследовании несчастного случая: </p>
            <input class="inputFull">
            <p class="center_p"><sup>(фамилия, инициалы доверенного лица пострадавшего (пострадавших); фамилии, инициалы,</sup></p>
            <input class="inputFull">
            <p class="center_p"><sup>должности и место работы других лиц, принимавших участие в расследовании несчастного случая)</sup></p>
        </div>

        <div class="block_one">
            <p>1. Сведения о пострадавшем (пострадавших): </p>
            <p>фамилия, имя, отчество <input class="width630"></p>
            <p>пол (мужской, женский) <input class="width630"></p>
            <p>Дата рождения: <input class="width650"></p>
            <p>профессиональный статус: <input class="width610"></p>
            <p>профессия (должность): <input class="width630"></p>
            <p>стаж работы, при выполнении которой произошел несчастный случай<input class="width350"></p>
            <p class="right_p"><sup>(число полных лет и месяцев)</sup></p>
            <p>в том числе в данной организации <input class="width550"></p>
            <p class="semi-right_p"><sup>(число полных лет и месяцев)</sup></p>
            <p>семейное положение <input class="width640"></p>
            <p class="center_p"><sup>(состав семьи, фамилии, инициалы, возраст членов семьи, находящихся на</sup></p>
            <input class="inputFull">
            <p class="center_p"><sup>на иждивении пострадавшего)</sup></p>
            <input class="inputFull breakline">

        </div>

        <div class="block_two">
            <p>2. Сведения о проведении инструктажей и обучения по охране труда</p>
            <p>Вводный инструктаж: <input class="width630"></p>
            <p class="semi-right_p"><sup>(число, месяц, год)</sup></p>
            <p>Инструктаж на рабочем месте <input list="briefing" class="width410 center_p"> по профессии или</p>
            <p>виду работы, при выполнении которой произошел несчастный случай <input class="width350">
            <p class="right_p"><sup>(число, месяц, год)</sup></p>
            <p>Стажировка: с "<input class="width20" maxlength="2">"<input list="month" class="center_p" maxlength="8"> 20 <input class="width20" maxlength="2"> г. по "<input class="width20" maxlength="2">" <input list="month" class="center_p" maxlength="8"> 20 <input class="width20" maxlength="2"> г. </p>
            <input class="inputFull">
            <p class="center_p"><sup>(если не проводилась - указать)</sup></p>
            <p>Обучение по охране труда по профессии или виду работы, при выполнении которой произошел</p>
            <p>несчастный случай: с "<input class="width20" maxlength="2">" <input list="month" class="center_p" maxlength="8"> 20 <input class="width20" maxlength="2"> г. по "<input class="width20" maxlength="2">" <input list="month" class="center_p" maxlength="8"> 20 <input class="width20" maxlength="2">г.</p>
            <input class="inputFull">
            <p class="center_p"><sup>(если не проводилась - указать)</sup></p>
            <p>Проверка знаний по охране труда по профессии или виду работы, при выполнении которой</p>
            <p>произошел несчастный случай <input class="width530"></p>
            <p class="semi-right_p"><sup>(число, месяц, год, № протокола)</sup></p>
        </div>
        <div class="block_three">
            <p>3. Краткая характеристика места (объекта), где произошел несчастный случай<input class="width280"></p>
            <input class="inputFull">
            <p class="center_p"><sup>(краткое описание места происшествия с указанием опасных и (или) вредных производственных)</sup></p>
            <input class="inputFull">
            <p class="center_p"><sup>факторов со ссылкой на сведения, содержащиеся в протоколе осмотра места насчастного случая)</sup></p>
            <input class="inputFull">
            <p>Оборудование, использование которого привело к несчастному случаю <input class="width320"></p>
            <input class="inputFull">
            <p class="center_p"><sup>(Наименование, тип, марка, год выпуска, организация-изготовитель)</sup></p>
            <p>3.1 Сведения о проведении специальной оценки условии труда (аттестация рабочих мест по условиям труда) с указанием индивидульного  номера рабочего места и класса (подкласса) условии труда <input class="width280"></p>
            <p>3.2 Сведения об организации, проводившей специальную оценку условий труда (аттестацию рабочих мест по условиям труда)(наименование, ИНН) <input class="width610"></p>
            <input class="inputFull breakline">
        </div>
        <div class="block_four">
            <p>4. Обстоятельства несчастного случая</p>
            <input class="inputFull">
            <p class="center_p"><sup>описание обстоятельств, предшествовавших несчастному случаю, последовательное</sup></p>
            <input class="inputFull">
            <p class="center_p"><sup>изложение событий и действий пострадавшего (пострадавших) и других лиц, связанных с</sup></p>
            <input class="inputFull">
            <p class="center_p"><sup>несчастным случаем, характер и степень тяжести полученных пострадавшим (пострадавшими)</sup></p>
            <input class="inputFull">
            <p class="center_p"><sup>повреждений с указанием поврежденных мест, объективные данные об алкогольном или ином</sup></p>
            <input class="inputFull">
            <p class="center_p"><sup>опьянении пострадавшего (пострадавших) и другие сведения, установленные в ходе расследования)</sup></p>
            <input class="inputFull">
            <input class="inputFull">
            <input class="inputFull breakline">
        </div>
        <div class="block_five">
            <p>5. Причины, вызвавшие несчастный случай <input class="width498"></p>
            <p class="right_p"><sup>указать основную и сопутствующие причины</sup></p>
            <input class="inputFull">
            <p class="center_p"><sup>несчастного случая со ссылками на нарушенные требования законодательных и иных</sup></p>
            <input class="inputFull">
            <p class="center_p"><sup>нормативных и правовых актов, локальных нормативных актов)</sup></p>
            <input class="inputFull">
            <input class="inputFull">
            <input class="inputFull breakline">
        </div>
        <div class="block_six">
            <p>6. Заключение о лицах, ответственных за допущенные нарушения законодательных и иных нормативных правовых и локальных нормативных актов, явившихся причинами несчастного случая:</sup></p>
            <input class="inputFull">
            <p class="center_p"><sup>(фамилии, инициалы, должности (профессии) лиц с указанием требований законодательных,</sup></p>
            <input class="inputFull">
            <p class="center_p"><sup>иных нормативных правовых и локальных нормативных актов, предусматривающих их</sup></p>
            <input class="inputFull">
            <p class="center_p"><sup>ответственность за нарушения, явившиеся причинами нсчастного случая, указанными в п.5</sup></p>
            <input class="inputFull">
            <p class="center_p"><sup>настоящего акта; при установлении факта грубой неосторожности пострадавшего</sup></p>
            <input class="inputFull">
            <p class="center_p"><sup>(пострадавших) указать степень его (их) вины в процентах)</sup></p>
            <input class="inputFull breakline">
        </div>
        <div class="block_seven">
            <p>7. Квалификация и учет несчастного случая</p>
            <input class="inputFull">
            <p class="center_p"><sup>(излагается решение лиц, проводивших расследование несчастного сулчая, о квалификации</sup></p>
            <input class="inputFull">
            <p class="center_p"><sup>несчастного случая со ссылками на соответствующие статьи Трудового кодекса Российской</sup></p>
            <input class="inputFull">
            <p class="center_p"><sup>Федерации и пункты Положения об особенностях расследования несчастных случаев на</sup></p>
            <input class="inputFull">
            <p class="center_p"><sup>производстве в отдельных отраслях и организациях, утвержденного постановлением</sup></p>
            <input class="inputFull">
            <p class="center_p"><sup>Минтруда России от 24 октября 2002 г. №73, и указывается наименование организации</sup></p>
            <input class="inputFull">
            <p class="center_p"><sup>(фамилия, инициалы работодателя - физического лица), где подлежит учету и регистрации</sup></p>
            <input class="inputFull">
            <p class="center_p"><sup>несчастный случай)</sup></p>
            <input class="inputFull breakline">
        </div>
        <div class="block_eight">
            <p>8. Мероприятия по устранению причин несчастного случая, сроки</p>
            <input class="inputFull">
            <p class="center_p"><sup>(указать содержание мероприятий и сроки их выполнения)</sup></p>
            <input class="inputFull">
            <input class="inputFull">
            <input class="inputFull">
        </div>
        <div class="block_nine">
            <p>9. Прилагаемые документы и материалы расследования:</p>
            <input class="inputFull">
            <p class="center_p"><sup>(перечислить прилагаемые к акту документы и материалы расследования)</sup></p>
            <input class="inputFull">
            <input class="inputFull">
            <input class="inputFull breakline">
        </div>
        <div class="block_sign">
            <p>Подписи лиц, проводивших</p>
            <p>расследование несчастного случая <input class="width200 paddings"> <input class="width200 paddings"></p>
            <p><sup class="sign_left paddings">(подписи)</sup><sup class="sign_right">(фамилии, инициалы)</sup></p>
            <div class="sign">
                <p><input class="width200 paddings"> <input class="width200 paddings"></p>
                <p> <input class="width200 paddings"> <input class="width200 paddings"></p>
                <p> <input class="width200 paddings"> <input class="width200 paddings"></p>
                <p> <input class="width200 paddings"> <input class="width200 paddings"></p>
            </div>
            <p><input class="width200" maxlength="14"></p>
            <p class="left_p"><sup>(дата)</sup></p>
        </div>
    </div>
</div>

