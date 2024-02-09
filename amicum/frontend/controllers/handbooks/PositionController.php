<?php

namespace frontend\controllers\handbooks;

use frontend\models\Position;
use Yii;
use yii\web\Response;
use yii\web\Controller;

/**
 * Class PositionController
 * Базовый класс-контроллер который содержит методы для работы с моделью Position (Должности)
 * Реализованные методы:
 * -
 *
 * @package frontend\controllers\handbooks
 */
class PositionController extends Controller
{                                                                                           //
    public $errors = [];                                                                                                // Массив ошибок
    public $warnings = [];                                                                                              // Массив предупреждений
    /**
     * Название метода: actionTest()
     * Метод для проведения тестирования
     *
     * @package frontend\controllers\handbooks
     * @example
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 16.05.2019 10:18
     * @since ver
     */
    public function actionTest($actionType = NULL, $title = NULL, $id = NULL)
    {
        $model = [];
        switch ($actionType)
        {
            case "actionAddPosition":
                $model = self::AddPositionDB($title);
                break;
            case "actionDeletePosition":
                $model = self::DeletePositionDB($id);
                break;
            case "actionEditPosition":
                $model = self::EditPositionDB($id, $title);
                break;
        }
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $model;
    }

    /**
     * Название метода: AddPositionDB()
     * Метод добавления должности в базу данных
     *
     * @param $title - наименование должности новой
     * @param null $search - массив фильтра
     * @return array
     * @package frontend\controllers\handbooks
     * @example
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 16.05.2019 10:15
     * @since ver
     */
    public static function AddPositionDB($title)
    {
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $model = array();                                                                                               // Результирующий массив
        $warnings[] = "AddPositionDB. Зашел в метод добавления должности";
        $position = Position::find()                                                                                    // Находим должность по наименованию
        ->where(['title' => $title])
            ->asArray()
            ->limit(1)
            ->one();
        if (!$position)                                                                                                 // Если должность с таким наименованием уже есть в базе, ошибка
        {
            $warnings[] = "AddPositionDB. Должности с подобным названием нет, добалвение";
            try
            {
                $positions = new Position();                                                                            // Создаем новый экземпляр класса модели
                $positions->title = $title;                                                                             // Устанавливаем значения атрибутам
                $positions->save();                                                                                     // Добавляем в БД
                $warnings[] = "AddPositionDB. Добавил должность, строю массив для вывода";
            }
            catch(\Exception $e) {
                $errors[] = $e->getMessage();                                                                           // Получаем ошибку добавления и пихаем её в массив ошибок
            }
        }
        else
        {
            $errors[] = "AddPositionDB. Такая должность уже существует";
        }
        return ["errors" => $errors, "warnings" => $warnings, "model" => $model];
    }

    /**
     * Название метода: actionEditPosition()
     * Метод редактирования данных должности в базе данных
     *
     * @param $title - новое наименование должности
     * @param null $search - поисковой запрос, для построения результирующего массива
     *
     * @package frontend\controllers\handbooks
     * @example
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 16.05.2019 10:05
     * @since ver
     */
    public static function EditPositionDB($id, $title)
    {
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $model = array();                                                                                               // Результирующий массив
        $warnings[] = "EditPositionDB. Зашел в метод изменения должности";
        $position = Position::find()                                                                                    // Находим должность по наименованию, чтобы избежать дубликатов
        ->where(['title' => $title])
            ->asArray()
            ->limit(1)
            ->one();

        if (!$position)                                                                                                 // Если должность с таким наименованием уже есть в базе, ошибка
        {
            $warnings[] = "EditPositionDB. Должности с подобным названием нет, добалвение";
            try
            {
                $positions = Position::findOne($id);                                                                    // Находим должность по её идентификатору
                $positions->title = $title;                                                                             // Устанавливаем значения атрибутам
                $positions->save();                                                                                     // Изменяем данные в БД
                $warnings[] = "EditPositionDB. Добавил должность, стрпою массив для вывоба";
            }
            catch(\Throwable $e) {
                $errors[] = $e->getMessage();                                                                           // Получаем ошибку добавления и пихаем её в массив ошибок
            }
        }
        else
        {
            $errors[] = "EditPositionDB. Такая должность уже существует";
        }
        return ["errors" => $errors, "warnings" => $warnings];
    }

    /**
     * Название метода: DeletePositionDB()
     * Метод удаления должности из базы данных
     *
     * @return array
     * @package frontend\controllers\handbooks
     * @example
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 16.05.2019 10:10
     * @since ver
     */
    public static function DeletePositionDB($id)
    {
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $model = array();                                                                                               // Результирующий массив

        $warnings[] = "DeletePositionDB. Зашел в метод удаления должности";
        try
        {
            $position = Position::find()                                                                                    // Находим должность по наименованию, чтобы избежать дубликатов
            ->where(['id' => $id])
                ->limit(1)
                ->one();
            $position->delete();                                                                                       // Изменяем данные в БД
            $warnings[] = "DeletePositionDB. Удалил должность, строю массив";
        }
        catch(\Throwable $e) {
            $errors[] = $e->getMessage();                                                                               // Получаем ошибку добавления и пихаем её в массив ошибок
        }
        return ["errors" => $errors, "warnings" => $warnings];
    }

    /**
     * Название метода: buildArray()
     * Метд построения массива должностей
     *
     * @param string $search
     * @return array
     * @package frontend\controllers\handbooks
     * @example
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 17.05.2019 15:24
     * @since ver
     */
    public static function GetPositionDB($search = NULL)
    {
        $positions = NULL;
        $condition = NULL;                                                                                              // Условие поиска
        $params = NULL;                                                                                                 // Параметры поиска
        if ($search)
        {
            $condition = "title LIKE :search";                                                                          // Условие для поиска
            $params = [':search' => '%'.$search.'%'];                                                                   // Параметры поиска
        }
        $positions = Position::find()                                                                                   // Выбираем все должности
        ->where($condition, $params)
            ->orderBy('title')
            ->all();
        $model = [];
        $i = 0;
        $lowerSearch = mb_strtolower($search);
        foreach ($positions as $position)
        {
            $model[$i]['id'] = $position['id'];
            $model[$i]['title'] = self::markSearched($search, $lowerSearch, $position['title']);
            $i++;
        }
        return $model;
    }

    /**
     * Название метода: markSearched()
     * Функция выделения найденной подстроки в строке для визуального отображения найденных совпадений
     *
     * @param $search - строка поиска
     * @param $lowerSearch - строка поиска в нижнем регистре
     * @param $titleSearched - строка в которой находим и вносим выделение
     * @return string
     * @package frontend\controllers\handbooks
     * @example
     *
     * Документация на портале:
     * @author Некрасов Е.П. <nep@pfsz.ru>
     * Created date: on 17.05.2019 9:59
     * @since ver
     */
    public static function markSearched($search, $lowerSearch, $titleSearched)
    {
        $title = "";
        if ($search != "") {                                                                                            // Если передана поисковая строка, выделение
            // echo $search;
            $titleParts = explode($lowerSearch, mb_strtolower($titleSearched));                                         // Разделяем строку в которой ищем на строки по поисковому запросу
            $titleCt = count($titleParts);                                                                              // Запоминаем количество частей на которую разделили
            $startIndex = 0;                                                                                            // Индексатор
            $title .= substr($titleSearched, $startIndex, strlen($titleParts[0]));                                      // Добавляем в результирующую строку подстроку
            $startIndex += strlen($titleParts[0] . $search);
            for ($j = 1; $j < $titleCt; $j++) {                                                                         // Перебираем части полученные после разбиения строки
                $title .= "<span class='searched'>" .                                                                   // Оборачиваем найденные строки в span'ы для которых прописан нужный класс
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
}