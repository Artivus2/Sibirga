<?php
/* @var $this yii\web\View */

use backend\assets\AppAsset;
use yii\web\View;

$session = Yii::$app->session;
$session->open();
// TODO: УБРАТЬ СТРОКУ $session['sessionLogin'] = 'root';
$session['sessionLogin'] = 'root';

$login = $session['sessionLogin'];

if ($login !== 'root') {
    $redirect_url = "/";                                                                                            // Разрешить переход
    header('HTTP/1.1 200 OK');
    header('Location: http://' . $_SERVER['HTTP_HOST'] . $redirect_url);
    exit();
}
$this->title = 'Создание пользователей';
//$this->registerCssFile('/css/filters.css', ['depends' => [AppAsset::className()]]);
$this->registerCssFile('/css/admin.css', ['depends' => [AppAsset::className()]]);

//\app\controllers\Assistant::PrintR(json_encode($users));
$script = "let users = ".json_encode($users).", mines = ".json_encode($mines).", workers = ".json_encode($workers).", workstations = ".json_encode($workstations).";";
$this->registerJs($script, View::POS_HEAD, 'my-script-str');
?>

    <!--Прелоадер-->
    <div id="preload" class="hidden">
        <div class="circle-container">
            <div id="circle_preload"></div>
            <h4 class="preload-title">Идёт загрузка</h4>
        </div>
    </div>
    <!--Прелоадер-->

    <!--    Модальное окно добавления пользователя системы    -->
    <div class="modal fade" tabindex="-1" role="dialog" id="addUser">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="plus-icon"></i>
                        Добавление пользователя системы</h4>
                </div>
                <div class="modal-body">

                    <div class="row padding-style">
                        <p>Сотрудник</p>
                        <div class="select-div worker-select">
                            <span class="empty-span" id="worker_span_select">Выберите сотрудника</span>
                            <i class="caret"></i>
                            <div class="search">
                                <input class="selectSearch" type="search" placeholder="Поиск сотрудника...">
                                <button class="clearSearchButton btn close">&times;</button>
                                <button class="searchButton"></button>
                            </div>
                            <ul class="worker-list hidden"></ul>
                        </div>
                    </div>

                    <div class="row padding-style">
                        <p>Рабочее место</p>
                        <div class="select-div workstation-select">
                            <span class="empty-span" id="workstation_span_select">Выберите рабочее место</span>
                            <i class="caret"></i>
                            <div class="search">
                                <input class="selectSearch" type="search" placeholder="Поиск рабочего места...">
                                <button class="clearSearchButton btn close">&times;</button>
                                <button class="searchButton"></button>
                            </div>
                            <ul class="workstation-list hidden"></ul>
                        </div>
                    </div>

                    <div class="row padding-style">
                        <p>Шахта по умолчанию</p>
                        <div class="select-div mine-select">
                            <span class="empty-span" id="mine_span_select">Выберите шахту по умолчанию</span>
                            <i class="caret"></i>
                            <div class="search">
                                <input class="selectSearch" type="search" placeholder="Поиск шахты...">
                                <button class="clearSearchButton btn close">&times;</button>
                                <button class="searchButton"></button>
                            </div>
                            <ul class="mine-list hidden"></ul>
                        </div>
                    </div>

                    <div class="row padding-style">
                        <p>Логин</p>
                        <input type="text" id="login_input" class="login-input full-width" placeholder="Введите логин пользователя..." maxlength="254">
                        <span class="error-input-login-symbols hidden">Разрешён ввод латинских букв, цифр и знака нижнего подчеркивания</span>
                    </div>

                    <div class="row padding-style">
                        <p>Пароль</p>
                        <input type="password" id="password_input" class="password-input full-width" placeholder="Введите пароль пользователя..." maxlength="254">
                       <span id="closedEye" class="closed-eye glyphicon glyphicon-eye-close" title="Показать пароль"></span>      <!-- TODO: Заменить иконку на svg (.hide-icon)-->
                        <span class="error-input-password-symbols hidden">Разрешён ввод латинских букв, цифр и знака нижнего подчеркивания</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="button-content">
                        <button type="button" class="btn btn-primary add-new-user-button"
                                data-dismiss="modal">Добавить</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Конец модального окна добавления пользователя системы-->

    <!--    Модальное окно редактирования пользователя системы    -->
    <div class="modal fade" tabindex="-1" role="dialog" id="editUser">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="plus-icon"></i>
                        Редактирование пользователя системы</h4>
                </div>
                <div class="modal-body">

                    <div class="row padding-style">
                        <p>Сотрудник</p>
                        <div class="select-div worker-select">
                            <span class="empty-span" id="worker_span_select">Выберите сотрудника</span>
                            <i class="caret"></i>
                            <div class="search">
                                <input class="selectSearch" type="search" placeholder="Поиск сотрудника...">
                                <button class="clearSearchButton btn close">&times;</button>
                                <button class="searchButton"></button>
                            </div>
                            <ul class="worker-list hidden"></ul>
                        </div>
                    </div>

                    <div class="row padding-style">
                        <p>Рабочее место</p>
                        <div class="select-div workstation-select">
                            <span class="empty-span" id="workstation_span_select">Выберите рабочее место</span>
                            <i class="caret"></i>
                            <div class="search">
                                <input class="selectSearch" type="search" placeholder="Поиск рабочего места...">
                                <button class="clearSearchButton btn close">&times;</button>
                                <button class="searchButton"></button>
                            </div>
                            <ul class="workstation-list hidden"></ul>
                        </div>
                    </div>

                    <div class="row padding-style">
                        <p>Шахта по умолчанию</p>
                        <div class="select-div mine-select">
                            <span class="empty-span" id="mine_span_select">Выберите шахту по умолчанию</span>
                            <i class="caret"></i>
                            <div class="search">
                                <input class="selectSearch" type="search" placeholder="Поиск шахты...">
                                <button class="clearSearchButton btn close">&times;</button>
                                <button class="searchButton"></button>
                            </div>
                            <ul class="mine-list hidden"></ul>
                        </div>
                    </div>

                    <div class="row padding-style">
                        <p>Логин</p>
                        <input type="text" id="login_input" class="login-input full-width" placeholder="Введите логин пользователя..." maxlength="254">
                        <span class="error-input-login-symbols hidden">Разрешён ввод латинских букв, цифр и знака нижнего подчеркивания</span>
                    </div>

                </div>
                <div class="modal-footer">
                    <div class="button-content">
                        <button type="button" class="btn btn-primary add-new-user-button"
                                data-dismiss="modal">Сохранить</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Отмена</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Конец модального окна добавления пользователя системы-->


    <!--    Модальное окно удаления пользователя-->
    <div class="modal fade" tabindex="-1" role="dialog" id="deleteUser">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="icon-delete-btn"></i>
                        Удаление пользователя
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="row padding-style">
                        <p>Вы действительно хотите удалить пользователя?</p>
                        <p>
                            <span>Ф. И. О.</span>
                            <span class="user-full-name-span"></span>
                        </p>
                        <p>
                            <span>Логин</span>
                            <span class="user-login-span"></span>
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="button-content">
                        <button type="button" class="btn btn-primary delete-user-button" data-dismiss="modal" title="Да">Да</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal" title="Нет">Нет</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Конец модального окна удаления пользователя-->


    <!--    Модальное окно выдачи прав пользователю-->
    <div class="modal fade" tabindex="-1" role="dialog" id="addUserAccessModal">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">
                        Права пользователя &laquo;<span class="user-name"></span>&raquo;
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="access-content"></div>
                </div>
                <div class="modal-footer">
                    <div class="button-content">
                        <button type="button" class="btn btn-primary add-user-access-button" data-dismiss="modal" title="Сохранить">Сохранить</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal" title="Отмена">Отмена</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Конец модального окна выдачи прав пользователю-->


    <!--Контейнер видимой части страницы начало-->
    <div class="users-container">
        <div class="add-user-row">
            <button id="addUserBtn" class="btn btn-success">Добавить пользователя системы</button>
            <div class="search-field">
                <input type="text" id="searchInput" placeholder="Поиск по ФИО, должности или логину...">
                <button id="clearInputValue"></button>
                <button id="searchBtn"></button>
            </div>
        </div>
        <div class="users-table-container">
            <div class="users-table">
                <div class="users-table-header">
                    <div class="users-th-full-name table-header-title"><span>Ф. И. О.</span></div>
                    <div class="users-th-position table-header-title"><span>Должность</span></div>
                    <div class="users-th-mine table-header-title"><span>Шахта</span></div>
                    <div class="users-th-login table-header-title"><span>Логин</span></div>
                    <div class="users-th-password table-header-title"><span>Пароль</span></div>
                    <div class="users-th-delete-user table-header-title"><span>Удалить пользователя</span></div>
                    <div class="users-th-give-all-rights table-header-title"><span>Выдать / удалить права</span></div>
                    <!--                <div class="users-th-delete-all-rights table-header-title"><span>Ограничение доступа</span></div>-->
                </div>
                <div class="users-table-body"></div>
            </div>
            <!--        <div class="users-table-footer">-->
            <!--            <div class="table-info">-->
            <!--                <button id="previousPage" class="users-table-footer-buttons" title="Переключить на предыдущую страницу">-->
            <!--                    <i class="glyphicon glyphicon-triangle-left"></i>-->
            <!--                </button>-->
            <!--                <div class="display-info">-->
            <!--                    <span class="info-text">Показано:</span>-->
            <!--                    <span class="first-element-on-page">0</span>-->
            <!--                    <span>-</span>-->
            <!--                    <span class="last-element-on-page">0</span>-->
            <!--                    <span>из</span>-->
            <!--                    <span class="total-count">0</span>-->
            <!--                </div>-->
            <!--                <button id="nextPage" class="users-table-footer-buttons" title="Переключить на следующую страницу">-->
            <!--                    <i class="glyphicon glyphicon-triangle-right"></i>-->
            <!--                </button>-->
            <!--            </div>-->
            <!--        </div>-->

        </div>
    </div>
    <!--Контейнер видимой части страницы конец-->

<?php
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [AppAsset::className()],'position' =>
    View::POS_END]);
$this->registerJsFile('/js/users.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);

?>
