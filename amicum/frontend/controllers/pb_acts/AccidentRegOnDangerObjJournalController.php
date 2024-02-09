<?php

namespace frontend\controllers\pb_acts;

class AccidentRegOnDangerObjJournalController extends \yii\web\Controller
{
    /**
     * Class AccidentRegOnDangerObjJournalController
     * @package app\controllers
     *
     *ЖУРНАЛ УЧЕТА ИНЦИДЕНТОВ, ПРОИСШЕДШИХ НА ОПАСНЫХ ПРОИЗВОДСТВЕННЫХ ОБЪЕКТАХ, ГИДРОТЕХНИЧЕСКИХ СООРУЖЕНИЯХ
     *JOURNAL OF ACCOUNTING INCIDENTS, HAPPENING AT DANGEROUS PRODUCTION FACILITIES, HYDROTECHNICAL FACILITIES
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

}
