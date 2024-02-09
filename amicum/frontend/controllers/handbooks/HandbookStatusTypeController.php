<?php

namespace frontend\controllers\handbooks;
//ob_start();
use frontend\controllers\Assistant;
use frontend\models\AccessCheck;
use frontend\models\StatusType;
use Yii;
use yii\db\Query;
use yii\web\Response;

class HandbookStatusTypeController extends \yii\web\Controller
{

    // GetStatusType()      - Получение справочника типов статуса
    // SaveStatusType()     - Сохранение справочника типов статуса
    // DeleteStatusType()   - Удаление справочника типов статуса

    public function actionIndex()
    {
        $model = $this->buildArray();
        return $this->render('index', [
            'model' => $model,
        ]);
    }

    public function buildArray()
    {
        $statusTypes = StatusType::find()->orderBy('title')->all();
        $model = array();
        $i = 0;
        foreach($statusTypes as $statusType){
            $model[$i] = array();
            $model[$i]['iterator'] = $i + 1;
            $model[$i]['id'] = $statusType->id;
            $model[$i]['title'] = $statusType->title;
            $i++;
        }
        return $model;
    }

    public function actionAddStatusType()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if(isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 66)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();
                $statusType = StatusType::findOne(['title'=>$post['title']]);
                if(!$statusType){
                    $statusType = new StatusType();
                    $statusType->title = $post['title'];
                    if($statusType->save()){
                        $model = $this->buildArray();
//                        echo json_encode($model);
                    }
                    else{
                        $errors[] = "Добавление не  удалось";
                        $model = $this->buildArray();
                    }
                }
                else{
                    $errors[] = "Тип статуса с таким названием уже существует";
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

    public function actionEditStatusType()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if(isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 67)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();
                $statusType = StatusType::findOne([$post['id']]);
                if($statusType){
                    $existingStatusType = StatusType::findOne(['title'=>$post['title']]);
                    if(!$existingStatusType || $existingStatusType->id === $statusType->id){
                        $statusType->title = $post['title'];
                        if($statusType->save()){
                            $model = $this->buildArray();
//                            echo json_encode($model);
                        }
                        else{
                            $errors[] = "Ошибка редактирования";
                            $model = $this->buildArray();
                        }
                    }
                    else{
                        $errors[] = "Тип статуса с таким названием уже существует";
                        $model = $this->buildArray();
                    }
                }
                else{
                    $errors[] = "Тип статуса не найден";
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

    public function actionDeleteStatusType()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if(isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 67)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();
                $statusType = StatusType::findOne($post['id']);
                if($statusType){
                    if($statusType->delete()){
                        $model = $this->buildArray();
                    }
                    else{
                        $errors[] = "Ошибка удаления";
                        $model = $this->buildArray();
                    }
                }
                else{
                    $errors[] = "Тип статуса не найден";
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

    public function actionMarkSearchStatusType()
    {
        $post = Yii::$app->request->post();
        $errors = array();
        $status_type_handbook = array();
        if(isset($post['search_title']))
        {
            $search_title = $post['search_title'];
            $sql_condition = "title like '%$search_title%'";
            $status_type_list = (new Query())
                ->select([
                    'id',
                    'title'
                ])
                ->from('status_type')
                ->where($sql_condition)
                ->orderBy(['id' => SORT_ASC])
                ->all();
            if($status_type_list)
            {

                $j = 0;
//                $flag = false;
                foreach ($status_type_list as $status_type)
                {
                    $status_type_handbook[$j]['id'] =  $status_type['id'];
                    $status_type_handbook[$j]['title'] =  Assistant::MarkSearched($search_title,$status_type['title']);
                    $j++;
                }
            }
        }
        else
        {
            $errors[] = "Параметры не переданы";
        }
        $result = array('errors' => $errors, 'status_type' => $status_type_handbook);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }


    // GetStatusType()      - Получение справочника типов статуса
    // SaveStatusType()     - Сохранение справочника типов статуса
    // DeleteStatusType()   - Удаление справочника типов статуса

    /**
     * Метод GetStatusType() - Получение справочника типов статуса
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                        // ключ справочника
     *      "title":"ACTION",                // название справочника
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=GetStatusType&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetStatusType()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetStatusType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_status_type = StatusType::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_status_type)) {
                $result = (object) array();
                $warnings[] = $method_name . '. Справочник типов статуса пуст';
            } else {
                $result = $handbook_status_type;
            }
        } catch (\Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод SaveStatusType() - Сохранение справочника типов статуса
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "status_type":
     *  {
     *      "status_type_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "status_type_id":-1,            // ключ справочника
     *      "title":"ACTION",                // название справочника
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=SaveStatusType&subscribe=&data={"status_type":{"status_type_id":-1,"title":"ACTION"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveStatusType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveStatusType';
        $handbook_status_type_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'status_type'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_status_type_id = $post_dec->status_type->status_type_id;
            $title = $post_dec->status_type->title;
            $new_handbook_status_type_id = StatusType::findOne(['id' => $handbook_status_type_id]);
            if (empty($new_handbook_status_type_id)) {
                $new_handbook_status_type_id = new StatusType();
            }
            $new_handbook_status_type_id->title = $title;
            if ($new_handbook_status_type_id->save()) {
                $new_handbook_status_type_id->refresh();
                $handbook_status_type_data['status_type_id'] = $new_handbook_status_type_id->id;
                $handbook_status_type_data['title'] = $new_handbook_status_type_id->title;
            } else {
                $errors[] = $new_handbook_status_type_id->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении справочника типов статуса');
            }
            unset($new_handbook_status_type_id);
        } catch (\Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_status_type_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteStatusType() - Удаление справочника типов статуса
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "status_type_id": 98             // идентификатор справочника типов статуса
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=DeleteStatusType&subscribe=&data={"status_type_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteStatusType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteStatusType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'status_type_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_status_type_id = $post_dec->status_type_id;
            $del_handbook_status_type = StatusType::deleteAll(['id' => $handbook_status_type_id]);
        } catch (\Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $post_dec;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }
}
