<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\handbooks;
//ob_start();

use backend\controllers\const_amicum\DepartmentTypeEnum;
use Exception;
use frontend\controllers\HandbookCachedController;
use frontend\models\AccessCheck;
use frontend\models\Company;
use frontend\models\CompanyDepartment;
use frontend\models\Department;
use frontend\models\DepartmentType;
use Throwable;
use Yii;
use yii\db\Query;
use yii\web\Controller;
use yii\web\Response;

class HandbookDepartmentController extends Controller
{
    // GetDepartmentType                        - Получение справочника типов подразделений
    // SaveDepartmentType                       - Сохранение нового типа подразделения
    // DeleteDepartmentType                     - Удаление типа подразделения
    // GetAllParentsCompanies                   - получение списка вышестоящих компаний (нужен для отображения полного пути)
    // GetAllParentsCompaniesWithCompany        - получение списка вышестоящих компаний с учетом переданной (нужен для отображения полного пути)
    // GetParentCompany                         - получение вышестоящей компаний метод рекурсивный
    // getCompanyListInLine                     - построение рекурсивного списка компаний - как подразделений с выводом в один столбец
    // GetParentCompanyWidthAttachment          - получение вышестоящей компаний метод рекурсивный c учетом вложения


    /*
      * Функция построения массива данных
      * Входные параметра отсутствуют.
      * Выходные параметры:
      * - $department_array(array) - массив данных о единицах измерения
      * |-- [i] (array) - ссоциативный массив с информацией об i-той единице измерения
      *     |-- ['id'] - идентификатор подразделения
      *     |-- ['iterator'] - итератор подразделения
      *     |-- ['title'] - название подразделения
      *     |--['short'] - краткое название подразделения
      */


    public function buildArray($search = "")
    {
//        //получить все подразделения
//        $departments = Department::find()->orderBy('title')->all();
//        //объявить пустой массив
//        $department_array = array();
//        $i=0;
//        //в цикле для каждой подразделения
//        foreach ($departments as $department){
//            $department_array[$i] = array();
//            $department_array[$i]['id'] = $department->id;
//            $department_array[$i]['iterator'] = $i + 1;
//            $department_array[$i]['title'] = $department->title;
//            $i++;
//        }
        //вернуть массив
        if ($search == "") {
            $departments = (new Query())
                ->select([
                    'id',
                    'title'
                ])
                ->from('department')
                ->orderBy('title')
                ->all();
        } else {
            $departments = (new Query())
                ->select([
                    'id',
                    'title'
                ])
                ->from('department')
                ->where('title like "%' . $search . '%"')
                ->orderBy('title')
                ->all();
        }
        $model = array();
        $i = 0;
        $lowerSearch = mb_strtolower($search);
        foreach ($departments as $department) {
            $model[$i] = array();
            $model[$i]['id'] = $department['id'];
            $model[$i]['title'] = $this->markSearched($search, $lowerSearch, $department['title']);
            $i++;
        }
        return $model;
    }

    // getCompanyListInLine - построение рекурсивного списка компаний - как подразделений с выводом в один столбец
    // входные параметры:
    //      отсутствуют
    // выходная структура:
    //      id   - ключ компании
    //      title - название с учетом пути компании/подразделения
    // разработал: Якимов М.Н.
    public static function getCompanyListInLine()
    {

        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $result = array();                                                                                              // Массив предупреждений
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'getCompanyListInLine';
        $warnings[] = $method_name . '. Начало метода';
        try {
            //получить список всех компаний
            $all_companies = Company::find()->indexBy('id')->asArray()->all();
            if (!$all_companies) {
                throw new Exception($method_name . '. Справочник компаний пуст. Поиск невозможен');
            }
            $companies = $all_companies;                                                                                // переменная для перебора списка компаний
            $iteration_recursiv = 0;
            $i = 0;
            foreach ($companies as $company) {

                $response = self::GetParentCompanyWidthAttachment($company['id'], $all_companies, $iteration_recursiv);
                $result[$i] = array();
                $result[$i]['id'] = $company['id'];
                $result[$i]['title'] = $response['path'];
                $i++;
            }


        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    public function actionSearchDepartment()
    {
        $errors = array();
        $post = Yii::$app->request->post();                                                                            // Переменная для получения post запросов
        if (isset($post['search'])) {
            $department_list = $this->buildArray($post['search']);
        } else {
            $department_list = $this->buildArray();
        }
        $result = array('errors' => $errors, 'department_list' => $department_list);                                                 // сохраним в массив список ошибок и новый список текстур
        Yii::$app->response->format = Response::FORMAT_JSON;                                                  // формат json
        Yii::$app->response->data = $result;                                                                         // отправляем обратно ввиде ajax формат
    }

    public function markSearched($search, $lowerSearch, $titleSearched)
    {
        $title = "";
        if ($search != "") {
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

    /*
     * Функция начальной загрузки страницы справочника
     * Входные параметры отсутствуют.
     * Выходные параметры:
     * - $department_array(array) - массив данных о единицах измерения
     * |-- [i] (array) - ссоциативный массив с информацией об i-той единице измерения
     *     |-- ['id'] - идентификатор подразделения
     *     |-- ['title'] - название подразделения
     *     |--['short'] - краткое название подразделения
     */
    public function actionIndex()
    {
        $model = $this->buildArray();
        return $this->render('index', ['model' => $model]);
    }

    /*
     * Функция добавления подразделения
     * Входные параметры:
     * - $post['title'] (string) - название подразделения
     * Выходные параметры:
     * - $department_array(array) - массив данных о единицах измерения
     * |-- [i] (array) - ссоциативный массив с информацией об i-той единице измерения
     *     |-- ['id'] - идентификатор подразделения
     *     |-- ['title'] - название подразделения
     */
    public function actionAddDepartment()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 15)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();                                                                             //получение данных от ajax-запроса
                $department = Department::find()->where(['title' => $post['title']])->one();                                    //найти единицу измерения с полученным названием
                if (!$department) {                                                                                               //если нет такой
                    $department = new Department();                                                                             //создать новую единицу измерения
                    $department->title = $post['title'];                                                                        //записать в нее полученные данные
                    if ($department->save()) {                                                                                    //Сохранить модель
                        $model = $this->buildArray();
                        HandbookCachedController::clearDepartmentCache();
//                        echo json_encode($this->buildArray());                                                                  //если сохранилась, вернуть ajax-запросу массив единиц измерения
//                        return ;                                                                                                //завершить выполнение функции
                    } else {                                                                                                       //если не сохранилась
                        $errors[] = "Ошибка сохранения";                                                                               //выдать ошибку
                        $model = $this->buildArray();
                    }
                } else {                                                                                                           //если есть, выдать ошибку
                    $errors[] = "Данное подразделение есть в БД";
                    $model = $this->buildArray();
                }
            } else {
                $errors[] = 'У вас недостаточно прав для выполнения этого действия';
                $model = $this->buildArray();
            }
        } else {
            $errors[] = 'Сессия неактивна';
        }
        $result = array('errors' => $errors, 'department_list' => $model);
        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    /****************** Метод создания подразделения *****************
     *
     *  AddDepartment  -  метод, заполняет 3 таблицы: Company, Department, CompanyDepartment.
     *
     * Company: id - автоинкримент, title - из входного параметра, upper_company_id - из входного параметра
     * Department: id - 1, title - прочее
     * CompanyDepartment: id - берется поле id из Company, department_id-1 , company_id - берется поле id из Company,
     * department_type_id - 5
     *
     *  Входные данные: title   -   название для таблицы company
     *                  upper   -   ключ выше стоящего company
     *
     **Пример:  http://localhost/read-manager-amicum?controller=handbooks\HandbookDepartment&method=AddDepartment&subscribe=&data={}
     **$post_dec = json_decode('{"Items":{"id":31,"title":"Запретная зона №31",
     * Митяева Л.А. 01.09.2019
     */


    const ONE = 1;
    const FIVE = 5;
    const DIF = 'прочее';

    public static function AddDepartment($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                            // Массив ошибок
        $department_data = array();                                                                               // Промежуточный результирующий массив
        $warnings[] = 'AddDepartment. Начало метода';
        try {
            if (!($data_post !== NULL && $data_post !== '')) {
                throw new Exception('AddDepartment. Данные с фронта не получены');
            }
            $warnings[] = 'AddDepartment. Данные успешно переданы';
            $warnings[] = 'AddDepartment. Входной массив данных' . $data_post;
            //$post_dec = json_decode($data_post);
            $post_dec = json_decode('{"Items":{"upper":30,"title":"Подразделение 1"}}');
            $warnings[] = 'AddDepartment. Декодировал входные параметры';
            if (
                !(property_exists($post_dec, 'Items'))
            ) {
                throw new Exception('AddDepartment. Переданы некорректные входные параметры');
            }                                                                                                        // Проверяем наличие в нем нужных нам полей
            $warnings[] = 'AddDepartment. Данные с фронта получены';
            $department_data = $post_dec->Items;
            $title = $department_data->title;
            $upper = $department_data->upper;
            //добавляем сущности для вставки в бд
            $company = new Company();
            $department = new Department();
            $company_department = new CompanyDepartment();
            //заполняем сущность company  сохраняем
            $company->title = $title;
            $company->upper_company_id = $upper;

            if ($company->save()) {
                $warnings[] = 'AddDepartment. Успешное сохранение  данных ';
                $company->refresh();
                $id_new_company = $company->id;
            } else {
                $errors[] = $company->errors;
                throw new \yii\db\Exception('AddDepartment. Ошибка при сохранении данных ');
            }
            //если есть department 1 - прочее то пропускаем, если нет - создаем
            if (Department::findOne(['id' => self::ONE, 'title' => self::DIF])) {
                $warnings[] = 'AddDepartment. Департамент 1 - прочее уже существует';
            } else {
                $department->id = self::ONE;
                $department->title = self::DIF;

                if ($department->save()) {
                    $warnings[] = 'AddDepartment. Успешное сохранение  данных ';
                } else {
                    $errors[] = $department->errors;
                    throw new \yii\db\Exception('AddDepartment. Ошибка при сохранении данных ');
                }
            }
            //заполняем поля для company_department и сохраняем
            $company_department->id = $id_new_company;
            $company_department->department_id = self::ONE;
            $company_department->company_id = $id_new_company;
            $company_department->department_type_id = DepartmentTypeEnum::OTHER;

            if ($company_department->save()) {
                $warnings[] = 'AddDepartment. Успешное сохранение  данных ';
                $company_department->refresh();
            } else {
                $errors[] = $company_department->errors;
                throw new \yii\db\Exception('AddDepartment. Ошибка при сохранении данных ');
            }

            HandbookCachedController::clearDepartmentCache();
        } catch (Throwable $exception) {
            $errors[] = 'AddDepartment. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'AddDepartment. Конец метода';
        $result = $department_data;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    /*
    * Функция редактирования подразделения
    * Входные параметры:
    * - $post['id'] (string) - идентификатор подразделения
    * - $post['title'] (string) - название подразделения
    * Выходные параметры:
    * - $department_array(array) - массив данных о единицах измерения
    * |-- [i] (array) - ссоциативный массив с информацией об i-той единице измерения
    *     |-- ['id'] - идентификатор подразделения
    *     |-- ['title'] - название подразделения
    */
    public function actionEditDepartment()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 16)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                //найти единицу измерения с полученным идентификатором
                $department = Department::findOne($post['id']);
                //если есть такая
                if ($department) {
                    //если задано название
                    if ($post['title']) {
                        //если подразделения с таким названием нет
                        $departmentExisted = Department::find()->where(['title' => $post['title']])->one();
                        if (!$departmentExisted) {
                            //записать это название
                            $department->title = $post['title'];
                        } //иначе
                        else {
                            //выдать ошибку
                            $errors[] = "Такое подразделение уже есть";
                        }
                    }

                    //Сохранить модель
                    if ($department->save()) {   //если модель сохранилась, вернуть ajax-запросу массив единиц измерения
                        $model = $this->buildArray();
//                        echo json_encode($this->buildArray());
//                        return ;
                    } //иначе
                    else {
                        //вернуть ошибку
                        $errors[] = "Ошибка сохранения";
                        $model = $this->buildArray();
//                        return ;
                    }
                } //если нет
                else {
                    //вернуть ошибку
                    $errors[] = "Такого подразделения не существует";
                    $model = $this->buildArray();
                }
            } else {
                $errors[] = "У вас недостаточно прав для выполнения этого действия";
                $model = $this->buildArray();
            }
        } else {
            $errors[] = "Сессия неактивна";
        }
        $result = array('errors' => $errors, 'department_list' => $model);
        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    /*
     *  Функция удаления подразделения
     * Входные параметры:
     * - $post['id'] (string) - идентификатор подразделения
     * Выходные параметры:
     * - $department_array(array) - массив данных о единицах измерения
     * |-- [i] (array) - ссоциативный массив с информацией об i-той единице измерения
     *     |-- ['id'] - идентификатор подразделения
     *     |-- ['title'] - название подразделения
     */
    public function actionDeleteDepartment()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 17)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                //найти единицу измерения по идентификатору
                $department = Department::findOne($post['id']);
                //если модель есть
                if ($department) {
                    //удалить модель. Если удалилась
                    if ($department->delete()) {
                        //вернуть ajax-запросу массив единиц измерения
                        $model = $this->buildArray();
//                        echo json_encode($this->buildArray());
//                        return ;
                    } //иначе
                    else {
                        //вернуть ошибку
                        $errors[] = "Ошибка удаления";
                        $model = $this->buildArray();
                    }
                } //если нет
                else {
                    //вернуть ошибку
                    $errors[] = "Такого подразделения не существует";
                    $model = $this->buildArray();
                }
            } else {
                $errors[] = "У вас недостаточно прав для выполнения этого действия";
                $model = $this->buildArray();
            }
        } else {
            $errors[] = "Сессия неактивна";
        }
        $result = array('errors' => $errors, 'department_list' => $model);
        Yii::$app->response->format = Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    /**
     * Метод получения подразделений
     * Created by: Фидченко М.В. on 06.12.2018 11:15
     */
    public static function GetDepartment()
    {
        $errors = array();
        $departments = (new Query())
            ->select([
                'id',
                'title'
            ])
            ->from('department')
            ->all();
        if (!$departments) {
            $errors[] = "Нет данных в БД";
        }
        $result = array('errors' => $errors, 'department' => $departments);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    /**
     * Метод GetDepartmentType() - Получение справочника типов подразделений
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,                                // идентификатор типа подразделения
     *      "title":"Очистной участок"                // наименование типа подразделения
     * ]
     * warnings:{}                                  // массив предупреждений
     * errors:{}                                    // массив ошибок
     * status:1                                     // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookDepartment&method=GetDepartmentType&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 10:40
     */
    public static function GetDepartmentType()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetDepartmentType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $department_type_data = DepartmentType::find()
                ->asArray()
                ->all();
            if (empty($department_type_data)) {
                $warnings[] = $method_name . '. Справочник типов подразделений пуст';
            } else {
                $result = $department_type_data;
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод SaveDepartmentType() - Сохранение нового типа подразделения
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "department_type":
     *  {
     *      "department_type_id":-1,                                    // идентификатор типа подразделения (-1 = при добавлении нового типа подразделения)
     *      "title":"DEPARTMENT_TYPE_TEST"                                // наименование типа подразделения
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "department_type_id":5,                                        // идентификатор сохранённого типа подразделения
     *      "title":"DEPARTMENT_TYPE_TEST"                                // сохранённое наименование типа подразделения
     * }
     * warnings:{}                                                      // массив предупреждений
     * errors:{}                                                        // массив ошибок
     * status:1                                                         // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookDepartment&method=SaveDepartmentType&subscribe=&data={"department_type":{"department_type_id":-1,"title":"DEPARTMENT_TYPE_TEST"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 10:45
     */
    public static function SaveDepartmentType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveGetDepartmentType';
        $chat_type_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'department_type'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $department_type_id_id = $post_dec->department_type->department_type_id;
            $title = $post_dec->department_type->title;
            $department_type = DepartmentType::findOne(['id' => $department_type_id_id]);
            if (empty($department_type)) {
                $department_type = new DepartmentType();
            }
            $department_type->title = $title;
            if ($department_type->save()) {
                $department_type->refresh();
                $chat_type_data['department_type_id'] = $department_type->id;
                $chat_type_data['title'] = $department_type->title;
            } else {
                $errors[] = $department_type->errors;
                throw new Exception($method_name . '. Ошибка при сохранении нового типа подразделения');
            }
            unset($department_type);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $chat_type_data;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод DeleteDangerLevel() - Удаление типа подразделения
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "department_type_id": 6             // идентификатор удаляемого типа подразделения
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookDepartment&method=DeleteDepartmentType&subscribe=&data={"department_type_id":6}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 10:49
     */
    public static function DeleteDepartmentType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $result = array();                                                                                            // Массив предупреждений
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteDepartmentType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'department_type_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $department_type_id = $post_dec->department_type_id;
            DepartmentType::deleteAll(['id' => $department_type_id]);
            $result = $post_dec;
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    // GetAllParentsCompanies                   - получение списка вышестоящих компаний (нужен для отображения полного пути)
    // $company_id - ключ компании для которой надо построить путь
    public static function GetAllParentsCompanies($company_id)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $result = array();                                                                                              // Массив предупреждений
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'GetAllParentsCompanies';
        $warnings[] = $method_name . '. Начало метода';
        try {
            //получить список всех компаний
            $all_companies = Company::find()->indexBy('id')->asArray()->all();
            if (!$all_companies) {
                throw new Exception($method_name . '. Справочник компаний пуст. Поиск невозможен');
            }
            $iteration_recursiv = 0;
            if (!isset($all_companies[$company_id])) {
                throw new Exception($method_name . '. запрашиваемой компании не существует');
            }

            if ($all_companies[$company_id]['upper_company_id']) {
                $response = self::GetParentCompany($all_companies[$company_id]['upper_company_id'], $all_companies, $iteration_recursiv);
                $result = $response['path'];
                $warnings = $response['result_company'];
            } else {
                $result = $all_companies[$company_id]['title'];
            }

        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * GetAllParentsCompaniesWithCompany - получение списка вышестоящих компаний с учетом переданной (нужен для отображения полного пути)
     * Входные параметры:
     *      company_id      - ключ компании для которой надо построить путь
     * Выходные параметры:
     *      Items           - Путь департамента
     *      ids             - вложенные ключ департаментов
     */
    public static function GetAllParentsCompaniesWithCompany($company_id, $all_companies_hand = null)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $ids = array();
        $result = array();
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'GetAllParentsCompaniesWithCompany';
        $warnings[] = $method_name . '. Начало метода';
        try {
            //получить список всех компаний
            if ($all_companies_hand) {
                $all_companies = $all_companies_hand;
            } else {
                $all_companies = Company::find()->indexBy('id')->asArray()->all();
            }

            if (!$all_companies) {
                throw new Exception($method_name . '. Справочник компаний пуст. Поиск невозможен');
            }
            $iteration_recursiv = 0;
            if (!isset($all_companies[$company_id])) {
                throw new Exception($method_name . '. запрашиваемой компании не существует');
            }

            $response = self::GetParentCompany($company_id, $all_companies, $iteration_recursiv);
            $result = $response['path'];
            $ids = $response['ids'];

        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'ids' => $ids);
    }

    // GetParentCompany - получение вышестоящей компаний метод рекурсивный
    // $upper_company_id        - ключ родительской компании
    // $all_companies           - справочник компаний
    // $iteration_recursiv      - итератор рекурсии (не может быть больше 20)
    public static function GetParentCompany($company_id, $all_companies, $iteration_recursiv)
    {
        $path = "";
        $ids = [];
        $result_company = [];
        $iteration_recursiv++;
        if ($iteration_recursiv < 20) {
            if (isset($all_companies[$company_id])) {
                $company = $all_companies[$company_id];
                $upper_company_id = $company['upper_company_id'];
                if ($upper_company_id) {
                    $response = self::GetParentCompany($upper_company_id, $all_companies, $iteration_recursiv);
                    $result_company = $response['result_company'];
                    $ids = array_merge($ids, $response['ids']);
                    $path = $response['path'];
                }
                $ids[] = $company['id'];
                $result_company[] = $company;
                $path .= $company['title'] . ' / ';
            }
        }

        return ['path' => $path, 'result_company' => $result_company, 'ids' => $ids];
    }

    // GetParentCompanyWidthAttachment - получение вышестоящей компаний метод рекурсивный c учетом вложения
    // входные параметры:
    //      $upper_company_id        - ключ родительсукой компании
    //      $all_companies                 - справочник компаний
    //      $iteration_recursiv      - итератор рекурсии (не может быть больше 20)

    // выходные параметры:
    //      $path - путь до искомой компании
    public static function GetParentCompanyWidthAttachment($company_id, $all_companies, $iteration_recursiv)
    {
        $path = "";
        $result_company = [];
        $iteration_recursiv++;
        if ($iteration_recursiv < 20) {
            if (isset($all_companies[$company_id])) {
                $company = $all_companies[$company_id];
                $upper_company_id = $company['upper_company_id'];
                if ($upper_company_id) {
                    $response = self::GetParentCompanyWidthAttachment($upper_company_id, $all_companies, $iteration_recursiv);
                    $result_company = $response['result_company'];
                    $path = $response['path'];
                }
                $result_company[] = $company;
                $path .= $company['title'] . ' / ';
            }
        }

        return ['path' => $path, 'result_company' => $result_company];
    }
}
