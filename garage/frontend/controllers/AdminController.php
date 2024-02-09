<?php

namespace frontend\controllers;

use Yii;
use yii\base\InvalidArgumentException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use frontend\models\User;
use frontend\models\Drivers;

/**
 * admin controller
 */
class AdminController extends Controller
{

    public function actionIndex()
    {
    //$user = Yii::$app->user->identity->id;
    //$model = 0;
    //if ($user==1) {
    //echo "вы АДМИН";
    $model = 1;
    
    //}
    //else {
    //echo "вЫ не АДМИН";
    //$model = 0;
    //}
    
    
        return $this->render('index', ['model' => $model]);
    }
    
    
    public function actionGetUsers () {
    $post = Yii::$app->request->post();
    //$result = "Получен список пользователей";
    $users = User::find()->all();
    $result = $users;
    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    Yii::$app->response->data = $result;
    return $result;
    }
    
    public function actionGetUsersById () {
    $get = Yii::$app->request->post();
    $result = $get['id']+ 1;
    $users = User::find()->where(['id' => $result])->one();
    $result = $users;
    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    Yii::$app->response->data = $result;
    return $result;
    }
    
    public function actionGetDriversById () {
    $post = Yii::$app->request->post();
    $result = $post['id']+ 1;
    $drivers = Drivers::find()->where(['id' => $result])->one();
    $result = $drivers;
    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    Yii::$app->response->data = $result;
    return $result;
    }
    
    public function actionEditUsers () {
    $post = Yii::$app->request->post();
    $users = User::findOne($post['id']);
    $users -> fio = $post['fio'];
    $users -> login = $post['login'];
    $users -> tabel_nom = $post['tabel_nom'];
    $users -> email = $post['email'];
    $users -> status = $post['status'];
    $users->save();
    //$result = $post['id']+ 1;
    //$users = User::find()->where(['id' => $result])->one();
    $result = "Изменения сохранены";
    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    Yii::$app->response->data = $result;
    return $result;
    }
    
    public function actionEditDrivers () {
    $post = Yii::$app->request->post();
    $drivers = Drivers::findOne($post['id']);
    $drivers -> fio = $post['fio'];
    $drivers -> tabelnom = $post['tabelnom'];
    $drivers -> status = $post['status'];
    $drivers -> save();
    //$result = $post['id']+ 1;
    //$users = User::find()->where(['id' => $result])->one();
    $result = "Изменения сохранены";
    Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    Yii::$app->response->data = $result;
    return $result;
    }
    
    public function actionGetDate () {
    $post = Yii::$app->request->post();
    $result = Date('Y-m-d H:i:s');
    return $result;
    }
    
    public function actionUpdate ($id) {
    
    $model = User::findOne($id);
    
    if ($model->load(Yii::$app->request->post())) {
	$model->save();
	return $this->redirect('index');
        }
    return $this->render('edit', ['model' => $model]);
    }
    
    public function actionView($id) {
    $model = User::findOne($id);
    return $this->render('index');
    }
    
    
}



