<?php

namespace frontend\controllers;

/**
 * Контроллер для предсменного экзаменатора
 * Class DashBoardController
 * @package frontend\controllers
 */
class ShiftExaminerController extends \yii\web\Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }

}
