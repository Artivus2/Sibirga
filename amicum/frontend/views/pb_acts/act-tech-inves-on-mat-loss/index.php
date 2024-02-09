<?php
/* @var $this yii\web\View */

use frontend\assets\AppAsset;

$this->registerCssFile('css/operational-report-about-accident.css', ['depends' => [AppAsset::className()]]);
$this->title = "АКТ ТЕХНИЧЕСКОГО РАССЛЕДОВАНИЯ СЛУЧАЯ УТРАТЫ ВЗРЫВЧАТЫХ
                МАТЕРИАЛОВ ПРОМЫШЛЕННОГО НАЗНАЧЕНИЯ";
?>
<div class="container">
    <div class="row">
        <div class="note">
            <div>
                <p>
                    <strong>Приложение N 7</strong>
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
            <h1>АКТ <br>
                ТЕХНИЧЕСКОГО РАССЛЕДОВАНИЯ СЛУЧАЯ УТРАТЫ ВЗРЫВЧАТЫХ
                МАТЕРИАЛОВ ПРОМЫШЛЕННОГО НАЗНАЧЕНИЯ, ПРОИСШЕДШЕГО
            </h1>
            <label for=""><input type="text">20<input type="text" class="width20">Г.</label>
        </div>

        <div class="message explosive-loss">
            <ul>
                <li>
                    <label for="#">1. Наименование организации, организационно-правовая форма, юридический
                        адрес</label>
                    <input type="text">
                    <input type="text" class="inputFull">
                </li>
                <li>
                    <div class="explosive-loss-block">
                        <p class="explosive-loss-text">2. Состав комиссии:</p>
                    </div>
                    <div class="explosive-loss-block">
                        <label for="#">Председатель </label><input type="text">
                    </div>
                    <div class="explosive-loss-block">
                        <p class="explosive-loss-textOne">(должность, фамилия, имя, отчество)</p>
                    </div>
                    <div class="explosive-loss-block">
                        <label for="#">Члены комиссии: </label><input type="text">
                        <input type="text" class="inputFull">
                    </div>
                    <div class="explosive-loss-block">
                        <p class="explosive-loss-textTwo">(должность, фамилия, имя, отчество)</p>
                    </div>

                </li>

                <li>
                    <label for="#">3.  Краткая  характеристика  объекта  и  места,  где  произошла  утрата
                        взрывчатых материалов промышленного назначения <input type="text" class="width695"></label>
                    <input type="text" class="inputFull">
                    <input type="text" class="inputFull">
                </li>
                <li>
                    <label for="#">4. Количество  и  наименование  взрывчатых  материалов  промышленного
                        назначения,  которые  были  похищены,  разбросаны  или потеряны (при утрате
                        изделия  со  взрывчатыми  веществами указывается количество  содержащихся в
                        нем взрывчатых веществ <input type="text" class="width485"></label>
                    <input type="text" class="inputFull">
                    <input type="text" class="inputFull">
                </li>

                <li>
                    <label for="#">5. Обстоятельства  утраты  (при  описании  обстоятельств  указываются
                        предпосылки   утраты   в   сложившейся   производственной  обстановке,  кем
                        обнаружена   утрата   взрывчатых   материалов   промышленного   назначения,
                        описывается  вид  маркировки изделий, в необходимых случаях делаются ссылки
                        на прилагаемые к акту схемы, планы, фотографии и другие документы) <input type="text" class="width498"></label>
                    <input type="text" class="inputFull">
                </li>
                <li>
                    <label for="#">6. Количество  и  наименование  взрывчатых  материалов  промышленного
                        назначения, обнаруженных (найденных) и возвращенных организации <input type="text" class="width485"></label>
                </li>

                <li>
                    <label for="">7. Организационные  и  технические  причины  утраты ВМ (указать, какие
                        недостатки  и недоработки организационно-технического характера имели место
                        на  предприятии, а также какие требования правил и инструкций были нарушены
                        и  привели  к  утрате  ВМ  с указанием соответствующих параграфов и пунктов
                        нормативных документов) <input type="text" class="width595"></label>
                    <input type="text" class="inputFull">
                </li>
                <li>
                    <label for="">8. Мероприятия   по  предупреждению  подобных  случаев,  предложенные
                        комиссией  по  результатам  технического  расследования, с указанием сроков
                        выполнения<input type="text" class="width350"></label>
                    <input type="text" class="inputFull">
                </li>
                <li>
                    <label for="#">9. Заключение  комиссии  о  лицах,  ответственных за утрату взрывчатых
                        материалов  промышленного  назначения,  и  предложенные  меры по применению
                        административных мер воздействия <input type="text" class="width170"></label>
                    <input type="text" class="inputFull">
                </li>
                <li>
                    <label for="#">10. Сроки   проведения  технического  расследования;  обстоятельства,
                        препятствовавшие проведению расследования в установленные сроки; выявленные
                        при   расследовании   недостатки   в   организации   хранения,   перевозки,
                        использования  и  учета  взрывчатых материалов промышленного назначения, не
                        относящиеся  к  прямым  причинам утраты взрывчатых материалов промышленного
                        назначения, и мероприятия по их устранению</label>
                    <input type="text">
                    <input type="text" class="inputFull">
                    <input type="text" class="inputFull">
                </li>
            </ul>

            <div class="explosive-loss-footer">
                <div class="labelFlex">
                    <div class="explosive-loss-block">
                        <label for="#">Расследование проведено и акт составлен</label><input type="text">
                    </div>
                    <div class="explosive-loss-block">
                        <p class="explosive-loss-textThree">(число, месяц, год)</p>
                    </div>

                </div>
                <div class="labelFlex">
                    <label for="#">Приложение: материалы расследования на <input type="text"> листах.</label>
                </div>
                <div class="col-xs-3">
                    <p>Подписи</p>
                </div>
                <div class="col-xs-9 labelFlex">
                    <div class="explosive-loss-block">
                        <label for="#">Председатель:</label><input type="text">
                    </div>
                    <div class="explosive-loss-block">
                        <p class="explosive-loss-textFour">(фамилия, инициалы, дата)</p>
                    </div>
                    <div class="explosive-loss-block">
                        <label for="#">Члены комиссии:</label><input type="text">
                    </div>
                    <div class="explosive-loss-block">
                        <p class="explosive-loss-textFour">(фамилия, инициалы, дата)</p>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>