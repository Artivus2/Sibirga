<?php

namespace frontend\controllers\handbooks;
//ob_start();

use frontend\controllers\Assistant;
use frontend\models\AccessCheck;
use frontend\models\Status;
use frontend\models\StatusType;
use Yii;
use yii\db\Query;
use yii\web\Response;

class HandbookStatusController extends \yii\web\Controller
{
    const STATUS_TYPE_RTN = 12;
    const STATUS_TYPE_PС = 11;

    public function actionIndex()
    {
        $model = $this->buildArray();
        $statusTypes = StatusType::find()
            ->select(['title', 'id'])
            ->asArray()->all();
        return $this->render('index', [
            'model' => $model,
            'statusType' =>$statusTypes,
        ]);
    }

    public function buildArray()
    {
        $status = Status::find()->orderBy('title')->all();
        $model = array();
        $i = 0;
        foreach ($status as $stats){
            $model[$i] = array();
            $model[$i]['iterator'] = $i + 1;
            $model[$i]['id'] = $stats->id;
            $model[$i]['title'] = $stats->title;
            $model[$i]['trigger'] = $stats->trigger;
            $model[$i]['statusTypeId'] = $stats->status_type_id;
            if($statusTypeTitle = $stats->statusType){
                $model[$i]['statusTypeTitle'] = $statusTypeTitle->title;
            }
            $i++;
        }
        return $model;
    }

    public function actionAddStatus()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if(isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 63)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();
                $stats = Status::findOne(['title'=>$post['title']]);
                if(!$stats){
                    $stats = new Status();
                    $stats->title = $post['title'];
                    if($post['trigger'] !== ""){
                        $stats->trigger = $post['trigger'];
                    }
                    else {
                        $stats->trigger = "-";
                    }
                    if(isset($post['statusType'])){
                        $statusType = StatusType::findOne($post['statusType']);
                        if($statusType){
                            $stats->status_type_id = $post['statusType'];
                        }
                        else{
                            $errors[] = "Нет такого типа статуса";
                            $model = $this->buildArray();
                        }
                    }
                    else {
                        $errors[] = "Тип статуса не передан";
                        $model = $this->buildArray();
                    }
                    if($stats->save()){
                        $model = $this->buildArray();
//                        echo json_encode($model);
                    }
                    else{
                        $errors[] = "Добавление не удалось";
                        $model = $this->buildArray();
                    }
                }
                else {
                    $errors[] = "Статус с таким названием уже существует";
                    $model = $this->buildArray();
                }
            }
            else{
                $errors[] = "У вас недостаточно доступа для выполнения этого действия";
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

    public function actionEditStatus()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if(isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 64)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();
                $stats = Status::findOne((int)$post['id']);
                if($stats){
                    $existingStatus = Status::findOne(['title'=>$post['title']]);
                    if(!$existingStatus || $existingStatus->id === $stats->id){
                        $stats->title = (string)$post['title'];
                        if($post['trigger'] !== ""){
                            $stats->trigger = (string)$post['trigger'];
                        }
                        else {
                            $stats->trigger = "-";
                        }
                        if(isset($post['statusType'])){
                            $stats->status_type_id = (int)$post['statusType'];
                        }
                        else{
                            $errors[] = "Нет такого типа статуса";
//                            $model = $this->buildArray();
                        }
                        if(!$stats->save()){
                            $errors[] = "Не удалось сохранить статус";
                        }
                    }
                    else{
                        $errors[] = "Статус с таким названием уже существует";
//                        $model = $this->buildArray();
                    }
                }
                else{
                    $errors[] = "Статус не найден";
//                    $model = $this->buildArray();
                }
            }
            else{
                $errors[] = "У вас недостаточно прав для выполнения этого действия";
//                $model = $this->buildArray();
            }
        }
        else{
            $errors[] = "Сессия неактивна";
        }
        $model = $this->buildArray();
        $result = array('errors' => $errors, 'model' => $model);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    public function actionDeleteStatus()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if(isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 65)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();
                $stats = Status::findOne($post['id']);
                if($stats){
                    if($stats->delete()) {
                        $model = $this->buildArray();
//                        echo json_encode($model);
                    }
                    else {
                        $errors[] = "Удаление не удалось";
                        $model = $this->buildArray();
                    }
                }
                else {
                    $errors[] = "Такого статуса нет";
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

    /**
     * Метод поиска cтатусов с выделением найденного
     * Created by: Одилов О.У. on 07.11.2018 10:06
     */
    public function actionMarkSearchStatus()
    {
        $post = Yii::$app->request->post();
        $errors = array();
        $status_handbook = array();
        if(isset($post['search_title']))
        {
            $search_title = $post['search_title'];
            $sql_condition = "status_title LIKE '%$search_title%' OR view_status_main.trigger LIKE '%$search_title%' OR status_type_title LIKE '%$search_title%'";
            $statuses = (new Query())
                ->select(['status_id', 'status_title', 'view_status_main.trigger', 'status_type_title', 'status_type_id'])
                ->from('view_status_main')
                ->where($sql_condition)
                ->orderBy('status_title ASC')
                ->all();
            $index = 0;
            foreach ($statuses as $status)
            {
                $status_handbook[$index]['id'] = $status['status_id'];
                $status_handbook[$index]['title'] = Assistant::MarkSearched($search_title, $status['status_title']);
                $status_handbook[$index]['trigger'] = Assistant::MarkSearched($search_title, $status['trigger']);
                $status_handbook[$index]['statusTypeTitle'] = Assistant::MarkSearched($search_title, $status['status_type_title']);
                $status_handbook[$index]['statusTypeId'] = $status['status_type_id'];
                $index++;
            }
        }
        $result = array('errors' => $errors, 'statuses' => $status_handbook);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * Метод GetStatusForFilterRTN() - Справочник статусов РТН
     * @return array  - выходной массив  [status_id]
     *                                          status_id:
     *                                          status_title:
     *
     * @package frontend\controllers\handbooks
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\HandbookStatus&method=GetStatusForFilterRTN&subscribe=data&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 15.08.2019 8:20
     */
    public static function GetStatusForFilterRTN()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $status_filter = array();                                                                                         // Промежуточный результирующий массив
        try
        {
            $status_filter = Status::find()
                ->select(['status.id as status_id', 'status.title as status_title'])
                ->leftJoin('status_type','status_type.id = status.status_type_id')
                ->where(['status_type.id'=>self::STATUS_TYPE_RTN])
                ->indexBy('status_id')
                ->asArray()
                ->limit(50000)
                ->all();
        }
        catch (\Throwable $exception)
        {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $result = $status_filter;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     * Метод GetStatusForFilterPC() - Получение статусов ПК
     * @return array  - выходной массив  [status_id]
     *                                          status_id:
     *                                          status_title:
     *
     * @package frontend\controllers\handbooks
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\HandbookStatus&method=GetStatusForFilterPC&subscribe=data&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 15.08.2019 8:21
     */
    public static function GetStatusForFilterPC()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $status_filter = array();                                                                                         // Промежуточный результирующий массив
        try
        {
            $status_filter = Status::find()
                ->select(['status.id as status_id', 'status.title as status_title'])
                ->leftJoin('status_type','status_type.id = status.status_type_id')
                ->where(['status_type.id'=>self::STATUS_TYPE_PС])
                ->indexBy('status_id')
                ->asArray()
                ->limit(50000)
                ->all();
        }
        catch (\Throwable $exception)
        {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $result = $status_filter;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }
    // GetListStatus - получить список  статусов
    // пример: http://127.0.0.1/read-manager-amicum?controller=handbooks\HandbookStatus&method=GetListStatus&subscribe=&data={}
    // разработал Якимов М.Н. 24.09.2019г.
    public static function GetListStatus($data_post = NULL)
    {
        $status = 1;                                                                                                 // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();                                                                                                // Массив ошибок

        try {
            $status_list = Status::find()
                ->limit(20000)
                ->indexBy('id')
                ->asArray()
                ->all();

            if (!$status_list) {
                $warnings[] = 'GetListStatus. Справочник статусов пуст';
                $result = (object)array();
            } else {
                $result = $status_list;
            }
        } catch (\Throwable $exception) {
            $warnings[] = 'GetListStatus. Исключение.';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }
}
