<?php

namespace frontend\controllers\pb_acts;

class IndustrialExplosivesJournalRegController extends \yii\web\Controller
{
    /**
     * ЖУРНАЛ УЧЕТА АВАРИЙ, ПРОИСШЕДШИХ НА ОПАСНЫХ ПРОИЗВОДСТВЕННЫХ ОБЪЕКТАХ, ПОВРЕЖДЕНИЙ ГИДРОТЕХНИЧЕСКИХ СООРУЖЕНИЙ
     * Class IndustrialExplosivesJournalRegController
     * @package app\controllers
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

}
