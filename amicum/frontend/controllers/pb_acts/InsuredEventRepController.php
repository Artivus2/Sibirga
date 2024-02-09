<?php

namespace frontend\controllers\pb_acts;

class InsuredEventRepController extends \yii\web\Controller
{
    /**
     * Приложение № 1 к приказу Фонда социального страхования Российской Федерации от 24.08.2000 № 157
     * Class InsuredEventRepController
     * @package app\controllers
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

}
