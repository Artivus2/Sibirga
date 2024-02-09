<?php
use yii\web\View;
use frontend\assets\AppAsset;

$this->registerCssFile('/css/login-form.css',['depends' => [AppAsset::className()]]);
$this->registerJsFile('/js/login-form.js',['position' => View::POS_END]);
$this->title = 'Авторизация';
header('cache-control: no-cache');
?>

<div class="col-xs-4 logotype">
    <img src="/img/amicum-logo.png" alt="Логотип АМИКУМ" class="main-logo"/>
</div>
<div class="col-xs-4 login-container">
    <div class="login-container-header">
        <div class="header-elements">
            <!--                <div class="glyphicon glyphicon-user"></div>-->
            <span>Авторизация</span>
        </div>
    </div>
    <div class="text-field-login">
        <div class="glyphicon glyphicon-user"></div>
        <input type="text" id="textLogin" placeholder="Введите логин">
    </div>
    <div class="text-field-password">
        <div class="glyphicon glyphicon-lock"></div>
        <input type="password" id="textPassword" placeholder="Введите пароль">
        <div id="switchPasswordVisibility" class="glyphicon glyphicon-eye-close" title="Показать пароль"></div>
    </div>
    <div class="active-directory-row">
        <label for="activeDirectoryFlag">
            <input type="checkbox" id="activeDirectoryFlag">
            Авторизация по ActiveDirectory
        </label>
    </div>
    <div class="forgot-password">
        <span id="forgotPass">Забыли пароль?</span>
    </div>
    <div class="text-error">
        <span id="errorMessage"></span>
    </div>
    <div class="login-button">
        <button id="loginButton">Войти</button>
    </div>
</div>

<div class="notify-forgot-password hidden" id="notify">
    <span>Свяжитесь с администратором</span>
</div>
