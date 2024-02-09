<?php

namespace backend\controllers\serviceamicum;

use backend\controllers\Assistant;
use Throwable;
use WebSocket\Exception;
use Yii;
use yii\web\Controller;
use yii\web\Response;

class ReplicationController extends Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }


    //AutostartRepl                                                                                              - Метод по автозапуску реликации


    /**
     * AutostartRepl - Метод по автозапуску реликации
     * Описание:
     * Метод получает статусы всех рабов и по циклу проверяет, если 'Slave_SQL_Running' равен 'Yes', 'Slave_IO_Running' равен 'No' и 'Last_IO_Errno' равен '13122' то даем команду 'START SLAVE FOR CHANNEL "имя канала"'
     * Пример вызова: // 127.0.0.1/admin/serviceamicum/replication/autostart-repl
     */
    public static function AutostartRepl()
    {
        $name_method = 'AutostartRepl ';
        $result = array();
        $status = 1;
        $errors = array();
        $warnings = array();

        try {
            //получаем все репликации имеющеся в данной БД
            $status_slaves = Yii::$app->db_replication->createCommand('SHOW SLAVE STATUS')->queryAll();
            //если нечего нет то ошибка и не пойдем дальше
            if (!$status_slaves) {

                throw new Exception($name_method . 'cannot get slave(s) status(es)');
            }
            //по циклу получем каждого раба (slave)
            foreach ($status_slaves as $status_slave) {

                //проверяем, если репликация трпебует перезарузки то перезапускаем
                if ($status_slave['Slave_SQL_Running'] === 'Yes' and $status_slave['Slave_IO_Running'] === 'No' and $status_slave['Last_IO_Errno'] === '13122') {
                    $restart_status = Yii::$app->db_replication->createCommand("START SLAVE FOR CHANNEL '" . $status_slave['Channel_Name'] . "'")->execute();
                    if ($restart_status) {
                        throw new Exception($name_method . 'cannot restart  channel ' . $status_slave['Channel_Name']);
                    }
                }
            }

        } catch (\Throwable $exception) {
            $status = 0;

            $errors[] = $name_method . "exception";
            $errors[] = $exception->getLine();
            $errors[] = $exception->getMessage();

        }
//        Yii::$app->response->format = Response::FORMAT_JSON;
//        Yii::$app->response->data = $errors;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        //Метод запускается в специальном bash скрипте в сервере где находится проект в папке /home/ingener401/scripts/autostart_Replication.sh
    }
}
