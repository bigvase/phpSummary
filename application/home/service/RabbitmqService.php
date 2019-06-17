<?php
/**
 * Created by PhpStorm.
 * User: ZouZhongHua
 * Date: 2018/9/19
 * Time: 10:38
 */

/***
 * 参考链接：https://blog.csdn.net/zhwxl_zyx/article/details/78892589
 */
//include_once ("MQService.class.php");

class RabbitmqService {
    private $configs        = [];
    private $exchange_name  = '';       // 交换机名称
    private $queue_name     = '';       // 队列名称
    private $route_key      = '';       // 路由名称
    private $durable        = true;     // 持久化，默认true
    private $autodelete     = false;    // 自动删除
    private $mirror         = true;    // 镜像队列，打开后消息会在节点之间复制，有master和slave的概念
    private $delay_exchange   = '';    // 死信交换机
    private $delay_routing_key      = '';    // 死信队列

    private $_conn          = null;
    private $_exchange      = null;
    private $_channel       = null;
    private $_queue         = null;

    private $logFile        = "Log/mq.log";

    /***
     * 构造方法 初始化配置信息
     *
     * RabbitmqService constructor.
     * @param $exchange_name
     * @param $queue_name
     * @param $route_key
     * @param string $delay_exchange
     * @param string $delay_routing_key
     * @throws Exception
     */
    public function __construct($exchange_name, $queue_name, $route_key, $delay_exchange = '', $delay_routing_key = '') {
        try {
            $this->setConfigs(MQParam::$config);
            $this->exchange_name    = $exchange_name;
            $this->queue_name       = $queue_name;
            $this->route_key        = $route_key;
            $this->delay_exchange    = $delay_exchange;
            $this->delay_routing_key = $delay_routing_key;
//            if (empty($this->exchange_name))    throw_exception("交换器名称不能为空");
//            if (empty($this->queue_name))       throw_exception("队列名称不能为空");
//            if (empty($this->route_key))        throw_exception("路由键不能为空");
        } catch (Exception $e) {
            throw_exception($e -> getMessage());
        }
    }

    /***
     * 设置配置信息
     *
     * @param $configs
     * @throws Exception
     */
    private function setConfigs($configs) {
        if (!is_array($configs)) throw_exception("configs is not array");
        if (!($configs['host'] && $configs['port'] && $configs['login'] && $configs['password'] && $configs['vhost'])) throw_exception("configs is empty");
        $this->configs = $configs;
    }

    /***
     * 设置是否持久化，默认为True
     *
     * @param $durable
     */
    public function setDurable($durable) {
        $this->durable = $durable;
    }

    /***
     * 设置是否自动删除
     *
     * @param $autodelete
     */
    public function setAutoDelete($autodelete) {
        $this->autodelete = $autodelete;
    }

    /***
     * 设置是否镜像，默认true
     *
     * @param $mirror
     */
    public function setMirror($mirror) {
        $this->mirror = $mirror;
    }

    /***
     * 打开AMQP连接
     *
     * @throws Exception
     */
    private function open() {
        if (!$this->_conn) {
            try {
                $this->_conn = new AMQPConnection($this->configs);
                $this->_conn->connect();
                $this->initConnection();
            } catch (AMQPConnectionException $ex) {
                throw_exception("cannot connection rabbitmq", 500);
            }
        }
    }

    /***
     * rabbitmq连接不变
     * 重置交换机，队列，路由等配置
     *
     * @param $exchange_name
     * @param $queue_name
     * @param $route_key
     * @throws Exception
     */
    public function reset($exchange_name, $queue_name, $route_key) {
        $this->exchange_name = $exchange_name;
        $this->queue_name    = $queue_name;
        $this->route_key     = $route_key;
        $this->initConnection();
    }

    /***
     * 初始化rabbit连接的相关配置
     *
     * @throws AMQPChannelException
     * @throws AMQPConnectionException
     * @throws AMQPExchangeException
     * @throws AMQPQueueException
     */
    private function initConnection() {
        if (empty($this->exchange_name) || empty($this->queue_name) || empty($this->route_key)) {
            throw_exception('rabbitmq exchange_name or queue_name or route_key is empty', 500);
        }
        $this->_channel  = new AMQPChannel($this->_conn);
        $this->_exchange = new AMQPExchange($this->_channel);
        $this->_exchange->setName($this->exchange_name);

        $this->_exchange->setType(AMQP_EX_TYPE_DIRECT);
        if ($this->durable)
            $this->_exchange->setFlags(AMQP_DURABLE);
        if ($this->autodelete)
            $this->_exchange->setFlags(AMQP_AUTODELETE);
        $this->_exchange->declare();

        $this->_queue = new AMQPQueue($this->_channel);
        $this->_queue->setName($this->queue_name);
        if ($this->durable)
            $this->_queue->setFlags(AMQP_DURABLE);
        if ($this->autodelete)
            $this->_queue->setFlags(AMQP_AUTODELETE);
        if ($this->mirror)
            $this->_queue->setArgument('x-ha-policy', 'all');
        if ($this->delay_exchange)
            $this->_queue->setArgument('x-dead-letter-exchange', $this->delay_exchange);
        if ($this->delay_routing_key)
            $this->_queue->setArgument('x-dead-letter-routing-key', $this->delay_routing_key);
        $this->_queue->declare();

        $this->_queue->bind($this->exchange_name, $this->route_key);
    }

    /***
     * 关闭连接
     */
    public function close() {
        if ($this->_conn) {
            $this->_conn->disconnect();
        }
    }

    public function __sleep() {
        $this->close();
        return array_keys(get_object_vars($this));
    }

    public function __destruct() {
        $this->close();
    }

    /***
     * 生产者发送消息
     *
     * @param $msg
     * @param int $expiration 消息过期时间
     * @return mixed
     * @throws Exception
     */
    public function send($msg, $expiration = 0) {
        $this->open();
        if(is_array($msg)){
            $msg = json_encode($msg);
        }else{
            $msg = trim(strval($msg));
        }

        $attributes['delivery_mode'] = 2;   // 开启消息持久化
        if ($expiration > 0) {
            $attributes['expiration'] = "".intval($expiration);    // 添加消息过期时间，转成字符串
        }
        return $this->_exchange->publish($msg, $this->route_key, null, $attributes);
    }

    /***
     *
     * 消费者
     *
     * function processMessage($envelope, $queue) {
     *      $msg = $envelope->getBody();
     *      echo $msg."\n"; //处理消息
     *      $queue->ack($envelope->getDeliveryTag());//手动应答
     * }
     *
     * @param bool $autoack
     * @return bool
     * @throws Exception
     */
    public function run($autoack = false) {
        $this->open();
        if (!$this->_queue) return false;
        while (true) {
            if ($autoack) $this->_queue->consume((function ($envelope, $queue) {
                output($this -> logFile, date('Y-m-d H:i:s') . '消息处理中.....', $queue);
                $msg = $envelope->getBody();
                output($this -> logFile, date('Y-m-d H:i:s') . '获得消息内容：'. $msg);
                try {
                    $response = service("Admin/MQ") -> mqConsumerLogic($msg);
                    output($this -> logFile, date('Y-m-d H:i:s') . '消息处理结果：'.($response ? 'true' : 'false'));
                    if ($response == true) {
                        $queue->ack($envelope->getDeliveryTag());
                    } else {
                        $queue->nack($envelope->getDeliveryTag());
                    }
                } catch (Exception $e) {
                    $queue->nack($envelope->getDeliveryTag());
                }
                // 如果监测到退出标记，则关闭当前消费者
                $queueName = $queue->getName();
                $redis     = RedisCache::G();
                $redisKey  = MQParam::$redisConsumerPreKey . strtoupper($queueName);
                if ($redis->exists($redisKey)) {
                    $redis->Del($redisKey);
                    exit();
                }
            }), AMQP_AUTOACK);
            else $this->_queue->consume((function ($envelope, $queue) {
                define('IS_IN_QUEUE',1);
                output($this -> logFile, date('Y-m-d H:i:s') . '消息处理中.....');
                $msg = $envelope->getBody();
                output($this -> logFile, date('Y-m-d H:i:s') . '获得消息内容：'. $msg);
                try {
                    $response = service("Admin/MQ") -> mqConsumerLogic($msg);
                    output($this -> logFile, date('Y-m-d H:i:s') . '消息处理结果：'.($response ? 'true' : 'false'));
                    if ($response == true) {
                        $queue->ack($envelope->getDeliveryTag());
                    } else {
                        $queue->nack($envelope->getDeliveryTag());
                    }
                } catch (Exception $e) {
                    $queue->nack($envelope->getDeliveryTag());
                }

                // 如果监测到退出标记，则关闭当前消费者
                $queueName = $queue->getName();
                $redis     = RedisCache::G();
                $redisKey  = MQParam::$redisConsumerPreKey . strtoupper($queueName);
                if ($redis->exists($redisKey)) {
                    $redis->Del($redisKey);
                    exit();
                }
            }));
            clearstatcache();
            gc_collect_cycles();
        }
    }

    /***
     * 初始化消费者
     */
    public function initConsumer()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        // 延时消息中转队列，不创建消费者，START
        foreach (MQParam::$queue as $k => $v) {
            if (strpos($k, MQParam::$delayLetterType) !== false) {
                unset(MQParam::$queue[$k]);
            }
        }
        // 延时消息中转队列，不创建消费者，END

        $routeKey      = MQParam::$mqRouteKey;
        $queueListTemp = $queueName = MQParam::$queue;
        $exchange      = MQParam::$exchange;
        $httpInterface = MQParam::$mqHttpApi;
        $username      = MQParam::$config['login'];
        $password      = MQParam::$config['password'];

        printAndLog("请求接口：" . $httpInterface['consumers']);
        $consumerList = json_decode(https_request($httpInterface['consumers'], null, $username, $password), true);
        foreach ($consumerList as $one) {
            while (isset($queueListTemp[$one['queue']['name']]) && !empty($one['channel_details'])) {
                unset($queueListTemp[$one['queue']['name']]);
            }
        }
        if (is_test_model()) {
            printAndLog("已有的队列：".json_encode($queueName));
            printAndLog("已经存在的消费者：".json_encode($consumerList));
        }
        $redis    = RedisCache::G();
        $redisKey = "INIT_CONSUMER_REDIS_KEY";
        $redisTTL = 600; // 这里貌似不设置也没关系，会被重写
        $redis->Set($redisKey, $queueListTemp, $redisTTL);
        printAndLog("剩余需要创建的队列消费者：".json_encode($queueListTemp));

        // 如果无需创建消费者，直接返回
        if (empty($queueListTemp)) {
            printAndLog("本次无需创建消费者，任务结束。");
            return true;
        }

        foreach ($routeKey as $rk => $rv) {
            foreach ($queueName as $qk => $qv) {
//                $exchangeName = $exchange[$rv];   // 交换机名
//                $queueNameTemp = $qk;             // 队列名
//                $k_route = $qv[1];                // 路由键
                if ($rv == $qv[0]) {
                    $redisLock = $redis -> Get($redisKey . strtoupper($qk));
                    if ($redisLock) {
                        printAndLog("RabbitMQ消费者创建锁定中，跳过处理：{$exchange[$rv]} -- {$qk} -- {$qv[1]}");
                        continue;
                    }
                    $fatherPid = getmypid();
                    printAndLog("父进程PID：" . $fatherPid);
                    $temp = $redis->Get($redisKey);
                    printAndLog("TEMP:".json_encode($temp));
                    if (isset($temp[$qk])) {
                        unset($temp[$qk]);
                        $redis->Set($redisKey, $temp, $redisTTL);
                        $redis->Set($redisKey . strtoupper($qk), 1, 30);
                        printAndLog("初始化RabbitMQ消费者：{$exchange[$rv]} -- {$qk} -- {$qv[1]}");
                        $object = new RabbitmqService($exchange[$rv], $qk, $qv[1]);
                        $object->run();
                        exit();
                    } else {
                        printAndLog("RabbitMQ消费者已存在，不再初始化：{$exchange[$rv]} -- {$qk} -- {$qv[1]}");
                    }
                }
            }
        }
    }

    /***
     * 销毁所有消费者 【已停用】
     */
    public function destroyConsumer() {
        exit();
        $httpInterface = MQParam::$mqHttpApi;
        $username      = MQParam::$config['login'];
        $password      = MQParam::$config['password'];

        $list          = json_decode(https_request($httpInterface['connections'], null, $username, $password), true);
        $header        = array('X-Reason' => 'DELETE');

        if (empty($list)) {
            printAndLog("无消费者", $this->logFile);
            if ($_GET['auth'] == 'show') {
                echo '无消费者<br>';
            }
        } else {
            $len = 1;
            echo "<br>";
            echo "初始化销毁消费者逻辑...", '<br>';
            echo "本次共需销毁消费者[", count($list), "]个", "<br>";
            foreach ($list as $k => $v) {
                $url = $httpInterface['connections'] . '/' . str_replace('+', '%20', urlencode($v['name']));
                echo date("Y-m-d H:i:s"), "准备销毁第", $len++, "个消费者：", $v['name'], '<br>';
                $res = http_request(strtolower($url), 'DELETE', null, $header, $username, $password);
                echo date("Y-m-d H:i:s"), "销毁消费者：", $v['name'], "完毕<br>";
                $message = date("Y-m-d H:i:s") . " 销毁消费者：{$v['name']}";
                printAndLog($message, $this->logFile);
            }
            echo "销毁消费者逻辑结束...";
        }
    }


    /***
     * RabbitMQ 消费者心跳监测，保持RabbitMQ消费者处于挂起状态，防止被haproxy关闭
     */
    public function rabbitmqConsumerMonitor() {

        $queueList = MQParam::$queue;

        // 延时消息中转队列，不创建消费者，START
        foreach (MQParam::$queue as $k => $v) {
            if (strpos($k, MQParam::$delayLetterType) !== false) {
                unset(MQParam::$queue[$k]);
            }
        }
        // 延时消息中转队列，不创建消费者，END

        foreach ($queueList as $one) {
            $mqData             = [];
            $mqData['content']  = ['routeKey' => $one[1]];
            $mqData['add_time'] = time();
            $mqData['ip']       = get_client_ip();
            $mqData['type']     = strtolower("consumer_monitor");
            $mqJson             = json_encode($mqData);

            $res = service('Admin/MQ') -> pushMessageToMQ($mqJson, '', MQParam::DEFAULT_MESSAGE_QUEUE, $one[1]);
        }
    }

    /***
     * RabbitMQ 队列状态监测，防止队列出现意外积压
     *
     * @return bool
     */
    public function rabbitmqQueueMonitor() {

        $httpInterface = MQParam::$mqHttpApi;
        $username      = MQParam::$config['login'];
        $password      = MQParam::$config['password'];

        printAndLog("请求接口：" . $httpInterface['queues']);
        $consumerList = json_decode(https_request($httpInterface['queues'], null, $username, $password), true);

        $exception                   = [];
        $exception['send_mode']      = ['wechat'];
        $exception['exception_area'] = 'mqException';
        $exception['description']    = '消息队列消费者状态异常';
        $exception['description_list']=[];
        foreach ($consumerList as $one) {
            if ($one['messages'] > MQParam::$alarmThreshold) {
                // 队列中消息积压总数目超过报警阈值
                $exception['description_list'][] = "消息队列：队列[{$one['name']}]中消息积压总数目超过报警阈值，当前：{$one['messages']}，阈值：" . MQParam::$alarmThreshold;
//                D("Admin/Exception")->addLog($exception, true);
            }
            if ($one['messages_ready'] > MQParam::$alarmThreshold) {
                // 队列中Ready消息积压数目超过报警阈值
                $exception['description_list'][] = "消息队列：队列[{$one['name']}]中Ready消息积压数目超过报警阈值，当前：{$one['messages_ready']}，阈值：" . MQParam::$alarmThreshold;
//                D("Admin/Exception")->addLog($exception, true);
            }
            if ($one['messages_unacknowledged'] > intval(MQParam::$alarmThreshold / 10)) {
                // 队列Unacked消息积压数目超过报警阈值
                $exception['description_list'][] = "消息队列：队列[{$one['name']}]中Unacked消息积压数目超过报警阈值，当前：{$one['messages_ready']}，阈值：" . intval(MQParam::$alarmThreshold / 10);
//                D("Admin/Exception")->addLog($exception, true);
            }
            if (($one['consumers'] == 0) && (strpos($one['name'], MQParam::$delayLetterType) === false)) {
                // 队列当前无消费者
                $exception['description_list'][] = "消息队列：队列[{$one['name']}]当前无消费者";
//                D("Admin/Exception")->addLog($exception, true);
            }
            if (count($one['synchronised_slave_nodes']) != 2) {
                // 队列同步出现问题，存在队列未完全同步数据
                $exception['description_list'][] = "消息队列：队列[{$one['name']}]同步出现问题，已同步镜像队列数：" . count($one['synchronised_slave_nodes']);
//                D("Admin/Exception")->addLog($exception, true);
            }
            if (count($one['slave_nodes']) != 2) {
                // 队列从节点出现问题
                $exception['description_list'][] = "消息队列：队列[{$one['name']}]从节点出现问题，当前从节点数据：" . count($one['slave_nodes']);
//                D("Admin/Exception")->addLog($exception, true);
            }
            if (!in_array($one['state'], ['running', 'idle'])) {
                // 队列状态出现问题
                $exception['description_list'][] = "消息队列：队列[{$one['name']}]状态异常，当前状态：" . $one['state'];
//                D("Admin/Exception")->addLog($exception, true);
            }
        }

        $redis               = RedisCache::G();
        $noWechatRedisNotice = $redis->Get('NO_WECHAT_TEMPLATE_NOTICE');

        if (!empty($exception['description_list']) && ($noWechatRedisNotice != 1)) {
            D("Admin/Exception")->addLog($exception, true);
        }

        return true;
    }
}