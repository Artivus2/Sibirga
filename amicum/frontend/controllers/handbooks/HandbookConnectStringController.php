<?php

namespace frontend\controllers\handbooks;
use frontend\controllers\Assistant;
use frontend\models\AccessCheck;
use frontend\models\SettingsDCS;
use frontend\models\ConnectString;
use yii\db\Query;
use Yii;
use yii\web\Response;

class HandbookConnectStringController extends \yii\web\Controller
{

    public function actionIndex()
    {
        $model = $this->buildArray();
        $settingsDCSes = SettingsDCS::find()
            ->select(['title', 'id'])
            ->asArray()->all();
        $sourceTypes = ConnectString::find()
            ->select(['source_type'])
            ->groupBy('source_type')
            ->asArray()->all();
        $sourceTypesArray = array();
        $i = 0;
        foreach($sourceTypes as $source){
            $sourceTypesArray[$i] = array();
            $sourceTypesArray[$i]['iterator'] = $i;
            $sourceTypesArray[$i]['title'] = $source['source_type'];
            $i++;
        }
        return $this->render('index', [
            'model' => $model,
            'settingsDCS' => $settingsDCSes,
            'sourceType' => $sourceTypesArray,
        ]);
    }

    public function buildArray()
    {
        $connects = ConnectString::find()->orderBy('title')->all();
        $model = array();
        $i = 0;
        foreach ($connects as $connect) {
            $model[$i] = array();
            $model[$i]['iterator'] = $i+1;
            $model[$i]['id'] = $connect->id;
            $model[$i]['title'] = $connect->title;
            $model[$i]['ip'] = $connect->ip;
            $model[$i]['connectString'] = $connect->connect_string;
            $model[$i]['settingsDcsId'] = $connect->settingsDCS->title;
            $model[$i]['sourceType'] = $connect->source_type;
            $i++;
        }
        return $model;
    }

    public function actionAddConnectString()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $model = array();
        $errors = array();
        if(isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 12)) {                                               //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();
                $connect = ConnectString::findOne(['title'=>$post['title']]);
                if(!$connect){
                    $connect = new ConnectString();
                    $connect->title = $post['title'];
                    $connect->ip = $post['ip'];
                    $connect->connect_string = $post['connectString'];
                    if(isset($post['settingsDcsId'])){
                        $settingsDcs = SettingsDCS::findOne($post['settingsDcsId']);
                        if($settingsDcs){
                            $connect->Settings_DCS_id = $post['settingsDcsId'];
                        }
                        else {
                            $errors[] = "Имя ССД не найдено";
                            $model = $this->buildArray();
                        }
                    }
                    else {
                        $errors[] = "Имя ССД не задано";
                        $model = $this->buildArray();
                    }
                    if(isset($post['sourceType'])){
                        $connect->source_type = $post['sourceType'];
                        $model = $this->buildArray();
                    }
                    else {
                        $errors[] = "Тип источника не задан";
                        $model = $this->buildArray();
                    }
                    if($connect->save()){
                        $model = $this->buildArray();
                    }
                    else {
                        $errors[] = "Модель не сохранена";
                        $model = $this->buildArray();
                    }
                }
                else {
                    $errors[] = "Такая строка подключения уже существует";
                    $model = $this->buildArray();
                }
            }
            else{
                $errors[] = "У вас недостаточно прав для выполнения этого действия";
                $model = $this->buildArray();
            }
        }
        else{
            $errors[] = "Сессия неактивна";
        }
        $result = array('errors' => $errors, 'model' => $model);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    public function actionEditConnectString()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if(isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 13)) {                                               //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();
                $connect = ConnectString::findOne([$post['id']]);
                if($connect){
                    $existingConnect = ConnectString::findOne(['title'=>$post['title']]);
                    if(!$existingConnect || $existingConnect->id === $connect->id){
                        $connect->title = $post['title'];
                        $connect->ip = $post['ip'];
                        $connect->connect_string = $post['connectString'];
                        if(isset($post['settingsDcsId'])){
                            $connectSettings = ConnectString::findOne($post['settingsDcsId']);
                            if($connectSettings){
                                $connect->Settings_DCS_id = $post['settingsDcsId'];
                            }
                        }
                        else {
                            $errors[] = "Имя ССД не задано";
                            $model = $this->buildArray();
                        }
                        if(isset($post['sourceType'])){
                            $connectSrType = ConnectString::findOne(['source_type'=>$post['sourceType']]);
                            if($connectSrType){
                                $connect->source_type = $post['sourceType'];
                            }
                        }
                        else {
                            $errors[] = "Тип источника не задан";
                            $model = $this->buildArray();
                        }
                        if($connect->save()){
                            $model = $this->buildArray();
                        }
                        else {
                            $errors[] = "Модель не сохранена";
                            $model = $this->buildArray();
                        }
                    }
                    else{
                        $errors[] = "Такая строка подключения уже существует";
                        $model = $this->buildArray();
                    }
                }
                else{
                    $errors[] = "Такой строки подключения не существует";
                    $model = $this->buildArray();
                }
            }
            else{
                $errors[] = "У вас недостаточно прав для выполнения этого действия";
                $model = $this->buildArray();
            }
        }
        else{
            $errors[] = "Сессия неактивна";
            $model = $this->buildArray();
        }
        $result = array('errors' => $errors, 'model' => $model);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    public function actionDeleteConnectString()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if(isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 14)) {                                               //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();
                $connect = ConnectString::findOne($post['id']);
                if($connect){
                    if(!$connect->sensorConnectString) {
                        if ($connect->delete()) {
                            $model = $this->buildArray();
//                            echo json_encode($model);
                        } else {
                            $errors[] = "Ошибка удаления";
                            $model = $this->buildArray();
                        }
                    }
                    else {
                        $errors[] = "Строка подключения привязана к объекту";
                        $model = $this->buildArray();
                    }
                }
                else {
                    $errors[] = "Такой строки подключения не существует";
                    $model = $this->buildArray();
                }
            }
            else{
                $errors[] = "У вас недостаточно прав для выполнения этого действия";
                $model = $this->buildArray();
            }
        }
        else{
            $errors[] = "Сессия неактивна";
            $model = $this->buildArray();
        }
        $result = array('errors' => $errors, 'model' => $model);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    public function actionMarkSearchConnectString()
    {
        $post = Yii::$app->request->post();
        $errors = array();
        $connect_string_handbook = array();
        if(isset($post['search_title']))
        {
            $search_title = $post['search_title'];
            $sql_condition = "connect_string_title like '%$search_title%' OR  connect_string_ip like '%$search_title%' OR connect_string like '%$search_title%' OR 
                              Settings_DCS_title like '%$search_title%' OR  connect_string_source_type like '%$search_title%'";
            $connect_string_list = (new Query())
                ->select([
                    'connect_string_id',
                    'connect_string_title',
                    'connect_string_ip',
                    'connect_string',
                    'Settings_DCS_id',
                    'Settings_DCS_title',
                    'connect_string_source_type'
                ])
                ->from('view_connect_string_handbook')
                ->where($sql_condition)
                ->orderBy(['connect_string_title' => SORT_ASC, 'connect_string_source_type' => SORT_ASC])
                ->all();
            if($connect_string_list)
            {
                $j = 0;
                foreach ($connect_string_list as $connect_string)
                {

                    $connect_string_handbook[$j]['id'] =  $connect_string['connect_string_id'];
                    $connect_string_handbook[$j]['title'] =  Assistant::MarkSearched($search_title,$connect_string['connect_string_title']);
                    $connect_string_handbook[$j]['ip'] =  Assistant::MarkSearched($search_title,$connect_string['connect_string_ip']);
                    $connect_string_handbook[$j]['connectString'] =   Assistant::MarkSearched($search_title,$connect_string['connect_string']);
                    $connect_string_handbook[$j]['settingsDcsId'] =  Assistant::MarkSearched($search_title,$connect_string['Settings_DCS_title']);
                    $connect_string_handbook[$j]['sourceType'] =  Assistant::MarkSearched($search_title,$connect_string['connect_string_source_type']);
                    $j++;
                }
            }
        }
        else
        {
            $errors[] = "Параметры не переданы";
        }
        $result = array('errors' => $errors, 'connect_string' => $connect_string_handbook);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }
}