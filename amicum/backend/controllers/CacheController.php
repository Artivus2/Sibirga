<?php

namespace backend\controllers;

class CacheController extends \yii\web\Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }
    public function actionTest()
    {
	echo "test ok";
    }

}
