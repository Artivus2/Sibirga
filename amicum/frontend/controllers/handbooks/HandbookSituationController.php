<?php

namespace frontend\controllers\handbooks;

//ob_start();
use frontend\models\AccessCheck;
use frontend\models\DangerLevel;
use frontend\models\Event;
use frontend\models\EventSituation;
use frontend\models\GroupSituation;
use frontend\models\KindGroupSituation;
use frontend\models\Main;
use frontend\models\Situation;
use frontend\models\TypicalObject;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\Response;

class HandbookSituationController extends \yii\web\Controller
{
    // GetDangerLevel                       - Получение справочника уровней риска
    // SaveDangerLevel                      - Сохранение нового уровня риска
    // DeleteDangerLevel                    - Удаление уровня риска

    // GetSituation()      - Получение справочника ситуаций
    // SaveSituation()     - Сохранение справочника ситуаций
    // DeleteSituation()   - Удаление справочника ситуаций

    public function actionIndex()
    {
        //  $cache = Yii::$app->cache;
        $kinds = KindGroupSituation::find()
            ->select('id, title')
            ->orderBy('title ASC')
            ->asArray()
            ->all(); //Выборка всех видов групп ситуаций
        $groups = GroupSituation::find()
            ->select(['title', 'id'])
            ->orderBy('title ASC')
            ->asArray()
            ->all();
        $situations = Situation::find()
            ->select(['title', 'id'])
            ->orderBy('title ASC')
            ->asArray()
            ->all();
        $events = Event::find()
            ->select(['title', 'id'])
            ->orderBy('title ASC')
            ->asArray()
            ->all();
        return $this->render//переход на страницу справочника
        ('index',
            [
                'kinds' => $kinds,
                'groups' => $groups,
                'situations' => $situations,
                'events' => $events,
            ]
        );
    }


    /*метод добавления элемента
    * Входные параметры (принимаются от ajax-запроса):
    * - $post['type'] - (string) тип добавляемого элемента
    * - $post['title'] - (string) название добавляемого элемента
    * - $post['parent_id'] - (int) идентификатор родительского элемента
    * - $post['danger_level'] - (int) идентификатор уровня опасности (только для ситуаций)
    * - $post['kind_id'] - (int) идентификатор вида групп ситуаций
    * Выходные параметры отсутствуют, происходит возврат
    * групп ситуаций, ситуаций и событий с учетом добавленного элемента в
    * одном многоуровневом массиве в ajax-запрос через вывод значения на экран
   */
    public function actionAddElement()
    {
        $session = Yii::$app->session;                                                                                  //старт сессии
        if(isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 60)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                $is_saved = false; //переменна - флаг сохранения модели
                switch($post['type']){
                    case 'kind': //если тип элемента - вид групп ситуаций
                        if($post['title'] != '') {//если название вида групп ситуаций задано
                            //если модели с таким названием не существует
                            $model = KindGroupSituation::find()->where(['title' => $post['title']])->one();
                            if(!$model) {
                                $model = new KindGroupSituation(); //создается новая модель
                                $model->title = $post['title']; //сохраняется название вида групп ситуаций
                                if ($model->save()) { //если модель сохранилась
                                    $kinds = KindGroupSituation::find()
                                        ->select(['title', 'id'])
                                        ->asArray()
                                        ->all();
                                    $this->returnArrayToAjax($kinds);
                                    return;
                                }
                            }
                            else{
                                echo "Такой вид групп ситуаций уже существует. ";
                            }
                        }
                        else{
                            echo "Название вида групп ситуаций не задано. ";
                        }
                        break;
                    case 'group': //если тип элемента - группа ситуаций
                        if($post['title'] != '') {//если название группы ситуаций задано
                            //если модели с таким названием не существует
                            $model = GroupSituation::find()->where(['title' => $post['title']])->one();
                            if (!$model) {
                                $model = new GroupSituation();//создается новая модель
                                $model->title = $post['title']; //сохраняется название группы ситуаций
                                $model->kind_group_situation_id = $post['parent_id']; //привязка к указанному виду групп ситуаций
                                if ($model->save()) { //если модель сохранилась
                                    $is_saved = true; //отметить этот факт
                                }
                            }
                            else{
                                echo "Такая группа ситуаций уже существует. ";
                            }
                        }
                        else{
                            echo "Название группы ситуаций не задано. ";
                        }
                        break;
                    case 'situation': //если тип элемента - ситуация
                        if($post['title'] != '') {//если название ситуации задано
                            //если модели с таким названием не существует
                            $model = Situation::find()->where(['title' => $post['title']])->one();
                            if (!$model) {
                                $model = new Situation(); //создается новая модель
                                $main = new Main();
                                $main->db_address = 'amicum';
                                $main->table_address = 'situation';
                                $main->save();
                                $object = TypicalObject::find()->where(['title' => 'Ситуация'])->one();
                                $model->id = $main->id;
                                $model->title = $post['title']; //сохраняется название ситуации
                                $danger_level = DangerLevel::find()->where(['number_of_level' => $post['danger_level']])->one();
                                $model->danger_level_id = $danger_level->id; //привязка к уровню опасности
                                $model->group_situation_id = $post['parent_id']; //Привязка к группе ситуаций
                                $model->object_id = $object->id; //Привязка к классу объекта
                                if ($model->save()) { //если модель сохранилась
                                    $is_saved = true; //отметить этот факт
                                }
                            }
                            else{
                                echo "Такая ситуация уже существует. ";
                            }
                        }
                        else{
                            echo "Название ситуации не задано. ";
                        }
                        break;
                    case 'event': //если тип элемента - событие
                        if($post['title'] != '') {//если название события задано
                            //если модели с таким названием не существует
                            $model = Event::find()->where(['title' => $post['title']])->one();
                            if (!$model) {
                                $model = new Event(); //создается новая модель
                                $main = new Main();
                                $main->db_address = 'amicum';
                                $main->table_address = 'event';
                                $main->save();
                                $object = TypicalObject::find()->where(['title' => 'Событие'])->one();
                                $model->id = $main->id;
                                $model->title = $post['title']; //сохраняется название события
                                $model->object_id = $object->id; //Привязка к классу объекта
                                if(!$model->save()){
                                    var_dump($model->errors);
                                }
                            }
                            $link = new EventSituation(); //создается новая модкль привязки ситуаций и событий
                            $link->event_id = $model->id; //привязка события
                            $link->situation_id = $post['parent_id']; //привязка ситуации
                            if($link->save()){ //если привязка сохранилась
                                $is_saved = true; //отметить этот факт
                            }
                            else{
                                var_dump($link->errors);
                            }

                        }
                        else{
                            echo "Название события не задано. ";
                        }
                        break;
                }
                if($is_saved){//если модель была успешно сохранена
                    //вызов функции отправки построенных данных в ajax-запрос
                    $this->returnArrayToAjax($this->buildArray($post['kind_id']));
                }
                else{ //иначе вывести ошибку
                    echo "Не могу сохранить данные";
                }
            }
            else{
                echo "No access";
            }
        }
        else{
            echo "Not registered";
        }
    }


    /*функция редактирования элемента
     * Входные параметры (принимаются от ajax-запроса):
     * - $post['type'] - (string) тип изменяемого элемента
     * - $post['elem_id'] - (int) идентификатор изменяемого элемента
     * - $post['title'] - (string) название изменяемого элемента
     * - $post['danger_level'] - (int) идентификатор уровня опасности (только для ситуаций)
     * - $post['kind_id'] - (int) идентификатор вида групп ситуаций
     * Выходные параметры отсутствуют, происходит возврат
     * групп ситуаций, ситуаций и событий с учетом измененного элемента в
     * одном многоуровневом массиве в ajax-запрос через вывод значения на экран
     */
    public function actionEditElement(){
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if(isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 62)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                $is_saved = false; //переменна - флаг сохранения модели
                switch($post['type']){
                    case 'kind': //если тип элемента - вид групп ситуаций
                        if($post['title'] != '') {//если название вида групп ситуаций задано
                            //если модели с таким названием не существует
                            $model = KindGroupSituation::find()->where(['title' => $post['title']])->one();
                            if(!$model) {
                                //выборка вида групп ситуаций с указанным id
                                $model = KindGroupSituation::findOne($post['elem_id']);
                                $model->title = $post['title']; //запись нового названия вида групп ситуаций
                                if ($model->save()) { //если привязка сохранилась
                                    $kinds = KindGroupSituation::find()
                                        ->select(['title', 'id'])
                                        ->asArray()
                                        ->all();
                                    $this->returnArrayToAjax($kinds);
                                    return;
                                }
                            }
                            else{
                                echo "Такой вид групп ситуаций уже существует. ";
                            }
                        }
                        else{
                            echo "Название вида групп ситуаций не задано. ";
                        }

                        break;
                    case 'group': //если тип элемента - группа ситуаций
                        if($post['title'] != '') {//если название группы ситуаций задано
                            //если модели с таким названием не существует
                            $model = GroupSituation::find()->where(['title' => $post['title']])->one();
                            //выборка группы ситуаций с указанным id
                            if (!$model) {     $model = GroupSituation::findOne($post['elem_id']);
                                $model->title = $post['title']; //запись нового названия группы ситуаций
                                if($model->save()){ //если привязка сохранилась
                                    $is_saved = true; //отметить этот факт
                                }
                            }
                            else{
                                echo "Такая группа ситуаций уже существует. ";
                            }
                        }
                        else{
                            echo "Название группы ситуаций не задано. ";
                        }
                        break;
                    case 'situation': //если тип элемента - ситуация
                        if($post['title'] != '') {//если название ситуации задано
                            //если модели с таким названием не существует
                            $model = Situation::find()->where(['title' => $post['title']])->one();
                            if (!$model || $model->id == $post['elem_id']) {
                                $model = Situation::findOne($post['elem_id']); //выборка ситуации с указанным id
                                $model->title = $post['title']; //запись нового названия ситуации
                                $danger_level = DangerLevel::find()->where(['number_of_level' => $post['danger_level']])->one();
                                $model->danger_level_id = $danger_level->id; //привязка к уровню опасности
                                if($model->save()){ //если привязка сохранилась
                                    $is_saved = true; //отметить этот факт
                                }
                            }
                            else{
                                echo "Такая ситуация уже существует. ";
                            }
                        }
                        else{
                            echo "Название ситуации не задано. ";
                        }
                        break;
                    case 'event': //если тип элемента - событие
                        if($post['title'] != '') {//если название события задано
                            //если модели с таким названием не существует
                            $model = Event::find()->where(['title' => $post['title']])->one();
                            if (!$model) {
                                $model = Event::findOne($post['elem_id']); //выборка события с указанным id
                                $model->title = $post['title']; //запись нового названия события
                                if($model->save()){ //если привязка сохранилась
                                    $is_saved = true; //отметить этот факт
                                }
                            }
                            else{
                                echo "Такое событие уже существует. ";
                            }
                        }
                        else{
                            echo "Название события не задано. ";
                        }
                        break;
                }
                if($is_saved){ //если модель была успешно сохранена
                    //вызов функции отправки построенных данных в ajax-запрос
                    $this->returnArrayToAjax($this->buildArray($post['kind_id']));
                }
                else{ //иначе вывести ошибку
                    echo "Не могу сохранить данные";
                }
            }
            else{
                echo "No access";
            }
        }
        else{
            echo "Not registered";
        }
    }


    /*функция удаления элемента
     * Входные параметры (принимаются от ajax-запроса):
     * - $post['type'] - (string) тип удаляемого элемента
     * - $post['elem_id'] - (int) идентификатор удаляемого элемента
     * - $post['kind_id'] - (int) идентификатор вида групп ситуаций
     * Выходные параметры отсутствуют, происходит возврат
     * групп ситуаций, ситуаций и событий с учетом удаленного элемента в
     * одном многоуровневом массиве в ajax-запрос через вывод значения на экран
    */
    public function actionDeleteElement(){
        $session = Yii::$app->session;                                                                                  //старт сессии
        $session->open();                                                                                               //открыть сессию
        if(isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 61)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                $is_deleted = false;
                switch($post['type']){
                    case 'kind': //если тип элемента - вид групп ситуаций
                        $model = KindGroupSituation::findOne($post['elem_id']); //выборка вида групп ситуаций с указанным id
                        if($model->delete()){ //удаление выбранной модели
                            $kinds = KindGroupSituation::find()
                                ->select(['title', 'id'])
                                ->asArray()
                                ->all();
                            $this->returnArrayToAjax($kinds);
                            return;
                        }
                        break;
                    case 'group': //если тип элемента - группа ситуаций
                        $model = GroupSituation::findOne($post['elem_id']); //выборка группы ситуаций с указанным id
                        if($model->delete()) { //удаление выбранной модели
                            $is_deleted = true;
                        }
                        break;
                    case 'situation': //если тип элемента - ситуация
                        $model = Situation::findOne($post['elem_id']); //выборка ситуации с указанным id
                        if($model->delete()) { //удаление выбранной модели
                            $is_deleted = true;
                        }
                        break;
                    case 'event': //если тип элемента - событие
                        $model = EventSituation::find()->where(['event_id' => $post['elem_id'], 'situation_id' => $post['sit_id']])->one(); //выборка события с указанным id
                        if($model->delete()) { //удаление выбранной модели
                            $is_deleted = true;
                        }
                        break;
                }
                //вызов функции отправки построенных данных в ajax-запрос
                $this->returnArrayToAjax($this->buildArray($post['kind_id']));
            }
            else{
                $errors[] = "Недостаточно прав для совершения данной операции";
            }
        }
        else{
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";$this->redirect('/' );
        }
    }

    /*функция копирования элемента
       * Входные параметры (принимаются от ajax-запроса):
       * - $post['type'] - (string) тип копируемого элемента
       * - $post['elem_id'] - (int) идентификатор копируемого элемента
       * - $post['title'] - (string) - название копируемого элемента
       * - $post['parent_id'] - (int) - идентификатор родительского элемента
       * - $post['kind_id'] - (int) - идентификатор вида групп ситуаций
       * Выходные параметры отсутствуют, происходит возврат
       * групп ситуаций, ситуаций и событий с учетом скопированного элемента в
       * одном многоуровневом массиве в ajax-запрос через вывод значения на экран
      */
    public function actionCopyElement(){
        $session = Yii::$app->session;                                                                                  //старт сессии
        $errors = array();
        $groups = array();
        if(isset($session['sessionLogin'])) {                                                                           //если в сессии есть логин
            if (AccessCheck::checkAccess($session['sessionLogin'], 60)) {                                                //если пользователю разрешен доступ к функции
                $post = Yii::$app->request->post(); //получение данных от ajax-запроса
                $is_saved = false; //переменна - флаг сохранения модели
                if (isset($post['kind_id']) && $post['kind_id'] != '') {
                    if ($post['type'] == 'situation') { //если тип элемента - ситуация
                        if ($post['title'] != '') {//если название ситуации задано
                            //если модели с таким названием не существует или название не изменилось
                            $model = Situation::find()->where(['title' => $post['title']])->one();
                            if (!$model || $model->id == $post['elem_id']) {
                                $old_sit = Situation::findOne($post['elem_id']); //выборка ситуации с указанным id
                                $new_sit = new Situation(); //создание новой ситуации
                                $main = new Main();
                                $main->db_address = 'amicum';
                                $main->table_address = 'situation';
                                $main->save();
                                $object = TypicalObject::find()->where(['title' => 'Ситуация'])->one();
                                $new_sit->id = $main->id;
                                $new_sit->title = $post['title']; //сохранение переданного названия в новую ситуацию
                                //сохранение уровня опасности копируемой ситуации в новую
                                $new_sit->danger_level_id = $old_sit->danger_level_id;
                                //привязка новой ситуации к выбранной группе ситуаций
                                $new_sit->group_situation_id = $post['parent_id'];
                                $new_sit->object_id = $object->id; //Привязка к классу объекта
                                if ($new_sit->save()) { //если привязка сохранилась
                                    //привязка событий копируемой ситуации к новой
                                    foreach ($old_sit->eventSituations as $evenSit) {
                                        $link = new EventSituation();
                                        $link->situation_id = $new_sit->id;
                                        $link->event_id = $evenSit->event_id;
                                        $link->save();
                                    }
                                    $is_saved = true; //отметить этот факт
                                }
                            } else {
                                $errors[] = "Такая ситуация уже существует. ";
                            }
                        } else {
                            $errors[] = "Название ситуации не задано. ";
                        }
                    } else if ($post['type'] == 'event') { //если тип элемента - событие
                        //если задан номер события - добавляется только привязка
                        if (isset($post['elem_id']) && $post['elem_id'] != '') {
                            $elems = explode(',', $post['elem_id']);
                            foreach ($elems as $elem) {
                                if (!EventSituation::find()->where(['situation_id' => $post['parent_id'], 'event_id' => $elem])->one()) {
                                    $link = new EventSituation(); //создание новой модели привязки ситуации и события
                                    $link->event_id = $elem; //привязка переданного события
                                    $link->situation_id = $post['parent_id']; //привязка переданной ситуации
                                    if ($link->save()) { //если привязка сохранилась
                                        $is_saved = true; //отметить этот факт
                                    }
                                }
                            }
                        } else {
                            $errors[] = "События не переданы";
                        }
                    }
                    //вызов функции отправки построенных данных в ajax-запрос
                    $groups = self::buildArray($post['kind_id']);
                }
                else {
                    $errors[] = "Не передан идентификатор вида групп ситуаций";
                }
            }
            else{
                $errors[] =  'У вас недостаточно прав для выполнения этого действия';
            }
        }
        else{
            $errors[] = "Время сессии закончилось. Требуется повторный ввод пароля";
            $this->redirect('/' );
        }
        $result = array('errors' => $errors, 'groups_array' => $groups);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }


    /*метод возврата массива ajax-запросу в формате json
     * Входные параметры:
     * - $array - (array) массив, который необходимо вернуть в формате json
     * Выходные параметры отсутствуют, происходит возврат значения в
     * ajax-запрос через вывод значения на печать
    */
    public function returnArrayToAjax($array){
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data =  (array)$array; //вывод переформатированного в json массива
    }


    /*метод построения массива групп ситуаций со всей внутренней структурой по заданному номеру вида групп ситуаций
    * Входные параметры:
    * - $kind - (int) идентификатор вида групп ситуаций
    * Выходные параметры
    * - $groups - (array) массив групп ситуаций со всей их структурой
   */
    public function buildArray($kind)
    {
        //выборка групп ситуаций с полученным номером вида групп ситуаций
        $model = GroupSituation::find()->where(['kind_group_situation_id' => $kind])->all();
        $groups = array(); //объявление массива групп ситуаций
        $i = 0;
        $t1 = microtime(true);
        foreach($model as $group){ //в цикле по группам
            //в массив сохраняется название вида групп ситуаций, индекс элемента - идентификатор группы
            $groups[$i]['id'] = $group->id;
            $groups[$i]['title'] = $group->title;
            $j = 0;
            $groups[$i]['situations'] = array();
            foreach($group->situations as $situation){ //в цикле по ситуациям группы
                //сохранение в массив названия ситуации, индекс элемента - идентификатор ситуации
                $groups[$i]['situations'][$j]['id'] = $situation->id;
                $groups[$i]['situations'][$j]['title'] = $situation->title;
                //сохранение в массив уровня опасности ситуации
                $groups[$i]['situations'][$j]['danger_level'] = $situation->dangerLevel->number_of_level;
                $k = 0;
                $groups[$i]['situations'][$j]['events'] = array();
                foreach ($situation->events as $event){ //в цикле по событиям ситуации
                    //сохранение в массив названия события, индекс элемента - идентификатор события
                    $groups[$i]['situations'][$j]['events'][$k]['id'] = $event->id;
                    $groups[$i]['situations'][$j]['events'][$k]['title'] = $event->title;
                    $k++;
                }
                ArrayHelper::multisort($groups[$i]['situations'][$j]['events'], 'title', SORT_ASC);
                $j++;
            }
            ArrayHelper::multisort($groups[$i]['situations'], 'title', SORT_ASC);
            $i++;
        }
        ArrayHelper::multisort($groups, 'title', SORT_ASC);
        $t2 = microtime(true) - $t1;
        //echo "Время выполнения скрипта = $t2";
        /* $cache = Yii::$app->cache;
         $kinds = $cache->get('kinds');
         $groups = $kinds[$kind]['groups'];*/
        return $groups;
    }



    /*метод для ajax-запроса показа данных для выбранного вида групп ситуаций
     * Входные параметры:
     * - $post['kind_id'] - (int) идентификатор вида групп ситуаций,
     *      принимается от ajax-запроса
     * Выходные параметры отсутствуют, происходит возврат
     * групп ситуаций, ситуаций и событий в одном многоуровневом массиве в
     * ajax-запрос через вывод значения на экран
    */
    public function actionShowSituations()
    {
        $post = Yii::$app->request->post(); //получение данных от ajax-запроса
        //вызов функции отправки построенных данных в ajax-запрос
        $this->returnArrayToAjax($this->buildArray($post['kind_id']));
    }

    /**
     * Метод GetDangerLevel() - Получение справочника уровней риска
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": 1,					        // идентификатор уровня риска
     *      "title":"Опасная",				    // наименование уровня риска
     *      "number_of_level":"2"				// уровень риска
     * ]
     * warnings:{}                              // массив предупреждений
     * errors:{}                                // массив ошибок
     * status:1                                 // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookSituation&method=GetDangerLevel&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 09:27
     */
    public static function GetDangerLevel()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetDangerLevel';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $danger_level_data = DangerLevel::find()
                ->asArray()
                ->all();
            if(empty($danger_level_data)){
                $warnings[] = $method_name.'. Справочник уровней риска пуст';
            }else{
                $result = $danger_level_data;
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
     * Метод SaveDangerLevel() - Сохранение нового уровня риска
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "danger_level":
     *  {
     *      "danger_level_id":-1,							// идентификатор уровня риска (-1 = новый уровень риска)
     *      "title":"DANGER_LEVEL_TEST",					// наименование уровня риска
     *      "number_of_level":5								// уровень риска (единичное число, 2 одинаковых уровня риска быть не может!!!!!!!!!)
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "danger_level_id": 5,					        // идентификатор сохранённого уровня риска
     *      "title": "DANGER_LEVEL_TEST"				    // сохранённое наименование уровня риска
     *      "number_of_level": 5				            // сохранённое значение уровня риска
     * }
     * warnings:{}                                          // массив предупреждений
     * errors:{}                                            // массив ошибок
     * status:1                                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookSituation&method=SaveDangerLevel&subscribe=&data={"danger_level":{"danger_level_id":-1,"title":"DANGER_LEVEL_TEST","number_of_level":5}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 09:30
     */
    public static function SaveDangerLevel($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveDangerLevel';
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
            if (!property_exists($post_dec, 'danger_level'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $danger_level_id = $post_dec->danger_level->danger_level_id;
            $title = $post_dec->danger_level->title;
            $number_of_level = $post_dec->danger_level->number_of_level;
            $danger_level = DangerLevel::findOne(['id'=>$danger_level_id]);
            if (empty($danger_level)){
                $danger_level = new DangerLevel();
            }
            $danger_level->title = $title;
            $danger_level->number_of_level = $number_of_level;
            if ($danger_level->save()){
                $danger_level->refresh();
                $chat_type_data['danger_level_id'] = $danger_level->id;
                $chat_type_data['title'] = $danger_level->title;
                $chat_type_data['number_of_level'] = $danger_level->number_of_level;
            }else{
                $errors[] = $danger_level->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении нового уровня риска');
            }
            unset($danger_level);
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
     * Метод DeleteDangerLevel() - Удаление уровня риска
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "danger_level_id": 6             // идентификатор удаляемого уровня риска
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookSituation&method=DeleteDangerLevel&subscribe=&data={"danger_level_id":10}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 17.03.2020 13:49
     */
    public static function DeleteDangerLevel($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteDangerLevel';
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
            if (!property_exists($post_dec, 'danger_level_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name.'. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name.'. Данные с фронта получены';
            $danger_level_id = $post_dec->danger_level_id;
            $del_danger_level = DangerLevel::deleteAll(['id'=>$danger_level_id]);
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


    // GetSituation()      - Получение справочника ситуаций
    // SaveSituation()     - Сохранение справочника ситуаций
    // DeleteSituation()   - Удаление справочника ситуаций

    /**
     * Метод GetSituation() - Получение справочника ситуаций
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                         // ключ справочника ситуаций
     *      "title":"ACTION",                // название ситуации
     *      "group_situation_id":"-1",       // ключ группы ситуации
     *      "danger_level_id":"-1",          // ключ уровня опасности
     *      "object_id":"-1",                // ключ типа объекта
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=GetSituation&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetSituation()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetSituation';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_situation = Situation::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_situation)) {
                $result = (object) array();
                $warnings[] = $method_name . '. Справочник ситуаций пуст';
            } else {
                $result = $handbook_situation;
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
     * Метод SaveSituation() - Сохранение справочника ситуаций
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "situation":
     *  {
     *      "situation_id":-1,               // ключ справочника ситуаций
     *      "title":"ACTION",                // название ситуации
     *      "group_situation_id":"-1",       // ключ группы ситуации
     *      "danger_level_id":"-1",          // ключ уровня опасности
     *      "object_id":"-1",                // ключ типа объекта
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "situation_id":-1,               // ключ справочника ситуаций
     *      "title":"ACTION",                // название ситуации
     *      "group_situation_id":"-1",       // ключ группы ситуации
     *      "danger_level_id":"-1",          // ключ уровня опасности
     *      "object_id":"-1",                // ключ типа объекта
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=SaveSituation&subscribe=&data={"situation":{"situation_id":-1,"title":"ACTION"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveSituation($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveSituation';
        $handbook_situation_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'situation'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_situation_id = $post_dec->situation->situation_id;
            $title = $post_dec->situation->title;
            $group_situation_id = $post_dec->situation->group_situation_id;
            $danger_level_id = $post_dec->situation->danger_level_id;
            $object_id = $post_dec->situation->object_id;
            $new_handbook_situation_id = Situation::findOne(['id' => $handbook_situation_id]);
            if (empty($new_handbook_situation_id)) {
                $new_handbook_situation_id = new Situation();
            }
            $new_handbook_situation_id->title = $title;
            $new_handbook_situation_id->group_situation_id = $group_situation_id;
            $new_handbook_situation_id->danger_level_id = $danger_level_id;
            $new_handbook_situation_id->object_id = $object_id;
            if ($new_handbook_situation_id->save()) {
                $new_handbook_situation_id->refresh();
                $handbook_situation_data['situation_id'] = $new_handbook_situation_id->id;
                $handbook_situation_data['title'] = $new_handbook_situation_id->title;
                $handbook_situation_data['group_situation_id'] = $new_handbook_situation_id->group_situation_id;
                $handbook_situation_data['danger_level_id'] = $new_handbook_situation_id->danger_level_id;
                $handbook_situation_data['object_id'] = $new_handbook_situation_id->object_id;
            } else {
                $errors[] = $new_handbook_situation_id->errors;
                throw new \Exception($method_name . '. Ошибка при сохранении справочника ситуаций');
            }
            unset($new_handbook_situation_id);
        } catch (\Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_situation_data;
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Метод DeleteSituation() - Удаление справочника ситуаций
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "situation_id": 98             // идентификатор справочника ситуаций
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\HandbookEvent&method=DeleteSituation&subscribe=&data={"situation_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteSituation($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteSituation';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new \Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'situation_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new \Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_situation_id = $post_dec->situation_id;
            $del_handbook_situation = Situation::deleteAll(['id' => $handbook_situation_id]);
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
