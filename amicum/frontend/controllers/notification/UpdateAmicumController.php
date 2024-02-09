<?php
/*
 * Copyright (c) 2020. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 */

namespace frontend\controllers\notification;


use Exception;
use frontend\controllers\Assistant;
use frontend\models\UpdateArchive;
use frontend\models\UpdateArchiveItems;
use frontend\models\UpdateArchiveWorker;
use frontend\models\UpdateTechWork;
use frontend\models\UpdateWish;
use Throwable;
use Yii;
use yii\db\Query;
use yii\web\Controller;

class UpdateAmicumController extends Controller
{

    // GetUpdateWishes                  - метод получения списка пожеланий
    // AddUpdateWishes                  - метод добавления пожелания
    // DeleteUpdateWishes               - метод удаления пожелания
    // ChangeStatusUpdateWishes         - метод смены статуса пожелания

    // GetUpdateArchives                - метод получения списка обновлений системы
    // DelUpdateArchives                - метод удаления обновления
    // GetNewUpdateArchives             - метод получения списка новых обновлений системы
    // ChangeStatusUpdateArchiveWorker  - метод установки пользователем отметки об ознакомлении с обновлением
    // AddUpdate                        - метод добавления обновления

    // AddTechWork                      - метод добавления технических работ
    // DelTechWork                      - метод удаления технической работы
    // GetTechWorks                     - метод получения списка технических работ
    // GetTechWorksActual               - метод получения списка актуальных технических работ


    // GetUpdateWishes - метод получения списка пожеланий
    // входной объект:
    //      date_time_start     - дата и время с которого начинаем получать полелания
    //      date_time_end       - дата и время до которого получаем пожелания
    // выходной объект:
    // {amicum_wish_id}:
    //    {
    //      amicum_wish_id	            "1"                                             - ключ пожелания
    //      date_time	                "2020-10-22 00:00:00"                           - дата размещения пожелания
    //      wish_title	                "Хочу чтобы все работало!!!!"                   - само пожелание
    //      worker_id	                "1"                                             - ключ работника
    //      full_name	                "Не заполнено Не заполнено Не заполнено"        - ФИО работника
    //      position_id	                "1"                                             - ключ должности
    //      position_title	            "Прочее"                                        - наименование должности
    //      company_department_id	    "101"                                           - ключ департамента
    //      company_department_title	"Прочее"                                        - название департамента
    //      status_id	                "1"                                             - ключ статуса пожелания
    //      status_title	            "Актуально"                                     - название статуса пожелания
    //    }
    //
    // http://127.0.0.1/read-manager-amicum?controller=notification\UpdateAmicum&method=GetUpdateWishes&subscribe=&data={}
    public static function GetUpdateWishes($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'GetUpdateWishes';
        $result = array();
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'date_time_start') ||                                               // Проверяем наличие в нем нужных нам полей
                !property_exists($post_dec, 'date_time_end'))                                                   // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $date_time_start = date("Y-m-d", strtotime($post_dec->date_time_start));

            if ($post_dec->date_time_end) {
                $date_time_end = date("Y-m-d", strtotime($post_dec->date_time_end . "+1days"));
            } else {
                $date_time_end = date("Y-m-d", strtotime("2099-12-31"));
            }

            $wishes = UpdateWish::find()
                ->joinWith('status')
                ->joinWith('worker.employee')
                ->joinWith('worker.position')
                ->joinWith('worker.companyDepartment.company')
                ->where("date_time>='" . $date_time_start . "' and date_time<='" . $date_time_end . "'")
                ->asArray()
                ->all();
            foreach ($wishes as $wish) {
                $full_name = $wish['worker']['employee']['last_name'] . " " . $wish['worker']['employee']['first_name'] . " " . $wish['worker']['employee']['patronymic'];
                $wishes_result[$wish['id']] = array(
                    'amicum_wish_id' => $wish['id'],                                                                    // ключ пожелания
                    'date_time' => $wish['date_time'],                                                                  // дата пожелания
                    'wish_title' => $wish['title'],                                                                     // само пожелание
                    'worker_id' => $wish['worker_id'],                                                                  // ключ работника
                    'full_name' => $full_name,                                                                          // ФИО работника
                    'position_id' => $wish['worker']['position_id'],                                                    // ключ должности работника
                    'position_title' => $wish['worker']['position']['title'],                                           // название должности работника
                    'company_department_id' => $wish['worker']['company_department_id'],                                // ключ департамента
                    'company_department_title' => $wish['worker']['companyDepartment']['company']['title'],             // название департамента
                    'status_id' => $wish['status_id'],                                                                         // ключ статуса пожелания (выполнено или нет)
                    'status_title' => $wish['status']['title'],                                                         // название статуса пожелания
                );
            }

            if (isset($wishes_result)) {
                $result = $wishes_result;
            } else {
                $result = (object)array();
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

    // AddUpdateWishes - метод добавления пожелания
    // входной объект:
    //      amicum_wish_id  -   ключ пожелания (если задан -1, то будет созадно новое пожелание, иначе редактирует существующее)
    //      wish_title      -   текст пожелания
    //      status_id       -   статус пожелания
    // http://127.0.0.1/read-manager-amicum?controller=notification\UpdateAmicum&method=AddUpdateWishes&subscribe=&data={"amicum_wish_id": -1,"status_id": 1,"wish_title": "пожелание"}
    public static function AddUpdateWishes($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'AddUpdateWishes';
        $result = array();
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'wish_title') ||                                                    // Проверяем наличие в нем нужных нам полей
                !property_exists($post_dec, 'status_id') ||                                                      // Проверяем наличие в нем нужных нам полей
                !property_exists($post_dec, 'amicum_wish_id'))                                                  // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';

            $wish_title = $post_dec->wish_title;
            $amicum_wish_id = $post_dec->amicum_wish_id;
            $status_id = $post_dec->status_id;
            $session = Yii::$app->session;
            $worker_id = $session['worker_id'];

            $new_wish = UpdateWish::findOne(['id' => $amicum_wish_id]);
            if (!$new_wish) {
                $new_wish = new UpdateWish();
                $new_wish->date_time = Assistant::GetDateTimeNow();
                $new_wish->worker_id = $worker_id;
            }

            $new_wish->status_id = $status_id;
            $new_wish->title = $wish_title;

            if (!$new_wish->save()) {
                $errors[] = $new_wish->errors;
                throw new Exception($method_name . '. Ошибка сохранения модели пожелания UpdateWish');
            }
            $post_dec->amicum_wish_id = $new_wish->id;
            $post_dec->date_time = $new_wish->date_time;
            $post_dec->worker_id = $session['worker_id'];
            $post_dec->full_name = $session['last_name'] . " " . $session['first_name'] . " " . $session['patronymic'];
            $post_dec->position_title = $session['position_title'];
            $post_dec->company_department_title = $session['userDepartmentTitle'];

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

    // DeleteUpdateWishes - метод удаления пожелания
    // входной объект:
    //      amicum_wish_id  -   ключ пожелания
    // http://127.0.0.1/read-manager-amicum?controller=notification\UpdateAmicum&method=DeleteUpdateWishes&subscribe=&data={"amicum_wish_id": 2}
    public static function DeleteUpdateWishes($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DeleteUpdateWishes';
        $result = array();
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (
            !property_exists($post_dec, 'amicum_wish_id'))                                                                // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $amicum_wish_id = $post_dec->amicum_wish_id;

            $result = UpdateWish::deleteAll(['id' => $amicum_wish_id]);


        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    // ChangeStatusUpdateWishes - метод смены статуса пожелания
    // входной объект:
    //      amicum_wish_id  -   ключ пожелания (если задан -1, то будет созадно новое пожелание, иначе редактирует существующее)
    //      status_id       -   статус пожелания
    // http://127.0.0.1/read-manager-amicum?controller=notification\UpdateAmicum&method=ChangeStatusUpdateWishes&subscribe=&data={"amicum_wish_id": -1,"status_id": 1}
    public static function ChangeStatusUpdateWishes($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'ChangeStatusUpdateWishes';
        $result = array();
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'status_id') ||                                                      // Проверяем наличие в нем нужных нам полей
                !property_exists($post_dec, 'amicum_wish_id'))                                                  // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';

            $amicum_wish_id = $post_dec->amicum_wish_id;
            $status_id = $post_dec->status_id;

            $wish = UpdateWish::findOne(['id' => $amicum_wish_id]);
            if (!$wish) {
                throw new Exception($method_name . '. Нет такого ключа пожелания');
            }

            $wish->status_id = $status_id;

            if (!$wish->save()) {
                $errors[] = $wish->errors;
                throw new Exception($method_name . '. Ошибка сохранения модели пожелания UpdateWish');
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

    // GetUpdateArchives - метод получения списка обновлений системы
    // входной объект:
    //      date_time_start     - дата и время с которого начинаем получать обновление
    //      date_time_end       - дата и время до которого получаем обновление
    // выходной объект:
    //  {amicum_update_id}                          - ключ архива обновления
    //          amicum_update_id	    "1"                     - ключ архива обновления
    //          date_time	            "2020-10-02 00:00:00"   - дата выхода обновления
    //          amicum_update_title	    "релиз 1"               - название релиза обновления
    //          release_number          "1"                     - Номер релиза
    //          amicum_update_items:	                        - список обновлений
    //              {amicum_update_item_id}
    //                  amicum_update_item_id	"1"                     - ключ изменения/дополнения
    //                  title	                "Обновление 1"          - названеи изменения/дополнения
    // выходной объект:

    // http://127.0.0.1/read-manager-amicum?controller=notification\UpdateAmicum&method=GetUpdateArchives&subscribe=&data={"date_time_start":"2020-02-02","date_time_end":"2021-02-02"}
    public static function GetUpdateArchives($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'GetUpdateArchives';
        $result = array();
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'date_time_start') ||                                               // Проверяем наличие в нем нужных нам полей
                !property_exists($post_dec, 'date_time_end'))                                                   // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $date_time_start = date("Y-m-d", strtotime($post_dec->date_time_start));
            if ($post_dec->date_time_end) {
                $date_time_end = date("Y-m-d", strtotime($post_dec->date_time_end));
            } else {
                $date_time_end = date("Y-m-d", strtotime("2099-12-31"));
            }

            $update_arcs = UpdateArchive::find()
                ->joinWith('updateArchiveItems')
                ->where("date_time>='" . $date_time_start . "' and date_time<='" . $date_time_end . "'")
                ->asArray()
                ->all();
            $warnings[] = $update_arcs;
            foreach ($update_arcs as $update_arc) {
                $update_arcs_result[$update_arc['id']]['amicum_update_id'] = $update_arc['id'];
                $update_arcs_result[$update_arc['id']]['date_time'] = $update_arc['date_time'];
                $update_arcs_result[$update_arc['id']]['amicum_update_title'] = $update_arc['title'];
                $update_arcs_result[$update_arc['id']]['release_number'] = $update_arc['release_number'];
                if (isset($update_arc['updateArchiveItems']) and !empty($update_arc['updateArchiveItems'])) {
                    foreach ($update_arc['updateArchiveItems'] as $item) {
                        $update_arcs_result[$update_arc['id']]['amicum_update_items'][$item['id']] = array(
                            'amicum_update_item_id' => $item['id'],
                            'title' => $item['title']
                        );
                    }
                } else {
                    $update_arcs_result[$update_arc['id']]['amicum_update_items'] = (object)array();
                }
            }


            if (isset($update_arcs_result)) {
                foreach ($update_arcs_result as $update_arc) {
                    if (!isset($update_arc['amicum_update_items'])) {
                        $update_arcs_result[$update_arc['amicum_update_id']]['amicum_update_items'] = (object)array();
                    }
                }
                $result = $update_arcs_result;
            } else {
                $result = (object)array();
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

    // GetNewUpdateArchives - метод получения списка новых обновлений системы для авторизовавшегося пользователя
    // выходной объект:
    //  {amicum_update_id}                          - ключ архива обновления
    //          amicum_update_id	    "1"                     - ключ архива обновления
    //          date_time	            "2020-10-02 00:00:00"   - дата выхода обновления
    //          amicum_update_title	    "релиз 1"               - название релиза обновления
    //          release_number          "1"                     - Номер релиза
    //          amicum_update_items:	                        - список обновлений
    //              []
    //                  amicum_update_item_id	"1"                     - ключ изменения/дополнения
    //                  title	                "Обновление 1"          - название изменения/дополнения
    // выходной объект:

    // http://127.0.0.1/read-manager-amicum?controller=notification\UpdateAmicum&method=GetNewUpdateArchives&subscribe=&data={}
    public static function GetNewUpdateArchives($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'GetNewUpdateArchives';
        $result = array();
        $warnings[] = $method_name . '. Начало метода';
        try {
//            if ($data_post == NULL && $data_post == '') {
//                throw new Exception($method_name . '. Не переданы входные параметры');
//            }
//            $warnings[] = $method_name . '. Данные успешно переданы';
//            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
//            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
//            $warnings[] = $method_name . '. Декодировал входные параметры';
//            if (
//                !property_exists($post_dec, 'date_time_start') ||                                               // Проверяем наличие в нем нужных нам полей
//                !property_exists($post_dec, 'date_time_end'))                                                   // Проверяем наличие в нем нужных нам полей
//            {
//                throw new Exception($method_name . '. Переданы некорректные входные параметры');
//            }
//            $warnings[] = $method_name . '. Данные с фронта получены';
//            $date_time_start = $post_dec->date_time_start;
//            $date_time_end = $post_dec->date_time_end;
            $session = Yii::$app->session;
            $worker_id = $session['worker_id'];

            $update_arcs = (new Query())
                ->select('
                    update_archive.id as id, 
                    update_archive.date_time as date_time, 
                    update_archive.title as amicum_update_title, 
                    update_archive.release_number as release_number, 
                    update_archive_items.title as update_archive_item_title, 
                    update_archive_items.id as update_archive_item_id
                ')
                ->from('update_archive')
                ->leftJoin('update_archive_items', 'update_archive_items.update_archive_id=update_archive.id')
                ->leftJoin('update_archive_worker', 'update_archive_worker.update_archive_id=update_archive.id and worker_id=' . $worker_id)
                ->where('worker_id is null')
                ->groupBy('id,date_time, amicum_update_title, release_number,update_archive_item_title,update_archive_item_id')
                ->all();

            foreach ($update_arcs as $update_arc) {
                $update_arcs_result[$update_arc['id']]['amicum_update_id'] = $update_arc['id'];
                $update_arcs_result[$update_arc['id']]['date_time'] = $update_arc['date_time'];
                $update_arcs_result[$update_arc['id']]['amicum_update_title'] = $update_arc['amicum_update_title'];
                $update_arcs_result[$update_arc['id']]['release_number'] = $update_arc['release_number'];
                if ($update_arc['update_archive_item_id']) {
                    $update_arcs_result[$update_arc['id']]['amicum_update_items'][] = array(
                        'amicum_update_item_id' => $update_arc['update_archive_item_title'],
                        'title' => $update_arc['update_archive_item_title']
                    );

                } else {
                    $update_arcs_result[$update_arc['id']]['amicum_update_items'] = [];
                }
            }

            if (isset($update_arcs_result)) {
                $result = $update_arcs_result;
            } else {
                $result = (object)array();
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


    // ChangeStatusUpdateArchiveWorker - метод установки пользователем отметки об ознакомлении с обновлением
    // входной объект:
    //      amicum_update_id    -   ключ релиза обновления
    //      status_id           -   статус прочтения обновления
    // http://127.0.0.1/read-manager-amicum?controller=notification\UpdateAmicum&method=ChangeStatusUpdateArchiveWorker&subscribe=&data={"amicum_update_id": 1,"status_id": 19}
    public static function ChangeStatusUpdateArchiveWorker($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'ChangeStatusUpdateArchiveWorker';
        $result = array();
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'amicum_update_id') ||                                              // Проверяем наличие в нем нужных нам полей
                !property_exists($post_dec, 'status_id'))                                                      // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';

            $amicum_update_id = $post_dec->amicum_update_id;
            $status_id = $post_dec->status_id;
            $session = Yii::$app->session;
            $worker_id = $session['worker_id'];
            $status_worker = UpdateArchiveWorker::findOne(['worker_id' => $worker_id, 'status_id' => $status_id, 'update_archive_id' => $amicum_update_id]);
            if (!$status_worker) {
                $status_worker = new UpdateArchiveWorker();
                $status_worker->update_archive_id = $amicum_update_id;
                $status_worker->status_id = $status_id;
                $status_worker->worker_id = $worker_id;

                if (!$status_worker->save()) {
                    $errors[] = $status_worker->errors;
                    throw new Exception($method_name . '. Ошибка сохранения модели пожелания UpdateArchiveWorker');
                }
            } else {
                $warnings[] = $method_name . '. Статус уже был, пересохранять не стал';
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

    // AddUpdate - метод добавления обновления
    // входной объект:
    //      amicumUpdate:   - обновление в целом
    //          amicum_update_id	    "1"                     - ключ архива обновления
    //          date_time	            "2020-10-02 00:00:00"   - дата выхода обновления
    //          amicum_update_title	    "релиз 1"               - название релиза обновления
    //          release_number          "1"                     - Номер релиза
    //          amicum_update_items:	                        - список обновлений
    //              [amicum_update_item_id]                         - ключ изменения/дополнения
    //                  amicum_update_item_id	"1"                     - ключ изменения/дополнения
    //                  title	                "Обновление 1"          - названеи изменения/дополнения
    // выходной объект аналогичен входному, но с обнавленными ключами
    // http://127.0.0.1/read-manager-amicum?controller=notification\UpdateAmicum&method=AddUpdate&subscribe=&data={}
    public static function AddUpdate($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'AddUpdate';
        $result = array();
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (
            !property_exists($post_dec, 'amicumUpdate'))                                                        // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';

            $amicum_update = $post_dec->amicumUpdate;


            $new_update = UpdateArchive::findOne(['id' => $amicum_update->amicum_update_id]);
            if (!$new_update) {
                $new_update = new UpdateArchive();
            }

            $new_update->date_time = date("Y-m-d", strtotime($amicum_update->date_time));
            $new_update->title = $amicum_update->amicum_update_title;
            $new_update->release_number = $amicum_update->release_number;

            if (!$new_update->save()) {
                $errors[] = $new_update->errors;
                throw new Exception($method_name . '. Ошибка сохранения модели обновлений АМИКУМ UpdateArchive');
            }
            $amicum_update->amicum_update_id = $new_update->id;
            $amicum_update->date_time = $new_update->date_time;

            UpdateArchiveItems::deleteAll(['update_archive_id' => $new_update->id]);

            foreach ($amicum_update->amicum_update_items as $amicum_update_item) {
                $new_update_items = new UpdateArchiveItems();
                $new_update_items->update_archive_id = $amicum_update->amicum_update_id;
                $new_update_items->title = $amicum_update_item->title;

                if (!$new_update_items->save()) {
                    $errors[] = $new_update_items->errors;
                    throw new Exception($method_name . '. Ошибка сохранения модели обновления АМИКУМ UpdateArchiveItems');
                }
                $amicum_update_item->amicum_update_item_id = $new_update_items->id;
            }
            $result = $amicum_update;

        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    // DelUpdateArchives - метод удаления обновления
    // входной объект:
    //      amicum_update_id  -   ключ пожелания
    // http://127.0.0.1/read-manager-amicum?controller=notification\UpdateAmicum&method=DelUpdateArchives&subscribe=&data={"amicum_update_id": 2}
    public static function DelUpdateArchives($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DelUpdateArchives';
        $result = array();
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                    // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (
            !property_exists($post_dec, 'amicum_update_id'))                                                                // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $amicum_update_id = $post_dec->amicum_update_id;

            $result = UpdateArchive::deleteAll(['id' => $amicum_update_id]);


        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    // AddTechWork - метод добавления технических работ
    // входной объект:
    //      amicumTechWork:   - объект техработы в целом
    //          amicum_tech_work_id 	    "-1"                     - ключ архива обновления
    //          date_time_start	            "2020-10-02 00:00:00"   - дата старта технических работ
    //          date_time_end	            "2020-10-02 00:00:00"   - дата окончания технических работ
    //          description                 "потому что"            - описание техническиз работ
    // выходной объект аналогичен входному, но с обнавленными ключами
    // http://127.0.0.1/read-manager-amicum?controller=notification\UpdateAmicum&method=AddTechWork&subscribe=&data={}
    public static function AddTechWork($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'AddTechWork';
        $result = array();
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (
            !property_exists($post_dec, 'amicumTechWork'))                                                        // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';

            $amicum_tech_work = $post_dec->amicumTechWork;


            $new_tech_work = UpdateTechWork::findOne(['id' => $amicum_tech_work->amicum_tech_work_id]);
            if (!$new_tech_work) {
                $new_tech_work = new UpdateTechWork();
            }

            $new_tech_work->date_start = date("Y-m-d H:i", strtotime($amicum_tech_work->date_time_start));
            $new_tech_work->date_end = date("Y-m-d H:i", strtotime($amicum_tech_work->date_time_end));
            $new_tech_work->description = $amicum_tech_work->description;

            if (!$new_tech_work->save()) {
                $errors[] = $new_tech_work->errors;
                throw new Exception($method_name . '. Ошибка сохранения модели технических работ АМИКУМ UpdateTechWork');
            }
            $amicum_tech_work->amicum_tech_work_id = $new_tech_work->id;
            $amicum_tech_work->date_time_start = $new_tech_work->date_start;
            $amicum_tech_work->date_time_end = $new_tech_work->date_end;

            $result = $amicum_tech_work;

        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    // DelTechWork - метод удаления технической работы
    // входной объект:
    //      amicum_tech_work_id  -   ключ технической работы
    // http://127.0.0.1/read-manager-amicum?controller=notification\UpdateAmicum&method=DelTechWork&subscribe=&data={"amicum_tech_work_id": 2}
    public static function DelTechWork($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'DelTechWork';
        $result = array();
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (
            !property_exists($post_dec, 'amicum_tech_work_id'))                                                 // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $amicum_tech_work_id = $post_dec->amicum_tech_work_id;

            $result = UpdateTechWork::deleteAll(['id' => $amicum_tech_work_id]);

        } catch (Throwable $exception) {
            $errors[] = $method_name . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status *= 0;
        }
        $warnings[] = $method_name . '. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }


    // GetTechWorks - метод получения списка технических работ
    // входной объект:
    //      date_time_start     - дата и время с которого начинаем получать список технических работ
    //      date_time_end       - дата и время до которого получаем список технических работ
    // выходной объект:
    //  {amicum_tech_work_id}                          - ключ списка технических работ
    //          amicum_tech_work_id 	    "-1"                     - ключ архива обновления
    //          date_time_start	            "2020-10-02 00:00:00"   - дата старта технических работ
    //          date_time_end	            "2020-10-02 00:00:00"   - дата окончания технических работ
    //          description                 "потому что"            - описание технических работ
    // выходной объект:

    // http://127.0.0.1/read-manager-amicum?controller=notification\UpdateAmicum&method=GetTechWorks&subscribe=&data={"date_time_start":"2020-02-02","date_time_end":"2021-02-02"}
    public static function GetTechWorks($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'GetTechWorks';
        $result = array();
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }
            $warnings[] = $method_name . '. Данные успешно переданы';
            $warnings[] = $method_name . '. Входной массив данных' . $data_post;
            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            $warnings[] = $method_name . '. Декодировал входные параметры';
            if (
                !property_exists($post_dec, 'date_time_start') ||                                               // Проверяем наличие в нем нужных нам полей
                !property_exists($post_dec, 'date_time_end'))                                                   // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $date_time_start = date("Y-m-d", strtotime($post_dec->date_time_start)) . " 00:00:00";

            if ($post_dec->date_time_end) {
                $date_time_end = date("Y-m-d", strtotime($post_dec->date_time_end)) . " 23:59:59";
            } else {
                $date_time_end = date("Y-m-d", strtotime("2099-12-31")) . " 23:59:59";
            }

            $tech_works = UpdateTechWork::find()
                ->where("date_start>='" . $date_time_start . "' and date_start<='" . $date_time_end . "'")
                ->asArray()
                ->all();

            foreach ($tech_works as $tech_work) {
                $tech_work_result[$tech_work['id']]['amicum_tech_work_id'] = $tech_work['id'];
                $tech_work_result[$tech_work['id']]['date_time_start'] = $tech_work['date_start'];
                $tech_work_result[$tech_work['id']]['date_time_end'] = $tech_work['date_end'];
                $tech_work_result[$tech_work['id']]['description'] = $tech_work['description'];
            }

            if (isset($tech_work_result)) {
                $result = $tech_work_result;
            } else {
                $result = (object)array();
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

    // GetTechWorksActual - метод получения списка актуальных технических работ
    // входной объект:
    // выходной объект:
    //  {amicum_tech_work_id}                          - ключ списка технических работ
    //          amicum_tech_work_id 	    "-1"                     - ключ архива обновления
    //          date_time_start	            "2020-10-02 00:00:00"   - дата старта технических работ
    //          date_time_end	            "2020-10-02 00:00:00"   - дата окончания технических работ
    //          description                 "потому что"            - описание технических работ
    // выходной объект:

    // http://127.0.0.1/read-manager-amicum?controller=notification\UpdateAmicum&method=GetTechWorksActual&subscribe=&data={}
    public static function GetTechWorksActual($data_post = NULL)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'GetTechWorksActual';
        $result = array();
        $warnings[] = $method_name . '. Начало метода';
        try {
            if ($data_post == NULL && $data_post == '') {
                throw new Exception($method_name . '. Не переданы входные параметры');
            }

            $date_time_start = \backend\controllers\Assistant::GetDateTimeNow();
            $date_time_end = date('Y-m-d H:i:s', strtotime($date_time_start . "+1 days"));

            $warnings[] = $method_name . '. Дата и время начала получения уведомления date_time_start ' . $date_time_start;
            $warnings[] = $method_name . '. Дата и время окончания получения уведомления date_time_end ' . $date_time_end;

            $tech_works = UpdateTechWork::find()
                ->where("date_start>='" . $date_time_start . "' and date_start<='" . $date_time_end . "'")
                ->orWhere("date_start<='" . $date_time_start . "' and date_end>='" . $date_time_start . "'")
                ->asArray()
                ->all();

            foreach ($tech_works as $tech_work) {
                $tech_work_result[$tech_work['id']]['amicum_tech_work_id'] = $tech_work['id'];
                $tech_work_result[$tech_work['id']]['date_time_start'] = $tech_work['date_start'];
                $tech_work_result[$tech_work['id']]['date_time_end'] = $tech_work['date_end'];
                $tech_work_result[$tech_work['id']]['description'] = $tech_work['description'];
            }

            if (isset($tech_work_result)) {
                $result = $tech_work_result;
            } else {
                $result = (object)array();
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
}