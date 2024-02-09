<?php

namespace frontend\controllers;

/**
 * Контроллер для дашбоарда
 * Class DashBoardController
 * @package frontend\controllers
 */
class DashBoardController extends \yii\web\Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }

}
