<?php
/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\controllers\handbooks;

use backend\controllers\Assistant as BackendAssistant;
use Exception;
use frontend\controllers\Assistant;
use frontend\models\Brigade;
use frontend\models\Chane;
use frontend\models\OperationWorker;
use frontend\models\Worker;
use Throwable;
use yii\web\Controller;

class BrigadeController extends Controller
{
    // GetBrigades                      - Получение всего списка бригад
    // SaveBrigade                      - Сохранение бригады
    // FullDeleteBrigade                - Удаление бригады если на неё нет операций
    // AddBrigade                       - Добавление одной бригады или изменение одной бригады (смена стауса у старой бригады и добавление новой)
    // ChangeStatus                     - Метод смены статуса у бригады на "Не актуально"
    // DeleteBrigade                    - Удаление существующей бригады
    // AddBrigader                      - Добавление бригадира в бригаду (смена статуса статуса у старой бригады и добавление новой бригады с новым бригадиром)
    // actionTestAddBrigaderChane       - тестовый Добавление бригадира в бригаду (смена статуса статуса у старой бригады и добавление новой бригады с новым бригадиром)

    // GetChane                         - Получение справочника звеньев
    // SaveChane                        - Сохранение справочника звеньев
    // DeleteChane                      - Удаление справочника звеньев
    // AddChane                         - Добавление одной бригады или изменение одной бригады (смена стауса у старой бригады и добавление новой)
    // AddBrigaderChane                 - Создание новой бригады с бригадиром и создание звена с только что созданой бригадой и звеневым (бригадир)


    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionTestAddBrigaderChane()
    {
        $result = $this->AddBrigaderChane(2913325, 801);
        Assistant::PrintR($result);
    }

    /**
     * Назначение: AddBrigade - Добавление одной бригады или изменение одной бригады (смена стауса у старой бригады и добавление новой)
     * Название метода: AddBrigade()
     * @param $brigade_id - ключ бригады
     * @param $description - название бригады
     * @param $brigader_id - ключ бригадира
     * @param $company_department_id - ключ департамента
     * @param $brigade_status - ключ статуса бригады
     * @return array
     *
     *
     * @package frontend\controllers\handbooks
     *
     * Входные обязательные параметры: $description             - описание бригады
     *                                 $date                    - дата формирования бригады
     *                                 $brigader_id             - идентификатор бригадира
     *                                 $status_id               - статус бригады
     *                                 $company_department_id   - идентификатор участка
     * @see
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 20.05.2019 14:04
     * @since ver
     */
    public static function AddBrigade($brigade_id, $description, $brigader_id, $company_department_id, $brigade_status = 1)
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        $id_brigade = 0;
        try {
            $warnings[] = 'AddBrigade. Начал выполнять метод.';
            $brigade = Brigade::findOne(['id' => $brigade_id]);
            if (!$brigade or ($brigade and $brigade->company_department_id == $company_department_id)) {
                if (!$brigade) {
                    $brigade = new Brigade();
                }
                $brigade->description = $description;
                $brigade->date_time = date('Y-m-d H:i:s');
                $brigade->brigader_id = $brigader_id;
                $brigade->status_id = $brigade_status;
                $brigade->company_department_id = $company_department_id;
                if (!$brigade->save()) {                                                                                  //если бригада не сохранилась тогда записываем в массив ошибок
                    $errors[] = $brigade->errors;                                                                       //записываем в ошибки при добавлении пригады в массив ошибок
                    throw new Exception("AddBrigade. Не удалось сохранить бригаду");
                }
                $brigade->refresh();
                $id_brigade = $brigade->id;
            } else {
                $id_brigade = $brigade_id;
            }
            $warnings[] = 'AddBrigade. Данные успешно сохранены. Идентификатор новой бригады ' . $id_brigade;
        } catch (Throwable $ex) {
            $errors[] = "AddBrigade. Исключение";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        $warnings[] = 'AddBrigade. Закончил выполнять метод.';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'id' => $id_brigade);
    }

    /**
     * Назначение: AddChane - Добавление одной бригады или изменение одной бригады (смена стауса у старой бригады и добавление новой)
     * Название метода: AddChane()
     * @param $brigade_id - идентификатор бригады, если передана -1, создается новая бригада
     * @param $chaner - ключ звеньевого
     * @param $chane_title - название звена
     * @param $chane_type - тип звена
     * @param $chane_id - ключ звена
     * @return array
     *
     *
     * @package frontend\controllers\handbooks
     *
     * Входные обязательные параметры: $description,$date,$brigader_id,$status_id,$department_id
     * @see
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 20.05.2019 14:04
     * @since ver
     */
    public static function AddChane($brigade_id, $chaner, $chane_title, $chane_type, $chane_id = -1)
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        $id_chane = -1;
        try {
            $warnings[] = 'AddChane. Начал выполнять метод';
            $new_chane = Chane::findOne(['id' => $chane_id]);
            if (!$new_chane or ($new_chane and $new_chane->brigade_id == $brigade_id)) {
                if (!$new_chane) {
                    $warnings[] = 'AddChane. Создаю звено с 0';
                    $new_chane = new Chane();                                                                   // Добавляем новое звено прикрепленное к созданной бригаде
                }
                $new_chane->brigade_id = (int)$brigade_id;
                $new_chane->chaner_id = (int)$chaner;
                $new_chane->title = $chane_title;
                $new_chane->chane_type_id = (int)$chane_type;

                if (!$new_chane->save()) {                                                                             //если бригада не сохранилась тогда записываем в массив ошибок
                    $errors[] = $new_chane->errors;                                                                       //записываем в ошибки при добавлении пригады в массив ошибок
                    throw new Exception("AddChane.шибка при сохранении данных модели Chane");
                }
                $new_chane->refresh();
                $id_chane = $new_chane->id;
            } else {
                $id_chane = $chane_id;
            }
            $warnings[] = 'AddChane. Данные успешно сохранены. Идентификатор новой звена ' . $id_chane;

        } catch (Throwable $ex) {
            $errors[] = "AddChane. Исключение";
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }

        $warnings[] = 'AddChane. Закончил выполнять метод';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'id' => $id_chane);
    }


    /**
     * Назначение: ChangeStatus - Метод смены статуса у бригады на "Не актуально"
     * Название метода: ChangeStatus()
     * @param $id - идентификатор бригады
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * Входные обязательные параметры:$id,$status_id
     * @see
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 20.05.2019 14:06
     * @since ver
     */
    public static function ChangeStatus($id)
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        if (!isset($id)) {
            $warnings[] = 'ChangeStatus. Переданы пустые данные.';
            $status = 0;
            return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        }
        $warnings[] = 'BrigadeController. Данные успешно переданы.';
        $change_status = Brigade::find()
            ->where(['id' => $id])
            ->limit(1)
            ->one();                                                                                                    //ищем есть ли такая строка в БД
        if (isset($change_status))                                                                                     //если есть, тогда меняем статус
        {
            $warnings[] = 'ChangeStatus. Запись успешно найдена.';
            $change_status->status_id = 19;
            if ($change_status->save())                                                                                 //если статус сохранился записываем информацию о том, что данные успешно сохранены
            {
                $warnings[] = 'ChangeStatus. Данные успешно сохранены. Новый статус: Не актуально';
            } else                                                                                                        //иначе выводим записываем информацию о том, что при сохранении статуса бригады произошла ошибка
            {
                $errors = $change_status->errors;                                                                      //записываем в ошибки при смене статуса бригады в массив ошибок
                $warnings[] = 'ChangeStatus. Ошибки при сохранении статуса старой версии бригады.';
            }
        } else {
            $errors[] = "Таких данных не существует в БД. Идентификатор искомой бригалы -  {$id}";
            $warnings[] = "ChangeStatus. Строка не найдена в БД. Идентификатор искомой бригалы -  {$id}";
        }
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);

    }

    /**
     * Назначение: DeleteBrigade - Удаление существующей бригады
     * Название метода: DeleteBrigade()
     * @param $id - идентификатор бригады, которую необходимо удалить
     * @return array
     *
     * Входные необязательные параметры
     * @throws
     * Документация на портале:
     * @example
     *
     * @package frontend\controllers\handbooks
     *
     * Входные обязательные параметры:$id
     * @see
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 20.05.2019 14:07
     * @since ver
     */
    public static function DeleteBrigade($id)
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        if (!isset($id)) {
            $warnings[] = 'DeleteBrigade. Переданы пустые данные.';
            $status = 0;
            return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        }
        $warnings[] = 'DeleteBrigade. Идентификатор бригады успешно получен.';
        $delete_brigade = Brigade::find()
            ->where(['id' => $id])
            ->limit(1)
            ->one();                                                                                                    //ищем есть ли такая бригада в БД
        if (isset($delete_brigade))                                                                                    //если есть, тогда удаляем бригаду
        {
            $warnings[] = 'DeleteBrigade. Запись успешно найдена.';
            if ($delete_brigade->delete() !== FALSE)                                                                    //если метод удаления не вернул false, тогда записываем информацию о том, что удаление прошло успешно
            {
                $warnings[] = 'DeleteBrigade. Удаление прошло успешно.';
            } else                                                                                                        //иначе записываем информацию о том, что в процессе удаления бригады произошли ошибки
            {
                $errors[] = 'Удаление бригады не возможно. Бригада используется в наряде';
//                $errors = $delete_brigade->errors;                                                                     //записываем ошибки при удалении бригады в массив ошибок
            }
        } else                                                                                                            //записываем информацию о том, что запись не найдена
        {
            $errors[] = 'Запись не найдена';
            $warnings[] = "DeleteBrigade. Запись не найдена. Идентификатор переданной бригады: {$id}";
        }
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);

    }

    /**
     * Назначение: AddBrigader - Добавление бригадира в бригаду (смена статуса статуса у старой бригады и добавление новой бригады с новым бригадиром)
     * Название метода: AddBrigader()
     * @param $id - идентификатор бригады
     * @param $brigader_id - идентификатор бригадира, которого необходимо добавить
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * Входные обязательные параметры:$id, $brigader_id, $status_id
     * @see
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 20.05.2019 14:08
     * @since ver
     */
    public static function AddBrigader($id, $brigader_id)
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        if (isset($id, $brigader_id)) {
            $warnings[] = 'AddBrigader. Переданы пустые данные.';
            $status = 0;
            return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        }
        $old_brigade = Brigade::find()
            ->where(['id' => $id])
            ->limit(1)
            ->one();                                                                                                    //ищем такую бригаду в БД
        if (isset($old_brigade))                                                                                        //если нашли тогда, меняем статус у старой бригады
        {
            $warnings[] = 'AddBrigader. Запись успешно найдена.';
            $old_brigade->status_id = 19;
            if ($old_brigade->save())                                                                                   //если статус сменился, тогда создаём новую бригаду
            {
                $warnings[] = 'BrigadeController. Статус успешно изменён.';
                $new_brigader = new Brigade();                                                                          //и создаём новую бригаду с новымм: биргадиром, версией, датой.
                $new_brigader->description = $old_brigade->description;
                $new_brigader->date_time = date('Y-m-d H:i:s');
                $new_brigader->brigader_id = $brigader_id;
                $new_brigader->status_id = 1;
                $new_brigader->company_department_id = $old_brigade->department_id;
                if (!$new_brigader->save())                                                                             //если бригада не сохранилась, тогда записываем в массив ошибок
                {
                    $errors = $new_brigader->errors;                                                                    //записывем в ошибки при создании новой бригады в массив ошибок
                    $warnings[] = 'AddBrigader. Ошибка при изменении бригадира бригады.';
                } else {
                    $warnings[] = 'AddBrigader. Бригадир успешно изменён. Идентификатор новой бригады: ' .
                        $new_brigader->id;
                }
            } else                                                                                                        //иначе записываем в массив ошибок
            {
                $errors[] = $old_brigade->errors;
                $warnings[] = 'AddBrigader. Ошибка при смене статуса бригады.';
            }
        } else {
            $errors[] = 'Бригада не найдена';
            $warnings[] = 'AddBrigader. Бригада не найдена.';
        }

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Назначение: AddBrigaderChane - Создание новой бригады с бригадиром и создание звена с только что созданой бригадой и звеневым (бригадир)
     * Название метода: AddBrigaderChane()
     * @param $brigader_id - идентификатор бригадира
     * @param $company_department_id - идентификатор участка предприятия
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * Входные обязательные параметры:$brigader_id, $company_department_id
     * @see
     * @example
     *
     * Документация на портале:
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 25.05.2019 20:51
     * @since ver
     */
    private function AddBrigaderChane($brigader_id, $company_department_id)
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        $id_brigade = 0;
        if (isset($brigader_id, $company_department_id))                                                                 //если данные не пустые, тогда добавляем бригадира
        {

            $warnings[] = "AddBrigaderChane. Данные успешно получены: ид бригадира - {$brigader_id}, ид участка {$company_department_id}";
            $warnings[] = 'AddBrigaderChane. Записываем нового бригадира';
            $add_brigade = new Brigade();
            $get_worker = Worker::find()
                ->joinWith('employee')
                ->where(['worker.id' => $brigader_id])
                ->asArray()
                ->limit(1)
                ->one();                                                                                                //ищем работника чтобы получить ФИО
            $description_brigade = "Бригада {$get_worker['employee']['first_name']} {$get_worker['employee']['last_name']} {$get_worker['employee']['patronymic']}";
            $add_brigade->description = $description_brigade;
            $add_brigade->date_time = date('Y-m-d H:i:s');
            $add_brigade->brigader_id = $brigader_id;
            $add_brigade->company_department_id = $company_department_id;
            $add_brigade->status_id = 1;
            if ($add_brigade->save())                                                                                    //если успешно сохранено, тогда добавляем звено
            {
                $warnings[] = 'AddBrigaderChane. Бригада успешно создана';
                $add_brigade->refresh();
                $id_brigade = $add_brigade->id;
                $warnings[] = 'AddBrigaderChane. Записываем новое звено';
                $add_chane = new Chane();
                $title_chane = "Звено {$get_worker['employee']['first_name']} {$get_worker['employee']['last_name']} {$get_worker['employee']['patronymic']}";
                $add_chane->title = $title_chane;
                $add_chane->brigade_id = $id_brigade;
                $add_chane->chaner_id = $brigader_id;
                $add_chane->chane_type_id = 1;
                if ($add_chane->save())                                                                                 //если успешно сохранено звено, тогда записываем вмассив предупреждений информацию о том что звено успешно создано
                {
                    $warnings[] = 'AddBrigaderChane. Звено успешно создано';
                } else                                                                                                    //иначе выводим ошибки
                {
                    $errors[] = $add_chane->errors;
                    $warnings[] = 'AddBrigaderChane. Ошибка при записи нового звена';
                }
            } else {
                $errors[] = $add_brigade->errors;
                $warnings[] = 'AddBrigaderChane. Ошибка при сохранении бригады';
            }
        }
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'id' => $id_brigade);
    }

    /**
     * Метод GetBrigades() - Получение всего списка бригад
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id": "326",                                    // идентификатор бригады
     *      "description":"Бр. Горячев Д. И.""                // наименование бригады
     *      "date_time":"2019-11-07 08:13:39"                // дата и время создания бригады
     *      "brigader_id":"1000759"                            // идентификатор бригадира
     *      "company_department_id":"20038029"                // идентификатор участка
     *      "status_id":"1"                                    // идентификатор статуса бригады
     * ]
     * warnings:{}                                          // массив предупреждений
     * errors:{}                                            // массив ошибок
     * status:1                                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Brigade&method=GetBrigades&subscribe=&data={}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 18.03.2020 8:21
     */
    public static function GetBrigades()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetBrigades';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $brigades = Brigade::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($brigades)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник бригад';
            } else {
                $result = $brigades;
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
     * Метод SaveBrigade() - Сохранение бригады
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "brigade":
     *  {
     *      "brigade_id":-1,                                // идентификатор бригады (-1 = при добавлении новой бригады)
     *      "description":"Бр. Горячев Д. И.""                // наименование бригады
     *      "brigader_id":"1000759"                            // идентификатор бригадира
     *      "company_department_id":"20038029"                // идентификатор участка
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "brigade_id":5,                                    // идентификатор сохранённой бригады
     *      "description":"Бр. Горячев Д. И.""                // сохранённое наименование бригады
     *      "brigader_id":"1000759"                            // сохранённый идентификатор бригадира
     *      "company_department_id":"20038029"                // сохранённый идентификатор участка
     *
     * }
     * warnings:{}                                          // массив предупреждений
     * errors:{}                                            // массив ошибок
     * status:1                                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Brigade&method=SaveBrigade&subscribe=&data={"brigade":{"brigade_id":-1,"description":"БРИГАДА ТЕСТЕРА","brigader_id":2050735,"company_department_id":20028748}}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 18.03.2020 8:30
     */
    public static function SaveBrigade($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок         // Массив ошибок
        $method_name = 'SaveBrigade';
        $brigade_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'brigade'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $brigade_id = $post_dec->brigade->brigade_id;
            $description = $post_dec->brigade->description;
            $brigader_id = $post_dec->brigade->brigader_id;
            $company_department_id = $post_dec->brigade->company_department_id;

            $brigade = Brigade::findOne(['id' => $brigade_id]);
            if (empty($brigade)) {
                $brigade = new Brigade();
            }
            $brigade->description = $description;
            $brigade->brigader_id = $brigader_id;
            $brigade->company_department_id = $company_department_id;
            $brigade->date_time = date('Y-m-d H:i:s', strtotime(BackendAssistant::GetDateNow()));
            $brigade->status_id = 1;
            if ($brigade->save()) {
                $brigade->refresh();
                $warnings[] = $method_name . '. Бригада успешно сохранена';
                $brigade_data['brigade_id'] = $brigade->id;
                $brigade_data['description'] = $brigade->description;
                $brigade_data['brigader_id'] = $brigade->brigader_id;
                $brigade_data['company_department_id'] = $brigade->company_department_id;
                $brigade_data['date_time'] = $brigade->date_time;
                $brigade_data['status_id'] = $brigade->status_id;
            } else {
                $errors[] = $brigade->errors;
                throw new Exception($method_name . '. Ошибка при сохранении бригады');
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        return array('Items' => $brigade_data, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод FullDeleteBrigade() - Удаление бригады если на неё нет операций
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "brigade_id": 391             // идентификатор удаляемой бригады (Удалиться только если на работинков бригады нет никаких нарядов)
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Brigade&method=FullDeleteBrigade&subscribe=&data={"brigade_id":391}
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 18.03.2020 8:37
     */
    public static function FullDeleteBrigade($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $post_dec = (object)array();                                                                                              // Массив ошибок
        $method_name = 'FullDeleteBrigade';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'brigade_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $brigade_id = $post_dec->brigade_id;
            $brigade_order = OperationWorker::find()
                ->where(['brigade_id' => $brigade_id])
                ->all();
            if (empty($brigade_order)) {
                Brigade::deleteAll(['id' => $brigade_id]);
            } else {
                $errors[] = 'У бригады назначены операции. Бригаду нельзя удалить';
                $status = 0;
            }
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';

        return array('Items' => $post_dec, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


// GetChane()      - Получение справочника звеньев
// SaveChane()     - Сохранение справочника звеньев
// DeleteChane()   - Удаление справочника звеньев

    /**
     * Метод GetChane() - Получение справочника звеньев
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * (метод не требует входных данных)
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:[
     *      "id":-1,                         // ключ звена
     *      "title":"ACTION",                // название звена
     *      "brigade_id":"-1",               // ключ бригады
     *      "chaner_id":"-1",                // ключ звеньевого (работника)
     *      "chane_type_id":"-1",            // ключ типа звена
     * ]
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Brigade&method=GetChane&subscribe=&data={}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:11
     */
    public static function GetChane()
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();
        $method_name = 'GetChane';
        $warnings[] = $method_name . '. Начало метода';
        try {
            $handbook_chane = Chane::find()
                ->asArray()
                ->indexBy('id')
                ->all();
            if (empty($handbook_chane)) {
                $result = (object)array();
                $warnings[] = $method_name . '. Справочник звеньев пуст';
            } else {
                $result = $handbook_chane;
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
     * Метод SaveChane() - Сохранение справочника звеньев
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * "chane":
     *  {
     *      "chane_id":-1,                   // ключ звена
     *      "title":"ACTION",                // название звена
     *      "brigade_id":"-1",               // ключ бригады
     *      "chaner_id":"-1",                // ключ звеньевого (работника)
     *      "chane_type_id":"-1",            // ключ типа звена
     *  }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * Items:{
     *      "chane_id":-1,                   // ключ звена
     *      "title":"ACTION",                // название звена
     *      "brigade_id":"-1",               // ключ бригады
     *      "chaner_id":"-1",                // ключ звеньевого (работника)
     *      "chane_type_id":"-1",            // ключ типа звена
     * }
     * warnings:{}                          // массив предупреждений
     * errors:{}                            // массив ошибок
     * status:1                             // статус выполнения метода
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Brigade&method=SaveChane&subscribe=&data={"chane":{"chane_id":-1,"title":"ACTION","brigade_id":"-1","chaner_id":"-1","chane_type_id":"-1"}}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:17
     */
    public static function SaveChane($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'SaveChane';
        $handbook_chane_data = array();                                                                                // Промежуточный результирующий массив
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'chane'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_chane_id = $post_dec->chane->chane_id;
            $title = $post_dec->chane->title;
            $brigade_id = $post_dec->chane->brigade_id;
            $chaner_id = $post_dec->chane->chaner_id;
            $chane_type_id = $post_dec->chane->chane_type_id;
            $new_handbook_chane_id = Chane::findOne(['id' => $handbook_chane_id]);
            if (empty($new_handbook_chane_id)) {
                $new_handbook_chane_id = new Chane();
            }
            $new_handbook_chane_id->title = $title;
            $new_handbook_chane_id->brigade_id = $brigade_id;
            $new_handbook_chane_id->chaner_id = $chaner_id;
            $new_handbook_chane_id->chane_type_id = $chane_type_id;
            if ($new_handbook_chane_id->save()) {
                $new_handbook_chane_id->refresh();
                $handbook_chane_data['chane_id'] = $new_handbook_chane_id->id;
                $handbook_chane_data['title'] = $new_handbook_chane_id->title;
                $handbook_chane_data['brigade_id'] = $new_handbook_chane_id->brigade_id;
                $handbook_chane_data['chaner_id'] = $new_handbook_chane_id->chaner_id;
                $handbook_chane_data['chane_type_id'] = $new_handbook_chane_id->chane_type_id;
            } else {
                $errors[] = $new_handbook_chane_id->errors;
                throw new Exception($method_name . '. Ошибка при сохранении справочника звеньев');
            }
            unset($new_handbook_chane_id);
        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        $result = $handbook_chane_data;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод DeleteChane() - Удаление справочника звеньев
     * @param null $data_post
     * @return array
     *
     * @package frontend\controllers\handbooks
     *
     * ВХОДНОЙ МАССИВ ДАННЫХ:
     * {
     *      "chane_id": 98             // идентификатор справочника звеньев
     * }
     *
     * ВЫХОДНОЙ МАССИВ ДАННЫХ:
     * (стандартный массив)
     *
     * @example amicum/read-manager-amicum?controller=handbooks\Brigade&method=DeleteChane&subscribe=&data={"chane_id":98}
     *
     * @author Якимов М.Н.
     * Created date: on 17.03.2020 10:21
     */
    public static function DeleteChane($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $post_dec = (object)array();                                                                                              // Массив ошибок
        $method_name = 'DeleteChane';
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (!property_exists($post_dec, 'chane_id'))                                                    // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $handbook_chane_id = $post_dec->chane_id;

            if (!OperationWorker::findOne(['chane_id' => $handbook_chane_id])) {
                Chane::deleteAll(['id' => $handbook_chane_id]);
            } else {
                throw new Exception('Удаление невозможно. Звено используется в наряде');
            }
        } catch (Throwable $exception) {
//            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';

        return array('Items' => $post_dec, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }
}
