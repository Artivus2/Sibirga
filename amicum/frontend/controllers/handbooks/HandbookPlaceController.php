<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace frontend\controllers\handbooks;
//ob_start();

//классы и контроллеры yii2
use Exception;
use frontend\controllers\HandbookCachedController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\AccessCheck;
use frontend\models\KindObject;
use frontend\models\Mine;
use frontend\models\Place;
use frontend\models\Plast;
use frontend\models\TypicalObject;
use Throwable;
use Yii;
use yii\db\Query;
use yii\web\Controller;
use yii\web\Response;

/** Контроллер для справочника Мест
 * Class HandbookPlaceController
 * @package frontend\controllers
 */
class HandbookPlaceController extends Controller
{
    // GetListKindPlace             - Метод получения списка мест сгруппированных по видам мест
    // GetHandbookKindPlace         - Метод получения списка видов мест из типовых объектов
    // GetListPlaceWithHandbook     - Метод получения списка мест сгруппированных по типам и видам типовых объектов вместе со справочниками шахтных полей, видов мест, пластов
    // SaveNewPlace                 - метод сохранения нового места из модалки в нарядной системе
    // GetUndergroundPlaceList      - Метод получения справочника подземных мест
    // GetEdgePlace                 - Метод получения справочника edge  и мест привязанных к ним
    // GetPlaceList                 - Получение списка мест из справочника мест, всех кроме ППК ПАБ

    public function actionIndex()
    {
        $model = PlaceController::buildArray();

        $plasts = (new Query())
            ->select([
                'id',
                'title'
            ])
            ->from('plast')
            ->orderBy('title')
            ->all();
        $mines = (new Query())
            ->select([
                'id',
                'title'
            ])
            ->from('mine')
            ->orderBy('title')
            ->all();
        $objects = (new Query())
            ->select([
                'id',
                'title'
            ])
            ->from('object')
            ->orderBy('title')
            ->all();

        return $this->render('index', [
            'model' => $model,
            'plasts' => $plasts,
            'mines' => $mines,
            'objects' => $objects
        ]);
    }

    public function buildArray($search = "")
    {
        $places = null;
        if ($search == "") {
            $places = (new Query())
                ->select(
                    [
                        'place.id id',
                        'place.title title',
                        'place.plast_id plast_id',
                        'place.object_id object_id',
                        'place.mine_id mine_id',
                        'plast.title plast_title',
                        'mine.title mine_title',
                        'object.title object_title'
                    ])
                ->from(['place'])
                ->leftJoin('plast', 'place.plast_id = plast.id')
                ->leftJoin('mine', 'place.mine_id = mine.id')
                ->leftJoin('object', 'place.object_id = object.id')
                ->orderBy('title')
                ->all();
        } else {
            $places = (new Query())
                ->select(
                    [
                        'place.id id',
                        'place.title title',
                        'place.plast_id plast_id',
                        'place.object_id object_id',
                        'place.mine_id mine_id',
                        'plast.title plast_title',
                        'mine.title mine_title',
                        'object.title object_title'
                    ])
                ->from(['place'])
                ->leftJoin('plast', 'place.plast_id = plast.id')
                ->leftJoin('mine', 'place.mine_id = mine.id')
                ->leftJoin('object', 'place.object_id = object.id')
                ->where('place.title like "%' . $search . '%"')
                ->orWhere('plast.title like "%' . $search . '%"')
                ->orWhere('mine.title like "%' . $search . '%"')
                ->orWhere('object.title like "%' . $search . '%"')
                ->orderBy('title')
                ->all();
        }
        //var_dump($places);
        $model = array();
        $i = 0;
        $lowerSearch = mb_strtolower($search);
        foreach ($places as $place) {
            $model[$i] = array();
            $model[$i]['iterator'] = $i + 1;
            $model[$i]['id'] = $place['id'];


            $model[$i]['title'] = $this->markSearched($search, $lowerSearch, $place['title']);
            $model[$i]['plast_id'] = $place['plast_id'];

            $model[$i]['plast_title'] = $this->markSearched($search, $lowerSearch, $place['plast_title']);
            $model[$i]['mine_id'] = $place['mine_id'];

            $model[$i]['mine_title'] = $this->markSearched($search, $lowerSearch, $place['mine_title']);
            $model[$i]['object_id'] = $place['object_id'];

            $model[$i]['object_title'] = $this->markSearched($search, $lowerSearch, $place['object_title']);
            $i++;
        }
        return $model;
    }

    /** Функция добавления нового места
     * @throws Throwable array - массив для передачи на фронт, в котором содержатся 2 массива:
     * array errors - массив сгенерированных ошибок
     * array place_list - массив мест
     * Commented by: Курбанов И. С. on 04.05.2019
     */
    public function actionAddPlace()
    {
        $errors = array();
        $session = Yii::$app->session;                                                                                  //старт сессии
        if (isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 45)) {                                       //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();
                $add_place = PlaceController::AddPlace($post['title'], $post['plastId'], $post['mineId'], $post['objectId']);
                $errors = array_merge($errors, $add_place['errors']);
            } else {
                $errors[] = 'У вас недостаточно прав для выполнения этого действия';
            }
        } else {
            $errors[] = 'Сессия неактивна';
        }
        $model = PlaceController::buildArray();
        $result = array('errors' => $errors, 'place_list' => $model);                                                        //возвращаем обновлённую модель и массив ошибо
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            // формат json
        Yii::$app->response->data = $result;
    }

    /**
     * SaveNewPlace - Метод сохранения нового места из модалки в нарядной системе
     * Входные данные:
     *      place_obj:
     *          place_id    - ключ места
     *          place_title - название места
     *          plast_id    - ключ пласта
     *          mine_id     - ключ шахты
     *          object_id   - ключ типового объекта
     * Выходной объект:
     *      Items:
     *          {}
     *              place_id    - ключ места
     *              place_title - название места
     *              plast_id    - ключ пласта
     *              mine_id     - ключ шахты
     *              object_id   - ключ типового объекта
     */
    public static function SaveNewPlace($data_post = NULL)
    {
        $log = new LogAmicumFront("SaveNewPlace");

        $result = (object)array();

        $place_id = -1;
        $place = null;

        try {
            $log->addLog("Начало метода");
            if (is_string($data_post)) {
                if (!($data_post !== NULL && $data_post !== '')) {
                    throw new Exception('Данные с фронта не получены');
                }

                $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных

                if (!(property_exists($post_dec, 'place_obj'))) {
                    throw new Exception('Переданы некорректные входные параметры');
                }                                                                                                           // Проверяем наличие в нем нужных нам полей

                $place_obj = $post_dec->place_obj;
            } else {
                $place_obj = $data_post;
            }

            $place = Place::find()
                ->where(['mine_id' => $place_obj->mine_id, 'title' => $place_obj->place_title])
                ->orWhere(['id' => $place_obj->place_id])
                ->one();

            if (!$place) {
                $place = new Place();
            }

            $place->title = $place_obj->place_title;
            $place->plast_id = $place_obj->plast_id;
            $place->mine_id = $place_obj->mine_id;
            $place->object_id = $place_obj->object_id;
            if (!$place->save()) {
                $log->addData($place->errors, '$place->errors', __LINE__);
                throw new Exception('Ошибка сохранения модели места Place');
            }
            $place->refresh();
            $place_id = $place->id;
            $place_obj->place_id = $place->id;
            $result = $place_obj;
            HandbookCachedController::clearPlaceCache();

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result, 'place_id' => $place_id, 'place' => $place], $log->getLogAll());
    }

    /**
     * Назначение: Функция редактирования существующего места
     * Название метода: actionEditPlace()
     * @package frontend\controllers\handbooks
     *
     * @see
     * @example
     *
     * Документация на портале:
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 20.05.2019 13:12
     * @since ver
     */
    public function actionEditPlace()
    {
        $errors = array();
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if (isset($session['sessionLogin'])) {                                                                                                               //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 46)) {                                                                                                           //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();
                $edit_place = PlaceController::EditPlace($post['id'], $post['title'], $post['plastId'], $post['mineId'], $post['objectId']);
                $errors = array_merge($errors, $edit_place['errors']);
            } else {
                $errors[] = 'У вас недостаточно прав для выполнения этого действия';
            }
        } else {
            $errors[] = 'Сессия неактивна';
        }
        $model = PlaceController::buildArray();
        $result = array('errors' => $errors, 'place_list' => $model);
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            // формат json
        Yii::$app->response->data = $result;
    }

    /**
     * Назначение: Функция удаления существующего места
     * Название метода: actionDeletePlace()
     * @package frontend\controllers\handbooks
     *
     * @see
     * @example
     *
     * Документация на портале:
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 20.05.2019 13:13
     * @since ver
     */
    public function actionDeletePlace()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        $errors = array();
        if (isset($session['sessionLogin'])) {                                                                                                               //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 47)) {                                                                                                           //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post();
                $delete_place = PlaceController::DeletePlace($post['id']);
                $errors = array_merge($errors, $delete_place['errors']);
            } else {
                $errors[] = 'У вас недостаточно прав для выполнения этого действия';
            }
        } else {
            $errors[] = 'Сессия неактивна';
        }
        $model = PlaceController::buildArray();
        $result = array('errors' => $errors, 'place_list' => $model);
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            // формат json
        Yii::$app->response->data = $result;
    }

    /**
     * Назначение: поиск по местам
     * Название метода: actionSearchPlace()
     * @package frontend\controllers\handbooks
     *
     * @see
     * @example
     *
     * Документация на портале:
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 20.05.2019 13:15
     * @since ver
     */
    public function actionSearchPlace()
    {
        $errors = array();         // Пустой массив для хранения нового списка текстур
        $post = Yii::$app->request->post();                                                                             // Переменная для получения post запросов
        if (isset($post['search'])) {
            $place_list = PlaceController::buildArray($post['search']);
        } else {
            $place_list = PlaceController::buildArray();
        }
        $result = array(['errors' => $errors, 'place_list' => $place_list]);                                               // сохраним в массив список ошибок и новый список текстур
        Yii::$app->response->format = Response::FORMAT_JSON;                                                            // формат json
        Yii::$app->response->data = $result;                                                                            // отправляем обратно ввиде ajax формат
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



    // GetListKindPlace    - Метод получения списка мест сгруппированных по видам мест

    /**
     * Название метода: GetListKindPlace()
     * Метод получения списка мест сгруппированных по типам и видам типовых объектов
     *
     * @param null $data_post
     * @return array
     * @package frontend\controllers\ordersystem
     * @example http://127.0.0.1/read-manager-amicum?controller=handbooks\HandbookPlace&method=GetListKindPlace&subscribe=&data={}
     *
     * Документация на портале:
     * @author Якимов М.Н.
     * Created date: on 25.05.2019 19:46
     * @since ver
     */
    public static function GetListKindPlace($data_post = NULL)
    {
        $status = 1;                                                                                                 // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();                                                                                                // Промежуточный результирующий массив

        $warnings[] = 'GetListKindPlace. Данные успешно переданы';
        $warnings[] = 'GetListKindPlace. Входной массив данных' . $data_post;
        try {
            $kind_objects = KindObject::find()
                ->with('objectTypes')
                ->with('objectTypes.objects')// Получаем вложенные связи сразу
                ->with('objectTypes.objects.places')
                ->with('objectTypes.objects.places.mine')
                ->where(['kind_object.id' => [2, 6]])
                ->all();
            if ($kind_objects) {
                foreach ($kind_objects as $kind_object) {
                    $place_list[$kind_object->id]['kind_object_id'] = $kind_object->id;
                    $place_list[$kind_object->id]['kind_object_title'] = $kind_object->title;
                    $place_list[$kind_object->id]['object_type'] = array();
                    foreach ($kind_object->objectTypes as $object_type) {
                        $place_list[$kind_object->id]['object_type'][$object_type->id]['object_type_id'] = $object_type->id;
                        $place_list[$kind_object->id]['object_type'][$object_type->id]['object_type_title'] = $object_type->title;
                        $place_list[$kind_object->id]['object_type'][$object_type->id]['typical_object'] = array();
                        foreach ($object_type->objects as $typical_object) {
                            $place_list[$kind_object->id]['object_type'][$object_type->id]['typical_object'][$typical_object->id]['typical_object_id'] = $typical_object->id;
                            $place_list[$kind_object->id]['object_type'][$object_type->id]['typical_object'][$typical_object->id]['typical_object_title'] = $typical_object->title;
                            $place_list[$kind_object->id]['object_type'][$object_type->id]['typical_object'][$typical_object->id]['mine'] = array();
                            foreach ($typical_object->places as $place) {
                                $place_list[$kind_object->id]['object_type'][$object_type->id]['typical_object'][$typical_object->id]['mine'][$place->mine_id]['mine_id'] = $place->mine_id;
                                $place_list[$kind_object->id]['object_type'][$object_type->id]['typical_object'][$typical_object->id]['mine'][$place->mine_id]['mine_title'] = $place->mine->title;
                                $place_list[$kind_object->id]['object_type'][$object_type->id]['typical_object'][$typical_object->id]['mine'][$place->mine_id]['place'][$place->id]['place_id'] = $place->id;
                                $place_list[$kind_object->id]['object_type'][$object_type->id]['typical_object'][$typical_object->id]['mine'][$place->mine_id]['place'][$place->id]['place_title'] = $place->title;
                            }

                        }
                    }
                }
                $result = $place_list;
                $status *= 1;
                $warnings[] = 'GetListKindPlace. Метод отработал все ок';
            } else {
                $warnings[] = 'GetListKindPlace. справочник мест пуст';
            }

        } catch (Throwable $ex) {
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Название метода: GetListPlaceWithHandbook()
     * GetListPlaceWithHandbook() -  Метод получения списка мест сгруппированных по типам и видам типовых объектов вместе со справочниками шахтных полей, видов мест, пластов
     *
     * @param null $data_post
     * @return array
     * @package frontend\controllers\ordersystem
     * @example http://127.0.0.1/read-manager-amicum?controller=handbooks\HandbookPlace&method=GetListPlaceWithHandbook&subscribe=&data=
     *
     * Документация на портале:
     * @author Якимов М.Н.
     * Created date: on 25.05.2019 19:46
     * @since ver
     */
    public static function GetListPlaceWithHandbook($data_post = NULL)
    {
        $status = 1;                                                                                                 // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();                                                                                                // Промежуточный результирующий массив

        $warnings[] = 'GetListPlaceWithHandbook. Данные успешно переданы';
        $warnings[] = 'GetListPlaceWithHandbook. Входной массив данных' . $data_post;
        try {

            // получение списка мест
            $places = (new Query())
                ->select("
                place.id as place_id,
                place.title as place_title,
                kind_object.id as kind_object_id,
                kind_object.title as kind_object_title,
                object_type.id as object_type_id,
                object_type.title as object_type_title,
                object.id as typical_object_id,
                object.title as object_title,
                mine.id as mine_id,
                mine.title as mine_title,
                
                ")
                ->from('place')
                ->innerJoin('mine', 'mine.id=place.mine_id')
                ->innerJoin('object', 'object.id=place.object_id')
                ->innerJoin('object_type', 'object_type.id=object.object_type_id')
                ->innerJoin('kind_object', 'kind_object.id=object_type.kind_object_id')
                ->where(['kind_object.id' => [2, 6]])
                ->andWhere("place.mine_id!=1")
                ->all();

            if ($places) {
                foreach ($places as $place) {

                    $kind_object_id = $place['kind_object_id'];
                    $place_list[$kind_object_id]['kind_object_id'] = $kind_object_id;
                    $place_list[$kind_object_id]['kind_object_title'] = $place['kind_object_title'];

                    $object_type_id = $place['object_type_id'];
                    $place_list[$kind_object_id]['object_type'][$object_type_id]['object_type_id'] = $object_type_id;
                    $place_list[$kind_object_id]['object_type'][$object_type_id]['object_type_title'] = $place['object_type_title'];

                    $typical_object_id = $place['typical_object_id'];
                    $place_list[$kind_object_id]['object_type'][$object_type_id]['typical_object'][$typical_object_id]['typical_object_id'] = $typical_object_id;
                    $place_list[$kind_object_id]['object_type'][$object_type_id]['typical_object'][$typical_object_id]['typical_object_title'] = $place['object_title'];

                    $mine_id = $place['mine_id'];
                    $place_list[$kind_object_id]['object_type'][$object_type_id]['typical_object'][$typical_object_id]['mine'][$mine_id]['mine_id'] = $mine_id;
                    $place_list[$kind_object_id]['object_type'][$object_type_id]['typical_object'][$typical_object_id]['mine'][$mine_id]['mine_title'] = $place['mine_title'];

                    $place_id = $place['place_id'];
                    $place_list[$kind_object_id]['object_type'][$object_type_id]['typical_object'][$typical_object_id]['mine'][$mine_id]['place'][$place_id]['place_id'] = $place_id;
                    $place_list[$kind_object_id]['object_type'][$object_type_id]['typical_object'][$typical_object_id]['mine'][$mine_id]['place'][$place_id]['place_title'] = $place['place_title'];
                }
                $result['place'] = $place_list;

                $warnings[] = 'GetListPlaceWithHandbook. Метод отработал все ок';
            } else {
                $result['place'] = (object)array();
                $warnings[] = 'GetListPlaceWithHandbook. справочник мест пуст';
            }

            // получение списка пластов
            $plast = Plast::find()
                ->indexBy('id')
                ->all();
            if ($plast) {
                $result['plast'] = $plast;
            } else {
                $result['plast'] = (object)array();
            }

            // получение списка шахтных полей
            $mine = Mine::find()
                ->indexBy('id')
                ->all();
            if ($plast) {
                $result['mine'] = $mine;
            } else {
                $result['mine'] = (object)array();
            }

            // получение списка видов мест
            // 110	Надшахтное здание
            // 111	Выработка
            // 112	Ствол
            // 113	Поворот
            // 115	Объект поверхности
            // 125	Места ППК ПАБ
            // 127 - Прочие объекты горной среды
            $placeObject = TypicalObject::find()
                ->indexBy('id')
                ->where(['IN',
                    'object_type_id',
                    [110, 111, 112, 113, 115, 125, 127]
                ])
                ->all();
            if ($placeObject) {
                $result['place_object'] = $placeObject;
            } else {
                $result['place_object'] = (object)array();
            }

        } catch (Throwable $ex) {
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Название метода: GetUndergroundPlaceList()
     * Метод получения справочника подземных мест
     *
     * @param null $data_post
     * @return array
     * @package frontend\controllers\ordersystem
     * @example http://127.0.0.1/read-manager-amicum?controller=handbooks\HandbookPlace&method=GetUndergroundPlaceList&subscribe=&data=
     *
     * Документация на портале:
     * @author Якимов М.Н.
     * Created date: on 25.05.2019 19:46
     * @since ver
     */
    public static function GetUndergroundPlaceList($data_post = NULL)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("GetUndergroundPlaceList");

        try {
            $log->addLog("Начало выполнения метода");

            $cache = Yii::$app->cache;
            $key = "GetUndergroundPlaceList";
            $keyHash = "GetUndergroundPlaceListHash";
            $place_list = $cache->get($key);
            if (!$place_list) {


                $place_list = (new Query())
                    ->select(
                        ['place.id',
                            'place.title',
                            'place.mine_id',
                            'place.object_id',
                            'object.object_type_id',
                            'object.title',
                            'object_type.kind_object_id',
                            'object_type.title']
                    )
                    ->from('place')
                    ->innerJoin('object', 'object.id=place.object_id')
                    ->innerJoin('object_type', 'object_type.id=object.object_type_id')
                    ->where(['kind_object_id' => [2]])
                    ->andWhere("place.mine_id!=1")
                    ->indexBy('id')
                    ->all();
                $hash = md5(json_encode($place_list));
                $cache->set($keyHash, $hash, 60 * 60 * 24);
                $cache->set($key, $place_list, 60 * 60 * 24);   // 60 * 60 * 24 = сутки
            } else {
                $log->addLog("Кеш был");
                $hash = $cache->get($keyHash);
            }

            if ($place_list) {
                $result['handbook'] = $place_list;
                $result['hash'] = $hash;
                $log->addLog("Метод отработал все ок");
            } else {
                $result = (object)array();
                $log->addLog("справочник мест пуст");
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Название метода: GetEdgePlace()
     * Метод получения справочника edge  и мест привязанных к ним
     *
     * @param null $data_post
     * @return array
     * @package frontend\controllers\ordersystem
     * @example http://127.0.0.1/read-manager-amicum?controller=handbooks\HandbookPlace&method=GetEdgePlace&subscribe=&data=
     *
     * Документация на портале:
     * @author Якимов М.Н.
     * Created date: on 25.05.2019 19:46
     * @since ver
     */
    public static function GetEdgePlace($data_post = NULL)
    {
        $status = 1;                                                                                                 // Флаг успешного выполнения метода
        $warnings = array();                                                                                              // Массив предупреждений
        $errors = array();                                                                                                // Массив ошибок
        $result = array();                                                                                                // Промежуточный результирующий массив

        $warnings[] = 'GetEdgePlace. Данные успешно переданы';
        $warnings[] = 'GetEdgePlace. Входной массив данных' . $data_post;
        try {
            $place_list = (new Query())
                ->select(
                    '
                    edge.id as edge_id,
                    edge.place_id as place_id,
                    place.title as place_title
                    '
                )
                ->from('edge')
                ->innerJoin('place', 'place.id=edge.place_id')
                ->indexBy('edge_id')
                ->all();
            if ($place_list) {
                $result = $place_list;
                $warnings[] = 'GetEdgePlace. Метод отработал все ок';
            } else {
                $warnings[] = 'GetEdgePlace. справочник мест пуст';
            }

        } catch (Throwable $ex) {
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    /**
     * Метод GetPlaceList() - получение списка мест из справочника мест, всех кроме ППК ПАБ
     * @return array            - массив мест из справочника мест
     * @package frontend\controllers\industrial_safety
     * @example http://127.0.0.1/read-manager-amicum?controller=handbooks\HandbookPlace&method=GetPlaceList&subscribe=&data=
     *
     * @author Якимов М.Н.
     * Created date: on 26.11.2020 18:04
     */
    public static function GetPlaceList()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = (object)array();                                                                                     // выходной объект
        try {
            $places = Place::find()
                ->select(['place.id AS place_id', 'place.title AS place_title'])
                ->orderBy('place_title')
                ->indexBy('place_id')
                ->andWhere("mine_id!=1")
                ->asArray()
                ->all();
            if ($places) {
                $result = $places;
            }
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * GetHandbookKindPlace - Метод получения списка видов мест из типовых объектов
     *
     * @param null $data_post
     * @return array
     * @package frontend\controllers\ordersystem
     * @example http://127.0.0.1/read-manager-amicum?controller=handbooks\HandbookPlace&method=GetHandbookKindPlace&subscribe=&data={}
     *
     * Документация на портале:
     * @author Якимов М.Н.
     * Created date: on 22.03.2023
     * @since ver
     */
    public static function GetHandbookKindPlace($data_post = NULL)
    {
        $log = new LogAmicumFront("GetHandbookKindPlace");

        $result = (object)array();

        try {
            $log->addLog("Начало выполнения метода");
            $result = TypicalObject::find()
                ->select('id,title')
                ->where(['object_table' => 'place'])
                ->indexBy('id')
                ->all();


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }
}
