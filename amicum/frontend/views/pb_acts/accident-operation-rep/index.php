<?php
/* @var $this yii\web\View */

use frontend\assets\AppAsset;

$this->registerCssFile('css/operational-report-about-accident.css', ['depends' => [AppAsset::className()]]);
$this->registerJsFile('/js/operational-report-about-accident.js', ['depends' => [AppAsset::className()]]);
$this->title = "ОПЕРАТИВНОЕ СООБЩЕНИЕ (ИНФОРМАЦИЯ) ОБ АВАРИИ,
                ИНЦИДЕНТЕ, СЛУЧАЕ УТРАТЫ ВЗРЫВЧАТЫХ МАТЕРИАЛОВ
                ПРОМЫШЛЕННОГО НАЗНАЧЕНИЯ";
?>

<div class="blockText" style="display: inline-block; opacity: 0;"> </div>

<div class="container">
    <div class="row">
        <div class="note">
            <div>
                <p>
                    <strong>Приложение N 1</strong>
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
            <h1>ОПЕРАТИВНОЕ СООБЩЕНИЕ (ИНФОРМАЦИЯ) ОБ АВАРИИ,
                ИНЦИДЕНТЕ, СЛУЧАЕ УТРАТЫ ВЗРЫВЧАТЫХ МАТЕРИАЛОВ
                ПРОМЫШЛЕННОГО НАЗНАЧЕНИЯ
            </h1>
        </div>

        <div class="message">
            <div class="message-view">
                <p class="message-view-text"><span> Вид аварии </span> (необходимую информацию отметить знаком <span class="checktext">x</span> )</p>
                <p> <span id="checkBoxOne" class="checkbox"></span> <span>- неконтролируемый взрыв</span></p>
                <p> <span id="checkBoxTwo" class="checkbox"></span> <span>- выброс опасных веществ</span></p>
                <p> <span id="checkBoxThree" class="checkbox"></span> <span>- разрушение сооружений</span></p>
                <p> <span id="checkBoxFour" class="checkbox"></span> <span>- повреждение, разрушение технических</span></p>
                <p> <span id="checkBoxFave" class="checkbox"></span> <span>- нарушение режима работы</span></p>
                <p> <span id="checkBoxSix" class="checkbox"></span> <span>- повреждение ГТС</span></p>
                <p> <span id="checkBoxSeven" class="checkbox"></span> <span>- утрата взрывчатых материалов промышленного
                    назначения</span></p>
                <p> <span class="checkbox"></span> <span>- другие виды аварии</span></p>

            </div>
<!--            <div style="text-align: center;">-->
<!--                <button id="send">Нажми на меня!</button>-->
<!--            </div>-->

            <div class="message-information">
                <div id="injured" class="labelFlex containerInput">
                    <label for="#"><span>Наличие пострадавших &lt;*&gt;</span></label>
                    <input type="text" class="inputText">
                    <input type="text" class="inputFull inputText">
                    <input type="text" class="inputFull inputText">
                </div>

                <div id="date" class="labelFlex containerInput">
                    <label for="#"><span>Дата и время (московское) аварии, повреждения ГТС, утраты взрывчатых
                        материалов промышленного назначения  </span> <input type="text" class="width695 inputText"> </label>
                </div>

                <div id="economicEducation" class="labelFlex containerInput">
                    <label for="#">Хозяйственное образование (хозяйствующий субъект), вертикально-интегрированная структура</label><input type="text" class="inputText">
                    <input type="text" class="inputFull inputText">
                </div>

                <div id="territorialBody" class="labelFlex containerInput">
                    <label for="#">Территориальный орган, вид надзора</label><input type="text" class="inputText">
                </div>

                <div  id="organization" class="labelFlex containerInput">
                    <label for="#">Организация</label><input type="text" class="inputText">
                </div>

                <div id="location" class="labelFlex containerInput">
                    <label for="#">Место нахождения организации (субъект Российской Федерации, город, поселок
                        и т.п.)</label>
                    <input type="text" class="inputText">
                    <input type="text" class="inputFull inputText">
                    <input type="text" class="inputFull inputText">
                </div>

                <div id="crashSite" class="labelFlex containerInput">
                    <label for="#">Место аварии, повреждения ГТС, утраты взрывчатых материалов промышленного
                        назначения (производство, участок, цех, координаты по трассе с привязкой к
                        ближайшему населенному пункту и т.п.)<input type="text" class="width165 inputText"></label>

                    <input type="text" class="inputFull inputText">
                    <input type="text" class="inputFull inputText">
                    <input type="text" class="inputFull inputText">
                </div>

                <div id="registrationNumber" class="labelFlex containerInput">
                    <label for="#">Регистрационный номер объекта &lt;**&gt;</label>
                    <input type="text" class="inputText">
                    <input type="text" class="inputFull inputText">
                </div>

                <div id="circumstancesAccident" class="labelFlex containerInput">
                    <label for="#">Обстоятельства аварии, повреждения ГТС, утраты взрывчатых материалов
                        промышленного назначения и последствия (в т.ч. травмирование) <input type="text" class="width530 inputText"></label>

                    <input type="text" class="inputFull inputText">
                    <input type="text" class="inputFull inputText">
                    <input type="text" class="inputFull inputText">
                    <input type="text" class="inputFull inputText">
                </div>
                <div id="accidentResponseOrganizations" class="labelFlex containerInput">
                    <label for="#">Организации, принимающие участие в ликвидации последствий аварии,
                        повреждения ГТС, утраты взрывчатых материалов промышленного назначения <input type="text" class="width498 inputText"></label>
                    <input type="text" class="inputFull inputText">
                    <input type="text" class="inputFull inputText">
                </div>

                <div>
                    <p>Передал(а): фамилия, инициалы, должность лица, имеющего право внешней
                        переписки, телефон,</p>
                    <label for="#" class="labelLeft">подпись</label> <input id="signatureOne" type="text" class="width200" disabled>

                    <p>Принял(а): фамилия, инициалы, должность,</p>
                    <label for="#" class="labelLeft">подпись</label> <input id="signatureTwo" type="text" class="width200" disabled>
                </div>

                <div id="DateReceipt" class="labelFlex containerInput">
                    <label for="#">Дата и время (московское) приема</label>
                    <input type="text">
                </div>

                <div id="delay" class="labelFlex containerInput">
                    <label for="#">Причина задержки передачи информации в установленный срок (указать при
                        задержке более 24 часов)</label>
                    <input type="text">
                    <input type="text" class="inputFull">
                </div>
                <div class="dotted"> </div>
                <div class="explanation">
                    <p>&lt;*&gt; Указать количество пострадавших, из них погибших. В этом случае к
                        оперативному сообщению об аварии прикладывается оперативное сообщение
                        (информация) о несчастном случае (тяжелом, групповом, со смертельным
                        исходом) по рекомендованному образцу (приложение N 2).</p>
                    <p>&lt;**&gt; Для опасных производственных объектов указывается регистрационный
                        номер опасного производственного объекта в Государственном реестре опасных
                        производственных объектов, для гидротехнических сооружений -
                        регистрационный номер в Российском регистре гидротехнических сооружений.</p>
                </div>

            </div>
        </div>

        <div class="caption">
            <h1>ИНФОРМАЦИЯ ОБ АВАРИЯХ НА ОБЪЕКТЕ ТРУБОПРОВОДА ХИМИЧЕСКИ ОПАСНЫХ,
                ВЗРЫВООПАСНЫХ И ГОРЮЧИХ ЖИДКОСТЕЙ И ГАЗОВ &lt;*&gt;
            </h1>
        </div>

        <div class="message">
            <ol>
                <li>
                    <label for="#">Наименование объекта, координаты по трассе с привязкой  к  ближайшему
                        населенному пункту</label>
                    <input type="text">
                    <input type="text" class="inputFull ">
                </li>
                <li>
                    <label for="#">Регистрационный номер объекта</label>
                    <input type="text">
                    <input type="text" class="inputFull">
                </li>
                <li>
                    <label for="#"> Наименование вещества</label>
                    <input type="text">
                    <input type="text" class="inputFull">
                </li>
                <li>
                    <label for="#">Объем утечки, м3</label>
                    <input type="text">
                </li>
                <li>
                    Информация по трубопроводу:
                    <ol>
                        <li>
                            <label for="#">Диаметр, мм</label>
                            <input type="text">
                        </li>
                        <li>
                            <label for="#">Толщина стенки, мм</label>
                            <input type="text">
                        </li>
                        <li>
                            <label for="#">Марка стали</label>
                            <input type="text">
                        </li>
                        <li>
                            <label for="#">Год ввода в эксплуатацию</label>
                            <input type="text">
                        </li>
                        <li>
                            <label for="#">Максимально разрешенное рабочее давление, МПа</label>
                            <input type="text">
                        </li>
                        <li>
                            <label for="#">Давление в момент аварии, МПа</label>
                            <input type="text">
                        </li>
                    </ol>
                </li>
                <li>
                    <label for="#">Характер аварии</label>
                    <input type="text">
                </li>
                <li>
                    <label for="#">Продолжительность истечения до ликвидации аварии, ч</label>
                    <input type="text">
                </li>
                <li>
                    Если утечка не устранена, то указать:
                    <ol>
                        <li>
                            <label for="#">Ожидаемый объем утечки до ее устранения, м3</label>
                            <input type="text">
                        </li>
                        <li>
                            <label for="#">Время до устранения утечки, ч</label>
                            <input type="text">
                        </li>
                    </ol>
                </li>
                <li>
                    <label for="">Характеристика места утечки (указать бетон/твердые покрытия;  гравий/
                        песок; пастбище и т.д.)</label>
                    <input type="text">
                    <input type="text">
                </li>
                <li>
                    <label for=""> После утечки (указать последствия):</label>
                    <input type="text">
                    <ol>
                        <li>
                            <label for="#">Попадание в водоток</label>
                            <input type="text">
                        </li>
                        <li>
                            <label for="#">Впитывание в грунт</label>
                            <input type="text">
                        </li>
                        <li>
                            <label for="#">Попадание в водоносный горизонт</label>
                            <input type="text">
                            <input type="text" class="inputFull">
                        </li>
                    </ol>
                </li>
                <li>
                    <label for="#">Удалось ли полностью убрать загрязнения, вызванные утечкой</label>
                    <input type="text">
                    <input type="text" class="inputFull">
                </li>
                <li>
                    <label for="#">Предпринятые или предпринимаемые меры по ликвидации загрязнений:</label>
                    <input type="text">
                    <input type="text" class="inputFull">
                    <ol>
                        <li>
                            <label for="#">Метод очистки</label>
                            <input type="text">
                        </li>
                        <li>
                            <label for="#">Дата окончания очистки</label>
                            <input type="text">
                        </li>
                        <li>
                            <label for="#">Привлекаемый подрядчик (указать)</label>
                            <input type="text">
                        </li>
                        <li>
                            <label for="#">Применяемые методы хранения собранной жидкости</label>
                            <input type="text">
                        </li>
                    </ol>
                </li>
                <li>
                    <label for="#">Погодные условия</label>
                    <input type="text">
                </li>
                <li>
                    <label for="#">Метод и обстоятельства обнаружения утечки</label>
                    <input type="text">
                </li>
                <li>
                    <label for="#">Ближайший водоем</label>
                    <input type="text">
                </li>
                <li>
                    <label for="#">Расстояние до водоема, км</label>
                    <input type="text">
                </li>
                <li>
                    <label for="#">Перерыв в работе (дата, время)</label>
                    <input type="text">
                </li>
                <li>
                    <label for="#">Воздействие на потребителя</label>
                    <input type="text">
                </li>
                <li>
                    <label for="#">Описание последствий, возможная причина</label>
                    <input type="text">
                </li>
                <li>
                    <label for="#">Вид ремонта  <input type="text" class="width150"> Начало  <input type="text" class="width150"> Окончание  </label><input type="text">
                </li>
                <li>
                    <label for="#">Координаты лица, сообщившего об аварии</label>
                    <input type="text">
                </li>
            </ol>
            <div class="dotted"> </div>
            <div class="explanation">
                <p><strong>&lt;*&gt; Заполняется в случае аварии на объекте трубопровода химически опасных,
                        взрывоопасных и горючих жидкостей и газов.
                        При необходимости приложить к форме дополнительные листы.</strong>
                </p>
            </div>
        </div>
    </div>
</div>