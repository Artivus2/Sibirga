<?php

namespace frontend\controllers\pb_acts;

class AccidentOperationRepController extends \yii\web\Controller
{
    /**
     * ОПЕРАТИВНОЕ СООБЩЕНИЕ (ИНФОРМАЦИЯ) ОБ АВАРИИ, ИНЦИДЕНТЕ, СЛУЧАЕ УТРАТЫ ВЗРЫВЧАТЫХ МАТЕРИАЛОВ ПРОМЫШЛЕННОГО НАЗНАЧЕНИЯ
     * Class OperationalReportAboutAccidentController
     * @package app\controllers
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

}
