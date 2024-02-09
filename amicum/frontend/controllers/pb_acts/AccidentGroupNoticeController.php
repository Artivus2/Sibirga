<?php

namespace frontend\controllers\pb_acts;

class AccidentGroupNoticeController extends \yii\web\Controller
{
    /**
     * Class AccidentGroupNoticeController
     * @package app\controllers
     *
     *ИЗВЕЩЕНИЕ о групповом несчастном случае (тяжелом несчастном случае, несчастном случае со смертельным исходом)
     *NOTICE about a group accident (serious accident, fatal accident)
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

}
