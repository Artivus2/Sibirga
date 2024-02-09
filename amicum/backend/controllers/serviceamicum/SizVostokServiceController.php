<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\serviceamicum;

use backend\controllers\Assistant;
use Exception;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\NormHand;
use frontend\models\NormSiz;
use frontend\models\NormSizNeed;
use frontend\models\Siz;
use frontend\models\Size;
use frontend\models\SizHand;
use frontend\models\SizHandNorm;
use frontend\models\Worker;
use frontend\models\WorkerSiz;
use Throwable;
use Yii;
use yii\web\Controller;
use yii\web\Response;
use ZipArchive;


class SizVostokServiceController extends Controller
{

    /**
     * НАСТРОЙКИ СЕРВЕРА СИНХРОНИЗАУИИ СИЗ ВОСТОК СЕРВИС
     *      define('SIZ_VOSTOK_SERVICE_HOST', "astb2.vostok.ru/astb_kolmar_dev");                                       // Имя хоста сервера синхронизации СИЗ Восток Сервис
     *      define('SIZ_VOSTOK_SERVICE_LOGIN', "");                                                                     // логин
     *      define('SIZ_VOSTOK_SERVICE_PWD', "");                                                                       // пароль
     */

    // actionGetData                        - Метод получения данных по изменениям СИЗ по людям с сервера astb2.vostok.ru
    // GetData                              - Метод получения данных по изменениям СИЗ по людям с сервера astb2.vostok.ru
    // GetChanges                           - Метод получения данных по изменениям СИЗ по людям с сервера astb2.vostok.ru

    // GetEstablishedNorms                  - Метод получения данных по нормам СИЗ

    // actionFixPackage                     - Метод подтверждения успешного получения пакета с данными
    // FixPackage                           - Метод подтверждения успешного получения пакета с данными

    // SynhSize                             - Метод синхронизации справочника размеров СИЗ
    // SynhProduction                       - Метод синхронизации справочника СИЗ
    // SynhSizNorm                          - Метод синхронизации справочника СИЗ для норм выдачи
    // SynhNormHand                         - Метод синхронизации справочника норм выдачи СИЗ
    // SynhNormSiz                          - Метод синхронизации связки нормы выдачи и справочника сиз на нормы выдачи
    // SynhIssue                            - Метод синхронизации выданных СИЗ
    // AddSiz                               - метод добавления СИЗ
    // ReturnSiz                            - Метод возврата СИЗ

    // BatchInsert                          - универсальный метод массовой вставки по блокам
    // UnZip                                - Метод разархивирования файла по заданному пути


    const STATUS_SIZ_ISSUE = 64;                                                                                        // статус выдачи
    const STATUS_SIZ_WRITE_OFF = 66;                                                                                    // статус списания
    const STATUS_SIZ_RETURN = 124;                                                                                      // статус возврата
    const STATUS_SIZ_ERROR = 125;                                                                                      // статус списано, но не выдано

    /**
     * actionGetData - Метод получения данных по изменениям с сервера astb2.vostok.ru
     * @example localhost/admin/serviceamicum/siz-vostok-service/get-data
     */
    public function actionGetData()
    {
//        ini_set('max_execution_time', -1);
//        ini_set('memory_limit', "10500M");
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionGetData");

        try {
            $log->addLog("Начало выполнения метода");

            // запрашиваем данные по СИЗ
            $response = $this->GetData();
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception('Ошибка при выполнении метода GetData');
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * GetData - Метод получения данных по изменениям с сервера astb2.vostok.ru
     * @example localhost/admin/serviceamicum/siz-vostok-service/get-data
     */
    public function GetData()
    {
//        ini_set('max_execution_time', -1);
//        ini_set('memory_limit', "10500M");
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("GetData");

        try {
            $log->addLog("Начало выполнения метода");

            // запрашиваем данные по СИЗ
            $response = $this->GetChanges(7704531762, 143401001);
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception('Ошибка при выполнении метода GetChanges');
            }
            $number_message = $response['number_message'];

            // подтверждаем получение сообщения
            $response = $this->FixPackage(7704531762, 143401001, $number_message);
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception('Ошибка при выполнении метода GetEstablishedNorms');
            }

            // запрашиваем данные по нормам СИЗ
            $response = $this->GetEstablishedNorms(7704531762, 143401001);
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception('Ошибка при выполнении метода GetEstablishedNorms');
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        $log->saveLogSynchronization();
        return array_merge(['Items' => $result], $log->getLogAll());
    }

    public function CurlGetData($address, $queryParams)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        // Стартовая отладочная информация
        $log = new LogAmicumFront("curl_get_data");

        try {

            $url = $address . '?' . http_build_query($queryParams);

            $log->addData($url, '$url', __LINE__);

            $curl_handle = curl_init();
            curl_setopt($curl_handle, CURLOPT_URL, $url);
            curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 10);
//            curl_setopt($curl_handle, CURLOPT_EXPECT_100_TIMEOUT_MS, 1800000);
            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl_handle, CURLOPT_USERAGENT, 'AMICUM');
            $result = curl_exec($curl_handle);
            curl_close($curl_handle);

        } catch (Throwable $ex) {
            $log->addData($result, '$result', __LINE__);
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }


    /**
     * GetChanges - Метод получения данных по изменениям с сервера astb2.vostok.ru
     * @param $inn - ИНН организации
     * @param $kpp - КПП организации
     * @param string $excode - тип запроса (004 - полный запрос)
     * @example localhost/admin/serviceamicum/siz-vostok-service/get-data
     */
    public function GetChanges($inn, $kpp, $excode = "004")
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $response = null;                                                                                                 // результирующий массив (если требуется)
        $number_message = 0;                                                                                            // номер сообщения для подтверждения его успешности получения
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("GetChanges");

        try {
            $log->addLog("Начало выполнения метода");
            $date_time_now = Assistant::GetDateTimeNow(true);
            $queryParams = [
                'inn' => $inn,
                'kpp' => $kpp,
                'excode' => $excode,
                'count' => 500
            ];

            $address = 'https://' . SIZ_VOSTOK_SERVICE_LOGIN . ":" . SIZ_VOSTOK_SERVICE_PWD . "@" . SIZ_VOSTOK_SERVICE_HOST . '/hs/AstbData/GetPackage/GetChanges';

            $response = $this->CurlGetData($address, $queryParams);
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception('Ошибка при выполнении метода запроса данных с сервера внешнего CurlGetData');
            }

            if (Yii::$app->id != "app-backend") {
                $path = Yii::getAlias('@app') . '/files/';
            } else {
                $path = "files/";
            }

            $file = $path . 'GetChanges_' . $date_time_now . '.zip';

            file_put_contents($file, $response['Items'], LOCK_EX);

            $response = $this->UnZip($file, $path);
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception('Ошибка при выполнении метода разархивирования файла UnZip');
            }

            $response = file_get_contents($path . 'package.txt');
            $json = json_decode($response);
            $log->addData($json, '$json', __LINE__);

            $number_message = $json->NumberMessage;
            $log->addData($number_message, 'Номер полученного сообщения $number_message', __LINE__);

            $sizes = $json->DirSizes;
            $response = $this->SynhSize($sizes);
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception('Ошибка при выполнении метода синхронизации справочника размеров СИЗ SynhSize');
            }

            $productions = $json->DirProducts;
            $response = $this->SynhProduction($productions);
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception('Ошибка при выполнении метода синхронизации справочника СИЗ SynhProduction');
            }

            $norms = $json->DirNorms;
            $response = $this->SynhSizNorm($norms);
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception('Ошибка при выполнении метода синхронизации справочника СИЗ для норм выдачи SynhSizNorm');
            }

            $response = $this->SynhNormHand($norms);
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception('Ошибка при выполнении метода синхронизации справочника норм выдачи СИЗ SynhNormHand');
            }

            $response = $this->SynhNormSiz($norms);
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception('Ошибка при выполнении метода синхронизации связки норм выдачи и справочника норм выдачи СИЗ SynhNormSiz');
            }

            $worker_sizs = $json->WorkList;
            $workers = $json->DirWorks;
            $response = $this->SynhIssue($worker_sizs, $workers, $productions, $norms, $sizes);
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception('Ошибка при выполнении метода синхронизации выданных СИЗ SynhIssue');
            }

        } catch (Throwable $ex) {
//            $log->addData($response, '$response', __LINE__);
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result, 'number_message' => $number_message], $log->getLogAll());
    }

    /**
     * UnZip - Метод разархивирования файла по заданному пути
     * @param $file - имя/путь до файла
     * @param $path - куда разархивировать
     * @return array|null[]
     */
    public function UnZip($file, $path)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)

        $log = new LogAmicumFront("UnZip");
        try {
            $log->addLog("Начало выполнения метода");
            $zip = new ZipArchive();
            $zip->open($file);
            $zip->extractTo($path);
            $zip->close();
        } catch (Throwable $ex) {
//            $log->addData($response, '$response', __LINE__);
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * SynhSize - Метод синхронизации справочника размеров СИЗ
     * @param $sizes - справочник размеров СИЗ
     * @return null[]
     */
    public function SynhSize($sizes)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("SynhSize");

        try {
            $log->addLog("Начало выполнения метода");
            $size_add = [];                                                                                             // массив размеров для массовой вставки
            $count_size_update = 0;
            if ($sizes and count($sizes)) {
                $size_hand = Size::find()->indexBy('link_1c')->all();
                foreach ($sizes as $size) {
                    if (!isset($size_hand[$size->SizeId])) {
                        $size_add[] = array(
                            'title' => $size->SizeName,
                            'link_1c' => $size->SizeId
                        );
                        $size_hand[$size->SizeId] = (object)array(
                            'title' => $size->SizeName,
                            'link_1c' => $size->SizeId
                        );
                    } else if ($size->SizeName != $size_hand[$size->SizeId]->title) {
                        $size_hand[$size->SizeId]->title = $size->SizeName;
                        if (!$size_hand[$size->SizeId]->save()) {
                            $log->addData($size_hand[$size->SizeId]->errors, '$size_hand[$size->SizeId]', __LINE__);
                            throw new Exception("Ошибка обновления справочника размеров СИЗ Size");
                        }
                        $count_size_update++;
                    }
                }

                if (!empty($size_add)) {
                    $batch_inserted = Yii::$app->db->createCommand()->batchInsert('size', ['title', 'link_1c'], $size_add)->execute();
                    if ($batch_inserted == 0) {
                        throw new Exception('Ошибка при сохранении массива размеров СИЗ');
                    }
                    unset($batch_inserted);
                }
                $log->addLog("Количество общее записей справочника размеров: " . count($sizes));
                $log->addLog("Количество добавленных записей справочника размеров: " . count($size_add));
                $log->addLog("Количество обновленных записей справочника размеров: " . $count_size_update);
                unset($size_add);
                unset($count_size_update);
                unset($size_hand);
                unset($sizes);
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * SynhProduction - Метод синхронизации справочника СИЗ
     * @param $productions - справочник СИЗ при выдаче
     * @return null[]
     */
    public function SynhProduction($productions)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("SynhProduction");

        try {
            $log->addLog("Начало выполнения метода");
            $production_add = [];                                                                                             // массив размеров для массовой вставки
            $count_production_update = 0;
            if ($productions and count($productions)) {
                $production_hand = SizHand::find()->indexBy('link_1c')->all();
                foreach ($productions as $production) {
                    if (!isset($production_hand[$production->ProductId])) {
                        $production_add[] = array(
                            'title' => $production->ProductName,
                            'link_1c' => $production->ProductId
                        );
                        $production_hand[$production->ProductId] = (object)array(
                            'title' => $production->ProductName,
                            'link_1c' => $production->ProductId
                        );
                    } else if ($production->ProductName != $production_hand[$production->ProductId]->title) {
                        $production_hand[$production->ProductId]->title = $production->ProductName;
                        if (!$production_hand[$production->ProductId]->save()) {
                            $log->addData($production_hand[$production->ProductId]->errors, '$production_hand[$production->ProductId]', __LINE__);
                            throw new Exception("Ошибка обновления справочника размеров СИЗ Size");
                        }
                        $count_production_update++;
                    }
                }

                if (!empty($production_add)) {
                    $batch_inserted = Yii::$app->db->createCommand()->batchInsert('siz_hand', ['title', 'link_1c'], $production_add)->execute();
                    if ($batch_inserted == 0) {
                        throw new Exception('Ошибка при сохранении массива справочника СИЗ');
                    }
                    unset($batch_inserted);
                }
                $log->addLog("Количество общее записей справочника СИЗ: " . count($productions));
                $log->addLog("Количество добавленных записей справочника СИЗ: " . count($production_add));
                $log->addLog("Количество обновленных записей справочника СИЗ: " . $count_production_update);
                unset($production_add);
                unset($count_production_update);
                unset($production_hand);
                unset($productions);
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * SynhSizNorm - Метод синхронизации справочника СИЗ для норм выдачи
     * @param $siz_hand_norms - справочник СИЗ как норм
     * @return null[]
     */
    public function SynhSizNorm($siz_hand_norms)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("SynhSizNorm");

        try {
            $log->addLog("Начало выполнения метода");
            $siz_hand_add = [];                                                                                             // массив размеров для массовой вставки
            $count_siz_hand_norm_update = 0;
            if ($siz_hand_norms and count($siz_hand_norms)) {
                $siz_hand_norm = SizHandNorm::find()->indexBy('link_1c')->all();
                foreach ($siz_hand_norms as $siz) {
                    if (!isset($siz_hand_norm[$siz->NomNormId])) {
                        $siz_hand_add[] = array(
                            'title' => $siz->NomNormName,
                            'link_1c' => $siz->NomNormId
                        );
                        $siz_hand_norm[$siz->NomNormId] = (object)array(
                            'title' => $siz->NomNormName,
                            'link_1c' => $siz->NomNormId
                        );
                    } else if ($siz->NomNormName != $siz_hand_norm[$siz->NomNormId]->title) {
                        $siz_hand_norm[$siz->NomNormId]->title = $siz->NomNormName;
                        if (!$siz_hand_norm[$siz->NomNormId]->save()) {
                            $log->addData($siz_hand_norm[$siz->NomNormId]->errors, '$siz_hand_norm[$siz->NomNormId]', __LINE__);
                            throw new Exception("Ошибка обновления справочника СИЗ для норм выдачи");
                        }
                        $count_siz_hand_norm_update++;
                    }
                }

                if (!empty($siz_hand_add)) {
                    $batch_inserted = Yii::$app->db->createCommand()->batchInsert('siz_hand_norm', ['title', 'link_1c'], $siz_hand_add)->execute();
                    if ($batch_inserted == 0) {
                        throw new Exception('Ошибка при сохранении массива справочника СИЗ для норм выдачи');
                    }
                    unset($batch_inserted);
                }
                $log->addLog("Количество общее записей справочника СИЗ для норм выдачи: " . count($siz_hand_norms));
                $log->addLog("Количество добавленных записей справочника СИЗ для норм выдачи: " . count($siz_hand_add));
                $log->addLog("Количество обновленных записей справочника СИЗ для норм выдачи: " . $count_siz_hand_norm_update);
                unset($siz_hand_add);
                unset($count_siz_hand_norm_update);
                unset($siz_hand_norm);
                unset($siz_hand_norms);
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * SynhNormHand - Метод синхронизации справочника норм выдачи СИЗ
     * @param $norm_hands - справочник норм выдачи сиз
     * @return null[]
     */
    public function SynhNormHand($norm_hands)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("SynhNormHand");

        try {
            $log->addLog("Начало выполнения метода");
            $norm_hand_add = [];                                                                                             // массив размеров для массовой вставки
            $count_norm_hand_update = 0;
            if ($norm_hands and count($norm_hands)) {
                $norm_hands_db = NormHand::find()->indexBy('link_1c')->all();
                foreach ($norm_hands as $norm) {
                    if (!isset($norm_hands_db[$norm->NormId])) {
                        $norm_hand_add[] = array(
                            'title' => $norm->NormName,
                            'link_1c' => $norm->NormId,
                            'issue_type' => $norm->NormIssueType,
                            'calculation_type' => $norm->NormCalculationType,
                            'period_type' => $norm->NormPeriodType,
                            'period_count' => $norm->NormPeriodCount,
                            'period_quantity' => $norm->NormPeriodQuantity
                        );
                        $norm_hands_db[$norm->NormId] = (object)array(
                            'title' => $norm->NormName,
                            'link_1c' => $norm->NormId,
                            'issue_type' => $norm->NormIssueType,
                            'calculation_type' => $norm->NormCalculationType,
                            'period_type' => $norm->NormPeriodType,
                            'period_count' => $norm->NormPeriodCount,
                            'period_quantity' => $norm->NormPeriodQuantity
                        );
                    } else if ($norm->NormName != $norm_hands_db[$norm->NormId]->title) {
                        $norm_hands_db[$norm->NormId]->title = $norm->NormName;
                        if (!$norm_hands_db[$norm->NormId]->save()) {
                            $log->addData($norm_hands_db[$norm->NormId]->errors, '$norm_hands_db[$norm->NormId]', __LINE__);
                            throw new Exception("Ошибка обновления справочника норм выдачи СИЗ");
                        }
                        $count_norm_hand_update++;
                    }
                }

                if (!empty($norm_hand_add)) {
                    $batch_inserted = Yii::$app->db->createCommand()->batchInsert('norm_hand', ['title', 'link_1c', 'issue_type', 'calculation_type', 'period_type', 'period_count', 'period_quantity'], $norm_hand_add)->execute();
                    if ($batch_inserted == 0) {
                        throw new Exception('Ошибка при сохранении массива норм выдачи СИЗ');
                    }
                    unset($batch_inserted);
                }
                $log->addLog("Количество общее записей справочника норм выдачи СИЗ: " . count($norm_hands));
                $log->addLog("Количество добавленных записей справочника норм выдачи СИЗ: " . count($norm_hand_add));
                $log->addLog("Количество обновленных записей справочника норм выдачи СИЗ: " . $count_norm_hand_update);
                unset($norm_hand_add);
                unset($count_norm_hand_update);
                unset($norm_hands_db);
                unset($norm_hands);
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * SynhNormSiz - Метод синхронизации связки нормы выдачи и справочника сиз на нормы выдачи
     * @param $norms - связка норм и сиз
     * @return null[]
     */
    public function SynhNormSiz($norms)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("SynhNormSiz");

        try {
            $log->addLog("Начало выполнения метода");
            $norm_siz_add = [];                                                                                             // массив размеров для массовой вставки
            if ($norms and count($norms)) {
                $norm_sizs = NormSiz::find()->indexBy(function ($func) {
                    return $func['norm_hand_id_link_1c'] . '_' . $func['siz_hand_norm_id_link_1c'];
                })->all();
                foreach ($norms as $norm) {
                    if (!isset($norm_sizs[$norm->NormId . "_" . $norm->NomNormId])) {
                        $norm_siz_add[] = array(
                            'norm_hand_id_link_1c' => $norm->NormId,
                            'siz_hand_norm_id_link_1c' => $norm->NomNormId
                        );
                        $norm_sizs[$norm->NormId . "_" . $norm->NomNormId] = (object)array(
                            'norm_hand_id_link_1c' => $norm->NormId,
                            'siz_hand_norm_id_link_1c' => $norm->NomNormId
                        );
                    }
                }

                if (!empty($norm_siz_add)) {
                    $batch_inserted = Yii::$app->db->createCommand()->batchInsert('norm_siz', ['norm_hand_id_link_1c', 'siz_hand_norm_id_link_1c'], $norm_siz_add)->execute();
                    if ($batch_inserted == 0) {
                        throw new Exception('Ошибка при сохранении массива связки норм выдачи СИЗ и справочника СИЗ для норм выдачи');
                    }
                    unset($batch_inserted);
                }
                $log->addLog("Количество общее записей связки норм СИЗ и справочника норм выдачи СИЗ: " . count($norms));
                $log->addLog("Количество добавленных записей связки норм СИЗ и справочника норм выдачи СИЗ: " . count($norm_siz_add));
                unset($norm_siz_add);
                unset($norm_sizs);
                unset($norms);
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * SynhIssue - Метод синхронизации выданных СИЗ
     * @param $worker_sizs - конкретные СИЗы работников для добавления
     * @param $workers - справочник работников
     * @param $productions - справочник СИЗ при выдаче
     * @param $norms - справочник норм выдачи СИЗ
     * @param $sizes - справочник размеров СИЗ
     * @return null[]
     */
    public function SynhIssue($worker_sizs, $workers, $productions, $norms, $sizes)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей
        $count_siz_add = 0;
        $count_worker = 0;
        $count_found_worker = 0;
        $count_siz_worker_add_issue = 0;
        $count_siz_worker_update_issue = 0;
        $count_siz_worker_add_error = 0;
        $count_siz_worker_update_write_off = 0;
        $count_siz_worker_update_return = 0;
        $need_siz_add = [];

        // Стартовая отладочная информация
        $log = new LogAmicumFront("SynhIssue");

        try {
            $log->addLog("Начало выполнения метода");
            $worker_hand = Worker::find()->asArray()->indexBy("tabel_number")->all();
            $siz_hand = Siz::find()->asArray()->indexBy("link_1c")->all();
            $size_hand = Size::find()->asArray()->indexBy("link_1c")->all();
            $production_hand = SizHand::find()->asArray()->indexBy("link_1c")->all();
            $norm_siz_hand = NormSiz::find()->indexBy(function ($func) {
                return $func['norm_hand_id_link_1c'] . '_' . $func['siz_hand_norm_id_link_1c'];
            })->all();

            $date_time_now = Assistant::GetDateNow();
            $date_time_now_without_u = Assistant::GetDateTimeNow();

            foreach ($worker_sizs as $worker_siz) {
                $count_worker++;
                /** ПРОВЕРЯЕМ НАЛИЧИЕ РАБОТНИКА */
                if (isset($workers[$worker_siz->WorkerKey]) and isset($worker_hand[$workers[$worker_siz->WorkerKey]->WorkerTab])) {
                    $log->addLog("Работника нашел");
                    $worker_id = $worker_hand[$workers[$worker_siz->WorkerKey]->WorkerTab]["id"];
                    $company_department_id = $worker_hand[$workers[$worker_siz->WorkerKey]->WorkerTab]["company_department_id"];
                    $log->addLog("worker_id: " . $worker_id);

                    $count_found_worker++;

                    /** ПРОВОДИМ УЧЕТ ВЫДАЧИ */
                    foreach ($worker_siz->IssueList as $siz_issue) {

                        /** ПРОВЕРЯЕМ НА ГОТОВНОСТЬ СПРАВОЧНИКОВ СИЗ (как продукция)*/
                        $response = $this->AddSiz($siz_issue, $productions, $production_hand, $siz_hand, $norms);
                        $log->addLogAll($response);
                        if ($response['status'] === 0) {
                            throw new Exception('Ошибка при выполнении метода добавления СИЗ при выдаче AddSiz');
                        }
                        if ($response['siz_hand_add']) {
                            $siz_hand[$response['siz_hand_add']['link_1c']] = $response['siz_hand_add'];
                        }
                        $wear_period = $response['wear_period'];
                        $siz_id = $response['siz_id'];
                        $count_siz_add += $response['count_siz_add'];


                        /** ПРОВЕРЯЕМ НА ГОТОВНОСТЬ СПРАВОЧНИКОВ РАЗМЕРОВ */
                        if (
                            property_exists($siz_issue, "SizeKey") and
                            isset($sizes[$siz_issue->SizeKey]) and
                            isset($size_hand[$sizes[$siz_issue->SizeKey]->SizeId])
                        ) {
//                            $log->addLog("Нашел размер");
                            $size = $size_hand[$sizes[$siz_issue->SizeKey]->SizeId]['title'];
                        } else {
//                            $log->addLog("Размер не найден");
                            $size = "-";
                        }

                        $date_issue = date("Y-m-d", strtotime($siz_issue->DataIssue));
                        $date_write_off = date("Y-m-d", strtotime($siz_issue->DataIssue . "+ " . $wear_period . " month"));
                        $date_return = $date_write_off;
                        $count_issued_siz = $siz_issue->Quantity;

                        /** ФОРМИРУЕМ КОНКРЕТНЫЙ СИЗ У КОНКРЕТНОГО РАБОТНИКА */
                        $siz_worker = WorkerSiz::findOne(['worker_id' => $worker_id, 'siz_id' => $siz_id, 'date_issue' => $date_issue]);
                        if (!$siz_worker) {
                            $siz_worker = new WorkerSiz();
                            $count_siz_worker_add_issue++;
                        } else {
                            $count_siz_worker_update_issue++;
                        }

                        $siz_worker->siz_id = $siz_id;
                        $siz_worker->worker_id = $worker_id;
                        $siz_worker->size = $size;
                        $siz_worker->count_issued_siz = $count_issued_siz;
                        $siz_worker->date_issue = $date_issue;
                        $siz_worker->date_write_off = $date_write_off;
                        $siz_worker->date_return = $date_return;
                        $siz_worker->status_id = self::STATUS_SIZ_ISSUE;
                        $siz_worker->company_department_id = $company_department_id;

                        if (!$siz_worker->save()) {
                            $log->addData($siz_worker->errors, '$siz_worker->errors', __LINE__);
                            throw new Exception("Ошибка сохранения конкретного работника СИЗ WorkerSiz");
                        }

                        $worker_siz_id = $siz_worker->id;

                        if (property_exists($siz_issue, "WearPercentage")) {
                            $percentage_wear = $siz_issue->WearPercentage;
                        } else {
                            $percentage_wear = 0;
                        }

                        $worker_siz_status[] = array(
                            'worker_siz_id' => $worker_siz_id,
                            'date' => $date_issue,
                            'percentage_wear' => $percentage_wear,
                            'status_id' => self::STATUS_SIZ_ISSUE,
                        );
                    }

                    /** ПРОВОДИМ УЧЕТ СПИСАНИЙ */
                    foreach ($worker_siz->WriteoffList as $siz_write_off) {
                        /** ПРОВЕРЯЕМ НА ГОТОВНОСТЬ СПРАВОЧНИКОВ СИЗ (как продукция)*/
                        $response = $this->AddSiz($siz_write_off, $productions, $production_hand, $siz_hand, $norms);
                        $log->addLogAll($response);
                        if ($response['status'] === 0) {
                            throw new Exception('Ошибка при выполнении метода добавления СИЗ при списании AddSiz');
                        }
                        if ($response['siz_hand_add']) {
                            $siz_hand[$response['siz_hand_add']['link_1c']] = $response['siz_hand_add'];
                        }
                        $wear_period = $response['wear_period'];
                        $siz_id = $response['siz_id'];
                        $count_siz_add += $response['count_siz_add'];

                        $date_issue = date("Y-m-d", strtotime($siz_write_off->DataIssue));
                        $date_write_off = date("Y-m-d", strtotime($siz_write_off->DateWriteOff));
                        $date_return = $date_write_off;
                        $count_issued_siz = $siz_write_off->Quantity;

                        /** ФОРМИРУЕМ КОНКРЕТНЫЙ СИЗ У КОНКРЕТНОГО РАБОТНИКА */
                        $siz_worker = WorkerSiz::findOne(['worker_id' => $worker_id, 'siz_id' => $siz_id, 'date_issue' => $date_issue, 'status_id' => self::STATUS_SIZ_ISSUE]);
                        if (!$siz_worker) {

                            $count_siz_worker_add_error++;
                            /** СПИСАНО, НО НЕ ВЫДАННО */
                            $log->addData($workers[$worker_siz->WorkerKey], '$workers[$worker_siz->WorkerKey]', __LINE__);
                            $log->addData($worker_id, '$worker_id', __LINE__);
                            $log->addData($siz_id, '$siz_id', __LINE__);
                            $log->addData($siz_write_off, '$siz_write_off', __LINE__);
//                            throw new Exception("Списан не выданный СИЗ");
                            $siz_worker_issue = new WorkerSiz();
                            $siz_worker_issue->siz_id = $siz_id;
                            $siz_worker_issue->worker_id = $worker_id;
                            $siz_worker_issue->size = "-";
                            $siz_worker_issue->count_issued_siz = $count_issued_siz;
                            $siz_worker_issue->date_issue = $date_issue;
                            $siz_worker_issue->date_write_off = $date_write_off;
                            $siz_worker_issue->date_return = $date_return;
                            $siz_worker_issue->status_id = self::STATUS_SIZ_ERROR;
                            $siz_worker_issue->company_department_id = $company_department_id;

                            if (!$siz_worker_issue->save()) {
                                $log->addData($siz_worker_issue->errors, '$siz_worker_issue->errors', __LINE__);
                                throw new Exception("Ошибка создания конкретного СИЗ работника (списано, но не выдано) WorkerSiz");
                            }
                            $worker_siz_id = $siz_worker_issue->id;
                            if (property_exists($siz_write_off, "WearPercentage")) {
                                $percentage_wear = $siz_write_off->WearPercentage;
                            } else {
                                $percentage_wear = 0;
                            }

                            $worker_siz_status[] = array(
                                'worker_siz_id' => $worker_siz_id,
                                'date' => $date_write_off,
                                'percentage_wear' => $percentage_wear,
                                'status_id' => self::STATUS_SIZ_ERROR,
                            );
                        } else {
                            /** СПИСАНО и ВЫДАНО */
                            $count_siz_worker_update_write_off++;

                            if ($siz_worker->count_issued_siz > $count_issued_siz) {                                        // если количество полученного неравно количеству списанного, то в существующей записи обновляем количество и создаем нокую запись списания записи делаем отметку
                                $siz_worker_issue = new WorkerSiz();
                                $siz_worker_issue->siz_id = $siz_id;
                                $siz_worker_issue->worker_id = $worker_id;
                                $siz_worker_issue->size = $siz_worker->size;
                                $siz_worker_issue->count_issued_siz = $count_issued_siz;
                                $siz_worker_issue->date_issue = $siz_worker->date_issue;
                                $siz_worker_issue->date_write_off = $date_write_off;
                                $siz_worker_issue->date_return = $date_return;
                                $siz_worker_issue->status_id = self::STATUS_SIZ_WRITE_OFF;
                                $siz_worker_issue->company_department_id = $company_department_id;

                                if (!$siz_worker_issue->save()) {
                                    $log->addData($siz_worker_issue->errors, '$siz_worker_issue->errors', __LINE__);
                                    throw new Exception("Ошибка создания конкретного работника СИЗ WorkerSiz");
                                }
                                $worker_siz_id = $siz_worker_issue->id;
                                if (property_exists($siz_write_off, "WearPercentage")) {
                                    $percentage_wear = $siz_write_off->WearPercentage;
                                } else {
                                    $percentage_wear = 0;
                                }

                                $worker_siz_status[] = array(
                                    'worker_siz_id' => $worker_siz_id,
                                    'date' => $date_write_off,
                                    'percentage_wear' => $percentage_wear,
                                    'status_id' => self::STATUS_SIZ_WRITE_OFF,
                                );

                            } else if ($siz_worker->count_issued_siz == $count_issued_siz) {                                // если полное списание СИЗ количество полученного равно количеству списанного, то в существующей записи делаем отметку
                                $worker_siz_status[] = array(
                                    'worker_siz_id' => $siz_worker->id,
                                    'date' => $date_write_off,
                                    'percentage_wear' => $percentage_wear,
                                    'status_id' => self::STATUS_SIZ_WRITE_OFF,
                                );
                                $siz_worker->status_id = self::STATUS_SIZ_WRITE_OFF;
                            }

                            $siz_worker->count_issued_siz = $siz_worker->count_issued_siz - $count_issued_siz;
                            $siz_worker->company_department_id = $company_department_id;

                            if (!$siz_worker->save()) {
                                $log->addData($siz_worker->errors, '$siz_worker->errors', __LINE__);
                                throw new Exception("Ошибка обновления конкретного работника СИЗ WorkerSiz");
                            }
                        }
                    }

//                    /** ПРОВОДИМ УЧЕТ ВОЗВРАТОВ */
//                    foreach ($worker_siz->ReturnList as $siz_return) {
//                        /** ПРОВЕРЯЕМ НА ГОТОВНОСТЬ СПРАВОЧНИКОВ СИЗ (как продукция)*/
//                        $response = $this->AddSiz($siz_return, $productions, $production_hand, $siz_hand, $norms);
//                        $log->addLogAll($response);
//                        if ($response['status'] === 0) {
//                            throw new Exception('Ошибка при выполнении метода добавления СИЗ при возврате AddSiz');
//                        }
////                        $log->addData($siz_id, '$siz_id', __LINE__);
//                        if ($response['siz_hand_add']) {
//                            $siz_hand[$response['siz_hand_add']['link_1c']] = $response['siz_hand_add'];
//                        }
//                        $siz_id = $response['siz_id'];
//                        $count_siz_add += $response['count_siz_add'];
//
//                        $date_return = date("Y-m-d", strtotime($siz_return->DateReturn));
//                        $count_issued_siz = $siz_return->Quantity;
//
//                        $response = $this->ReturnSiz($siz_id, $worker_id, $date_return, $count_issued_siz, $date_time_now);
//                        $log->addLogAll($response);
//                        if ($response['status'] === 0) {
//                            $log->addData($siz_return, '$siz_return', __LINE__);
//                            throw new Exception('Ошибка при выполнении метода возврата СИЗ ReturnSiz');
//                        }
//
//                        if (!isset($worker_siz_status)) {
//                            $worker_siz_status = [];
//                        }
//
//                        if (!empty($response['worker_siz_status'])) {
//                            $worker_siz_status = array_merge($worker_siz_status, $response['worker_siz_status']);
//                        }
//
//                    }

                    /** ПРОВОДИМ УЧЕТ ПОТРЕБНОСТИ */
                    foreach ($worker_siz->NeedList as $siz_need) {
                        $response = $this->NeedSiz($worker_id, $siz_need, $norms, $norm_siz_hand);
                        $log->addLogAll($response);
                        if ($response['status'] === 0) {
                            throw new Exception('Ошибка при выполнении метода учета потребности СИЗ NeedSiz');
                        }
//                        $log->addData($response['need_siz_add'],'$response[need_siz_add]',__LINE__);
                        if (!empty($response['need_siz_add'])) {
                            $need_siz_add[] = $response['need_siz_add'];
                        }
                    }

                    /** Проводим автоматическое списание */
                    $siz_workers = WorkerSiz::find()
                        ->where(['worker_id' => $worker_id, 'status_id' => self::STATUS_SIZ_ISSUE])
                        ->andWhere('date_write_off<="' . $date_time_now_without_u . '"')
                        ->all();

                    foreach ($siz_workers as $siz_worker) {
                        $siz_worker->status_id = self::STATUS_SIZ_WRITE_OFF;

                        if (!$siz_worker->save()) {
                            $log->addData($siz_worker->errors, '$siz_worker->errors', __LINE__);
                            throw new Exception("Ошибка при обновлении статуса СИЗ WorkerSiz");
                        }

                        $worker_siz_status[] = array(
                            'worker_siz_id' => $siz_worker->id,
                            'date' => $date_time_now_without_u,
                            'percentage_wear' => 100,
                            'status_id' => self::STATUS_SIZ_WRITE_OFF,
                        );

                    }
                    NormSizNeed::deleteAll(['worker_id' => $worker_id]);
                }
            }

            /** МАССОВАЯ ВСТАВКА СТАТУСОВ КОНКРЕТНЫХ СИЗ */
            if (isset($worker_siz_status)) {
                $response = $this->BatchInsert(
                    $worker_siz_status,
                    'worker_siz_status',
                    ['worker_siz_id', 'date', 'percentage_wear', 'status_id'],
                    " ON DUPLICATE KEY UPDATE `worker_siz_id` = VALUES (`worker_siz_id`), `date` = VALUES (`date`)");
                $log->addLogAll($response);
                if ($response['status'] === 0) {
                    throw new Exception('Ошибка при выполнении метода BatchInsert worker_siz_status');
                }
            }


            /** МАССОВАЯ ВСТАВКА ПОТРЕБНОСТИ СИЗ */
            if (isset($need_siz_add) and !empty($need_siz_add)) {
                $response = $this->BatchInsert($need_siz_add, 'norm_siz_need', ['worker_id', 'date_time_need', 'count_siz', 'norm_siz_id'], " ON DUPLICATE KEY UPDATE `worker_id` = VALUES (`worker_id`),`norm_siz_id` = VALUES (`norm_siz_id`), `date_time_need` = VALUES (`date_time_need`)");
                $log->addLogAll($response);
                if ($response['status'] === 0) {
                    throw new Exception('Ошибка при выполнении метода BatchInsert norm_siz_need');
                }
            }

            $log->addLog("Количество добавленных СИЗ: " . $count_siz_add);
            $log->addLog("Количество обработанных работников: " . $count_worker);
            $log->addLog("Количество найденных работников: " . $count_found_worker);
            $log->addLog("Количество добавленных конкретных СИЗ выданных: " . $count_siz_worker_add_issue);
            $log->addLog("Количество обновленных конкретных СИЗ выданных: " . $count_siz_worker_update_issue);
            $log->addLog("Количество обновленных конкретных СИЗ списанных: " . $count_siz_worker_update_write_off);
            $log->addLog("Количество обновленных конкретных СИЗ возвращенных: " . $count_siz_worker_update_return);
            $log->addLog("Количество СИЗ СПИСАННЫХ, НО НЕ ВЫДАННЫХ: " . $count_siz_worker_add_error);
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * BatchInsert - универсальный метод массовой вставки по блокам
     * @param $data_to_inserts - массив данных на вставку
     * @param $table - таблица куда вставляем
     * @param $field_array - список полей для массвой вставки
     * @param $on_duplicate - условия на проверку дубликатов
     * @return null[]
     */
    public function BatchInsert($data_to_inserts, $table, $field_array, $on_duplicate = "")
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей
        $block_to_insert = [];

        // Стартовая отладочная информация
        $log = new LogAmicumFront("batch_insert");

        try {
            $count = 0;
            foreach ($data_to_inserts as $data_to_insert) {
                $count++;
                $block_to_insert[] = $data_to_insert;

                if ($count > 2000) {
                    $insert_param_val = Yii::$app->db_amicum2->queryBuilder->batchInsert($table, $field_array, $block_to_insert);
                    $batch_inserted = Yii::$app->db_amicum2->createCommand($insert_param_val . " " . $on_duplicate)->execute();
                    $log->addLog("Вставил: " . $count);
                    unset($batch_inserted);
                    $block_to_insert = [];
                    $count = 0;
                }
            }

            if (isset($block_to_insert) and !empty($block_to_insert)) {
                $insert_param_val = Yii::$app->db_amicum2->queryBuilder->batchInsert($table, $field_array, $block_to_insert);
                $batch_inserted = Yii::$app->db_amicum2->createCommand($insert_param_val . " " . $on_duplicate)->execute();
                unset($batch_inserted);
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * NeedSiz - Метод возврата СИЗ
     * @param $worker_id - ключ работника
     * @param $siz_need - потребность работника в СИЗ
     * @param $norms - норма СИЗ справочник
     * @param $norm_siz_hand - связка нормы и сиз как нормы
     * @return null[]
     */
    public function NeedSiz($worker_id, $siz_need, $norms, $norm_siz_hand)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей
        $need_siz_add = [];

        // Стартовая отладочная информация
        $log = new LogAmicumFront("NeedSiz");

        try {
//            $log->addLog("Начало выполнения метода");

            if (!isset($norms[$siz_need->NormKey])) {
                throw new Exception('Отсутствие готовности справочника норм СИЗ');
            }

            $norm = $norms[$siz_need->NormKey];

            if (!isset($norm_siz_hand[$norm->NormId . '_' . $norm->NomNormId])) {
                throw new Exception('Отсутствие готовности справочника норм СИЗ');
            }

            $norm_siz_id = $norm_siz_hand[$norm->NormId . '_' . $norm->NomNormId]['id'];
            $count_siz = $siz_need->QuantityNeeds;
            $date_time_need = date("Y-m-d", strtotime($siz_need->DateNeeds));

            $need_siz_add = array(
                'worker_id' => $worker_id,
                'date_time_need' => $date_time_need,
                'count_siz' => $count_siz,
                'norm_siz_id' => $norm_siz_id,
            );


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

//        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result, 'need_siz_add' => $need_siz_add], $log->getLogAll());
    }

    /**
     * AddSiz - метод добавления СИЗ
     * @param $siz_vostok - сиз, который проверяем на наличие в БД
     * @param $productions - справочник СИЗ при выдаче
     * @param $siz_hand - справочник СИЗ АМИКУМ
     * @return float[]|int[]|null[]
     */
    public function AddSiz($siz_vostok, $productions, $production_hand, $siz_hand, $norms)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_siz_add = 0;
        $wear_period = -1;
        $siz_id = -1;
        $siz_hand_add = null;

        // Стартовая отладочная информация
        $log = new LogAmicumFront("AddSiz");

        try {
            if (
                !isset($productions[$siz_vostok->ProductKey]) or
                !isset($production_hand[$productions[$siz_vostok->ProductKey]->ProductId])
            ) {
                throw new Exception("Отсутствие готовности по справочникам СИЗ (как продукция)");
            }

            $siz = $production_hand[$productions[$siz_vostok->ProductKey]->ProductId];

            /** СОЗДАЕМ СИЗ ЕСЛИ ЕГО НЕТ, ИЛИ ВЫБИРАЕМ СУЩЕСТВУЮЩИЙ */
            if (
                !isset($siz_hand[$siz['link_1c']]) and
                isset($norms[$siz_vostok->ProductKey])
            ) {
                $norm = $norms[$siz_vostok->ProductKey];


                if (
                    $norm->NormCalculationType == "До износа" or
                    $norm->NormCalculationType == "Дежурный"
                ) {
                    $wear_period = 36;
                } else if (
                    $norm->NormCalculationType == "Период" and
                    $norm->NormPeriodType == "Год"
                ) {
                    $wear_period = $norm->NormPeriodCount * 12;
                } else if (
                    $norm->NormCalculationType == "Период" and
                    $norm->NormPeriodType == "Месяц"
                ) {
                    $wear_period = $norm->NormPeriodCount;
                }

                $siz_new = new Siz();
                $siz_new->title = $siz['title'];                                                                    // название СИЗ
                $siz_new->unit_id = 81;                                                                             // единицы измерения
                $siz_new->wear_period = $wear_period;                                                               // срок носки
                $siz_new->season_id = 5;                                                                            // сезон носки
                $siz_new->siz_kind_id = 30100092;                                                                   // вид сиз
                $siz_new->link_1c = $siz['link_1c'];                                                                // ссылка на внешнюю систему

                if (!$siz_new->save()) {
                    $log->addData($siz_new->errors, '$siz_new->errors', __LINE__);
                    throw new Exception("Ошибка обновления справочника СИЗ Siz");
                }
                $siz_id = $siz_new->id;
                $count_siz_add++;
                $siz_hand_add = array(
                    'id' => $siz_id,
                    'title' => $siz['title'],
                    'unit_id' => 81,
                    'wear_period' => $wear_period,
                    'season_id' => 5,
                    'siz_kind_id' => 30100092,
                    'link_1c' => $siz['link_1c'],
                );

//                    $log->addLog("Создал СИЗ: ". $siz_id);
            } else {
                $wear_period = $siz_hand[$siz['link_1c']]['wear_period'];
                $siz_id = $siz_hand[$siz['link_1c']]['id'];
//                    $log->addLog("СИЗ был: ". $siz_id);
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

//        $log->addLog("Окончание выполнения метода");

        return array_merge([
            'Items' => $result,
            'siz_id' => $siz_id,
            'count_siz_add' => $count_siz_add,
            'wear_period' => $wear_period,
            'siz_hand_add' => $siz_hand_add
        ], $log->getLogAll());
    }

    /**
     * ReturnSiz - Метод возврата СИЗ
     * @param $siz_id - Ключ справочника сиз
     * @param $worker_id - ключ работника
     * @param $date_return - дата возврата сиз
     * @param $count_issued_siz - количество сиз на возврат
     * @param $date_time_now - дата и время транзакции
     * @return null[]
     */
    public function ReturnSiz($siz_id, $worker_id, $date_return, $count_issued_siz, $date_time_now)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей
        $worker_siz_status = null;                                                                                      // статусы по возвратам работника

        // Стартовая отладочная информация
        $log = new LogAmicumFront("ReturnSiz");

        try {
            $log->addLog("Начало выполнения метода");

            if ($count_issued_siz <= 0 or !$count_issued_siz) {
                throw new Exception('Количество сиз на возврат null, 0 или меньше ноля: ' . $count_issued_siz);
            }

            $siz_workers = WorkerSiz::find()                                                                            // список выданных СИЗ
            ->where(['worker_id' => $worker_id, 'siz_id' => $siz_id, 'status_id' => self::STATUS_SIZ_ISSUE])
                ->orderBy(['date_issue' => SORT_ASC])
                ->all();
            $siz_workers_arr = WorkerSiz::find()                                                                            // список выданных СИЗ
            ->where(['worker_id' => $worker_id, 'siz_id' => $siz_id, 'status_id' => self::STATUS_SIZ_ISSUE])
                ->orderBy(['date_issue' => SORT_ASC])
                ->asArray()
                ->all();

            foreach ($siz_workers as $siz_worker) {
                if ($siz_worker->count_issued_siz > $count_issued_siz) {                                                // если количество СИЗ выданных больше чем возврат
                    $count_siz_to_return = $siz_worker->count_issued_siz - $count_issued_siz;
                    $count_issued_siz = 0;
                } elseif ($siz_worker->count_issued_siz == $count_issued_siz) {                                         // если количество СИЗ выданных равно возврату
                    $count_siz_to_return = $count_issued_siz;
                    $count_issued_siz = 0;
                    $siz_worker->status_id = self::STATUS_SIZ_RETURN;
                } else {                                                                                                // если количество СИЗ выданных меньше чем возврат, то сделать еще круг
                    $count_siz_to_return = $siz_worker->count_issued_siz;
                    $count_issued_siz = $count_issued_siz - $siz_worker->count_issued_siz;
                    $siz_worker->status_id = self::STATUS_SIZ_RETURN;
                }

                $siz_worker->count_issued_siz = $count_siz_to_return;

                $siz_worker->date_return = $date_return;

                if (!$siz_worker->save()) {
                    $log->addData($siz_worker->errors, '$siz_worker->errors', __LINE__);
                    throw new Exception("Ошибка обновления конкретного работника СИЗ при возврате WorkerSiz");
                }

                $worker_siz_status[] = array(
                    'worker_siz_id' => $siz_worker->id,
                    'date' => $date_return,
                    'percentage_wear' => 0,
                    'status_id' => self::STATUS_SIZ_RETURN,
                );

                if ($count_issued_siz == 0) {
                    break;
                }
            }

            if ($count_issued_siz != 0) {
//                $log->addData($siz_workers_arr, '$siz_workers_arr', __LINE__);
//                $log->addData($worker_id, '$worker_id', __LINE__);
//                $log->addData($siz_id, '$siz_id', __LINE__);
//                $log->addData($count_issued_siz, '$count_issued_siz', __LINE__);
//                throw new Exception("Вернули больше чем было выдано");
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result, 'worker_siz_status' => $worker_siz_status], $log->getLogAll());
    }

    /**
     * GetEstablishedNorms - Метод получения данных по нормам СИЗ
     * @param $inn - ИНН организации
     * @param $kpp - КПП организации
     * @param string $excode - тип запроса (004 - полный запрос)
     * @return int[]|null[]
     * @example localhost/admin/serviceamicum/siz-vostok-service/get-data
     */
    public function GetEstablishedNorms($inn, $kpp, $excode = "004")
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("GetEstablishedNorms");

        try {
            $log->addLog("Начало выполнения метода");

            $queryParams = [
                'inn' => $inn,
                'kpp' => $kpp,
                'excode' => $excode
            ];

            $address = 'https://' . SIZ_VOSTOK_SERVICE_LOGIN . ":" . SIZ_VOSTOK_SERVICE_PWD . "@" . SIZ_VOSTOK_SERVICE_HOST . '/hs/AstbData/GetEstablishedNorms';

            $response = $this->CurlGetData($address, $queryParams);
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception('Ошибка при выполнении метода запроса данных с сервера внешнего CurlGetData');
            }

            if (Yii::$app->id != "app-backend") {
                $path = Yii::getAlias('@app') . '/files/';
            } else {
                $path = "files/";
            }

            $file = $path . 'GetEstablishedNorms_' . Assistant::GetDateTimeNow(true) . '.zip';

            file_put_contents($file, $response['Items'], LOCK_EX);

            $response = $this->UnZip($file, $path);
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception('Ошибка при выполнении метода разархивирования файла UnZip');
            }

            $response = file_get_contents($path . 'package.txt');
            $json = json_decode($response);

            $log->addData($json, '$json', __LINE__);

            $norms = $json->DirNorms;
            $response = $this->SynhSizNorm($norms);
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception('Ошибка при выполнении метода синхронизации справочника СИЗ для норм выдачи SynhSizNorm');
            }

            $response = $this->SynhNormHand($norms);
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception('Ошибка при выполнении метода синхронизации справочника норм выдачи СИЗ SynhNormHand');
            }

            $response = $this->SynhNormSiz($norms);
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception('Ошибка при выполнении метода синхронизации связки норм выдачи и справочника норм выдачи СИЗ SynhNormSiz');
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * actionFixPackage - Метод подтверждения успешного получения пакета с данными
     * @example localhost/admin/serviceamicum/siz-vostok-service/fix-package
     */
    public function actionFixPackage()
    {
//        ini_set('max_execution_time', -1);
//        ini_set('memory_limit', "10500M");
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("actionGetData");

        try {
            $log->addLog("Начало выполнения метода");


            // подтверждаем получение сообщения
            $response = $this->FixPackage(7704531762, 143401001, 1);
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception('Ошибка при выполнении метода GetEstablishedNorms');
            }


        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * FixPackage - Метод подтверждения успешного получения пакета с данными
     * @param $inn - ИНН организации
     * @param $kpp - КПП организации
     * @param $number_message - номер подтверждаемого сообщения
     * @param string $excode - тип запроса (004 - полный запрос)
     * @return null[]
     * @example localhost/admin/serviceamicum/siz-vostok-service/get-data
     */
    public function FixPackage($inn, $kpp, $number_message, $excode = "004")
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("FixPackage");

        try {
            $log->addLog("Начало выполнения метода");

            $queryParams = [
                'inn' => $inn,
                'kpp' => $kpp,
                'excode' => $excode
            ];

            $address = 'https://' . SIZ_VOSTOK_SERVICE_LOGIN . ":" . SIZ_VOSTOK_SERVICE_PWD . "@" . SIZ_VOSTOK_SERVICE_HOST . '/hs/AstbData/FixPackage/' . $number_message;

            $response = $this->CurlGetData($address, $queryParams);
            $log->addLogAll($response);
            if ($response['status'] === 0) {
                throw new Exception('Ошибка при выполнении метода запроса данных с сервера внешнего CurlGetData');
            }

            $json = json_decode($response['Items']);

            $log->addData($json, '$json', __LINE__);
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog("Окончание выполнения метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }
}