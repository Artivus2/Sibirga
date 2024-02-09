<?php
/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\handbooks;
//ob_start();

//классы и контроллеры yii2
use frontend\models\AccessCheck;
use frontend\models\Unit;
use Yii;
use yii\db\Query;
use yii\web\Controller;
use yii\web\Response;

//модели из БД
//модели без БД

class HandbookUnitController extends Controller
{
        // GetUnitList - справочник единиц измерения - массив

    /*
     * Функция построения массива данных
     * Входные параметра отсутствуют.
     * Выходные параметры:
     * - $unit_array(array) - массив данных о единицах измерения
     * |-- [i] (array) - ссоциативный массив с информацией об i-той единице измерения
     *     |-- ['id'] - идентификатор единицы измерения
     *     |-- ['iterator'] - итератор единицы измерения
     *     |-- ['title'] - название единицы измерения
     *     |--['short'] - краткое название единицы измерения
     */
    public function buildArray($search = "")
    {
        //получить все единицы измерения
        $units = null;
        if ($search == "") {
            $units = (new Query())
                ->select([
                    'unit.id id',
                    'unit.title title',
                    'unit.short short'
                ])
                ->from(['unit'])
                ->orderBy(['title' => SORT_ASC])
                ->all();
        } else {
            $units = (new Query())
                ->select([
                    'unit.id id',
                    'unit.title title',
                    'unit.short short'
                ])
                ->from(['unit'])
                ->where('unit.title like "%' . $search . '%"')
                ->orWhere('unit.short like "%' . $search . '%"')
                ->orderBy(['title' => SORT_ASC])
                ->all();
        }
        $model = array();
        $i = 0;
        $lowerSearch = mb_strtolower($search);
        foreach ($units as $unit) {
            $model[$i] = array();
            $model[$i]['iterator'] = $i + 1;
            $model[$i]['id'] = $unit['id'];
            $model[$i]['title'] = self::markSearched($search, $lowerSearch, $unit['title']);
            $model[$i]['short'] = self::markSearched($search, $lowerSearch, $unit['short']);
            $i++;
        }
        return $model;
    }

    public function actionSearchUnit()
    {
        $errors = array();
        $unit_list = array();                                                                                           // Пустой массив для хранения нового списка текстур
        $post = \Yii::$app->request->post();                                                                            // Переменная для получения post запросов
        if (isset($post['search'])) {
            $unit_list = self::buildArray($post['search']);
        } else {
            $unit_list = self::buildArray();
        }
        $result = array('errors' => $errors, 'unit_list' => $unit_list);                                                 // сохраним в массив список ошибок и новый список текстур
        \Yii::$app->response->format = Response::FORMAT_JSON;                                                  // формат json
        \Yii::$app->response->data = $result;                                                                         // отправляем обратно ввиде ajax формат
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
     * - $unit_array(array) - массив данных о единицах измерения
     * |-- [i] (array) - ссоциативный массив с информацией об i-той единице измерения
     *     |-- ['id'] - идентификатор единицы измерения
     *     |-- ['title'] - название единицы измерения
     *     |--['short'] - краткое название единицы измерения
     */

    public function actionIndex()
    {
        $unitsList = $this->buildArray();
        return $this->render('index', ['unitsList' => $unitsList]);
    }

    /*
     * Функция добавления единицы измерения
     * Входные параметры:
     * - $post['title'] (string) - название единицы измерения
     * - $post['short'] (string) - краткое название единицы измерения
     * Выходные параметры:
     * - $unit_array(array) - массив данных о единицах измерения
     * |-- [i] (array) - ссоциативный массив с информацией об i-той единице измерения
     *     |-- ['id'] - идентификатор единицы измерения
     *     |-- ['title'] - название единицы измерения
     *     |--['short'] - краткое название единицы измерения
     */
    public function actionAddUnit()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        $model = array();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 4)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                //найти единицу измерения с полученным названием

                $currentUnit = Unit::find()->where(['title' => $post['title'], 'short' => $post['short']])->one();
                //если нет такой

                if (!$currentUnit) {
                    //создать новую единицу измерения
                    $unit = new Unit();
                    //записать в нее полученные данные
                    $unit->title = $post['title'];
                    $unit->short = $post['short'];
                    //Сохранить модель
                    if ($unit->save()) {
                        $model = self::buildArray();
                        //если сохранилась, вернуть ajax-запросу массив единиц измерения
//                        echo json_encode(self::buildArray());
                        //завершить выполнение функции
//                        return;
                    } //если не сохранилась
                    else {
                        //выдать ошибку
                        $errors[] = "Ошибка сохранения";
                        $model = self::buildArray();
                    }
                } //если есть, выдать ошибку
                else {
                    $errors[] = "Данная единица измерения есть в БД";
                    $model = self::buildArray();
                }
            } else {
                $errors[] = "У вас недостаточно прав для выполнения этого действия";
                $model = self::buildArray();
            }
        } else {
            $errors[] = "Сессия неактивна";
        }
        $result = array('errors' => $errors, 'unit_list' => $model);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    /*
    * Функция редактирования единицы измерения
    * Входные параметры:
    * - $post['id'] (string) - идентификатор единицы измерения
    * - $post['title'] (string) - название единицы измерения
    * - $post['short'] (string) - краткое название единицы измерения
    * Выходные параметры:
    * - $unit_array(array) - массив данных о единицах измерения
    * |-- [i] (array) - ссоциативный массив с информацией об i-той единице измерения
    *     |-- ['id'] - идентификатор единицы измерения
    *     |-- ['title'] - название единицы измерения
    *     |--['short'] - краткое название единицы измерения
    */
    public function actionEditUnit()
    {
        $errors = array();
        $model = array();
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 5)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                //найти единицу измерения с полученным идентификатором
                $unit = Unit::findOne($post['id']);
                //если есть такая
                if ($unit) {
                    //если задано название
                    if ($post['title']) {
                        //если единицы измерения с таким названием нет


                        //записать это название
                        $unit->title = $post['title'];


                    }
                    //если задано краткое название
                    if ($post['short']) {
                        //записать его
                        $unit->short = $post['short'];
                    }
                    //Сохранить модель
                    if ($unit->save()) {   //если модель сохранилась, вернуть ajax-запросу массив единиц измерения
                        $model = self::buildArray();
                    } //иначе
                    else {
                        //вернуть ошибку
                        $errors[] = "Ошибка сохранения";
                        $model = self::buildArray();
                    }
                } //если нет
                else {
                    //вернуть ошибку
                    $errors[] = "Такой единицы измерения не существует";
                    $model = self::buildArray();
                }
            } else {
                $errors[] = "У вас недостаточно прав для выполнения этого действия";
                $model = self::buildArray();
            }
        } else {
            $errors[] = "Сессия неактивна";
        }
        $result = array('errors' => $errors, 'unit_list' => $model);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    /*
     *  Функция удаления единицы измерения
     * Входные параметры:
     * - $post['id'] (string) - идентификатор единицы измерения
     * Выходные параметры:
     * - $unit_array(array) - массив данных о единицах измерения
     * |-- [i] (array) - ссоциативный массив с информацией об i-той единице измерения
     *     |-- ['id'] - идентификатор единицы измерения
     *     |-- ['title'] - название единицы измерения
     *     |--['short'] - краткое название единицы измерения
     */
    public function actionDeleteUnit()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $model = array();
        $errors = array();
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 6)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                //найти единицу измерения по идентификатору
                $unit = Unit::findOne($post['id']);
                //если модель есть
                if ($unit) {
                    //удалить модель. Если удалилась
                    if ($unit->delete()) {
                        //вернуть ajax-запросу массив единиц измерения
                        $model = self::buildArray();
                    } //иначе
                    else {
                        //вернуть ошибку
                        $errors[] = "Ошибка удаления";
                    }
                } //если нет
                else {
                    //вернуть ошибку
                    $errors[] = "Такой единицы измерения не существует";
                    $model = self::buildArray();
                }
            } else {
                $errors[] = "У вас недостаточно прав для выполнения этого действия";
                $model = self::buildArray();
            }
        } else {
            $errors[] = "Сессия неактивна";
        }
        $result = array('errors' => $errors, 'unit_list' => $model);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;                                           // формат json
        Yii::$app->response->data = $result;
    }

    // GetUnitList - справочник единиц измерения
    // http://amicum/read-manager-amicum?controller=handbooks\HandbookUnit&method=GetUnitList&subscribe=&data={}
    public static function GetUnitList()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $warnings[] = 'GetUnitList. Начало метода';
        try {
            $warnings[] = 'GetUnitList. Данные с фронта получены и они правильные';
            $unit = Unit::find()
                ->orderBy('title')
                ->all();

        } catch (\Throwable $exception) {
            $errors[] = 'GetUnitList. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }

        $warnings[] = 'GetUnitList. Конец метода';
        if (isset($unit)) {
            $result = $unit;
        }

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }
}
