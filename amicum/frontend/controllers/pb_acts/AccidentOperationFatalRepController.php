<?php

namespace frontend\controllers\pb_acts;

class AccidentOperationFatalRepController extends \yii\web\Controller
{
    /**
     * Class AccidentOperationFatalRepController
     * @package app\controllers
     *
     * ОПЕРАТИВНОЕ СООБЩЕНИЕ (ИНФОРМАЦИЯ) ОБ АВАРИИ (ТЯЖЕЛОМ, ГРУППОВОМ, СО СМЕРТЕЛЬНЫМ ИСХОДОМ)ПРОИЗОШЕДШЕМ В РЕЗУЛЬТАТЕ АВАРИИ, ИНЦИДЕНТА, УТРАТЫ ВЗРЫВЧАТЫХ МАТЕРИАЛОВ ПРОМЫШЛЕННОГО НАЗНАЧЕНИЯ
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

}
