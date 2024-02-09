<?php

namespace frontend\controllers;
//ob_start();

use backend\controllers\Alias;
use backend\controllers\PackData;
use frontend\models\Place;
use Yii;
use yii\web\Controller;


class SecondaryUnityController extends Controller
{
    use Alias;
    use PackData;

    public function actionIndex()
    {
        $session = Yii::$app->session;
        $mine_id = $session['userMineId'];
        $place = Place::find()
            ->select(['title', 'id'])
            ->asArray()->all();
        //$ex = $this->actionGetWorkers();
//        $sensorList = $this->SendSensorAc();
        return $this->render('index', [
            //'ex' => $ex,
            'mine_id' => $mine_id,
            'place' => $place
//            'sensorList' => $sensorList
        ]);
//        return $this->render('index');
    }
}
