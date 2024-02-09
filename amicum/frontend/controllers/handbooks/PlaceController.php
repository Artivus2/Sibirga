<?php

namespace frontend\controllers\handbooks;

use frontend\controllers\HandbookCachedController;
use frontend\models\Main;
use frontend\models\Place;
use frontend\models\PlaceType;
use yii\web\Controller;

/**
 * Базовый контроллер, который работает с моделью Place(Список мест)
 * Class PlaceController
 * @package frontend\controllers\handbooks
 *
 * Реализованные методы:
 *                  buildArray ($search) - массив на заполенение таблицы и поиск
 *                  markSearched ($search, $lowerSearch, $titleSearched) - выделение поисковых символов жирным цветом
 *                  AddPlace ($title, $plast_id, $mine_id, $object_id)- добавление места
 *                  EditPlace($id, $title, $plast_id, $mine_id, $object_id) - редактирование места
 *                  DeletePlace ($id) - удаление места
 * Документация на портале: (вставить ссылку)
 */
class PlaceController extends Controller
{
    // GetPlaceType                         - Получение справочника типов мест
    // SavePlaceType                        - Сохранение нового типа места
    // DeletePlaceType                      - Удаление типа места



    public function actionIndex()
    {
        return $this->render('index');
    }

    /** Функция сбора массива мест, которые нужно отправить на фронт
     * @param string $search - поисковый запрос, по умолчанию - пустая строка
     * @return array $model - массив мест (place)
     * Commented by: Курбанов И. С. on 04.05.2019
     */
    public static function buildArray($search = '')
    {

        $places = null;
        if($search==='')                                                                                                //заполянем таблицу данными если значение переменной поиска пусто
        {
            $places = Place::find()
                ->select([
                    'place.id id',
                    'place.title title',
                    'place.plast_id plast_id',
                    'place.object_id object_id',
                    'place.mine_id mine_id',
                    'plast.title plast_title',
                    'mine.title mine_title',
                    'object.title object_title'
                ])
                ->leftJoin('plast', 'place.plast_id = plast.id')
                ->leftJoin('mine','place.mine_id = mine.id')
                ->leftJoin('object', 'place.object_id = object.id')
                ->orderBy('place.title')
                ->asArray()
                ->all();
        }
        else                                                                                                            //иначе выделяем жирным те символы которые искали и заполняем таблицу
        {
            $places = Place::find()
                ->select([
                    'place.id id',
                    'place.title title',
                    'place.plast_id plast_id',
                    'place.object_id object_id',
                    'place.mine_id mine_id',
                    'plast.title plast_title',
                    'mine.title mine_title',
                    'object.title object_title'
                ])
                ->leftJoin('plast', 'place.plast_id = plast.id')
                ->leftJoin('mine','place.mine_id = mine.id')
                ->leftJoin('object', 'place.object_id = object.id')
                ->where('place.title like "%'.$search.'%"')
                ->orWhere('plast.title like "%'.$search.'%"')
                ->orWhere('mine.title like "%'.$search.'%"')
                ->orWhere('object.title like "%'.$search.'%"')
                ->orderBy('place.title')
                ->asArray()
                ->all();
        }
        //var_dump($places);
        $model = array();
        $i = 0;
        $lowerSearch = mb_strtolower($search);                                                                          //приведение поисковой строки к нижнему регистру
        foreach ($places as $place) {                                                                                   //перебор всех элментов и выделение жирным у них тех символов которые есть в строке поиска
            $model[$i] = array();
            $model[$i]['iterator'] = $i + 1;
            $model[$i]['id'] = $place['id'];
            $model[$i]['title'] = self::markSearched($search, $lowerSearch, $place['title']);
            $model[$i]['plast_id'] = $place['plast_id'];
            $model[$i]['plast_title'] = self::markSearched($search, $lowerSearch, $place['plast_title']);
            $model[$i]['mine_id'] = $place['mine_id'];
            $model[$i]['mine_title'] = self::markSearched($search, $lowerSearch, $place['mine_title']);
            $model[$i]['object_id'] = $place['object_id'];
            $model[$i]['object_title'] = self::markSearched($search, $lowerSearch, $place['object_title']);
            $i++;
        }
        return $model;
    }

    public static function markSearched($search, $lowerSearch, $titleSearched)
    {
        $title = '';
        if ($search !== '') {
            // echo $search;
            $titleParts = explode($lowerSearch, mb_strtolower($titleSearched));                                         //разбивает строку на "До найденного" "найденное" "после найденного"
            $titleCt = count($titleParts);                                                                              //количество разбиений
            $startIndex = 0;
            $title .= substr($titleSearched, $startIndex, strlen($titleParts[0]));                                      //вернёт поисковую строку от 0 до конца первого элемента в разбитой строке
            $startIndex += strlen($titleParts[0] . $search);                                                     //присваиваем индексу длинну строки (первого элемента и поискового запроса)
            for ($j = 1; $j < $titleCt; $j++) {
                $title .= "<span class='searched'>" .
                    substr($titleSearched, $startIndex - strlen($search), strlen
                    ($search)) . '</span>' .
                    substr($titleSearched, $startIndex, strlen
                    ($titleParts[$j]));                                                                                 //добавляем тег который сделает жирным символы из поисковой строки
                $startIndex += strlen($titleParts[$j] . $search);
            }
        } else {
            $title .= $titleSearched;
        }
        return $title;
    }

    public static function AddPlace($title, $plast_id, $mine_id, $object_id)
    {
        $errors = array();
        $model = array();
        $place = Place::find()
            ->select('id')
            ->where(['title'=>$title])
            ->limit(1)
            ->one();                                                                                                    //находим место в БД в таблице place (Список мест)
        if (!$place) {                                                                                                  //если место не найдено тогда добавляем новое место
            $main = new Main();
            $main->db_address = 'amicum2';
            $main->table_address = 'place';
            $main->save();

            $place = new Place();
            $place->id = $main->id;
            $place->title = $title;
            $place->plast_id = $plast_id;
            $place->mine_id = $mine_id;
            $place->object_id = $object_id;
            $place->save();
            $errors = $place->getErrors();                                                                              //получаем все ошибки в массив ошибок
            HandbookCachedController::clearPlaceCache();
        } else {
            $errors[] = 'Такое место уже существует';
        }
        $model = self::buildArray();
        return array('errors' => $errors, 'place_list' => $model);                                                      //возвращаем обновлённые данные и массив ошибок
    }

    public static function EditPlace($id, $title, $plast_id, $mine_id, $object_id)
    {
        $errors = array();
        $place = Place::findOne(['id' => $id]);                                             //находим запись по идентификатору на редактирование
        $existingPlace = Place::findOne(['title' => $title, 'plast_id' => $plast_id, 'mine_id' => $mine_id, 'object_id' => $object_id]);                                                                                                    //ищем такую же строку в БД

        if(!$place and !$existingPlace) {
            $place = new Place();
            $place->title = $title;
            $place->plast_id = $plast_id;
            $place->mine_id = $mine_id;
            $place->object_id = $object_id;
            if(!$place->save()) {
                $errors = $place->getErrors();
            }
        } else if(!$place and $existingPlace) {
            $errors[] = 'Такое место уже существует';
        } else if ($place) {
            $place->title = $title;
            $place->plast_id = $plast_id;
            $place->mine_id = $mine_id;
            $place->object_id = $object_id;
            if(!$place->save()) {
                $errors = $place->getErrors();
            }                                                                            //получаем все ошибки в массив ошибок
        }
        HandbookCachedController::clearPlaceCache();
        $model = self::buildArray();
        return array('errors' => $errors, 'place_list' => $model);                                                      //возвращаем обновлённую модель и массив ошибок
    }

    public static function DeletePlace($id)
    {
        $errors = array();
        $model = array();
        $new_erorrs = array();
        $error_main = array();
        $places = Place::find()
            ->where(['id' => $id])
            ->limit(1)
            ->one();                                                                                                    //нахоим такое место (place) в БД
        $places->delete();
        $errors = $places->getErrors();                                                                                 //получаем все ошибки в массив ошибок

        $main = Main::find()
            ->where(['id' => $id])
            ->limit(1)
            ->one();                                                                                                    //находим его в перечене объектов
        $main->delete();
        $error_main = $places->getErrors();                                                                             //получаем все ошибки в массив ошибок
        $new_erorrs = array_merge($errors,$error_main);                                                                 //сливаем ошибки в один массив и возвращаем его
        return array('errors' => $new_erorrs, 'place_list' => $model);
    }

    /**
     * Назначение: Полечение всех мест
     * Название метода: GetPlaces()
     * @return array
     *
     * @see
     * @example
     *
     * Документация на портале:
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 20.05.2019 13:14
     * @since ver
     */
    public static function GetPlaces()
    {
        $errors = array();
        $places = Place::find()
            ->select(['id','title'])
            ->limit(10000)
            ->orderBy('title')
            ->all();
        if ($places) {
            $result = array('errors' => $errors, 'places' => $places);
        } else {
            $errors[] = 'Нет данных в БД';
            $result = array('errors' => $errors, 'places' => $places);
        }
        return $result;
    }
    /**
     * Метод GetPlaceType() - Получение справочника типов мест
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Place&method=GetPlaceType&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 30.03.2020 08:25
     */
    public static function GetPlaceType()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetPlaceType';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $place_type = PlaceType::find()
                ->asArray()
                ->all();
            if(empty($place_type)){
                $warnings[] = $method_name.'. Справочник типов мест пуст';
            }else{
                $result = $place_type;
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
     * Метод SavePlaceType() - Сохранение нового типа места
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Place&method=SavePlaceType&subscribe=&data={"place_type":{"place_type_id":-1,"title":"PLACE_TYPE_TEST"}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 30.03.2020 08:30
     */
    public static function SavePlaceType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SavePlaceType';
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
            if (!property_exists($post_dec, 'place_type'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $place_type_id = $post_dec->place_type->place_type_id;
            $title = $post_dec->place_type->title;
            $place_type = PlaceType::findOne(['id'=>$place_type_id]);
            if (empty($place_type)){
                $place_type = new PlaceType();
            }
            $place_type->title = $title;
            if ($place_type->save()){
                $place_type->refresh();
                $chat_type_data['place_type_id'] = $place_type->id;
                $chat_type_data['title'] = $place_type->title;
            }else{
                $errors[] = $place_type->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении нового типа места');
            }
            unset($place_type);
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
     * Метод DeletePlaceType() - Удаление типа места
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Place&method=DeletePlaceType&subscribe=&data={"place_type_id":4}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 30.03.2020 08:35
     */
    public static function DeletePlaceType($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeletePlaceType';
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
            if (!property_exists($post_dec, 'place_type_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $place_type_id = $post_dec->place_type_id;
            $del_place_type = PlaceType::deleteAll(['id'=>$place_type_id]);
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
