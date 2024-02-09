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
use frontend\models\Smena;
use frontend\models\typeWorks;
use frontend\models\workJournal;

use yii\web\Response;

/**
 * Garage controller
 */
class GarageController extends Controller
{

    
    public function actionGetAccessGarage()
    {
    $post = Yii::$app->request->post();
    $user = Yii::$app->user->identity->id;
    $result = 0;
    $id_garage = $post['id'];
    $userAccessGarage = UserAccess::find()->where(['user_id' => $user, 'garage_id' => $id_garage])->all();
    if (empty($userAccessGarage)) {
    $result = 0;
    } else {
    $result = $userAccessGarage;
    }
    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    Yii::$app->response->data = $result;
    return $result;
    }
    
    public function actionGetAccessCompany()
    {
    $post = Yii::$app->request->post();
    $user = Yii::$app->user->identity->id;
    $result = 0;
    $id_company = $post['id'];
    $userAccessCompany = UserAccess::find()->where(['user_id' => $user, 'company_id' => $id_company])->all();
    if (empty($userAccessGarage)) {
    $result = 0;
    } else {
    $result = $userAccessGarage;
    }
    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    Yii::$app->response->data = $result;
    return $result;
    }
    
    
    public function actionGetAccess()
    {
    $post = Yii::$app->request->post();
    $user = Yii::$app->user->identity->id;
    $userAccess = UserAccess::find()->where(['user_id' => $user])->all();
    $result = $userAccess;
    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    Yii::$app->response->data = $result;
    return $result;
    }
    
    
    
    
    public function actionIndex()
    {
    $work_status = 0;
    $result = 0;
    //$user = Yii::$app->user->identity->id;
    $auto = Auto::find()->where(['work_status' => $work_status])->all();
    $garage = Garage::find()->all();
    $company = Company::find()->all();
    return $this->render('index', ['auto'=>$auto, 'garage' => $garage, 'company' => $company]);
    }
    
    public function actionAuto() {
    return "api rabotaet";
    }
    
    public function actionGetZakazchik()
    {
        $model = array();
        $post = Yii::$app->request->post();
        //var_dump($_POST);
        $department = Department::find()->where(['company_id' => $post['company_id']])->all();
        $result = $department;
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
        return $result;
    }
    
        public function actionGetZakazchikAuto()
    {
        $model = array();
        $post = Yii::$app->request->post();
        //var_dump($_POST);
        $work_status_department = Auto::find()->where(['id' => $post['auto_id']])->all();
        $work = $work_status_department[0]['work_status'];
        $department = Department::find()->where(['id' => $work])->all();
        //$all_deps=Department::find()->where(['company_id' => $department[0]['company_id']]);
        $result = $department;
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
        return $result;
    }


	public function actionGetAutos()
	{
        $model = array();
        $post = Yii::$app->request->post();
        //var_dump($_POST);
        
        //$autos = Auto::find()->where(['work_status' => $post['tab_id']])->all();
        $autos = Auto::find()->all();
        
        $result = $autos;
        
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
        return $result;
//        $garage = Garage::find()->where(['id' => $id])->all();
//        $garage_name=$garage->name;
        

	}
	
	public function actionGetDrivers()
	{
        $model = array();
        $worked=0; //свободные
        $post = Yii::$app->request->post();
        //$drivers = Drivers::find()->where(['status' => $worked])->all();
        $drivers = Drivers::find()->all();
        $result = $drivers;
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
        return $result;
	}
	
	public function actionGetTypeWorks()
	{
        $model = array();
        $post = Yii::$app->request->post();
        $types = typeWorks::find()->all();
        $result = $types;
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
        return $result;
	}
	
	
	public function actionSaveZadania () {
	$post = Yii::$app->request->post();
	$lastWorkJournalDriverStatusByIdAuto = null;
	$result = $post;
	Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
	return $result;
	}
	
	
	public function actionSaveAutos()
	{
        $model = array();
        $post = Yii::$app->request->post();
        $lastWorkJournalDriverStatusByIdAuto = null;
        //var_dump($_POST);
        $i = 0;
        //$massivUser = explode(",", $post['userFacets']);
        $massivUser = $post['userFacets'];
        if (empty($massivUser)) {
        $result = "Нет машин в данном подразделении";
        } else 
    	    {
    	    $massivUser = explode(",", $post['userFacets']);
    	    $tabId = $post['tab_id'];
    	    //сохранение статуса авто
            foreach ($massivUser as $i) {
	        $autos = Auto::findOne($i);
    		$autos ->work_status = $tabId;
        	$autos->save();

    		//сохранение статуса водителя 
    		if ($post['autoToChange']==$autos->id) {
    		    $driver_id = $post['driver_id'];
    		    $driver = Drivers::findOne($driver_id);
    		    $driver -> status = 1; //водитель занят, 0 - сободен
    		    $driver -> save();
    		    
    		    //создаем новую запись в журнале сменного задания
    		    $workJournal = new workJournal();
	    	    $workJournal->auto_id = $post['autoToChange'];
    	    	    $workJournal->status_id = 3; // в работе
	    	    $workJournal->date_begin = $post['date_begin'];
	    	    $workJournal->date_end = null;
	    	    $workJournal->garage_id = $post['garage_id'];
	    	    $workJournal->zakazchik_id = $post['zakazchik_id'];
	    	    $workJournal->driver_id = $post['driver_id'];
	    	    $workJournal->user_id = $post['user_id'];
    	    	    $workJournal->worksType = $post['worksTypes'];
    	    	    $hournow = Date('H');
    	    	    $currentSmena = 3;
    	    	    $smena = Smena::find()->all();
    	    	    if ($hournow >=8 && $hournow <= 16) {
    	    	    $currentSmena = 1;
    	    	    }
    	    	    if ($hournow >=16 && $hournow <= 24) {
    	    	    $currentSmena = 2;
    	    	    }
    	    	    $workJournal->smena_id = $currentSmena;
	    	    $workJournal->save();
    		}
                $i++;
    		}
    	    }
    	$i = 0;
    	//$massivAll = explode(",", $post['allFacets']);
    	$massivAll = $post['allFacets'];
    	
        if (empty($massivAll)) {
        $result = "Свободные отсутствуют";
        } else 
    	    {
    	    $massivAll = explode(",", $post['allFacets']);
            foreach ($massivAll as $i) {
	        $autos = Auto::findOne($i);
    		$autos ->work_status = 0;
        	$autos->save();
        	//нужно найти последнюю запись в журнале с id водителя по id auto
        	//добавить права для начальника смены
        	if ($post['autoToChange']==$autos->id) {
        	    $lastWorkJournalDriverStatusByIdAuto = workJournal::find()->where(['auto_id' => $post['autoToChange']])->orderBy(['id' => SORT_DESC])->limit(1)->one();
        	    $lastWorkJournalDriverStatusByIdAuto->date_end = Date('Y-m-d H:i:s');
        	    $driverToChangeStatus = $lastWorkJournalDriverStatusByIdAuto -> driver_id;
		    $driver = Drivers::findOne($driverToChangeStatus);
        	    $driver -> status = 0;
        	    $driver -> save();
        	    $lastWorkJournalDriverStatusByIdAuto->save();
    		    }
        	
                $i++;
    		}
    	    }
    	    
    	    $result = $lastWorkJournalDriverStatusByIdAuto;
	    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    	    Yii::$app->response->data = $result;
    	
    	return $result;
	}
	
    

}



