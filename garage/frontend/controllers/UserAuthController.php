<?php

namespace frontend\controllers;
use Yii;
use yii\web\Controller;
use frontend\models\User;
use frontend\models\Garage;
use frontend\models\UserAccess;
use Throwable;
use yii\db\Query;
use yii\web\Response;



class UserAuthController extends Controller
{
//    public function actionIndex()
//    {
//        $session = Yii::$app->session;
//        $garage_id = $session['garageID'];
//        $place = Place::find()
//            ->select(['title', 'id'])
//            ->asArray()->all();
        //$ex = $this->actionGetWorkers();
//        $sensorList = $this->SendSensorAc();
//        return $this->render('index', [
//            'garage_id' => $garage_id,
//        ]);
//    }


    public static function actionLogin($data_post = null)
    {
        $session = Yii::$app->session;
        $user = User::find()
                        ->where(['login' => Yii::$app->user->identity->login])
                        ->one();
        $session_id = null;
        $status = 1;
        $method_name = 'actionLogin';
        $warnings = array();
        $errors = array();
        $result = null;
        if (!is_null($data_post) and $data_post != "") {
            $warnings[] = $method_name . ". данные успешно переданы";
            $warnings[] = $method_name . ". Входной массив данных" . $data_post;
//            var_dump($warnings);
            }
    }
    }
