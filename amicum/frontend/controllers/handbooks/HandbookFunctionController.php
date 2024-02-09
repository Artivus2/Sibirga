<?php

namespace frontend\controllers\handbooks;
//ob_start();
use frontend\controllers\Assistant;
use frontend\models\AccessCheck;
use frontend\models\Func;
use frontend\models\FunctionParameter;
use frontend\models\FunctionType;
use frontend\models\Parameter;
use frontend\models\ParameterType;
use frontend\web;
use Yii;
use yii\db\Query;
use yii\web\Response;

class HandbookFunctionController extends \yii\web\Controller
{
    public function actionIndex()
    {
        $model = $this->buildArray();
        $functionParametersArray = $this->buildFunctionParameters();
        $parameterId = Parameter::find()
            ->select(['title', 'id'])
            ->asArray()->all();
        $parameterType = FunctionParameter::find()
            ->select(['parameter_type'])
            ->groupBy(['parameter_type'])
            ->asArray()->all();
        $parameterTypeId = ParameterType::find()
            ->select(['title', 'id'])
            ->asArray()->all();
        return $this->render('index', [
            'functionParameters' => $functionParametersArray,
            'parameterId' => $parameterId,
            'parameterType' => $parameterType,
            'parameterTypeId' => $parameterTypeId,
            'model' => $model,
        ]);
    }

    public function buildArray()
    {
        $funcTypes = FunctionType::find()->orderBy('id')->all();
        $model = array();
        $i = 0;
        foreach($funcTypes as $funcType){
            $model[$i] = array();
            $model[$i]['iterator'] = $i+1;
            $model[$i]['id'] = $funcType->id;
            $model[$i]['title'] = $funcType->title;
            $j = 0;
            if($funcs = $funcType->getFuncs()->orderBy(['title'=>SORT_ASC])->all()){

                foreach($funcs as $func)
                {
                    $model[$i]['function'][$j]['iterator'] = $j+1;
                    $model[$i]['function'][$j]['id'] = $func->id;
                    $model[$i]['function'][$j]['title'] = $func->title;
                    $model[$i]['function'][$j]['functionTypeId'] = $func->function_type_id;
                    $model[$i]['function'][$j]['functionScriptName'] = $func->func_script_name;
                    $model[$i]['function'][$j]['hasParameters'] = $func->functionParameters ? true : false;
                    $j++;
                }
            }
            else {
                $model[$i]['function'] = array();
            }
            $i++;
        }
        return $model;
    }

    public function buildFunctionParameters()
    {
        $functionParameters = FunctionParameter::find()->all();
        $functionParametersArray = array();
        $i = 0;
        foreach($functionParameters as $functionParameter){
            $functionParametersArray[$i] = array();
            $functionParametersArray[$i]['iterator'] = $i+1;
            $functionParametersArray[$i]['id'] = $functionParameter->id;
            $functionParametersArray[$i]['functionId'] = $functionParameter->function_id;
            $functionParametersArray[$i]['parameterId'] = $functionParameter->parameter_id;
            if($parameterName = $functionParameter->parameter)
            {
                $functionParametersArray[$i]['parameterTitle'] = $parameterName->title;
            }
            $functionParametersArray[$i]['parameterType'] = $functionParameter->parameter_type;
            if($parameterTypeName = $functionParameter->parameterType)
            {
                $functionParametersArray[$i]['parameterTypeTitle'] = $parameterTypeName->title;
            }
            $functionParametersArray[$i]['ordinalNumber'] = $functionParameter->ordinal_number;
            $functionParametersArray[$i]['parameterTypeId'] = $functionParameter->parameter_type_id;
            $i++;
        }
        return $functionParametersArray;
    }

    public function actionAddFunctionType()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if(isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 32)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();
                $funcType = FunctionType::findOne(['title'=>$post['title']]);
                if(!$funcType){
                    $funcType = new FunctionType();
                    $funcType->title = $post['title'];
                    if($funcType->save()){
                        $model = $this->buildArray();
                    }
                    else{
                        $errors[] = "Добавление не удалось";
                        $model = $this->buildArray();
                    }
                }
                else{
                    $errors[] = "Такой тип функции уже существует";
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
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;                                                   // формат json
        Yii::$app->response->data = $result;
    }

    public function actionEditFunctionType()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if(isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 33)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();
                $funcType = FunctionType::findOne($post['id']);
                if($funcType){
                    $existingFuncType = FunctionType::findOne(['title'=>$post['title']]);                               // находим тип функции по полученному новому названию
                    if(!$existingFuncType || $funcType->id === $existingFuncType->id){                                                                             // если такой тип функции нет, то добавим
                        $funcType->title = $post['title'];
                        if($funcType->save()){
                            $model = $this->buildArray();
                        }
                        else{
                            $errors[] = "Редактирование не удалось";
                            $model = $this->buildArray();
                        }
                    }
                    else{
                        $errors[] = "Тип функции с таким названием уже существует";
                        $model = $this->buildArray();
                    }
                }
                else{
                    $errors[] = "Такого типа функции не существует";
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
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;                                                   // формат json
        Yii::$app->response->data = $result;
    }

    public function actionDeleteFunctionType()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if(isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 34)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();
                $funcType = FunctionType::findOne($post['id']);
                if($funcType){
                    if($funcType->delete()){
                        $model = $this->buildArray();
                    }
                    else{
                        $errors[] = "Удаление не удалось";
                        $model = $this->buildArray();
                    }
                }
                else{
                    $errors[] = "Такого типа функции не существует";
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
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;                                                   // формат json
        Yii::$app->response->data = $result;
    }

    public function actionAddFunction()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if(isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 35)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();
                $func = Func::findOne(['title'=>$post['title']]);
                if(!$func){
                    $func = new Func();
                    $func->title = strval($post['title']);
                    $func->function_type_id = (int)$post['id'];
                    $func->func_script_name = strval($post['functionScriptName']);
                    if(!$func->save()){
                        $errors[] = "Добавление не удалось";
                    }
                    $model = $this->buildArray();
                }
                else{
                    $errors[] = "Такая функция уже существует";
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
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;                                                   // формат json
        Yii::$app->response->data = $result;
    }

    public function actionEditFunction()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if(isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 36)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();
                $func = Func::findOne($post['id']);
                if($func){
                    $existingFunc = Func::findOne(['title'=>['title']]);
                    if(!$existingFunc || $existingFunc->id === $func->id){
                        $func->title = $post['title'];
                        $func->func_script_name = $post['functionScriptName'];
                    }
                    if($func->save()){
                        $model = $this->buildArray();
                    }
                    else{
                        $errors[] = "Редактирование функции не удалось";
                        $model = $this->buildArray();
                    }
                }
                else{
                    $errors[] = "Такой функции не существует";
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
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;                                                   // формат json
        Yii::$app->response->data = $result;
    }

    public function actionDeleteFunction()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if(isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 37)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();
                $func = Func::findOne($post['id']);
                if($func){
                    if(!$func->delete()){
                        $errors[] = "Удаление не удалось";
                    }
                }
                else{
                    $errors[] = "Нет такой функции";
                }
            }
            else{
                $errors[] = "У вас недостаточно прав для выполнения этого действия";
            }
        }
        else{
            $errors[] = "Сессия неактивна";
        }
        $model = $this->buildArray();
        $result = array('errors' => $errors, 'model' => $model);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;                                                   // формат json
        Yii::$app->response->data = $result;
    }

    /*
     * Функция сохранения параметров функции
     * Входные параметры
     * - $post['functionId'] (int) - id датчика
     * - $post['parameterIds'] (string) - id изменяемых параметров через разделитель ☭
     * - $post['parameterTypeIds'] (string) - id типов изменяемых параметров через разделитель ☭
     * - $post['inOuts'] (string) - направления срабатывания уставок параметров через разделитель ☭
     */
    public function actionSaveParameters()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if(isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 38)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();
                if(
                    isset($post['functionId']) &&
                    isset($post['parameterIds']) &&
                    isset($post['parameterTypeIds']) &&
                    isset($post['inOuts'])
                ){
                    $func = Func::findOne(($post['functionId']));
                    if(!$func){
                        echo "Такой функции не существует";
                        return;
                    }
                    $parameterIds = explode("☭",$post['parameterIds']);
                    $parameterTypeIds = explode("☭", $post['parameterTypeIds']);
                    $inOuts = explode("☭",$post['inOuts']);
                    $countPar = count($parameterIds);
                    if(count($parameterTypeIds) == $countPar && count($inOuts) == $countPar){
                        $ordinalNumbers = array();
                        for($i=0; $i<$countPar; $i++)
                            $ordinalNumbers[$i] = $i+1;
                        foreach ($func->functionParameters as $functionParameter){
                            $wasDeleted = false;
                            for($i=0; $i<$countPar; $i++){
                                if($functionParameter->parameter_id == $parameterIds[$i] &&
                                    $functionParameter->parameter_type_id == $parameterTypeIds[$i]){
                                    $functionParameter->ordinal_number = $ordinalNumbers[$i];
                                    $functionParameter->parameter_type = $inOuts[$i];
                                    $functionParameter->save();
                                    array_splice($parameterIds, $i, 1);
                                    array_splice($parameterTypeIds, $i, 1);
                                    array_splice($inOuts, $i, 1);
                                    array_splice($ordinalNumbers, $i, 1);
                                    $countPar = count($parameterIds);
                                    $wasDeleted = true;
                                    break;
                                }
                            }
                            if(!$wasDeleted){
                                $functionParameter->delete();
                            }
                        }
                        for($i=0; $i<$countPar; $i++){
                            if($parameterIds[$i]!='' && $parameterTypeIds[$i]!='' && $inOuts[$i]!=''){
                                $functionParameter = new FunctionParameter();
                                $functionParameter->function_id = $post['functionId'];
                                $functionParameter->parameter_id = $parameterIds[$i];
                                $functionParameter->parameter_type_id = $parameterTypeIds[$i];
                                $functionParameter->parameter_type = $inOuts[$i];
                                $functionParameter->ordinal_number = $ordinalNumbers[$i];
                                if(!$functionParameter->save()){
                                    echo "Функция не привязана к параметру";
                                    return;
                                }
                            }
                        }
                    }
                    else{
                        echo "Количество данных не совпадает";
                        return;
                    }
                    $functionParameters = $this->buildFunctionParameters();
                    $model = $this->buildArray();
                    $result = array(
                        'model' => $model,
                        'functionParameters' => $functionParameters
                    );
                    echo json_encode($result);
                    return;
                }
                else{
                    echo "Данные не переданы";
                    return;
                }
            }
            else{
                echo "У вас недостаточно прав для выполнения этого действия";
            }
        }
        else{
            echo "Сессия неактивна";
        }
    }

    public function actionDeleteParameters()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if(isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 37)) {                                                //если пользователю разрешен доступ к функции
                $post = Assistant::GetServerMethod();
                $func = FunctionParameter::findOne($post['id']);
                if($func){
                    if(!$func->delete()){
                        $errors[] = "Удаление не удалось";
                    }
                }
                else{
                    $errors[] = "Нет такого параметра";
                }
            }
            else{
                $errors[] = "У вас недостаточно прав для выполнения этого действия";
            }
        }
        else{
            $errors[] = "Сессия неактивна";
        }
        $model = $this->buildArray();
        $result = array('errors' => $errors, 'model' => $model);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;                                                   // формат json
        Yii::$app->response->data = $result;
    }

    public function actionMarkSearchFunction()
    {
        $post = Yii::$app->request->post();
        $errors = array();
        $function_handbook = array();
        if(isset($post['search_title']))
        {
            $search_title = $post['search_title'];
            if(!isset($search_title))
            {
                $sql_condition = "";
            }
            else
            {
                $sql_condition = "func.title like '%$search_title%' OR func.func_script_name like '%$search_title%'";
            }

            $function_handbook_list = (new Query())
                ->select([
                    'func.id',
                    'func.title',
                    'func.function_type_id',
                    'func.func_script_name',
                    'function_type.title as function_type_title'
                ])
                ->from('func')
                ->leftJoin('function_type', 'function_type.id = func.function_type_id')
                ->where($sql_condition)
                ->orderBy(['func.function_type_id' => SORT_ASC])
                ->all();
            if($function_handbook_list)
            {
                $index = -1;
                $j = 0;
//                $flag = false;
                foreach ($function_handbook_list as $function)
                {
                    $function_type_id = $function['function_type_id'];
                    if ($index == -1 OR $function_handbook[$index]['id'] != $function_type_id)
                    {
                        $index++;
                        $function_handbook[$index]['id'] = $function_type_id;
                        $function_handbook[$index]['title'] = $function['function_type_title'];
                        $j = 0;
                    }
                    if($function['id'] != '')
                    {
                        $function_handbook[$index]['function'][$j]['id'] =  $function['id'];
                        $function_handbook[$index]['function'][$j]['title'] =  Assistant::MarkSearched($search_title,$function['title']);
                        $function_handbook[$index]['function'][$j]['functionScriptName'] =  Assistant::MarkSearched($search_title,$function['func_script_name']);
                        $j++;
                    }
                    else {
                        $function_handbook[$index]['function'] = array();
                    }
                }
            }
        }
        else
        {
            $errors[] = "Параметры не переданы";
        }
        $result = array('errors' => $errors, 'function' => $function_handbook);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

}
