<?php

namespace frontend\controllers\handbooks;
//ob_start();

use DeepCopyTest\Matcher\Y;
use frontend\models\AccessCheck;
use frontend\models\KindParameter;
use frontend\models\Parameter;
use frontend\models\ParameterType;
use frontend\models\Unit;
use Yii;
use yii\db\Query;

class HandbookParameterController extends \yii\web\Controller
{


    // GetParameters                - Cправочник параметров
    // GetAllParameters             - метод получения списка всех параметров сгруппировнанных по видам - для ReadManager
    // GetParametersFromDb          - Метотод получения всех параметров из бд
    // GetKindParameter             - Получение справочника видов параметров
    // SaveKindParameter            - Сохранение нового вида параметра
    // DeleteKindParameter          - Удаление вида параметра
    // GetParameterType             - Получение справочника типов параметров
    // SaveParameterType            - Сохранение нового типа параметра
    // DeleteParameterType          - Удаление типа параметра

    /*
     * Метод отображения страницы справочника параметров
     * Возвращает на страницу списки единиц измерения и видов параметров, а также ассоциативный массив параметров
     */
    public function actionIndex()
    {
        $parametersList = $this->getParameters();
        return $this->render('index', ['parametersList' => $parametersList]);
    }

    /*
     * Функция построения массива параметров
     * Входные параметры отсутствуют.
     * Выходные параметры:
     * - $parameters - (array) массив параметров
     * |--[$i] - (array) - ассоциативный массив свойств $i-го параметра
     *    |--['id'] - (int) - идентификатор $i-го параметра
     *    |--['iterator'] - (int) порядковый номер $i-го параметра
     *    |--['title'] - (string) название $i-го параметра
     *    |--['unit'] - (string) единица измерения $i-го параметра
     *    |--['kind'] - (string) вид $i-го параметра
     */
    public function getParameters($search = "")
    {
        if ($search == "") {
            $parameters = (new Query())
                ->select([
                    'parameter.id id',
                    'parameter.title parameter_title',
                    'unit.id unit_id',
                    'unit.title unit_title',
                    'kind_parameter.id kind_parameter_id',
                    'kind_parameter.title kind_parameter_title'
                ])
                ->from('parameter')
                ->leftJoin('unit', 'parameter.unit_id = unit.id')
                ->leftJoin('kind_parameter', 'parameter.kind_parameter_id = kind_parameter.id')
                ->orderBy('parameter.title')
                ->all();
        } else {
            $parameters = (new Query())
                ->select([
                    'parameter.id id',
                    'parameter.title parameter_title',
                    'unit.id unit_id',
                    'unit.title unit_title',
                    'kind_parameter.id kind_parameter_id',
                    'kind_parameter.title kind_parameter_title'
                ])
                ->from('parameter')
                ->leftJoin('unit', 'parameter.unit_id = unit.id')
                ->leftJoin('kind_parameter', 'parameter.kind_parameter_id = kind_parameter.id')
                ->where('parameter.title like "%' . $search . '%"')
                ->orWhere('unit.title like "%' . $search . '%"')
                ->orWhere('kind_parameter.title like "%' . $search . '%"')
                ->orderBy('parameter.title')
                ->all();
        }
        $lowerSearch = mb_strtolower($search);
        $parameter_array = array();
        $i = 0;
        foreach ($parameters as $parameter) {
            $parameter_array[$i]['id'] = $parameter['id'];
            $parameter_array[$i]['parameter_title'] = $this->markSearched($search, $lowerSearch, $parameter['parameter_title']);
            $parameter_array[$i]['unit_id'] = $parameter['unit_id'];
            $parameter_array[$i]['unit_title'] = $this->markSearched($search, $lowerSearch, $parameter['unit_title']);
            $parameter_array[$i]['kind_parameter_id'] = $parameter['kind_parameter_id'];
            $parameter_array[$i]['kind_parameter_title'] = $this->markSearched($search, $lowerSearch, $parameter['kind_parameter_title']);
            $i++;
        }
        $parameters = $parameter_array;
        return $parameters;
    }

    /*
 * Функция добавения параметра
 * Входные параметры:
 * - $post['title'] - (string) название нового параметра
 * - $post['unit'] - (string) название единицы измерения нового параметра
 * - $post['kind'] - (string) название вида нового параметра
 * Выходные параметры: результат выполнения метода buildArray в формате json
 */
    public function actionAddParameter()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 42)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                //найти параметр с полученным названием
                $parameter = Parameter::find()->where(['title' => $post['title']])->one();
                //если параметр не найден
                if (!$parameter) {
                    //создать новый параметр
                    $parameter = new Parameter();
                    //сохранить название параметра
                    $parameter->title = $post['title'];
                    //если передано назвние единицы измерения
                    if ($post['unit']) {
                        //найти единицу измерения
                        $unit = Unit::findOne(['id' => $post['unit']]);
                        //если найдена
                        if ($unit) {
                            //сохранить единицу измерения
                            $parameter->unit_id = $unit->id;
                        } //если не найдена
                        else {
                            //сообщить об этом
                            $errors[] = "Такой единицы измерения не существует";
                            $model = $this->getParameters();
                        }
                    } //если не передано
                    else {
                        //сообщить об этом
                        $errors[] = "Единица измерения не указана";
                        $model = $this->getParameters();
                    }
                    //если передан вид параметра
                    if ($post['kind']) {
                        //найти вид параметра
                        $kind = KindParameter::findOne(['id' => $post['kind']]);
                        //если найден
                        if ($kind) {
                            //сохранить вид параметра
                            $parameter->kind_parameter_id = $kind->id;
                        } //если не найден
                        else {
                            //сообщить об этом
                            $errors[] = "Такого вида параметров не существует";
                            $model = $this->getParameters();
                        }
                    } //если не передано
                    else {
                        //сообщить об этом
                        $errors[] = "Единица измерения не указана";
                        $model = $this->getParameters();
                    }
                    //если парамтер сохранился
                    if ($parameter->save()) {
                        //получить обновленный массив в формате Json
                        $model = $this->getParameters();
//                        echo json_encode($this->buildArray());
                    } //если не сохранилось
                    else {
                        //сообщить об этом
                        $errors[] = "Ошибка сохранения";
                        $model = $this->getParameters();
                    }
                } //если параметр найден
                else {
                    //сообщить об этом
                    $errors[] = "Такой параметр уже существует";
                    $model = $this->getParameters();
                }
            } else {
                $errors[] = "У вас недостаточно прав для выполнения этого действия";
                $model = $this->getParameters();
            }
        } else {
            $errors[] = "Сессия неактивна";
        }
        $result = array('errors' => $errors, 'parameter_list' => $model);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    /*
     * Функция редактирования параметра
     * Входные параметры:
     * - $post['id'] - (int) идентификатор нового параметра
     * - $post['title'] - (string) название нового параметра
     * - $post['unit'] - (string) название единицы измерения нового параметра
     * - $post['kind'] - (string) название вида нового параметра
     * Выходные параметры: результат выполнения метода buildArray в формате json
     */
    public function actionEditParameter()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 43)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                //найти параметр с полученным идентификатором
                $parameter = Parameter::findOne($post['id']);
                $currentId = $post['id'];
                $currentTitle = (new Query())
                    ->select('title')
                    ->from('parameter')
                    ->where("id = '$currentId'")
                    ->one();
                //если параметр найден
                if ($parameter) {
                    //если передано название параметра
                    if ($post['title']) {
                        $title = strval($post['title']);
                        //найти уже существующий параметр с таким названием
//                        $existingParameter = Parameter::find()->where(['title' => $post['title']])->one();
                        $existingParameter = (new Query())
                            ->select('title')
                            ->from('parameter')
                            ->where("binary title = '$title'")
                            ->one();
                        //если такого параметра нет
                        if (!$existingParameter || $existingParameter == $currentTitle) {
                            //сохранить новое название параметра
                            $parameter->title = $title;
                        } //если такой параметр уже существует
                        else {
                            //сообщить об этом
                            $errors[] = "Такой параметр уже существует";
                            $model = $this->getParameters();
                            //прекратить  выполнение функции
//                            return;
                        }
                    }
                    //если передана единица измерения
                    if ($post['unit']) {
                        //найти единицу измерения с таким названием
                        $unit = Unit::findOne(['id' => $post['unit']]);
                        //привязать единицу измерения к параметру
                        $parameter->unit_id = $unit->id;
                    }
                    //если передан вид параметра
                    if ($post['kind']) {
                        //найти единицу измерения с таким названием
                        $kind = KindParameter::findOne(['id' => $post['kind']]);
                        //привязать единицу измерения к параметру
                        $parameter->kind_parameter_id = $kind->id;
                    }
                    //если параметр сохранился
                    if ($parameter->save()) {
                        //построить обновленный массив
                        $model = $this->getParameters();
//                        echo json_encode($this->buildArray());
                    } //если ен сохранился
                    else {
                        //сообщить об этом
                        $errors[] = "Ошибка сохранения";
                        $model = $this->getParameters();
                        //прекратить выполнение функции
//                        return;
                    }
                } //если параметр не найден
                else {
                    //сообщить об этом
                    $errors[] = "Параметр не найден";
                    $model = $this->getParameters();
                }
            } else {
                $errors[] = "У вас недостаточно прав для выполнения этого действия";
                $model = $this->getParameters();
            }
        } else {
            $errors[] = "Сессия неактивна";
        }
        $result = array('errors' => $errors, 'texture_list' => $model);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    public function actionDeleteParameter()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 44)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                $parameter = Parameter::findOne($post['id']);
                if ($parameter) {
                    if ($parameter->delete()) {
                        $model = $this->getParameters();
//                        echo json_encode($this->buildArray());
                    } else {
                        $errors[] = "Ошибка удаления";
                        $model = $this->getParameters();
                    }
                } else {
                    $errors[] = "Такого параметра не существует";
                    $model = $this->getParameters();
                }
            } else {
                $errors[] = "У вас недостаточно прав для выполнения этого действия";
                $model = $this->getParameters();
            }
        } else {
            $errors[] = "Сессия неактивна";
        }
        $result = array('errors' => $errors, 'parameter_list' => $model);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    public function actionSearchParameter()
    {
        $errors = array();
        $parameter_list = array();                                                                                        // Пустой массив для хранения нового списка текстур
        $post = \Yii::$app->request->post();                                                                            // Переменная для получения post запросов
        if (isset($post['search'])) {
            $parameter_list = $this->getParameters($post['search']);
        } else {
            $parameter_list = $this->getParameters();
        }
        $result = array(['errors' => $errors, 'parameter_list' => $parameter_list]);                                           // сохраним в массив список ошибок и новый список текстур
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;                                                   // формат json
        \Yii::$app->response->data = $result;                                                                          // отправляем обратно ввиде ajax формат
    }

    public function markSearched($search, $lowerSearch, $titleSearched)
    {
        $title = "";
        if ($search != "") {
            // echo $search;
            $titleParts = explode($lowerSearch, mb_strtolower($titleSearched));
            $titleCt = count($titleParts);
            $startIndex = 0;
            $title .= substr($titleSearched, $startIndex, strlen($titleParts[0]));
            $startIndex += strlen($titleParts[0] . $search);
            for ($j = 1; $j < $titleCt; $j++) {
                $title .= "<span class='searched'>" .
                    substr($titleSearched, $startIndex - strlen($search), strlen
                    ($search)) . "</span>" .
                    substr($titleSearched, $startIndex, strlen
                    ($titleParts[$j]));
                $startIndex += strlen($titleParts[$j] . $search);
            }
        } else {
            $title .= $titleSearched;
        }
        return $title;
    }

    public function actionGetUnits()
    {
        $units = (new Query())
            ->select([
                'id',
                'title'
            ])
            ->from('unit')
            ->orderBy('title')
            ->all();
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;                                                   // формат json
        \Yii::$app->response->data = $units;
    }

    public function actionGetKinds()
    {
        $kinds = (new Query())
            ->select([
                'id',
                'title'
            ])
            ->from('kind_parameter')
            ->orderBy('title')
            ->all();
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;                                                   // формат json
        \Yii::$app->response->data = $kinds;
    }

    /**
     * Метод GetAllParameters() - метод получения списка всех параметров сгруппировнанных по видам - для ReadManager
     * @return array - структура выходного массива: [parameter_id]
     *                                                        parameter_id:
     *                                                        parameter_title:
     *                                                        unit_title:
     *                                                        kind_parameter_title:
     *
     * @package frontend\controllers\handbooks
     *
     * @example http://amicum/read-manager-amicum?controller=handbooks\HandbookParameter&method=GetAllParameters&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 08.08.2019 10:37
     */
    public static function GetAllParameters()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $parameters = array();                                                                                         // Промежуточный результирующий массив
        try {
            $found_parameters = Parameter::find()
                ->joinWith('kindParameter')
                ->joinWith('unit')
                ->limit(50000)
                ->all();
            foreach ($found_parameters as $found_parameter) {
                $parameters[$found_parameter->id]['parameter_id'] = $found_parameter->id;
                $parameters[$found_parameter->id]['parameter_title'] = $found_parameter->title;
                $parameters[$found_parameter->id]['unit_short_title'] = $found_parameter->unit->short;
                $parameters[$found_parameter->id]['kind_parameter_title'] = $found_parameter->kindParameter->title;
            }
        } catch (\Throwable $exception) {
            $errors[] = 'GetAllParameters. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $result = $parameters;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * GetParametersFromDb - Метотод получения всех параметров из бд
     * Пример вызова: /read-manager-amicum?controller=handbooks\HandbookParameter&method=GetParametersFromDb&subscribe=&data={}
     *
     */
    public static function GetParametersFromDb()
    {
        $Items = array(); // резутьат выборки из БД
        $status = 1;
        $errors = array();
        $warnings = array();
        try {
            $warnings[] = 'GetParametersFromDb. Начало';
            $Items = Parameter::find()
                ->orderBy('title ASC')
                ->asArray()
                ->all();
            $warnings[] = 'GetParametersFromDb. Конец';
        } catch (\Exception $exception) {
            $errors[] = 'GetParametersFromDb. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $errors[] = $exception->getFile();
        }
        return array('Items' => $Items, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * GetParameterTypesFromDb - Метотод получения всех тип параметров из бд
     * Пример вызова: /read-manager-amicum?controller=handbooks\HandbookParameter&method=GetParameterTypesFromDb&subscribe=&data={}
     */
    public static function GetParameterTypesFromDb()
    {
        $Items = array(); // резутьат выборки из БД
        $status = 1;
        $errors = array();
        $warnings = array();
        try {
            $warnings[] = 'GetParameterTypesFromDb. Начало';
            $Items = ParameterType::find()
                ->orderBy('id ASC')
                ->asArray()
                ->all();
            $warnings[] = 'GetParameterTypesFromDb. Конец';
        } catch (\Exception $exception) {
            $errors[] = 'GetParameterTypesFromDb. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $errors[] = $exception->getFile();
        }
        return array('Items' => $Items, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод GetKindParameter() - Получение справочника видов параметров
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,					    // идентификатор вида параметра
     *      "title":"Общие"				    // наименование вида параметра
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookParameter&method=GetKindParameter&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 12:14
     */
    public static function GetKindParameter()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetKindMishap';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $kind_parameter = KindParameter::find()
                ->asArray()
                ->all();
            if(empty($kind_parameter)){
                $warnings[] = $method_name.'. Справочник видов параметров пуст';
            }else{
                $result = $kind_parameter;
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
     * Метод SaveKindParameter() - Сохранение нового вида параметра
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "kind_parameter":
     *  {
     *      "kind_parameter_id":-1,					                    // идентификатор вида параметра (-1 =  новый вид параметра)
     *      "title":"KIND_PARAMETER_TEST"				                // наименование вида параметра
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "kind_parameter_id":13,					                    // идентификатор сохранённого вида параметра
     *      "title":"KIND_PARAMETER_TEST"				                // сохранённое наименование вида параметра
     * }
     * warnings:{}                                                      // массив предупреждений
     * errors:{}                                                        // массив ошибок
     * status:1                                                         // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookParameter&method=SaveKindParameter&subscribe=&data={"kind_parameter":{"kind_parameter_id":-1,"title":"KIND_PARAMETER_TEST"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 12:16
     */
    public static function SaveKindParameter($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveKindParameter';
        $chat_type_data = array();																				// Промежуточный результирующий массив
        $warnings[] = $method_name.'. Начало метода';
        try
        {
            if ($data_post == NULL && $data_post == '')
            {
                throw new \Exception($method_name.'. Не переданы входные параметры');
            }
            $warnings[] = $method_name.'. Данные успешно переданы';
            $warnings[] = $method_name.'. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name.'. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_parameter'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $kind_parameter_id = $post_dec->kind_parameter->kind_parameter_id;
            $title = $post_dec->kind_parameter->title;
            $kind_parameter = KindParameter::findOne(['id'=>$kind_parameter_id]);
            if (empty($kind_parameter)){
                $kind_parameter = new KindParameter();
            }
            $kind_parameter->title = $title;
            if ($kind_parameter->save()){
                $kind_parameter->refresh();
                $chat_type_data['kind_parameter_id'] = $kind_parameter->id;
                $chat_type_data['title'] = $kind_parameter->title;
            }else{
                $errors[] = $kind_parameter->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении нового вида параметра');
            }
            unset($kind_parameter);
        }
        catch (\Throwable $exception)
        {
            $errors[] = $method_name.'. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name.'. Конец метода';
        $result = $chat_type_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteKindParameter() - Удаление вида параметра
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "kind_parameter_id": 13             // идентификатор удаляемого вида параметра
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookParameter&method=DeleteKindParameter&subscribe=&data={"kind_parameter_id":13}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.03.2020 12:17
     */
    public static function DeleteKindParameter($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteKindParameter';
        $warnings[] = $method_name.'. Начало метода';
        try
        {
            if ($data_post == NULL && $data_post == '')
            {
                throw new \Exception($method_name.'. Не переданы входные параметры');
            }
            $warnings[] = $method_name.'. Данные успешно переданы';
            $warnings[] = $method_name.'. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name.'. Декодировал входные параметры';
            if (!property_exists($post_dec, 'kind_parameter_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $kind_parameter_id = $post_dec->kind_parameter_id;
            $del_kind_parameter = KindParameter::deleteAll(['id'=>$kind_parameter_id]);
        }
        catch (\Throwable $exception)
        {
            $errors[] = $method_name.'. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name.'. Конец метода';
        $result = $post_dec;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод GetParameterType() - Получение справочника типов параметров
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,					        // идентификатор типа параметра
     *      "title":"Справочный"				// наименование типа параметра
     * ]
     * warnings:{}                              // массив предупреждений
     * errors:{}                                // массив ошибок
     * status:1                                 // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookParameter&method=GetParameterType&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.03.2020 11:48
     */
    public static function GetParameterType()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetParameterType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $parameter_type = ParameterType::find()
                ->asArray()
                ->all();
            if(empty($parameter_type)){
                $warnings[] = $method_name.'. Справочник типов параметров пуст';
            }else{
                $result = $parameter_type;
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
     * Метод SaveParameterType() - Сохранение нового типа параметра
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "parameter_type":
     *  {
     *      "parameter_type_id":-1,					                    // идентификатор типа параметра (-1 =  новый тип параметра)
     *      "title":"PARAMETER_TYPE_TEST"				                // наименование типа параметра
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "parameter_type_id":6,					                    // идентификатор сохранённого типа параметра
     *      "title":"PARAMETER_TYPE_TEST"				                // сохранённое наименование типа параметра
     * }
     * warnings:{}                                                      // массив предупреждений
     * errors:{}                                                        // массив ошибок
     * status:1                                                         // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookParameter&method=SaveParameterType&subscribe=&data={"parameter_type":{"parameter_type_id":-1,"title":"PARAMETER_TYPE_TEST"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.03.2020 11:50
     */
    public static function SaveParameterType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveParameterType';
        $chat_type_data = array();																				// Промежуточный результирующий массив
        $warnings[] = $method_name.'. Начало метода';
        try
        {
            if ($data_post == NULL && $data_post == '')
            {
                throw new \Exception($method_name.'. Не переданы входные параметры');
            }
            $warnings[] = $method_name.'. Данные успешно переданы';
            $warnings[] = $method_name.'. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name.'. Декодировал входные параметры';
            if (!property_exists($post_dec, 'parameter_type'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $parameter_type_id = $post_dec->parameter_type->parameter_type_id;
            $title = $post_dec->parameter_type->title;
            $parameter_type = ParameterType::findOne(['id'=>$parameter_type_id]);
            if (empty($parameter_type)){
                $parameter_type = new ParameterType();
            }
            $parameter_type->title = $title;
            if ($parameter_type->save()){
                $parameter_type->refresh();
                $chat_type_data['parameter_type_id'] = $parameter_type->id;
                $chat_type_data['title'] = $parameter_type->title;
            }else{
                $errors[] = $parameter_type->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении нового типа параметра');
            }
            unset($parameter_type);
        }
        catch (\Throwable $exception)
        {
            $errors[] = $method_name.'. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name.'. Конец метода';
        $result = $chat_type_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteParameterType() - Удаление типа параметра
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "parameter_type_id": 6             // идентификатор удаляемого типа параметра
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookParameter&method=DeleteParameterType&subscribe=&data={"parameter_type_id":6}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 26.03.2020 11:52
     */
    public static function DeleteParameterType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteKindParameter';
        $warnings[] = $method_name.'. Начало метода';
        try
        {
            if ($data_post == NULL && $data_post == '')
            {
                throw new \Exception($method_name.'. Не переданы входные параметры');
            }
            $warnings[] = $method_name.'. Данные успешно переданы';
            $warnings[] = $method_name.'. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name.'. Декодировал входные параметры';
            if (!property_exists($post_dec, 'parameter_type_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $parameter_type_id = $post_dec->parameter_type_id;
            $del_parameter_type = ParameterType::deleteAll(['id'=>$parameter_type_id]);
        }
        catch (\Throwable $exception)
        {
            $errors[] = $method_name.'. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name.'. Конец метода';
        $result = $post_dec;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }
}
