<?php

namespace frontend\controllers;

/**
 * Контроллер для Системы оценки и управления рисками
 * Class RiskAssessmentAndManagementSystemController
 * @package frontend\controllers
 */
class RiskAssessmentAndManagementSystemController extends \yii\web\Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }

}
