<?php

namespace frontend\controllers;

/**
 * Контроллер для модуля выдачи наряда
 * Class OrderSystemController
 * @package frontend\controllers
 */
class OrderSystemVersionTwoController extends \yii\web\Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }

}