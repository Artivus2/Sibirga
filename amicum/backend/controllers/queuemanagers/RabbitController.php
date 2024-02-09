<?php
/*
 * Copyright (c) 2023. Все права принадлежат ООО "Профсоюз" Распространение и использование без разрешения ООО "Профсоюз" запрещено
 *
 */

namespace backend\controllers\queuemanagers;


use backend\controllers\serviceamicum\SyncFromRabbitMQController;
use Enqueue\AmqpExt\AmqpConnectionFactory as AmqpExtConnectionFactory;
use Enqueue\AmqpLib\AmqpConnectionFactory as AmqpLibConnectionFactory;
use Enqueue\AmqpTools\DelayStrategyAware;
use Enqueue\AmqpTools\RabbitMqDlxDelayStrategy;
use Interop\Amqp\AmqpConnectionFactory;
use Interop\Amqp\AmqpConsumer;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpMessage;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind;
use yii\base\Application as BaseApp;
use yii\base\Event;
use yii\base\NotSupportedException;
use yii\queue\amqp_interop\Command;
use yii\queue\cli\Queue as CliQueue;

class RabbitController extends CliQueue
{
    const ATTEMPT = 'yii-attempt';
    const TTR = 'yii-ttr';
    const DELAY = 'yii-delay';
    const PRIORITY = 'yii-priority';
    const ENQUEUE_AMQP_LIB = 'enqueue/amqp-lib';
    const ENQUEUE_AMQP_EXT = 'enqueue/amqp-ext';
    const ENQUEUE_AMQP_BUNNY = 'enqueue/amqp-bunny';


    /**
     * The connection to the borker could be configured as an array of options
     * or as a DSN string like amqp:, amqps:, amqps://user:pass@localhost:1000/vhost.
     *
     * @var string
     */
    public $dsn;
    /**
     * The message queue broker's host.
     *
     * @var string|null
     */
    public $host;
    /**
     * The message queue broker's port.
     *
     * @var string|null
     */
    public $port;
    /**
     * This is RabbitMQ user which is used to login on the broker.
     *
     * @var string|null
     */
    public $user;
    /**
     * This is RabbitMQ password which is used to login on the broker.
     *
     * @var string|null
     */
    public $password;
    /**
     * Virtual hosts provide logical grouping and separation of resources.
     *
     * @var string|null
     */
    public $vhost;
    /**
     * The time PHP socket waits for an information while reading. In seconds.
     *
     * @var float|null
     */
    public $readTimeout;
    /**
     * The time PHP socket waits for an information while witting. In seconds.
     *
     * @var float|null
     */
    public $writeTimeout;
    /**
     * The time RabbitMQ keeps the connection on idle. In seconds.
     *
     * @var float|null
     */
    public $connectionTimeout;
    /**
     * The periods of time PHP pings the broker in order to prolong the connection timeout. In seconds.
     *
     * @var float|null
     */
    public $heartbeat;
    /**
     * PHP uses one shared connection if set true.
     *
     * @var bool|null
     */
    public $persisted;
    /**
     * The connection will be established as later as possible if set true.
     *
     * @var bool|null
     */
    public $lazy;
    /**
     * If false prefetch_count option applied separately to each new consumer on the channel
     * If true prefetch_count option shared across all consumers on the channel.
     *
     * @var bool|null
     */
    public $qosGlobal;
    /**
     * Defines number of message pre-fetched in advance on a channel basis.
     *
     * @var int|null
     */
    public $qosPrefetchSize;
    /**
     * Defines number of message pre-fetched in advance per consumer.
     *
     * @var int|null
     */
    public $qosPrefetchCount;
    /**
     * Defines whether secure connection should be used or not.
     *
     * @var bool|null
     */
    public $sslOn;
    /**
     * Require verification of SSL certificate used.
     *
     * @var bool|null
     */
    public $sslVerify;
    /**
     * Location of Certificate Authority file on local filesystem which should be used with the verify_peer context option to authenticate the identity of the remote peer.
     *
     * @var string|null
     */
    public $sslCacert;
    /**
     * Path to local certificate file on filesystem.
     *
     * @var string|null
     */
    public $sslCert;
    /**
     * Path to local private key file on filesystem in case of separate files for certificate (local_cert) and private key.
     *
     * @var string|null
     */
    public $sslKey;
    /**
     * The queue used to consume messages from.
     *
     * @var string
     */
    public $queueName = 'interop_queue';
    /**
     * The reply queue used to consume messages from.
     *
     * @var string
     */
    public $replyQueueName = 'interop_queue';
    /**
     * The exchange used to publish messages to.
     *
     * @var string
     */
    public $exchangeName = 'rpc.it.sdesk';
    /**
     *
     *
     * @var int
     */
    public $durable_queue = AmqpQueue::FLAG_DURABLE;
    /**
     *
     *
     * @var int
     */
    public $durable_topic = AmqpTopic::FLAG_DURABLE;
    /**
     *
     *
     * @var int
     */
    public $auto_delete = AmqpQueue::FLAG_AUTODELETE;
    /**
     *
     *
     * @var int
     */
    public $passive = AmqpQueue::FLAG_PASSIVE;
    /**
     * Defines the amqp interop transport being internally used. Currently supports lib, ext and bunny values.
     *
     * @var string
     */
    public $driver = self::ENQUEUE_AMQP_LIB;
    /**
     * This property should be an integer indicating the maximum priority the queue should support. Default is 10.
     *
     * @var int
     */
    public $maxPriority = 10;
    /**
     * The property contains a command class which used in cli.
     *
     * @var string command class name
     */
    public $commandClass = Command::class;

    /**
     * Amqp interop context.
     *
     * @var AmqpContext
     */
    protected $context;
    /**
     * List of supported amqp interop drivers.
     *
     * @var string[]
     */
    protected $supportedDrivers = [self::ENQUEUE_AMQP_LIB, self::ENQUEUE_AMQP_EXT, self::ENQUEUE_AMQP_BUNNY];
    /**
     * The property tells whether the setupBroker method was called or not.
     * Having it we can do broker setup only once per process.
     *
     * @var bool
     */
    protected $setupBrokerDone = false;

    /**
     * Инициализация класса очереди
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        Event::on(BaseApp::class, BaseApp::EVENT_AFTER_REQUEST, function () {
            $this->close();
        });

        if (extension_loaded('pcntl') && PHP_MAJOR_VERSION >= 7) {
            // https://github.com/php-amqplib/php-amqplib#unix-signals
            $signals = [SIGTERM, SIGQUIT, SIGINT, SIGHUP];

            foreach ($signals as $signal) {
                $oldHandler = null;
                // This got added in php 7.1 and might not exist on all supported versions
                if (function_exists('pcntl_signal_get_handler')) {
                    $oldHandler = pcntl_signal_get_handler($signal);
                }

                pcntl_signal($signal, static function ($signal) use ($oldHandler) {
                    if ($oldHandler && is_callable($oldHandler)) {
                        $oldHandler($signal);
                    }

                    pcntl_signal($signal, SIG_DFL);
                    posix_kill(posix_getpid(), $signal);
                });
            }
        }
    }

    /**
     * Получение текущего экземпляра очереди
     * @return AmqpContext
     */
    public function getContext()
    {
        $this->open();

        return $this->context;
    }

    /**
     * Прослушивание очереди сообщений
     */
    public function listen()
    {
//        ini_set('max_execution_time', -1);
//        ini_set('mysqlnd.connect_timeout', 1440000);
//        ini_set('default_socket_timeout', 1440000);
//        ini_set('mysqlnd.net_read_timeout', 1440000);
//        ini_set('mysqlnd.net_write_timeout', 1440000);
//        ini_set('memory_limit', "10500M");

        $this->open();
        $this->setupBroker();

        $queue = $this->context->createQueue($this->queueName);
        $consumer = $this->context->createConsumer($queue);
        $callback = function (AmqpMessage $message, AmqpConsumer $consumer) {
//            ini_set('max_execution_time', -1);
//            ini_set('mysqlnd.connect_timeout', 1440000);
//            ini_set('default_socket_timeout', 1440000);
//            ini_set('mysqlnd.net_read_timeout', 1440000);
//            ini_set('mysqlnd.net_write_timeout', 1440000);
//            ini_set('memory_limit', "10500M");
            if ($message->isRedelivered()) {
                $consumer->acknowledge($message);
                $this->redeliver($message);
                return true;
            }

            if (SyncFromRabbitMQController::saveMessageRabbitMQ($this->queueName, $message->getBody())['status']) {
                $consumer->acknowledge($message);
            } else {
                $consumer->acknowledge($message);
                $this->redeliver($message);
            }

            return true;
        };
        $subscriptionConsumer = $this->context->createSubscriptionConsumer();
        $subscriptionConsumer->subscribe($consumer, $callback);
        $subscriptionConsumer->consume();
    }

    /**
     * Открытие соединения и подключение к серверу очереди сообщений
     */
    protected function open()
    {
        if ($this->context) {
            return;
        }

        switch ($this->driver) {
            case self::ENQUEUE_AMQP_LIB:
                $connectionClass = AmqpLibConnectionFactory::class;
                break;
            case self::ENQUEUE_AMQP_EXT:
                $connectionClass = AmqpExtConnectionFactory::class;
                break;
            case self::ENQUEUE_AMQP_BUNNY:
                $connectionClass = AmqpBunnyConnectionFactory::class;
                break;
            default:
                throw new \LogicException(sprintf('The given driver "%s" is not supported. Drivers supported are "%s"', $this->driver, implode('", "', $this->supportedDrivers)));
        }

        $config = [
            'dsn' => $this->dsn,
            'host' => $this->host,
            'port' => $this->port,
            'user' => $this->user,
            'pass' => $this->password,
            'vhost' => $this->vhost,
            'read_timeout' => $this->readTimeout,
            'write_timeout' => $this->writeTimeout,
            'connection_timeout' => $this->connectionTimeout,
            'heartbeat' => $this->heartbeat,
            'persisted' => $this->persisted,
            'lazy' => $this->lazy,
            'qos_global' => $this->qosGlobal,
            'qos_prefetch_size' => $this->qosPrefetchSize,
            'qos_prefetch_count' => $this->qosPrefetchCount,
            'ssl_on' => $this->sslOn,
            'ssl_verify' => $this->sslVerify,
            'ssl_cacert' => $this->sslCacert,
            'ssl_cert' => $this->sslCert,
            'ssl_key' => $this->sslKey,
        ];

        $config = array_filter($config, function ($value) {
            return null !== $value;
        });

        /** @var AmqpConnectionFactory $factory */
        $factory = new $connectionClass($config);

        $this->context = $factory->createContext();

        if ($this->context instanceof DelayStrategyAware) {
            $this->context->setDelayStrategy(new RabbitMqDlxDelayStrategy());
        }
    }

    /**
     * Настройка брокера очереди
     */
    protected function setupBroker()
    {
        if ($this->setupBrokerDone) {
            return;
        }

        $queue = $this->context->createQueue($this->queueName);
        $queue->addFlag($this->durable_queue);
        $queue->addFlag($this->auto_delete);
        $queue->addFlag($this->passive);
//        $queue->setArguments(['x-max-priority' => $this->maxPriority]);
        $this->context->declareQueue($queue);

        if ($this->exchangeName and $this->exchangeName != "") {
            $topic = $this->context->createTopic($this->exchangeName);
            $topic->setType(AmqpTopic::TYPE_DIRECT);
            $topic->addFlag($this->durable_topic);
            $this->context->declareTopic($topic);

            $this->context->bind(new AmqpBind($queue, $topic));
        }
        $this->setupBrokerDone = true;
    }

    /**
     * Закрыть соединение
     * Closes connection and channel.
     */
    protected function close()
    {
        if (!$this->context) {
            return;
        }

        $this->context->close();
        $this->context = null;
        $this->setupBrokerDone = false;
    }

    /**
     * Повторить оправку сообщения
     * {@inheritdoc}
     */
    protected function redeliver(AmqpMessage $message)
    {
        $attempt = $message->getProperty(self::ATTEMPT, 1);

        $newMessage = $this->context->createMessage($message->getBody(), $message->getProperties(), $message->getHeaders());
        $newMessage->setDeliveryMode($message->getDeliveryMode());
        $newMessage->setProperty(self::ATTEMPT, ++$attempt);

        $this->context->createProducer()->send(
            $this->context->createQueue($this->queueName),
            $newMessage
        );
    }

    /**
     * Положить сообщение в очередь
     * @inheritdoc
     */
    public function pushMessage($payload, $ttr, $delay, $priority)
    {
        $this->open();
        $this->setupBroker();

        $topic = $this->context->createTopic($this->exchangeName);

        $message = $this->context->createMessage($payload['payload']);
        $message->setDeliveryMode(AmqpMessage::DELIVERY_MODE_PERSISTENT);
        $message->setMessageId(uniqid('', true));
        $message->setTimestamp(time());
        $message->setCorrelationId($payload['correlation_id']);
        $message->setReplyTo($this->replyQueueName);
        $message->setContentType($payload['method']);
        $message->setProperty('type', $payload['method']);
        $message->setHeader('type', $payload['method']);

        $producer = $this->context->createProducer();

        if ($delay) {
            $producer->setDeliveryDelay($delay * 1000);
        }

        if ($priority) {
            $producer->setPriority($priority);
        }

        $producer->send($topic, $message);

        return $message->getMessageId();
    }

    /**
     * проверить статус очереди
     * @inheritdoc
     */
    public function status($id)
    {
        throw new NotSupportedException('Status is not supported in the driver.');
    }
}