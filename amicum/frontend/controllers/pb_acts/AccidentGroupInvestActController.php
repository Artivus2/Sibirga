<?php

namespace frontend\controllers\pb_acts;
//ob_start();
use yii\db\Query;
use yii\web\Controller;

/**
 * Class AccidentGroupInvestActController
 * @package app\controllers
 *  АКТ О РАССЛЕДОВАНИИ ГРУППОВОГО НЕСЧАСТНОГО СЛУЧАЯ (ТЯЖЕЛОГО НЕСЧАСТНОГО СЛУЧАЯ, НЕСЧАСТНОГО СЛУЧАЯ СО СМЕРТЕЛЬНЫМ ИСХОДОМ)
 *  ACT ON THE INVESTIGATION OF A GROUP ACCIDENT (SEVERE ACCIDENT, ACCIDENT WITH A FATAL OUTCOME)
 *
 */
class AccidentGroupInvestActController extends Controller
{
    public function actionIndex()
    {
//        $model = $this->actionGetWorkersInfo();
//        return $this->render('index', [
//            'model' => $model
//        ]);
        return $this->render('index');
    }

    /**
     * Метод получения информации о всех работниках(Возвращает всех работников с данными
     * Фио
     * Дата рождения
     * Должность)
     * Дата начала работы
     * Created by: Фидченко М.В. on 30.11.2018 11:04
     */
    public function actionGetWorkersInfo()
    {
        $model = array();
        $workers_collection = (new Query())
            ->select ([
                'last_name',
                'first_name',
                'patronymic',
                'birthdate',
                'title as position',
                'date_start'
            ])
            ->from('worker')
            ->LeftJoin('employee','employee.id =  worker.employee_id')
            ->LeftJoin('position','position.id =  worker.position_id')
            ->orderby("last_name")
            ->all();
        $model = $workers_collection;
        return $model;

    }
}
