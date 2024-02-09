<?php

namespace frontend\controllers;

use Yii;
use yii\base\InvalidArgumentException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use frontend\models\garageForm;
use frontend\models\Auto;
use frontend\models\Garage;
use frontend\models\Company;
use frontend\models\UserAccess;
use frontend\models\Department;
use frontend\models\Drivers;
use frontend\models\typeWorks;
use frontend\models\workJournal;

use yii\web\Response;

/**
 * Garage controller
 */
class JobsController extends Controller
{

    public function actionIndex()
    {
    //$work_status = 0;
    //$result = 0;
    //$user = Yii::$app->user->identity->id;
    //$auto = Auto::find()->where(['work_status' => $work_status])->all();
    //$garage = Garage::find()->all();
    //$company = Company::find()->all();
    return $this->render('index');
    }
    
    public function actionGetWorkJournalDate() {
    $post = Yii::$app->request->post();
    $startdate = date("Y-m-d", strtotime($post['startdate']));
    //$startdate =date("Y-m-d", (int)$startdate);
    $enddate = strtotime($post['enddate']);
    $enddate =date("Y-m-d", (int)$enddate);
    
    
    $workjournal = Yii::$app->db->createCommand("
                SELECT workJournal.id, drivers.fio, drivers.tabelnom,
                Department.name as zakazchik, smena.name as smena,
                timestampdiff(MINUTE, workJournal.date_begin, workJournal.date_end) as period,
                workJournal.date_begin, workJournal.date_end
                FROM workJournal
                LEFT JOIN drivers ON drivers.id = driver_id
                LEFT JOIN Department ON Department.id = zakazchik_id
                LEFT JOIN smena ON smena.id = smena_id
                WHERE workJournal.date_begin BETWEEN '".$startdate." 00:00' AND '".$enddate." 23:59'
                ORDER BY workJournal.id DESC")->queryAll();
    $result = $workjournal;
    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    Yii::$app->response->data = $result;
    return $result;
    }
    
    public function actionGetWorkJournal() {
// №п/п | ФИО | Таб. № | Заказчик | Дата/время выдачи задания | Смена | Дата/время выполнения задания | Продолжительность, минут
	$model = array();
	$post = Yii::$app->request->post();
	$workjournal = Yii::$app->db->createCommand("
                SELECT workJournal.id, drivers.fio, drivers.tabelnom,
                Department.name as zakazchik, smena.name as smena,
                timestampdiff(MINUTE, workJournal.date_begin, workJournal.date_end) as period,
                workJournal.date_begin, workJournal.date_end
                FROM workJournal
                LEFT JOIN drivers ON drivers.id = driver_id
                LEFT JOIN Department ON Department.id = zakazchik_id
                LEFT JOIN smena ON smena.id = smena_id
                ORDER BY workJournal.id DESC
	")->queryAll();
        //$workjournal = workJournal::find()->all();
        $result = $workjournal;
        //$date_now = date('Y-m-d', strtotime(AssistantBackend::GetDateNow()));
        //$between_date = (strtotime($date_now) - strtotime($briefing['max_date_briefing'])) / (60 * 60 * 24);
        
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
        return $result;
    }

}



