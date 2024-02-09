<?php

namespace frontend\controllers;

/**
 * Контроллер для модуля авторизации
 * Class AuthorizationController
 * @package frontend\controllers
 */
class AuthorizationController extends \yii\web\Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }

}
