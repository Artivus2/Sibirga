<?php

namespace frontend\controllers;
//ob_start();

use backend\controllers\Alias;
use backend\controllers\PackData;
use frontend\controllers\handbooks\HandbookTypicalObjectController;
use frontend\models\Place;
use Yii;
use yii\db\Query;


class ThirdUnityController extends \yii\web\Controller
{
    use Alias;
    use PackData;

    public function actionIndex()
    {
        $session = Yii::$app->session;
        $mine_id = $session['userMineId'];
        $typicalObjects = HandbookTypicalObjectController::getTypicalObjectArray()['Items'];
        $kindObjectIdsForInit = [1, 2, 3, 5];
        $objectIdsForInit = (new Query())
            ->select('object.id')
            ->from('object')
            ->innerJoin('object_type', 'object_type.id=object.object_type_id')
            ->where(['kind_object_id' => $kindObjectIdsForInit])
            ->column();
        $place = Place::find()
            ->select(['title', 'id'])
            ->asArray()->all();
        return $this->render('index', [
            'mine_id' => $mine_id,
            'typicalObjects' => $typicalObjects,
            'kindObjectIdsForInit' => $kindObjectIdsForInit,
            'objectIdsForInit' => $objectIdsForInit,
            'place' => $place
        ]);
    }

}
