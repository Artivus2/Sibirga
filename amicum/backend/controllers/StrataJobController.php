<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers;

use backend\controllers\cachemanagers\EquipmentCacheController;
use backend\controllers\cachemanagers\LogCacheController;
use backend\controllers\cachemanagers\SensorCacheController;
use backend\controllers\cachemanagers\ServiceCache;
use backend\controllers\cachemanagers\WorkerCacheController;
use backend\controllers\const_amicum\EventEnumController;
use backend\controllers\const_amicum\ParamEnum;
use backend\controllers\const_amicum\ParameterTypeEnumController;
use backend\controllers\const_amicum\StatusEnumController;
use frontend\controllers\BindMinerToLanternController;
use frontend\controllers\handbooks\HandbookDepartmentController;
use frontend\controllers\positioningsystem\ForbiddenZoneController;
use frontend\controllers\reports\SummaryReportEndOfShiftController;
use frontend\controllers\system\LogAmicumFront;
use frontend\models\Equipment;
use frontend\models\EquipmentParameterValue;
use frontend\models\Parameter;
use frontend\models\Sensor;
use frontend\models\StrataPackageInfo;
use frontend\models\TextMessage;
use frontend\models\ViewWorkerSensorMaxDateFullInfo;
use frontend\models\WorkerObject;
use Throwable;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\httpclient\Exception;
use yii\web\Controller;
use yii\web\Response;

// checkExistenceEquipmentInEdge                    - Метод проверки наличие конвейера на конкретном выработке
// checkExpiredPackage                              - Метод проверяет просроченность пакета в Кеше. Если в Кеше время новее чем пришедшее с пакета, то в кеш не кладем
// getLastCheckinout                                - Метод проверяет если в кеше параметр 158 (регистрация разрегистрация) воркера новее прешедшего значения, то вернет false иначе вернет true и тем самым разрешит запись нового значения в кеш
// getCheckPossibilityRegistration                  - Метод проверяет возможность регистрациии работника в шахте в случае если он не был зарегистрирован там ранее
// stopConveyor                                     - Функция остановки конвейера
// encodeTextToStrataBytes                          - Переводит строку в байты, понятные светильникам Strata
// actionCancelAlarmSignal                          - Отмена аварийного сигнала
// actionReadTextMessagesSensor                     - Функция отображения истории сообщений выбранного сенсора диспетчером
// actionReadTextMessages                           - Функция отображения истории сообщений выбранного воркера с диспетчером

// AddMessageSensor                                 - Метод добавления сообщений в кеш и в БД по сенсорам
// AddMessage                                       - Метод добавления сообщений в кеш и в БД по работникам
// isSensorDataUpdate                               - функция проверки актуальности данных в кеше по значению параметров датчиков.
// saveSensorParameterForce                         - Функция для сохранения параметра датчика в кеш и БД без проверки актуальности.
// saveSensorParameter                              - Функция для сохранения параметра датчика в кеш и БД с проверкой на актуальность
// saveSensorParameterBatchForce                    - Функция для создания структуры параметра датчика в кеш и БД - без проверки на изменение значения
// saveSensorParameterBatch                         - Функция для создания структуры параметра датчика в кеш и БД - для последующего сохранения с проверкой на изменение значения
// getMinerBatteryPercent                           - Метод для вычисления процента заряда светильника
// getCommnodeBatteryPercent                        - Метод для вычисления процента заряда батареи узла связи
// createSensorDatabase                             - Метод для создания сенсора в базе данных.
// saveWorkerParameterIfChange                      - Тупо для определения времени без движения, потом переделать
// saveWorkerParameterForceBatch                    - Функция для сохранения параметра воркера в кеш и БД без проверки актуальности.
// saveWorkerParameterForce                         - Функция для сохранения параметра воркера в кеш и БД без проверки актуальности.
// saveWorkerParameterBatch                         - Функция для сохранения параметра воркера в кеш и БД.
// saveWorkerParameter                              - Функция для сохранения параметра воркера в кеш и БД.
// isWorkerDataUpdate                               - Функция проверки актуальности данных в кеше по значению параметров воркеров
// getShiftDateNum                                  - Метод для вычисления номера и даты смены
// getSensorType                                    - Возвращает тип луча (постоянный/резервный) из базы
// AddSummaryReportSensorGasConcentrationRecord     - Метод для добавления записи в отчётную таблицу summary_report_sensor_gas_concentration
// SaveEquipmentParameter                           - Функция для сохранения параметра оборудования в кеш и БД.
// isEquipmentDataUpdate                            - функция проверки актуальности данных в кеше по значению параметров оборудования
// saveEnvironmentalPacket                          - Фунцкия сохранения параметров датчиков окружения
// saveEnvironmentalPacketEquipmentParameters       - Сохранение параметров оборудования из пакета газов
// saveEnvironmentalPacketWorkerParameters          - Сохранение параметров воркера из пакета газов
// saveEnvironmentalPacketSensorParameters          - Сохранение параметров сенсора из пакета газов
// saveRegistrationPacketWorkerParameters           - Сохраняет параметры воркера, полученные из пакета регистрации
// saveRegistrationPacketSensorParameters           - Сохраняет параметры сенсора, полученные из пакета регистрации шахтёра
// saveRegistrationPacket                           - Сохранение параметров пакета регистрации шахтёра
// saveHeartbeatPacket                              - Функция сохранения параметров Heartbeat сообщений
// addWorkerMotionLessRecord                        - Метод для добавления записи в отчетную таблицу worker_motion_less
// saveLocationPacket                               - Метод сохранения местоположения (координат) и места (edge) работника и датчика.
// checkForbiddenZoneStatus                         - Проверяет находится ли объект в запретной зоне.
// insertRowWorkerRegisteredWithoutCheckin          - Добавляет новую строку в таблицу worker_registered_without_checkin.
// saveLocationPacketEquipmentParameters            - Сохраняет параметры оборудования, полученные из пакета положения шахтёра.
// saveLocationPacketWorkerParameters               - Сохраняет параметры воркера, полученные из пакета положения шахтёра.
// saveLocationPacketSensorParameters               - Сохраняет параметры сенсора, полученные из пакета положения шахтёра.
// actionSavePackToBase                             - Функция записи пакета в отладочную таблицу в БД
// actionNodeListUpdate                             - Функция для проверки актуальности значения состояния узлов связи.
// SaveMessageRead                                  - Используется для обработки и сохранения данных из пакета
// SaveMessageAck                                   - Метод сохранения статса сообщение в Бд. Что оно доставлено
// actionSaveStrataPackage                          - Точка входа службы. Является главным методом, который принимает от службы
// isWorkerDataUpdateNoTime                         - проверка на наличие данных в кеше, если нет, то проиниицализирует (проверит в БД), и если совсем нет, то вернет false, иначе вернет значение

/** НАЧАЛ ПЕРЕПИСЫВАТЬ СЛУЖБУ СБОРА ДАННЫХ СТРАТА */
// actionTranslatePackage           - метод расшифровки пакетов Strata
// TranslatePackage                 - метод расшифровки пакетов Strata
// crc16                            - расчет контролльной суммы пакета Strata по CRC16
// TranslateHeartbeat               - метод по расшифровке пакетов состояний узлов связи
// TranslateLocation                - метод по расшифровке пакетов локаций
// TranslateCheckInCheckOut         - метод по расшифровке пакетов регистрации / разрегистрации
// TranslateEnvironmental           - метод по расшифровке пакетов газов
// TranslateAscRead                 - метод по расшифровке пакетов получения / прочтения сообщения

// getExternalId                    - метод возвращает внешний ключ в страте по сетевому адресу
// getNetworkId                     - метод возвращает сетевой адрес по внешенему ключу страта

// convertAmsSensorIdToId           - Convert AMS Sensor Commtrac External Id to Human Readable format
// convertMinerExternalIdToId       - Convert Miner Commtrac External Id to Human Readable format

/**
 * Description of HearedNode
 * Структура услышанного узла связи. Используется в классах дешифрованных сигналов
 * @author Ingener401
 */
class HearedNode
{
    public $address; //адрес, сетевой идентификатор узла связи
    public $rssi; //уровень сигнала, с которым узел слышен

    public function __construct($address, $rssi)
    {
        $this->address = $address;
        $this->rssi = $rssi;
    }
}

/**
 * Class CommunicationNodeHeartbeat - пакет узла связи
 * @package backend\controllers
 */
class CommunicationNodeHeartbeat
{
    public $sequenceNumber;         //последовательный номер сигнала узла
    public $batteryVoltage;         //напряжение батареи узла связи
    public $sourceNetworkId;        //сетевой адрес узла-источника сигнала
    public $routingRootNodeAddress; //адрес шлюза (корня) маршрутизации
    public $routingParentNode;      //узел связи, через который сигнал идет шлюзу
    public $neighborTableFull;      //флаг заполненности таблицы соседей
    public $neighborCount;          //количество соседних узлов (от 0 до 7)
    public $routingRootHops;        //количество транзитных участков до шлюза маршрутизации
    public $timingRootNodeAddress;  //сетевой адрес шлюза синхронизации
    public $timingParentNode;       //узел, через который идет сигнал к шлюзу синхронизации
    public $timingRootHops;         //количество транзитных участков до шлюза синхронизации
    public $lostRoutingParent;      //количество потерь родителя маршрутизации с момента последнего heartbeat-сообщения
    public $lostTimingParent;       //количество потерь родителя синхронизации с момента последнего heartbeat-сообщения
    public $routingChangeParents;   //количество изменений родителя маршрутизации
    public $timingChangeParents;    //количество изменений родителя синхронизации
    public $routingAboveThresh;     //флаг превышения порога уровня сигнала родителем маршрутизации
    public $timingAboveThresh;      //флаг превышения порога уровня сигнала родителем синхронизации
    public $queueOverflow;          //размер переполнения очереди
    public $netEntryCount;          //количество входов в сеть
    public $minNumberIdleSlots;     //минимальное количество простоев
    public $listenDuringTransmit;   //флаг разрешения прослушивания сети при передаче данных
    public $netEntryReason;         //причина входа в сеть
    public $grandparentBlocked;     //прародитель заблокирован
    public $parentTimeoutExpired;   //время ожидания родителя истекло
    public $cycleDetection;         //флаг обнаружения зацикливания сигнала
    public $noIdleSlots;            //флаг отсутствия простоев
    public $CC1110;                 //версия СС1110
    public $PIC;                    //версия PIC
    public $numberOfHeartbeats;     //количество heartbeat-сообщений
    public $timestamp;              // метка времени

    public function __construct($dataArray)
    {
        $this->timestamp = $dataArray->timestamp;
//        $this->timestamp = Assistant::GetDateNow();
        $this->sequenceNumber = $dataArray->sequenceNumber;                                                             //последовательный номер сигнала узла
        $this->batteryVoltage = (string)str_replace(',', '.', $dataArray->batteryVoltage);                  //напряжение батареи узла связи
        $this->sourceNetworkId = $dataArray->sourceNode;                                                                //сетевой адрес узла-источника сигнала
        $this->routingRootNodeAddress = $dataArray->routingRootNodeAddress;                                             //адрес шлюза (корня) маршрутизации
        $this->routingParentNode = new HearedNode($dataArray->routingParentNode->address, $dataArray->routingParentNode->rssi);
        $this->neighborTableFull = $dataArray->neighborTableFull;                                                       //флаг заполненности таблицы соседей
        $this->neighborCount = $dataArray->neighborCount;                                                               //количество соседних узлов (от 0 до 7)
        $this->routingRootHops = $dataArray->routingRootHops;                                                           //количество транзитных участков до шлюза маршрутизации
        $this->timingRootNodeAddress = $dataArray->timingRootNodeAddress;                                               //сетевой адрес шлюза синхронизации
        $this->timingParentNode = new HearedNode($dataArray->timingParentNode->address, $dataArray->timingParentNode->rssi);
        $this->timingRootHops = $dataArray->timingRootHops;                                                             //количество транзитных участков до шлюза синхронизации
        $this->lostRoutingParent = $dataArray->lostRoutingParent;                                                       //количество потерь родителя маршрутизации с момента последнего heartbeat-сообщения
        $this->lostTimingParent = $dataArray->lostTimingParent;                                                         //количество потерь родителя синхронизации с момента последнего heartbeat-сообщения
        $this->routingChangeParents = $dataArray->routingChangeParents;                                                 //количество изменений родителя маршрутизации
        $this->timingChangeParents = $dataArray->timingChangeParents;                                                   //количество изменений родителя синхронизации
        $this->routingAboveThresh = $dataArray->routingAboveThresh;                                                     //флаг превышения порога уровня сигнала родителем маршрутизации
        $this->timingAboveThresh = $dataArray->timingAboveThresh;                                                       //флаг превышения порога уровня сигнала родителем синхронизации
        $this->queueOverflow = $dataArray->queueOverfow;                                                                //размер переполнения очереди
        $this->netEntryCount = $dataArray->netEntryCount;                                                               //количество входов в сеть
        $this->minNumberIdleSlots = $dataArray->minNumberIdleSlots;                                                     //минимальное количество простоев
        $this->listenDuringTransmit = $dataArray->listenDuringTransmit;                                                 //флаг разрешения прослушивания сети при передаче данных
        $this->netEntryReason = $dataArray->netEntryReason;                                                             //причина входа в сеть
        $this->grandparentBlocked = $dataArray->grantparentBlocked;                                                     //прародитель заблокирован
        $this->parentTimeoutExpired = $dataArray->parentTimeoutExpired;                                                 //время ожидания родителя истекло
        $this->cycleDetection = $dataArray->cycleDetection;                                                             //флаг обнаружения зацикливания сигнала
        $this->noIdleSlots = $dataArray->noIdleSlots;                                                                   //флаг отсутствия простоев
        $this->CC1110 = $dataArray->cc1110;                                                                             //версия СС1110
        $this->PIC = $dataArray->pic;                                                                                   //версия PIC
        $this->numberOfHeartbeats = $dataArray->numberOfHeartbeats;                                                     //количество heartbeat-сообщений
    }
}

/**
 * Description of MinerNodeCheckInOut - пакет метки регистрации/разрегистрации
 *
 * @author Ingener401
 */
class MinerNodeCheckInOut
{
    public $sequenceNumber;     // последовательный номер сигнала
    public $batteryVoltage;     // напряжение батареи устройства
    public $minerNodeAddress;   // сетевой адрес узла шахтера
    public $hearedNodesCount;   // количество слышимых узлов
    public $hearedNodes;        // массив слышимых узлов (HearedNode)
    public $checkIn;            // флаг спуска
    public $timestamp;          // метка времени

    public function __construct($dataArray)
    {

        $this->timestamp = $dataArray->timestamp;
        $this->sequenceNumber = $dataArray->sequenceNumber;
        $this->batteryVoltage = (string)str_replace(',', '.', $dataArray->batteryVoltage);
        $this->minerNodeAddress = $dataArray->sourceNode;
        $this->checkIn = $dataArray->checkIn;
//        $this->hearedNodesCount = $dataArray->hearedNodesCount;
//        $this->hearedNodes = $dataArray->hearedNodes;
    }
}

/**
 * Description of EnvironmentalSensor
 * Структура сигналов с параметрами окружения датчика
 * @author Ingener401
 */
class EnvironmentalSensor
{
    // Параметры, передаваемые в пакете
    public $timestamp;
    public $sequenceNumber;     // последовательный номер сигнала
    public $nodeAddress;        // адрес узла (датчика)
    public $parametersCount;    // количество параметров
    public $parameters;         // массив параметров с их значениями (EnvironmentalParameter)

    // Вычисленные параметры
    public $moduleType;         // Тип датчика (какой газ измеряет CO или CH4)
    public $gasFactor;          // Множитель, на который умножается значение газа (количество знаков после запятой)
    public $gasLevelValue;      // Значение параметра концентрации газа

    // пример пакета на вход в конструктор):
    /**
     * $packet_object = (object) [
     * 'sequenceNumber' => '1',                                                                                // номер секции, не имеет практического значения (но нужно для сбора сообщения из разных кусков)
     * 'sourceNode' => '12',                                                                                   // сетевой идентификатор узла связи
     * 'parametersCount' => 2,                                                                                 // количество параметров передаваемых в пакете
     * 'timestamp' => '2019-08-08 11:50:00',                                                                   // время в которое был получен пакет
     * 'parameters' => array(                                                                                  // блок параметров
     * (object) [
     * 'id' => 100,                                                                                    // ключ параметра (бесполезен в данном случае) изначально это sensor_parameter_id. Нужен для записи на прямую в БД без поиска ключа, но в методах не используется)
     * 'value' => (object) [                                                                           //
     * 'gasReading' => '70',                                                                       // значение газа полученное из пакета (но его еще надо обработать,  т.к. для CO надо делить на 10000, а CH4 надо делить на 100)
     * 'sensorModuleType' => '20'                                                                  // Тип датчика (какой газ измеряет CO(0) или CH4(20))
     * ]
     * ],
     * (object) [                                                                                          // блок нужен для правильного вычисления значения самого газа (эта часть по сути параметры для вышестоящего параметра) Но пример рукожопства конкретного программиста
     * 'id' => 101,                                                                                    // ключ параметра (бесполезен в данном случае) изначально это sensor_parameter_id. Нужен для записи на прямую в БД без поиска ключа, но в методах не используется)
     * 'value' => (object) [
     * 'totalDigits' => '0',                                                                       // количество знаков перед запятой
     * 'decimalDigits' => '0'                                                                      // количество знаков после запятой
     * ]
     * ],
     * )
     * ];
     */
    public function __construct($dataArray)
    {
        // Инициализация полученных параметров
        $this->sequenceNumber = $dataArray->sequenceNumber;
        $this->nodeAddress = $dataArray->sourceNode;
        $this->parametersCount = $dataArray->parametersCount;
        $this->timestamp = $dataArray->timestamp;
        $this->parameters = array();
        foreach ($dataArray->parameters as $parameter) {
            $this->parameters[] = new EnvironmentalParameter($parameter->id, $parameter->value);
        }

        // Вычисление вспомогательных параметров
        // Тип датчика: 0 - CO, 20 - CH4
        $this->moduleType = $this->parameters[0]->value->sensorModuleType;

        // Множитель, на который умножается значение (10 в отрицательной степени)
        // Обозначает число цифр после запятой
        $this->gasFactor = pow(10, -$this->parameters[1]->value->decimalDigits);
        if ($this->moduleType == 20 && $this->gasFactor == 1) {
            $this->gasFactor = pow(10, -2);
        }

        // Значение газа
        if ($this->parameters[0]->value->gasReading == 999) {
            $this->gasLevelValue = 0;
        } else {
            $this->gasLevelValue = $this->parameters[0]->value->gasReading * $this->gasFactor;
            if ($this->moduleType == 0) {
                $this->gasLevelValue *= pow(10, -4);
                $this->gasLevelValue = number_format($this->gasLevelValue, 4, '.', '');
            }
        }
    }
}

/**
 * Description of EnvironmentalParameter
 * Параметр окружающей среды
 * @author Ingener401
 */
class EnvironmentalParameter
{
    public $id; //идентификатор параметра
    public $value; //значение параметра

    public function __construct($id, $value)
    {
        $this->id = (int)$id;
        $this->value = $value;
    }
}

/**
 * Description of MinerNodeLocation
 * Структура сигнала местоположения шахтера
 * @author Ingener401
 */
class MinerNodeLocation
{
    public $timestamp;          // метка времени
    public $batteryVoltage;     // напряжение батареи узла связи
    public $networkId;          // адрес узла-источника
    public $alarmFlag;          // флаг получения сигнала SOS с устройства
    public $emergencyFlag;      // флаг экстренной ситуации
    public $surfaceFlag;        // нахождение шахтера (под землей/на поверхности в движении/на поверхности без движения)
    public $movingFlag;         // флаг в движении/без движения
    public $nodes = [];         // Массив, содержащий информацию о слышимых узлах и уровне сигнала с них (HearedNode)

    public function __construct($dataArray)
    {
//        $this->timestamp = Assistant::GetDateNow();
        $this->timestamp = $dataArray->timestamp;
        $this->batteryVoltage = $dataArray->batteryVoltage;
        $this->networkId = $dataArray->networkId;
        $this->alarmFlag = $dataArray->alarmFlag;
        $this->emergencyFlag = $dataArray->emergencyFlag;
        $this->surfaceFlag = $dataArray->surfaceFlag;
        $this->movingFlag = $dataArray->movingFlag;
        foreach ($dataArray->nodes as $node) {
            $this->nodes[] = new HearedNode($node->address, $node->rssi);
        }
    }
}


/**
 * Class StrataJobController
 * @package app\controllers
 * Контроллер службы сбора данных Strata. Реализует методы для обработки полученных
 * данных и записи в БД и кеш.
 */
class StrataJobController extends Controller
{

    public static function actionNodeListUpdate()
    {
        //return "";
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        $start = microtime(true);

        try {
            $date_time_now = Assistant::GetDateNow();
            $currentTime = strtotime(Assistant::GetDateTimeNow());                                                          // берем текущее время
            $warnings[] = "actionNodeListUpdate. Начал выполнять метод";
            //массив сетевых идентификаторов узлов связи
            $nodeList = (new Query())
                ->select([
                    'id',
                    'title',
                    'object_id',
                ])
                ->from('sensor')
                ->where('object_id in (45, 46, 90, 91, 105, 113)')
                ->all();

            $warnings[] = __FUNCTION__ . '. получил узлы связи из БД. Количество = ' . count($nodeList);
            $sensor_cache_controller = (new SensorCacheController());
            $sensor_parameter_values = $sensor_cache_controller->multiGetParameterValueHash('*', 164, 3); // получаем предыдущее значение параметра/тега
            if ($sensor_parameter_values) {
                foreach ($sensor_parameter_values as $sensor_parameter_value) {
                    $sensor_parameter_values_164[$sensor_parameter_value['sensor_id']] = $sensor_parameter_value;
                }
            }

            $sensor_parameter_values = $sensor_cache_controller->multiGetParameterValueHash('*', 346, 1); // получаем предыдущее значение параметра/тега
            if ($sensor_parameter_values) {
                foreach ($sensor_parameter_values as $sensor_parameter_value) {
                    $sensor_parameter_values_346[$sensor_parameter_value['sensor_id']] = $sensor_parameter_value;
                }
            }

            $count_errors_node = 0;                                                                                       // количество узлов в отказе
            $count_mine_node = 0;                                                                                       // количество узлов в отказе
            if ($nodeList) {
                foreach ($nodeList as $node) {                                                                                  //перебираем все сетевые идентификаторы
                    if (isset($sensor_parameter_values_346[$node['id']]) and $sensor_parameter_values_346[$node['id']]['value'] == AMICUM_DEFAULT_MINE and isset($sensor_parameter_values_164[$node['id']])) {
                        $time_of_last_value = strtotime($sensor_parameter_values_164[$node['id']]['date_time']);                                           // берем время без микросекунд
                        $dTime = $currentTime - $time_of_last_value;                                                            // вычисляем разницу во времени
                        if ($dTime > 660) {                // если значение не обновлялось более 11 минут
                            // добавить в кеш
                            $date_to_cache[] = SensorCacheController::buildStructureSensorParametersValue($node['id'], $sensor_parameter_values_164[$node['id']]['sensor_parameter_id'], 164, 3, $date_time_now, 0, 1);

                            // добавить в БД
                            $date_to_db[] = array(
                                'sensor_parameter_id' => $sensor_parameter_values_164[$node['id']]['sensor_parameter_id'],
                                'date_time' => $date_time_now,
                                'value' => 0,
                                'status_id' => 1
                            );
                            $count_errors_node++;
                        }
                    } else {
                        $count_mine_node++;
                    }
                }
            }
            $warnings[] = __FUNCTION__ . '. Количество узлов в отказе = ' . $count_errors_node;
            $warnings[] = __FUNCTION__ . '. Количество узлов без кеша или с другой шахтой = ' . $count_mine_node;

            if (isset($date_to_db)) {
                $insert_param_val = Yii::$app->db_amicum2->queryBuilder->batchInsert('sensor_parameter_value', ['sensor_parameter_id', 'date_time', 'value', 'status_id'], $date_to_db);
                $count_insert_param_val = Yii::$app->db_amicum2->createCommand($insert_param_val)->execute();
//                $count_insert_param_val = Yii::$app->db_amicum2->createCommand($insert_param_val . " ON DUPLICATE KEY UPDATE `value` = VALUES (`value`), `status_id` = VALUES (`status_id`)")->execute();
                $warnings[] = __FUNCTION__ . '. Вставил в БД = ' . $count_insert_param_val;
            }
            $warnings[] = __FUNCTION__ . '. блок массовой вставки значений в БД = ' . (microtime(true) - $start);

            /**
             * блок массовой вставки значений в кеш
             */
            if (isset($date_to_cache)) {
                $ask_from_method = (new SensorCacheController)->multiSetSensorParameterValues($date_to_cache);
                if ($ask_from_method['status'] != 1) {
                    $errors[] = $ask_from_method['errors'];
                    throw new \Exception('actionNodeListUpdate. Не смог обновить параметры в кеше сенсоров ');
                }
                $warnings[] = __FUNCTION__ . '. Вставил в кэш = ' . count($date_to_cache);
            }
            $warnings[] = __FUNCTION__ . '. блок массовой вставки значений в кэш = ' . (microtime(true) - $start);


        } catch (Throwable $exception) {
            $errors[] = "actionNodeListUpdate. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = "actionNodeListUpdate. Закончил выполнять метод";
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        /* Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
         Yii::$app->response->data = $result_main;*/
        return $result_main;
    }

    /**
     * Функция сохранения параметров Heartbeat сообщений
     * @param CommunicationNodeHeartbeat $pack
     * @param $mine_id
     * @return array
     */
    public static function saveHeartbeatPacket(CommunicationNodeHeartbeat $pack, $mine_id)
    {
        $method_name = "saveHeartbeatPacket";
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        try {
            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            /**=================================================================
             * Получаем из кеша сенсор айди по нетворк айди
             * ==================================================================*/
            $start = microtime(true);
            $network_id = $pack->sourceNetworkId;
            $warnings[] = 'saveHeartbeatPacket. Начал выполнять метод';
            // Поиск объекта сенсора по сетевому идентификатору из пакета.
            // Если такой сенсор не найден, то создаём новый сенсор по шаблону.
            $warnings[] = 'saveHeartbeatPacket. Получаем по network_id -> sensor_id';
            $response = SensorMainController::getOrSetSensorByNetworkId($network_id);

            /** Отладка */
            $description = 'Закончил поиск в кеше сенсора айди по нетворк айди';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            if ($response['status'] == 1) {   //если sensor_id не найден, создать его
                $warnings[] = $response['warnings'];
                $sensor_id = $response['sensor_id'];
                $warnings[] = 'saveHeartbeatPacket. Успешно закончил поиск в кеше и БД ключа сенсора по нет айди';
                if ($response['sensor_id'] === false) {
                    $title = 'Узел связи С прочее networkID ' . $network_id;
                    $response = self::createSensorDatabase($title, $network_id, $mine_id, 105, 1, 10);
                    if ($response['status'] == 1) {
                        $warnings[] = $response['warnings'];
                        $sensor_id = $response['sensor_id'];
                        $warnings[] = 'saveHeartbeatPacket. Успешно закончил создание сенсора ' . $response['sensor_id'] . ' в кеше и БД';
                    } else {
                        $errors[] = $response['errors'];
                        $warnings[] = $response['warnings'];
                        throw new \Exception("saveHeartbeatPacket. Ошибка создания сенсора по нетворк айди $network_id в кеше и в БД");
                    }
                }
                $sensor_id = $response['sensor_id'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception("saveHeartbeatPacket. Ошибка при инициализации сенсора по сетевому адресу: " . $network_id);
            }
            $warnings[] = __FUNCTION__ . '. Получаем из кеша сенсор айди ' . $sensor_id . ' по нетворк айди ' . $network_id . ' = ' . (microtime(true) - $start);

            /** Отладка */
            $description = 'Найден сенсорв айди по нет айди и проинициализирован сенсор в кеше';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            /**=================================================================
             * Инициализация кеша сенсора SensorMine
             * ==================================================================*/
            $start = microtime(true);
            if ((new SensorCacheController())->getSensorMineBySensorHash($sensor_id) === false) {
                $response = SensorMainController::initSensorInCache($sensor_id);
                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new \Exception('saveLocationPacketSensorParameters. Ошибка при инициализации сенсора');
                }
            }
            $warnings[] = __FUNCTION__ . '. Инициализация кеша сенсора SensorMine = ' . (microtime(true) - $start);

            /**=================================================================
             * Сохранение параметров
             * ==================================================================*/
            $start = microtime(true);
            $warnings[] = 'saveHeartbeatPacket. Сохранение параметров';
            $sensor_cache_controller = new SensorCacheController();
            /**
             * получаем за раз все последние значения по сенсору из кеша
             */
            $sensor_parameter_value_list_cache = $sensor_cache_controller->multiGetParameterValueHash($sensor_id, '*', '*');

            if ($sensor_parameter_value_list_cache !== false) {
                foreach ($sensor_parameter_value_list_cache as $sensor_parameter_value_cache) {
                    $sensor_parameter_value_cache_array[$sensor_parameter_value_cache['sensor_id']][$sensor_parameter_value_cache['parameter_type_id']][$sensor_parameter_value_cache['parameter_id']] = $sensor_parameter_value_cache;
                }
            } else {
                $sensor_parameter_value_cache_array = null;
            }
            $warnings[__FUNCTION__ . '. получаем за раз все последние значения по сенсору из кеша'] = microtime(true) - $start;

            /** Отладка */
            $description = 'получил за раз все последние значения по сенсору из кеша';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $start = microtime(true);
            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::VOLTAGE, $pack->batteryVoltage, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::VOLTAGE);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::ROUTING_PARENT_ID, $pack->routingParentNode->address, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::ROUTING_PARENT_ID);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::RSSI_TO_ROUTING_PARENT, $pack->routingParentNode->rssi, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::RSSI_TO_ROUTING_PARENT);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::ROUTING_GATEWAY_ID, $pack->routingRootNodeAddress, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::ROUTING_GATEWAY_ID);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::HOPS_TO_ROUTING_GATEWAY, $pack->routingRootHops, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::HOPS_TO_ROUTING_GATEWAY);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::TIMING_PARENT_ID, $pack->timingParentNode->address, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::TIMING_PARENT_ID);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::RSSI_TO_TIMING_PARENT, $pack->timingParentNode->rssi, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::RSSI_TO_TIMING_PARENT);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::TIMING_GATEWAY_ID, $pack->timingRootNodeAddress, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::TIMING_GATEWAY_ID);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::HOPS_TO_TIMING_GATEWAY, $pack->timingRootHops, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::HOPS_TO_TIMING_GATEWAY);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::NEIGHBOUR_TABLE_FULL, $pack->neighborTableFull, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::NEIGHBOUR_TABLE_FULL);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::NEIGHBOUR_COUNT, $pack->neighborCount, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::NEIGHBOUR_COUNT);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::TIMING_PARENT_LOST, $pack->lostTimingParent, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::TIMING_PARENT_LOST);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::ROUTING_PARENT_LOST, $pack->lostRoutingParent, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::ROUTING_PARENT_LOST);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::TIMING_PARENT_CHANGED, $pack->timingChangeParents, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::TIMING_PARENT_CHANGED);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::ROUTING_PARENT_CHANGED, $pack->routingChangeParents, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::ROUTING_PARENT_CHANGED);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::ROUTING_PARENT_ABOVE_RSSI, $pack->routingAboveThresh, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::ROUTING_PARENT_ABOVE_RSSI);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::TIMING_PARENT_ABOVE_RSSI, $pack->timingAboveThresh, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::TIMING_PARENT_ABOVE_RSSI);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::QUEUE_OVERFLOW_COUNT, $pack->queueOverflow, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::QUEUE_OVERFLOW_COUNT);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::NET_ENTRY_COUNT, $pack->netEntryCount, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::NET_ENTRY_COUNT);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::MIN_NUMBER_IDLE_SLOTS, $pack->minNumberIdleSlots, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::MIN_NUMBER_IDLE_SLOTS);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::LISTEN_DURING_TRANSMIT, $pack->listenDuringTransmit, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::LISTEN_DURING_TRANSMIT);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::NET_ENTRY_REASON, $pack->netEntryReason, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::NET_ENTRY_REASON);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::GRANDPARENT_BLOCKED, $pack->grandparentBlocked, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::GRANDPARENT_BLOCKED);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::PARENT_TIMEOUT_EXPIRED, $pack->parentTimeoutExpired, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::PARENT_TIMEOUT_EXPIRED);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::CYCLE_DETECTION, $pack->cycleDetection, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::CYCLE_DETECTION);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::NO_IDLE_SLOTS, $pack->noIdleSlots, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::NO_IDLE_SLOTS);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::CC1110_VERSION, $pack->CC1110, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::CC1110_VERSION);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::PIC_VERSION, $pack->PIC, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::PIC_VERSION);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::NUMBERS_OF_HEARTBEAT, $pack->numberOfHeartbeats, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::NUMBERS_OF_HEARTBEAT);
            }
            $warnings[__FUNCTION__ . '. Генерация структур для вставки'] = microtime(true) - $start;

            /**=================================================================
             * Сохранение параметра процента заряда батареи узла связи
             * ==================================================================*/
            $cordPowered = (new Query)//Запрос для проверки принадлежности датчика к узлам, запитанным от сети
            ->select('sensor_id')
                ->from('view_sensor_a_c_xp')
                ->where('sensor_id = ' . $sensor_id)
                ->limit(1)
                ->one();

            $batteryPercent = self::getCommnodeBatteryPercent(str_replace(',', '.', $pack->batteryVoltage));//вычисляем процент заряда батареи узла

            if ($batteryPercent <= 10 && !$cordPowered) {
                $value_status_id = StatusEnumController::EMERGENCY_VALUE;
                $event_status_id = StatusEnumController::EVENT_RECEIVED;
                $state_value = 2;
            } else {
                $value_status_id = StatusEnumController::NORMAL_VALUE;
                $event_status_id = StatusEnumController::EVENT_ELIMINATED_BY_SYSTEM;
                $state_value = 1;
            }

            //$warnings[] = 'saveHeartbeatPacket. Сохранение заряда батареи';
            $response = self::saveSensorParameterBatch(
                $sensor_id,
                ParameterTypeEnumController::MEASURED, ParamEnum::COMMNODE_BATTERY_PERCENT,
                $batteryPercent,
                $pack->timestamp,
                StatusEnumController::ACTUAL,
                $sensor_parameter_value_cache_array
            );
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::COMMNODE_BATTERY_PERCENT);
            }

            //Сохранение состояния узла
            $start = microtime(true);
            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::CALCULATED, ParamEnum::STATE, $state_value, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveHeartbeatPacket. Ошибка сохранения параметра ' . ParamEnum::STATE);
            }
            $warnings[] = __FUNCTION__ . '. Сохранение состояния узла = ' . (microtime(true) - $start);

            /**=================================================================
             * блок массовой вставки значений в БД
             * =================================================================*/
            $start = microtime(true);
            if (isset($date_to_db)) {
//                Yii::$app->db_amicum2->createCommand()->batchInsert('sensor_parameter_value',
//                    ['sensor_parameter_id', 'date_time', 'value', 'status_id'],
//                    $date_to_db)->execute();
                $insert_param_val = Yii::$app->db_amicum2->queryBuilder->batchInsert('sensor_parameter_value', ['sensor_parameter_id', 'date_time', 'value', 'status_id'], $date_to_db);
                Yii::$app->db_amicum2->createCommand($insert_param_val)->execute();
//                Yii::$app->db_amicum2->createCommand($insert_param_val . " ON DUPLICATE KEY UPDATE `value` = VALUES (`value`), `status_id` = VALUES (`status_id`)")->execute();
            }
            $warnings[] = __FUNCTION__ . '. блок массовой вставки значений в БД = ' . (microtime(true) - $start);

            /**
             * блок массовой вставки значений в кеш
             */
            $start = microtime(true);
            if (isset($date_to_cache)) {
                //$warnings[] = $sensor_id;
                $ask_from_method = (new SensorCacheController)->multiSetSensorParameterValueHash($date_to_cache);
                if ($ask_from_method['status'] == 1) {
                    //$warnings[] = $ask_from_method['warnings'];
                    //$warnings[] = 'saveHeartbeatPacket. обновил параметры сенсора в кеше';
                } else {
                    //$warnings[] = $ask_from_method['warnings'];
                    $errors[] = $ask_from_method['errors'];
                    throw new \Exception('saveHeartbeatPacket. Не смог обновить параметры в кеше сенсора ' . $sensor_id);
                }
            }
            $warnings[] = __FUNCTION__ . '. блок массовой вставки значений в кэш = ' . (microtime(true) - $start);

            /**=================================================================
             * Генерация события о низком заряде батареи узла связи
             * =================================================================*/
            $response = EventMainController::createEventFor('sensor', $sensor_id, EventEnumController::LOW_BATTERY, $batteryPercent,
                $pack->timestamp, $value_status_id, ParamEnum::COMMNODE_BATTERY_PERCENT, $mine_id,
                $event_status_id);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            }

        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'saveHeartbeatPacket. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $errors['Method parameters'] = [
                'pack' => $pack,
                'mine_id' => $mine_id
            ];
            $data_to_log_cache = array('Items' => $result, 'status' => $status,
                'errors' => $errors, 'warnings' => $warnings, 'LogAmicum::LogEventStrata' => $response);
            LogCacheController::setStrataLogValue('saveHeartbeatPacket', $data_to_log_cache, '2');
            LogAmicum::LogAmicumStrata("saveHeartbeatPacket", $pack, $warnings, $errors);
        }
        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        //$warnings[] = 'saveHeartbeatPacket. Закончил метод';
        return array('Items' => '', 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
    }

    /**
     * Метод для вычисления процента заряда батареи узла связи
     * @param double $batteryValue Значение напряжения батареи
     * @return int  Процент заряда батареи
     */
    public static function getCommnodeBatteryPercent($batteryValue)
    {
        if ($batteryValue >= 6) return 100;
        if ($batteryValue >= 5.8) return 90;
        if ($batteryValue >= 5.7) return 80;
        if ($batteryValue >= 5.6) return 70;
        if ($batteryValue >= 5.4) return 60;
        if ($batteryValue >= 5.3) return 50;
        if ($batteryValue >= 5.1) return 40;
        if ($batteryValue >= 5) return 30;
        if ($batteryValue >= 4.7) return 20;
        if ($batteryValue >= 4.5) return 10;
        if ($batteryValue === null) return -1;
        return 0;
    }

    /**
     * Функция проверки актуальности данных в кеше по значению параметров воркеров
     * Данные считаются актуальными, если они не изменились в течении 5 минут
     * @param $worker_id -   id воркера
     * @param $parameter_id -   идентификатор параметра
     * @param $parameter_type_id -   идентификатор типа параметра
     * @param $value -   новое значение параметра, которые будет сравниваться с тем, что лежит в кеше
     * @return bool                         -   true, если данные актуальные, false в другом случае
     */
    public static function isWorkerDataUpdate($worker_id, $parameter_id, $parameter_type_id, $value)
    {
        $old_parameter_value = WorkerMainController::getWorkerParameterLastValue($worker_id, $parameter_id, $parameter_type_id);

        if ($old_parameter_value === false) {
            return false;
        }

        $old_time_date = explode('.', $old_parameter_value['date_time']);                                      // разбиваем строку (разделяем время от микросекунд)
        $old_time = strtotime($old_time_date[0]);                                                                   // берем время без микросекунд

        $currentTime = strtotime(date('Y-m-d H:i:s'));                                                       // сохраняем в переменную текущее время
        $dTime = abs($currentTime - $old_time);
        return $old_parameter_value['value'] == $value && $dTime < 300;
    }

    /**
     * Функция для проверки актуальности значения состояния узлов связи.
     * Данные считаются актуальными, если они обновлялись за последние 11 минут.
     * В случае, если данные не акутальны, происходит запись значения 0
     */
    // пример: 127.0.0.1/admin/strata-job/node-list-update
    // Якимов М.Н.
    /**
     * Функция для сохранения параметра воркера в кеш и БД без проверки актуальности.
     * Таким образом данные будут сохраняться в любом случае
     * @param int $worker_id идентификатор работника
     * @param int $parameter_type_id идентификатор типа параметра
     * @param int $parameter_id идентификатор параметра
     * @param $value -   новое значение, которое будет записываться
     * @param $datetime -   метка времени, записываемого значения
     * @param $status_id -   статус записываемого значения
     * @return array
     */
    public static function saveWorkerParameterForce($worker_id, $parameter_type_id, $parameter_id, $value, $datetime, $status_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();
        $value_database_id = -1;
        try {
            /**=================================================================
             * Получение идентификатор параметра воркера из таблицы worker_parameter
             * ==================================================================*/
            $response = WorkerMainController::getOrSetWorkerParameter($worker_id, $parameter_id, $parameter_type_id);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $worker_parameter_id = $response['worker_parameter_id'];
            } else {
                $errors[] = $response['errors'];
                //$warnings[] = $response['warnings'];
                throw new \Exception("saveWorkerParameterForce. Ошибка при получении параметра $parameter_id-$parameter_type_id
                    воркера " . $worker_id);
            }

            /**=================================================================
             * Сохранение значения в БД
             * ==================================================================*/
            $shift_info = self::getShiftDateNum($datetime);
            $value_database_id = WorkerBasicController::addWorkerParameterValue($worker_parameter_id, $value, $shift_info['shift_num'], $status_id, $datetime, $shift_info['shift_date']);
            if (is_array($value_database_id)) {
                $errors[] = $value_database_id;
                throw new \Exception("saveWorkerParameterForce. Ошибка при сохранении значения в БД параметра $parameter_id-$parameter_type_id
                    воркера " . $worker_id);
            }

            /**=================================================================
             * Сохранение значения в кеш
             * ==================================================================*/
            $response = (new WorkerCacheController())->setWorkerParameterValueHash(
                $worker_id,
                $worker_parameter_id,
                $parameter_id,
                $parameter_type_id,
                $datetime,
                $value,
                $status_id
            );
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            } else {
                $errors[] = $response['errors'];
                //$warnings[] = $response['warnings'];
                throw new \Exception('saveWorkerParameterForce. Значение воркера ' . $worker_id . ' не сохранено в кеш.
                        Параметр ' . $parameter_id);
            }

            /**=============================================================
             * Сохранение значения в таблицу временного хранения
             * worker_parameter_value_temp для формирования отчётных данных
             * ==============================================================*/
            /*$value_database_id = WorkerBasicController::addWorkerParameterValueTemp($worker_parameter_id, $value, $shift_info['shift_num'], $status_id, $datetime, $shift_info['shift_date']);
            if (is_array($value_database_id)) {
                $errors[] = $value_database_id;
                throw new \Exception("saveWorkerParameter. Ошибка при сохранении значения в БД параметра $parameter_id-$parameter_type_id
                    воркера " . $worker_id . ' в таблицу worker_parameter_value_temp');
            }*/
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'saveWorkerParameterForce. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }
        $warnings = 'saveWorkerParameterForce. Закончил метод ' . $parameter_id;
        return array('Items' => $result, 'status' => $status, 'errors' => $errors,
            'warnings' => $warnings, 'worker_parameter_value_id' => $value_database_id);
    }

    public static function saveWorkerParameterIfChange($worker_sensor, $parameter_type_id, $parameter_id, $value, $datetime, $status_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();
        $value_database_id = -1;
        try {

            if (!self::isWorkerDataUpdateNoTime($worker_sensor['worker_id'], $parameter_id, $parameter_type_id, $value)) {                 //если в кеше лежат неактуальные данные
                /**=============================================================
                 * Получение идентификатор параметра воркера из таблицы worker_parameter
                 * ==============================================================*/
                $response = WorkerMainController::getOrSetWorkerParameter($worker_sensor['worker_id'], $parameter_id, $parameter_type_id);
                if ($response['status'] == 1) {
                    //$warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    $worker_parameter_id = $response['worker_parameter_id'];
                } else {
                    $errors[] = $response['errors'];
                    //$warnings[] = $response['warnings'];
                    throw new \Exception("saveWorkerParameterIfChange. Ошибка при получении параметра $parameter_id-$parameter_type_id
                    воркера " . $worker_sensor['worker_id']);
                }

                /**=============================================================
                 * Сохранение значения в БД
                 * ==============================================================*/
                $shift_info = self::getShiftDateNum($datetime);
                $value_database_id = WorkerBasicController::addWorkerParameterValue($worker_parameter_id, $value, $shift_info['shift_num'], $status_id, $datetime, $shift_info['shift_date']);
                if (is_array($value_database_id)) {
                    $errors[] = $value_database_id;
                    throw new \Exception("saveWorkerParameterIfChange. Ошибка при сохранении значения в БД параметра $parameter_id-$parameter_type_id
                    воркера " . $worker_sensor['worker_id']);
                }

                /**=============================================================
                 * Сохранение значения в кеш
                 * ==============================================================*/
                $response = (new WorkerCacheController())->setWorkerParameterValueHash(
                    $worker_sensor['worker_id'],
                    $worker_parameter_id,
                    $parameter_id,
                    $parameter_type_id,
                    $datetime,
                    $value,
                    $status_id
                );
                if ($response['status'] == 1) {
                    //$warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                } else {
                    $errors[] = $response['errors'];
                    //$warnings[] = $response['warnings'];
                    throw new \Exception('saveWorkerParameterIfChange. Значение воркера ' . $worker_sensor['worker_id'] . ' не сохранено в кеш.
                        Параметр ' . $parameter_id);
                }

                /**=============================================================
                 * Сохранение значения в таблицу временного хранения
                 * worker_parameter_value_temp для формирования отчётных данных
                 * ==============================================================*/
                /*$value_database_id = WorkerBasicController::addWorkerParameterValueTemp($worker_parameter_id, $value, $shift_info['shift_num'], $status_id, $datetime, $shift_info['shift_date']);
                if (is_array($value_database_id)) {
                    $errors[] = $value_database_id;
                    throw new \Exception("saveWorkerParameter. Ошибка при сохранении значения в БД параметра $parameter_id-$parameter_type_id
                    воркера " . $worker_sensor['worker_id'] . ' в таблицу worker_parameter_value_temp');
                }*/
            } else {
                //$warnings[] = 'saveWorkerParameterIfChange. Значение параметра актулаьно';
            }
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'saveWorkerParameterIfChange. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }
        $warnings = 'saveWorkerParameterIfChange. Закончил метод';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors,
            'warnings' => $warnings, 'worker_parameter_value_id' => $value_database_id);
    }

    public static function isWorkerDataUpdateNoTime($worker_id, $parameter_id, $parameter_type_id, $value)
    {
        $old_parameter_value = WorkerMainController::getWorkerParameterLastValue($worker_id, $parameter_id, $parameter_type_id);
        if ($old_parameter_value === false) {
            return false;
        }
        return $old_parameter_value['value'] == $value;
    }

    /**
     * saveSensorParameterForce - Функция для сохранения параметра датчика в кеш и БД без проверки актуальности.
     * Таким образом данные будут сохраняться в любом случае
     * @param $sensor_id -   идентификатор сенсора
     * @param $typeParameterParameterId -   связка типа параметра и его id (например 2-269)
     * @param $value -   новое значение, которое будет записываться
     * @param $datetime -   метка времени, записываемого значения
     * @param $status_id -   статус записываемого значения
     * @return array
     */
    public static function saveSensorParameterForce($sensor_id, $parameter_type_id, $parameter_id, $value, $datetime, $status_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();
        $value_database_id = -1;
        try {
            /**
             * Получение идентификатора параметра сенсора (из таблицы sensor_parameter)
             */
            $response = SensorMainController::GetOrSetSensorParameter($sensor_id, $parameter_id, $parameter_type_id);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $sensor_parameter_id = $response['sensor_parameter_id'];
            } else {
                $errors[] = $response['errors'];
                //$warnings[] = $response['warnings'];
                throw new \Exception("saveSensorParameterForce. Ошибка при получении параметра $parameter_id-$parameter_type_id 
                    сенсора $sensor_id");
            }

            /**
             * Сохранение значения параметра в БД
             */
            $response = SensorBasicController::addSensorParameterValue($sensor_parameter_id, $value, $status_id, $datetime);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $value_database_id = $response['sensor_parameter_value_id'];
            } else {
                $errors[] = $response['errors'];
                //$warnings[] = $response['warnings'];
                throw new \Exception("saveSensorParameterForce. Ошибка при сохранении значения в БД 
                    параметра $parameter_id-$parameter_type_id сенсора $sensor_id");
            }

            /**
             * Сохранение значения параметра в кеш
             */
            $response = (new SensorCacheController())
                ->setSensorParameterValueHash(
                    $sensor_id,
                    $sensor_parameter_id,
                    $value,
                    $parameter_id,
                    $parameter_type_id,
                    $status_id,
                    $datetime
                );
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                //$warnings[] = 'saveSensorParameterForce. Сохранил значение в кеш';
            } else {
                $errors[] = $response['errors'];
                //$warnings[] = $response['warnings'];
                throw new \Exception('saveSensorParameterForce. Значение сенсора ' . $sensor_id . ' не сохранено в кеш.
                        Параметр ' . $parameter_id);
            }
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'saveSensorParameterForce. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }
        $warnings = 'saveSensorParameterForce. Закончил метод';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors,
            'warnings' => $warnings, 'sensor_parameter_value_id' => $value_database_id);
    }

    /**
     * функция проверки актуальности данных в кеше по значению параметров датчиков.
     * Данные являются актуальными, если значение параметра не изменилось и
     * время записи параметра меньше 5 минут назад.
     * @param $sensor_id -   id датчика
     * @param $parameter_id -   идентификатор параметра
     * @param $parameter_type_id -   идентификатор типа параметра
     * @param $value -   новое значение параметра, которые будет сравниваться с тем, что лежит в кеше
     * @return bool                         -   true, если данные актуальные, false в другом случае
     */
    public static function isSensorDataUpdate($sensor_id, $parameter_id, $parameter_type_id, $value)
    {
        $old_parameter_value = (new SensorCacheController())
            ->getParameterValueHash($sensor_id, $parameter_id, $parameter_type_id);

        if ($old_parameter_value === false) {
            return false;
        }
        $old_time_date = explode('.', $old_parameter_value['date_time']);       // разбиваем строку (разделяем время от микросекунд)
        $old_time = strtotime($old_time_date[0]);

        $currentTime = strtotime(date('Y-m-d H:i:s'));
//        $dTime = $currentTime - $old_time;
        $dTime = abs($currentTime - $old_time);

        return $old_parameter_value['value'] == $value && $dTime < 300;
    }

    /**
     * Название метода: AddMessage()
     * AddMessage - Метод добавления сообщений в кеш и в БД по работникам
     * @param $workersID - массив работников которым нужно отправить данное сообщение
     * @param $text - текст сообщения
     * @param $type - тип сообщения
     * @param $sender - отправитель
     * @return array
     *
     * Метод вызывается когда нужно записать сообщение от диспетчера(т.е. со схемы)
     * @package backend\controllers
     *
     * @see
     * @example
     *
     * @author Якимов М.Н.
     * Created date: on 21.05.2019 17:10
     * @since ver
     */
    public static function AddMessage($workersID, $text, $type, $sender)
    {
        $result = array();
        $worker_dictionary = array();
        $status = 1;
        $errors = array();
        $warnings = array();
        try {
            if ($workersID == 'broadcast') {
                /**
                 * Получение списка воркеров в шахте из кэша
                 */
                $workers = (new WorkerCacheController())->getWorkerMineHash(AMICUM_DEFAULT_MINE);
                if ($workers === false) {
                    $workersID = [];
                } else {
                    $workers_ids = array();
                    foreach ($workers as $worker) {
                        $workers_ids[] = $worker['worker_id'];
                    }
                    $workersID = $workers_ids;
                    unset($workers_ids);
                }
                unset($workers);
            }

            //$warnings[] = 'AddMessage. Начало выполнения метода';
            $cache = Yii::$app->redis_service;                                                                                  //используем в качетсве кеша именно редис
            // поиск привязки светильников к работникам
            $sensors = ViewWorkerSensorMaxDateFullInfo::find()
                ->where(['worker_id' => $workersID])
                ->limit(500)
                ->asArray()
                ->all();  //найти датчики с соответствующими networkId для нужных нам воркеров
            // создание словаря по работникам - светильникам
            foreach ($sensors as $sensor) {
                $worker_dictionary[$sensor['worker_id']]['sensor_id'] = $sensor['sensor_id'];
                $worker_dictionary[$sensor['worker_id']]['worker_id'] = $sensor['worker_id'];
                $worker_dictionary[$sensor['worker_id']]['network_id'] = $sensor['network_id'];
            }
            //$warnings[] = 'AddMessage. Словарь работников ';
            //$warnings[] = $worker_dictionary;

            /**
             * блок получения привязки светильника работника отправителя, если таковой существовал
             */
            $sender_sensor = ViewWorkerSensorMaxDateFullInfo::find()->where(['worker_id' => $sender])->limit(500)->one();
            /**
             * Блок сохранения данных в БД
             */
            //$warnings[] = 'AddMessage. Начал сохранение сообщений в БД';

            foreach ($workersID as $worker_id) {                                                                               //начинаем перебирать каждый сенсор
                // Условие сделано для корректного подтверждения получения сообщения,
                // т.к. на аварийных сообщениях должен быть id = 205
                // Без него сообщение отправлялось с id 205, но в базе хранилось с другим
                // Возможны косяки при отправлении одному человеку нескольких аварийных сообщений
                if ($type === 'alarm') {
                    $message_id = 205;
                } else {
                    $message_id = $cache->incr('sequenceNumber');                                                           //получаем идентификатор последнего сообщения из кеша и прибавляем ему +1
                    if ($message_id == 199)                                                                                  //если он равен 255 то устанавлваем ему значение 0
                    {
                        $cache->set('sequenceNumber', 0);
                        $message_id = $cache->get('sequenceNumber');
                    }
                }

                $text_message = new TextMessage();                                                                      //создаем модель сообщения
                $text_message->status_id = StatusEnumController::MSG_SENDED;
                $text_message->message_id = $message_id;
                $text_message->message_type = $type;
                $text_message->message = $text;
                $text_message->datetime = date('Y-m-d H:i:s');
                // делаем проверку наличия привязки светильника у принимающей стороны, если привязки нет, то фиксируем попытку отправки сообщения отправителем
                if (array_key_exists($worker_id, $worker_dictionary)) {
                    $text_message->reciever_network_id = $worker_dictionary[$worker_id]['network_id'];
                    $text_message->reciever_sensor_id = $worker_dictionary[$worker_id]['sensor_id'];
                    //$warnings[] = 'AddMessage. с привязкой работник ' .$worker_id;
                    $sensor_send_array[$worker_dictionary[$worker_id]['sensor_id']] = $message_id;
                } else {
                    $text_message->reciever_network_id = '-';
                    $text_message->reciever_sensor_id = null;
                    //$warnings[] = 'AddMessage. без привязки работник ' .$worker_id;
                }
                $text_message->reciever_worker_id = $worker_id;
                //если существует привязка светильника у отправителя, то пишем ее, иначе просто поле
                //$warnings[] = 'AddMessage. отправитель ' .$sender;
                if ($sender_sensor) {
                    $text_message->sender_sensor_id = $sender_sensor['sensor_id'];
                    //$warnings[] = 'AddMessage. Светильник отправителя ' .$sender_sensor['sensor_id'];
                } else {
                    $text_message->sender_sensor_id = null;
                    //$warnings[] = 'AddMessage. У отправителя нет светильника';
                }
                $text_message->sender_worker_id = $sender;
                $text_message->sender_network_id = 'surface';
                if ($text_message->save()) {
                    //$warnings[] = 'AddMessage. Сохранил  сообщение в БД для сенсора ';
                    //$warnings[] = $sensor;
                } else {
                    $errors[] = $text_message->errors;
                    throw new \Exception('AddMessage. Не смог сохранить данные по сообщению в таблицу text_message');
                }

                /**
                 * Сохранение параметра "Флаг текстового сообщения для воркера"
                 * Значением параметра является статус текстового сообщения
                 */
                self::saveWorkerParameter($worker_id, ParameterTypeEnumController::MEASURED, ParamEnum::TEXT_MSG_FLAG,
                    StatusEnumController::MSG_SENDED, $text_message->datetime, StatusEnumController::ACTUAL);

                /**
                 * Сохранение параметра "Флаг сигнал об аварии"
                 */
                if ($type === 'alarm') {
                    self::saveWorkerParameter($worker_id, ParameterTypeEnumController::MEASURED, ParamEnum::ALARM_SIGNAL_FLAG,
                        StatusEnumController::ALARM_SENDED, $text_message->datetime, StatusEnumController::ACTUAL);
                }
            }

            /**
             * Блок сохранения сообщений в кеш для отправки в службу сбора/отправки данных ССД Strata
             */
            if ($sensors) {
                foreach ($sensors as $sensor) {                                                                               //начинаем перебирать каждый сенсор
                    $message_id = $sensor_send_array[$sensor['sensor_id']];                                                    //получаем идентификатор последнего сообщения из кеша и прибавляем ему +1

                    $temp_cache = array();                                                                                   //формируем массив для сохранения сообщения в кеш
                    $temp_cache['network_id'] = $sensor['network_id'];
                    $temp_cache['message'] = self::encodeTextToStrataBytes($text); // Нужно для правильной работы с кириллицей
                    $temp_cache['message_id'] = $message_id;
                    $temp_cache['type'] = $type;
                    $cache->rpush('packages', json_encode($temp_cache));                                                 //записываем в кеш наше сообщение
                    //$warnings[] = 'AddMessage. Сохранил  сообщение в кеш для сенсора ';
                    //$warnings[] = $sensor;
                }
            } else {
                //$warnings[] = 'AddMessage. У выбранных работников нет ни одной привязанной лампы';
                //$warnings[] = $workersID;
            }
        } catch (Throwable $ex)                                                                                          //если ошибка отдаем ошибку
        {
            $errors[] = 'AddMessage. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        //$warnings[] = 'AddMessage. Закончил метод';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Переводит строку в байты, понятные светильникам Strata
     * @param $text -   Строка, содержащая сообщение
     * @return array    -   Строка байтов
     */
    public static function encodeTextToStrataBytes($text)
    {
        $bytes = [
            '0' => 0x30, '1' => 0x31, '2' => 0x32, '3' => 0x33, '4' => 0x34, '5' => 0x35,
            '6' => 0x36, '7' => 0x37, '8' => 0x38, '9' => 0x39, '.' => 0x2e, ',' => 0x2c,
            '!' => 0x21, '?' => 0x3f, ' ' => 0x20, '-' => 0x2d, '(' => 0x28, ')' => 0x29,
            'А' => 0x80, 'Б' => 0x81, 'В' => 0x82, 'Г' => 0x83, 'Д' => 0x84, 'Е' => 0x85,
            'Ё' => 0xf0, 'Ж' => 0x86, 'З' => 0x87, 'И' => 0x88, 'Й' => 0x89, 'К' => 0x8a,
            'Л' => 0x8b, 'М' => 0x8c, 'Н' => 0x8d, 'О' => 0x8e, 'П' => 0x8f, 'Р' => 0x90,
            'С' => 0x91, 'Т' => 0x92, 'У' => 0x93, 'Ф' => 0x94, 'Х' => 0x95, 'Ц' => 0x96,
            'Ч' => 0x97, 'Ш' => 0x98, 'Щ' => 0x99, 'Ъ' => 0x9a, 'Ы' => 0x9b, 'Ь' => 0x9c,
            'Э' => 0x9d, 'Ю' => 0x9e, 'Я' => 0x9f, 'а' => 0xa0, 'б' => 0xa1, 'в' => 0xa2,
            'г' => 0xa3, 'д' => 0xa4, 'е' => 0xa5, 'ё' => 0xf1, 'ж' => 0xa6, 'з' => 0xa7,
            'и' => 0xa8, 'й' => 0xa9, 'к' => 0xaa, 'л' => 0xab, 'м' => 0xac, 'н' => 0xad,
            'о' => 0xae, 'п' => 0xaf, 'р' => 0xe0, 'с' => 0xe1, 'т' => 0xe2, 'у' => 0xe3,
            'ф' => 0xe4, 'х' => 0xe5, 'ц' => 0xe6, 'ч' => 0xe7, 'ш' => 0xe8, 'щ' => 0xe9,
            'ъ' => 0xea, 'ы' => 0xeb, 'ь' => 0xec, 'э' => 0xed, 'ю' => 0xee, 'я' => 0xef,
            'A' => 0x41, 'B' => 0x42, 'C' => 0x43, 'D' => 0x44, 'E' => 0x45, 'F' => 0x46,
            'G' => 0x47, 'H' => 0x48, 'I' => 0x49, 'J' => 0x4a, 'K' => 0x4b, 'L' => 0x4c,
            'M' => 0x4d, 'N' => 0x4e, 'O' => 0x4f, 'P' => 0x50, 'Q' => 0x51, 'R' => 0x52,
            'S' => 0x53, 'T' => 0x54, 'U' => 0x55, 'V' => 0x56, 'W' => 0x57, 'X' => 0x58,
            'Y' => 0x59, 'Z' => 0x5a, 'a' => 0x61, 'b' => 0x62, 'c' => 0x63, 'd' => 0x64,
            'e' => 0x65, 'f' => 0x66, 'g' => 0x67, 'h' => 0x68, 'i' => 0x69, 'j' => 0x6a,
            'k' => 0x6b, 'l' => 0x6c, 'm' => 0x6d, 'n' => 0x6e, 'o' => 0x6f, 'p' => 0x70,
            'q' => 0x71, 'r' => 0x72, 's' => 0x73, 't' => 0x74, 'u' => 0x75, 'v' => 0x76,
            'w' => 0x77, 'x' => 0x78, 'y' => 0x79, 'z' => 0x7a, '%' => 0x25
        ];
        mb_internal_encoding('UTF-8');
        $len = mb_strlen($text);
        $bytesText = array();
        //echo $len;
        for ($i = 0; $i < $len; $i++) {
            $sym = mb_substr($text, $i, 1);
            $bytesText[$i] = $bytes[$sym];
            //echo $bytesText[$i] . ' ';
        }
        return $bytesText;
    }

    /**
     * Название метода: AddMessageSensor()
     * Метод добавления сообщений в кеш и в БД по сенсорам
     * @param $sensorsID - массив работников которым нужно отправить данное сообщение
     * @param $text - текст сообщения
     * @param $type - тип сообщения
     * @param $sender - отправитель
     * @return array
     *
     * Метод вызывается когда нужно записать сообщение от диспетчера(т.е. со схемы)
     * @package backend\controllers
     *
     * @see
     * @example
     *
     * @author Якимов М.Н.
     * Created date: on 21.05.2019 17:10
     * @since ver
     */
    public static function AddMessageSensor($sensorsID, $text, $type, $sender)
    {
        $result = array();
        $status = 1;
        $errors = array();
        $warnings = array();
        try {
            $warnings[] = 'AddMessage. Начало выполнения метода';

            // получаем все net_id всех сенсоров параметр 88 тип параметра (1)
            $sensor_cache_controller = (new SensorCacheController());
            $sensor_net_ids = $sensor_cache_controller->multiGetParameterValueHash('*', 88, 1);

            // проверка на существование кеша параметров 88 тип 1
            if (!$sensor_net_ids) {
                throw new \Exception('AddMessageSensor. В кеше параметров сенсора нет параметров сенсора 88 тип 1');
            }
            foreach ($sensor_net_ids as $sensor_net_id) {
                $sensor_cache_88[$sensor_net_id['sensor_id']] = $sensor_net_id['value'];
            }

//            $warnings[] = $sensorsID;
//            $warnings[] = $sensor_cache_88;
            $cache = Yii::$app->redis_service;                                                                                  //используем в качетсве кеша именно редис

            // создание словаря по работникам - светильникам
            foreach ($sensorsID as $sensor) {
                if (isset($sensor_cache_88[$sensor])) {
                    $sensor_dictionary[$sensor]['sensor_id'] = $sensor;
                    $sensor_dictionary[$sensor]['worker_id'] = null;
                    $sensor_dictionary[$sensor]['network_id'] = $sensor_cache_88[$sensor];
                }
            }

            // проверка на существование net_id у искомых светильников
            if (!isset($sensor_dictionary)) {
                throw new \Exception('AddMessageSensor. У сенсоров нет привязанных net_id');
            }
            $warnings[] = 'AddMessage. Словарь работников ';
            $warnings[] = $sensor_dictionary;

            /**
             * блок получения привязки светильника работника отправителя, если таковой существовал
             */
            $sender_sensor = ViewWorkerSensorMaxDateFullInfo::find()->where(['worker_id' => $sender])->limit(500)->one();
            /**
             * Блок сохранения данных в БД
             */
            //$warnings[] = 'AddMessage. Начал сохранение сообщений в БД';

            foreach ($sensorsID as $sensor_id) {                                                                               //начинаем перебирать каждый сенсор
                // Условие сделано для корректного подтверждения получения сообщения,
                // т.к. на аварийных сообщениях должен быть id = 205
                // Без него сообщение отправлялось с id 205, но в базе хранилось с другим
                // Возможны косяки при отправлении одному человеку нескольких аварийных сообщений
                if ($type === 'alarm') {
                    $message_id = 205;
                } else {
                    $message_id = $cache->incr('sequenceNumber');                                                           //получаем идентификатор последнего сообщения из кеша и прибавляем ему +1
                    if ($message_id == 199)                                                                                  //если он равен 255 то устанавлваем ему значение 0
                    {
                        $cache->set('sequenceNumber', 0);
                        $message_id = $cache->get('sequenceNumber');
                    }
                }

                $text_message = new TextMessage();                                                                      //создаем модель сообщения
                $text_message->status_id = StatusEnumController::MSG_SENDED;
                $text_message->message_id = $message_id;
                $text_message->message_type = $type;
                $text_message->message = $text;
                $text_message->datetime = date('Y-m-d H:i:s');
                // делаем проверку наличия привязки светильника у принимающей стороны, если привязки нет, то фиксируем попытку отправки сообщения отправителем
                if (array_key_exists($sensor_id, $sensor_dictionary)) {
                    $text_message->reciever_network_id = $sensor_dictionary[$sensor_id]['network_id'];
                    $text_message->reciever_sensor_id = $sensor_dictionary[$sensor_id]['sensor_id'];
                    //$warnings[] = 'AddMessage. с привязкой работник ' .$worker_id;
                    $sensor_send_array[$sensor_dictionary[$sensor_id]['sensor_id']] = $message_id;
                } else {
                    $text_message->reciever_network_id = '-';
                    $text_message->reciever_sensor_id = null;
                    //$warnings[] = 'AddMessage. без привязки работник ' .$worker_id;
                }
                $text_message->reciever_worker_id = null;
                //если существует привязка светильника у отправителя, то пишем ее, иначе просто поле
                //$warnings[] = 'AddMessage. отправитель ' .$sender;
                if ($sender_sensor) {
                    $text_message->sender_sensor_id = $sender_sensor['sensor_id'];
                    //$warnings[] = 'AddMessage. Светильник отправителя ' .$sender_sensor['sensor_id'];
                } else {
                    $text_message->sender_sensor_id = null;
                    //$warnings[] = 'AddMessage. У отправителя нет светильника';
                }
                $text_message->sender_worker_id = $sender;
                $text_message->sender_network_id = 'surface';
                if ($text_message->save()) {
                    //$warnings[] = 'AddMessage. Сохранил  сообщение в БД для сенсора ';
                    //$warnings[] = $sensor;
                } else {
                    $errors[] = $text_message->errors;
                    throw new \Exception('AddMessageSensor. Не смог сохранить данные по сообщению в таблицу text_message');
                }


            }

            /**
             * Блок сохранения сообщений в кеш для отправки в службу сбора/отправки данных ССД Strata
             */

            foreach ($sensor_dictionary as $sensor) {                                                                               //начинаем перебирать каждый сенсор
                $message_id = $sensor_send_array[$sensor['sensor_id']];                                                    //получаем идентификатор последнего сообщения из кеша и прибавляем ему +1

                $temp_cache = array();                                                                                   //формируем массив для сохранения сообщения в кеш
                $temp_cache['network_id'] = $sensor['network_id'];
                $temp_cache['message'] = self::encodeTextToStrataBytes($text); // Нужно для правильной работы с кириллицей
                $temp_cache['message_id'] = $message_id;
                $temp_cache['type'] = $type;
                $cache->rpush('packages', json_encode($temp_cache));                                                 //записываем в кеш наше сообщение
                $warnings[] = 'AddMessage. Сохранил  сообщение в кеш для сенсора ОТПРАКА В СТРАТУ';
                $warnings[] = $sensor;
            }

        } catch (Throwable $ex)                                                                                          //если ошибка отдаем ошибку
        {
            $errors[] = 'AddMessageSensor. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        //$warnings[] = 'AddMessage. Закончил метод';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }


    /**
     * checkExistenceEquipmentInEdge - Метод проверки наличие конвейера на конкретном выроботке
     * Входные данные:
     * edge_id - id выроботка
     * Выходные данные:
     * status - статус выполнение метода
     * warnings - массив возможных ошибок и результать выполнения метода
     * result - репзультат проверки. Если false то на выработке не существует конвейер и событие неверное
     * пример вызова: StrataJobController::checkExistenceEquipmentInEdge(141390);
     **/
    public static function checkExistenceEquipmentInEdge($edge_id)
    {
        $status = 1;
        $warnings = array();
        $result = false;
        try {
            if ($edge_id) {
                $warnings[] = 'checkExistenceEquipmentInEdge. Edge_id ' . $edge_id;
            } else {
                throw new \Exception('checkExistenceEquipmentInEdge. Не передан входной параметр edge_id');
            }
            $response = EquipmentParameterValue::find()
                ->select([
                        'equipment_parameter.equipment_id',
                        'edge.id AS edge_id',
                        'edge.place_id AS place_id',
                        'equipment_parameter_value.date_time as date_time']
                )
                ->from('equipment_parameter_value')
                ->leftJoin('equipment_parameter', 'equipment_parameter.id=equipment_parameter_value.equipment_parameter_id')
                ->leftJoin('edge', 'edge.place_id=equipment_parameter_value.value')
                ->where(['edge.place_id' => $edge_id])
                ->asArray()
                ->limit(1)
                ->one();

            if (isset($response)) {
                $warnings[] = $response;
                $warnings[] = "checkExistenceEquipmentInEdge. На выработке найдена конвейер.";
                $result = true;
            } else {
                $result = false;
                $warnings[] = "checkExistenceEquipmentInEdge. На выработке не найдена конвейер. Событие не будут формироваться. edge_id = " . $edge_id;
            }
        } catch (Throwable $exception) {
            $status = 0;
            $warnings[] = "checkExistenceEquipmentInEdge. Исключения";
            $warnings[] = $exception->getMessage();
            $warnings[] = $exception->getLine();
        }
        return $result_main = array('status' => $status, 'warnings' => $warnings, 'result' => $result);
    }

    public static function actionTranslatePackage()
    {
        $method_name = "actionTranslatePackage";
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $debug_data = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        try {
            $warnings[] = "actionTranslatePackage. Начал выполнять метод";

            $post = Assistant::GetServerMethod();                                                                       //получение данных от ajax-запроса
            $package_from_strata = $post['package'];
            $package_date_time = $post['date_time'];
            $ip = $post['ip'];
            $mine_id = $post['mineId'];

            $response = self::TranslatePackage($package_from_strata, $package_date_time, $ip, $mine_id);
            if ($response['status'] == 1) {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $debug[] = $response['debug'];
                $debug_data[] = $response['debug_data'];
                $status *= $response['status'];
            } else {
                $warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception("actionTranslatePackage. Ошибка в расчете контролльной суммы");
            }
        } catch (Throwable $exception) {
            $errors[] = "actionTranslatePackage. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        // запись в БД начала выполнения скрипта
        // TODO когда будет метод отдельный для контроля работы скриптов то включить эту часть
        // $date_time_debug_end = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));                       // время окончания выполнения метода
        // LogAmicum::LogAmicumSynchronization($method_name, $debug, $warnings, $errors,
        //     $duration_summary, $max_memory_peak, $count_all);
        //     $date_time_debug_start, $date_time_debug_end, $log_id,

        $warnings[] = "actionTranslatePackage. Закончил выполнять метод";
        $result_main = array('debug' => $debug, 'items' => $result, 'status' => $status, 'warnings' => $warnings, 'errors' => $errors, 'debug_data' => $debug_data);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = $result_main;
    }

    // TranslatePackage - метод расшифровки пакетов Strata
    // 04 - CommunicationNodeHeartbeat
    // получает на вход битовый пакет и расшифровывает его
    //  define('PHP_INTERPRETATOR', 'php73');           // php интерпритатор, который стоит на сервере
    //  define('YII_CONSOLE_PATH', '/var/www/html/amicum/yii');                                                                 // путь до yii2 для запуска консольных контроллеров
    // пример: 127.0.0.1/admin/strata-job/translate-package?ip=172.16.51.56&mineId=270&date_time=2020-06-17 12:30:01&package=8fea040c3c00400500bbaa004007bc200400bbaa004007bc0004000000c007800104030201010023fc74ab - пакет хардбита - целый
    // пример: 127.0.0.1/admin/strata-job/translate-package?ip=172.16.51.56&mineId=270&date_time=2020-06-17 12:30:01&package=8fea040c3c00400500bbaa004007bc200400bbaa004007bc0004000000c007800104030201010023fc74ab - пакет хардбита - касячный в страте
    // пример: 127.0.0.1/admin/strata-job/translate-package?ip=172.16.51.56&mineId=270&date_time=2020-06-17 12:30:01&package=8fea10028a187c046400000000650003000c6600270000670000000079d58fea04083d00490b00474c0048ccc0300700bc9d0048ccc0000f000000c00780010403020101000e88d9ba8fea0104278a80c00002004895eb00415aad818b8fea10098b0a64046400120014650201000066002700006700000000754c8fea0100278ad16d060200528dd7004ab1b1d345 - пакет хардбита - касячный в страте
    // пример: 127.0.0.1/admin/strata-job/translate-package?ip=172.16.51.56&mineId=270&date_time=2020-06-17 12:30:01&package=8fea010c258a81680604004ad7de005681b000bb93ac004f37a4d2bc                              - пакет локации
    // пример: 127.0.0.1/admin/strata-job/translate-package?ip=172.16.51.56&mineId=270&date_time=2020-06-17 12:30:01&package=8fea10038a15c6046400000000650003000c66002600006700000000c0fd                          - пакет с газом
    // пример: 127.0.0.1/admin/strata-job/translate-package?ip=172.16.51.56&mineId=270&date_time=2020-06-17 12:30:01&package=8fea10018a8370046400000000650003000c66002700006700000000d751 - пакет газа
    // пример: 127.0.0.1/admin/strata-job/translate-package?ip=172.16.51.56&mineId=270&date_time=2020-06-17 12:30:01&package=8fea0c00258a80a27600cc92 - пакет регистрации
    // пример: 127.0.0.1/admin/strata-job/translate-package?ip=172.16.51.56&mineId=270&date_time=2020-06-17 12:30:01&package=8fea0d00258a80a27600cc92 - пакет разрегистрации
    // пример: 127.0.0.1/admin/strata-job/translate-package?ip=172.16.51.56&mineId=270&date_time=2020-06-17 12:30:01&package=8fea06038acf56003152 - пакет доставления сообщения


    public static function TranslatePackage($package_from_strata, $package_date_time, $ip, $mine = AMICUM_DEFAULT_MINE)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей
        // Стартовая отладочная информация
        $log = new LogAmicumFront("TranslatePackage");

        try {
            $log->addLog("Начало выполнения метода");
            $log->addLog("Исходный пакет");
            $log->addData($package_from_strata, '$package_from_strata: ', __LINE__);

            // вычисляем длину строки
            $package_length = strlen($package_from_strata);

            //если длина строки больше двух сиволов, то проверяем дальше (8fea)
            if ($package_length < 3) {
                throw new Exception("Длина пакета <2 символов. Пакет битый");
            }

            $log->addLog("в пакете есть биты для расшифровки");

            $package_array = str_split($package_from_strata, 2);
            // делаем проверку на длинные склеенные пакеты
            $j = 0;
            $second_bite = 0;

            if ($package_length > 80) {
                foreach ($package_array as $bit) {
                    if ($bit == "8f") {
                        $second_bite = 1;
                    } else if ($bit == "ea" and $second_bite == 1) {
                        $second_bite = 0;
                        $j++;
                        $package_split[$j] = "8fea";
                    } else if ($bit != "ea" and $second_bite == 1) {
                        $second_bite = 0;
                        $package_split[$j] .= "8f" . $bit;
                    } else {
                        if (!isset($package_split)) {
                            $package_split[$j] = "";
                        }
                        $package_split[$j] .= $bit;
                    }
                }
            } else {
                $package_split[] = $package_from_strata;
            }

            if (!isset($package_split)) {
                $package_split = [];
            }
            $count_record = count($package_split);
            $log->addLog("Количество пакетов на обработку " . $count_record);

//            throw new Exception("actionTranslatePackage. Отладочный стоп");
            // взять первые два символа и проверить их на стартовость
            $log->addLog("НАЧАЛ ОБРАБОТКУ ПАКЕТОВ ----------------------");
            foreach ($package_split as $package_from_strata) {
                $flag_translate = true;
                $package_length = strlen($package_from_strata);
                $start_bit = substr($package_from_strata, 0, 4);

                if ($start_bit != "8fea") {
                    $flag_translate = false;
                    $log->addLog("Нет стартового пакета: " . $start_bit);
                } else {
                    //$log->addLog("В пакете есть стартовые биты");
                }

                //делаем проверку на корректность пакета расчет CRC суммы
                if ($package_length < 10) {
                    $flag_translate = false;
                    $log->addLog("Длина пакета <10 символов. контрольную сумму не смог получить");
                }

                if ($flag_translate) {
                    // бере с конца два последних байта
                    $crc16_bit_1 = substr($package_from_strata, $package_length - 4, 2);
                    $crc16_bit_2 = substr($package_from_strata, $package_length - 2, 2);
                    $package_source = substr($package_from_strata, 0, $package_length - 4);

                    $log->addData($crc16_bit_1, 'Первый бит контрольной суммы $crc16_bit_1: ', __LINE__);
                    $log->addData($crc16_bit_2, 'Первый бит контрольной суммы $crc16_bit_2: ', __LINE__);
                    $log->addData($package_source, 'Обрезанные пакет $package_source: ', __LINE__);

                    $response = self::crc16($package_source, $package_length - 4);
                    $log->addLogAll($response);
                    if ($response['status'] != 1) {
                        throw new Exception("actionTranslatePackage. Ошибка в расчете контролльной суммы");
                    }
                    $crc16_bit_calc_1 = $response['Items']['a'];
                    $crc16_bit_calc_2 = $response['Items']['b'];

                    $log->addData($crc16_bit_calc_1, 'Первый бит контрольной суммы $crc16_bit_calc_1: ', __LINE__);
                    $log->addData($crc16_bit_calc_2, 'Первый бит контрольной суммы $crc16_bit_calc_2: ', __LINE__);

                    if ($crc16_bit_calc_1 != $crc16_bit_1 and $crc16_bit_calc_2 != $crc16_bit_2) {
                        $flag_translate = false;
                        $log->addLog("Контрольная сумма в пакете не совпадает с расчетной");
                    }
                }

                if ($flag_translate) {
                    //тип пакета
                    $package_type = substr($package_from_strata, 4, 2);
                    $package = substr($package_from_strata, 6, $package_length - 4);
                    //расшифровка хардбита узла связи
                    switch ($package_type) {
                        case '04':                                                                                              // хардбит узла связи
                            $log->addLog("Пакет состояния узла связи");
//                            $warnings[] = PHP_INTERPRETATOR . ' ' . YII_CONSOLE_PATH . ' strata-queue-command-line/thread-translate-heartbeat-package '
//                                . $package . ' '
//                                . '"' . $package_date_time . '" '
//                                . $ip . ' '
//                                . $mine
//                                . ' ' . DEV_NULL;

                            exec(PHP_INTERPRETATOR . ' ' . YII_CONSOLE_PATH . ' strata-queue-command-line/thread-translate-heartbeat-package '
                                . $package . ' '
                                . '"' . $package_date_time . '" '
                                . $ip . ' '
                                . $mine
                                . ' ' . DEV_NULL);

//                            $response = self::TranslateHeartbeat($package);
//                            if ($response['status'] == 1) {
//                                $translate_package = $response['Items'];
//                                $translate_package->timestamp = $package_date_time;
//                                $translate_package->ip = $ip;
//                                $warnings[] = "actionTranslatePackage. Пакет после рассшифровки";
//                                $warnings[] = $translate_package;
//                                $warnings[] = $response['warnings'];
//
//
//                                $result = self::saveHeartbeatPacket($translate_package, $mine);
//                                if ($result['status'] != 1) {
//                                    $warnings[] = $result['warnings'];
//                                    $errors[] = $result['errors'];
//                                    $debug[] = $result['debug'];
//                                    $status *= $result['status'];
//                                }
//                            } else {
//                                $warnings[] = $response['warnings'];
//                                $errors[] = $response['errors'];
//                                $errors[] = "actionTranslatePackage. Ошибка в расшифровке пакета: " . $package;
//                            }
                            break;
                        case '01':                                                                                              // локация
                            $log->addLog("Пакет локации");
//                            $warnings[] = PHP_INTERPRETATOR . ' ' . YII_CONSOLE_PATH . ' strata-queue-command-line/thread-translate-location-package '
//                                . $package . ' '
//                                . '"' . $package_date_time . '" '
//                                . $ip . ' '
//                                . $mine
//                                . ' ' . DEV_NULL;
//
                            exec(PHP_INTERPRETATOR . ' ' . YII_CONSOLE_PATH . ' strata-queue-command-line/thread-translate-location-package '
                                . $package . ' '
                                . '"' . $package_date_time . '" '
                                . $ip . ' '
                                . $mine
                                . ' ' . DEV_NULL);
//                            $response = self::TranslateLocation($package);
//                            if ($response['status'] == 1) {
//                                $translate_package = $response['Items'];
//                                $translate_package->timestamp = $package_date_time;
//                                $translate_package->ip = $ip;
//
//                                $log->addData($translate_package, 'Пакет после рассшифровки $translate_package: ', __LINE__);
//
//                                $result = self::saveLocationPacket($translate_package, $mine);
//                                $flag = $result['flag']; // 1- человек // 2- оборудование
//                                $log->addLog("Обработал локацию: " . $flag);
//
//                                $log->addLogAll($result);
//
//                            } else {
//                                $log->addLogAll($response);
//                                $log->addError("Ошибка в расшифровке пакета: " . $package, __LINE__);
//                            }
                            break;
                        case '0c':                                                                                              // локация
                        case '0d':                                                                                              // локация
                            $log->addLog("Пакет регистрации/разрегистрации работника");
//                        $warnings[] = PHP_INTERPRETATOR . ' ' . YII_CONSOLE_PATH . ' strata-queue-command-line/thread-translate-registration-package '
//                            . $package . ' '
//                            . '"' . $package_date_time . '" '
//                            . $ip . ' '
//                            . $package_type . ' '
//                            . $mine
//                            . ' ' . DEV_NULL;
//
                        exec(PHP_INTERPRETATOR . ' ' . YII_CONSOLE_PATH . ' strata-queue-command-line/thread-translate-registration-package '
                            . $package . ' '
                            . '"' . $package_date_time . '" '
                            . $ip . ' '
                            . $package_type . ' '
                            . $mine
                            . ' ' . DEV_NULL);
//
//                            $response = self::TranslateCheckInCheckOut($package, $package_type);
//                            if ($response['status'] == 1) {
//                                $translate_package = $response['Items'];
//                                $translate_package->timestamp = $package_date_time;
//                                $translate_package->ip = $ip;
//
//                                $log->addData($translate_package, 'Пакет после рассшифровки $translate_package: ', __LINE__);
//
//                                $result = self::saveRegistrationPacket($translate_package, $mine, $ip);
//                                if ($result['status'] != 1) {
//                                    $log->addLogAll($result);
//                                }
//                            } else {
//                                $log->addLogAll($response);
//                                $log->addError("Ошибка в расшифровке пакета: " . $package, __LINE__);
//                            }
                            break;
                        case '06':                                                                                              // локация
                            // подтверждение получения пакета
                            $log->addLog("Пакет подтверждение получения пакета");
                            $response = self::TranslateAscRead($package);
                            //$log->addLogAll($response);
                            if ($response['status'] == 1) {
                                $translate_package = $response['Items'];
                                $translate_package->timestamp = $package_date_time;
                                $translate_package->ip = $ip;

                                $log->addData($translate_package, 'Пакет после рассшифровки $translate_package: ', __LINE__);

                                $result = self::SaveMessageAck($package_date_time, $translate_package->messageId, $translate_package->networkId);
                                if ($result['status'] != 1) {
                                    $log->addLogAll($result);
                                }
                            } else {
                                $log->addError("Ошибка в расшифровке пакета: " . $package, __LINE__);
                            }
                            break;
                        case '0a':                                                                                              // локация
                            // подтверждение прочтения пакета
                            $log->addLog("Пакет подтверждение прочтения пакета");
                            $response = self::TranslateAscRead($package);
                            if ($response['status'] == 1) {
                                $translate_package = $response['Items'];
                                $translate_package->timestamp = $package_date_time;
                                $translate_package->ip = $ip;

                                $log->addData($translate_package, 'Пакет после рассшифровки $translate_package: ', __LINE__);

                                $result = self::SaveMessageRead($package_date_time, $translate_package->messageId, $translate_package->networkId);
                                if ($result['status'] != 1) {
                                    $log->addLogAll($result);
                                }
                            } else {
                                $log->addLogAll($response);
                                $log->addError("Ошибка в расшифровке пакета: " . $package, __LINE__);
                            }
                            break;
                        case '10':                                                                                              // газ
                            $log->addLog("Пакет газа");
//                            $warnings[] = PHP_INTERPRETATOR . ' ' . YII_CONSOLE_PATH . ' strata-queue-command-line/thread-translate-environmental-package '
//                                . $package . ' '
//                                . '"' . $package_date_time . '" '
//                                . $ip . ' '
//                                . $mine
//                                . ' ' . DEV_NULL;

                            exec(PHP_INTERPRETATOR . ' ' . YII_CONSOLE_PATH . ' strata-queue-command-line/thread-translate-environmental-package '
                                . $package . ' '
                                . '"' . $package_date_time . '" '
                                . $ip . ' '
                                . $mine
                                . ' ' . DEV_NULL);

//                            $response = self::TranslateEnvironmental($package);
//                            $log->addLogAll($response);
//                            if ($response['status'] == 1) {
//                                $translate_package = $response['Items'];
//                                $translate_package->timestamp = $package_date_time;
//                                $translate_package->ip = $ip;
//                                $log->addData($translate_package, 'Пакет после рассшифровки $translate_package: ', __LINE__);
//                                $result = self::saveEnvironmentalPacket($translate_package, $mine);
//                                if ($result['status'] != 1) {
//                                    $log->addLogAll($result);
//                                }
//                            } else {
//                                $warnings[] = $response['warnings'];
//                                $errors[] = $response['errors'];
//                                $errors[] = "actionTranslatePackage. Ошибка в расшифровке пакета: " . $package;
//                            }
                            break;
                    }
                }
                $log->addLog("ЗАКОНЧИЛ ОБРАБОТКУ ПАКЕТА  ----------------------");
            }
            $log->addLog("ЗАКОНЧИЛ ОБРАБОТКУ ПАКЕТОВ ----------------------");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());

            $data_to_log_cache = array_merge(['Items' => []], $log->getLogAll());
            LogCacheController::setStrataLogValue('actionTranslatePackage', $data_to_log_cache);
        }
        $log->addLog("Закончил выполнять метод");
        if ($count_record > 300) {
            $data_to_log_cache = array_merge(['Items' => []], $log->getLogAll());
            LogCacheController::setStrataLogValue('actionTranslatePackage', $data_to_log_cache, 4);
        }
        return array_merge(['Items' => $result], $log->getLogAll());
    }

    static function crc16($data, $length)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {
            $warnings[] = "crc16. Начал выполнять метод";

            $crcA = 0;

            $crcB = 0;
            for ($i = 0; $i < $length; $i = $i + 2) {
                $crcA += (hexdec(substr($data, $i, 2)) & 0xff);
//                $warnings[]=($crcA);
                $crcB += $crcA;
//                $warnings[]=($crcA & 0xff);
            }


            $a = dechex($crcA & 0xff);
            $b = dechex($crcB & 0xff);
            $result = array('a' => $a, 'b' => $b);
        } catch (Throwable $exception) {
            $errors[] = "crc16. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = "crc16. Закончил выполнять метод";

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);

        return $result_main;
    }

    static function TranslateLocation($package)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {
            $warnings[] = "TranslateLocation. Начал выполнять метод";
            $warnings[] = "TranslateLocation. Длина пакета: " . strlen($package);
            $result['timestamp'] = Assistant::GetDateNow();                                                             // временная отметка
            $result['sequenceNumber'] = hexdec(substr($package, 0, 2));                                                 // последовательный номер сигнала узла
            $result['batteryVoltage'] = (string)str_replace(',', '.', hexdec(substr($package, 2, 2)) / 10);             // напряжение батареи узла связи


            $msb = self::AddZero(base_convert(substr($package, 4, 2), 16, 2), 8);                                                         // сетевой адрес узла-источника сигнала
            $msb_shrink = base_convert(substr($msb, 1, 7), 2, 16);
            $snd_hex = substr($package, 6, 2);                                                                          // сетевой адрес узла-источника сигнала
            $lsb_hex = substr($package, 8, 2);                                                                          // сетевой адрес узла-источника сигнала

            $package_net_id = $msb_shrink . "" . $snd_hex . "" . $lsb_hex;
            $result['sourceNode'] = hexdec($package_net_id);                                                            // сетевой адрес узла-источника сигнала
            $result['networkId'] = hexdec($package_net_id);                                                             // сетевой адрес узла-источника сигнала

            $package_service_byte = self::AddZero(base_convert(substr($package, 10, 2), 16, 2), 8);
            $warnings[] = $package_service_byte;

            $result['alarmFlag'] = base_convert(substr($package_service_byte, 7, 1), 2, 10);                            //флаг получения сигнала SOS с устройства
            $result['emergencyFlag'] = base_convert(substr($package_service_byte, 4, 1), 2, 10);                        //флаг экстренной ситуации

            $surface_moving_flag = base_convert(substr($package_service_byte, 5, 2), 2, 10);                           //подземный/в движении флаг
//            $result['movingFlag'] = base_convert(substr($package_service_byte, 5, 2), 2, 10);                          //поверхностный флаг
            $warnings[] = $surface_moving_flag;
//            $surface_moving_flag=1;

            switch ($surface_moving_flag) {

                case "0":
                    $result['movingFlag'] = "Stationary";
                    $result['surfaceFlag'] = "Underground";
                    break;
                case "1":
                    $result['movingFlag'] = "Stationary";
                    $result['surfaceFlag'] = "Surface";
                    break;
                case "2":
                    $result['movingFlag'] = "Moving";
                    $result['surfaceFlag'] = "Surface";
                    break;
                case "3":
                    $result['movingFlag'] = "Moving";
                    $result['surfaceFlag'] = "Underground";
                    break;
                default:
                    $result['movingFlag'] = "N/A";
                    $result['surfaceFlag'] = "N/A";

            }

            $count_nodes = hexdec(substr($package, 12, 2));                                                             //количество heartbeat-сообщений
            $result['count_nodes'] = $count_nodes;
            //проверяем не равень ли количество улышанных узлов нулю (0)
            //если количесво равен 0 то формируем пустой массив

            if (isset($count_nodes) and $count_nodes != 0) {
                for ($i = 14; $i < 14 + $count_nodes * 8; $i = $i + 8) {

                    // байт 12-14
                    $result['nodes'][] = new HearedNode(
                        hexdec(substr($package, $i, 6)),                                                                //сетевой адрес шлюза синхронизации родительский времени
                        hexdec(substr($package, $i + 6, 2)) - 256                                                       //уровень сигнала сетевого адреса шлюза синхронизации родительский времени (14 байт)
                    );
                }
            } else {
                $result['nodes'] = array();
            }

            $result = (object)$result;
            $result = new MinerNodeLocation($result);
        } catch (Throwable $exception) {
            $errors[] = "TranslateLocation. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = "TranslateLocation. Закончил выполнять метод";

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);

        return $result_main;
    }

    static function AddZero($value, $count_zero)
    {
        $str_len = strlen($value);
        $count_zero_to_add = $count_zero - $str_len;
        if ($count_zero_to_add) {
            for ($i = 0; $i < $count_zero_to_add; $i++) {
                $value = "0" . $value;
            }
        }
        return $value;
    }

    /**
     * Метод сохранения местоположения (координат) и места (edge) работника и датчика.
     * @param MinerNodeLocation $pack Пакет местоположения шахтёра
     * @param int $mine_id Идентификатор шахты
     * @return array
     */
    public static function saveLocationPacket(MinerNodeLocation $pack, $mine_id)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("saveLocationPacket");
        $flag = 0;

        $count_all = 0;                                                                                                 // количество полученных записей
        //состояние выполнения метода
        try {
            $log->addLog("Начало выполнения метода");

            $sensor_cache_controller = new SensorCacheController();

            /**=================================================================
             * Получаем из кеша сенсор айди по нетворк айди
             * ==================================================================*/
            $network_id = $pack->networkId;

            // Поиск объекта сенсора по сетевому идентификатору из пакета.
            // Если такой сенсор не найден, то создаём новый сенсор по шаблону.

            $response = SensorMainController::getOrSetSensorByNetworkId($network_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new \Exception('Ошибка при инициализации сенсора по сетевому адресу: ' . $network_id);
            }

            if ($response['sensor_id'] === false) {                                                                     //если sensor_id не найден, создать его
                $title = 'Метка прочее networkID ' . $network_id;
                $response = self::createSensorDatabase($title, $network_id, $mine_id, 104, 1, 4);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception("Ошибка создания сенсора по нетворк айди $network_id в кеше и в БД");
                }
                $sensor_id = $response['sensor_id'];
            } else {
                $sensor_id = $response['sensor_id'];
            }

            $log->addLog('Получил из кеша сенсор айди ' . $sensor_id . ' по нетворк айди ' . $network_id);

            /**=================================================================
             * Инициализация кеша сенсора SensorMine
             * ==================================================================*/

            if ($sensor_cache_controller->getSensorMineBySensorHash($sensor_id) === false) {
                $response = SensorMainController::initSensorInCache($sensor_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception('Ошибка при инициализации сенсора: ' . $sensor_id);
                }
            } else {
                $response = SensorMainController::moveSensorMineInitCache($sensor_id, $mine_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception("Ошибка при перемещении сенсора $sensor_id из кеша шахты");
                }
            }

            $log->addLog('Инициализация кеша сенсора SensorMine закончил');

            /**=================================================================
             * Нахождение координат точки
             * ===============================================================*/
            $location_status = 1;
            $total_nodes_heared = count($pack->nodes);
            /**
             * Если в пакете не было услышанных узлов, то повторно сохраняем
             * последние параметры координат
             */
            if ($total_nodes_heared === 0) {
                $log->addLog('Начал расчет координат В пакете не было услышанных узлов, беру последие параметры сенсора');

                $last_sensor_values = $sensor_cache_controller->multiGetParameterValueHash($sensor_id);

                foreach ($last_sensor_values as $last_sensor_value) {
                    $last_sensor_values_hand[$last_sensor_value['parameter_id']][$last_sensor_value['parameter_type_id']] = $last_sensor_value;
                }

                if (isset($last_sensor_values_hand[ParamEnum::COORD][ParameterTypeEnumController::MEASURED])) {
                    $xyz = $last_sensor_values_hand[ParamEnum::COORD][ParameterTypeEnumController::MEASURED]['value'];
                } else {
                    throw new \Exception("В кэше не найдены координаты для узла $sensor_id");
                }

                if (isset($last_sensor_values_hand[ParamEnum::EDGE_ID][ParameterTypeEnumController::MEASURED])) {
                    $edge_id = $last_sensor_values_hand[ParamEnum::EDGE_ID][ParameterTypeEnumController::MEASURED]['value'];
                } else {
                    throw new \Exception("В кэше не найдены эдж для узла $sensor_id");
                }

                if (isset($last_sensor_values_hand[ParamEnum::PLACE_ID][ParameterTypeEnumController::MEASURED])) {
                    $place_id = $last_sensor_values_hand[ParamEnum::PLACE_ID][ParameterTypeEnumController::MEASURED]['value'];
                } else {
                    throw new \Exception("В кэше не найдены плейс для узла $sensor_id");
                }

                $log->addLog('Закончил расчет координат Если в пакете не было услышанных узлов, то повторно сохраняем');
            } /**
             * Если в пакете был только один услышанный узел, то берём его параметры
             */
            elseif ($total_nodes_heared === 1) {
                $log->addLog('Начал расчет координат В пакете был только один услышанный узел: ' . $network_id . ', берём его параметры');

                $location_status = StatusEnumController::CALCULATED_VALUE;

                $network_id = $pack->nodes[0]->address;
                $node_id = (new ServiceCache())->getSensorByNetworkId($network_id);
                if ($node_id === false) {
                    throw new \Exception("Шлюз/узел не стоит на схеме. Не удалось найти в кэше сенсор по сетевому идентификатору $network_id");
                }

                $log->addLog("Сеносор $node_id по сетевому адресу $network_id, беру его параметры");

                $lamp_node_values = $sensor_cache_controller->multiGetParameterValueHash($node_id);

                foreach ($lamp_node_values as $lamp_node_value) {
                    $lamp_node_values_hand[$lamp_node_value['parameter_id']][$lamp_node_value['parameter_type_id']] = $lamp_node_value;
                }

                if (isset($lamp_node_values_hand[ParamEnum::COORD][ParameterTypeEnumController::REFERENCE])) {
                    $xyz = $lamp_node_values_hand[ParamEnum::COORD][ParameterTypeEnumController::REFERENCE]['value'];
                } else {
                    throw new \Exception("В кэше не найдены координаты для узла $node_id");
                }

                if (isset($lamp_node_values_hand[ParamEnum::EDGE_ID][ParameterTypeEnumController::REFERENCE])) {
                    $edge_id = $lamp_node_values_hand[ParamEnum::EDGE_ID][ParameterTypeEnumController::REFERENCE]['value'];
                } else {
                    throw new \Exception("В кэше не найдены эдж для узла $node_id");
                }

                if (isset($lamp_node_values_hand[ParamEnum::PLACE_ID][ParameterTypeEnumController::REFERENCE])) {
                    $place_id = $lamp_node_values_hand[ParamEnum::PLACE_ID][ParameterTypeEnumController::REFERENCE]['value'];
                } else {
                    throw new \Exception("В кэше не найдены плейс для узла $node_id");
                }

                $log->addLog("Закончил расчет координат");
            } else {
                /**
                 * Если в пакете больше 1 услышанного узла, то вычисляем координаты
                 */
                $log->addLog("Начал расчет координат Если в пакете больше 1 услышанного узла, то вычисляем координаты");

                $response = (new CoordinateController)->calculateCoordinates($pack->nodes, $mine_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception('Ошибка при расчёте координат');
                }

                $xyz = $response['xyz'];
                $edge_id = $response['edge_id'];
                $place_id = $response['place_id'];

                $log->addLog("Закончил расчет координат Если в пакете больше 1 услышанного узла, то вычисляем координаты");
            }

            $log->addLog("Закончил расчет координат");

            if ($xyz == -1 || $edge_id == -1 || $place_id == -1) {
                $errors['xyz'] = $xyz;
                $errors['edge_id'] = $edge_id;
                $errors['place_id'] = $place_id;
                throw new \Exception('Параметры положения некорректны');
            }

            /**=================================================================
             * Нахождение информации о нахождении светильника.
             * Нужно для проверки на нахождение в ламповой и запретной зоне
             * ==================================================================*/
            $log->addLog("Определение статуса местоположения (разрешено/запрещено)");

            $edge_info = EdgeMainController::getEdgeMineDetail($mine_id, $edge_id);
            $log->addLogAll($edge_info);
            if ($edge_info && $edge_info['edge_info']) {
                $place_object_id = $edge_info['edge_info']['place_object_id'];
                $status_danger_zone = StatusEnumController::PERMITTED;
                if ($edge_info['edge_info']['danger_zona'] == 1) {
                    $status_danger_zone = StatusEnumController::FORBIDDEN;
                    $log->addLog("Запрет постоянный через выработку Unity");
                } else {
                    $isForbidden = 0;

                    $response = ForbiddenZoneController::GetActiveForbiddenZoneByEdge($edge_id);
                    $log->addLogAll($response);
                    if ($response['status'] == 1) {
                        $isForbidden = $response['isForbidden'];
                    }

                    if ($isForbidden) {
                        $log->addLog("Запрет через конструктор запретов");
                        $status_danger_zone = StatusEnumController::FORBIDDEN;
                    }
                }
            } else {
                $place_object_id = -1;
                $status_danger_zone = StatusEnumController::PERMITTED;
            }

            $log->addLog("Закончил определение статуса местоположения (разрешено/запрещено)");
            $log->addLog("Нахождение скорости движения человека");


            /**=================================================================
             * Вычисление скорости движения человека
             * Условия запуска алгоритма:
             *  1) На выработке есть конвейер
             *  2) В пакете не менее 2 узлов связи
             *  3) Предыдущее значение координат расчитано не менее чем по 2 узлам связи
             * ==================================================================*/

            $speed['generate_event'] = false;
            $speed['speed_value'] = -1;
            if ($edge_info && $edge_info['edge_info'] && $edge_info['edge_info']['conveyor'] == 1) {
                $log->addLog("На выработке $edge_id есть конвейер");
                if ($total_nodes_heared > 2) {
                    $last_total_nodes_heared = $sensor_cache_controller->getParameterValueHash($sensor_id, ParamEnum::NEIGHBOUR_COUNT, ParameterTypeEnumController::MEASURED);
                    $last_total_nodes_heared_flag = false;                                                                // флаг разрешения для расчета скорости движения работника (если расчет проивзодился по двум узлам и более и время корректное, то делать расчет)
                    if ($last_total_nodes_heared !== false && $last_total_nodes_heared['status_id'] == 1) {
                        $delta_time = strtotime($pack->timestamp) - strtotime($last_total_nodes_heared['date_time']);
                        $log->addLog("Расчет скорости текущая дата " . strtotime($pack->timestamp));
                        $log->addLog("Расчет скорости Прошлая дата " . strtotime($last_total_nodes_heared['date_time']));

                        if ($delta_time < 150 and $last_total_nodes_heared['value'] > 1) {                                   // время не больше 2,5 минут и количество услышанных узлов в расчете больше 1
                            $last_total_nodes_heared_flag = true;
                            $log->addLog("Расчет скорости Разрешение расчета");
                        } else {
                            $log->addLog("Расчет скорости Количество услышанных узлов связи " . $last_total_nodes_heared['value']);
                            $log->addLog("Расчет скорости Дельта времени " . $delta_time);
                            $log->addLog("Расчет скорости Запрет расчета ");
                        }
                    } else {
                        $log->addLog("Нет предыдущего параметра расчета количества услышанных узлов");
                    }
                    $log->addLog("В текущем пакете не менее 2 узлов связи");

                    $last_coord = $sensor_cache_controller->getParameterValueHash($sensor_id, ParamEnum::COORD, ParameterTypeEnumController::MEASURED);
                    if ($last_coord !== false && $last_coord['status_id'] == 1 && $last_total_nodes_heared_flag) {
                        $log->addLog("Предыдущее значение координат расчитано не менее чем по 2 узлам связи");
                        try {
                            $speed = CoordinateController::calculateSpeed(
                                $last_coord['value'], $last_coord['date_time'],
                                $xyz, $pack->timestamp
                            );
                            $log->addLog("Расcчитал скорость движения человека = " . $speed['speed_value'] . " м/с");
                        } catch (Throwable $exception) {
                            $log->addData($exception->getMessage(), '$exception->getMessage()', __LINE__);
                        }
                    } else {
                        $log->addLog("Предыдущее значение координат расчитано менее чем по 2 узлам связи или есть запрет на расчет");
                    }
                } else {
                    $log->addLog("В пакете менее 2 узлов связи");
                }
            } else {
                $log->addLog("На выработке $edge_id нет конвейера");
            }
            $log->addLog("Вычислил скорость движения человека");

            /**=================================================================
             * Сохранение параметров сенсора
             * ==================================================================*/
            $response = self::saveLocationPacketSensorParameters($sensor_id, $pack, $mine_id, $xyz, $edge_id, $place_id, $place_object_id, $location_status, $total_nodes_heared);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
//                throw new \Exception('Ошибка при сохранении параметров сенсора');
            }

            $log->addLog("Закончил Сохранение параметров сенсора");

            /**=================================================================
             * Сохранение параметров воркера
             * ==================================================================*/
            $response = WorkerMainController::getWorkerInfoBySensorId($sensor_id);
            $log->addLogAll($response);
            $worker_sensor = $response['Items'];
            if ($worker_sensor) {
                $flag += 1;
                $log->addLog("Найдена привязка воркера к сенсору: " . $worker_sensor['worker_id']);
                $response = self::saveLocationPacketWorkerParameters($pack, $worker_sensor, $mine_id, $xyz, $edge_id, $place_id, $status_danger_zone, $place_object_id, $speed, $location_status);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception('Ошибка при сохранении параметров воркера');
                }
            }
            $log->addLog("Закончил Сохранение параметров воркера");

            /**=================================================================
             * Сохранение параметров оборудования
             * ==================================================================*/
            $response = EquipmentMainController::getEquipmentInfoBySensorId($sensor_id);
            $log->addLogAll($response);
            $equipment_sensor = $response['Items'];
            if ($equipment_sensor) {
                $flag += 2;
                $log->addLog("Найдена привязка оборудования к сенсору");
                $response = self::saveLocationPacketEquipmentParameters($pack, $equipment_sensor, $xyz, $edge_id, $place_id, $status_danger_zone, $mine_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception('Ошибка при сохранении параметров оборудования');
                }
            }

            $log->addLog("Сохранение параметров оборудования");

            /**=================================================================
             * Генерация записи в отчётной таблице о нахождении в запретной зоне
             * (при необходимости)
             * ==================================================================*/
            // Нахождение объекта, привязанного к сенсору.
            // Если к сенсору не привязан объект, то ищется объект самого сенсора
            // Требуется для записи в отчётную таблицу summary_report_forbidden_zones
            if ($worker_sensor) {
                $log->addLog("Поиск объекта воркера");

                $main_obj = WorkerObject::find()
                    ->with('worker.employee')
                    ->where(['worker_id' => $worker_sensor['worker_id']])
                    ->limit(1)
                    ->one();
                $main_title = $main_obj->worker->employee->last_name . ' ' . $main_obj->worker->employee->first_name . ' ' . $main_obj->worker->employee->patronymic;
                $table_name = 'worker_object';
            } elseif ($equipment_sensor) {
                $log->addLog("Поиск объекта оборудования");
                $main_obj = Equipment::findOne(['id' => $equipment_sensor['equipment_id']]);
                $main_title = $main_obj['title'];
                $table_name = 'equipment';
            } else {
                $log->addLog("Поиск объекта сенсора");
                $main_obj = Sensor::findOne(['id' => $sensor_id]);
                $main_title = $main_obj['title'];
                $table_name = 'sensor';
            }

            // Выполнение проверки на нахождение в запретной зоне.
            // При выявлении факта нахождения или выхода из запретной зоны -
            // создаётся запись в отчетной таблице summary_report_forbidden_zones
            $response = self::checkForbiddenZoneStatus($pack, $status_danger_zone, $main_obj, $main_title, $table_name, $place_id, $edge_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new \Exception('Ошибка при проверке запретной зоны');
            }
            $log->addLog("Генерация записи в отчётной таблице о нахождении в запретной зоне");

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
            $data_to_log = array_merge(
                [
                    'Items' => $result,
                    'Method parameters' => ['pack' => $pack, 'mine_id' => $mine_id]
                ],
                $log->getLogAll()
            );
            LogCacheController::setStrataLogValue('saveLocationPacket', $data_to_log, '2');
        }
        $log->addLog("Окончание выполнения метода");

        return array_merge(['flag' => $flag, 'Items' => $result], $log->getLogAll());
    }

    /**
     * Метод для создания сенсора в базе данных.
     * Создает запись в основной таблице sensor, в таблице sensor_parameter
     * создает связки для параметров "Сетевой идентификатор" и "Наименование
     * шахтного поля", в таблицу sensor_parameter_value записывает значения
     * этих параметров.
     * @param $sensor_title -   Название сенсора
     * @param $network_id -   Сетевой идентификатор сенсора
     * @param $mine_id -   Идентификатор шахты
     * @param $object_id -   Идентификатор объекта
     * @param $asmtp_id -   Идентификатор АСУТП
     * @param $sensor_type_id -   Идентификатор типа сенсора
     * @return array        -   запись из таблицы sensor, с информацией о новом сенсоре
     */
    public static function createSensorDatabase($sensor_title, $network_id, $mine_id, $object_id, $asmtp_id, $sensor_type_id)
    {
        $result = array();                                                                                                // промежуточный результирующий массив
        $errors = array();                                                                                              // массив ошибок
        $warnings = array();                                                                                              // массив предупреждений
        $status = 1;
        $sensor_id = false;
        $sensor_parameter_id_network = -1;
        //$warnings[] = 'createSensorDatabase. Зашел с метод';
        try {
            /**
             * Создание сенсора с типовыми параметрами в БД и в КЕШЕ
             */
            $response = SensorBasicController::addSensor($sensor_title, $object_id, $asmtp_id, $sensor_type_id, $mine_id, $network_id);
            if ($response['status'] == 1) {
                $sensor_id = $response['sensor_id'];
                $sensor_parameter_id_network = $response['sensor_parameter_id_network'];
                $sensor_parameter_handbook_value = $response['sensor_parameter_handbook_value'];
                //$warnings[] = $response['warnings'];
            } else {
                $errors[] = $response['errors'];
                //$warnings[] = $response['warnings'];
                throw new \Exception("createSensorDatabase. Ошибка создания сенсора в БД с типовыми параметрами. сетевой айди = $network_id");
            }
            $response = (new SensorCacheController)->multiSetSensorParameterValueHash($sensor_parameter_handbook_value);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                //$warnings[] = 'createSensorDatabase. Инициализировал кеш параметров сенсора.Инициализирую главный кеш сенсора';
            } else {
                $errors[] = $response['errors'];
                //$warnings[] = $response['warnings'];
                throw new \Exception("createSensorDatabase. Ошибка массового создания параметров сенсора $sensor_id в кеше ");
            }
            $response = (new SensorCacheController)->initSensorMainHash($mine_id, $sensor_id);
            if ($response['status'] == 1) {
                $errors[] = $response['errors'];
                //$warnings[] = $response['warnings'];
                //$warnings[] = 'createSensorDatabase. Успешно закончил создание сенсора в кеше и БД';
            } else {
                $errors[] = $response['errors'];
                //$warnings[] = $response['warnings'];
                throw new \Exception("createSensorDatabase. Ошибка создания главного кеша сенсора $sensor_id");
            }

//            /**
//             * Создание параметра network_id в БД
//             */
//            $response = SensorBasicController::addSensorParameter($sensor_id, 88, 1);
//            if ($response['status'] == 1) {
//                $sensor_parameter_id = $response['sensor_parameter_id'];
//                //$warnings[] = $response['warnings'];
//                $errors[] = $response['errors'];
//                $status *= $response['status'];
//            } else {
//                //$warnings[] = $response['warnings'];
//                $errors[] = $response['errors'];
//                throw new \Exception("createSensorDatabase. Ошибка сохранения параметра 88 сенсора $sensor_id");
//            }
            /**
             * Запись значения сетевого адреса в БД
             */
//            $date_now = Assistant::GetDateNow();
//            if($sensor_parameter_id_network!=-1) {
//                $response = SensorBasicController::addSensorParameterHandbookValue($sensor_parameter_id_network, $network_id, 1, $date_now);
//                if ($response['status'] == 1) {
//                    //$warnings[] = $response['warnings'];
//                    $errors[] = $response['errors'];
//                    $status *= $response['status'];
//                } else {
//                    //$warnings[] = $response['warnings'];
//                    $errors[] = $response['errors'];
//                    throw new \Exception("createSensorDatabase. Ошибка сохранения значения параметра 88 сенсора $sensor_id");
//                }
//
//                /**
//                 * Запись к кеш значения нового параметра
//                 */
//                $sensor_cache = new SensorCacheController();
//                $response = $sensor_cache->setSensorParameterValueHash($sensor_id, $sensor_parameter_id_network, $network_id, 88, 1, 1, $date_now);
//                if ($response['status'] == 1) {
//                    //$warnings[] = $response['warnings'];
//                    $errors[] = $response['errors'];
//                    $status *= $response['status'];
//                } else {
//                    //$warnings[] = $response['warnings'];
//                    $errors[] = $response['errors'];
//                    throw new \Exception("createSensorDatabase. Ошибка сохранения значения параметра 88 сенсора $sensor_id");
//                }
//            }
            /**
             * Инициализируем кеш сетевых адресов сенсоров
             */
            $response = (new ServiceCache())->setSensorByNetworkId($network_id, $sensor_id);
            if ($response === true) {
                //$warnings[] = 'Создание кеша сетевых адресов закончилось успешно';
            } else {
                throw new \Exception("createSensorDatabase. Ошибка создания кеша сетевых адресов $network_id для сенсора $sensor_id");
            }

        } catch (Throwable $e) {
            $status = 0;
            $errors[] = 'addSensor. Исключение';
            $errors[] = $e->getMessage();
            $errors[] = $e->getLine();
        }
        //$warnings[] = 'createSensorDatabase. Вышел с метода';
        return array('Items' => $result, 'sensor_id' => $sensor_id, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Сохраняет параметры сенсора, полученные из пакета положения шахтёра.
     * @param int $sensor_id Объект сенсора
     * @param object $pack Объект пакета положения шахтёра
     * @param int $mine_id Идентификатор шахты
     *
     * @param $xyz координата метки
     * @param $edge_id ключ выработки
     * @param $place_id ключ места
     * @param $place_object_id ключ типа места
     * @param int $location_status статус локации
     * @param int $total_nodes_heared количество услышанных узлов связи
     * @return array
     *
     */
    public static function saveLocationPacketSensorParameters($sensor_id, $pack, $mine_id, $xyz, $edge_id, $place_id, $place_object_id, $location_status = 1, $total_nodes_heared = 0)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();

        try {
            //$warnings[] = 'saveLocationPacketSensorParameters. Сохраняем параметры сенсора';
            /**
             * получаем за раз все последние значения по сенсору из кеша
             */
            // TODO: данный блок занимает больше всего времени в данном методе для выполнения
            //
            $start = microtime(true);
            $sensor_cache_controller = new SensorCacheController();
            //$warnings[] = 'saveLocationPacketSensorParameters. получаем данные с кеша по всему сенсору';
            $inner_start = microtime(true);
            $sensor_parameter_value_list_cache = $sensor_cache_controller->multiGetParameterValueHash($sensor_id, '*', '*', true);
            $warnings[__FUNCTION__ . '.multiget'] = microtime(true) - $inner_start;
            $inner_start = microtime(true);
            foreach ($sensor_parameter_value_list_cache as $sensor_parameter_value_cache) {
                $sensor_parameter_value_cache_array[$sensor_parameter_value_cache['sensor_id']][$sensor_parameter_value_cache['parameter_type_id']][$sensor_parameter_value_cache['parameter_id']] = $sensor_parameter_value_cache;
            }
            $warnings[__FUNCTION__ . '. foreach'] = microtime(true) - $inner_start;
            //$warnings[] = 'saveLocationPacketSensorParameters. получил данные с кеша по всему сенсору';
            $warnings[__FUNCTION__ . '. получаем за раз все последние значения по сенсору из кеша'] = microtime(true) - $start;
            //$sensor_parameter_value_cache_array = "";
            /**=================================================================
             * Генерация структур для вставки
             * ==================================================================*/
            $start = microtime(true);
            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::VOLTAGE, $pack->batteryVoltage, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveLocationPacketSensorParameters. Ошибка сохранения параметра ' . ParamEnum::VOLTAGE);
            }


            //количество услышанных узлов связи меткой (используется для расчета скорости - откидывает координаты меток без расчета)
            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::NEIGHBOUR_COUNT, $total_nodes_heared, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveLocationPacketSensorParameters. Ошибка сохранения параметра ' . ParamEnum::NEIGHBOUR_COUNT);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::CALCULATED, ParamEnum::STATE, 1, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveLocationPacketSensorParameters. Ошибка сохранения параметра ' . ParamEnum::STATE);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::COORD, $xyz, $pack->timestamp, $location_status, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveLocationPacketSensorParameters. Ошибка сохранения параметра ' . ParamEnum::COORD);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::PLACE_ID, $place_id, $pack->timestamp, $location_status, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveLocationPacketSensorParameters. Ошибка сохранения параметра ' . ParamEnum::PLACE_ID);
            }

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::EDGE_ID, $edge_id, $pack->timestamp, $location_status, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveLocationPacketSensorParameters. Ошибка сохранения параметра ' . ParamEnum::EDGE_ID);
            }

            /**=================================================================
             * Вычисление статусов в зависимости от процента заряда
             * ==================================================================*/
            $start = microtime(true);
            //$warnings[] = 'saveLocationPacketSensorParameters. Вычисление и сохранение параметра "Процент заряда батареи светильника"';
            $minerPercent = self::getMinerBatteryPercent(str_replace(',', '.', $pack->batteryVoltage));
            if ($minerPercent < 10) {
                //$warnings[] = 'saveLocationPacketSensorParameters. Значение аварийное';
                $value_status_id = StatusEnumController::EMERGENCY_VALUE;
                $event_status_id = StatusEnumController::EVENT_RECEIVED;
            } else {
                //$warnings[] = 'saveLocationPacketSensorParameters. Значение нормальное';
                $value_status_id = StatusEnumController::NORMAL_VALUE;
                $event_status_id = StatusEnumController::EVENT_ELIMINATED_BY_SYSTEM;
            }
            $warnings[__FUNCTION__ . '. Вычисление статусов в зависимости от процента заряда'] = microtime(true) - $start;

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::MINER_BATTERY_PERCENT, $minerPercent, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveLocationPacketSensorParameters. Ошибка сохранения параметра ' . ParamEnum::MINER_BATTERY_PERCENT);
            }
            $warnings[__FUNCTION__ . '. Генерация структур для вставки'] = microtime(true) - $start;

            /**=================================================================
             * Перемещение сенсора в кеше шахт (если надо) и сохранение параметра шахты
             * ==================================================================*/
            $start = microtime(true);
            SensorMainController::moveSensorMineInitCache($sensor_id, $mine_id);
            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::MINE_ID, $mine_id, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveLocationPacketSensorParameters. Ошибка сохранения параметра ' . ParamEnum::MINE_ID);
            }
            $warnings[__FUNCTION__ . '. Перемещение сенсора в кеше шахт (если надо) и сохранение параметра шахты'] = microtime(true) - $start;

            /**=================================================================
             * Сохранение параметра регистрации для сенсора
             * Если уже в шахте, то писать чекин не нужно
             * ==================================================================*/
            $start = microtime(true);
            //$warnings[] = 'saveLocationPacketSensorParameters. Проверка на нахождение в ламповой';
            $flagRegistration = 1;
            $sensor_main_controller = new SensorMainController(Yii::$app->id, Yii::$app);
            $sensor_checkin_parameter_last_value = $sensor_main_controller->getOrSetParameterValue($sensor_id, 158, 2);
            if ($place_object_id == 80 || (isset($sensor_checkin_parameter_last_value['Items']['value']) && $sensor_checkin_parameter_last_value['Items']['value'] == 1)) {
                //$warnings[] = 'saveLocationPacketSensorParameters. Сенсор находится в ламповой или уже зачекинен, чекин не нужен';
                $flagRegistration = 0;
            }
            // Сохранение параметра, если сенсор не зачекинен и не в ламповой
            if ($flagRegistration) {
                //$warnings[] = 'saveLocationPacketSensorParameters. Сенсор находится не в ламповой, чекиним';

                $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::CHECKIN, '1', $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
                if ($response['status'] == 1) {
                    //$warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    if ($response['date_to_cache']) {
                        $date_to_cache[] = $response['date_to_cache'];
                    }
                    if ($response['date_to_db']) {
                        $date_to_db[] = $response['date_to_db'];
                    }
                } else {
                    //$warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new \Exception('saveLocationPacketSensorParameters. Ошибка сохранения параметра ' . ParamEnum::CHECKIN);
                }
            }
            $warnings[__FUNCTION__ . '. Сохранение параметра чекина'] = microtime(true) - $start;

            /**=================================================================
             * блок массовой вставки значений в БД
             * =================================================================*/
            $start = microtime(true);
            if (isset($date_to_db)) {
//                Yii::$app->db_amicum2->createCommand()->batchInsert('sensor_parameter_value',
//                    ['sensor_parameter_id', 'date_time', 'value', 'status_id'],
//                    $date_to_db)->execute();
                $insert_param_val = Yii::$app->db_amicum2->queryBuilder->batchInsert('sensor_parameter_value', ['sensor_parameter_id', 'date_time', 'value', 'status_id'], $date_to_db);
                Yii::$app->db_amicum2->createCommand($insert_param_val)->execute();
//                Yii::$app->db_amicum2->createCommand($insert_param_val . " ON DUPLICATE KEY UPDATE `value` = VALUES (`value`), `status_id` = VALUES (`status_id`)")->execute();
            }
            $warnings[__FUNCTION__ . '. блок массовой вставки значений в БД'] = microtime(true) - $start;

            /**=================================================================
             * блок массовой вставки значений в кеш
             * =================================================================*/
            $start = microtime(true);
            if (isset($date_to_cache)) {
                //$warnings[] = $sensor_id;
                $ask_from_method = $sensor_cache_controller->multiSetSensorParameterValueHash($date_to_cache);
                if ($ask_from_method['status'] == 1) {
                    //$warnings[] = $ask_from_method['warnings'];
                    //$warnings[] = 'saveLocationPacketSensorParameters. обновил параметры сенсора в кеше';
                } else {
                    //$warnings[] = $ask_from_method['warnings'];
                    $errors[] = $ask_from_method['errors'];
                    throw new \Exception('saveLocationPacketSensorParameters. Не смог обновить параметры в кеше сенсора' . $sensor_id);
                }
            }
            $warnings[__FUNCTION__ . '. блок массовой вставки значений в кеш'] = microtime(true) - $start;

            /**
             * Генерация события для заряда батареи
             */
            $start = microtime(true);
            $event_id = EventEnumController::LAMP_LOW_BATTERY;
            $sensor_obj = $sensor_cache_controller->getSensorMineBySensorOneHash($mine_id, $sensor_id);
            if ($sensor_obj !== false && isset($sensor_obj['object_id']) && $sensor_obj['object_id'] == 159) {
                $event_id = EventEnumController::POS_MARK_LOW_BATTERY;
            }
            $response = EventMainController::createEventFor('sensor', $sensor_id, $event_id, $minerPercent,
                $pack->timestamp, $value_status_id, ParamEnum::MINER_BATTERY_PERCENT, $mine_id,
                $event_status_id, $edge_id, $xyz);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            }
            $warnings[__FUNCTION__ . '. Генерация события для заряда батареи'] = microtime(true) - $start;

            $result[] = 'OK';
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'saveLocationPacketSensorParameters. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }
        //$warnings[] = 'saveLocationPacketSensorParameters. Закончил метод';
        return array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * saveSensorParameterBatch - Функция для создания структуры параметра датчика в кеш и БД - для последующего сохранения с проверкой на изменение параметра
     * @param $sensor_id -   идентификатор сенсора
     * @param int $parameter_type_id идентификатор типа параметра
     * @param int $parameter_id идентификатор параметра
     * @param $value -   новое значение, которое будет записываться
     * @param $datetime -   метка времени, записываемого значения
     * @param $status_id -   статус записываемого значения
     * @param $sensor_parameter_value_cache_array
     * @return array
     */
    public static function saveSensorParameterBatch($sensor_id, $parameter_type_id, $parameter_id, $value, $datetime, $status_id, $sensor_parameter_value_cache_array)
    {

        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей
        $date_to_cache = null;                                                                                          //возвращаемы массив для вставки в кеш одним заходом
        $date_to_db = null;                                                                                             //возвращаемы массив для вставки в БД одним заходом
        $value_database_id = -1;
        // Стартовая отладочная информация
        $log = new LogAmicumFront("saveSensorParameterBatch", true);

        try {
            /**
             * Проверка актуальности значения параметра
             */
            $response = SensorMainController::IsChangeSensorParameterValue($sensor_id, $parameter_id, $parameter_type_id, $value, $datetime, $sensor_parameter_value_cache_array);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new \Exception("Ошибка при проверке актуальности параметра $parameter_id-$parameter_type_id  сенсора $sensor_id");
            }

            if ($response['flag_save']) {
                /**
                 * Получение идентификатора параметра сенсора (из таблицы sensor_parameter)
                 * с предварительной проверкой в кеше, если в кеше нет, то получаем значение из базы, если и там нет, то создаем параметр в бд и получаем его здесь
                 */
                if (isset($sensor_parameter_value_cache_array[$sensor_id][$parameter_type_id][$parameter_id])) {
                    $sensor_parameter_id = $sensor_parameter_value_cache_array[$sensor_id][$parameter_type_id][$parameter_id]['sensor_parameter_id'];
                } else {
                    $response = SensorBasicController::getSensorParameter($sensor_id, $parameter_id, $parameter_type_id);
                    $log->addLogAll($response);
                    if ($response['status'] != 1) {
                        throw new \Exception("Ошибка при получении параметра $parameter_id-$parameter_type_id сенсора $sensor_id");
                    }
                    $sensor_parameter_id = $response['sensor_parameter_id'];
                }

                $date_to_db = array(
                    'sensor_parameter_id' => $sensor_parameter_id,
                    'date_time' => $datetime,
                    'value' => $value,
                    'status_id' => $status_id
                );

                /**
                 * Построение структуры значения параметра в кеш
                 */
                $date_to_cache = SensorCacheController::buildStructureSensorParametersValue($sensor_id, $sensor_parameter_id, $parameter_id, $parameter_type_id, $datetime, $value, $status_id);
            } else {
                $log->addLog("Значение параметра актуально");
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Закончил метод");

        return array_merge(['Items' => $result, 'sensor_parameter_value_id' => $value_database_id, 'date_to_cache' => $date_to_cache, 'date_to_db' => $date_to_db], $log->getLogAll());
    }

    /**
     * Метод для вычисления процента заряда светильника
     * @param double $batteryValue Значение напряжения батареи
     * @return string
     */
    public static function getMinerBatteryPercent($batteryValue)
    {
        if ($batteryValue >= 4.1) return '100';
        if ($batteryValue >= 4) return '80';
        if ($batteryValue >= 3.8) return '60';
        if ($batteryValue >= 3.7) return '40';
        if ($batteryValue >= 3.6) return '20';
        if ($batteryValue >= 3.5) return '10';
        if ($batteryValue === null) return '-1';
        return '0';
    }

    /**
     * Сохраняет параметры воркера, полученные из пакета положения шахтёра.
     * @param $pack
     * @param $worker_sensor
     * @param $mine_id
     * @param $xyz
     * @param $edge_id
     * @param $place_id
     * @param $status_danger_zone
     * @param $place_object_id
     * @param $speed
     * @param int $location_status
     * @return array
     */
    public static function saveLocationPacketWorkerParameters($pack, $worker_sensor, $mine_id, $xyz, $edge_id, $place_id, $status_danger_zone, $place_object_id, $speed, $location_status = 1)
    {
        $method_name = "saveLocationPacketWorkerParameters";
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                                 // количество полученных записей
        $count_save = 0;                                                                                                // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                   // начало выполнения скрипта
        $microtime_current = microtime(true);                                                                 // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(\backend\controllers\Assistant::GetDateNow()));                        // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта
        try {
            //$warnings[] = 'saveLocationPacketWorkerParameters. Сохранение параметров работника';
            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            /**=================================================================
             * Сохранение параметров работника
             * ==================================================================*/
            /**
             * получаем за раз все последние значения по воркеру из кеша
             */
            $worker_cache_controller = new WorkerCacheController();
            //$warnings[] = 'saveLocationPacketWorkerParameters. получаем данные с кеша по всему воркеру';
            $worker_parameter_value_list_cache = $worker_cache_controller->multiGetParameterValueHash($worker_sensor['worker_id'], '*', '*');
            if ($worker_parameter_value_list_cache === false) {
                $worker_parameter_value_cache_array = null;
            } else {
                foreach ($worker_parameter_value_list_cache as $worker_parameter_value_cache) {
                    $worker_parameter_value_cache_array[$worker_parameter_value_cache['worker_id']][$worker_parameter_value_cache['parameter_type_id']][$worker_parameter_value_cache['parameter_id']] = $worker_parameter_value_cache;
                }
                //$warnings[] = 'saveLocationPacketWorkerParameters. получил данные с кеша по всему воркеру';
            }

            /** Отладка */
            $description = 'получил за раз все последние значения по воркеру из кеша';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            $response = self::saveWorkerParameterBatch($worker_sensor['worker_id'], ParameterTypeEnumController::MEASURED, ParamEnum::MINE_ID, $mine_id, $pack->timestamp, StatusEnumController::ACTUAL, $worker_parameter_value_cache_array);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveLocationPacketWorkerParameters. Ошибка сохранения параметра ' . ParamEnum::MINE_ID);
            }

            $response = self::saveWorkerParameterBatch($worker_sensor['worker_id'], ParameterTypeEnumController::MEASURED, ParamEnum::SURFACE_MOVING, $pack->surfaceFlag, $pack->timestamp, StatusEnumController::ACTUAL, $worker_parameter_value_cache_array);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveLocationPacketWorkerParameters. Ошибка сохранения параметра ' . ParamEnum::SURFACE_MOVING);
            }

            $response = self::saveWorkerParameterBatch($worker_sensor['worker_id'], ParameterTypeEnumController::MEASURED, ParamEnum::COORD, $xyz, $pack->timestamp, $location_status, $worker_parameter_value_cache_array);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveLocationPacketWorkerParameters. Ошибка сохранения параметра ' . ParamEnum::COORD);
            }

            $response = self::saveWorkerParameterBatch($worker_sensor['worker_id'], ParameterTypeEnumController::MEASURED, ParamEnum::EDGE_ID, $edge_id, $pack->timestamp, $location_status, $worker_parameter_value_cache_array);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveLocationPacketWorkerParameters. Ошибка сохранения параметра ' . ParamEnum::EDGE_ID);
            }

            /** Отладка */
            $description = 'Подготовил параметры для сохранения';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            /**=================================================================
             * Сохранение скорости движения человека и генерация события,
             * если параметр расчитывался
             * ==================================================================*/
            if ($speed['speed_value'] > -1) {
                if ($speed['speed_value'] > 3.5) {
                    //$warnings[] = 'saveLocationPacketWorkerParameters. Сохранение скорости человека с генерацией события';
                    $value_status_id = StatusEnumController::EMERGENCY_VALUE;
                    $event_status_id = StatusEnumController::EVENT_RECEIVED;
                } else {
                    //$warnings[] = 'saveLocationPacketWorkerParameters. Сохранение скорости человека без генерации события';
                    $value_status_id = StatusEnumController::NORMAL_VALUE;
                    $event_status_id = StatusEnumController::EVENT_ELIMINATED_BY_SYSTEM;
                }

                $response = self::saveWorkerParameterBatch($worker_sensor['worker_id'], ParameterTypeEnumController::CALCULATED, ParamEnum::WORKER_SPEED, $speed['speed_value'], $pack->timestamp, StatusEnumController::ACTUAL, $worker_parameter_value_cache_array);
                if ($response['status'] == 1) {
                    //$warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    if ($response['date_to_cache']) {
                        $date_to_cache[] = $response['date_to_cache'];
                    }
                    if ($response['date_to_db']) {
                        $date_to_db[] = $response['date_to_db'];
                    }
                } else {
                    //$warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new \Exception('saveLocationPacketWorkerParameters. Ошибка сохранения параметра ' . ParamEnum::WORKER_SPEED);
                }
                if ($speed['generate_event'] == true and $speed['speed_value'] < 5.5) {
                    $response = EventMainController::createEventFor('worker', $worker_sensor['worker_id'], EventEnumController::MOVEMENT_ON_CONVEYOR, $speed['speed_value'],
                        $pack->timestamp, $value_status_id, ParamEnum::WORKER_SPEED, $mine_id,
                        $event_status_id, $edge_id, $xyz);
                    if ($response['status'] == 1) {
                        //$warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                    } else {
                        //$warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                    }
                }
                // Если есть превышение возможной скорости, то вызываем метод
                // остановки конвейера
                if ($value_status_id == StatusEnumController::EMERGENCY_VALUE) {
                    $response = self::stopConveyor($mine_id, $edge_id);
                    if ($response['status'] == 1) {
                        //$warnings[] = $response['warnings'];
                    } else {
                        $errors[] = $response['errors'];
                    }
                }
            }

            /** Отладка */
            $description = 'Скорость и конвейра - обработал';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            /**=================================================================
             * ----Обработка параметра "Человек без движения"----
             * ==================================================================*/
            //$warnings[] = 'saveLocationPacketWorkerParameters. Обработка параметра "Человек без движения"';
            $flag_generate_event_witout_moving = 0;                                                                     // разрешение на генерацию события без движения
            $duration_not_moving_to_save = 0;                                                                           // продолжительность без движения для записи ИТОГОВАЯ
            $status_emergency_value_to_save = StatusEnumController::NORMAL_VALUE;                                                 // статус переменной с учетом аварийности нормальности ИТОГОВАЯ
            $event_status_id_to_save = StatusEnumController::EVENT_RECEIVED;                                                      // статус события в системе (получено, снято системой)

            // проверяем, не находиться ли человек в ламповой
            if ($place_object_id == 80) {
                $warnings[] = 'saveLocationPacketWorkerParameters. Метка в ламповой, контроль без движения не осуществляется';
            } else {
                // проверяем пришедший статус - в движении, без движения,
                // если пришел в движении и предыдущий был в движении, проходим мимо
                $duration_witout_moving = (new WorkerCacheController())->getParameterValueHash($worker_sensor['worker_id'], ParamEnum::DURATION_WITHOUT_MOVING, ParameterTypeEnumController::CALCULATED);
                $last_not_moving = (new WorkerCacheController())->getParameterValueHash($worker_sensor['worker_id'], ParamEnum::NOT_MOVING, ParameterTypeEnumController::MEASURED);

                $actual_time_not_moving = true;                                                                         // актуальность данных для расчета в движении/без движения
                $duration_actual_time_not_moving = 0;                                                                   // продолжительность нахождения без движения человека

                // считаем актуальность полученных данных и старых данных
                // если с момента получения новых данных от старых прошло больше 3 минут, то данные откидываются и начинаем считать с нуля
                if ($last_not_moving and isset($last_not_moving['date_time'])) {
                    $duration_actual_time_not_moving = Assistant::GetMysqlTimeDifference($pack->timestamp, $last_not_moving['date_time']);
                    if ($duration_actual_time_not_moving > 180) {
                        $actual_time_not_moving = false;
                        $warnings[] = 'saveLocationPacketWorkerParameters. Данные не актуальны, Просрочка больше 3 минут: ' . $duration_actual_time_not_moving;
                        $warnings[] = 'saveLocationPacketWorkerParameters. Данные не актуальны, Пакет: ' . $pack->timestamp;
                        $warnings[] = 'saveLocationPacketWorkerParameters. Данные не актуальны, Кеш: ' . $last_not_moving['date_time'];
                    } else {
                        $warnings[] = 'saveLocationPacketWorkerParameters. Данные актуальны, Просрочки нет: ' . $duration_actual_time_not_moving;
                    }
                    if ($duration_witout_moving) {
                        $duration_actual_time_not_moving += $duration_witout_moving['value'];
                    }
                } else {
                    $warnings[] = 'saveLocationPacketWorkerParameters. Нет последних данных в кеше';
                }

                // если данные не актуальны, то обнуляем продолжительность и делаем запись в сводную о старой продолжительности
                if (!$actual_time_not_moving) {
                    $warnings[] = 'saveLocationPacketWorkerParameters. Данные не актуальны, начинаем расчет с нуля';

                    if ($duration_witout_moving and $duration_witout_moving['value'] > 780) {
                        $warnings[] = 'saveLocationPacketWorkerParameters. Пишу в сводную о событии. Пусть и старом.';
                        self::addWorkerMotionLessRecord($worker_sensor['worker_id'], 'Stationary', $pack->timestamp, $place_id, $duration_witout_moving['value'], $mine_id);
                    } else {
                        $warnings[] = 'saveLocationPacketWorkerParameters. Стартовой продолжительности не было';
                    }

                    $status_emergency_value_to_save = StatusEnumController::EMERGENCY_VALUE;
                    $duration_not_moving_to_save = 0;
                } // если остается без движения, и время уже больше 15 минут, то генериуем сообщение и пишем продолжительность в БД, в сводную не записываем
                else if ($last_not_moving and $last_not_moving['value'] === 'Stationary' and $pack->movingFlag === 'Stationary') {
                    $warnings[] = 'saveLocationPacketWorkerParameters. остается без движения';

                    $status_emergency_value_to_save = StatusEnumController::EMERGENCY_VALUE;
                    $duration_not_moving_to_save = $duration_actual_time_not_moving;

                    if ($duration_actual_time_not_moving > 900) {
                        $warnings[] = 'saveLocationPacketWorkerParameters. Продолжительность > 15 минут';

                        $flag_generate_event_witout_moving = 1;
                        $event_status_id_to_save = StatusEnumController::EVENT_RECEIVED;
                    }
                }// если двигался и остановился
                else if ($last_not_moving and $last_not_moving['value'] === 'Moving' and $pack->movingFlag === 'Stationary') {
                    $warnings[] = 'saveLocationPacketWorkerParameters. Остановился после движения';

                    $status_emergency_value_to_save = StatusEnumController::EMERGENCY_VALUE;
                    $duration_not_moving_to_save = 0;
                }
                // если пришел пакет в движении, было до этого без движения и данные актуальны
                // то делаем проверку на продолжительность,
                // если продолжительность больше 15 минут, то генерируем событие и пишем данные в кеш и в бд, а продолжительность обнуляем
                // для исключения дублирования данных
                else if ($pack->movingFlag === 'Moving' and $last_not_moving and $last_not_moving['value'] === 'Stationary' and $actual_time_not_moving) {
                    $warnings[] = 'saveLocationPacketWorkerParameters. Данные актуальны, смена с без движения на в движении. Проверка на продолжительность';
                    if ($duration_actual_time_not_moving > 900) {
                        $warnings[] = 'saveLocationPacketWorkerParameters. Продолжительность > 15 минут. Пишу в сводную о без движении';

                        self::addWorkerMotionLessRecord($worker_sensor['worker_id'], 'Stationary', $pack->timestamp, $place_id, $duration_actual_time_not_moving, $mine_id);
                        $flag_generate_event_witout_moving = 1;
                        $status_emergency_value_to_save = StatusEnumController::EMERGENCY_VALUE;
                        $event_status_id_to_save = StatusEnumController::EVENT_RECEIVED;
                        $duration_not_moving_to_save = 0;
                    } else {
                        $warnings[] = 'saveLocationPacketWorkerParameters. Продолжительность < 15 минут';

                        $status_emergency_value_to_save = StatusEnumController::EMERGENCY_VALUE;
                        $duration_not_moving_to_save = $duration_actual_time_not_moving;
                    }
                }
                // если пришел в движении и предыдущий был в движении,
                // то пишем, в кеш и в БД текущий статус без движения и генерируем нормальное событие
                else if ($pack->movingFlag === 'Moving' and (!$last_not_moving or $last_not_moving['value'] === 'Moving')) {
                    $warnings[] = 'saveLocationPacketWorkerParameters. Все норм. был в движении и продолжает двигаться';

                    $flag_generate_event_witout_moving = 1;
                    $status_emergency_value_to_save = StatusEnumController::NORMAL_VALUE;
                    $event_status_id_to_save = StatusEnumController::EVENT_ELIMINATED_BY_SYSTEM;
                    $duration_not_moving_to_save = 0;
                }

                $warnings[] = 'saveLocationPacketWorkerParameters. Итоговый результат расчета без движения: ';
                $warnings[] = 'saveLocationPacketWorkerParameters. Разрешение на генерацию события: ' . $flag_generate_event_witout_moving;
                $warnings[] = 'saveLocationPacketWorkerParameters. Статус переменной/значения: ' . $status_emergency_value_to_save;
                $warnings[] = 'saveLocationPacketWorkerParameters. Статус события: ' . $event_status_id_to_save;
                $warnings[] = 'saveLocationPacketWorkerParameters. Продолжительность без движения: ' . $duration_not_moving_to_save;

                $response = self::saveWorkerParameterBatch($worker_sensor['worker_id'], ParameterTypeEnumController::MEASURED, ParamEnum::NOT_MOVING, $pack->movingFlag, $pack->timestamp, StatusEnumController::EMERGENCY_VALUE, $worker_parameter_value_cache_array);
                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    if ($response['date_to_cache']) {
                        $date_to_cache[] = $response['date_to_cache'];
                    }
                    if ($response['date_to_db']) {
                        $date_to_db[] = $response['date_to_db'];
                    }
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new \Exception('saveLocationPacketWorkerParameters. Ошибка сохранения параметра ' . ParamEnum::NOT_MOVING);
                }

                $response = self::saveWorkerParameterBatch($worker_sensor['worker_id'], ParameterTypeEnumController::CALCULATED, ParamEnum::DURATION_WITHOUT_MOVING, $duration_not_moving_to_save, $pack->timestamp, StatusEnumController::ACTUAL, $worker_parameter_value_cache_array);
                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    if ($response['date_to_cache']) {
                        $date_to_cache[] = $response['date_to_cache'];
                    }
                    if ($response['date_to_db']) {
                        $date_to_db[] = $response['date_to_db'];
                    }
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new \Exception('saveLocationPacketWorkerParameters. Ошибка сохранения параметра ' . ParamEnum::DURATION_WITHOUT_MOVING);
                }

                // если есть разрешение на генерацию события без движения нормального или аварийного
                if ($flag_generate_event_witout_moving) {
                    $response = EventMainController::createEventFor('worker', $worker_sensor['worker_id'], EventEnumController::WORKER_NOT_MOVING, $pack->movingFlag,
                        $pack->timestamp, $status_emergency_value_to_save, ParamEnum::NOT_MOVING, $mine_id,
                        $event_status_id_to_save, $edge_id, $xyz);
                    if ($response['status'] == 1) {
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                    } else {
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                    }
                }
            }

            /** Отладка */
            $description = 'Обработал без движения';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            /**=================================================================
             * Добавление записи в отчётную таблицу worker_collection.
             * Для отчёта "История местоположения персонала и транспорта"
             * ==================================================================*/
            $response = WorkerMainController::addWorkerCollection($worker_sensor['worker_id'], $status_danger_zone, $place_id, $pack->timestamp);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = 'saveLocationPacketWorkerParameters. Не удалось сохранить данные в отчётную таблицу worker_collection';
                $errors[] = $response['errors'];
                //throw new \Exception('saveLocationPacketWorkerParameters. Ошибка сохранения параметра 356');
            }
            /** Отладка */
            $description = 'Записал воркер коллекшен';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            /**=================================================================
             * Сохранение идентификатора плейса.
             * При статусе зоны - запретная, создается событие
             * ==================================================================*/
            //$warnings[] = 'saveLocationPacketWorkerParameters. Вычисление статуса запретной зоны';
            if ($status_danger_zone === StatusEnumController::FORBIDDEN) {
                //$warnings[] = 'saveLocationPacketWorkerParameters. Сохранение запретной зоны с генерацией события';
                $value_status_id = StatusEnumController::EMERGENCY_VALUE;
                $event_status_id = StatusEnumController::EVENT_RECEIVED;
            } else {
                //$warnings[] = 'saveLocationPacketWorkerParameters. Сохранение запретной зоны без генерации события';
                $value_status_id = StatusEnumController::NORMAL_VALUE;
                $event_status_id = StatusEnumController::EVENT_ELIMINATED_BY_SYSTEM;
            }

            $response = self::saveWorkerParameterBatch($worker_sensor['worker_id'], ParameterTypeEnumController::MEASURED, ParamEnum::PLACE_ID, $place_id, $pack->timestamp, $status_danger_zone, $worker_parameter_value_cache_array);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveLocationPacketWorkerParameters. Ошибка сохранения параметра ' . ParamEnum::PLACE_ID);
            }

            $response = EventMainController::createEventFor('worker', $worker_sensor['worker_id'], EventEnumController::WORKER_DANGER_ZONE, $place_id,
                $pack->timestamp, $value_status_id, ParamEnum::PLACE_ID, $mine_id,
                $event_status_id, $edge_id, $xyz);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            }

            /** Отладка */
            $description = 'Запретная зона - закончил';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            /**=================================================================
             * Сохранение параметра сигнал SOS.
             * ==================================================================*/
            //$warnings[] = 'saveLocationPacketWorkerParameters. Вычисление статуса сигнала SOS';
            if ($pack->alarmFlag == 1) {
                //$warnings[] = 'saveLocationPacketWorkerParameters. Сохранение SOS с генерацией события';
                $value_status_id = StatusEnumController::EMERGENCY_VALUE;
                $event_status_id = StatusEnumController::EVENT_RECEIVED;
            } else {
                //$warnings[] = 'saveLocationPacketWorkerParameters. Сохранение SOS без генерации события';
                $value_status_id = StatusEnumController::NORMAL_VALUE;
                $event_status_id = StatusEnumController::EVENT_ELIMINATED_BY_SYSTEM;

            }

            $response = self::saveWorkerParameterBatch($worker_sensor['worker_id'], ParameterTypeEnumController::MEASURED, ParamEnum::SOS, $pack->alarmFlag, $pack->timestamp, StatusEnumController::ACTUAL, $worker_parameter_value_cache_array);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('saveLocationPacketWorkerParameters. Ошибка сохранения параметра ' . ParamEnum::SOS);
            }

            $response = EventMainController::createEventFor('worker', $worker_sensor['worker_id'], EventEnumController::SOS, $pack->alarmFlag,
                $pack->timestamp, $value_status_id, ParamEnum::SOS, $mine_id,
                $event_status_id, $edge_id);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $status = $response['status'];
            }

            /** Отладка */
            $description = 'Сос - закончил';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            /**=================================================================
             * Сохранение параметра чекина
             * ==================================================================*/
            //$warnings[] = 'saveLocationPacketWorkerParameters. Проверка на нахождение в ламповой';
            $flagRegistration = 1;
            $flag_zapret_registration = true;      // по умолчанию запрет на регистрацию в шахте
            $worker_checkin_parameter_last_value = WorkerMainController::getWorkerParameterLastValue($worker_sensor['worker_id'], ParamEnum::CHECKIN, ParameterTypeEnumController::MEASURED);
            $check_posibility_registration = self::getCheckPossibilityRegistration($worker_sensor['worker_id'], $pack->timestamp);
            if ($check_posibility_registration['status'] == 1) {
                $flag_zapret_registration = !$check_posibility_registration['posibility_registration_status'];
            }
            if ($worker_checkin_parameter_last_value['value'] == 1 || $place_object_id == 80 || $flag_zapret_registration) {
                $warnings[] = 'saveLocationPacketWorkerParameters. $worker_checkin_parameter_last_value[value]: ' . $worker_checkin_parameter_last_value['value'];
                $warnings[] = 'saveLocationPacketWorkerParameters. $place_object_id: ' . $place_object_id;
                $warnings[] = 'saveLocationPacketWorkerParameters. $flag_zapret_registration: ' . $flag_zapret_registration;
                $warnings[] = 'saveLocationPacketWorkerParameters. Воркер был зарегистрирован';

                $flagRegistration = 0;
            }

            // Сохранение параметра, если воркер не зачекинен и не в ламповой
            if ($flagRegistration) {
                //$warnings[] = 'saveLocationPacketWorkerParameters. Воркер находится не в ламповой, чекиним';

                $response = self::saveWorkerParameterBatch($worker_sensor['worker_id'], ParameterTypeEnumController::MEASURED, ParamEnum::CHECKIN, 1, $pack->timestamp, StatusEnumController::FORCED, $worker_parameter_value_cache_array);
                if ($response['status'] == 1) {
                    //$warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    if ($response['date_to_cache']) {
                        $date_to_cache[] = $response['date_to_cache'];
                    }
                    if ($response['date_to_db']) {
                        $date_to_db[] = $response['date_to_db'];
                    }
                } else {
                    //$warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new \Exception('saveLocationPacketWorkerParameters. Ошибка сохранения параметра ' . ParamEnum::CHECKIN);
                }
                // Добавление записи в таблицу о том, что воркер зарегался без пакета чекина
                self::insertRowWorkerRegisteredWithoutCheckin($pack->timestamp, $pack->networkId, $worker_sensor['worker_id']);
            }

            /** Отладка */
            $description = 'Чекин закончил';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            /**=================================================================
             * блок массовой вставки значений в БД
             * =================================================================*/
            if (isset($date_to_db)) {
//                Yii::$app->db_amicum2->createCommand()->batchInsert('worker_parameter_value',
//                    ['worker_parameter_id', 'date_time', 'value', 'status_id', 'shift', 'date_work'],
//                    $date_to_db)->execute();
                $insert_param_val = Yii::$app->db_amicum2->queryBuilder->batchInsert('worker_parameter_value', ['worker_parameter_id', 'date_time', 'value', 'status_id', 'shift', 'date_work'], $date_to_db);
//                Yii::$app->db_amicum2->createCommand($insert_param_val)->execute();
                Yii::$app->db_amicum2->createCommand($insert_param_val . " ON DUPLICATE KEY UPDATE `value` = VALUES (`value`), `status_id` = VALUES (`status_id`), `shift` = VALUES (`shift`), `date_work` = VALUES (`date_work`)")->execute();
//                $warnings[]=$date_to_db;
            }
            /** Отладка */
            $description = 'Закончил массовую вставку в БД';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            /**
             * блок массовой вставки значений в кеш
             */
            if (isset($date_to_cache)) {
                $start = microtime(true);

                $check_date_time_in_cache = self::checkExpiredPackage($worker_sensor['worker_id'], $pack->timestamp, 83, 2, $worker_parameter_value_cache_array);
                if ($check_date_time_in_cache['status'] == 1) {
                    if ($check_date_time_in_cache['move_to_cache'] == true) {
                        //$warnings[] = $worker_sensor['worker_id'];
                        $ask_from_method = (new WorkerCacheController)->multiSetWorkerParameterValueHash($date_to_cache, $worker_sensor['worker_id']);
                        if ($ask_from_method['status'] == 1) {
                            //$warnings[] = $ask_from_method['warnings'];
                            //$warnings[] = 'saveLocationPacketWorkerParameters. обновил параметры работника в кеше';
                        } else {
                            //$warnings[] = $ask_from_method['warnings'];
                            $errors[] = $ask_from_method['errors'];
                            throw new \Exception('saveLocationPacketWorkerParameters. Не смог обновить параметры в кеше работника' . $worker_sensor['worker_id']);
                        }
                    }
                }
                $warnings[__FUNCTION__ . '. checkExpiredPackage блок массовой вставки значений в кеш'] = microtime(true) - $start;
            }

            /** Отладка */
            $description = 'Массовая вставка в кеш';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            /**
             * Сохранение в кеш зачекиненных, если воркер не в ламповой
             */
            if ($flagRegistration) {
                (new WorkerCacheController())->initWorkerMineHash($mine_id, $worker_sensor['worker_id']);
                //$warnings[] = 'saveLocationPacketWorkerParameters. Воркер ' . $worker_sensor['worker_id'] . ' сохранен в кеш зачекиненных';
            }
            /** Отладка */
            $description = 'Инициализация кеша в работнике если его там не было: ' . $flagRegistration;                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            //$warnings[] = 'saveLocationPacketWorkerParameters. Конец метода сохранения параметров воркера';
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'saveLocationPacketWorkerParameters. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }
        /** Отладка */
        $description = 'Окончание выполнения метода';                                                                      // описание текущей отладочной точки
        $description = $method_name . ' ' . $description;
        $warnings[] = $description;                                                                                     // описание текущей отладочной точки
        $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
        $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
        $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
        $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
        $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
        $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
        $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
        $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                           // количество обработанных записей
        $microtime_current = microtime(true);
        /** Окончание отладки */

        //$warnings[] = 'saveLocationPacketWorkerParameters. Закончил метод';
        return array('Items' => $result, 'status' => $status, 'debug' => $debug,
            'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Функция для сохранения параметра воркера в кеш и БД.
     *
     * Возвращает массив вида:
     * [
     *  'Items',
     *  'status',
     *  'errors',
     *  'warnings',
     *  'worker_parameter_value_id'
     * ]
     *
     * @param int $worker_id идентификатор работника
     * @param int $parameter_type_id идентификатор типа параметра
     * @param int $parameter_id идентификатор параметра
     * @param $value -   новое значение, которое будет записываться
     * @param $datetime -   метка времени, записываемого значения
     * @param $status_id -   статус записываемого значения
     * @return array Идентификатор значения параметра, сохраненного в базе. false в случае ошибки
     */
    public static function saveWorkerParameterBatch($worker_id, $parameter_type_id, $parameter_id, $value, $datetime, $status_id, $worker_parameter_value_cache_array = null)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("saveWorkerParameterBatch");

        $date_to_cache = null;                                                                                          //возвращаемы массив для вставки в кеш одним заходом
        $date_to_db = null;                                                                                             //возвращаемы массив для вставки в БД одним заходом
        $value_database_id = -1;
        try {
            $log->addLog("Начало выполнения метода");
            /**=================================================================
             * Проверка актуальности значения параметра
             * ==================================================================*/
            $response = WorkerMainController::IsChangeWorkerParameterValue($worker_id, $parameter_id, $parameter_type_id, $value, $datetime, $worker_parameter_value_cache_array);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new \Exception("Ошибка при проверке актуальности параметра $parameter_id-$parameter_type_id  воркера " . $worker_id);
            }

            if ($response['flag_save']) {
                /**=============================================================
                 * Получение идентификатор параметра воркера из таблицы worker_parameter
                 * ==============================================================*/
                $response = WorkerMainController::getOrSetWorkerParameter($worker_id, $parameter_id, $parameter_type_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception("Ошибка при получении параметра $parameter_id-$parameter_type_id воркера " . $worker_id);
                }
                $worker_parameter_id = $response['worker_parameter_id'];

                /**=============================================================
                 * Сохранение значения в БД
                 * ==============================================================*/
                $shift_info = self::getShiftDateNum($datetime);

                $date_to_db = array(
                    'worker_parameter_id' => $worker_parameter_id,
                    'date_time' => $datetime,
                    'value' => $value,
                    'status_id' => $status_id,
                    'shift' => $shift_info['shift_num'],
                    'date_work' => $shift_info['shift_date']
                );

                /**=============================================================
                 * Сохранение значения в кеш
                 * ==============================================================*/

                $date_to_cache = WorkerCacheController::buildStructureWorkerParametersValue($worker_id, $worker_parameter_id, $parameter_id, $parameter_type_id, $datetime, $value, $status_id);

            } else {
                $log->addLog("Значение параметра актуально");
            }
            $log->addLog("Окончил выполнение метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog('Конец метода ' . $parameter_id);

        return array_merge(['Items' => $result, 'worker_parameter_value_id' => $value_database_id,
            'date_to_cache' => $date_to_cache, 'date_to_db' => $date_to_db], $log->getLogAll());
    }

    /**
     * Функция остановки конвейера
     * @param int $mine_id идентификатор шахты
     * @param int $edge_id идентификатор выработки
     * @return array
     */
    public static function stopConveyor($mine_id, $edge_id)
    {
        $status = 1;
        $errors = array();
        $warnings = array();
        $result = array();

        //$warnings[] = 'stopConveyor. Начало метода';

        try {
            /**
             * Нахождение конвейера на выработке
             */
            $edge_info = EdgeMainController::getEdgeMineDetail($mine_id, $edge_id);
            if ($edge_info && $edge_info['edge_info']) {
                $conveyor = $edge_info['edge_info']['conveyor'];
                $conveyor_tag = $edge_info['edge_info']['conveyor_tag'];
                //$warnings[] = "stopConveyor. На выработке $edge_id есть конвейер с тегом $conveyor_tag";
            } else {
                $warnings[] = $edge_info['warnings'];
                $errors[] = $edge_info['errors'];
                throw new \Exception('stopConveyor. Ошибка при получении информации о выработке');
            }

            if ($conveyor == 1) {
                $opc_info = (new Query())
                    ->select([
                        'sensor_parameter.sensor_id as opc_id',
                        'parameter.title as tag_name',
                        'sensor_parameter.parameter_id as tag_parameter_id'
                    ])
                    ->from('parameter')
                    ->innerJoin('sensor_parameter', 'parameter.id = sensor_parameter.parameter_id')
                    ->where([
                        'parameter.title' => $conveyor_tag,
                        'sensor_parameter.parameter_type_id' => ParameterTypeEnumController::MEASURED
                    ])
                    ->all();
                $errors[] = $opc_info;

                if ($opc_info) {
                    foreach ($opc_info as $item) {
                        $response = OpcController::writeTagOnServer($item['opc_id'], $item['tag_name'], 0, $item['tag_parameter_id'], ParameterTypeEnumController::MEASURED);
                        if ($response['status'] == 1) {
                            //$warnings[] = $response['warnings'];
                        } else {
                            $errors[] = $response['errors'];
                        }
                    }
                } else {
                    throw new \Exception("stopConveyor. Не найден тег $conveyor_tag в БД. Метод остановки конвейера не вызывался");
                }
            } else {
                throw new \Exception("stopConveyor. На выработке $edge_id нет конвейера");
            }

        } catch (Throwable $exception) {
            $errors[] = 'stopConveyor. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }

        //$warnings[] = 'stopConveyor. Конец метода';

        return array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Метод для добавления записи в отчетную таблицу worker_motion_less
     * @param $worker_id -   Идентификатор работника
     * @param $value -   Значение параметра "Человек без движения"
     * @param $date_time -   Дата получения значения
     * @param $place_id -   Идентификатор местоположения
     * @param $stationary_time -   Время нахождения без движения
     * @throws yii\db\Exception        -   При ошибке записи в таблицу
     */
    public static function addWorkerMotionLessRecord($worker_id, $value, $date_time, $place_id, $stationary_time, $mine_id = AMICUM_DEFAULT_MINE)
    {
        // Получение информации о дате и номере смены
        $shift_info = self::getShiftDateNum($date_time);

        // Получение наименования места
        $place_title = (new Query())
            ->select('title')
            ->from('place')
            ->where([
                'id' => $place_id
            ])
            ->scalar();
        if (!$place_title) $place_title = -1;

        // Получение информации о работнике
        $worker_info = (new Query())
            ->select([
                'tabel_number',
                'FIO',
                'department_title',
                'position_title',
                'department_id',
                'company_id',
                'company_title'
            ])
            ->from('view_worker_employee_info')
            ->where([
                'worker_id' => $worker_id
            ])
            ->one();

        if ($worker_info) {
            // Добавление записи в таблицу worker_motion_less
            $response = HandbookDepartmentController::GetAllParentsCompaniesWithCompany($worker_info['company_id']);
            if ($response['status'] == 1) {
                $copmany_path = $response['Items'];
            } else {
                $copmany_path = $worker_info['company_title'];
            }
            Yii::$app->db_amicum2->createCommand()
                ->insert('worker_motion_less', [
                    'date_work' => $shift_info['shift_date'],
                    'tabel_number' => $worker_info['tabel_number'],
                    'fio' => $worker_info['FIO'] . ' (' . $worker_info['position_title'] . ')',
                    'value' => $value,
                    'mine_id' => $mine_id,
                    'title_department' => $copmany_path,
                    'title_place' => $place_title,
                    'date_time' => $date_time,
                    'smena' => $shift_info['shift_num'],
                    'department_id' => $worker_info['department_id'],
                    'unmotion_time' => Assistant::SecondsToTime($stationary_time)
                ])->execute();
        }
    }

    // isWorkerDataUpdateNoTime - проверка на наличие данных в кеше, если нет, то проиниицализирует (проверит в БД), и если совсем нет, то вернет false, иначе вернет значение

    /**
     * Метод проверяет возможность регистрациии работника в шахте в случае если он не был зарегистрирован там ранее
     * @param $timestamp - метка времени из пакета
     * @param $worker_id
     * @return array
     */
    public static function getCheckPossibilityRegistration($worker_id, $date_time)
    {
        $log = new LogAmicumFront("getCheckPossibilityRegistration");
        $posibility_registration_status = false;

        $down_value = 0;
        try {
            $log->addLog('Начало выполнения метода');
            $worker_cache_controller = new WorkerCacheController();
            $worker_parameter_value_list_cache = $worker_cache_controller->getParameterValueHash($worker_id, 158, 2);
            if ($worker_parameter_value_list_cache) {
                $down_value = $worker_parameter_value_list_cache['value'];
            }

            if (!$down_value or $down_value == "0") {                                                                   // если человек не в шахте
                $log->addLog("Человек не в шахте (взял с кеша)");

                $time_from_cache = strtotime($worker_parameter_value_list_cache['date_time']);                          // получаем последнюю метку времени в кеше
                $time_package = strtotime($date_time);

                if ($time_package < $time_from_cache) {                                                                 // если время в пакете меньше времени в кеше, то это считается не новый пакет и мы не добавляем его в кеш
                    $log->addLog("Старый пакет");
                    $posibility_registration_status = false;
                } else {
                    $log->addLog("Пакет новый");                                                                // иначе отнимаем от времени в пакете время которая в кеше

                    $diff_time = $time_package - $time_from_cache;                                                      // если остаток больше или ровно 10 минут, то можно добавить этого человека в кеш (снова регистрировать)
                    if ($diff_time >= 600) {
                        $log->addLog("Больше часа с последней регистрации регистрировать можно");
                        $posibility_registration_status = true;
                    } else {
                        $log->addLog("Регистрировать нельзя - мало времени с последней регистрации: " . $diff_time);
                        $log->addLog("time_package: " . $time_package);
                        $log->addLog("date_time: " . $date_time);
                        $log->addLog("worker_parameter_value_list_cache: " . $worker_parameter_value_list_cache['date_time']);
                        $log->addLog("time_from_cache: " . $time_from_cache);
                    }
                }
            } else {
                $log->addLog("человек в шахте (взял с кеша)");
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog('Окончание выполнения метода');

        return array_merge(['Items' => [], 'posibility_registration_status' => $posibility_registration_status], $log->getLogAll());
    }

    /**
     * Добавляет новую строку в таблицу worker_registered_without_checkin.
     *
     * @details Таблица содержит сведения о воркерах, которых зарегистрировало
     * в шахте без получения пакета чекина. То есть пришёл сразу пакет
     * координат
     *
     * @param string $date_time дата и время регистрации воркера
     * @param int $sensor_network_id сетевой идентификатор сенсора, к которому
     * привязан воркер
     * @param int $worker_id идентификатор воркера
     * @throws \yii\db\Exception
     */
    public static function insertRowWorkerRegisteredWithoutCheckin($date_time, $sensor_network_id, $worker_id)
    {
        Yii::$app->db_amicum2->createCommand()->insert('worker_registered_without_checkin', [
            'date_time' => $date_time,
            'sensor_network_id' => $sensor_network_id,
            'worker_id' => $worker_id
        ])->execute();
    }

    /**
     * Метод проверяет просроченность пакета в Кеше. Если в Кеше время новее чем пришедшее с пакета, то в кеш не кладем
     * @param $timestamp - метка времне из пакета
     * @param $worker_id
     * @return array
     */
    public static function checkExpiredPackage($worker_id, $date_time, $parameter_id, $parameter_type_id, $worker_parameter_value_cache_array)
    {
        $log = new LogAmicumFront("checkExpiredPackage");

        $move_to_cache = true;

        try {
            $log->addLog('Начало выполнения метода');
            if (isset($worker_parameter_value_cache_array[(int)$worker_id][$parameter_type_id][$parameter_id])) {
                $worker_parameter_value_list_cache = $worker_parameter_value_cache_array[(int)$worker_id][$parameter_type_id][$parameter_id];
                $log->addLog('Получал из присланных данных');
            } else {
                $worker_cache_controller = new WorkerCacheController();
                $worker_parameter_value_list_cache = $worker_cache_controller->getParameterValueHash($worker_id, $parameter_id, $parameter_type_id);
                $log->addLog('Получал напрямую с кеша');
            }
            if ($worker_parameter_value_list_cache) {
                $down_value = $worker_parameter_value_list_cache['value'];
                $time_from_cache = strtotime($worker_parameter_value_list_cache['date_time']);
                $time_package = strtotime($date_time);
                //если время в пакете меньше времени в кеше, то это считается не новый пакет и мы не добавляем его в кеш
                if ($time_package < $time_from_cache) {
                    $move_to_cache = false;
                    $log->addLog('Данные просрочены');
                }
                $log->addData($worker_parameter_value_list_cache['date_time'], "Время в кеше", __LINE__);
                $log->addData($date_time, "Время пакета", __LINE__);
            }

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }

        $log->addLog('Окончание выполнения метода');

        return array_merge(['Items' => [], 'move_to_cache' => $move_to_cache], $log->getLogAll());
    }

    /**
     * Сохраняет параметры оборудования, полученные из пакета положения шахтёра.
     * @param MinerNodeLocation $pack Объект пакета положения шахтёра
     * @param array $equipment_sensor Массив с данными о привязке оборудования к сенсору
     * @param $xyz
     * @param $edge_id
     * @param $place_id
     * @param $status_danger_zone
     * @param $mine_id
     * @return array
     */
    public static function saveLocationPacketEquipmentParameters(MinerNodeLocation $pack, $equipment_sensor, $xyz, $edge_id,
                                                                                   $place_id, $status_danger_zone, $mine_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();

        try {
            //$warnings[] = 'saveLocationPacketEquipmentParameters. Начало метода';
            // Сохранение координат
            $response = self::SaveEquipmentParameter(
                $equipment_sensor['equipment_id'],
                ParameterTypeEnumController::MEASURED, ParamEnum::COORD,
                $xyz,
                $pack->timestamp,
                1
            );
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
            } else {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                throw new \Exception('Ошибка сохранения координат оборудования');
            }
            // Сохранение выработки
            self::SaveEquipmentParameter(
                $equipment_sensor['equipment_id'],
                ParameterTypeEnumController::MEASURED, ParamEnum::EDGE_ID,
                $edge_id,
                $pack->timestamp,
                1
            );
            // Сохранение местоположения
            self::SaveEquipmentParameter(
                $equipment_sensor['equipment_id'],
                ParameterTypeEnumController::MEASURED, ParamEnum::PLACE_ID,
                $place_id,
                $pack->timestamp,
                $status_danger_zone
            );
            // Сохранение состояния
            self::SaveEquipmentParameter(
                $equipment_sensor['equipment_id'],
                ParameterTypeEnumController::CALCULATED, ParamEnum::STATE,
                1,
                $pack->timestamp,
                1
            );
            // перенос оборудования между шахтами, если они сменились
            // todo метод требует оптимизации т.к. произовдится двойная проверка и запрос в кеш, в случае если сменилась шахта, кроме того, нужно добавитьобработчики и проверку на корректность обработки и возврата данных
            $equipment_move = false;
            $equipment_cache_controller = new EquipmentCacheController();
            $equipment_mines = $equipment_cache_controller->getEquipmentMineByEquipment($equipment_sensor['equipment_id']);
            if ($equipment_mines) {
                foreach ($equipment_mines as $equipment_mine) { // может вернуться массив списка шахт, потому сдела форич
                    if ($equipment_mine['mine_id'] != $mine_id) {
                        $equipment_move = true;
                    }
                }
                if ($equipment_move === true) {
                    EquipmentMainController::moveEquipmentMineInitCache($equipment_sensor['equipment_id'], $mine_id);
                }
            }
            // Сохранение шахты
            self::SaveEquipmentParameter(
                $equipment_sensor['equipment_id'],
                ParameterTypeEnumController::MEASURED, ParamEnum::MINE_ID,
                $mine_id,
                $pack->timestamp,
                1
            );
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'saveLocationPacketEquipmentParameters. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }
        //$warnings[] = 'saveLocationPacketEquipmentParameters. Закончил выполнение метода';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    /**
     * Функция для сохранения параметра оборудования в кеш и БД.
     * @param $equipment_id -   идентификатор оборудования из БД
     * @param int $parameter_type_id Идентификатор типа параметра
     * @param int $parameter_id Идентификатор параметра
     * @param $value -   новое значение, которое будет записываться
     * @param $datetime -   метка времени, записываемого значения
     * @param $status_id -   статус записываемого значения
     * @return array|bool|int Идентификатор значения параметра, сохраненного в базе. false в случае ошибки
     */
    public static function SaveEquipmentParameter($equipment_id, $parameter_type_id, $parameter_id, $value, $datetime, $status_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();
        $value_database_id = -1;

        try {
            if (!self::isEquipmentDataUpdate($equipment_id, $parameter_id, $parameter_type_id, $value)) {                                       //если в кеше лежат неактуальные данные
                /**=================================================================
                 * Получение идентификатора параметра оборудования (из таблицы equipment_parameter)
                 * ==================================================================*/
                $response = EquipmentMainController::getOrSetEquipmentParameter($equipment_id, $parameter_id, $parameter_type_id);
                if ($response['status'] == 1) {
                    //$warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    $equipment_parameter_id = $response['equipment_parameter_id'];
                } else {
                    $errors[] = $response['errors'];
                    //$warnings[] = $response['warnings'];
                    throw new \Exception('saveEquipmentParameter. Ошибка при получении параметра');
                }

                /**=================================================================
                 * Сохранение значения параметра в БД
                 * ==================================================================*/
                $response = EquipmentBasicController::addEquipmentParameterValue($equipment_parameter_id, $value, $status_id, $datetime);
                if ($response['status'] == 1) {
                    //$warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    $value_database_id = $response['equipment_parameter_value_id'];
                } else {
                    $errors[] = $response['errors'];
                    //$warnings[] = $response['warnings'];
                    throw new \Exception('saveEquipmentParameter. Ошибка при сохранении значения в БД');
                }

                /**=================================================================
                 * Сохранение значения параметра в кеш
                 * ==================================================================*/
                $response = (new EquipmentCacheController())
                    ->setEquipmentParameterValue(
                        $equipment_id,
                        $equipment_parameter_id,
                        $parameter_id,
                        $parameter_type_id,
                        $datetime,
                        $value,
                        $status_id
                    );
                if ($response) {
                    //$warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                } else {
                    $errors[] = $response['errors'];
                    //$warnings[] = $response['warnings'];
                    throw new \Exception('saveEquipmentParameter. Значение оборудования ' . $equipment_id . ' не сохранено в кеш.
                        Параметр ' . $parameter_id);
                }
            } else {
                $warning[] = 'SaveEquipmentParameter. Значение параметра актуально';
            }
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'SaveEquipmentParameter. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $data_to_cache_log = array('Items' => $result, 'status' => $status, 'errors' => $errors,
                'warnings' => $warnings, 'equipment_parameter_value_id' => $value_database_id);
            LogCacheController::setEquipmentLogValue('SaveEquipmentParameter', $data_to_cache_log, '2');
        }
        $warnings = 'SaveEquipmentParameter. Закончил метод';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors,
            'warnings' => $warnings, 'equipment_parameter_value_id' => $value_database_id);
    }

    // Тупо для определения времени без движения, потом переделать

    /**
     * функция проверки актуальности данных в кеше по значению параметров оборудования
     * @param $equipment_id - идентификатор оборудования
     * @param $parameter_id - идентификатор параметра
     * @param $parameter_type_id - идентификатор типа параметра
     * @param $value - новое значение параметра, которые будет сравниваться с тем, что лежит в кеше
     * @return bool                 - true, если данные актуальные, false в другом случае
     */
    public static function isEquipmentDataUpdate($equipment_id, $parameter_id, $parameter_type_id, $value)
    {
        $old_parameter_value = (new EquipmentCacheController())
            ->getParameterValue($equipment_id, $parameter_id, $parameter_type_id);

        if ($old_parameter_value === false) {
            return false;
        }

        $currentTime = strtotime(date('Y-m-d H:i:s'));
        $dTime = Assistant::GetMysqlTimeDifference($currentTime, $old_parameter_value['date_time']);
        return $old_parameter_value['value'] == $value && $dTime < 300;
    }

    /**
     * Проверяет находится ли объект в запретной зоне.
     * Если объект входит в запретную зону, то фиксируется время входа.
     * При выходе объекта из запретной зоны вычисляется длительность его нахождения
     * в ней и создается запись в отчётной таблице summary_report_forbidden_zones
     * @param MinerNodeLocation $pack Объект пакета положения шахтёра
     * @param int $status_danger_zone Статус зоны (запретная/разрешённая)
     * @param object $object Объект, который сохраняется в базу.
     * Это либо воркер/оборудование, привязанный к сенсору, либо сам сенсор,
     * если к нему не привязан никакой объект.
     * @param int $place_id Объект плейса в котором находится сенсор
     * @return array
     */
    public static function checkForbiddenZoneStatus($pack, $status_danger_zone, $object, $main_title, $table_name, $place_id, $edge_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();

        //$warnings[] = 'checkForbiddenZoneStatus. Начало метода';
        try {
            // Проверка на нахождение в запрещённой зоне
            // При выходе из запретной зоны в отчётную таблицу заносится запись
            $service_cache = new ServiceCache();
            //$cache = Yii::$app->cache_sensor;
            $cache_key = 'ObjectDangerZone_' . $pack->networkId;
            //if (!$cache->exists($cache_key)) {                                      // Если кэша не существует, то его начальная генерация
            if (!$service_cache->amicum_rGet($cache_key)) {                                      // Если кэша не существует, то его начальная генерация
                //$warnings[] = 'checkForbiddenZoneStatus. Кеш не существует, генерация';
                $cache_array = array();
                $cache_array['danger_zone_status'] = StatusEnumController::PERMITTED;
                $cache_array['date_start'] = 0;
                $service_cache->amicum_rSet($cache_key, $cache_array);
                //$cache->set($cache_key, $cache_array);
                unset($cache_array);
            }
            //$object_danger_info = $cache->get($cache_key);                          // Получение данных из кэша
            $object_danger_info = $service_cache->amicum_rGet($cache_key);                          // Получение данных из кэша
            if ($object_danger_info['danger_zone_status'] != $status_danger_zone) {   // Если статус запретной зоны изменился
                //$warnings[] = 'checkForbiddenZoneStatus. Статус запретной зоны изменился с ' . $object_danger_info['danger_zone_status'] . ' на ' . $status_danger_zone;
                if ($status_danger_zone === StatusEnumController::FORBIDDEN) {                                     // Если объект вошёл в запретную зону
                    //$warnings[] = 'checkForbiddenZoneStatus. Объект вошёл в запретную зону, фиксирую время';
                    // Генерация в кэше структуры, обозначающей метку времени входа в запретную зону
                    $object_danger_info['danger_zone_status'] = StatusEnumController::FORBIDDEN;
                    $object_danger_info['date_start'] = date('Y-m-d H:i:s');
                    //$cache->set($cache_key, $object_danger_info);
                    $service_cache->amicum_rSet($cache_key, $object_danger_info);
                } elseif ($status_danger_zone === StatusEnumController::PERMITTED) {                               // Если объект вышел из запретной зоны
                    //$warnings[] = 'checkForbiddenZoneStatus. Объект вышел из запретной зоны, расчёт времени и генерация события';
                    $object_danger_info['danger_zone_status'] = StatusEnumController::PERMITTED;
                    // Расчёт времени нахождения в запретной зоне
                    $forbidden_zone_duration = Assistant::GetMysqlTimeDifference(
                        $pack->timestamp,
                        $object_danger_info['date_start']
                    );

                    // Нахождение дополнительных данных для создания записи в отчётной таблице
                    $shift = self::getShiftDateNum($pack->timestamp);

                    // Запись факта нахождения в запретной зоне в отчётную таблицу
                    Yii::$app->db_amicum2->createCommand()->insert('summary_report_forbidden_zones', [
                        'date_work' => $shift['shift_date'],
                        'shift' => $shift['shift_num'],
                        'main_id' => $object->id,
                        'main_title' => $main_title,
                        'table_name' => $table_name,
                        'place_id' => $place_id,
                        'edge_id' => $edge_id,
                        'object_id' => $object->object_id,
                        'place_status_id' => $status_danger_zone,
                        'date_time_start' => $object_danger_info['date_start'],
                        'date_time_end' => $pack->timestamp,
                        'duration' => Assistant::SecondsToTime($forbidden_zone_duration)
                    ])->execute();

                    // "Сброс" кэша
                    $object_danger_info['date_start'] = 0;
                    //$cache->set($cache_key, $object_danger_info);
                    $service_cache->amicum_rSet($cache_key, $object_danger_info);
                }
            }
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'checkForbiddenZoneStatus. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $warnings = 'checkForbiddenZoneStatus. Закончил выполнение метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    static function TranslateCheckInCheckOut($package, $checkIn_checkOut)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {
            $warnings[] = "TranslateCheckInCheckOut. Начал выполнять метод";
            $warnings[] = "TranslateCheckInCheckOut. Длина пакета: " . strlen($package);
            $result['timestamp'] = Assistant::GetDateNow();                                                             // временная отметка
            $result['sequenceNumber'] = hexdec(substr($package, 0, 2));                                                 // последовательный номер сигнала узла
            $result['batteryVoltage'] = hexdec(substr($package, 2, 2)) / 10;                                            // напряжение батареи узла связи

            $msb = self::AddZero(base_convert(substr($package, 4, 2), 16, 2), 8);                                                         // сетевой адрес узла-источника сигнала
            $msb_shrink = base_convert(substr($msb, 1, 7), 2, 16);
            $snd_hex = substr($package, 6, 2);                                                                          // сетевой адрес узла-источника сигнала
            $lsb_hex = substr($package, 8, 2);                                                                          // сетевой адрес узла-источника сигнала

            $package_net_id = $msb_shrink . "" . $snd_hex . "" . $lsb_hex;
            $result['minerNodeAddress'] = hexdec($package_net_id);                                                      // сетевой адрес узла-источника сигнала
            $result['sourceNode'] = hexdec($package_net_id);                                                            // сетевой адрес узла-источника сигнала
            $result['checkIn'] = hexdec(substr($package, 10, 2));                                                       //количество нодов
            $result['hearedNodesCount'] = hexdec(substr($package, 12, 2));                                              //количество нодов
            if ($checkIn_checkOut == "0c") {
                $result['className'] = "MinerNodeCheckIn";
                $result['checkIn'] = 1;
            } elseif ($checkIn_checkOut == "0d") {
                $result['className'] = "MinerNodeCheckOut";
                $result['checkIn'] = 0;
            } else {
                throw new \Exception("TranslateCheckInCheckOut. Ошибка в типа пакета регистрации / разрегистрации");
            }

            $result = (object)$result;
            $result = new MinerNodeCheckInOut($result);
        } catch (Throwable $exception) {
            $errors[] = "TranslateCheckInCheckOut. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;

            LogAmicum::LogAmicumStrata("TranslateCheckInCheckOut", $package, $warnings, $errors);

        }
        $warnings[] = "TranslateCheckInCheckOut. Закончил выполнять метод";

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);

        return $result_main;
    }

    /**
     * Сохранение параметров пакета регистрации шахтёра
     * @param MinerNodeCheckInOut $pack - Пакет регистрации
     * @param $mine_id - ключ шахты
     * @param $ip_addr - IP адрес шлюза, через который пришёл пакет
     * @return array
     * @throws \Exception
     */
    public static function saveRegistrationPacket(MinerNodeCheckInOut $pack, $mine_id, $ip_addr)
    {
        $result = [];                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("saveRegistrationPacket");

        try {
            $log->addLog("Начало выполнения метода");

            /**=================================================================
             * Получаем из кеша сенсор айди по нетворк айди
             * ==================================================================*/

            $network_id = $pack->minerNodeAddress;
            // Поиск объекта сенсора по сетевому идентификатору из пакета.
            // Если такой сенсор не найден, то создаём новый сенсор по шаблону.
            $log->addLog("Получаем по network_id -> sensor_id");
            $response = SensorMainController::getOrSetSensorByNetworkId($network_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new \Exception('Ошибка при инициализации сенсора по сетевому адресу: ' . $network_id);
            }

            if ($response['sensor_id'] === false) {
                //если sensor_id не найден, создать его
                $title = 'Метка прочее networkID ' . $network_id;
                $response = self::createSensorDatabase($title, $network_id, $mine_id, 104, 1, 4);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception("Ошибка создания сенсора по нетворк айди $network_id в кеше и в БД");
                }
            }
            $sensor_id = $response['sensor_id'];
            $log->addLog("Получен из кеша сенсор айди по нетворк айди");

            /**=================================================================
             * Инициализация кеша сенсора SensorMine
             * ==================================================================*/
            $sensor_cache = new SensorCacheController();
            if ($sensor_cache->getSensorMineBySensorHash($sensor_id) === false) {
                $response = SensorMainController::initSensorInCache($sensor_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception("Ошибка при инициализации сенсора $sensor_id");
                }
            } else {
                $response = SensorMainController::moveSensorMineInitCache($sensor_id, $mine_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception("Ошибка при перемещении сенсора $sensor_id из кеша шахты");
                }
            }
            $log->addLog("Инициализирован кеш сенсора SensorMine");

            /**=================================================================
             * Находим параметры местоположения шлюза, через который пришел пакет
             * Сделано по причине того, что в пакете нет координат
             * ==================================================================*/
            $response = $sensor_cache->getGatewayParameterByIpHash($ip_addr);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new \Exception("Ошибка нахождения параметров шлюза по ip $ip_addr");
            }

            $gateway_place_id = $response['gateway_place_id'];
            $gateway_coord = $response['gateway_coord'];
            $gateway_edge_id = $response['gateway_edge_id'];

            $log->addLog("Нашел параметры местоположения шлюза");

            /**=================================================================
             * Сохранение параметров сенсора
             * ==================================================================*/
            $response = self::saveRegistrationPacketSensorParameters($sensor_id, $pack, $mine_id, $gateway_coord, $gateway_place_id, $gateway_edge_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
//                throw new \Exception('Ошибка сохранения параметров сенсора');
            }

            $log->addLog("Сохранил параметры сенсора");

            /**=================================================================
             * Сохранение параметров воркера
             * ==================================================================*/
            $response = WorkerMainController::getWorkerInfoBySensorId($sensor_id);
            $log->addLogAll($response);
            $workerSensor = $response['Items'];
            $log->addData($workerSensor, "Привязка воркера к сенсору", __LINE__);

            if ($workerSensor != false and isset($workerSensor['worker_id'])) {
                $log->addLog("Найдена привязка воркера к сенсору");
                $response = self::saveRegistrationPacketWorkerParameters($pack, $workerSensor['worker_id'], $mine_id, $gateway_coord, $gateway_place_id, $gateway_edge_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception('Ошибка сохранения параметров воркера');
                }
                $log->addLog("Сохраненил параметры воркера");

                /**=================================================================
                 * Сохранение/удаление воркера из кеша зачекиненных
                 * ==================================================================*/

                // Если воркер вышел из шахты
                if ($pack->checkIn == 0) {
                    // Удаление из кеша зачекиненных
                    $log->addLog("Удаление из кеша зачекиненных");
                    $response = (new WorkerCacheController())->delInWorkerMineHash($workerSensor['worker_id'], $mine_id);
                    if ($response === false) {
                        $log->addError("Ошибка при удалении воркера из кеша зачекиненны", __LINE__);
                    }

                    // Удаление сенсора из кэша SensorMine
                    $sensor_cache->delInSensorMineByOneHash($sensor_id, $mine_id);

                    $log->addLog("удалил из кеша зачекиненных");

                    // Добавление записи о выходе в отчётную таблицу
                    SummaryReportEndOfShiftController::AddTableReportRecord($workerSensor['worker_id'], $pack->timestamp);

                    $log->addLog("Записал SummaryReportEndOfShift");

                    // Если у него была резервная лампа, то отвязать её
                    if (self::getSensorType($sensor_id) === 'Резервная') {
                        $worker_parameter_coord = WorkerMainController::getWorkerParameterLastValue($workerSensor['worker_id'], ParamEnum::COORD, ParameterTypeEnumController::MEASURED);
                        if ($worker_parameter_coord && isset($worker_parameter_coord['worker_parameter_id'])) {
//                        ArrowController::actionAddWorkerParameterSensor($worker_parameter_coord['worker_parameter_id'], -1, 0);  // Отвязка резервной лампы воркера
                            $result_unbind = BindMinerToLanternController::UnbindReserveLampForStrata($worker_parameter_coord['worker_parameter_id'], $sensor_id, $workerSensor['worker_id']);  // Отвязка резервной лампы воркера
                            $log->addLogAll($result_unbind);
                            if ($result_unbind['status'] != 1) {
                                throw new \Exception('Не удалось отвязать резервный светильник');
                            }
                        }
                    }
                } else {
                    // Сохранение в кеш зачекиненных
                    $log->addLog("Сохранение в кеш зачекиненных");
                    (new WorkerCacheController())->initWorkerMineHash($mine_id, $workerSensor['worker_id']);
                    $log->addLog("Сохраненил в кеш зачекиненных");

                    /**
                     * Удаление флага чекаута, если есть
                     */
                    $cache = Yii::$app->redis_service;
                    $cache_key = 'out:' . $sensor_id;
                    if ($cache->exists($cache_key)) {
                        $cache->del($cache_key);
                    }
                }
                $log->addLog("У сенсора есть привязка к работнику");
            } else {
                $log->addLog("У сенсора нет привязки к работнику");
            }
            $log->addLog("Конец метода");

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());

            LogCacheController::setStrataLogValue('saveRegistrationPacket', array_merge(['Items' => $result, 'Method parameters' => [
                'pack' => $pack,
                'mine_id' => $mine_id,
                'ip_addr' => $ip_addr
            ]], $log->getLogAll()), '2');
            LogAmicum::LogAmicumStrata("saveRegistrationPacket", $pack, $log->getWarnings(), $log->getErrors());
        }

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Сохраняет параметры сенсора, полученные из пакета регистрации шахтёра
     * @param $sensor_id
     * @param $pack
     * @param $mine_id
     * @param $coord
     * @param $place_id
     * @param $edge_id
     * @return array
     */
    public static function saveRegistrationPacketSensorParameters($sensor_id, $pack, $mine_id, $coord, $place_id, $edge_id)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("saveRegistrationPacketSensorParameters");

        try {
            $log->addLog("Начало выполнения метода");
            /**=================================================================
             * Сохранение параметров
             * ==================================================================*/
            /**
             * получаем за раз все последние значения по сенсору из кеша
             */
            $sensor_cache_controller = new SensorCacheController();
            $sensor_parameter_value_list_cache = $sensor_cache_controller->multiGetParameterValueHash($sensor_id, '*', '*');
            foreach ($sensor_parameter_value_list_cache as $sensor_parameter_value_cache) {
                $sensor_parameter_value_cache_array[$sensor_parameter_value_cache['sensor_id']][$sensor_parameter_value_cache['parameter_type_id']][$sensor_parameter_value_cache['parameter_id']] = $sensor_parameter_value_cache;
            }

            $log->addLog("получил данные с кеша по всему сенсору");

            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::VOLTAGE, $pack->batteryVoltage, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new \Exception('Ошибка сохранения параметра ' . ParamEnum::VOLTAGE);
            }

            if ($response['date_to_cache']) {
                $date_to_cache[] = $response['date_to_cache'];
            }
            if ($response['date_to_db']) {
                $date_to_db[] = $response['date_to_db'];
            }


            if ($place_id != -1) {
                $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::PLACE_ID, $place_id, $pack->timestamp, StatusEnumController::PERMITTED, $sensor_parameter_value_cache_array);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception('Ошибка сохранения параметра ' . ParamEnum::PLACE_ID);
                }

                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }

            }

            if ($coord != -1) {
                $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::COORD, $coord, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception('Ошибка сохранения параметра ' . ParamEnum::COORD);
                }

                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }

            }

            if ($edge_id != -1) {
                $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::EDGE_ID, $edge_id, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception('Ошибка сохранения параметра ' . ParamEnum::EDGE_ID);
                }

                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }

            }

            $response = self::saveSensorParameterBatchForce($sensor_id, ParameterTypeEnumController::CALCULATED, ParamEnum::STATE, $pack->checkIn, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new \Exception('Ошибка сохранения параметра ' . ParamEnum::STATE);
            }

            if ($response['date_to_cache']) {
                $date_to_cache[] = $response['date_to_cache'];
            }
            if ($response['date_to_db']) {
                $date_to_db[] = $response['date_to_db'];
            }


            $response = self::saveSensorParameterBatchForce($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::CHECKIN, $pack->checkIn, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new \Exception('Ошибка сохранения параметра ' . ParamEnum::CHECKIN);
            }

            if ($response['date_to_cache']) {
                $date_to_cache[] = $response['date_to_cache'];
            }
            if ($response['date_to_db']) {
                $date_to_db[] = $response['date_to_db'];
            }


            /**=================================================================
             * Вычисление статусов в зависимости от процента заряда
             * ==================================================================*/
            $minerPercent = self::getMinerBatteryPercent(str_replace(',', '.', $pack->batteryVoltage));
            if ($minerPercent <= 10) {
                $value_status_id = StatusEnumController::EMERGENCY_VALUE;
                $event_status_id = StatusEnumController::EVENT_RECEIVED;
            } else {
                $value_status_id = StatusEnumController::NORMAL_VALUE;
                $event_status_id = StatusEnumController::EVENT_ELIMINATED_BY_SYSTEM;
            }

            /**
             * Сохранение заряда батареи
             */
            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED, ParamEnum::MINER_BATTERY_PERCENT, $minerPercent, $pack->timestamp, $value_status_id, $sensor_parameter_value_cache_array);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new \Exception('Ошибка сохранения параметра ' . ParamEnum::MINER_BATTERY_PERCENT);
            }

            if ($response['date_to_cache']) {
                $date_to_cache[] = $response['date_to_cache'];
            }
            if ($response['date_to_db']) {
                $date_to_db[] = $response['date_to_db'];
            }

            $log->addLog("Закончил подготовку данных");

            /**=================================================================
             * блок массовой вставки значений в БД
             * =================================================================*/
            if (isset($date_to_db)) {
                Yii::$app->db_amicum2->createCommand()->batchInsert('sensor_parameter_value',
                    ['sensor_parameter_id', 'date_time', 'value', 'status_id'],
                    $date_to_db)->execute();
//                $insert_param_val = Yii::$app->db_amicum2->queryBuilder->batchInsert('sensor_parameter_value', ['sensor_parameter_id', 'date_time', 'value', 'status_id'], $date_to_db);
//                Yii::$app->db_amicum2->createCommand($insert_param_val)->execute();
//                Yii::$app->db_amicum2->createCommand($insert_param_val . " ON DUPLICATE KEY UPDATE `value` = VALUES (`value`), `status_id` = VALUES (`status_id`)")->execute();
            }

            $log->addLog("Закончил вставку данных в БД");

            /**=================================================================
             * блок массовой вставки значений в кеш
             * =================================================================*/
            if (isset($date_to_cache)) {
                //$warnings[] = $sensor_id;
                $ask_from_method = (new SensorCacheController)->multiSetSensorParameterValueHash($date_to_cache);
                $log->addLogAll($ask_from_method);
                if ($ask_from_method['status'] != 1) {
                    throw new \Exception('saveLocationPacketSensorParameters. Не смог обновить параметры в кеше сенсора' . $sensor_id);
                }
            }

            $log->addLog("Закончил вставку данных в кеш");

            /**
             * Генерация события для заряда батареи
             */
            $response = EventMainController::createEventFor('sensor', $sensor_id, EventEnumController::LAMP_LOW_BATTERY, $minerPercent,
                $pack->timestamp, $value_status_id, ParamEnum::MINER_BATTERY_PERCENT, $mine_id, $event_status_id);
            $log->addLogAll($response);

            $log->addLog("Сгенерировал событие");

            /**=================================================================
             * Проверка на разрегистрацию в шахте
             * ==================================================================*/
            if ($pack->checkIn == 0) {
                $response = SensorMainController::moveSensorMineInitCache($sensor_id, -1);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception("Ошибка при перемещении сенсора $sensor_id из кеша шахты");
                }
            }
            $log->addLog("Прошел проверку на регистрацию в шахте");

            $log->addLog("Конце метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * saveSensorParameterBatchForce - Функция для создания структуры параметра датчика в кеш и БД - без проверки на изменение значения
     * @param $sensor_id -   идентификатор сенсора
     * @param int $parameter_type_id идентификатор типа параметра
     * @param int $parameter_id идентификатор параметра
     * @param $value -   новое значение, которое будет записываться
     * @param $datetime -   метка времени, записываемого значения
     * @param $status_id -   статус записываемого значения
     * @param $sensor_parameter_value_cache_array
     * @return array
     */
    public static function saveSensorParameterBatchForce($sensor_id, $parameter_type_id, $parameter_id, $value, $datetime, $status_id, $sensor_parameter_value_cache_array)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей
        $date_to_cache = null;                                                                                          //возвращаемый массив для вставки в кеш одним заходом
        $date_to_db = null;                                                                                             //возвращаемый массив для вставки в БД одним заходом
        $value_database_id = -1;
        // Стартовая отладочная информация
        $log = new LogAmicumFront("saveSensorParameterBatchForce");

        try {
            $log->addLog("Начал метод");

            /**
             * Получение идентификатора параметра сенсора (из таблицы sensor_parameter)
             * с предварительной проверкой в кеше, если в кеше нет, то получаем значение из базы,
             * если и там нет, то создаем параметр в бд и получаем его здесь
             */
            if (isset($sensor_parameter_value_cache_array[$sensor_id][$parameter_type_id][$parameter_id])) {
                $sensor_parameter_id = $sensor_parameter_value_cache_array[$sensor_id][$parameter_type_id][$parameter_id]['sensor_parameter_id'];
            } else {
                $response = SensorBasicController::getSensorParameter($sensor_id, $parameter_id, $parameter_type_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception("Ошибка при получении параметра $parameter_id-$parameter_type_id сенсора $sensor_id");
                }
                $sensor_parameter_id = $response['sensor_parameter_id'];
            }
            $log->addLog("Получил ключ параметра");

            /**
             * Сохранение значения параметра в БД
             */
            $date_to_db = array(
                'sensor_parameter_id' => $sensor_parameter_id,
                'date_time' => $datetime,
                'value' => $value,
                'status_id' => $status_id
            );

            /**
             * Сохранение значения параметра в кеш
             */
            $date_to_cache = SensorCacheController::buildStructureSensorParametersValue($sensor_id, $sensor_parameter_id, $parameter_id, $parameter_type_id, $datetime, $value, $status_id);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Закончил метод");

        return array_merge(['Items' => $result, 'sensor_parameter_value_id' => $value_database_id, 'date_to_cache' => $date_to_cache, 'date_to_db' => $date_to_db], $log->getLogAll());
    }

    /**
     * Сохраняет параметры воркера, полученные из пакета регистрации
     * @param object $pack Объект пакета регистрации
     * @param int $worker_id Идентификатор воркера
     * @param int $mine_id Идентификатор шахты
     * @param string $coord Координаты
     * @param int $place_id Идентификатор места
     * @param $edge_id
     * @return array
     */
    public static function saveRegistrationPacketWorkerParameters($pack, $worker_id, $mine_id, $coord, $place_id, $edge_id)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("saveRegistrationPacketWorkerParameters");

        try {
            $log->addLog("Начало выполнения метода");
            /**
             * получаем за раз все последние значения по воркеру из кеша
             */
            $worker_cache_controller = new WorkerCacheController();
            $worker_parameter_value_list_cache = $worker_cache_controller->multiGetParameterValueHash($worker_id, '*', '*');
            if ($worker_parameter_value_list_cache === false) {
                $worker_parameter_value_cache_array = null;
            } else {
                foreach ($worker_parameter_value_list_cache as $worker_parameter_value_cache) {
                    $worker_parameter_value_cache_array[$worker_parameter_value_cache['worker_id']][$worker_parameter_value_cache['parameter_type_id']][$worker_parameter_value_cache['parameter_id']] = $worker_parameter_value_cache;
                }
            }

            $log->addLog("Получил данные по работнику из кеша");

            /**=================================================================
             * Сохранение параметров чекина и идентификатора плейса
             * ==================================================================*/
            $response = self::saveWorkerParameterForceBatch($worker_id, ParameterTypeEnumController::MEASURED, ParamEnum::CHECKIN, $pack->checkIn, $pack->timestamp, StatusEnumController::ACTUAL);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new \Exception('Ошибка сохранения параметра ' . ParamEnum::CHECKIN);
            }

            if ($response['date_to_cache']) {
                $date_to_cache[] = $response['date_to_cache'];
            }
            if ($response['date_to_db']) {
                $date_to_db[] = $response['date_to_db'];
            }


            if ($place_id != -1) {
                $response = self::saveWorkerParameterBatch($worker_id, ParameterTypeEnumController::MEASURED, ParamEnum::PLACE_ID, $place_id, $pack->timestamp, StatusEnumController::PERMITTED, $worker_parameter_value_cache_array);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception('Ошибка сохранения параметра ' . ParamEnum::PLACE_ID);
                }

                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            }

            if ($coord != -1) {
                $response = self::saveWorkerParameterBatch($worker_id, ParameterTypeEnumController::MEASURED, ParamEnum::COORD, $coord, $pack->timestamp, StatusEnumController::PERMITTED, $worker_parameter_value_cache_array);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception('Ошибка сохранения параметра ' . ParamEnum::COORD);
                }

                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }

            }

            if ($edge_id != -1) {
                $response = self::saveWorkerParameterBatch($worker_id, ParameterTypeEnumController::MEASURED, ParamEnum::EDGE_ID, $edge_id, $pack->timestamp, StatusEnumController::PERMITTED, $worker_parameter_value_cache_array);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception('Ошибка сохранения параметра ' . ParamEnum::EDGE_ID);
                }

                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }

            }

            $response = self::saveWorkerParameterBatch($worker_id, ParameterTypeEnumController::MEASURED, ParamEnum::MINE_ID, $mine_id, $pack->timestamp, StatusEnumController::ACTUAL, $worker_parameter_value_cache_array);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new \Exception('Ошибка сохранения параметра ' . ParamEnum::MINE_ID);
            }

            if ($response['date_to_cache']) {
                $date_to_cache[] = $response['date_to_cache'];
            }
            if ($response['date_to_db']) {
                $date_to_db[] = $response['date_to_db'];
            }

            $log->addLog("Подготовил данные для записи в бд и вставку в кеш");


            /**=================================================================
             * блок массовой вставки значений в БД
             * =================================================================*/
            if (isset($date_to_db)) {
//                ini_set('mysqlnd.connect_timeout', 1440000);
//                ini_set('default_socket_timeout', 1440000);
//                ini_set('mysqlnd.net_read_timeout', 1440000);
//                ini_set('mysqlnd.net_write_timeout', 1440000);
                Yii::$app->db_amicum2->createCommand()->batchInsert('worker_parameter_value',
                    ['worker_parameter_id', 'date_time', 'value', 'status_id', 'shift', 'date_work'],
                    $date_to_db)->execute();
//                $insert_param_val = Yii::$app->db_amicum2->queryBuilder->batchInsert('worker_parameter_value', ['worker_parameter_id', 'date_time', 'value', 'status_id', 'shift', 'date_work'], $date_to_db);
//                Yii::$app->db_amicum2->createCommand($insert_param_val)->execute();
//                Yii::$app->db_amicum2->createCommand($insert_param_val . " ON DUPLICATE KEY UPDATE `value` = VALUES (`value`), `status_id` = VALUES (`status_id`), `shift` = VALUES (`shift`), `date_work` = VALUES (`date_work`)")->execute();
            }
            $log->addLog("Записал данные в БД");

            /**
             * блок массовой вставки значений в кеш
             */
            if (isset($date_to_cache)) {
                $check_date_time_in_cache = self::getLastCheckinout($worker_id, $pack->timestamp);
                if ($check_date_time_in_cache['status'] == 1) {
                    if ($check_date_time_in_cache['move_to_cache'] == true) {
                        $ask_from_method = (new WorkerCacheController)->multiSetWorkerParameterValueHash($date_to_cache, $worker_id);
                        $log->addLogAll($ask_from_method);
                        if ($ask_from_method['status'] != 1) {
                            throw new \Exception('Не смог обновить параметры в кеше работника' . $worker_id);
                        }
                    }
                }
            }
            $log->addLog("Записал данные в кеш");

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Закончил метод");
        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Функция для сохранения параметра воркера в кеш и БД без проверки актуальности.
     * Таким образом данные будут сохраняться в любом случае
     * @param int $worker_id идентификатор работника
     * @param int $parameter_type_id идентификатор типа параметра
     * @param int $parameter_id идентификатор параметра
     * @param $value -   новое значение, которое будет записываться
     * @param $datetime -   метка времени, записываемого значения
     * @param $status_id -   статус записываемого значения
     * @return array
     */
    public static function saveWorkerParameterForceBatch($worker_id, $parameter_type_id, $parameter_id, $value, $datetime, $status_id)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $log = new LogAmicumFront("saveWorkerParameterForceBatch");

        try {
            $date_to_cache = null;  //возвращаемы массив для вставки в кеш одним заходом
            $date_to_db = null;  //возвращаемы массив для вставки в БД одним заходом
            $value_database_id = -1;

            $log->addLog("Начало выполнения метода");
            /**=================================================================
             * Получение идентификатор параметра воркера из таблицы worker_parameter
             * ==================================================================*/
            $response = WorkerMainController::getOrSetWorkerParameter($worker_id, $parameter_id, $parameter_type_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new \Exception("Ошибка при получении параметра $parameter_id-$parameter_type_id воркера " . $worker_id);
            }
            $worker_parameter_id = $response['worker_parameter_id'];

            /**=================================================================
             * Сохранение значения в БД
             * ==================================================================*/
            $shift_info = self::getShiftDateNum($datetime);
            $date_to_db = array(
                'worker_parameter_id' => $worker_parameter_id,
                'date_time' => $datetime,
                'value' => $value,
                'status_id' => $status_id,
                'shift' => $shift_info['shift_num'],
                'date_work' => $shift_info['shift_date']
            );

            /**=================================================================
             * Сохранение значения в кеш
             * ==================================================================*/
// НУ И ГДЕ В КЕШ ОТПРАВЛЯЕМ???
            $date_to_cache = WorkerCacheController::buildStructureWorkerParametersValue($worker_id, $worker_parameter_id, $parameter_id, $parameter_type_id, $datetime, $value, $status_id);

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Закончил метод");

        return array_merge(['Items' => $result, 'worker_parameter_value_id' => $value_database_id,
            'date_to_cache' => $date_to_cache, 'date_to_db' => $date_to_db], $log->getLogAll());
    }

    /**
     * Метод проверяет если в кеше параметр 158 (регистрация разрегистрация) воркера новее прешедшего значения, то вернет false иначе вернет true и тем самым разрешит запись нового значения в кеш
     * @param $timestamp - метка времне из пакета
     * @param $worker_id
     * @return array
     */
    public static function getLastCheckinout($worker_id, $date_time)
    {
        $status = 1;
        $move_to_cache = true;
        $errors = array();
        $warnings = array();
        try {
            $worker_cache_controller = new WorkerCacheController();
            $worker_parameter_value_list_cache = $worker_cache_controller->getParameterValueHash($worker_id, 158, 2);
            if ($worker_parameter_value_list_cache) {
                $down_value = $worker_parameter_value_list_cache['value'];
                $time_from_cache = strtotime($worker_parameter_value_list_cache['date_time']);
                $time_package = strtotime($date_time);
                //если время в пакете меньше времени в кеше то это считается не новый пакет и мы не добовляем его в кеш
                if ($time_package < $time_from_cache) {
                    $move_to_cache = false;
                }
            }
        } catch (\Exception $exception) {
            $status = 0;
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        return array('status' => $status, 'move_to_cache' => $move_to_cache, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Возвращает тип луча (постоянный/резервный) из базы
     * @param $sensorId - Идентификатор сенсора
     * @return string|null - "Постоянная"/"Резервная"/NULL
     */
    public static function getSensorType($sensorId)
    {
        $sensorType = (new Query())
            ->select('sensor_type')
            ->from('view_workers_and_attached_sensors')
            ->where(['id' => $sensorId])
            ->one();

        if ($sensorType) {
            return $sensorType['sensor_type'];
        }
    }

    static function TranslateAscRead($package)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {
            $warnings[] = "TranslateAscRead. Начал выполнять метод";
            $warnings[] = "TranslateAscRead. Длина пакета: " . strlen($package);
            $result['timestamp'] = Assistant::GetDateNow();                                                             // временная отметка
            $result['messageId'] = hexdec(substr($package, 0, 2));                                                       // номер сообщения

            $msb = self::AddZero(base_convert(substr($package, 2, 2), 16, 2), 8);                                                         // сетевой адрес узла-источника сигнала
            $msb_shrink = base_convert(substr($msb, 1, 7), 2, 16);
            $snd_hex = substr($package, 4, 2);                                                                          // сетевой адрес узла-источника сигнала
            $lsb_hex = substr($package, 6, 2);                                                                          // сетевой адрес узла-источника сигнала

            $package_net_id = $msb_shrink . "" . $snd_hex . "" . $lsb_hex;
            $result['networkId'] = hexdec($package_net_id);                                                             // сетевой адрес узла-источника сигнала
            $result = (object)$result;
        } catch (Throwable $exception) {
            $errors[] = "TranslateCheckInCheckOut. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
        }
        $warnings[] = "TranslateCheckInCheckOut. Закончил выполнять метод";

        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);

        return $result_main;
    }

    /**
     * Название метода: SaveMessageAck()
     * Метод сохранения статса сообщение в Бд. Что оно доставлено
     * @param $date_time - дата доставки сообщения
     * @param $message_id - идентификатор сообщения
     * @param $network_id - идентификатор датчика с которого пришло сообщение
     * @return array
     *
     * @package backend\controllers
     *
     * @author fidchenkoM
     * Created date: on 23.05.2019 16:48
     * @since ver
     */
    public static function SaveMessageAck($date_time, $message_id, $network_id)
    {
//        $date_time =date('Y-m-d H:i:s.U');$message_id = 12410;$network_id =661025;
        $result = array();
        $status = 1;
        $errors = array();
        $warnings = array();
        $text_message = TextMessage::find()//ищем последнее сообщение по network_id и message_id
        ->andWhere([
            'message_id' => $message_id,
            'reciever_network_id' => $network_id
        ])
            ->andWhere('status_id !=' . StatusEnumController::MSG_READED)
            ->limit(1)
            ->orderBy(['datetime' => SORT_DESC])
            ->one();
        if ($text_message)                                                                                             //если нашли такую запись
        {
            $text_message->status_id = StatusEnumController::MSG_DELIVERED;
            if (!$text_message->save()) {
                $status = 0;
                //$warnings[] = 'SaveMessageAck. Не смог сохранить данные по сообщению в таблицу text_message';
                $errors[] = $text_message->errors;
            }

            /**
             * Сохранение параметра "Флаг текстового сообщения для воркера"
             * Значением параметра является статус текстового сообщения
             */
            self::saveWorkerParameter($text_message->reciever_worker_id, ParameterTypeEnumController::MEASURED, ParamEnum::TEXT_MSG_FLAG,
                StatusEnumController::MSG_DELIVERED, $text_message->datetime, StatusEnumController::ACTUAL);

            /**
             * Сохранение параметра "Флаг сигнал об аварии"
             * Аварийное сообщение определяется по идентификатору сообщения
             */
            if ($message_id > 200) {
                self::saveWorkerParameter($text_message->reciever_worker_id, ParameterTypeEnumController::MEASURED, ParamEnum::ALARM_SIGNAL_FLAG,
                    StatusEnumController::ALARM_DELIVERED, $text_message->datetime, StatusEnumController::ACTUAL);
            }
        } else {
            $errors[] = 'SaveMessageAck.Нет в бд такого сообщения для указаного network_id ' . $network_id;
            $status = 0;
        }
        $warnings = 'SaveMessageAck. Закончил метод';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Функция для сохранения параметра воркера в кеш и БД.
     *
     * Возвращает массив вида:
     * [
     *  'Items',
     *  'status',
     *  'errors',
     *  'warnings',
     *  'worker_parameter_value_id'
     * ]
     *
     * @param int $worker_id идентификатор работника
     * @param int $parameter_type_id идентификатор типа параметра
     * @param int $parameter_id идентификатор параметра
     * @param $value -   новое значение, которое будет записываться
     * @param $datetime -   метка времени, записываемого значения
     * @param $status_id -   статус записываемого значения
     * @return array Идентификатор значения параметра, сохраненного в базе. false в случае ошибки
     */
    public static function saveWorkerParameter($worker_id, $parameter_type_id, $parameter_id, $value, $datetime, $status_id)
    {
        $errors = array();                                                                                                //массив ошибок
        $status = 1;                                                                                                      //состояние выполнения метода
        $result = null;
        $warnings = array();
        $value_database_id = -1;
        try {
            /**=================================================================
             * Проверка актуальности значения параметра
             * ==================================================================*/
            $response = WorkerMainController::IsChangeWorkerParameterValue($worker_id, $parameter_id, $parameter_type_id, $value, $datetime);
            if ($response['status'] == 1) {
                //$warnings[] = $response['warnings'];
                $errors[] = $response['errors'];
                $flag_save = $response['flag_save'];
            } else {
                $errors[] = $response['errors'];
                //$warnings[] = $response['warnings'];
                throw new \Exception("saveWorkerParameter. Ошибка при проверке актуальности параметра $parameter_id-$parameter_type_id 
                    воркера " . $worker_id);
            }
            if ($flag_save) {
                /**=============================================================
                 * Получение идентификатор параметра воркера из таблицы worker_parameter
                 * ==============================================================*/
                $response = WorkerMainController::getOrSetWorkerParameter($worker_id, $parameter_id, $parameter_type_id);
                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    $worker_parameter_id = $response['worker_parameter_id'];
                } else {
                    $errors[] = $response['errors'];
                    $warnings[] = $response['warnings'];
                    throw new \Exception("saveWorkerParameter. Ошибка при получении параметра $parameter_id-$parameter_type_id
                    воркера " . $worker_id);
                }

                /**=============================================================
                 * Сохранение значения в БД
                 * ==============================================================*/
                $shift_info = self::getShiftDateNum($datetime);
                $value_database_id = WorkerBasicController::addWorkerParameterValue($worker_parameter_id, $value, $shift_info['shift_num'], $status_id, $datetime, $shift_info['shift_date']);
                if (is_array($value_database_id)) {

                    $errors[] = $value_database_id;
                    throw new \Exception("saveWorkerParameter. Ошибка при сохранении значения в БД параметра $parameter_id-$parameter_type_id
                    воркера " . $worker_id);
                }

                /**=============================================================
                 * Сохранение значения в кеш
                 * ==============================================================*/
                $response = (new WorkerCacheController())->setWorkerParameterValueHash(
                    $worker_id,
                    $worker_parameter_id,
                    $parameter_id,
                    $parameter_type_id,
                    $datetime,
                    $value,
                    $status_id
                );
                if ($response['status'] == 1) {
                    //$warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                } else {
                    $errors[] = $response['errors'];
                    //$warnings[] = $response['warnings'];
                    throw new \Exception('saveWorkerParameter. Значение воркера ' . $worker_id . ' не сохранено в кеш.
                        Параметр ' . $parameter_id);
                }

                /**=============================================================
                 * Сохранение значения в таблицу временного хранения
                 * worker_parameter_value_temp для формирования отчётных данных
                 * ==============================================================*/
                /*$value_database_id = WorkerBasicController::addWorkerParameterValueTemp($worker_parameter_id, $value, $shift_info['shift_num'], $status_id, $datetime, $shift_info['shift_date']);
                if (is_array($value_database_id)) {
                    $errors[] = $value_database_id;
                    throw new \Exception("saveWorkerParameter. Ошибка при сохранении значения в БД параметра $parameter_id-$parameter_type_id
                    воркера " . $worker_id . ' в таблицу worker_parameter_value_temp');
                }*/
            } else {
                //$warnings[] = 'saveWorkerParameter. Значение параметра актулаьно';
            }
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'saveWorkerParameter. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $errors[__FUNCTION__ . ' parameters'] = [
                'worker_id' => $worker_id,
                'parameter_type_id' => $parameter_type_id,
                'parameter_id' => $parameter_id,
                'value' => $value,
                'datetime' => $datetime,
                'status_id' => $status_id
            ];
        }

        //$warnings[] = 'saveWorkerParameter. Конец метода';
        return array('Items' => $result, 'status' => $status, 'errors' => $errors,
            'warnings' => $warnings, 'worker_parameter_value_id' => $value_database_id);
    }

    /**
     * Метод для вычисления номера смены и производственной даты
     * @param $date_time - Дата и время получения пакета
     * @return array        - Ассоциативный массив с 2 элементами.
     *  Индекс shift_num    - номер смены
     *  Индекс shift_date   - производственная дата
     *  Индекс count_shifts - количество смен на предприятии
     *
     * Тесты:
     * @see \StrataControllerTest::testGetShiftDateNumWithCorrectArguments()
     */
    public static function getShiftDateNum($date_time)
    {
        $shift_id = null;
        $date_work = "";
        $time = strtotime(date('H:i:s', strtotime($date_time)));
        $date = date('Y-m-d', strtotime($date_time));

        $count_shifts = \frontend\controllers\Assistant::GetCountShifts();

        if ($count_shifts == 3) {
            if ($time < strtotime("08:00:00")) {
                $shift_id = 3;
                $date_work = date('Y-m-d', strtotime($date . "-1day"));
            } else if ($time < strtotime("16:00:00")) {
                $shift_id = 1;
                $date_work = $date;
            } else {
                $shift_id = 2;
                $date_work = $date;
            }
        } else {
            if ($time < strtotime("02:00:00")) {
                $shift_id = 3;
                $date_work = date('Y-m-d', strtotime($date_time . "-1day"));
            } else if ($time < strtotime("08:00:00")) {
                $shift_id = 4;
                $date_work = date('Y-m-d', strtotime($date_time . "-1day"));
            } else if ($time < strtotime("14:00:00")) {
                $shift_id = 1;
                $date_work = $date;
            } else if ($time < strtotime("20:00:00")) {
                $shift_id = 2;
                $date_work = $date;
            } else {
                $shift_id = 3;
                $date_work = $date;
            }
        }

        return ['shift_num' => $shift_id, 'shift_date' => $date_work, 'count_shifts' => $count_shifts];
    }

    /**
     * Название метода: SaveMessageRead()
     * @brief Используется для обработки и сохранения данных из пакета
     * "Подтверждение чтения текстового сообщения". Изменяет статус сообщения,
     * к которому относится пакет, с "доставлено" на "прочитано".
     *
     * @details В таблице text_message должно содержаться сообщение со статусом
     * "Сообщение доставлено".
     * После этого на вход методу подается текущая дата и время в формате MySQL,
     * идентификатор данного сообщения и сетевой идентификатор получателя
     * сообщения (именно от него приходит подтверждение чтения).
     * В результате в таблице text_message должна добавиться новая запись,
     * содержащая все те же данные, что и в исходном сообщении, но с новой датой
     * и статусом ("Прочитано").
     *
     * @param $date_time - дата доставки сообщения
     * @param $message_id - идентификатор сообщения
     * @param $network_id - идентификатор датчика с которого пришло сообщение
     * @return array
     *
     * @package backend\controllers
     *
     * @author fidchenkoM
     * Created date: on 23.05.2019 16:48
     * @since ver
     */
    public static function SaveMessageRead($date_time, $message_id, $network_id)
    {
//        $date_time =date('Y-m-d H:i:s.U');$message_id = 12410;$network_id =661025;
        $result = array();
        $status = 1;
        $errors = array();
        $warnings = array();
        $text_message = TextMessage::find()//ищем последнее сообщение по network_id и message_id
        ->andWhere([
            'message_id' => $message_id,
            'reciever_network_id' => $network_id
        ])
            ->andWhere('status_id !=' . StatusEnumController::MSG_READED)
            ->limit(1)
            ->orderBy(['datetime' => SORT_DESC])
            ->one();
        if ($text_message)                                                                                             //если нашли такую запись
        {
            $text_message->status_id = StatusEnumController::MSG_READED;
            if (!$text_message->save()) {
                $status = 0;
                //$warnings[] = 'saveMessageRead. Не смог сохранить данные по сообщению в таблицу text_message';
                $errors[] = $text_message->errors;
            }

            /**
             * Сохранение параметра "Флаг текстового сообщения для воркера"
             * Значением параметра является статус текстового сообщения
             */
            self::saveWorkerParameter($text_message->reciever_worker_id, ParameterTypeEnumController::MEASURED, ParamEnum::TEXT_MSG_FLAG,
                StatusEnumController::MSG_READED, $text_message->datetime, StatusEnumController::ACTUAL);

            /**
             * Сохранение параметра "Флаг сигнал об аварии"
             * Аварийное сообщение определяется по идентификатору сообщения
             */
            if ($message_id > 200) {
                self::saveWorkerParameter($text_message->reciever_worker_id, ParameterTypeEnumController::MEASURED, ParamEnum::ALARM_SIGNAL_FLAG,
                    StatusEnumController::ALARM_READED, $text_message->datetime, StatusEnumController::ACTUAL);
            }
        } else {
            $errors[] = 'saveMessageRead.Нет в бд такого сообщения для указаного network_id ' . $network_id;
            $status = 0;
        }
        $warnings = 'saveMessageRead. Закончил метод';
        $result_main = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
        return $result_main;
    }

    static function TranslateEnvironmental($package)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {

            $warnings[] = "TranslateEnvironmental. Начал выполнять метод";
            $warnings[] = "TranslateEnvironmental. Длина пакета: " . strlen($package);
            $result['timestamp'] = Assistant::GetDateNow();                                                             // временная отметка
            $result['sequenceNumber'] = hexdec(substr($package, 0, 2));                                      // количество узлов

            $msb = self::AddZero(base_convert(substr($package, 2, 2), 16, 2), 8);                                                         // сетевой адрес узла-источника сигнала
            $msb_shrink = base_convert(substr($msb, 1, 7), 2, 16);
            $snd_hex = substr($package, 4, 2);                                                               // сетевой адрес узла-источника сигнала
            $lsb_hex = substr($package, 6, 2);                                                               // сетевой адрес узла-источника сигнала

            $package_net_id = $msb_shrink . "" . $snd_hex . "" . $lsb_hex;
            $result['sourceNode'] = hexdec($package_net_id);                                                            // сетевой адрес узла-источника сигнала

            $result['className'] = "EnvironmentalSensor";                                                               // количество узлов

            $parametersCount = hexdec(substr($package, 8, 2));                                               // количество узлов
            $result['parametersCount'] = $parametersCount;                                                              // количество узлов

            for ($i = 10; $i < $parametersCount * 10 + 10; $i = $i + 10) {
                $result_parameter = array();
                $gas_parameter_id = hexdec(substr($package, $i, 2));

                if ($gas_parameter_id == 100) {

                    $result_parameter['className'] = "TrolexInfo1";
                    $result_parameter['gasReading'] = hexdec(substr($package, $i + 2, 4));                                // A+B
                    $result_parameter['sensorModuleType'] = hexdec(substr($package, $i + 6, 4));                          // C+D

                } else if ($gas_parameter_id == 101) {

                    $result_parameter['className'] = "TrolexInfo2";
                    $byte_A = self::AddZero(base_convert(hexdec(substr($package, $i + 2, 2)), 16, 2), 8);
                    $result_parameter['totalDigits'] = base_convert(substr($byte_A, 0, 4), 2, 10);
                    $result_parameter['decimalDigits'] = base_convert(substr($byte_A, 4, 4), 2, 10);

                    $byte_B = self::AddZero(base_convert(hexdec(substr($package, $i + 4, 2)), 16, 2), 8);

                    $result_parameter['trolexStatus']['moduleAbsent'] = base_convert(substr($byte_B, 0, 1), 2, 10);
                    $result_parameter['trolexStatus']['warmup'] = base_convert(substr($byte_B, 1, 1), 2, 10);
                    $result_parameter['trolexStatus']['sp1'] = base_convert(substr($byte_B, 2, 1), 2, 10);
                    $result_parameter['trolexStatus']['sp2'] = base_convert(substr($byte_B, 3, 1), 2, 10);
                    $result_parameter['trolexStatus']['stelAlarm'] = base_convert(substr($byte_B, 4, 1), 2, 10);
                    $result_parameter['trolexStatus']['twaAlarm'] = base_convert(substr($byte_B, 5, 1), 2, 10);
                    $result_parameter['trolexStatus']['fault'] = base_convert(substr($byte_B, 6, 1), 2, 10);
                    $result_parameter['trolexStatus']['pellistorOver'] = base_convert(substr($byte_B, 7, 1), 2, 10);

                    $byte_C = self::AddZero(base_convert(hexdec(substr($package, $i + 6, 2)), 16, 2), 8);
                    $result_parameter['strataStatus']['io1ConfigLeastSignificantBit'] = base_convert(substr($byte_C, 0, 1), 2, 10);
                    $result_parameter['strataStatus']['io2ConfigLeastSignificantBit'] = base_convert(substr($byte_C, 1, 1), 2, 10);
                    $result_parameter['strataStatus']['io1State'] = base_convert(substr($byte_C, 2, 1), 2, 10);
                    $result_parameter['strataStatus']['io2State'] = base_convert(substr($byte_C, 3, 1), 2, 10);
                    $result_parameter['strataStatus']['powerSource'] = base_convert(substr($byte_C, 4, 1), 2, 10);
                    $result_parameter['strataStatus']['messageReason'] = base_convert(substr($byte_C, 5, 1), 2, 10);
                    $result_parameter['strataStatus']['io1ConfigMiddleBit'] = base_convert(substr($byte_C, 6, 1), 2, 10);
                    $result_parameter['strataStatus']['io2ConfigMiddleBit'] = base_convert(substr($byte_C, 7, 1), 2, 10);
                } else if ($gas_parameter_id == 102) {

                    $result_parameter['className'] = "TrolexVoltage";
                    $byte_A = self::AddZero(base_convert(hexdec(substr($package, $i + 2, 2)), 16, 2), 8);

                    $result_parameter['strataStatus']['softwareNodeTypeConfig'] = base_convert(substr($byte_A, 0, 1), 2, 10);
                    $result_parameter['strataStatus']['emergencyPowerMode'] = base_convert(substr($byte_A, 1, 1), 2, 10);
                    $result_parameter['strataStatus']['resetShutdownReason'] = base_convert(substr($byte_A, 2, 3), 2, 10);
                    $result_parameter['strataStatus']['io1ConfigMostSignificantBit'] = base_convert(substr($byte_A, 6, 1), 2, 10);
                    $result_parameter['strataStatus']['io2ConfigMostSignificantBit'] = base_convert(substr($byte_A, 7, 1), 2, 10);


                    $result_parameter['batteryVoltage'] = hexdec(substr($package, $i + 4, 2)) / 10;                     // B

                    $result_parameter['externalVoltage'] = hexdec(substr($package, $i + 6, 4));                         // C+D
                } else if ($gas_parameter_id == 103) {

                    $result_parameter['className'] = "TrolexSN";
                    $result_parameter['sn'] = hexdec(substr($package, $i + 2, 8));                                      // A + B + C + D
                }

                $result_parameter = (object)$result_parameter;
                $result['parameters'][] = new EnvironmentalParameter(
                    $gas_parameter_id,
                    $result_parameter
                );
                unset($result_parameter);

            }


            $result = (object)$result;
            $result = new EnvironmentalSensor($result);
        } catch (Throwable $exception) {
            $errors[] = "TranslateEnvironmental. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
            LogAmicum::LogAmicumStrata("TranslateEnvironmental", $package, $warnings, $errors);
        }
        $warnings[] = "TranslateEnvironmental. Закончил выполнять метод";

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    /**
     * Фунцкия сохранения параметров датчиков окружения
     * @param EnvironmentalSensor $pack
     * @param $mine_id
     * @return array
     */
    public static function saveEnvironmentalPacket(EnvironmentalSensor $pack, $mine_id)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("saveEnvironmentalPacket");

        try {
            $count_all = 0;                                                                                                 // количество вставленных записей
            $log->addLog("Начало выполнения метода");

            /** Метод начало */
            /**=================================================================
             * Получаем из кеша сенсор айди по нетворк айди
             * ==================================================================*/
            $network_id = $pack->nodeAddress;

            // Поиск объекта сенсора по сетевому идентификатору из пакета.
            // Если такой сенсор не найден, то создаём новый сенсор по шаблону.
            $response = SensorMainController::getOrSetSensorByNetworkId($network_id);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new \Exception("Ошибка при инициализации сенсора по сетевому адресу: " . $network_id);
            }

            if ($response['sensor_id'] === false) {//если sensor_id не найден, создать его
                $title = 'Метка прочее networkID ' . $network_id;
                $response = self::createSensorDatabase($title, $network_id, $mine_id, 104, 1, 4);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception("Ошибка создания сенсора по нетворк айди $network_id в кеше и в БД");
                }
            }

            $sensor_id = $response['sensor_id'];

            $log->addLog("Получили из кеша сенсор айди по нетворк айди");

            /**=================================================================
             * Инициализация кеша сенсора SensorMine
             * ==================================================================*/
            if ((new SensorCacheController())->getSensorMineBySensorHash($sensor_id) === false) {
                $response = SensorMainController::initSensorInCache($sensor_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception('Ошибка при инициализации сенсора');
                }
            }

            $log->addLog("Инициализировал кеш сенсора SensorMine");

            /**=================================================================
             * Нахождение последних значений параметров положения сенсора
             * ==================================================================*/
            $log->addLog("Нахождение последних значений параметров положения сенсора");
            $sensor_main_controller = new SensorMainController(Yii::$app->id, Yii::$app);
            // Находим последние значения координат, эджа и плейса
            $last_coord = $sensor_main_controller->getOrSetParameterValue($sensor_id, ParamEnum::COORD, ParameterTypeEnumController::MEASURED)['Items'];
            $last_place = $sensor_main_controller->getOrSetParameterValue($sensor_id, ParamEnum::PLACE_ID, ParameterTypeEnumController::MEASURED)['Items']['value'];
            $last_edge = $sensor_main_controller->getOrSetParameterValue($sensor_id, ParamEnum::EDGE_ID, ParameterTypeEnumController::MEASURED)['Items']['value'];

            $log->addLog("Определение статуса местоположения (разрешено/запрещено)");
            $log->addLog('последняя выработка, в которой был сенсор = ' . $last_edge);
            $log->addLog('последнее место, в котором был сенсор = ' . $last_place);
            $log->addData($last_coord, '$last_coord', __LINE__);
            $log->addLog("Нашел последние значения параметров положения сенсора");

            /**=================================================================
             * Определение статуса местоположения (разрешено/запрещено)
             * 15 - запретная
             * 16 - разрешенная
             * ==================================================================*/
            $edge_info = EdgeMainController::getEdgeMineDetail($mine_id, $last_edge);
            $log->addLogAll($edge_info);
            if ($edge_info && $edge_info['edge_info']) {
                $place_object_id = $edge_info['edge_info']['place_object_id'];
                $status_danger_zone = $edge_info['edge_info']['danger_zona'] == 1 ? StatusEnumController::FORBIDDEN : StatusEnumController::PERMITTED;
            } else {
                $place_object_id = -1;
                $status_danger_zone = StatusEnumController::PERMITTED;
            }
            $log->addLog('Статус местоположения (запретная-16/разрешенная-15) = ' . $status_danger_zone);
            $log->addLog("Проверил выработку на запретную/разрешенную");

            /**=================================================================
             * Определение идентификаторов параметра нужного газа, параметра
             * превышения конкретного газа и генерируемого события при превышении
             * ==================================================================*/
            switch ($pack->moduleType) {
                // Датчик CO
                case 0:
                    $log->addLog("Данные от датчика CO");
                    $parameter_id = ParamEnum::GAS_LEVEL_CO;
                    $parameter_excess_id = ParamEnum::GAS_EXCESS_CO;
                    $event_id = EventEnumController::CO_EXCESS_LAMP;
                    break;
                // Датчик CH4
                case 9:
                    if (WRITE_CH4_AS_TEMPERATURE) {
                        $log->addLog("Данные от датчика температуры");
                        /*Температура*/
                        $parameter_id = 9;
                        $parameter_excess_id = -1;
                        $event_id = -1;
                    }
                    break;
                // Датчик CH4
                case 20:
                    $log->addLog("Данные от датчика CH4");
                    $parameter_id = ParamEnum::GAS_LEVEL_CH4;
                    $parameter_excess_id = ParamEnum::GAS_EXCESS_CH4;
                    $event_id = EventEnumController::CH4_EXCESS_LAMP;
                    break;
                default:
                    throw new \Exception('. Данные от неизвестного датчика');
            }
            $log->addLog('Идентификатор параметра = ' . $parameter_id);
            $log->addLog('Идентификатор параметра превышения газа = ' . $parameter_excess_id);
            $log->addLog('Идентификатор события = ' . $event_id);


            /**=================================================================
             * Нахождение уставок газов для данного эджа
             * ==================================================================*/
            $log->addLog("Нахождение уставок газов для данного эджа");
            if ($parameter_id === ParamEnum::GAS_LEVEL_CO) {                                                        // CO
                $porog_val = $edge_info['value_co'] ?? 0.0017;                                                          // isset($edge_info['value_co']) ? $edge_info['value_co'] : 0.0017
            } else {                                                                                                    // CH4
                if (!WRITE_CH4_AS_TEMPERATURE) {
                    $porog_val = $edge_info['value_ch'] ?? 1;                                                           // isset($edge_info['value_ch']) ? $edge_info['value_ch'] : 1;
                } else {
                    $porog_val = 40;
                }
            }

            $log->addLog('Уставка газа ' . $porog_val);
            $log->addLog("Нахождение уставок газа выполнено");

            /**=================================================================
             * Вычисление наличия превышения газов
             * ==================================================================*/
            if (WRITE_CH4_AS_TEMPERATURE && $pack->moduleType == 9/*температура*/) {
                $status_id = StatusEnumController::ACTUAL;
                $is_gas_excess = -1;
                $porog_val = -1;
            } else {
                $log->addLog("Вычисление наличия превышения газов");
                $is_gas_excess = (($pack->gasLevelValue > $porog_val) ? 1 : 0);
                if ($is_gas_excess) {
                    $log->addLog("Есть превышение газа");
                    $status_id = StatusEnumController::EMERGENCY_VALUE;
                    // получаем тип места из $edge_info[']
                    //если превышение в ламповой, то генерируем отказ
                    // иначе сообщение летит в обычном режиме
                    if ($place_object_id == 80) {
                        $event_id = EventEnumController::CH4_CRUSH_LAMP;
                        $log->addLog("Отказ датчика. Показал газ в ламповой");
                    }
                } else {
                    $log->addLog("Нет превышения газа");
                    $status_id = StatusEnumController::NORMAL_VALUE;
                }
            }
            $log->addLog('Значение = ' . $pack->gasLevelValue);
            $log->addLog('Превышен газ = ' . $is_gas_excess);
            $log->addLog('Уставка газа = ' . $porog_val);
            $log->addLog('Статус газа (44 аварийное/45 нормальное) = ' . $status_id);
            $log->addLog("Вычислил наличие превышения газов");


            /**=================================================================
             * Сохранение параметров сенсора
             * ==================================================================*/
            $log->addLog(" Сохранение параметров сенсора");
            $response = self::saveEnvironmentalPacketSensorParameters($pack, $sensor_id, $porog_val,
                $last_coord, $last_place, $last_edge, $parameter_id, $status_id,
                $parameter_excess_id, $is_gas_excess);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new \Exception('Ошибка при сохранении параметров сенсора');
            }
            /**
             * Вычисление статусов в зависимости от значения превышения газа
             */
            if ($is_gas_excess) {
                $value_status_id = StatusEnumController::EMERGENCY_VALUE;
                $event_status_id = StatusEnumController::EVENT_RECEIVED;
            } else {
                $value_status_id = StatusEnumController::NORMAL_VALUE;
                $event_status_id = StatusEnumController::EVENT_ELIMINATED_BY_SYSTEM;
            }
            if (!WRITE_CH4_AS_TEMPERATURE && $pack->moduleType == 20) {
                $response = OpcController::actionCalcGasValueStaticMovement($sensor_id, $last_edge, $last_coord['value'], $mine_id, $pack->gasLevelValue);
                $log->addLogAll($response);

                $response = EventMainController::createEventFor('sensor', $sensor_id, 22409, $pack->gasLevelValue,
                    $pack->timestamp, $value_status_id, 99, $mine_id,
                    $event_status_id, $last_edge, $last_coord['value']);
                $log->addLogAll($response);
            } else if (!WRITE_CH4_AS_TEMPERATURE && $pack->moduleType == 10) {
                $response = EventMainController::createEventFor('sensor', $sensor_id, 22410, $pack->gasLevelValue,
                    $pack->timestamp, $value_status_id, 98, $mine_id,
                    $event_status_id, $last_edge, $last_coord['value']);
                $log->addLogAll($response);
            }

            $log->addLog("Сохранил значения параметров сенсора");

            /**=================================================================
             * Сохранение параметров воркера
             * ==================================================================*/
            $response = WorkerMainController::getWorkerInfoBySensorId($sensor_id);
            $log->addLogAll($response);
            $worker_sensor = $response['Items'];
            $log->addData($worker_sensor, '$worker_sensor', __LINE__);

            if ($worker_sensor) {
                $log->addLog("Найдена привязка сенсора к воркеру");
                $response = self::saveEnvironmentalPacketWorkerParameters($pack, $mine_id, $worker_sensor,
                    $status_danger_zone, $last_coord, $last_place, $last_edge, $parameter_id, $status_id,
                    $parameter_excess_id, $is_gas_excess, $event_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception('Ошибка при сохранении параметров воркера');
                }
            }

            $log->addLog("Сохранил значения параметров воркеров");

            /**=================================================================
             * Сохранение параметров оборудования
             * ==================================================================*/
            $response = EquipmentMainController::getEquipmentInfoBySensorId($sensor_id);
            $log->addLogAll($response);
            $equipment_sensor = $response['Items'];
            $log->addData($equipment_sensor, '$equipment_sensor', __LINE__);

            if ($equipment_sensor) {
                $log->addLog("Найдена привязка к оборудованию");
                $response = self::saveEnvironmentalPacketEquipmentParameters($pack, $equipment_sensor, $event_id,
                    $parameter_id, $status_id, $parameter_excess_id, $is_gas_excess, $mine_id, $last_edge);
                $log->addLog("Найдена привязка сенсора к воркеру");
                if ($response['status'] != 1) {
                    throw new \Exception('Ошибка при сохранении параметров оборудования');
                }
            }

            $log->addLog("Сохранил значения параметров оборудования");
            $log->addLog("Конец метода");

        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
            $data_to_log = array_merge(
                [
                    'Items' => $result,
                    'Method parameters' => ['pack' => $pack, 'mine_id' => $mine_id]
                ],
                $log->getLogAll()
            );
            LogCacheController::setStrataLogValue('saveEnvironmentalPacket', $data_to_log, '2');
        }
        $log->addLog("Окончил выполнение метода");

        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * Сохранение параметров сенсора из пакета газов
     * @param EnvironmentalSensor $pack
     * @param $sensor_id - ключ сенсора
     * @param $porog_val - пороговое значение газа
     * @param $last_coord - последняя координата
     * @param $last_place - ключ последнего места
     * @param $last_edge - ключ последней выработки
     * @param $parameter_id - ключ параметра газа (СО или СН4) 98/99
     * @param $status_id - ключ статуса значения
     * @param $parameter_excess_id - ключ параметра превышения газа
     * @param $is_gas_excess - флаг наличия превышения газа
     * @return array
     */
    public static function saveEnvironmentalPacketSensorParameters(EnvironmentalSensor $pack, $sensor_id, $porog_val,
                                                                                       $last_coord, $last_place, $last_edge, $parameter_id,
                                                                                       $status_id, $parameter_excess_id, $is_gas_excess)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей

        // Стартовая отладочная информация
        $log = new LogAmicumFront("saveEnvironmentalPacketSensorParameters");

        try {
            $log->addLog("Начало выполнения метода");

            /**=================================================================
             * Получаем за раз все последние значения по сенсору из кеша
             * =================================================================*/
            $sensor_parameter_value_cache_array = null;

            $sensor_cache_controller = new SensorCacheController();
            $sensor_parameter_value_list_cache = $sensor_cache_controller->multiGetParameterValueHash($sensor_id, '*', 2);

            if ($sensor_parameter_value_list_cache !== false) {
                foreach ($sensor_parameter_value_list_cache as $sensor_parameter_value_cache) {
                    $sensor_parameter_value_cache_array[$sensor_parameter_value_cache['sensor_id']][$sensor_parameter_value_cache['parameter_type_id']][$sensor_parameter_value_cache['parameter_id']] = $sensor_parameter_value_cache;
                }
                $log->addLog("Нашел данные по сенсору в кеше");
            } else {
                $log->addLog("Не нашел данные по сенсору в кеше");
                $log->addData($sensor_parameter_value_list_cache, '$sensor_parameter_value_list_cache', __LINE__);
                $log->addData($sensor_id, '$sensor_id', __LINE__);
            }

            /**=================================================================
             * Сохранение параметров положения и состояния для сенсора
             * ==================================================================*/
            // Coord (83)
            if ($last_coord && $last_coord['value']) {
                $response = self::saveSensorParameterBatchForce($sensor_id, ParameterTypeEnumController::MEASURED,
                    ParamEnum::COORD, $last_coord['value'], $pack->timestamp, $last_coord['status_id'], $sensor_parameter_value_cache_array);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception('Ошибка сохранения параметра ' . ParamEnum::COORD);
                }
//                if ($response['date_to_cache']) {
//                        $date_to_cache[] = $response['date_to_cache'];                                                // отключил, т.к. при таком раскладе не верно расчитывается скорость движения работника (там стоит проверка на время, а тут получается, что время обновляется  (на 1 минуту вырастает погрешность)
//                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            }

            // Place (122)
            if ($last_place) {
                $response = self::saveSensorParameterBatchForce($sensor_id, ParameterTypeEnumController::MEASURED,
                    ParamEnum::PLACE_ID, $last_place, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception('Ошибка сохранения параметра ' . ParamEnum::PLACE_ID);
                }
//                if ($response['date_to_cache']) {
//                        $date_to_cache[] = $response['date_to_cache'];                                                  // отключил, т.к. при таком раскладе не верно расчитывается скорость движения работника (там стоит проверка на время, а тут получается, что время обновляется  (на 1 минуту вырастает погрешность)
//                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            }
            // Edge (269)
            if ($last_edge) {
                $response = self::saveSensorParameterBatchForce($sensor_id, ParameterTypeEnumController::MEASURED,
                    ParamEnum::EDGE_ID, $last_edge, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception('Ошибка сохранения параметра ' . ParamEnum::EDGE_ID);
                }
//                if ($response['date_to_cache']) {
//                        $date_to_cache[] = $response['date_to_cache'];                                                  // отключил, т.к. при таком раскладе не верно расчитывается скорость движения работника (там стоит проверка на время, а тут получается, что время обновляется  (на 1 минуту вырастает погрешность)
//                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }
            }

            // Состояние (164)
            $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::CALCULATED,
                ParamEnum::STATE, 1, $pack->timestamp, StatusEnumController::ACTUAL, $sensor_parameter_value_cache_array);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new \Exception('Ошибка сохранения параметра ' . ParamEnum::STATE);
            }

            if ($response['date_to_cache']) {
                $date_to_cache[] = $response['date_to_cache'];
            }
            if ($response['date_to_db']) {
                $date_to_db[] = $response['date_to_db'];
            }


            if (WRITE_CH4_AS_TEMPERATURE) {
                /**=================================================================
                 * Сохранение значений параметра температуры для сенсоров
                 * ==================================================================*/
                $response = self::saveSensorParameter($sensor_id, ParameterTypeEnumController::MEASURED,
                    $parameter_id, $pack->gasLevelValue, $pack->timestamp, $status_id);
                $log->addLogAll($response);
            } else {
                /**=================================================================
                 * Сохранение значений параметров газа для сенсоров
                 * ==================================================================*/
                $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED,
                    $parameter_id, $pack->gasLevelValue, $pack->timestamp, $status_id, $sensor_parameter_value_cache_array);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception('Ошибка сохранения параметра ' . $parameter_id);
                }

                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }


                $response = self::saveSensorParameterBatch($sensor_id, ParameterTypeEnumController::MEASURED,
                    $parameter_excess_id, $is_gas_excess, $pack->timestamp, $status_id, $sensor_parameter_value_cache_array);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception('Ошибка сохранения параметра превышения газа ' . $parameter_excess_id);
                }
                if ($response['date_to_cache']) {
                    $date_to_cache[] = $response['date_to_cache'];
                }
                if ($response['date_to_db']) {
                    $date_to_db[] = $response['date_to_db'];
                }

                $log->addLog("Закончил подготовку данных");

                /**=================================================================
                 * блок массовой вставки значений в БД
                 * =================================================================*/

                if (isset($date_to_db)) {
//                    Yii::$app->db_amicum2->createCommand()->batchInsert('sensor_parameter_value',
//                        ['sensor_parameter_id', 'date_time', 'value', 'status_id'],
//                        $date_to_db)->execute();
                    $insert_param_val = Yii::$app->db_amicum2->queryBuilder->batchInsert('sensor_parameter_value', ['sensor_parameter_id', 'date_time', 'value', 'status_id'], $date_to_db);
                    Yii::$app->db_amicum2->createCommand($insert_param_val)->execute();
//                    Yii::$app->db_amicum2->createCommand($insert_param_val . " ON DUPLICATE KEY UPDATE `value` = VALUES (`value`), `status_id` = VALUES (`status_id`)")->execute();
                }

                $log->addLog("Массово вставил данные в БД");

                /**=============================================================
                 * блок массовой вставки значений в кеш
                 * =============================================================*/
                if (isset($date_to_cache)) {
                    $ask_from_method = (new SensorCacheController)->multiSetSensorParameterValueHash($date_to_cache);
                    $log->addLogAll($ask_from_method);
                    if ($ask_from_method['status'] != 1) {
                        throw new \Exception('Не смог обновить параметры в кеше сенсора ' . $sensor_id);
                    }
                }

                $log->addLog("Массово вставил данные в Кеш");

                /**=================================================================
                 * Добавление записи в таблицу summary_report_sensor_gas_concentration,
                 * если есть превышение нормы
                 * ==================================================================*/
                if ($is_gas_excess) {
                    self::AddSummaryReportSensorGasConcentrationRecord(
                        $sensor_id,
                        $parameter_id,
                        $pack->gasLevelValue,
                        $porog_val,
                        $pack->timestamp,
                        $last_edge,
                        $last_place
                    );
                    $log->addLog("Добавление записи в таблицу summary_report_sensor_gas_concentration");
                }
            }
            $log->addLog("Конец метода");
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        return array_merge(['Items' => $result], $log->getLogAll());
    }

    /**
     * saveSensorParameter - Функция для сохранения параметра датчика в кеш и БД.
     * @param $sensor_id -   идентификатор сенсора
     * @param $typeParameterParameterId -   связка типа параметра и его id (например 2-269)
     * @param $value -   новое значение, которое будет записываться
     * @param $datetime -   метка времени, записываемого значения
     * @param $status_id -   статус записываемого значения
     * @return array
     */
    public static function saveSensorParameter($sensor_id, $parameter_type_id, $parameter_id, $value, $datetime, $status_id)
    {
        $result = null;                                                                                                 // результирующий массив (если требуется)
        $count_record = 0;                                                                                              // количество обработанных записей
        $value_database_id = -1;

        // Стартовая отладочная информация
        $log = new LogAmicumFront("saveSensorParameter");

        try {
            /**
             * Проверка актуальности значения параметра
             */
            $response = SensorMainController::IsChangeSensorParameterValue($sensor_id, $parameter_id, $parameter_type_id, $value, $datetime);
            $log->addLogAll($response);
            if ($response['status'] != 1) {
                throw new \Exception("Ошибка при проверке актуальности параметра $parameter_id-$parameter_type_id сенсора $sensor_id");
            }

            if ($response['flag_save']) {
                /**
                 * Получение идентификатора параметра сенсора (из таблицы sensor_parameter)
                 */
                $response = SensorMainController::GetOrSetSensorParameter($sensor_id, $parameter_id, $parameter_type_id);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception("Ошибка при получении параметра $parameter_id-$parameter_type_id сенсора $sensor_id");
                }

                $sensor_parameter_id = $response['sensor_parameter_id'];

                /**
                 * Сохранение значения параметра в БД
                 */
                $response = SensorBasicController::addSensorParameterValue($sensor_parameter_id, $value, $status_id, $datetime);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception("Ошибка при сохранении значения в БД параметра $parameter_id-$parameter_type_id сенсора $sensor_id");
                }

                $value_database_id = $response['sensor_parameter_value_id'];

                /**
                 * Сохранение значения параметра в кеш
                 */
                $response = (new SensorCacheController())->setSensorParameterValueHash($sensor_id, $sensor_parameter_id, $value, $parameter_id, $parameter_type_id, $status_id, $datetime);
                $log->addLogAll($response);
                if ($response['status'] != 1) {
                    throw new \Exception('Значение сенсора ' . $sensor_id . ' не сохранено в кеш. Параметр ' . $parameter_id);
                }
            } else {
                $log->addLog("Значение параметра актуально");
            }
        } catch (Throwable $ex) {
            $log->addError($ex->getMessage(), $ex->getLine());
        }
        $log->addLog("Закончил метод");

        return array_merge(['Items' => $result, 'sensor_parameter_value_id' => $value_database_id], $log->getLogAll());
    }

    /**
     * Метод для добавления записи в отчётную таблицу summary_report_sensor_gas_concentration
     * @param int $sensor_id -   массив с информацией о сенсоре (запись из таблицы sensor)
     * @param $parameter_id -   идентификатор параметра
     * @param $fact_value -   фактическое значение параметра
     * @param $nominal_value -   уставка (пороговое значение) газа на выработке
     * @param $date_time -   дата и время получения значения параметра
     * @param $edge_id -   идентификатор выработки
     * @param $place_id -   идентификатор местоположения
     * @throws \yii\db\Exception    при ошибке записи значения в базу
     */
    public static function AddSummaryReportSensorGasConcentrationRecord(
        $sensor_id,
        $parameter_id,
        $fact_value,
        $nominal_value,
        $date_time,
        $edge_id,
        $place_id
    )
    {
        $sensor_title = (new Query())
            ->select('title')
            ->from('sensor')
            ->where([
                'id' => $sensor_id
            ])
            ->scalar();

        $place_title = (new Query())
            ->select('title')
            ->from('place')
            ->where([
                'id' => $place_id
            ])
            ->scalar();

        $parameter = Parameter::findOne(['id' => $parameter_id]);

        if ($parameter) {
            Yii::$app->db_amicum2->createCommand()
                ->insert('summary_report_sensor_gas_concentration', [
                    'sensor_id' => $sensor_id,
                    'sensor_title' => $sensor_title,
                    'parameter_id' => $parameter_id,
                    'gas_fact_value' => $fact_value,
                    'edge_gas_nominal_value' => $nominal_value,
                    'date_time' => $date_time,
                    'edge_id' => $edge_id,
                    'place_title' => $place_title,
                    'unit_title' => $parameter->unit->short,
                    'place_id' => $place_id,
                    'parameter_title' => $parameter->title
                ])->execute();
        }
    }

    /**
     * Сохранение параметров воркера из пакета газов
     * @param EnvironmentalSensor $pack
     * @param $mine_id - ключ шахты работника
     * @param $worker_sensor - ключ лампы работника
     * @param $status_danger_zone - флаг опасной зоны
     * @param $last_coord - последняя координата, где был зафиксирован шахтер
     * @param $last_place - ключ последнего места где был зафиксирован шахтер
     * @param $last_edge - ключ последней выработки где был зафиксирован шахтер
     * @param $parameter_id - ключ параметра
     * @param $status_id - ключ статуса значения парамтера
     * @param $parameter_excess_id - параметр по которому было выявлено превышение
     * @param $is_gas_excess - флаг наличия превышения газа
     * @param $event_id - ключ события
     * @return array
     */
    public static function saveEnvironmentalPacketWorkerParameters(EnvironmentalSensor $pack, $mine_id, $worker_sensor, $status_danger_zone,
                                                                                       $last_coord, $last_place, $last_edge, $parameter_id, $status_id,
                                                                                       $parameter_excess_id, $is_gas_excess, $event_id)
    {
        // Стартовая отладочная информация
        $method_name = 'saveEnvironmentalPacketWorkerParameters';                                                                             // название логируемого метода
        $debug = array();                                                                                               // блок отладочной информации
        $log_id = null;                                                                                                 // ключ записи лога
        $count_all = 0;                                                                                              // количество вставленных записей
        $duration_summary = null;                                                                                       // общая продолжительность выполнения скрипта
        $max_memory_peak = null;                                                                                        // пиковое потребление памяти при выполнении скрипта
        $number_row_affected = null;                                                                                    // количество обработанных строк первичных данных
        $microtime_start = microtime(true);                                                                 // начало выполнения скрипта
        $microtime_current = microtime(true);                                                               // текущая отметка времени выполнения скрипта
        $date_time_debug_start = date('Y-m-d H:i:s', strtotime(Assistant::GetDateNow()));                       // время начала выполнения метода
        $date_time_debug_end = null;                                                                                    // время окончания выполнения скрипта

        // базовые входные параметры скрипта
        $errors = array();                                                                                              // блок ошибок
        $warnings = array();                                                                                            // блок предупреждений (шаги выполнения скрипта)
        $result = array();                                                                                              // результирующий массив (если требуется)
        $status = 1;                                                                                                    // статус выполнения скрипта

        try {
            /** Отладка */
            $description = 'Начало выполнения метода';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            if (isset($last_coord['date_time']) and $last_coord['date_time'] != "") {                                      // если из предыдущего пакета (с которого берем координаты) совпадают с текущей датой, то координату не перезаписываем
                $last_package_date_time = $last_coord['date_time'];
            } else {
                $last_package_date_time = "";
            }
            /**
             * получаем за раз все последние значения по воркеру из кеша
             */
            $worker_cache_controller = new WorkerCacheController();
            $worker_parameter_value_list_cache = $worker_cache_controller->multiGetParameterValueHash($worker_sensor['worker_id'], '*', '*');
            if ($worker_parameter_value_list_cache === false) {
                $worker_parameter_value_cache_array = null;
                $warnings[] = $method_name . '. НЕ получил данные с кеша по всему воркеру. Кеш воркера ПУСТ';
            } else {
                foreach ($worker_parameter_value_list_cache as $worker_parameter_value_cache) {
                    $worker_parameter_value_cache_array[$worker_parameter_value_cache['worker_id']][$worker_parameter_value_cache['parameter_type_id']][$worker_parameter_value_cache['parameter_id']] = $worker_parameter_value_cache;
                }
                $warnings[] = $method_name . '. получил данные с кеша по всему воркеру. Кеш воркера ПОЛОН';
            }

            /** Отладка */
            $description = 'Получил за раз все последние значения по воркеру из кеша';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            /**=================================================================
             * Сохранение параметров положения и состояния для воркера
             * ==================================================================*/
            $warnings[] = $method_name . '. Обработка параметров положения и состояния для воркера';
            //Сохранить параметр воркера Координаты (83)
            $xyz = '';
            if ($last_coord && $last_coord['value'] && $pack->timestamp != $last_package_date_time) {
                $xyz = $last_coord['value'];
                $response = self::saveWorkerParameterForceBatch($worker_sensor['worker_id'], ParameterTypeEnumController::MEASURED, ParamEnum::COORD, $last_coord['value'], $pack->timestamp, $last_coord['status_id']);
                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    if ($response['date_to_cache']) {
                        $date_to_cache[] = $response['date_to_cache'];
                    }
                    if ($response['date_to_db']) {
                        $date_to_db[] = $response['date_to_db'];
                    }
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    $errors[] = $method_name . '. Ошибка сохранения параметра ' . ParamEnum::COORD;
                    //throw new \Exception($method_name . '. Ошибка сохранения параметра ' . ParameterEnumController::COORD);
                }
            }
            /** Отладка */
            $description = 'Обработал координату и состояние работника (но не сохранил)';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            //Сохранить параметр воркера Местоположение (122)
            if ($last_place && $pack->timestamp != $last_package_date_time) {
                $response = self::saveWorkerParameterForceBatch($worker_sensor['worker_id'], ParameterTypeEnumController::MEASURED, ParamEnum::PLACE_ID, $last_place, $pack->timestamp, $status_danger_zone);
                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    if ($response['date_to_cache']) {
                        $date_to_cache[] = $response['date_to_cache'];
                    }
                    if ($response['date_to_db']) {
                        $date_to_db[] = $response['date_to_db'];
                    }
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    $errors[] = $method_name . '. Ошибка сохранения параметра ' . ParamEnum::PLACE_ID;
                    //throw new \Exception($method_name . '. Ошибка сохранения параметра ' . ParameterEnumController::PLACE_ID);
                }
            }

            /** Отладка */
            $description = 'Обработал место (place) работника (но не сохранил)';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            //Сохранить параметр воркера Местоположение (269)
            if ($last_edge && $pack->timestamp != $last_package_date_time) {
                $response = self::saveWorkerParameterForceBatch($worker_sensor['worker_id'], ParameterTypeEnumController::MEASURED, ParamEnum::EDGE_ID, $last_edge, $pack->timestamp, StatusEnumController::ACTUAL);
                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    if ($response['date_to_cache']) {
                        $date_to_cache[] = $response['date_to_cache'];
                    }
                    if ($response['date_to_db']) {
                        $date_to_db[] = $response['date_to_db'];
                    }
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    $errors[] = $method_name . '. Ошибка сохранения параметра ' . ParamEnum::EDGE_ID;
                    //throw new \Exception($method_name . '. Ошибка сохранения параметра ' . ParameterEnumController::EDGE_ID);
                }
            }
            /** Отладка */
            $description = 'Обработал выработку (edge) работника (но не сохранил)';                                                                      // описание текущей отладочной точки
            $description = $method_name . ' ' . $description;
            $warnings[] = $description;                                                                                     // описание текущей отладочной точки
            $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
            $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
            $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
            $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
            $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
            $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
            $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
            $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
            $microtime_current = microtime(true);
            /** Окончание отладки */

            if (WRITE_CH4_AS_TEMPERATURE && $parameter_id == 99) {
                /**=================================================================
                 * Сохранение значений параметра температуры для воркера
                 * ==================================================================*/
                $warnings[] = $method_name . '. Сохранение значений параметра температуры для воркера';
                $response = self::saveWorkerParameterBatch(
                    $worker_sensor['worker_id'],
                    ParameterTypeEnumController::MEASURED, $parameter_id,
                    $pack->gasLevelValue,
                    $pack->timestamp,
                    $status_id,
                    $worker_parameter_value_cache_array
                );
                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    if ($response['date_to_cache']) {
                        $date_to_cache[] = $response['date_to_cache'];
                    }
                    if ($response['date_to_db']) {
                        $date_to_db[] = $response['date_to_db'];
                    }
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new \Exception($method_name . '. Ошибка сохранения параметра ' . $parameter_id);
                }
            } else {
                /**=================================================================
                 * Сохранение значений параметров газа для воркера
                 * ==================================================================*/
                $warnings[] = $method_name . '. Сохранение значений параметров газа для воркера';
                $response = self::saveWorkerParameterBatch(
                    $worker_sensor['worker_id'],
                    ParameterTypeEnumController::MEASURED, $parameter_excess_id,
                    $is_gas_excess,
                    $pack->timestamp,
                    $status_id,
                    $worker_parameter_value_cache_array
                );
                if ($response['status'] == 1) {
                    //$warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    if ($response['date_to_cache']) {
                        $date_to_cache[] = $response['date_to_cache'];
                    }
                    if ($response['date_to_db']) {
                        $date_to_db[] = $response['date_to_db'];
                    }
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new \Exception($method_name . '. Ошибка сохранения параметра ' . $parameter_excess_id);
                }

                /** Отладка */
                $description = 'Обработал Параметр статус превышения (386(метан)/387(СО)) газа работника (но не сохранил)' . $parameter_excess_id;                                                                      // описание текущей отладочной точки
                $description = $method_name . ' ' . $description;
                $warnings[] = $description;                                                                                     // описание текущей отладочной точки
                $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
                $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
                $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
                $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
                $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
                $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
                $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
                $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
                $microtime_current = microtime(true);
                /** Окончание отладки */

                /**
                 * Вычисление статусов в зависимости от значения превышения газа
                 */
                if ($is_gas_excess) {
                    $value_status_id = StatusEnumController::EMERGENCY_VALUE;
                    $event_status_id = StatusEnumController::EVENT_RECEIVED;
                } else {
                    $value_status_id = StatusEnumController::NORMAL_VALUE;
                    $event_status_id = StatusEnumController::EVENT_ELIMINATED_BY_SYSTEM;
                }

                /** Отладка */
                $description = 'Обработал Статус превышения (386(метан)/387(СО)) газа у работника (но не сохранил)';                                                                      // описание текущей отладочной точки
                $description = $method_name . ' ' . $description;
                $warnings[] = $description;                                                                                     // описание текущей отладочной точки
                $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
                $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
                $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
                $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
                $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
                $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
                $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
                $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
                $microtime_current = microtime(true);
                /** Окончание отладки */

                /**
                 * Сохранение значения концентрации газа
                 */
                $warnings[] = $method_name . '. Сохранение значения газа';
                $response = self::saveWorkerParameterBatch(
                    $worker_sensor['worker_id'],
                    ParameterTypeEnumController::MEASURED, $parameter_id,
                    $pack->gasLevelValue,
                    $pack->timestamp,
                    StatusEnumController::ACTUAL,
                    $worker_parameter_value_cache_array
                );
                if ($response['status'] == 1) {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    if ($response['date_to_cache']) {
                        $date_to_cache[] = $response['date_to_cache'];
                    }
                    if ($response['date_to_db']) {
                        $date_to_db[] = $response['date_to_db'];
                    }
                } else {
                    $warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new \Exception($method_name . '. Ошибка сохранения параметра ' . $parameter_id);
                }

                /** Отладка */
                $description = 'Обработал значение газа (98(СО)/99(метан)) работника (но не сохранил)' . $parameter_id;                                                                      // описание текущей отладочной точки
                $description = $method_name . ' ' . $description;
                $warnings[] = $description;                                                                                     // описание текущей отладочной точки
                $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
                $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
                $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
                $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
                $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
                $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
                $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
                $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
                $microtime_current = microtime(true);
                /** Окончание отладки */

                /**=================================================================
                 * блок массовой вставки значений в БД
                 * =================================================================*/
                if (isset($date_to_db)) {
//                    Yii::$app->db_amicum2->createCommand()->batchInsert('worker_parameter_value',
//                        ['worker_parameter_id', 'date_time', 'value', 'status_id', 'shift', 'date_work'],
//                        $date_to_db)->execute();
                    $insert_param_val = Yii::$app->db_amicum2->queryBuilder->batchInsert('worker_parameter_value', ['worker_parameter_id', 'date_time', 'value', 'status_id', 'shift', 'date_work'], $date_to_db);
//                    Yii::$app->db_amicum2->createCommand($insert_param_val)->execute();
                    Yii::$app->db_amicum2->createCommand($insert_param_val . " ON DUPLICATE KEY UPDATE `value` = VALUES (`value`), `status_id` = VALUES (`status_id`), `shift` = VALUES (`shift`), `date_work` = VALUES (`date_work`)")->execute();
                }

                /** Отладка */
                $description = 'Массовая вставка параметров в БД';                                                                      // описание текущей отладочной точки
                $description = $method_name . ' ' . $description;
                $warnings[] = $description;                                                                                     // описание текущей отладочной точки
                $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
                $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
                $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
                $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
                $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
                $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
                $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
                $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
                $microtime_current = microtime(true);
                /** Окончание отладки */

                /**
                 * блок массовой вставки значений в кеш
                 */
                if (isset($date_to_cache)) {
//                    $warnings[] = $worker_sensor['worker_id'];
                    $ask_from_method = (new WorkerCacheController)->multiSetWorkerParameterValueHash($date_to_cache, $worker_sensor['worker_id']);
                    if ($ask_from_method['status'] == 1) {
                        $warnings[] = $ask_from_method['warnings'];
                        $warnings[] = $method_name . '. обновил параметры работника в кеше';
                    } else {
                        $warnings[] = $ask_from_method['warnings'];
                        $errors[] = $ask_from_method['errors'];
                        throw new \Exception($method_name . '. Не смог обновить параметры в кеше работника' . $worker_sensor['worker_id']);
                    }
                }

                /** Отладка */
                $description = 'Массовая вставка параметров в кеш';                                                                      // описание текущей отладочной точки
                $description = $method_name . ' ' . $description;
                $warnings[] = $description;                                                                                     // описание текущей отладочной точки
                $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
                $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
                $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
                $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
                $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
                $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
                $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
                $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
                $microtime_current = microtime(true);
                /** Окончание отладки */

                /**
                 * Генерация события для концентрации газа
                 */
                if ($event_id != 22410 and $event_id != -1) {
                    $response = EventMainController::createEventForWorkerGas('worker', $worker_sensor['worker_id'], $event_id, $pack->gasLevelValue,
                        $pack->timestamp, $value_status_id, $parameter_id, $mine_id,
                        $event_status_id, $last_edge, $xyz, $worker_sensor['sensor_id']);
                    if ($response['status'] == 1) {
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        $debug['createEventForWorkerGas'] = $response['debug'];
                    } else {
                        $warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        $debug['createEventForWorkerGas'] = $response['debug'];
                        throw new \Exception($method_name . '. Не смог создать событие работника' . $worker_sensor['worker_id']);
                    }
                } else {
                    /** Отладка */
                    $description = 'ПРОПУСК СОБЫТИЯ ПО СО';                                                                      // описание текущей отладочной точки
                    $description = $method_name . ' ' . $description;
                    $warnings[] = $description;                                                                                     // описание текущей отладочной точки
                    $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
                    $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
                    $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
                    $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
                    $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
                    $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
                    $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
                    $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
                    $microtime_current = microtime(true);
                    /** Окончание отладки */
                }

                // старый метод генерации чисто события без обработки
//                $response = EventMainController::createEventFor('worker', $worker_sensor['worker_id'], $event_id, $pack->gasLevelValue,
//                    $pack->timestamp, $value_status_id, $parameter_id, $mine_id,
//                    $event_status_id, $last_edge);
//                if ($response['status'] == 1) {
//                    $warnings[] = $response['warnings'];
//                    $errors[] = $response['errors'];
//                } else {
//                    $warnings[] = $response['warnings'];
//                    $errors[] = $response['errors'];
//                }
                /** Отладка */
                $description = 'Сгенерировал событие';                                                                      // описание текущей отладочной точки
                $description = $method_name . ' ' . $description;
                $warnings[] = $description;                                                                                     // описание текущей отладочной точки
                $debug['description'][] = $description;                                                                         // описание текущей отладочной точки
                $max_memory_peak = memory_get_peak_usage() / 1024;                                                              // текущее пиковое значение использованной памяти
                $debug['memory_peak'][] = $max_memory_peak . ' ' . $description;                                                // текущее пиковое значение использованной памяти
                $debug['memory'][] = memory_get_usage() / 1024 . ' ' . $description;                                            // текущее количество использованной памяти
                $duration_summary = round(microtime(true) - $microtime_start, 6);                      // общая продолжительность выполнения скрипта
                $debug['durationSummary'][] = $duration_summary . ' ' . $description;                                           // итоговая продолжительность выполнения скрипта
                $debug['durationCurrent'][] = round(microtime(true) - $microtime_current, 6) . ' ' . $description;  // продолжительность выполнения текущего куска кода
                $debug['number_row_affected'][] = 'Кол-во записей: ' . $count_all . 'шт. ' . $method_name . ' ' . $description;                                                                          // количество обработанных записей
                $microtime_current = microtime(true);
                /** Окончание отладки */
            }

            $warnings[] = $method_name . '. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = $method_name . '. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }
        $warnings[] = $method_name . '. Закончил метод';
        return array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings, 'debug' => $debug);
    }

    /**
     * Сохранение параметров оборудования из пакета газов
     * @param EnvironmentalSensor $pack
     * @param $equipment_sensor
     * @param $event_id
     * @param $parameter_id
     * @param $status_id
     * @param $parameter_excess_id
     * @param $is_gas_excess
     * @param $mine_id
     * @param $edge_id
     * @return array
     */
    public static function saveEnvironmentalPacketEquipmentParameters(EnvironmentalSensor $pack, $equipment_sensor,
                                                                                          $event_id, $parameter_id,
                                                                                          $status_id, $parameter_excess_id, $is_gas_excess, $mine_id, $edge_id)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();                                                                                              // Массив ошибок
        $result = array();

        try {
            //$warnings[] = 'saveEnvironmentalPacketEquipmentParameters. Начало метода';

            // TODO: исправить. При получении СО не сохранит значение превышения газа
            if (WRITE_CH4_AS_TEMPERATURE) {
                /**=================================================================
                 * Сохранение значения параметра температуры
                 * ==================================================================*/
                //$warnings[] = __FUNCTION__ . '. Сохранение значения параметра температуры';
                self::SaveEquipmentParameter(
                    $equipment_sensor['equipment_id'],
                    ParameterTypeEnumController::MEASURED, $parameter_id,
                    $pack->gasLevelValue,
                    $pack->timestamp,
                    $status_id
                );
            } else {
                /**=================================================================
                 * Сохранение значения превышения газа
                 * ==================================================================*/
                //$warnings[] = __FUNCTION__ . '. Сохранение значения превышения газа';
                self::SaveEquipmentParameter(
                    $equipment_sensor['equipment_id'],
                    ParameterTypeEnumController::MEASURED, $parameter_excess_id,
                    $is_gas_excess,
                    $pack->timestamp,
                    $status_id
                );

                /**=================================================================
                 * Сохранение значения концентрации газа с генерацией события если нужно
                 * ==================================================================*/
                //$warnings[] = __FUNCTION__ . '. Сохранение значения концентрации газа с генерацией события если нужно';
                if ($is_gas_excess) {
                    //$warnings[] = __FUNCTION__ . '. Сохранение значения газа с событием';
                    $value_status_id = StatusEnumController::EMERGENCY_VALUE;
                    $event_status_id = StatusEnumController::EVENT_RECEIVED;
                } else {
                    //$warnings[] = __FUNCTION__ . '. Сохранение значения газа без события';
                    $value_status_id = StatusEnumController::NORMAL_VALUE;
                    $event_status_id = StatusEnumController::EVENT_ELIMINATED_BY_SYSTEM;
                }

                /**
                 * Сохранение концентрации газа
                 */
                $response = self::SaveEquipmentParameter(
                    $equipment_sensor['equipment_id'],
                    ParameterTypeEnumController::MEASURED, $parameter_id,
                    $pack->gasLevelValue,
                    $pack->timestamp,
                    $status_id
                );
                if ($response['status'] == 1) {
                    //$warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                } else {
                    //$warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                    throw new \Exception(__FUNCTION__ . '. Ошибка сохранения параметра ' . $parameter_id);
                }

                /**
                 * Генерация события для превышения концентрации газа
                 */
                $response = EventMainController::createEventFor('equipment', $equipment_sensor['equipment_id'],
                    $event_id, $pack->gasLevelValue, $pack->timestamp, $value_status_id, $parameter_id, $mine_id,
                    $event_status_id, $edge_id);
                if ($response['status'] == 1) {
                    //$warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                } else {
                    //$warnings[] = $response['warnings'];
                    $errors[] = $response['errors'];
                }
            }

            //$warnings[] = __FUNCTION__ . '. Конец метода';
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'saveEnvironmentalSensorParameters. Исключение: ';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }
        $warnings = 'saveEnvironmentalSensorParameters. Закончил метод';
        return array('Items' => $result, 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
    }


    // TranslateHeartbeat - метод расшифровки пакетов Strata:
    // 04 - CommunicationNodeHeartbeat
    // получает на вход битовый пакет и расшифровывает его
    // пример: 127.0.0.1/admin/strata-job/translate-package?ip='172.16.52.5'&date_time=2020-06-08 15:00:00&package=8fea040c3c00400500bbaa004007bc200400bbaa004007bc0004000000c007800104030201010023fc74ab - пакет хардбита - целый
    // пример: 127.0.0.1/admin/strata-job/translate-package?ip='172.16.52.5'&date_time=2020-06-08 15:00:00&package=8fea040c3c00400500bbaa004007bc200400bbaa004007bc0004000000c007800104030201010023fc74ab - пакет хардбита - касячный в страте
    // пример: 127.0.0.1/admin/strata-job/translate-package?ip='172.16.52.5'&date_time=2020-06-08 15:00:00&package=8fea10028a187c046400000000650003000c6600270000670000000079d58fea04083d00490b00474c0048ccc0300700bc9d0048ccc0000f000000c00780010403020101000e88d9ba8fea0104278a80c00002004895eb00415aad818b8fea10098b0a64046400120014650201000066002700006700000000754c8fea0100278ad16d060200528dd7004ab1b1d345 - пакет хардбита слипшийся - касячный в страте

    static function TranslateHeartbeat($package)
    {
        $status = 1;                                                                                                    // Флаг успешного выполнения метода
        $warnings = array();                                                                                            // Массив предупреждений
        $errors = array();
        $result = array();
        try {
            $warnings[] = "TranslateHeartbeat. Начал выполнять метод";
            $warnings[] = "TranslateHeartbeat. Длина пакета: " . strlen($package);
            $result['timestamp'] = Assistant::GetDateNow();                                                             // временная отметка
            $result['sequenceNumber'] = hexdec(substr($package, 0, 2));                                                 // последовательный номер сигнала узла
            $result['batteryVoltage'] = (string)str_replace(',', '.', hexdec(substr($package, 2, 2)) / 10);             // напряжение батареи узла связи

            // байт 5-7
            $result['sourceNode'] = hexdec(substr($package, 4, 6));                                                     // сетевой адрес узла-источника сигнала
            $result['sourceNetworkId'] = hexdec(substr($package, 4, 6));                                                // сетевой адрес узла-источника сигнала

            // байт 8-10
            $result['routingRootNodeAddress'] = hexdec(substr($package, 10, 6));                                        // адрес шлюза (корня) маршрутизации

            // байт 11-13
            $result['routingParentNode'] = new HearedNode(
                hexdec(substr($package, 16, 6)),                                                                        //сетевой адрес шлюза синхронизации родительский времени
                hexdec(substr($package, 22, 2)) - 256                                                                   //уровень сигнала сетевого адреса шлюза синхронизации родительский времени (14 байт)
            );

            $package_service_byte = self::AddZero(base_convert(substr($package, 24, 2), 16, 2), 8);

            $result['neighborTableFull'] = base_convert(substr($package_service_byte, 0, 1), 2, 10);                    //флаг заполненности таблицы соседей
            $result['neighborCount'] = base_convert(substr($package_service_byte, 1, 3), 2, 10);                        //количество соседних узлов (от 0 до 7)

            $routingRootHops_hex = substr($package_service_byte, 4, 4) . substr($package, 26, 2); // + байт 16
            $result['routingRootHops'] = base_convert($routingRootHops_hex, 2, 10);                                     //количество транзитных участков до шлюза маршрутизации MSB

            // байт 17-19
            $result['timingRootNodeAddress'] = hexdec(substr($package, 28, 6));                                         //сетевой адрес шлюза синхронизации корневой времени

            // байт 20-22
            $result['timingParentNode'] = new HearedNode(
                hexdec(substr($package, 34, 6)),                                                                        //сетевой адрес шлюза синхронизации родительский времени
                hexdec(substr($package, 40, 2)) - 256                                                                   //уровень сигнала сетевого адреса шлюза синхронизации родительский времени
            );

            $result['timingRootHops'] = hexdec(substr($package, 42, 4));                                                //количество транзитных участков до шлюза синхронизации
            $result['lostRoutingParent'] = hexdec(substr($package, 46, 2));                                             //количество потерь родителя маршрутизации с момента последнего heartbeat-сообщения
            $result['lostTimingParent'] = hexdec(substr($package, 48, 2));                                              //количество потерь родителя синхронизации с момента последнего heartbeat-сообщения

            $package_service_byte = self::AddZero(base_convert(substr($package, 50, 2), 16, 2), 8);                      // 28 байт

            $result['routingChangeParents'] = base_convert(substr($package_service_byte, 0, 4), 2, 10);                 //количество изменений родителя маршрутизации
            $result['timingChangeParents'] = base_convert(substr($package_service_byte, 4, 4), 2, 10);                  //количество изменений родителя синхронизации

            $package_service_byte = self::AddZero(base_convert(substr($package, 52, 2), 16, 2), 8);                                       // 29 байт

            $result['routingAboveThresh'] = base_convert(substr($package_service_byte, 0, 1), 2, 10);                   //флаг превышения порога уровня сигнала родителем маршрутизации
            $result['timingAboveThresh'] = base_convert(substr($package_service_byte, 1, 1), 2, 10);                    //флаг превышения порога уровня сигнала родителем синхронизации
            $result['queueOverflow'] = base_convert(substr($package_service_byte, 2, 6), 2, 10);                        //размер переполнения очереди
            $result['queueOverfow'] = base_convert(substr($package_service_byte, 2, 6), 2, 10);                         //размер переполнения очереди

            $package_service_byte = self::AddZero(base_convert(substr($package, 54, 2), 16, 2), 8);                                        // 30 байт

            $result['netEntryCount'] = base_convert(substr($package_service_byte, 0, 4), 2, 10);                        //количество входов в сеть
            $result['minNumberIdleSlots'] = base_convert(substr($package_service_byte, 4, 4), 2, 10);                   //минимальное количество простоев

            $package_service_byte = self::AddZero(base_convert(substr($package, 56, 2), 16, 2), 8);                                       // 31 байт

            $result['listenDuringTransmit'] = base_convert(substr($package_service_byte, 0, 1), 2, 10);                 //флаг разрешения прослушивания сети при передаче данных
            $result['netEntryReason'] = base_convert(substr($package_service_byte, 1, 3), 2, 10);                       //причина входа в сеть
            $result['grandparentBlocked'] = base_convert(substr($package_service_byte, 3, 1), 2, 10);                   //прародитель заблокирован
            $result['grantparentBlocked'] = base_convert(substr($package_service_byte, 3, 1), 2, 10);                   //прародитель заблокирован
            $result['parentTimeoutExpired'] = base_convert(substr($package_service_byte, 4, 1), 2, 10);                 //время ожидания родителя истекло
            $result['cycleDetection'] = base_convert(substr($package_service_byte, 5, 1), 2, 10);                       //флаг обнаружения зацикливания сигнала
            $result['noIdleSlots'] = base_convert(substr($package_service_byte, 6, 1), 2, 10);                          //флаг отсутствия простоев

            $CC1110_1 = hexdec(substr($package, 58, 2));
            $CC1110_2 = hexdec(substr($package, 60, 2));
            $CC1110_3 = hexdec(substr($package, 62, 2));
            $result['CC1110'] = $CC1110_1 . "." . $CC1110_2 . "." . $CC1110_3;                                          //версия СС1110
            $result['cc1110'] = $CC1110_1 . "." . $CC1110_2 . "." . $CC1110_3;                                          //версия СС1110

            $pic_1 = hexdec(substr($package, 64, 2));
            $pic_2 = hexdec(substr($package, 66, 2));
            $pic_3 = hexdec(substr($package, 68, 2));
            $result['PIC'] = $pic_1 . "." . $pic_2 . "." . $pic_3;                                                      //версия PIC
            $result['pic'] = $pic_1 . "." . $pic_2 . "." . $pic_3;                                                      //версия PIC

            $result['numberOfHeartbeats'] = hexdec(substr($package, 70, 6));                                            //количество heartbeat-сообщений
            $result = (object)$result;
            $result = new CommunicationNodeHeartbeat($result);
        } catch (Throwable $exception) {
            $errors[] = "TranslateHeartbeat. Исключение: ";
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
            $status = 0;
            LogAmicum::LogAmicumStrata("TranslateHeartbeat", $package, $warnings, $errors);
        }
        $warnings[] = "TranslateHeartbeat. Закончил выполнять метод";

        return array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings);
    }

    // getExternalId - метод возвращает внешний ключ в страте по сетевому адресу
    public static function getExternalId($networkId, $type = 2)
    {
        //fromDecimal
        $translator[0] = "";           //00 - Assets;           // вернет сам себя
        $translator[1] = "| 0x400000"; //01 - Sensors           // вернет внешний ключ для сенсоров
        $translator[2] = "| 0x800000"; //02 - Miners and Tags   // вернет внешний ключ для меток и шахтеров

        if (isset($translator[$type])) {
            eval("\$networkId = \$networkId " . $translator[$type] . ";");
        }

        return $networkId;
    }

    // TranslateHeartbeat - метод по расшифровке пакетов состояний узлов связи

    public static function getNetworkId($externalId, $type = 2)
    {

        //toDecimal

        $translator[0] = "";           //00 - Assets             // вернет сам себя
        $translator[1] = "& 0x3FFFFF"; //01 - Sensors            // вернет net_id для сенсоров
        $translator[2] = "& 0x7FFFFF"; //02 - Miners and Tags    // вернет net_id для меток и шахтеров

        if (isset($translator[$type])) {
            eval("\$externalId = \$externalId " . $translator[$type] . ";");
        }

        return $externalId;
    }

    // TranslateLocation - метод по расшифровке пакетов локаций

    /**
     * convertAmsSensorIdToId - Convert AMS Sensor Commtrac External Id to Human Readable format
     */
    public static function convertAmsSensorIdToId($id)
    {
        return $id & 4194303;
    }

    // AddZero - метод добавления нулей в начало строки

    /**
     * convertMinerExternalIdToId - Convert Miner Commtrac External Id to Human Readable format
     */
    public static function convertMinerExternalIdToId($id)
    {
        return $id & 8388607;
    }

    // TranslateEnvironmental - метод по расшифровке пакетов газов


    //TranslateCheckInCheckOut - метод по расшифровке пакетов регистрации / разрегистрации

    /**
     * Точка входа службы. Является главным методом, который принимает от службы
     * JSON строку (расшифрованный пакет) и перенаправляет её в метод,
     * соответствующий типу полученного пакета.
     *
     * Пакет координат
     *  с 3 услышанными узлами
     * 127.0.0.1/admin/strata-job/save-strata-package?ip=127.0.0.1&mineId=290&package={"timestamp":"2019-06-05 00:15:36.366640","className":"MinerNodeLocation","sequenceNumber":"6","batteryVoltage":"4,100000","networkId":"708457","alarmFlag":0,"emergencyFlag":0,"surfaceFlag":"Underground","movingFlag":"Moving","nodes":[{"address":"48229","rssi":"-76"},{"address":"17757","rssi":"-82"},{"address":"48133","rssi":"-83"}]}
     *  с 2 услышанными узлами
     * 127.0.0.1/admin/strata-job/save-strata-package?ip=127.0.0.1&mineId=290&package={"timestamp":"2019-07-18 00:15:36.366640","className":"MinerNodeLocation","sequenceNumber":"6","batteryVoltage":"4,100000","networkId":"708457","alarmFlag":0,"emergencyFlag":0,"surfaceFlag":"Underground","movingFlag":"Moving","nodes":[{"address":"18278","rssi":"-62"},{"address":"18789","rssi":"-80"}]}
     *  с 1 услышанным узлом
     * 127.0.0.1/admin/strata-job/save-strata-package?ip=127.0.0.1&mineId=290&package={"timestamp":"2019-07-18 00:15:36.366640","className":"MinerNodeLocation","sequenceNumber":"6","batteryVoltage":"4,100000","networkId":"687850","alarmFlag":0,"emergencyFlag":0,"surfaceFlag":"Underground","movingFlag":"Moving","nodes":[{"address":"19592","rssi":"-83"}]}
     *  без услышанных узлов
     * 127.0.0.1/admin/strata-job/save-strata-package?ip=172.16.51.45&mineId=290&package={"timestamp":"2019-07-18 00:16:36.366640","className":"MinerNodeLocation","sequenceNumber":"6","batteryVoltage":"4,100000","networkId":"687850","alarmFlag":0,"emergencyFlag":0,"surfaceFlag":"Underground","movingFlag":"Moving","nodes":[]}
     *
     *  с 1 услышанным узлом и генерацией события "Низкий заряд батареи светильника" или "Низкий заряд батареи метки позиционирования"
     * 127.0.0.1/admin/strata-job/save-strata-package?ip=127.0.0.1&mineId=290&package={"timestamp":"2019-10-15 11:18:36.366640","className":"MinerNodeLocation","sequenceNumber":"6","batteryVoltage":"3,100000","networkId":"708766","alarmFlag":0,"emergencyFlag":0,"surfaceFlag":"Underground","movingFlag":"Moving","nodes":[{"address":"19592","rssi":"-83"}]}
     *
     * Пакет Heartbeat
     * 127.0.0.1/admin/strata-job/save-strata-package?ip=172.16.51.35&mineId=290&package={"timestamp":"2019-07-22 09:00:00.983980","className":"CommunicationNodeHeartbeat","sequenceNumber":"14","batteryVoltage":"6,400000","sourceNode":"21806","routingRootNodeAddress":"48222","routingParentNode":{"address":"48222","rssi":"-29"},"neighborTableFull":"0","neighborCount":"7","routingRootHops":"1","timingRootNodeAddress":"48222","timingParentNode":{"address":"48222","rssi":"-29"},"timingRootHops":"1","lostRoutingParent":"0","lostTimingParent":"0","routingChangeParents":"0","timingChangeParents":"0","routingAboveThresh":"1","timingAboveThresh":"1","queueOverfow":"0","netEntryCount":"0","minNumberIdleSlots":"4","listenDuringTransmit":"1","netEntryReason":"0","grantparentBlocked":"0","parentTimeoutExpired":"0","cycleDetection":"0","noIdleSlots":"0","cc1110":"1.4.1","pic":"2.1.0","numberOfHeartbeats":"222"}
     *
     * Пакет газов
     * 127.0.0.1/admin/strata-job/save-strata-package?ip=127.0.0.1&mineId=290&package={"timestamp":"2019-12-24 10:12:46.244513","className":"EnvironmentalSensor","sequenceNumber":"9","sourceNode":"687950","parametersCount":"4","parameters":[{"id":"100","value":{"className":"TrolexInfo1","gasReading":"1","sensorModuleType":"20"}},{"id":"101","value":{"className":"TrolexInfo2","totalDigits":"0","decimalDigits":"0","trolexStatus":{"moduleAbsent":"module not fitted","warmup":"warmup in progress","sp1":"setpoint not tripped","sp2":"setpoint not tripped","stelAlarm":"STEL ok","twaAlarm":"TWA ok","fault":"no fault","pellistorOver":"Not in overrange"},"strataStatus":{"io1ConfigLeastSignificantBit":"0","io2ConfigLeastSignificantBit":"0","io1State":"off","io2State":"off","powerSource":"Battery","messageReason":"Interval message","io1ConfigMiddleBit":"0","io2ConfigMiddleBit":"0"}}},{"id":"102","value":{"className":"TrolexVoltage","strataStatus":{"softwareNodeTypeConfig":"IS","emergencyPowerMode":"not set","resetShutdownReason":"unknown","io1ConfigMostSignificantBit":"0","io2ConfigMostSignificantBit":"0"},"batteryVoltage":"3,700000","externalVoltage":"0,000000"}},{"id":"103","value":{"className":"TrolexSN","sn":"0"}}]}
     *
     * Пакет регистрации
     * 127.0.0.1/admin/strata-job/save-strata-package?ip=172.16.51.200&mineId=290&package={"timestamp":"2019-07-22 09:29:36.366640","className":"MinerRegistration","sequenceNumber":"6","batteryVoltage":"4,100000","sourceNode":"661025","messageId":"1","checkIn":"1"}
     */
    public function actionSaveStrataPackage()
    {
        $method_name = "actionSaveStrataPackage ";                                                                      // название метода
        $status = 1;                                                                                                    // флаг успешного выполнения метода
        $pack = null;                                                                                                   // пакет со страты
        $warnings = array();                                                                                            // массив предупреждений
        $errors = array();                                                                                              // массив ошибок
        $result = array();                                                                                              // результирующий массив
        $write_log_status = 1;                                                                                          // флаг записи логов
        $start = microtime(true);

        try {
            //$warnings[] = 'actionSaveStrataPackage. начал выполнять метод';

            $post = Assistant::GetServerMethod();

            $ip_addr = $post['ip'];
            $mine_id = $post['mineId'];
            $pack = json_decode($post['package'], false);
            $method_name = $method_name . $pack->className;
            ////$warnings[] = $pack;

            switch ($pack->className) {
                case 'MinerNodeLocation':
//                    $result = self::saveLocationPacket(new MinerNodeLocation($pack), $mine_id);
//                    $warnings[] = $result['warnings'];
//                    $errors[] = $result['errors'];
                    $status *= 1;
                    //LogCacheController::setStrataLogValue("MinerNodeLocation", $pack = json_decode($post['package'],1));
                    break;
                case 'MinerOriginatedText':
                    // TODO
                    break;
                case 'CommunicationNodeHeartbeat':
//                    $result = self::saveHeartbeatPacket(new CommunicationNodeHeartbeat($pack), $mine_id);
                    $status = 1;
                    break;
                case 'MinerTerminatedMessageAck':
//                    self::SaveMessageAck($pack->timestamp, $pack->messageId, $pack->networkId);
                    break;
                case 'MinerTerminatedMessageRead':
//                    self::SaveMessageRead($pack->timestamp, $pack->messageId, $pack->networkId);
                    break;
                case 'BroadcastResponse':
                    // TODO
                    break;
                case 'MinerRegistration':
                case 'MinerNodeCheckIn':
                case 'MinerNodeCheckOut':
//                    $result = self::saveRegistrationPacket(new MinerNodeCheckInOut($pack), $mine_id, $ip_addr);
//                    $warnings[] = $result['warnings'];
//                    $errors[] = $result['errors'];
                    $status = 1;
                    break;
                case 'EnvironmentalSensor':
                    $write_log_status = 0;                                                                              // для газов сюда не писать, он пишется отдельно
//                    $result = self::saveEnvironmentalPacket(new EnvironmentalSensor($pack), $mine_id);
//                    $warnings[] = $result['warnings'];
//                    $errors[] = $result['errors'];
                    $status = 1;
                    break;
            }
        } catch (Throwable $ex) {
            $errors[] = 'actionSaveStrataPackage. Исключение';
            $errors[] = $ex->getMessage();
            $errors[] = $ex->getLine();
            $status = 0;
        }
        if ($status == 0 and $write_log_status) {
            $data_to_log_cache = array('Items' => $result, 'status' => $status, 'errors' => $errors, 'warnings' => $warnings, 'pack' => $pack);
            LogCacheController::setStrataLogValue('actionSaveStrataPackage', $data_to_log_cache);
        }
        $result = 'method start ' . $method_name;
        $errors = array();
        $warnings = array();

        return $this->asJson(array('status' => $status, 'duration' => 0, 'Items' => $result, 'errors' => $errors, 'warnings' => $warnings));
    }

    //TranslateAscRead - метод по расшифровке пакетов получения / прочтения сообщения

    /**
     * Функция записи пакета в отладочную таблицу в БД
     * Методом POST принимает пакет в исходном виде от ССД Strata.
     */
    public function actionSavePackToBase()
    {
        try {
            $post = Yii::$app->request->post();
            $jsonPack = json_decode($post['jsonpack']);
            $date_time_string = $jsonPack->timestamp;
            $netId = 0;
            if (isset($jsonPack->sourceNode)) {
                $netId = $jsonPack->sourceNode;
            } else if (isset($jsonPack->nodeAddress)) {
                $netId = $jsonPack->nodeAddress;
            } else if (isset($jsonPack->minerNodeAddress)) {
                $netId = $jsonPack->minerNodeAddress;
            } else if (isset($jsonPack->sourceNodeAddress)) {
                $netId = $jsonPack->sourceNodeAddress;
            } else if (isset($jsonPack->networkId)) {
                $netId = $jsonPack->networkId;
            }

            $newInfo = new StrataPackageInfo();
            $newInfo->bytes = $post['pack'];
            $newInfo->date_time = $date_time_string;
            $newInfo->net_id = $netId;
            $newInfo->ip_gateway = $post['ip'];
            if (!$newInfo->save()) {
                echo "Fail\n";
            }
        } catch (Throwable $e) {
            $err = 'Line ' . $e->getLine() . ' ' . $e->getMessage();
            echo $err;
        }
    }

    // crc16 - расчет контролльной суммы пакета Strata по CRC16

    /**
     * Функция отображения истории сообщений выбранного воркера с диспетчером
     * http://127.0.0.1/admin/strata-job/read-text-messages?worker_id=1092350
     */
    public function actionReadTextMessages()
    {
        $result = array();
        $status = 1;
        $networkId = null;
        $errors = array();
        $warnings = array();
        $messages = array();
        $warnings [] = 'actionReadTextMessages. Начал выполнять метод';
        try {
            $post = Assistant::GetServerMethod();
            if (isset($post['worker_id']) && $post['worker_id'] != "") {
                $worker_id = $post['worker_id'];
                $warnings [] = 'actionReadTextMessages. Входные данные получены';
            } else {
                throw new \Exception('actionReadTextMessages. Ошибка получения входных параметров. Не передан worker_id сотрудника');
            }
            $messages = (new Query())//запросить из таблицы text_message отправителя, сообщение и дату
            ->select([
                '*'
            ])
                ->from('text_message')
                ->orWhere(['reciever_worker_id' => $worker_id, 'sender_network_id' => 'surface'])
                ->orWhere(['sender_worker_id' => $worker_id, 'reciever_network_id' => 'surface'])
                ->orderBy('datetime')
                ->all();
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'actionReadTextMessages. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }
        //$warnings[] = 'actionReadTextMessages. Закончил метод';
        $result = array('messages' => $messages,
            'Items' => $result,
            'status' => $status,
            'errors' => $errors,
            'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }


    /**
     * actionReadTextMessagesSensor - Функция отображения истории сообщений выбранного сенсора диспетчером
     * http://127.0.0.1/admin/strata-job/read-text-messages-sensor?sensor_id=27606
     */
    public function actionReadTextMessagesSensor()
    {
        $result = array();
        $status = 1;
        $networkId = null;
        $errors = array();
        $warnings = array();
        $messages = array();
        $warnings [] = 'actionReadTextMessagesSensor. Начал выполнять метод';
        try {
            $post = Assistant::GetServerMethod();
            if (isset($post['sensor_id']) && $post['sensor_id'] != "") {
                $sensor_id = $post['sensor_id'];
                $warnings [] = 'actionReadTextMessagesSensor. Входные данные получены';
            } else {
                throw new \Exception('actionReadTextMessagesSensor. Ошибка получения входных параметров. Не передан sensor_id сотрудника');
            }
            $messages = (new Query())//запросить из таблицы text_message отправителя, сообщение и дату
            ->select([
                '*'
            ])
                ->from('text_message')
                ->orWhere(['reciever_sensor_id' => $sensor_id, 'sender_network_id' => 'surface'])
                ->orWhere(['sender_sensor_id' => $sensor_id, 'reciever_network_id' => 'surface'])
                ->orderBy('datetime')
                ->all();
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = 'actionReadTextMessagesSensor. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }
        //$warnings[] = 'actionReadTextMessages. Закончил метод';
        $result = array('messages' => $messages,
            'Items' => $result,
            'status' => $status,
            'errors' => $errors,
            'warnings' => $warnings);
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->data = $result;
    }

    // getNetworkId - метод возвращает сетевой адрес по внешенему ключу страта

    /**
     * Отмена аварийного сигнала
     * @return Response
     */
    public function actionCancelAlarmSignal()
    {
        $status = 1;
        $warnings = array();
        $errors = array();

        try {
            //находим айдишники воркеров, у которых последнее значение параметра 2-357 (Флаг сигнал об аварии) не равно 0
            $worker_parameter_last_values = (new Query())
                ->select('worker_id')
                ->from('view_worker_parameter_value_only_main')
                ->where('parameter_id = 357 and parameter_type_id = 2')
                ->andWhere('value <> 0')
                ->all();
            $worker_parameter_last_values = ArrayHelper::getColumn($worker_parameter_last_values, 'worker_id');
            $cur_date = Assistant::GetDateNow();                                      //Записываем текущее время
            //перебираем всех воркеров, у кого есть сигнал об аварии
            if ($worker_parameter_last_values) {
                foreach ($worker_parameter_last_values as $worker_id) {
                    $response = self::saveWorkerParameterBatch($worker_id, 2, 357, 48, $cur_date, 1);
                    if ($response['status'] == 1) {
                        //$warnings[] = $response['warnings'];
                        if ($response['date_to_cache']) {
                            $response['date_to_cache']['worker_object_id'] = $worker_id;
                            unset($response['worker_id']);
                            $date_to_cache[] = $response['date_to_cache'];
                        }
                        if ($response['date_to_db']) {
                            $date_to_db[] = $response['date_to_db'];
                        }
                    } else {
                        //$warnings[] = $response['warnings'];
                        $errors[] = $response['errors'];
                        throw new \Exception(__FUNCTION__ . '. Ошибка сохранения параметра 357');
                    }
                }

                /**=================================================================
                 * блок массовой вставки значений в БД
                 * =================================================================*/
                if (isset($date_to_db)) {
//                    Yii::$app->db_amicum2->createCommand()->batchInsert('worker_parameter_value',
//                        ['worker_parameter_id', 'date_time', 'value', 'status_id', 'shift', 'date_work'],
//                        $date_to_db)->execute();
                    $insert_param_val = Yii::$app->db_amicum2->queryBuilder->batchInsert('worker_parameter_value', ['worker_parameter_id', 'date_time', 'value', 'status_id', 'shift', 'date_work'], $date_to_db);
                    Yii::$app->db_amicum2->createCommand($insert_param_val)->execute();
//                    Yii::$app->db_amicum2->createCommand($insert_param_val . " ON DUPLICATE KEY UPDATE `value` = VALUES (`value`), `status_id` = VALUES (`status_id`), `shift` = VALUES (`shift`), `date_work` = VALUES (`date_work`)")->execute();
                }

                /**
                 * блок массовой вставки значений в кеш
                 */
                if (isset($date_to_cache)) {
                    /******************* Вставка значений в кэш *******************/
                    $worker_cache_controller = new WorkerCacheController();
                    $insert_into_cache = $worker_cache_controller->multiSetWorkerParameterHash($date_to_cache);
                }

            }
        } catch (Throwable $exception) {
            $status = 0;
            $errors[] = __FUNCTION__ . '. Исключение';
            $errors[] = $exception->getMessage();
            $errors[] = $exception->getLine();
        }

        $result_main = array('Items' => 'actionCancelAlarmSignal', 'status' => $status,
            'errors' => $errors, 'warnings' => $warnings);
        return $this->asJson($result_main);
    }


    public function crc16strata($string)
    {
        $crc = 0;
        $len = strlen($string);
        for ($i = 0; $i < $len; $i++) {
            $crc = $crc ^ ord($string[$i]);
            for ($j = 0; $j < 8; $j++) {
                if (($crc & 0x0001) == 0x0001)
                    $crc = (($crc >> 1) ^ 0x8408);
                else
                    $crc = $crc >> 1;
            }
        }
        return $crc;
    }


}
