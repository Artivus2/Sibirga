<?php
/* @var $this yii\web\View */

use frontend\assets\AppAsset;

$this->registerCssFile('css/accident.css', ['depends' => [AppAsset::className()]]);
$this->title = "Журнал регистрации случаев утраты взрывчатых материалов промышленного назначения";
?>
<div class="container">
    <div class="row">
        <div class="note">
            <div>
                <p>
                    <strong>Приложение N 8</strong>
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
            <h1>ЖУРНАЛ <br>
                регистрации случаев утраты взрывчатых материалов промышленного назначения
            </h1>
            <div class="caption-log">
                <input type="text">
                <p>(наименование территориального органа Федеральной службы по экологическому,
                    технологическому и атомному надзору)
                </p>
            </div>
        </div>
        <div class="table-content">
            <div class="table-header">
                <div class="table-column col-w-1"><span>N п/п</span>
                </div>
                <div class="table-column col-w-5"> <span>Наименование организации, организационно-правовая форма</span>
                </div>
                <div class="table-column col-w-5"> <span>Наименование объекта (места), где произошла утрата ВМ; дата происшествия и выявления утраты ВМ</span>
                </div>
                <div class="table-column col-w-5"><span>Характер утраты (хищение, разбрасывание, потеря), количество и наименование утраченных ВМ</span>
                </div>
                <div class="table-column col-w-5"><span>Краткое описание обстоятельств утраты ВМ, основные организационно-технические причины, приведшие к утрате; количество и наименование ВМ, возвращенных организации</span>
                </div>
                <div class="table-column col-w-5"><span>Дата направления материалов расследования в прокуратуру, в правоохранительные органы и результаты их рассмотрения</span>
                </div>
                <div class="table-column col-w-3"><span>Примечание</span>
                </div>
            </div>
            <div class="table-number">
                <div class="table-column-number col-w-1"><span>1</span></div>
                <div class="table-column-number col-w-5"><span>2</span></div>
                <div class="table-column-number col-w-5"><span>3</span></div>
                <div class="table-column-number col-w-5"><span>4</span></div>
                <div class="table-column-number col-w-5"><span>5</span></div>
                <div class="table-column-number col-w-5"><span>6</span></div>
                <div class="table-column-number col-w-3"><span>7</span></div>
            </div>
            <div class="table-row">
                <div class="table-column col-w-1"><span>1</span></div>
                <div class="table-column col-w-5"><span>2</span></div>
                <div class="table-column col-w-5"><span>3</span></div>
                <div class="table-column col-w-5"><span>4</span></div>
                <div class="table-column col-w-5"><span>5</span></div>
                <div class="table-column col-w-5"><span>6</span></div>
                <div class="table-column col-w-3"><span>7</span></div>
            </div>
        </div>


    </div>
</div>