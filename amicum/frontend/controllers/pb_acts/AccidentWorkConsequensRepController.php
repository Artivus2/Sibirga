<?php

namespace frontend\controllers\pb_acts;

class AccidentWorkConsequensRepController extends \yii\web\Controller
{
    /**
     * СООБЩЕНИЕо последствиях несчастного случая на производстве и принятых мерах
     * Class AccidentWorkConsequensRepController
     * @package app\controllers
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

}
