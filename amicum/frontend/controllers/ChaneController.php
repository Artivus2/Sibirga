<?php

namespace frontend\controllers;

use frontend\controllers\system\LogAmicumFront;
use frontend\models\BrigadeWorker;
use frontend\models\ChaneType;
use frontend\models\ChaneWorker;
use Throwable;
use yii\web\Controller;

class ChaneController extends Controller
{

    // GetHandbookChaneType             - Метод получения справочника типов звеньев
    // AddChaneWorker                   - Метод добавления нового человека в звено

    public function actionIndex(): string
    {
        return $this->render('index');
    }

    /**
     * AddChaneWorker - Добавление нового человека в звено
     * Название метода: AddChaneWorker()
     * @param $worker_id - идентификатор работника
     * @param $chane_id - идентификатор звена
     * @return array
     *
     * @package frontend\controllers
     *
     * Входные обязательные параметры:$worker_id - идентификатор работника
     *                                $chane_id - идентификатор звена
     * @see
     * @example
     *
     * @author Рудов Михаил <rms@pfsz.ru>
     * Created date: on 20.05.2019 17:22
     * @since ver
     */
    public static function AddChaneWorker($worker_id, $chane_id): array
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();
        if (!isset($worker_id, $chane_id)) {
            $warnings[] = 'AddChaneWorker. Не получен идентификатор работника и идентификатор звена.';
            $status = 0;
            return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        }
        $existWorker = BrigadeWorker::find()
            ->where(['worker_id' => $worker_id])
            ->limit(1)
            ->one();
        $warnings[] = "AddChaneWorker. Поиск работника с идентификатором - {$worker_id} в бригаде.";
        if (isset($existWorker)) {
            $warnings[] = 'AddChaneWorker. Работник найден в бригаде.';
            $warnings[] = 'AddChaneWorker. Поиск есть ли такой человек уже в звене';
            $existChaneWorker = ChaneWorker::find()
                ->where(['worker_id' => $worker_id, 'chane_id' => $chane_id])
                ->limit(1)
                ->one();

            if (!isset($existChaneWorker)) {
                $warnings[] = 'AddChaneWorker. Такого человека нет в звене.';
                $warnings[] = 'AddChaneWorker. Записываем человека в звено.';
                $add_chane_worker = new ChaneWorker();
                $add_chane_worker->chane_id = $chane_id;
                $add_chane_worker->worker_id = $worker_id;
                if (!$add_chane_worker->save()) {
                    $errors[] = $add_chane_worker->errors;
                    $warnings[] = 'AddChaneWorker. Ошибка при сохранении человека в звено.';
                    $status = 0;
                } else {
                    $id_chane_worker = $add_chane_worker->id;
                    $warnings[] = "AddChaneWorker. Запись успешно добавлена. Идентификатор добавленной записи: {$id_chane_worker}";
                }
            } else {
                $errors[] = "Такая запись уже существует. Идентификатор работника: {$worker_id}, Идентификатор звена: {$chane_id}";
                $warnings[] = "AddChaneWorker. Такая запись уже существует.  Идентификатор работника: {$worker_id}, Идентификатор звена: {$chane_id}";
            }
        } else {
            $errors[] = 'Работник не существует в бригаде';
            $warnings[] = "AddChaneWorker. Работник не существует в бригаде. Идентификатор полученного работника: {$worker_id}";
            $status = 0;
        }
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * GetHandbookChaneType - метод получения справочника типов звеньев
     * Входные данные:
     *  - нет
     * Выходные данные:
     *      {id}        - ключ типа звена
     *          id          - ключ типа звена
     *          title       - название типа звена
     *          type        - тип типа звена (бригада/звено)
     * @example http://127.0.0.1/read-manager-amicum?controller=Chane&method=GetHandbookChaneType&subscribe=&data={}
     * @param null $data_post
     * @return array
     */
    public static function GetHandbookChaneType($data_post = NULL): array
    {
        $log = new LogAmicumFront("GetHandbookChaneType");
        $result = (object)array();

        try {
            $chane_types = ChaneType::find()->indexBy('id')->all();

            if ($chane_types) {
                $result = $chane_types;
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }
}
