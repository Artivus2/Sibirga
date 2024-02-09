<?php

namespace frontend\controllers\ordersystem;

use frontend\controllers\Assistant;
use backend\controllers\Assistant as BackendAssistant;
use Exception;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Attachment;
use frontend\models\Worker;
use frontend\models\ZipperJournal;
use frontend\models\ZipperJournalSendStatus;
use Throwable;
use Yii;
use yii\db\Query;
use yii\web\Controller;

class ZipperController extends Controller
{
    // Контроллер мгновенных сообщений типа "МОЛНИЯ"

    // внешние методы


    // внутренние методы:

    /** Молния */
    //      getZipperJournal                - Получение списка молний (метод может использоваться как для архива (date_time = NULL), так и для конкретной даты(date_time=2020-02-18))
    //      saveZipper                      - Сохранить молнию

    /** Уведомления "Предсменный экзаменатор" */
    //      GetPersonalZipperJournal()      - Получение списка молний для конкретного работника
    //      SaveStatusZipper()              - Сохранить статус прочтения молнии
    //      delZipper                       - Удалить молнию

    const ZIPPER_READ = 30;                 // Сообщение прочитано
    const ZIPPER_NOT_READ = 29;             // Сообщение отправлено

    public function actionIndex(): string
    {
        return $this->render('index');
    }

    /**
     * Метод getZipperJournal() - Получение списка молний (метод может использоваться как для архива (date_time = NULL), так и для конкретной даты(date_time=2020-02-18))
     * @param null $data_post
     *      date_time           - дата на которую строим список молний (ФИЛЬТР - поле может быть null - вернет весь архив)
     * @return array
     *      zipper_journal
     *          0
     *              zipper_journal_id        "1"                                    - ключ журнала молний
     *              title                    "молния 1"                             - название молнии
     *              date_time                "2020-02-18 00:00:00"                  - дата создания молнии
     *              date_time_format        "18.02.2020 00:00:00"                   - дата создания молнии форматированная
     *              full_name                "Цаюк СветланаВасильевна, Прочее"      - ФИО создавшего
     *              attachment_id                                                   - вложение
     *              attachment_path                                                 - путь до вложения
     *              attachment_title                                                - название вложения
     *              attachment_status                                               - статус вложения (del)
     *              sketch                                                          - эскиз вложения
     *              attachment_type                                                 - тип вложения
     *              attachment_blob                                                 - вложение типа blob
     * @package frontend\controllers\prostoi
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\Zipper&method=getZipperJournal&subscribe=&data={}
     *
     * @author Якимов М.Н,
     * Created date: on 30.12.2019 9:19
     */
    public static function getZipperJournal($data_post = NULL): array
    {
        $log = new LogAmicumFront("getZipperJournal");

        $result = array();                                                                                              // Промежуточный результирующий массив

        try {
            $log->addLog("Начало выполнения метода");

            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }

            $post_dec = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post_dec, 'date_time')
            ) {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $date_time = $post_dec->date_time;

            if ($date_time) {
                $year = date('Y', strtotime($date_time));
                $month = date('m', strtotime($date_time));
                $day = date('d', strtotime($date_time));
            } else {
                $year = null;
                $month = null;
                $day = null;
            }

            $zipper_journal = ZipperJournal::find()                                                                     // получаем список молний
            ->joinWith('worker.employee')
                ->joinWith('worker.position')
                ->joinWith('attachment')
                ->filterWhere(['year(date_time)' => $year])
                ->andfilterWhere(['month(date_time)' => $month])
                ->andfilterWhere(['day(date_time)' => $day])
                ->asArray()
                ->all();

            foreach ($zipper_journal as $zipper) {
                $zipper_item['zipper_journal_id'] = $zipper['id'];
                $zipper_item['title'] = $zipper['title'];
                $zipper_item['description'] = $zipper['description'];
                $zipper_item['date_time'] = $zipper['date_time'];
                $zipper_item['date_time_format'] = date("d.m.Y H:i:s", strtotime($zipper['date_time']));
                $zipper_item['full_name'] = $zipper['worker']['employee']['last_name'] . " " . $zipper['worker']['employee']['first_name'] . " " . $zipper['worker']['employee']['patronymic'] . ', ' . $zipper['worker']['position']['title'];
                $zipper_item['attachment_id'] = $zipper['attachment_id'];
                if ($zipper['attachment_id']) {
                    $zipper_item['attachment_path'] = $zipper['attachment']['path'];
                    $zipper_item['attachment_title'] = $zipper['attachment']['title'];
                    $zipper_item['attachment_type'] = $zipper['attachment']['attachment_type'];
                    $zipper_item['sketch'] = $zipper['attachment']['sketch'];
                } else {
                    $zipper_item['attachment_path'] = "";
                    $zipper_item['attachment_title'] = "";
                    $zipper_item['attachment_type'] = "";
                    $zipper_item['sketch'] = (object)array();
                }

                $zipper_item['attachment_blob'] = (object)array();
                $zipper_item['attachment_status'] = "";

                $zipper_result[] = $zipper_item;
            }

            if (!isset($zipper_result)) {
                $result['zipper_journal'] = array();
            } else {
                $result['zipper_journal'] = $zipper_result;
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Закончил выполнение метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * Метод delZipper() - Удалить молнию
     * @param null $data_post
     *      zipper_journal_id                              - ключ молнии
     * @return array
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\Zipper&method=delZipper&subscribe=&data={"zipper_journal_id":1}
     *
     * @author Якимов М.Н.
     * Created date: on 30.12.2019 9:19
     */
    public static function delZipper($data_post = NULL): array
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $method_name = 'delZipper';
        $result = array();                                                                                              // Промежуточный результирующий массив
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
                !property_exists($post_dec, 'zipper_journal_id')
            )                                                                                                           // Проверяем наличие в нем нужных нам полей
            {
                throw new Exception($method_name . '. Переданы некорректные входные параметры');
            }
            $warnings[] = $method_name . '. Данные с фронта получены';
            $zipper_journal_id = $post_dec->zipper_journal_id;

            // Удаляем простой
            $result = ZipperJournal::deleteAll(['id' => $zipper_journal_id]);

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
     * Метод saveZipper - Сохранить молнию
     * @param null $data_post
     *      zipper
     *          zipper_journal_id        "1"                                    - ключ журнала молний
     *          title                    "молния 1"                             - название молнии
     *          date_time                "2020-02-18 00:00:00"                  - дата создания молнии
     *          date_time_format        "18.02.2020 00:00:00"                   - дата создания молнии форматированная
     *          full_name                "Цаюк СветланаВасильевна, Прочее"      - ФИО создавшего
     *          attachment_id                                                   - вложение
     *          attachment_path                                                 - путь до вложения
     *          attachment_title                                                - название вложения
     *          attachment_status                                               - статус вложения (del)
     *          sketch                                                          - эскиз вложения
     *          attachment_type                                                 - тип вложения
     *          attachment_blob                                                 - вложение типа blob
     * @return array
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\Zipper&method=saveZipper&subscribe=&data={"zipper":{}}
     *
     * @author Якимов М.Н.
     * Created date: on 30.12.2019 9:19
     */
    public static function saveZipper($data_post = NULL): array
    {
        $log = new LogAmicumFront("saveZipper");

        $storage = (object)array();                                                                                     // Промежуточный результирующий массив
        $session = Yii::$app->session;

        try {
            $log->addLog("Начало выполнения метода");
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }

            $post = json_decode($data_post);
            // Декодируем входной массив данных
            if (
                !property_exists($post, 'zipper')
            ) {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $zipper = $post->zipper;
            $zipper_journal_id = $zipper->zipper_journal_id;

            $save_zipper = ZipperJournal::findOne(['id' => $zipper_journal_id]);
            $date_time_save = date('Y-m-d H:i:s', strtotime($zipper->date_time));
            $worker_id = $zipper->worker_id;
            if (!$save_zipper) {
                $save_zipper = new ZipperJournal();
                $date_time_save = date('Y-m-d H:i:s', strtotime(Assistant::GetDateTimeNow()));
                $worker_id = $session['worker_id'];
            }

            $save_zipper->worker_id = $worker_id;
            $save_zipper->title = $zipper->title;
            $save_zipper->description = $zipper->description;
            $save_zipper->date_time = $date_time_save;
            /**
             * Сохраняем вложение в таблицу Attachment
             **/
            if (isset($zipper->attachment_status) && $zipper->attachment_status === "del") {
                $log->addLog("Открепляем вложение");
                $new_attachment_id = null;
            } else {
                $log->addLog("Ищем или сохраняем вложение");
                $attachment_id = $zipper->attachment_id;
                $new_attachment = Attachment::findOne(['id' => $attachment_id]);
                if ($attachment_id and !$new_attachment and $zipper->attachment_blob) {
                    $log->addLog("Вложения не было и был blob");
                    $new_attachment = new Attachment();
                    $path1 = Assistant::UploadFile($zipper->attachment_blob, $zipper->attachment_title, 'zipper', $zipper->attachment_type);
                    $new_attachment->path = $path1;
                    $new_attachment->date = BackendAssistant::GetDateFormatYMD();
                    $new_attachment->worker_id = $session['worker_id'];
                    $new_attachment->section_title = 'Молнии';
                    $new_attachment->title = $zipper->attachment_title;
                    $new_attachment->attachment_type = $zipper->attachment_type;
                    $new_attachment->sketch = $zipper->sketch;

                    if (!$new_attachment->save()) {
                        $log->addData($new_attachment->errors, '$new_attachment->errors', __LINE__);
                        throw new Exception('Ошибка сохранения модели Attachment');
                    }

                    $new_attachment->refresh();
                    $zipper->attachment_id = $new_attachment->id;
                    $zipper->attachment_blob = (object)array();
                    $zipper->attachment_path = $path1;

                    $new_attachment_id = $new_attachment->id;

                } else if ($new_attachment) {
                    $log->addLog("Вложение уже было");
                    $new_attachment_id = $attachment_id;
                } else {
                    $log->addLog("Вложение не существует");
                    $new_attachment_id = null;
                }

            }

            $save_zipper->attachment_id = $new_attachment_id;


            if ($save_zipper->save()) {
                $save_zipper->refresh();
                $zipper->zipper_journal_id = $save_zipper->id;
            } else {
                $log->addData($save_zipper->errors, '$save_zipper->errors', __LINE__);
                throw new Exception('Ошибка сохранения модели склад материалов ZipperJournal');
            }

            $storage = $zipper;

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        return array_merge(['Items' => $storage], $log->getLogAll());
    }

    /**
     * Метод GetPersonalZipperJournal() - Получение списка молний для конкретного работника
     * @param null $data_post
     *      date_time           - дата на которую строим список молний (ФИЛЬТР - поле может быть null - вернет весь архив)
     *      worker_id           - ключ работника, для которого получаем журнал молний. Если не задан, то берется из сессии
     * @return array
     *      zipper_journal
     *          0
     *              zipper_journal_id        "1"                                    - ключ журнала молний
     *              title                    "молния 1"                             - название молнии
     *              date_time                "2020-02-18 00:00:00"                  - дата создания молнии
     *              date_time_format        "18.02.2020 00:00:00"                   - дата создания молнии форматированная
     *              full_name                "Цаюк СветланаВасильевна, Прочее"      - ФИО создавшего
     *              status_id                                                       - ключ статус прочтения
     *              status_title                                                    - название статуса прочтения
     *              attachment_id                                                   - вложение
     *              attachment_path                                                 - путь до вложения
     *              attachment_title                                                - название вложения
     *              attachment_status                                               - статус вложения (del)
     *              sketch                                                          - эскиз вложения
     *              attachment_type                                                 - тип вложения
     *              attachment_blob                                                 - вложение типа blob
     * @package frontend\controllers\prostoi
     *
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\Zipper&method=GetPersonalZipperJournal&subscribe=&data={"date_time":null}
     *
     * @author Якимов М.Н,
     * Created date: on 30.12.2019 9:19
     */
    public static function GetPersonalZipperJournal($data_post = NULL): array
    {
        $log = new LogAmicumFront("GetPersonalZipperJournal");

        $result = array();                                                                                              // Промежуточный результирующий массив

        try {
            $log->addLog("Начало выполнения метода");

            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }

            $post = json_decode($data_post);                                                                        // Декодируем входной массив данных
            if (
                !property_exists($post, 'date_time')
            ) {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $date_time = $post->date_time;

            if ($date_time) {
                $year = date('Y', strtotime($date_time));
                $month = date('m', strtotime($date_time));
                $day = date('d', strtotime($date_time));
            } else {
                $year = null;
                $month = null;
                $day = null;
            }

            if (
                !property_exists($post, 'worker_id') ||
                $post->worker_id == ''
            ) {
                $session = Yii::$app->session;
                $worker_id = $session['worker_id'];
            } else {
                $worker_id = $post->worker_id;
            }

            $zipper_journal = (new Query())
                ->select([
                    'zipper_journal.id as zipper_journal_id',
                    'zipper_journal.date_time as date_time',
                    'zipper_journal.title as zipper_journal_title',
                    'zipper_journal.description as zipper_journal_description',
                    'zipper_journal_send_status.status_id as status_id',
                    'status.title as status_title',
                    'position.title as position_title',
                    'status.title as status_title',
                    'employee.last_name as last_name',
                    'employee.first_name as first_name',
                    'employee.patronymic as patronymic',
                    'attachment.id as attachment_id',
                    'attachment.path as attachment_path',
                    'attachment.title as attachment_title',
                    'attachment.attachment_type as attachment_type',
                    'attachment.sketch as sketch',
                ])
                ->from('zipper_journal')
                ->innerJoin('worker', 'worker.id=zipper_journal.worker_id')
                ->innerJoin('employee', 'worker.employee_id=employee.id')
                ->innerJoin('position', 'worker.position_id=position.id')
                ->leftJoin('zipper_journal_send_status', 'zipper_journal_send_status.zipper_journal_id=zipper_journal.id and zipper_journal_send_status.worker_id =' . $worker_id)
                ->leftJoin('status', 'zipper_journal_send_status.status_id= status.id')
                ->leftJoin('attachment', 'attachment.id=zipper_journal.attachment_id')
                ->filterWhere(['year(date_time)' => $year])
                ->andfilterWhere(['month(date_time)' => $month])
                ->andfilterWhere(['day(date_time)' => $day])
                ->where(['or',
                    ['zipper_journal_send_status.worker_id' => $worker_id],
                    ['is', 'zipper_journal_send_status.worker_id', null],
                ])
                ->all();

//            $log->addData($zipper_journal, '$zipper_journal', __LINE__);
            foreach ($zipper_journal as $zipper) {
                $zipper_item['zipper_journal_id'] = $zipper['zipper_journal_id'];
                $zipper_item['title'] = $zipper['zipper_journal_title'];
                $zipper_item['description'] = $zipper['zipper_journal_description'];
                $zipper_item['status_id'] = $zipper['status_id'] ? $zipper['status_id'] : self::ZIPPER_NOT_READ;
                $zipper_item['status_title'] = $zipper['status_id'] ? $zipper['status_title'] : "Сообщение отправлено";
                $zipper_item['date_time'] = $zipper['date_time'];
                $zipper_item['date_time_format'] = date("d.m.Y H:i:s", strtotime($zipper['date_time']));
                $zipper_item['full_name'] = $zipper['last_name'] . " " . $zipper['first_name'] . " " . $zipper['patronymic'] . ', ' . $zipper['position_title'];
                $zipper_item['attachment_id'] = $zipper['attachment_id'];
                if ($zipper['attachment_id']) {
                    $zipper_item['attachment_path'] = $zipper['attachment_path'];
                    $zipper_item['attachment_title'] = $zipper['attachment_title'];
                    $zipper_item['attachment_type'] = $zipper['attachment_type'];
                    $zipper_item['sketch'] = $zipper['sketch'];
                } else {
                    $zipper_item['attachment_path'] = "";
                    $zipper_item['attachment_title'] = "";
                    $zipper_item['attachment_type'] = "";
                    $zipper_item['sketch'] = (object)array();
                }
                $zipper_item['attachment_blob'] = (object)array();
                $zipper_item['attachment_status'] = "";

                $zipper_result[] = $zipper_item;
            }

            if (!isset($zipper_result)) {
                $result['zipper_journal'] = array();
            } else {
                $result['zipper_journal'] = $zipper_result;
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Закончил выполнение метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Метод SaveStatusZipper() - Сохранить статус прочтения молнии
     * @param null $data_post
     *   zipper_journal_id  - ключ журнала молний
     * @return array
     * @example http://127.0.0.1/read-manager-amicum?controller=ordersystem\Zipper&method=SaveStatusZipper&subscribe=&data={"zipper_journal_id":7}
     *
     * @author Якимов М.Н.
     * Created date: on 30.12.2019 9:19
     */
    public static function SaveStatusZipper($data_post = NULL): array
    {
        $log = new LogAmicumFront("SaveStatusZipper");

        $result = array();

        try {
            $log->addLog("Начало выполнения метода");
            if ($data_post == NULL && $data_post == '') {
                throw new Exception('Не переданы входные параметры');
            }

            $post = json_decode($data_post);

            if (
                !property_exists($post, 'zipper_journal_id')
            ) {
                throw new Exception('Переданы некорректные входные параметры');
            }

            $zipper_journal_id = $post->zipper_journal_id;

            $session = Yii::$app->session;
            $worker_id = $session['worker_id'];

            $worker = Worker::findOne(['id' => $worker_id]);

            if (!$worker) {
                throw new Exception("Работника с переданным ключом не существует");
            }

            $zipper_status = ZipperJournalSendStatus::findOne(['zipper_journal_id' => $zipper_journal_id, 'worker_id' => $worker_id]);
            if (!$zipper_status) {
                $zipper_status = new ZipperJournalSendStatus();

                $zipper_status->zipper_journal_id = $zipper_journal_id;
                $zipper_status->company_department_id = $worker->company_department_id;
                $zipper_status->date_time = Assistant::GetDateTimeNow();
                $zipper_status->worker_id = $worker_id;
                $zipper_status->status_id = self::ZIPPER_READ;

                if (!$zipper_status->save()) {
                    $log->addData($zipper_status->errors, '$zipper_status->errors', __LINE__);
                    throw new Exception("Не удалось сохранить данные в модели ZipperJournalSendStatus");
                }

                $zipper_status->refresh();
            }

            $result = $zipper_status;


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончил выполнение метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }
}
