<?php
/*
 * Copyright (c) 2021. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\daemon;


use backend\controllers\Assistant;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\ConnectString;
use Throwable;
use Yii;
use yii\db\Query;
use yii\web\Controller;
use yii\web\Response;

/**
 * Class DaemonStrata - главный класс запуска демонов обработки очередей Страта
 * @package backend\controllers
 */
class DaemonStrataController extends Controller
{
    // actionInitDaemonAllDcs       - Метод инициализации демонов из админки всех служб страта
    // actionInitDaemon             - Метод инициализации демонов из админки
    // InitDaemon                   - Метод инициализации демонов обработки очередей страта
    // actionGetDaemonsAll          - Метод получения списка демонов всех служб сбора данных
    // actionGetStatusDaemonsAll    - Метод получения списка статусов демонов всех служб сбора данных
    // actionGetDaemons             - Метод получения списка демонов
    // actionKillDaemons            - Метод остановки демонов
    // actionKillDaemonsAllDcs      - Метод остановки демонов всех служб

    /**
     * actionInitDaemon - Метод инициализации демонов из админки
     * Пример: 127.0.0.1/admin/daemon/daemon-strata/init-daemon?dcs_id=1380
     * Пример: 127.0.0.1/admin/daemon/daemon-strata/init-daemon?dcs_id=1380&ip=172.16.59.86
     */
    public function actionInitDaemon()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("actionInitDaemon");

        try {
            $log->addLog("Начало выполнения метода");
            $post = Assistant::GetServerMethod();
            $dcs_id = $post['dcs_id'];
            $ip = null;
            if (isset($post['ip'])) {
                $ip = $post['ip'];
            }
            $response = self::InitDaemon($dcs_id, $ip);
            $log->addLogAll($response);
            $log->addLog("Окончил выполнение метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * actionInitDaemonAllDcs - Метод инициализации демонов из админки всех служб страта
     * Пример: 127.0.0.1/admin/daemon/daemon-strata/init-daemon-all-dcs
     */
    public function actionInitDaemonAllDcs()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("actionInitDaemonAllDcs");

        try {
            $dcses = (new Query())
                ->select('Settings_DCS.id as id')
                ->from('Settings_DCS')
                ->innerJoin('connect_string', 'connect_string.Settings_DCS_id=Settings_DCS.id')
                ->where("source_type='Strata'")
                ->groupBy('id')
                ->all();
            $log->addLog("Получил службы на инициализацию с БД");
            $log->addData($dcses, '$dcses', __LINE__);
            foreach ($dcses as $dcs) {
                $log->addLog("Начинаю заполнять службу: " . $dcs['id']);
                $response = self::InitDaemon($dcs['id']);
                $log->addLogAll($response);
                $log->addLog("Инициировал службу: " . $dcs['id']);
            }
            $log->addLog("Окончил выполнение метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * InitDaemon - Метод инициализации демонов обработки очередей страта
     * @param $dcs_id - ключ службы сбора данных
     * @param $ip - айпи адрес службы, которую надо запустить
     * @return array|null[]
     */
    public static function InitDaemon($dcs_id, $ip = null)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("InitDaemon");
        try {
            $log->addLog("Начало выполнения метода");
            $log->addData($dcs_id, "dcs_id", __LINE__);
            // получить список IP адресов шлюзов, по конкретной шахте
            $gateways = ConnectString::find()
                ->where(['Settings_DCS_id' => $dcs_id, 'source_type' => 'Strata'])
                ->asArray()
                ->all();                                                                                                // получить список IP адресов шлюзов, по конкретной шахте
            $log->addLog("Получил шлюзы ССД Страта", count($gateways));
            $date_now = Assistant::GetDateNow();
            $daemon_controller = new DaemonCache();
            $result = $daemon_controller->killDaemons($dcs_id, $ip);

            foreach ($gateways as $gateway) {
                $daemon_structure = DaemonCache::buildDaemon($dcs_id, $gateway['ip'], $gateway['title'], [], $date_now);

                $response = $daemon_controller->addDaemonHash($dcs_id, $daemon_structure);                              // положить сведения о службах в кеш
                $log->addLogAll($response);
            }
            $log->addLog("Уложил данные в кеш");

            // запустить обработку служб на основе данных из кеша
            $response = $daemon_controller->initDaemons($dcs_id);
            $log->addLogAll($response);

            $log->addLog("Окончил выполнение метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * actionGetDaemons - Метод получения списка демонов
     * Пример: 127.0.0.1/admin/daemon/daemon-strata/get-daemons?dcs_id=1380
     */
    public function actionGetDaemons()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("actionGetDaemons");

        try {
            $log->addLog("Начало выполнения метода");
            $post = Assistant::GetServerMethod();
            $dcs_id = $post['dcs_id'];

            $daemon_controller = new DaemonCache();
            $result = $daemon_controller->getDaemonHash($dcs_id);

            $log->addLog("Окончил выполнение метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * actionKillDaemons - Метод остановки демонов
     * Пример: 127.0.0.1/admin/daemon/daemon-strata/kill-daemons?dcs_id=1380
     * Пример: 127.0.0.1/admin/daemon/daemon-strata/kill-daemons?dcs_id=1380&ip=172.16.59.86
     */
    public function actionKillDaemons()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("actionKillDaemons");

        try {
            $log->addLog("Начало выполнения метода");
            $post = Assistant::GetServerMethod();
            $dcs_id = $post['dcs_id'];
            $ip = null;
            if (isset($post['ip'])) {
                $ip = $post['ip'];
            }

            $daemon_controller = new DaemonCache();
            $result = $daemon_controller->killDaemons($dcs_id, $ip);

            $log->addLog("Окончил выполнение метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * actionKillDaemonsAllDcs - Метод остановки демонов всех служб
     * Пример: 127.0.0.1/admin/daemon/daemon-strata/kill-daemons-all-dcs
     */
    public function actionKillDaemonsAllDcs()
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("actionKillDaemonsAllDcs");

        try {
            $log->addLog("Начало выполнения метода");

            $daemon_controller = new DaemonCache();
            $dcses = (new Query())
                ->select('Settings_DCS.id as id')
                ->from('Settings_DCS')
                ->innerJoin('connect_string', 'connect_string.Settings_DCS_id=Settings_DCS.id')
                ->where("source_type='Strata'")
                ->groupBy('id')
                ->all();
            $log->addLog("Получил службы на инициализацию с БД");
            $log->addData($dcses, '$dcses', __LINE__);
            foreach ($dcses as $dcs) {
                $response = $daemon_controller->killDaemons($dcs['id']);
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
     * Пример: http://127.0.0.1/admin/daemon/daemon-strata/get-daemons-all
     */
    public function actionGetDaemonsAll()
    {
        $daemons = [];                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("actionGetDaemonsAll");

        try {
            $log->addLog("Начало выполнения метода");

            $daemon_controller = new DaemonCache();
            $dcses = (new Query())
                ->select('Settings_DCS.id as id')
                ->from('Settings_DCS')
                ->innerJoin('connect_string', 'connect_string.Settings_DCS_id=Settings_DCS.id')
                ->where("source_type='Strata'")
                ->groupBy('id')
                ->all();
            $log->addLog("Получил службы на инициализацию с БД");
            $log->addData($dcses, '$dcses', __LINE__);
            foreach ($dcses as $dcs) {
                $daemons = array_merge($daemons, $daemon_controller->getDaemonHash($dcs['id']));
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
     * Пример: http://127.0.0.1/admin/daemon/daemon-strata/get-status-daemons-all
     */
    public function actionGetStatusDaemonsAll()
    {
        $daemons = [];                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("actionGetStatusDaemonsAll");

        try {
            $log->addLog("Начало выполнения метода");

            $daemon_controller = new DaemonCache();
            $dcses = (new Query())
                ->select('Settings_DCS.id as id')
                ->from('Settings_DCS')
                ->innerJoin('connect_string', 'connect_string.Settings_DCS_id=Settings_DCS.id')
                ->where("source_type='Strata'")
                ->groupBy('id')
                ->all();
            $log->addLog("Получил службы на инициализацию с БД");
            $log->addData($dcses, '$dcses', __LINE__);
            foreach ($dcses as $dcs) {
                $daemons = array_merge($daemons, $daemon_controller->getDaemonHash($dcs['id']));
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