<?php
/* @var $this yii\web\View */

use frontend\assets\AppAsset;
$this->title = "Бланки документов";
$this->registerCssFile('css/doc-blanks.css', ['depends' => [AppAsset::className()]]);
$this->registerJsFile('/js/doc-blanks.js', ['depends' => [AppAsset::className()]]);
?>

<div class="accident">
    <div class="accident-caption">
        <h1>Бланки документов</h1>
    </div>
    <div class="accident-nav">
        <div class="accident-nav-button">
            <button id="createButton">Создать</button>
            <div class="accident-nav-button-block">
                <div class="accident-list">
                    <a class="accident-list-list" href="#">Список 1</a>
                    <a class="accident-list-list" href="#">Список 2</a>
                    <a class="accident-list-list" href="#">Список 3</a>
                </div>
                <div class="accident-sublist">
                    <a href="#">Подсписок 1</a>
                    <a href="#">Подсписок 1</a>
                    <a href="#">Подсписок 1</a>
                    <a href="#">Подсписок 1</a>
                    <a href="#">Подсписок 1</a>
                </div>
                <div class="accident-sublist">
                    <a href="#">Подсписок 2</a>
                    <a href="#">Подсписок 2</a>
                    <a href="#">Подсписок 2</a>
                    <a href="#">Подсписок 2</a>
                </div>
                <div class="accident-sublist">
                    <a href="#">Подсписок 3</a>
                    <a href="#">Подсписок 3</a>
                    <a href="#">Подсписок 3</a>
                </div>
            </div>

        </div>
        <button>Распечатать</button>
        <button>Отправить</button>
        <button>Выгрузить</button>
    </div>
    <div class="accident-search">
        <div class="accident-search-input">
            <input type="text" placeholder="Наименование:">
            <div class="accident-search-input-butt">
                <button  class="open"><span class="caret"></span></button>
                <button class="shut"></button>
            </div>
        </div>
        <div class="accident-search-input">
            <input type="text" placeholder="Период:">
            <div class="accident-search-input-butt">
                <button  class="open"><span class="caret"></span></button>
                <button class="shut"></button>
            </div>
        </div>
        <div class="accident-search-input">
            <input type="text" placeholder="Состояние:">
            <div class="accident-search-input-butt">
                <button class="open"><span class="caret"></span></button>

                <button class="shut"></button>
            </div>
        </div>
    </div>

    <div class="accident-table">
        <div class="accident-table-header">
            <div class="accident-table-header-block col-w-8 ">Наименование</div>
            <div class="accident-table-header-block col-w-5 ">Период</div>
            <div class="accident-table-header-block col-w-5 ">Состояние</div>
            <div class="accident-table-header-block col-w-8 ">Организация</div>
        </div>
        <div class="accident-table-content">
            <div class="accident-table-content-line">
                <div class="accident-table-content-block col-w-8 "><img src="/img/fail-accident.png" alt="fail"><span>Наименование</span></div>
                <div class="accident-table-content-block col-w-5 ">01.01.2017-31.12.2017</div>
                <div class="accident-table-content-block col-w-5 "><span class="spanText">Отправлено</span><span class="spanText">Ростехнадзор</span></div>
                <div class="accident-table-content-block col-w-8 ">Шахта угольная "Заполярная-2"</div>
            </div>
            <div class="accident-table-content-line">
                <div class="accident-table-content-block col-w-8 "><img src="/img/fail-accident.png" alt="fail"><span>Наименование</span></div>
                <div class="accident-table-content-block col-w-5 ">01.01.2017-31.12.2017</div>
                <div class="accident-table-content-block col-w-5 "><span class="spanText">Отправлено</span><span class="spanText">Ростехнадзор</span></div>
                <div class="accident-table-content-block col-w-8 ">Шахта угольная "Заполярная-2"</div>
            </div>
            <div class="accident-table-content-line">
                <div class="accident-table-content-block col-w-8 "><img src="/img/fail-accident.png" alt="fail"><span>Наименование</span></div>
                <div class="accident-table-content-block col-w-5 ">01.01.2017-31.12.2017</div>
                <div class="accident-table-content-block col-w-5 "><span class="spanText">Отправлено</span><span class="spanText">Ростехнадзор</span></div>
                <div class="accident-table-content-block col-w-8 ">Шахта угольная "Заполярная-2"</div>
            </div>
            <div class="accident-table-content-line">
                <div class="accident-table-content-block col-w-8 "><img src="/img/fail-accident.png" alt="fail"><span>Наименование</span></div>
                <div class="accident-table-content-block col-w-5 ">01.01.2017-31.12.2017</div>
                <div class="accident-table-content-block col-w-5 "><span class="spanText">Отправлено</span><span class="spanText">Ростехнадзор</span></div>
                <div class="accident-table-content-block col-w-8 ">Шахта угольная "Заполярная-2"</div>
            </div>
            <div class="accident-table-content-line">
                <div class="accident-table-content-block col-w-8 "><img src="/img/fail-accident.png" alt="fail"><span>Наименование</span></div>
                <div class="accident-table-content-block col-w-5 ">01.01.2017-31.12.2017</div>
                <div class="accident-table-content-block col-w-5 "><span class="spanText">Отправлено</span><span class="spanText">Ростехнадзор</span></div>
                <div class="accident-table-content-block col-w-8 ">Шахта угольная "Заполярная-2"</div>
            </div>
            <div class="accident-table-content-line">
                <div class="accident-table-content-block col-w-8 "><img src="/img/fail-accident.png" alt="fail"><span>Наименование</span></div>
                <div class="accident-table-content-block col-w-5 ">01.01.2017-31.12.2017</div>
                <div class="accident-table-content-block col-w-5 "><span class="spanText">Отправлено</span><span class="spanText">Ростехнадзор</span></div>
                <div class="accident-table-content-block col-w-8 ">Шахта угольная "Заполярная-2"</div>
            </div>
            <div class="accident-table-content-line">
                <div class="accident-table-content-block col-w-8 "><img src="/img/fail-accident.png" alt="fail"><span>Наименование</span></div>
                <div class="accident-table-content-block col-w-5 ">01.01.2017-31.12.2017</div>
                <div class="accident-table-content-block col-w-5 "><span class="spanText">Отправлено</span><span class="spanText">Ростехнадзор</span></div>
                <div class="accident-table-content-block col-w-8 ">Шахта угольная "Заполярная-2"</div>
            </div>
            <div class="accident-table-content-line">
                <div class="accident-table-content-block col-w-8 "><img src="/img/fail-accident.png" alt="fail"><span>Наименование</span></div>
                <div class="accident-table-content-block col-w-5 ">01.01.2017-31.12.2017</div>
                <div class="accident-table-content-block col-w-5 "><span class="spanText">Отправлено</span><span class="spanText">Ростехнадзор</span></div>
                <div class="accident-table-content-block col-w-8 ">Шахта угольная "Заполярная-2"</div>
            </div>
            <div class="accident-table-content-line">
                <div class="accident-table-content-block col-w-8 "><img src="/img/fail-accident.png" alt="fail"><span>Наименование</span></div>
                <div class="accident-table-content-block col-w-5 ">01.01.2017-31.12.2017</div>
                <div class="accident-table-content-block col-w-5 "><span class="spanText">Отправлено</span><span class="spanText">Ростехнадзор</span></div>
                <div class="accident-table-content-block col-w-8 ">Шахта угольная "Заполярная-2"</div>
            </div>
        </div>
    </div>



</div>