<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\handbooks;

use frontend\controllers\Assistant;
use frontend\models\Main;
use frontend\models\Mine;
use frontend\models\TypicalObject;
use yii\web\Controller;
use frontend\models\Company;

/**
 * Базовый контроллер, который работает с моделью Mine(Шахты)
 * Class MineController
 * @package frontend\controllers\handbooks
 *
 * Реализованные методы:
 *          buildMineArray - массив на заполенение таблицы
 *          AddMine ($post_title,$post_object_id,$post_company_id) - Добавление новой шахты
 *          EditMine ($title,$object_id,$company_id) - Редактирование шахты
 *          DeleteMine ($id) - Удаление шахты
 * Документация на портале: (вставить ссылку)
 */
class MineController extends Controller
{

    /**
     * Название метода: actionIndex()
     * @return string
     *
     * @package frontend\controllers\handbooks
     *
     * @see
     * @example
     *
     * Документация на портале:
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.05.2019 15:38
     * @since ver
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionTest()
    {
        $add = self::AddMine('1234', 13, 101);
        $add['errors'][] = "Новая ошибка";
        Assistant::PrintR($add);
    }

    /**
     * Заполняет таблицу данными
     * Название метода: buildMineArray()
     * @return array
     *
     * @package frontend\controllers\handbooks
     * @see
     * @example
     *
     *
     * Выходные параметры: массив
     *
     * Документация на портале:
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.05.2019 10:48
     * @since ver
     */
    public static function buildMineArray()
    {
        $mines = Mine::find()
            ->orderBy('title')
            ->limit(1000)
            ->all();                                                                                                    //получаем все шахты отсортированные по наименованию
        $minesArray = array();                                                                                          //массив для того чтобы отобразить данные пользователю
        $i = 0;
        foreach ($mines as $mine) {
            $minesArray[$i]['id'] = $mine->id;                                                                          //добавляем в массив идентификатор шахты
            $minesArray[$i]['title'] = $mine->title;                                                                    //добавляем в массив наименование шахты
            $minesArray[$i]['objectId'] = $mine->object_id;                                                             //добавляем в массив идентификатор объекта
            $minesArray[$i]['objectTitle'] = $mine->object->title;                                                      //добавляем в массив наименование объекта
            $minesArray[$i]['companyId'] = $mine->company_id;                                                           //добавляем в массив идентификатор предприятия
            $minesArray[$i]['companyTitle'] = $mine->company->title;                                                    //добавляем в массив наименование предприятия
            $minesArray[$i]['iterator'] = $i + 1;
            $i++;
        }
        return $minesArray;
    }

    /**
     * Добавление шахты
     * Название метода: AddMine()
     * @param $title
     * @param $object_id
     * @param $company_id
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * Входные обязательные параметры: $post_title - наименование добавляемой шахты
     *                                 $post_object_id - добовляемый типовой объект
     *                                 $post_company_id - добовляемое предприятие
     * @see
     * @example
     *
     * Документация на портале:
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.05.2019 15:41
     * @since ver
     */
    public static function AddMine($title, $object_id, $company_id)
    {
        $model = array();                                                                                               //массив модели (хранит таблицу (наименование шахты, наименование типового объекта, наименование предприятия))
        $errors = array();                                                                                              //массив ошибок
        $mine = Mine::find()
            ->where(['title' => $title])
            ->asArray()
            ->limit(1)
            ->one();                                                                                                    //ищем есть ли такое наименование в таблице шахт
        if (!$mine)                                                                                                      //если такого наименования в БД нет, тогда добавляем новую шахту
        {
            $main = new Main();                                                                                         //создание нового объекта в перечене объектов
            $main->table_address = "mine";                                                                              //название объекта
            $main->db_address = "amicum2";                                                                              //название бд
            $main->save();                                                                                              //сохраняем новый объект
            $error_main = $main->getErrors();                                                                           //получаем все ошибки в результате добавления, в массив ошибок


            $mine = new Mine();                                                                                         //создаём новую шахт
            $mine->id = $main->id;                                                                                      //берём идентификатор ранее созданного объекта
            $mine->title = $title;                                                                                      //устанавливаем наименование шахты
            $mine->object_id = $object_id;
            $mine->company_id = $company_id;
            $mine->version_scheme = 1;
            $mine->save();                                                                                              //вызываем метод базового класса, который добавляет новую шахту
            $errors = $mine->getErrors();                                                                               //получаем все ошибки в результате добавления, в массив ошибок
        } else                                                                                                            //иначе записываем ошибку в массив ошибок и обновляем таблицу
        {
            $errors[] = 'Такая шахта уже существует';
        }
        $merge_errors = array_merge($error_main, $errors);                                                               //сливаем ошибки в один массив и возвращаем его
        $model = self::buildMineArray();
        return array('errors' => $merge_errors, 'model' => $model);
    }


    /**
     * Название метода: EditMine()
     * @param $id
     * @param $title
     * @param $object_id
     * @param $company_id
     * @return array
     *
     * Входные необязательные параметры
     *
     * @package frontend\controllers\handbooks
     *
     * Входные обязательные параметры: $id - идентификатор редактируемой шахты
     *                                 $title - наименование редактируемой шахты
     *                                 $object_id - типовой объект (который может быть измеён)
     *                                 $company_id - предприятие (который может быть измеён)
     * @see
     * @example
     *
     * Документация на портале:
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.05.2019 15:47
     * @since ver
     */
    public static function EditMine($id, $title, $object_id, $company_id)
    {
        $model = array();                                                                                               //массив модели (хранит таблицу (наименование шахты, наименование типового объекта, наименование предприятия))
        $errors = array();                                                                                              //массив ошибок
        $mine = Mine::find()
            ->where(['id' => $id])
            ->limit(1)
            ->one();                                                                                                    //поиск такой записи в БД в таблице Шахт
        $existingMine = Mine::find()
            ->where(['title' => $title])
            ->asArray()
            ->limit(1)
            ->one();                                                                                                    //ищем такое наименование в БД
        if (!isset($existingMine) && isset($mine))                                                                       //если такого наименование нету в бд ИЛИ оно совпадает с тем что пришло постом тогда изменяем
        {
            $mine->title = $title;
            $mine->object_id = $object_id;
            $mine->company_id = $company_id;
//            $mine->version_scheme = $mine->version_scheme + 1;
            $mine->save();
            $errors = $mine->getErrors();                                                                               //получаем в массив ошибок все ошибки при редактировании шахты
        } else                                                                                                            //иначе записываем ошибку в массив ошибок и обновляем таблицу
        {
            $errors[] = 'Шахта с таким названием уже существует';
        }
        $model = self::buildMineArray();
        return array('errors' => $errors, 'model' => $model);
    }

    /**
     * Удаляет шахту в БД (Mine)
     * Название метода: DeleteMine()
     * @param $id
     * @return array
     *
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * Документация на портале:
     * @example
     *
     * @package frontend\controllers\handbooks
     *
     * Входные обязательные параметры: $id - идентификатор удаляемой шахты
     * @see
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 16.05.2019 15:51
     * @since ver
     */
    public static function DeleteMine($id)
    {
        $model = array();                                                                                               //массив модели (хранит таблицу (наименование шахты, наименование типового объекта, наименование предприятия))
        $errors = array();                                                                                              //массив ошибок
        $mine = Mine::find()
            ->where(['id' => $id])
            ->limit(1)
            ->one();                                                                                                    //поиск такой записи в БД в таблице Mine (Шахты)
        if ($mine)                                                                                                       //если запись найдена тогда удаляем её и обновляем модель
        {
            $mine->delete();                                                                                            //вызываем метод базового класса на удаление записи
            $errors = $mine->getErrors();                                                                               //получаем в массив ошибок все ошибки при удалении шахты
        } else                                                                                                            //иначе записываем ошибку в массив ошибок и обновляем таблицу
        {
            $errors[] = 'Такой шахты не существует';
        }
        $model = self::buildMineArray();
        return array('errors' => $errors, 'model' => $model);
    }
}
