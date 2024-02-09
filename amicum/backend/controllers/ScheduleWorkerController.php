<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers;

//ob_start();

use Yii;
use yii\db\Query;

class ScheduleWorkerController extends \yii\web\Controller
{
    public function actionIndex()
    {
        //Вывод информации на страницу по скриптам, параметрам и значениям
        /*$this->buildArraysForWorkers();
        return $this->render('index', [
            'dataScripts' => $dataScripts,
            'dataValues' => $dataValues,
            'dataParameters' => $dataParameters,
        ]);*/

        return $this->render('index');
    }


    //метод укладывания данных в кэш в части воркеров, которые зарегистрировались в шахте - нужен для первоначального построения кэша
    //доступ к ключу шахтному полю: за шахтой храниться список айдишников воркеров, которые спустились в шахту
    public static function actionGetWorkers()
    {
//        ini_set('max_execution_time', 3000);
        $flag_debug=0;
        if($flag_debug==1) echo nl2br("start actionGetWorkers\n");
        $mines = (new Query())                                                                                          //выполняем запрос в бд напрямую в view_worker_checkIn_main
        ->select(
            [
                'id'
            ])
            ->from(['mine'])
            ->all();

        $workers = (new Query())
            ->select(
                [
                    'mine_id',
                    'worker_id',
                    'date_time_checkIn',
                    'tabel_number',
                    'FIO',
                    'department_id',
                    'department_title',
                    'position_title',
                    'company_id',
                    'company_title'
                ])
            ->from(['view_worker_checkIn_main'])
            ->all();
        if($flag_debug==1) echo nl2br("query actionGetWorkers\n");
        foreach ($mines as $mine)
        {
            $arrayWorker = array();
            foreach ($workers as $worker)
            {
                if($mine['id'] === $worker['mine_id']) {
                    $item = array();
                    $item['worker_id'] = $worker['worker_id'];
                    $item['tabel_number'] = $worker['tabel_number'];
                    $item['FIO'] = $worker['FIO'];
                    $item['date_time_checkIn'] = $worker['date_time_checkIn'];
                    $item['department_id'] = $worker['department_id'];
                    $item['department_title'] = $worker['department_title'];
                    $item['company_id'] = $worker['company_id'];
                    $item['company_title'] = $worker['company_title'];
                    $item['mine_id'] = $worker['mine_id'];
                    $item['position_title'] = $worker['position_title'];
                    $arrayWorker[] = $item;
                }
            }

            if($flag_debug===1) echo nl2br("cache actionGetWorkers\n");
            $key = 'CheckinWorkerMine_'.$mine['id'];
            self::setCacheCheckin($key, $arrayWorker);
            if($flag_debug===1) echo nl2br("end actionGetWorkers\n");
        }
    }

    //нахер нужна функция как отдельный метод наверное не знает и сам создатель, но факт в том, что она есть.
    //метод создает в кэше переданное в него значение.
    //$lifetime - время жизни ключа в КЭШе. Если равен 0 - бесконечно
    public static function setCacheCheckin($key, $value, $lifetime=0)
    {
        $cache = Yii::$app->cache;
        $cache->set($key, $value);
    }

}
