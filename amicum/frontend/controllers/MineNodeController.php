<?php

namespace frontend\controllers;
//ob_start();
use yii\web\Controller;

class MineNodeController extends Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }

}
