<?php

namespace frontend\controllers\pb_acts;

class AccidentPollingRepController extends \yii\web\Controller
{
    /**
     * Протокол опроса пострадавшего при несчастном случае (очевидца несчастного случая, должностного лица)
     * Class AccidentPollingRepController
     * @package app\controllers
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

}
