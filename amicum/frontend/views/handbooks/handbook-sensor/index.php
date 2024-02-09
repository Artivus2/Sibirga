<?php
use yii\web\View;
use \frontend\assets\AppAsset;

$getSourceData = 'let model = '.json_encode($model).', typicalObjects =' .json_encode($typicalObjects). ', 
sensorsTypes =' .json_encode($sensorTypes). ', handbookConnectStrings = '. json_encode($connectStrings) .';';
$this->registerJs($getSourceData, View::POS_HEAD, 'sensor-js');
$this->registerCssFile('/css/basic-table.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/sensor.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
//$this->registerCssFile('/css/handbook-media.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/basic-header.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerCssFile('/css/header-form-handbook.css', ['depends' => [\frontend\assets\AppAsset::className()]]);
$this->registerJsFile('/js/sensor.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
//$this->registerJsFile('/js/anime.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [\frontend\assets\AppAsset::className()],'position' => View::POS_END]);
$this->title = 'Справочник датчиков, систем автоматизации';
?>
<div id="preload" class="hidden">
    <div class="circle-container">
        <div id="circle_preload"></div>
        <h4 class="preload-title">Идёт загрузка</h4>
    </div>
</div>

<!-- Модальное окно добавления ASMTP -->
<div id="addAsmtpModal" class="modal fade" tabindex="-1" role="dialog" >
    <!-- Контейнер для модального окна -->
    <div class="modal-dialog" role="document">
        <!-- Контейнер контента -->
        <div class="modal-content no-padding">
            <!-- Шапка модального окна -->
            <div class="modal-header">
                <!-- Внутренности шапки -->
                <div class="add-header-asmtp">
                    <!-- Иконка добавления -->
                    <span class="icon-add-current-btn"></span>
                    <!-- Текст шапки -->
                    <span>Добавление новой системы автоматизации</span>
                </div>
                <!-- Кнопка закрытия окна -->
                <button class="close close-x" data-dismiss="modal">&times;</button>
            </div>
            <!-- Тело модального окна -->
            <div class="modal-body add-asmtp-body">
                <!-- Текст модального окна -->
                <span class="add-asmtp-text">Введите название системы автоматизации</span></br>
                <!-- Поле ввода наименования ASMTP -->
                <input class="add-asmtp-input" id="addAsmtpInput" type="text" placeholder="Наименование...">
            </div>
            <!-- Футер модального окна -->
            <div class="modal-footer">
                <!-- Кнопка сохранения -->
                <button class="btn btn-primary btn-add-asmtp" id="addAsmtpButtonSave" type="button">Создать</button>
                <!-- Кнопка отмены -->
                <button class="btn btn-cancel-asmtp" id="modal-cancel" type="button" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно редактирования ASMTP -->
<div id="editAsmtpModal" class="modal fade" tabindex="-1" role="dialog" >
    <!-- Контейнер для модального окна -->
    <div class="modal-dialog" role="document">
        <!-- Контейнер контента -->
        <div class="modal-content no-padding">
            <!-- Шапка модального окна -->
            <div class="modal-header">
                <!-- Внутренности шапки -->
                <div class="add-header-asmtp">
                    <!-- Иконка добавления -->
                    <span class="icon-edit-btn"></span>
                    <!-- Текст шапки -->
                    <span>Редактирование системы автоматизации</span>
                </div>
                <!-- Кнопка закрытия окна -->
                <button class="close close-x" data-dismiss="modal">&times;</button>
            </div>
            <!-- Тело модального окна -->
            <div class="modal-body add-asmtp-body">
                <!-- Текст модального окна -->
                <span class="add-asmtp-text">Введите название системы автоматизации</span></br>
                <!-- Поле ввода наименования ASMTP -->
                <input class="add-asmtp-input" id="editAsmtpInput" type="text" placeholder="Наименование...">
            </div>
            <!-- Футер модального окна -->
            <div class="modal-footer">
                <!-- Кнопка сохранения -->
                <button class="btn btn-primary btn-add-asmtp" id="editAsmtpButtonSave" type="button">Изменить</button>
                <!-- Кнопка отмены -->
                <button class="btn btn-cancel-asmtp" id="modal-cancel" type="button" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>

<!--Модальная форма удаления режима работы-->
<div id="deleteAsmtpModal" class="modal fade" tabindex="-1" role="dialog" >
    <!-- Контейнер для модального окна -->
    <div class="modal-dialog" role="document">
        <!-- Контейнер контента -->
        <div class="modal-content no-padding">
            <!-- Шапка модального окна -->
            <div class="modal-header">
                <!-- Внутренности шапки -->
                <div class="add-header-asmtp">
                    <!-- Иконка добавления -->
                    <span class="icon-delete-btn"></span>
                    <!-- Текст шапки -->
                    <span>Удаление системы автоматизации</span>
                </div>
                <!-- Кнопка закрытия окна -->
                <button class="close close-x" data-dismiss="modal">&times;</button>
            </div>
            <!-- Тело модального окна -->
            <div class="modal-body delete-asmtp-body">
                <!-- Текст модального окна -->
                <span class="delete-asmtp-text">Вы действительно хотите удалить систему автоматизации:</span>
                <!-- Название системы автоматизации -->
                </br><span class="delete-asmtp-parse" id="deleteAsmtpParse"></span>
            </div>
            <!-- Футер модального окна -->
            <div class="modal-footer">
                <!-- Кнопка сохранения -->
                <button class="btn btn-primary btn-add-asmtp" id="deleteAsmtpButtonSave" type="button" data-dismiss="modal">Удалить</button>
                <!-- Кнопка отмены -->
                <button class="btn btn-cancel-asmtp" id="modal-cancel" type="button" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно добавления датчика -->
<div id="addSensorModal" class="modal fade" tabindex="-1" role="dialog" >
    <!-- Контейнер для модального окна -->
    <div class="modal-dialog" role="document">
        <!-- Контейнер контента -->
        <div class="modal-content no-padding">
            <!-- Шапка модального окна -->
            <div class="modal-header">
                <!-- Внутренности шапки -->
                <div class="add-header-asmtp">
                    <!-- Иконка добавления -->
                    <span class="icon-add-current-btn"></span>
                    <!-- Текст шапки -->
                    <span>Добавление нового датчика</span>
                </div>
                <!-- Кнопка закрытия окна -->
                <button class="close close-x" data-dismiss="modal">&times;</button>
            </div>
            <!-- Тело модального окна -->
            <div class="modal-body add-asmtp-body">
                <!-- Текст модального окна -->
                <span class="add-asmtp-text">Введите название датчика</span></br>
                <!-- Поле ввода наименования датчика -->
                <input class="add-asmtp-input" id="addSensorInput" type="text" placeholder="Наименование...">
                <!-- Заголовок селекта -->
                <span class="select-title">Тип датчика</span>
                <!-- Селект для типа датчика -->
                <div class="select" id="sensorTypeSelect">
                    <!-- Текущий тип датчика -->
                    <span class="dropdown-textcontent"></span>
                    <!-- Значок треугольника -->
                    <span class="caret"></span>
                </div>
                <!-- Заголовок селекта -->
                <span class="select-title">Типовой объект</span>
                <!-- Селект для класса объекта -->
                <div class="select" id="sensorObjectClassSelect">
                    <!-- Текущий тип датчика -->
                    <span class="dropdown-textcontent"></span>
                    <!-- Значок треугольника -->
                    <span class="caret"></span>
                </div>
            </div>
            <!-- Футер модального окна -->
            <div class="modal-footer">
                <!-- Кнопка сохранения -->
                <button class="btn btn-primary btn-add-asmtp" id="addSensorButtonSave" type="button" data-dismiss="modal">Создать</button>
                <!-- Кнопка отмены -->
                <button class="btn btn-cancel-asmtp" id="modal-cancel" type="button" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
    <div class="dropdown-main-container" id="dropdown">
        <div class="dropdown-search-container">
            <input class="handbook-header__input" placeholder="Введите поисковой запрос" id="dropdownInput">
            <button class="clear-search-button" id="clear-dropdown"></button>
            <button class="handbook-header__search-button" id="search-dropdown"></button>
        </div>
        <div class="dropdown-items-container" id="drop-container">
        </div>
    </div>
</div>

<!-- Модальное окно добавления датчика с выбором ASMTP -->
<div id="addSensorModalWithAsmtp" class="modal fade" tabindex="-1" role="dialog" >
    <!-- Контейнер для модального окна -->
    <div class="modal-dialog" role="document">
        <!-- Контейнер контента -->
        <div class="modal-content no-padding">
            <!-- Шапка модального окна -->
            <div class="modal-header">
                <!-- Внутренности шапки -->
                <div class="add-header-asmtp">
                    <!-- Иконка добавления -->
                    <span class="icon-add-current-btn"></span>
                    <!-- Текст шапки -->
                    <span>Добавление нового датчика</span>
                </div>
                <!-- Кнопка закрытия окна -->
                <button class="close close-x" data-dismiss="modal">&times;</button>
            </div>
            <!-- Тело модального окна -->
            <div class="modal-body add-asmtp-body">
                <!-- Заголовок селекта -->
                <span class="select-title">Система автоматизации</span>
                <!-- Селект для класса объекта -->
                <div class="select" id="sensorAsmtpSelectWithAsmtp">
                    <!-- Текущий тип датчика -->
                    <span class="dropdown-textcontent"></span>
                    <!-- Значок треугольника -->
                    <span class="caret"></span>
                </div>
                <!-- Текст модального окна -->
                <span class="add-asmtp-text">Введите название датчика</span></br>
                <!-- Поле ввода наименования датчика -->
                <input class="add-asmtp-input" id="addSensorInputWithAsmtp" type="text" placeholder="Наименование...">
                <!-- Заголовок селекта -->
                <span class="select-title">Тип датчика</span>
                <!-- Селект для типа датчика -->
                <div class="select" id="sensorTypeSelectWithAsmtp">
                    <!-- Текущий тип датчика -->
                    <span class="dropdown-textcontent"></span>
                    <!-- Значок треугольника -->
                    <span class="caret"></span>
                </div>
                <!-- Заголовок селекта -->
                <span class="select-title">Типовой объект</span>
                <!-- Селект для класса объекта -->
                <div class="select" id="sensorObjectClassSelectWithAsmtp">
                    <!-- Текущий тип датчика -->
                    <span class="dropdown-textcontent"></span>
                    <!-- Значок треугольника -->
                    <span class="caret"></span>
                </div>
            </div>
            <!-- Футер модального окна -->
            <div class="modal-footer">
                <!-- Кнопка сохранения -->
                <button class="btn btn-primary btn-add-asmtp" id="addSensorButtonSaveWithAsmtp" type="button" data-dismiss="modal">Создать</button>
                <!-- Кнопка отмены -->
                <button class="btn btn-cancel-asmtp" id="modal-cancel" type="button" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
    <div class="dropdown-main-container" id="dropdownAsmtp">
        <div class="dropdown-search-container">
            <input class="handbook-header__input" placeholder="Введите поисковой запрос" id="dropdownInputAsmtp">
            <button class="clear-search-button" id="clear-dropdownAsmtp"></button>
            <button class="handbook-header__search-button" id="search-dropdownAsmtp"></button>
        </div>
        <div class="dropdown-items-container" id="drop-containerAsmtp">
        </div>
    </div>
</div>

<!-- Модальное окно редактирования датчика -->
<div id="editSensorModal" class="modal fade" tabindex="-1" role="dialog" >
    <!-- Контейнер для модального окна -->
    <div class="modal-dialog" role="document">
        <!-- Контейнер контента -->
        <div class="modal-content no-padding">
            <!-- Шапка модального окна -->
            <div class="modal-header">
                <!-- Внутренности шапки -->
                <div class="add-header-asmtp">
                    <!-- Иконка добавления -->
                    <span class="icon-edit-btn"></span>
                    <!-- Текст шапки -->
                    <span>Редактирование датчика</span>
                </div>
                <!-- Кнопка закрытия окна -->
                <button class="close close-x" data-dismiss="modal">&times;</button>
            </div>
            <!-- Тело модального окна -->
            <div class="modal-body add-asmtp-body">
                <!-- Текст модального окна -->
                <span class="add-asmtp-text">Введите название датчика</span></br>
                <!-- Поле ввода наименования датчика -->
                <input class="add-asmtp-input" id="editSensorInput" type="text" placeholder="Наименование...">
                <!-- Заголовок селекта -->
                <span class="select-title">Тип датчика</span>
                <!-- Селект для типа датчика -->
                <div class="select" id="sensorTypeSelect2">
                    <!-- Текущий тип датчика -->
                    <span class="dropdown-textcontent"></span>
                    <!-- Значок треугольника -->
                    <span class="caret"></span>
                </div>
                <!-- Заголовок селекта -->
                <span class="select-title">Типовой объект</span>
                <!-- Селект для класса объекта -->
                <div class="select" id="sensorObjectClassSelect2">
                    <!-- Текущий тип датчика -->
                    <span class="dropdown-textcontent"></span>
                    <!-- Значок треугольника -->
                    <span class="caret"></span>
                </div>
            </div>
            <!-- Футер модального окна -->
            <div class="modal-footer">
                <!-- Кнопка сохранения -->
                <button class="btn btn-primary btn-add-asmtp" id="editSensorButtonSave" type="button" data-dismiss="">Изменить</button>
                <!-- Кнопка отмены -->
                <button class="btn btn-cancel-asmtp" id="modal-cancel" type="button" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
    <div class="dropdown-main-container" id="dropdown2">
        <div class="dropdown-search-container">
            <input class="handbook-header__input" placeholder="Введите поисковой запрос" id="dropdownInput2">
            <button class="clear-search-button" id="clear-dropdown2"></button>
            <button class="handbook-header__search-button" id="search-dropdown2"></button>
        </div>
        <div class="dropdown-items-container" id="drop-container2">
        </div>
    </div>
</div>

<!--Модальная форма удаления датчика-->
<div id="deleteSensorModal" class="modal fade" tabindex="-1" role="dialog" >
    <!-- Контейнер для модального окна -->
    <div class="modal-dialog" role="document">
        <!-- Контейнер контента -->
        <div class="modal-content no-padding">
            <!-- Шапка модального окна -->
            <div class="modal-header">
                <!-- Внутренности шапки -->
                <div class="add-header-asmtp">
                    <!-- Иконка добавления -->
                    <span class="icon-delete-btn"></span>
                    <!-- Текст шапки -->
                    <span>Удаление датчика</span>
                </div>
                <!-- Кнопка закрытия окна -->
                <button class="close close-x" data-dismiss="modal">&times;</button>
            </div>
            <!-- Тело модального окна -->
            <div class="modal-body delete-sensor-body">
                <!-- Текст модального окна -->
                <span class="delete-asmtp-text">Вы действительно хотите удалить датчик:</span>
                <!-- Название датчика -->
                <span class="delete-sensor-parse" id="deleteSensorParse"></span>
            </div>
            <!-- Футер модального окна -->
            <div class="modal-footer">
                <!-- Кнопка сохранения -->
                <button class="btn btn-primary btn-add-asmtp" id="deleteSensorButtonSave" type="button" data-dismiss="modal">Удалить</button>
                <!-- Кнопка отмены -->
                <button class="btn btn-cancel-asmtp" id="modal-cancel" type="button" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>

<!--Модальная форма параметров-->
<div id="parameterSensorModal" class="modal fade" tabindex="-1" role="dialog" >
    <!-- Контейнер для модального окна -->
    <div class="modal-dialog" role="document">
        <!-- Контейнер контента -->
        <div class="modal-content no-padding">
            <!-- Шапка модального окна -->
            <div class="modal-header-parameters">
                <!-- Внутренности шапки -->
                <!-- Иконка параметров -->
                <span class="glyphicon glyphicon-cog"></span>
                <!-- Текст шапки -->
                <span>Параметры датчика</span>
                <!-- Кнопка закрытия окна -->
                <button class="close-params" data-dismiss="modal">&times;</button>
            </div>
            <!-- Тело модального окна -->
            <div class="modal-body parameters-sensor-body">
                <!-- Контейнер бутстрапа с заголовками -->
                <div class="col-xs-12 bootstrap-headers">
                    <div class="col-xs-3">Параметр</div>
                    <div class="col-xs-3">Тип параметра</div>
                    <div class="col-xs-1" style="padding-top: 10px;">Уставочное значение</div>
                    <div class="col-xs-1">Критерий границы уставки</div>
                    <div class="col-xs-2" style="width: 21%;">Событие</div>
                    <div class="col-xs-1" style="width: 4%;"></div>
                    <div class="col-xs-1" style="padding-top: 10px;">Дата установки</div>
                </div>
                <!-- Контейнер бутстрапа с содержимым -->
                <div class="col-xs-12 bootstrap-content" id="bootstrapContent"></div>
            </div>
            <!-- Футер модального окна -->
            <div class="modal-footer">
                <span id="parseParameterHere"></span>
                <button class="addFrame" id="newParameterButton">Добавить параметр
                </button>
                <!-- Кнопка сохранения -->
                <button class="btn btn-primary btn-add-asmtp" id="parameterButtonSave" type="button" data-dismiss="modal">Сохранить</button>
                <!-- Кнопка отмены -->
                <button class="btn btn-cancel-asmtp" id="modal-cancel" type="button" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно строки подключения -->
<div id="connectStringModal" class="modal fade" tabindex="-1" role="dialog" >
    <!-- Контейнер для модального окна -->
    <div class="modal-dialog" role="document">
        <!-- Контейнер контента -->
        <div class="modal-content no-padding">
            <!-- Шапка модального окна -->
            <div class="modal-header">
                <!-- Внутренности шапки -->
                <div class="add-header-asmtp">
                    <!-- Иконка добавления -->
                    <span class="glyphicon glyphicon-transfer" style="left: 170px;"></span>
                    <!-- Текст шапки -->
                    <span style="left: 210px;">Выбор строки подключения</span>
                </div>
                <!-- Кнопка закрытия окна -->
                <button class="close close-x" data-dismiss="modal">&times;</button>
            </div>
            <!-- Тело модального окна -->
            <div class="modal-body add-asmtp-body" style="margin-top: 10px;">
                <div id="sensorName" style="margin-bottom: 5px;">Название датчика</div>
                <!-- Заголовок селекта -->
                <span class="select-title">Строка подключения</span>
                <div class="select-clear-container" style="display: flex; width: 100%;">
                    <!-- Селект для типа датчика -->
                    <div class="select" id="connectStringSelect" style="width: 95%; display: block;">
                        <!-- Текущий тип датчика -->
                        <span class="noslide dropdown-textcontent"></span>
                        <!-- Значок треугольника -->
                        <span class="caret noslide" style="position: absolute; left: 530px; top: 75px;"></span>
                    </div>
                    <!-- Кнопка удаления значения селекта-->
                    <div class="clear_string_btn" id="removeStringSelect" style="width: 30px; position: absolute; right: 10px; top: 54px;" title="Отвязать строку подключения">×</div>
                </div>
            </div>
            <!-- Футер модального окна -->
            <div class="modal-footer">
                <!-- Кнопка сохранения -->
                <button class="btn btn-primary btn-add-asmtp" id="connectStringSave" type="button" data-dismiss="modal">Сохранить</button>
                <!-- Кнопка отмены -->
                <button class="btn btn-cancel-asmtp" id="modal-cancel" type="button" data-dismiss="modal">Отмена</button>
            </div>
        </div>
    </div>
    <div class="dropdown-main-container" id="dropdown3">
        <div class="dropdown-search-container">
            <input class="handbook-header__input" placeholder="Введите поисковой запрос" id="dropdownInput3">
            <button class="clear-search-button" id="clear-dropdown3"></button>
            <button class="handbook-header__search-button" id="search-dropdown3"></button>
        </div>
        <div class="dropdown-items-container" id="drop-container3"></div>
    </div>
</div>

<!-- Внутренняя шапка в справочнике датчиков -->
<header class="header">
    <!-- Кнопка добавления системы автоматизации-->
    <!--    <button class="addButton" id="addAsmtpButton" title="Добавить систему автоматизации">-->
    <!-- Иконка добавления -->
    <div class="handbook-header__add-item " id="addAsmtpButton">
        <div class="button-add-left">
            <div class="glyphicon glyphicon-plus"></div>
            <div class="button-add-right"></div>
        </div>
        <div class="handbook-header__search-container"><span>Добавить</span></div>
    </div>
    <!--        <div class="button-add-left">-->
    <!--            <div class="glyphicon glyphicon-plus"></div>-->
    <!--            <div class="button-add-right"></div>-->
    <!--        </div>-->
    <!--        <span class="icon-add-current-btn"></span>-->
    <!--         Надпись-->
    <!--        Добавить-->
    <!--    </button>-->
    <!-- Кнопка синхронизации -->
    <button class="syncButton" title="Синхронизировать данные"></button>
    <!-- Кнопка экспорта -->
    <button class="exportButton" title="Экспортировать данные в Excel"></button>
    <!-- Кнопка импорта -->
    <button class="importButton" title="Загрузить готовые данные"></button>
    <!-- Строка поиска -->
    <div class="search">
        <!-- Поле для ввода запроса -->
        <input id="search" type="search" placeholder="Введите поисковой запрос">
        <!-- Кнопка для очистки поиска -->
        <button id="clear" class="clearSearchButton"></button>
        <!-- Кнопка поиска(нужна только для декорации, потому что поиск живой) -->
        <button class="searchButton" id="searchButton"></button>
    </div>
</header>

<!-- Основное окно -->
<div class="col-xs-12 main-body">
    <!-- Левая часть со списком систем автоматизации -->
    <div class="col-xs-2 asmtp-menu">
        <!-- Заголовок левой части -->
        <div class="asmtp-header">Системы автоматизации</div>
        <!-- Контейнер с контентом -->
        <div class="asmtp-content">
            <!-- Список систем автоматизации -->
            <ul class="asmtp-content-list" id="asmtpList">
                <!-- Пункты списка -->
            </ul>
        </div>
    </div>
    <!-- Правая часть со списком датчиков -->
    <div class="col-xs-10 main-table" id="scrTp">
        <!-- Строка заголовка -->
        <div class="table-head" id="tableHead">
            <!-- Первый заголовок -->
            <div class="col-title-1 sort-field table-header" id="sensor_title">
                <span>Датчик</span>
                <span class="glyphicon"></span>
            </div>
            <!-- Второй заголовок -->
            <div class="col-title-2 sort-field table-header" id="sensor_type_title">
                <span>Тип датчика</span>
                <span class="glyphicon"></span>
            </div>
            <!-- Третий заголовок -->
            <div class="col-title-3 sort-field table-header" id="object_title">
                <span>Типовой объект</span>
                <span class="glyphicon"></span>
            </div>
            <!-- Четвертый заголовок -->
            <div class="col-title-4 sort-field table-header" id="connection_string_title">
                <span>Строка подключения</span>
                <span class="glyphicon"></span>
            </div>
            <div class="col-title-5 sort-field table-header" id="tag_title">
                <span>Название тега</span>
                <span class="glyphicon"></span>
            </div>
        </div>
        <!-- Основная таблица справочника -->
        <div class="main-sensor-table" id="mainSensorTable">
        </div>
    </div>
</div>


<div class="footRow">
    <div class="handbook-content__footer__pagination">
    </div>
</div>

<!-- Всплывающее меню -->
<div id="blockActionMenu" class="container-of-action">
    <!-- Контейнер с кнопками -->
    <div class="buttons">
        <!-- Кнопка добавления датчика -->
        <button id="addSensorBAM"><span class="icon-add-current-btn"></span> Добавить датчик</button>
        <!-- Строка подключения -->
        <button id="connectStringBAM"><span class="glyphicon glyphicon-transfer"></span></button>
        <!-- Кнопка параметров датчика -->
        <!--        <button id="parameterBAM"><span class="glyphicon glyphicon-cog"></span></button>-->
        <!-- Кнопка редактирования системы автоматизации -->
        <button id="editBAM"><span class="icon-edit-btn"></span></button>
        <!-- Кнопка удаления системы автоматизации -->
        <button id="deleteBAM"><span class="icon-delete-btn"></span></button>
    </div>
    <!-- Треугольник снизу -->
    <div class="bottom-triangle">
    </div>
</div>


<!-- Плашка селекта с поиском -->
<div class="select-container" id="selectContainer">
    <!-- Контейнер поиска -->
    <div class="select-search-container">
        <input class="select-search" id="selectSearch" type="search" placeholder="Введите запрос...">
        <button id="clearSelectSearch" class="clearSearchButton"></button>
        <button class="searchButton"></button>
    </div>
    <!-- Контейнер списка -->
    <div class="select-list-container" id="parseListHere">
    </div>
</div>

<!-- Плашка селекта без поиска -->
<div class="select-container-no-search" id="selectContainerNoSearch">
    <!-- Контейнер списка -->
    <div class="select-list-container-no-search" id="parseListNoSearchHere">
        <ul>
            <li class="listElementNoSearch" id="listElemsNoSrch1" data-original-id="1"><</li>
            <li class="listElementNoSearch" id="listElemsNoSrch2" data-original-id="2">></li>
            <li class="listElementNoSearch" id="listElemsNoSrch3" data-original-id="3">=</li>
            <li class="listElementNoSearch" id="listElemsNoSrch4" data-original-id="4"><=</li>
            <li class="listElementNoSearch" id="listElemsNoSrch5" data-original-id="5">>=</li>
        </ul>
    </div>
</div>
<!-- Выпадающий список-->

