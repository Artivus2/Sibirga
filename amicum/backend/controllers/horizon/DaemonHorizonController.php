<?php
/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\horizon;

use backend\controllers\Assistant;
use frontend\controllers\system\LogAmicumFront;
use Throwable;
use Yii;
use yii\db\Query;
use yii\web\Controller;
use yii\web\Response;

class DaemonHorizonController extends Controller
{
    // actionInitDaemonAllDcs       - Метод инициализации демонов из админки всех служб
    // actionInitDaemon             - Метод инициализации демонов из админки
    // InitDaemon                   - Метод инициализации демонов обработки очередей
    // actionGetDaemonsAll          - Метод получения списка демонов всех служб сбора данных
    // actionGetStatusDaemonsAll    - Метод получения списка статусов демонов всех служб сбора данных
    // actionGetDaemons             - Метод получения списка демонов
    // actionKillDaemons            - Метод остановки демонов
    // actionKillDaemonsAllDcs      - Метод остановки демонов всех служб

    /**
     * actionInitDaemon - Метод инициализации демонов из админки
     * Пример: 127.0.0.1/admin/horizon/daemon-horizon/init-daemon?mine_id=290
     * Пример: 127.0.0.1/admin/horizon/daemon-horizon/init-daemon?mine_id=290&net_id=290:683
     */
    public function actionInitDaemon()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("actionInitDaemon");

        try {
            $log->addLog("Начало выполнения метода");
            $post = Assistant::GetServerMethod();
            $mine_id = $post['mine_id'];
            $net_id = null;
            if (isset($post['net_id'])) {
                $net_id = $post['net_id'];
            }
            $response = self::InitDaemon($mine_id, $net_id);
            $log->addLogAll($response);
            $log->addLog("Окончил выполнение метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * InitDaemon - Метод инициализации демонов обработки очередей
     * @param $mine_id - ключ службы сбора данных
     * @param $net_id - айпи адрес службы, которую надо запустить
     * @return array|null[]
     */
    public static function InitDaemon($mine_id, $net_id = null)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("InitDaemon");
        try {
            $log->addLog("Начало выполнения метода");
            $log->addData($mine_id, "mine_id", __LINE__);
            // получить список net_id адресов шлюзов, по конкретной шахте
            $gateways = (new Query())
                ->select('sensor_title, sensor_id, mine_id, net_id')
                ->from('vw_sensor_mine_by_sensor_type')
                ->where(["mine_id" => $mine_id, "sensor_type_id" => 11])
                ->all();                                                                                                // sensor_type_id = 11 (ЛС Горизонт)

            $log->addLog("Получил шлюзы ССД", count($gateways));
            $date_now = Assistant::GetDateNow();
            $daemon_controller = new DaemonHorizonCache();
            $result = $daemon_controller->killDaemons($mine_id, $net_id);

            foreach ($gateways as $gateway) {
                $daemon_structure = DaemonHorizonCache::buildDaemon($gateway['mine_id'], $gateway['net_id'], $gateway['sensor_title'], [], $date_now);

                $response = $daemon_controller->addDaemonHash($mine_id, $daemon_structure);                              // положить сведения о службах в кеш
                $log->addLogAll($response);
            }
            $log->addLog("Уложил данные в кеш");

            // запустить обработку служб на основе данных из кеша
            $response = $daemon_controller->initDaemons($mine_id);
            $log->addLogAll($response);

            $log->addLog("Окончил выполнение метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * actionInitDaemonAllDcs - Метод инициализации демонов из админки всех служб
     * Пример: 127.0.0.1/admin/horizon/daemon-horizon/init-daemon-all-dcs
     */
    public function actionInitDaemonAllDcs()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("actionInitDaemonAllDcs");

        try {
            $mines = (new Query())
                ->select('mine.id as id')
                ->from('mine')
                ->all();
            $log->addLog("Получил службы на инициализацию с БД");
            $log->addData($mines, '$mines', __LINE__);
            foreach ($mines as $mine) {
                $log->addLog("Начинаю заполнять службу: " . $mine['id']);
                $response = self::InitDaemon($mine['id']);
                $log->addLogAll($response);
                $log->addLog("Инициализировал службу: " . $mine['id']);
            }
            $log->addLog("Окончил выполнение метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * actionGetDaemons - Метод получения списка демонов
     * Пример: 127.0.0.1/admin/horizon/daemon-horizon/get-daemons?mine_id=290
     */
    public function actionGetDaemons()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("actionGetDaemons");

        try {
            $log->addLog("Начало выполнения метода");
            $post = Assistant::GetServerMethod();
            $mine_id = $post['mine_id'];

            $daemon_controller = new DaemonHorizonCache();
            $result = $daemon_controller->getDaemonHash($mine_id);

            $log->addLog("Окончил выполнение метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * actionKillDaemons - Метод остановки демонов
     * Пример: 127.0.0.1/admin/horizon/daemon-horizon/kill-daemons?mine_id=290
     * Пример: 127.0.0.1/admin/horizon/daemon-horizon/kill-daemons?mine_id=290&net_id=680
     */
    public function actionKillDaemons()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("actionKillDaemons");

        try {
            $log->addLog("Начало выполнения метода");
            $post = Assistant::GetServerMethod();
            $mine_id = $post['mine_id'];
            $net_id = null;
            if (isset($post['net_id'])) {
                $net_id = $post['net_id'];
            }

            $daemon_controller = new DaemonHorizonCache();
            $result = $daemon_controller->killDaemons($mine_id, $net_id);

            $log->addLog("Окончил выполнение метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * actionKillDaemonsAllDcs - Метод остановки демонов всех служб
     * Пример: 127.0.0.1/admin/horizon/daemon-horizon/kill-daemons-all-dcs
     */
    public function actionKillDaemonsAllDcs()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("actionKillDaemonsAllDcs");

        try {
            $log->addLog("Начало выполнения метода");

            $daemon_controller = new DaemonHorizonCache();
            $mines = (new Query())
                ->select('mine.id as id')
                ->from('mine')
                ->all();
            $log->addLog("Получил службы на инициализацию с БД");
            $log->addData($mines, '$mines', __LINE__);
            foreach ($mines as $mine) {
                $response = $daemon_controller->killDaemons($mine['id']);
                $log->addLogAll($response);
                $result[] = $response['Items'];
            }

            $log->addLog("Окончил выполнение метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * actionGetDaemonsAll - Метод получения списка демонов всех служб сбора данных
     * Пример: http://127.0.0.1/admin/horizon/daemon-horizon/get-daemons-all
     */
    public function actionGetDaemonsAll()
    {
        $daemons = [];                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("actionGetDaemonsAll");

        try {
            $log->addLog("Начало выполнения метода");

            $daemon_controller = new DaemonHorizonCache();
            $mines = (new Query())
                ->select('mine.id as id')
                ->from('mine')
                ->all();

            $log->addLog("Получил службы на инициализацию с БД");
            $log->addData($mines, '$mines', __LINE__);
            foreach ($mines as $mine) {
                $daemon = $daemon_controller->getDaemonHash($mine['id']);
                if ($daemon) {
                    $daemons = array_merge($daemons, $daemon);
                }
            }
            $count_run_daemon = 0;
            $count_all_daemon = 0;
            foreach ($daemons as $daemon) {
                if (($daemon['pid'] and !is_array($daemon['pid']))) {
                    $count_run_daemon++;
                } else if ((is_array($daemon['pid']) and !empty($daemon['pid']))) {
                    foreach ($daemon['pid'] as $pid) {
                        $count_run_daemon++;
                    }
                }
                $count_all_daemon++;
            }

            $log->addLog("Количество запущенных демонов: " . $count_run_daemon);
            $log->addLog("Количество всего демонов: " . $count_all_daemon);
            $log->addLog("Окончил выполнение метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $daemons], $log->getLogAll());
    }

    /**
     * actionGetStatusDaemonsAll - Метод получения списка статусов демонов всех служб сбора данных
     * Пример: http://127.0.0.1/admin/horizon/daemon-horizon/get-status-daemons-all
     */
    public function actionGetStatusDaemonsAll()
    {
        $daemons = [];                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("actionGetStatusDaemonsAll");

        try {
            $log->addLog("Начало выполнения метода");

            $daemon_controller = new DaemonHorizonCache();
            $mines = (new Query())
                ->select('mine.id as id')
                ->from('mine')
                ->all();

            $log->addLog("Получил службы на инициализацию с БД");
            $log->addData($mines, '$mines', __LINE__);
            foreach ($mines as $mine) {
                $daemon = $daemon_controller->getDaemonHash($mine['id']);
                if ($daemon) {
                    $daemons = array_merge($daemons, $daemon);
                }
            }
            $count_run_daemon = 0;
            $count_all_daemon = 0;
            $count_pid_daemon = 0;
            $count_pid_30 = 0;
            $count_pid_5 = 0;
            $count_null_pid = 0;
            foreach ($daemons as $daemon) {
                if (
                    ($daemon['pid'] and !is_array($daemon['pid'])) or
                    (is_array($daemon['pid']) and !empty($daemon['pid']))
                ) {
                    $count_run_daemon++;
                    if (is_array($daemon['pid'])) {
                        foreach ($daemon['pid'] as $pid) {
                            $status = $daemon_controller->status($pid);
                            if ($status) {
                                $count_pid_daemon++;
                            }
                        }
                        $count_pid = count($daemon['pid']);
                        if ($count_pid > 3 and $count_pid < 10) {
                            $count_pid_5++;
                        }
                        if ($count_pid > 30 and $count_pid < 40) {
                            $count_pid_30++;
                        }
                    } else {
                        $status = $daemon_controller->status($daemon['pid']);
                        if ($status) {
                            $count_pid_daemon++;
                        }
                    }
                } else {
                    $count_null_pid++;
                }
                $count_all_daemon++;
            }

            $log->addLog("Количество запущенных демонов: " . $count_pid_daemon);
            $log->addLog("Количество демонов в кеше (есть пид): " . $count_run_daemon);
            $log->addLog("Количество всего демонов: " . $count_all_daemon);
            $log->addLog("Количество демонов c 5 потоками: " . $count_pid_5);
            $log->addLog("Количество демонов с 30 потоками: " . $count_pid_30);
            $log->addLog("Количество демонов без потока: " . $count_null_pid);
            $log->addLog("Окончил выполнение метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $daemons], $log->getLogAll());
    }

}