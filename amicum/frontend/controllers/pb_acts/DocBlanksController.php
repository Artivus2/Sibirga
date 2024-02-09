<?php

namespace frontend\controllers\pb_acts;

class DocBlanksController extends \yii\web\Controller
{
    /**
     * сонтролер для страницы отчеты тк
     * Class DocBlanksController
     * @package app\controllers
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

}
