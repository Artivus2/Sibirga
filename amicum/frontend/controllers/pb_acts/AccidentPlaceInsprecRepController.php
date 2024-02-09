<?php

namespace frontend\controllers\pb_acts;

class AccidentPlaceInsprecRepController extends \yii\web\Controller
{
    /**
     * ПРОТОКОЛ осмотра места несчастного случая, происшедшего
     * Class AccidentPlaceInsprecRepController
     * @package app\controllers
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

}
