<?php
/**
 * Created by PhpStorm.
 * User: ZouZhongHua
 * Date: 2018/9/17
 * Time: 11:16
 */

/***
 * 本类用于处理消息队列相关的逻辑
 *  ● 为防止可能的意外造成数据丢失，所有写入队列的数据要先进行数据库写入，并标记相关状态
 *  ● 处理数据会等待自定义方法返回对应的成功参数，返回成功后才会修改成成功状态
 *  ● 业务类型参数【type】必须以下划线的命名方式命名，系统会自动转小写处理。
 *  ● 不同业务逻辑需要定义不同的业务方法，并且以 convert_underline('mqConsumerFor_' . strtolower($type)) 命名，即驼峰法命名
 *  ● 各业务方法【必须返回是否成功】，true会修改status状态，否则不会修改
 *  ●
 * Class MQService
 */

include_once ("RabbitmqService.php");

class MQService  {

    private $logFile        = "Log/mq_log.log";
    private $logFilePre     = '/xjlc/php/www.xiaojilicai.com/';
    private $graylogName    = 'MQService';

    /***
     * 生产者
     *
     * @param $param
     * @param $type
     * @param int $sendTime 发送时间：时间戳  #如果时间不大于当前时间，则立即发送
     * @return bool
     * @throws Exception
     */
    public function mqProducer($param, $type, $sendTime = 0) {

        $graylogHash = get_random_str(40); // 生成一个Graylog标识字符串

        try {
            if (!is_array($param))  throw_exception('参数类型错误，必须是数组');
            if (empty($type))       throw_exception('业务逻辑处理方式不能为空');
            if (empty(MQParam::$businessLogic[$type])) {
                throw_exception("MQParam::\$businessLogic 未配置业务参数{$type}");
            }
            if (empty(MQParam::$mqRouteKey[$type])) {
                throw_exception("请在[MQParam::\$mqRouteKey]中配置自定义Key[即{$type}]，如需完全独立，请配置[MQParam::\$exchange]，并将[MQParam::\$mqRouteKey]的value作为[MQParam::\$exchange]的Key以绑定关系，如无必要，则只需在[MQParam::\$mqRouteKey]中配置[ '{$type}' => 'default', ]即可");
            }

            // 规范数据统一
            $nowTime = time();
            $ip      = get_client_ip();

            // 本地建一张表，专门用于存储信息
            $modelMQLog      = M('mq_log');
            $saveLog         = [
                'content'   => json_encode($param),
                'type'      => strtolower($type),
                'status'   => 1,
                'add_time' => $nowTime,
                'add_ip'    => $ip,
                'random_str'=> get_random_str(40)
            ];
            $saveLog['hash'] = sha1($saveLog['content'] . $saveLog['random_str']);
            $saveRes = $modelMQLog -> add($saveLog);
            if (!$saveRes) {
                // 因为有可能发生意外，所以不能依据mq_log表中的status来处理数据，status只能作为辅助字段查询记录状态
                $outputMessage = date('Y-m-d H:i:s') . ' 出现异常，添加数据失败，SQL：'. M()->getLastSql();
                output($this -> logFile, $outputMessage . "，Graylog查询码[graylogHash]：{$graylogHash}");

                $graylogConfig                = $saveLog;
                $graylogConfig['sql']         = M()->getLastSql();
                $graylogConfig['graylogHash'] = $graylogHash;       // 标识，可表示关联output打印
                $graylogConfig['graylogName'] = $this->graylogName;
                $graylogMessage               = "mqProducer添加数据失败";
                glog($graylogMessage, $graylogConfig);

                $exception['exception_area'] = 'mqException';   // 消息队列处理相关异常专用Key
                $exception['description']    = "mqProducer添加数据失败[{$graylogHash}]";
                D("Admin/Exception")->addLog($exception);
            }

            $method = convert_underline('mqConsumerFor_' . strtolower($type));
            if (!method_exists($this, $method) && (strtolower($type) != MQParam::$delayLetterType)) {
                /***
                 * 此处报警，但是不能停止代码，不可以抛异常，不然会造成数据丢失
                 */
                $outputMessage = "{$method}方法不存在";
                output($this -> logFile, $outputMessage . "，Graylog查询码[graylogHash]：{$graylogHash}");

                $graylogConfig                = $saveLog;
                $graylogConfig['sql']         = M()->getLastSql();
                $graylogConfig['graylogHash'] = $graylogHash;       // 标识，可表示关联output打印
                $graylogConfig['graylogName'] = $this->graylogName;
                $graylogMessage               = $outputMessage;
                glog($graylogMessage, $graylogConfig);

                $exception['exception_area'] = 'mqException';   // 消息队列处理相关异常专用Key
                $exception['description']    = "{$outputMessage}[{$graylogHash}]";
                D("Admin/Exception")->addLog($exception);
            }

            // TODO 发送到队列，确定发送成功，返回true
            // 送入队列需要基本的数据规范
            $mqData               = [];
            $mqData['content']    = $param;                   // 参数内容
            $mqData['add_time']   = $nowTime;                 // 申请时间
            $mqData['ip']         = $ip;                      // 申请IP
            $mqData['type']       = strtolower($type);        // 处理类型
            $mqData['hash']       = $saveLog['hash'];         // sha1 hash
            $mqData['logId']      = $saveRes;                 // logId
            $mqData['admin_name'] = session('adminname');    // adminname
            $mqData['admin_id']   = session('admin');    // admin id

            $mqJson             = json_encode($mqData);

            // TODO mqJson 是需要送入队列的数据，送入完毕后，判断是否成功，根据成功状态返回true|false
            try {
                if (is_numeric($sendTime) && (($sendTime - time()) > 0)) {
                    $type     = MQParam::$delayLetterType;
                    // 防止延时队列堵塞 2018年12月12日 23:04:45
//                    $postData = '{"vhost":"xiaojilicai","name":"queue_xjlc_delay_letter_1","truncate":"50000","ackmode":"ack_requeue_true","encoding":"auto","count":"100"}';
//                    https_request(MQParam::$mqHttpApi['delay_letter'], $postData, MQParam::$config['login'], MQParam::$config['password']);
                }
                $res = $this -> pushMessageToMQ($mqJson, $type, MQParam::DEFAULT_MESSAGE_QUEUE, '', $sendTime - time());
                return $res == true ? true : false;
            } catch (Exception $ee ) {
                return false;
            }

            return true;

        } catch (Exception $e) {
            throw_exception($e -> getMessage());
        }
    }

    /***
     * 根据KeyId获取随机路由Key
     *
     * KeyId 可认为是自定义路由值，为 MQParam::$mqRouteKey 的Key
     *
     * @param $keyId
     * @return bool|mixed
     */
    private function getRandRouteKey($keyId) {
        $mqRouteKey = MQParam::$mqRouteKey;
        $queueName  = MQParam::$queue;

        if (!$mqRouteKey[$keyId]) return false;

        $list = [];
        foreach ($queueName as $qk => $qv) {
            if ($qv[0] == $mqRouteKey[$keyId]) {
                $list[$qv[1]] = 1;
            }
        }

        return array_rand($list);
    }

    /***
     * 消费者逻辑处理
     *
     * @param string $json
     * @return bool|mixed
     * @throws Exception
     */
    public function mqConsumerLogic($json = "") {

        try {

            output($this->logFile, date('Y-m-d H:i:s') . '进入消息处理逻辑方法：'.$json);
            if (!is_string($json))  throw_exception("传入数据非字符串");
            if (!is_json($json))    throw_exception("传入数据非JSON");
            if (empty($json))      throw_exception("传入数据不能为空字符串");

            $modelMQLog = M('mq_log');
            $mqData     = json_decode($json, true);
            if (empty($mqData['type'])) throw_exception("业务逻辑类型不能为空");

            // 检查事务层级是否正常，消费者逻辑执行时必须保证执行前的事务层级为0 START
            $transTimes = M()->getTransTimes();
            if ($transTimes != 0) {

                $exception['exception_area'] = 'mqException';   // 消息队列处理相关异常专用Key
                $exception['description']    = "严重警告，执行消费者逻辑[".strtolower($mqData['type'])."]发现上次逻辑事务未正常关闭，当前层级：{$transTimes}";
                D("Admin/Exception")->addLog($exception);

                M()->rollback(); // 执行一次rollback(); 因事务回滚，上面的addLog记录也会丢失。

                $exception['send_mode']      = ['wechat'];
                $exception['exception_area'] = 'mqException';   // 消息队列处理相关异常专用Key
                $exception['description']    = "已执行事务回滚，上次数据处理未正常保存于数据库，请注意检查数据。[".strtolower($mqData['type'])."]{$mqData['logId']}";
                D("Admin/Exception")->addLog($exception);

                // 此处不暂停，直接执行新的任务
            }
            // 检查事务层级是否正常，消费者逻辑执行时必须保证执行前的事务层级为0 END

            if (isset($mqData['content'])) {
                // 转成驼峰法命名
                $method = convert_underline('mqConsumerFor_' . strtolower($mqData['type']));
                // 检查在当前类中是否存在该方法
                if (!method_exists($this, $method)) throw_exception($method . " 该方法不存在");
                $logStatus = $modelMQLog -> where(['hash' => $mqData['hash'], 'id' => $mqData['logId']]) -> getField('status');
                output($this -> logFile, M() -> query("/*PROXY_INTERNAL*/show last route;"));
                // 从主库操作
                M() -> hint('master');

                $logStatus = $modelMQLog -> where(['hash' => $mqData['hash'], 'id' => $mqData['logId']]) -> getField('status');
                if (in_array($logStatus, [3, 4])) throw_exception("{$method} MQLogID：{$mqData['logId']} 已是被标记成完成的状态，不可重复处理");

                // 准备开始处理逻辑标记
                $modelMQLog -> where(['hash' => $mqData['hash'], 'id' => $mqData['logId']]) -> save(['status' => 2]);
                output($this -> logFile, M() -> query("/*PROXY_INTERNAL*/show last route;"));
                try {
                    $response = call_user_func_array([$this, $method], [$mqData, $this->logFilePre . "Log/{$method}.log"]);
                } catch (Exception $ee) {
                    $exception['exception_area'] = 'mqException';   // 消息队列处理相关异常专用Key
                    $exception['description']    = "不要抛出异常到上一层，MQService内部方法：{$method}";
                    D("Admin/Exception")->addLog($exception);
                    // TODO 暂不处理标记
                    return false;
                }
                // 返回，务必为true|false
                switch ($response) {
                    case true:
                        $modelMQLog -> where(['hash' => $mqData['hash'], 'id' => $mqData['logId']]) -> save(['status' => 3]);
                        break;
                    case false:
                        $modelMQLog -> where(['hash' => $mqData['hash'], 'id' => $mqData['logId']]) -> save(['status' => 4]);
                        break;
                    default:
                        $exception['exception_area'] = 'mqException';   // 消息队列处理相关异常专用Key
                        $exception['description']    = "MQService内部方法{$method}返回非bool类型，logId：".$mqData['logId'];
                        D("Admin/Exception")->addLog($exception);
                        // TODO 暂不处理标记
                        break;
                }
                return $response;
            } else {
                throw_exception("数据内容出错，不存在[content]键值");
            }

            return true;

        } catch (Exception $e) {
            output($this->logFile, date('Y-m-d H:i:s') . ' ' . $e -> getMessage());
            throw_exception($e->getMessage());
        }
    }

    /***
     * 推送消息到消息队列
     *
     * @param $message
     * @param string $type
     * @param string $mqType
     * @param string $keyRoute
     * @param int $expiration
     * @return bool
     * @throws Exception
     */
    public function pushMessageToMQ($message, $type = 'default', $mqType = MQParam::DEFAULT_MESSAGE_QUEUE, $keyRoute = '', $expiration = 0) {

        switch (strtolower($mqType)) {
            case "rabbitmq":

                $exchange   = MQParam::$exchange;
                $queueName  = MQParam::$queue;
                $mqRouteKey = MQParam::$mqRouteKey;

                $routeKey = $mqRouteKey[$type];
                $keyRoute = empty($keyRoute) ? $this -> getRandRouteKey($type) : $keyRoute;
                $routeKey = empty($routeKey) ? MQParam::$defaultKey : $routeKey;
                $keyRoute = empty($keyRoute) ? MQParam::$defaultKey : $keyRoute;

                foreach ($queueName as $qk => $qv) {
                    if ($qv[1] == $keyRoute) {
                        if ($expiration > 0) {
                            $object = new RabbitmqService($exchange[$routeKey], $qk, $keyRoute, 'xjlc_'.MQParam::$delayQueueType, $this -> getRandRouteKey(MQParam::$delayQueueType));
                            $object->send($message, $expiration * 1000);
                        } else {
                            $object = new RabbitmqService($exchange[$routeKey], $qk, $keyRoute);
                            $object->send($message);
                        }
                        break;
                    }
                }

                return true;
                break;
        }
        return false;
    }

    /***
     * 内部方法模板
     *
     * 私有方法，同时必须返回true或者false，不可向上层抛异常。
     *
     * @param $param
     * @param $logFile
     * @return bool
     */
    private function mqConsumerForTemplate ($param, $logFile) {

        $graylogHash = get_random_str(40); // 生成一个Graylog标识字符串

        // 处理数据时请合理使用事务，同时注意连贯操作下的数据库主从读取问题。

        try {
            $content   = $param['content'];     // 存储的信息正文内容，格式为数组，即送入队列时的信息
            $ip        = $param['ip'];          // 送入队列时候的IP
            $addTime   = $param['add_time'];    // 送入队列时候的时间
            $paramType = $param['type'];        // 业务类型

            // FIXME 理论上，你要的数据都在 $content 中

            /****************************************************************\
             *  请在此处理相关的逻辑，如需存储日志或通知，请参考下述代码。  *
            \****************************************************************/

            // ● 如果需要output打印日志，请参考下例，$logFile会以对应业务类型区分名字。
            // output() Start
            $outputMessage = "这是内部方法模板";
            output($logFile, $outputMessage . "，Graylog查询码[graylogHash]：{$graylogHash}");
            // output() End

            // ● 如果需要Graylog存储日志，请参考下例，$graylogConfig的内容能方便地在Graylog进行搜索。
            // graylog Start
            $graylogConfig                = [
                '键名1' => '键值1',
                '键名2' => '键值2',
                '键名3' => '键值3',
                // ……
            ];
            $graylogConfig['graylogHash'] = $graylogHash;       // 标识，可表示关联output打印
            $graylogConfig['graylogName'] = $this->graylogName;
            $graylogMessage               = "这是MQ中{$paramType}的日志信息"; // 该信息将成为键名为message的值。
            glog($graylogMessage, $graylogConfig);
            // graylog End

            // ● 如果需要短信和微信通知
            // 短信+微信 Start
            $exception['exception_area'] = 'mqException';   // 消息队列处理相关异常专用Key
            $exception['description']    = "这是MQ中{$paramType}的日志信息，具体信息请查询Graylog或本地日志"; // 可自定义，字数不要太多
            D("Admin/Exception")->addLog($exception);
            // 短信+微信 End

            return true;    // TODO 处理完毕，无论成功与否，必须返回 true 或 false，上层会根据这个状态修改数据库status状态。
        } catch (Exception $e) {
            /****************************************************************\
             *  请不要抛出异常到上一层，上一层只处理基本逻辑，不处理异常    *
            \****************************************************************/
            return false;   // TODO 处理完毕，无论成功与否，必须返回 true 或 false，上层会根据这个状态修改数据库status状态。
        }
    }

    private function mqConsumerForDefault ($param, $logFile) {

        $graylogHash = get_random_str(40); // 生成一个Graylog标识字符串

        // 处理数据时请合理使用事务，同时注意连贯操作下的数据库主从读取问题。

        try {
            $content   = $param['content'];     // 存储的信息正文内容，格式为数组，即送入队列时的信息
            $ip        = $param['ip'];          // 送入队列时候的IP
            $addTime   = $param['add_time'];    // 送入队列时候的时间
            $paramType = $param['type'];        // 业务类型

            output($logFile, $content);
            try {
                service('Admin/Disclosure') -> checkParam(disclosureParam::$disclosureParam, $content);
            } catch (Exception $e) {
                output($logFile, date('Y-m-d H:i:s') . ' ' . $e -> getMessage());
                return false;
            }

            return true;    // TODO 处理完毕，无论成功与否，必须返回 true 或 false，上层会根据这个状态修改数据库status状态。
        } catch (Exception $e) {
            /****************************************************************\
             *  请不要抛出异常到上一层，上一层只处理基本逻辑，不处理异常    *
            \****************************************************************/
            return false;   // TODO 处理完毕，无论成功与否，必须返回 true 或 false，上层会根据这个状态修改数据库status状态。
        }
    }

    /***
     * 消费者心跳监测
     *
     * @param $param
     * @param $logFile
     * @return bool
     */
    private function mqConsumerForConsumerMonitor ($param, $logFile) {

        $graylogHash = get_random_str(40); // 生成一个Graylog标识字符串

        try {
            $content   = $param['content'];     // 存储的信息正文内容，格式为数组，即送入队列时的信息
            $ip        = $param['ip'];          // 送入队列时候的IP
            $addTime   = $param['add_time'];    // 送入队列时候的时间
            $paramType = $param['type'];        // 业务类型

            output($logFile, date('Y-m-d H:i:s') . ' 读取到数据信息：'.json_encode($param));

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /***
     * 信息披露 消费者逻辑
     *
     * @param $param
     * @param $logFile
     * @return bool
     */
    private function mqConsumerForDisclosure ($param, $logFile) {

        $graylogHash = get_random_str(40); // 生成一个Graylog标识字符串

        // 处理数据时请合理使用事务，同时注意连贯操作下的数据库主从读取问题。

        try {
            $content   = $param['content'];     // 存储的信息正文内容，格式为数组，即送入队列时的信息
            $ip        = $param['ip'];          // 送入队列时候的IP
            $addTime   = $param['add_time'];    // 送入队列时候的时间
            $paramType = $param['type'];        // 业务类型

            printAndLog("开始执行信息披露消费者逻辑，param：" . json_encode($param));
            $assetNo = $content['assetNo'];
            if (!empty($assetNo)) {
                $response = service('Admin/Disclosure') -> updateCreditInfo($assetNo);
            }
            if ($content['test'] == 'all') {
                printAndLog($response ? '请求成功，接收到普惠数据：'.json_encode($response) : '请求失败');
            } else {
                printAndLog($response ? '请求成功' : '请求失败');
            }

            return true;    // TODO 处理完毕，无论成功与否，必须返回 true 或 false，上层会根据这个状态修改数据库status状态。
        } catch (Exception $e) {
            /****************************************************************\
             *  请不要抛出异常到上一层，上一层只处理基本逻辑，不处理异常    *
            \****************************************************************/
            printAndLog("mqConsumerForDisclosure 收到异常返回：" . $e -> getMessage());
            return false;   // TODO 处理完毕，无论成功与否，必须返回 true 或 false，上层会根据这个状态修改数据库status状态。
        }
    }

    /***
     * 微信模板消息 消费者逻辑
     *
     * @param $param
     * @param $logFile
     * @return bool
     */
    private function mqConsumerForWechat($param, $logFile) {

        $graylogHash = get_random_str(40); // 生成一个Graylog标识字符串

        try {
            $content   = $param['content'];     // 存储的信息正文内容，格式为数组，即送入队列时的信息
            $ip        = $param['ip'];          // 送入队列时候的IP
            $addTime   = $param['add_time'];    // 送入队列时候的时间
            $paramType = $param['type'];        // 业务类型

            printAndLog("开始执行微信模板消息消费者逻辑，param：" . json_encode($param));
            $logic = strtolower($content['logic']);

            $service = service("Admin/Wechat");

            switch ($logic) {
                case "login":   // 登录模板消息
                    //代码中模板格式
                    //$mqParam               = [];
                    //$mqParam['logic']      = 'login';
                    //$mqParam['uid']        = $this->uid;
                    //$mqParam['sourceName'] = $source;
                    //$mqParam['url']        = C('CHANGE_LOGIN_PASS');
                    //$mqRes                 = service('Admin/MQ')->mqProducer($mqParam, 'wechat');
                    if ($content['uid']) {
                        $service->mqSendLoginMessage($content['uid'], $content, $ip, $addTime);
                    }
                    break;
                case "multi_warning":   // 多条服务器报警提醒
                    //代码中模板格式
                    //$mqParam            = [];
                    //$mqParam['logic']   = 'multi_warning';
                    //$mqParam['uids']    = $contacts;
                    //$mqParam['type']    = $val['exception_area'];
                    //$mqParam['first']   = $val['description'];
                    //$mqParam['grade']   = '高';
                    //$mqParam['message'] = '服务器处理异常通知';
                    //$mqParam['remark']  = '';
                    //$mqParam['url']     = '';
                    //$mqRes              = service('Admin/MQ')->mqProducer($mqParam, 'wechat');
                    foreach ($content['uids'] as $uid) {
                        if (is_numeric($uid)) {
                            $service -> mqSendWarningTemplateMessage($uid, $content, $ip, $addTime);
                        }
                    }

                    break;
                case "trade_message":   // 交易模板消息
                    $service -> sendTemplateMessage(json_encode($content['template']));
                    break;
                case "bill":
                    if ($content['uid']) {
                        $uid     = $content['uid'];
                        $newId   = $content['new_id'];
                        $logFile = "Log/statisticWechatTemplateBillData.log";
                        // 发送模板消息
                        $resMsg = service("Admin/Wechat")->mqSendBillMessage($uid, $content);
                        if ($resMsg !== false) {
                            output($logFile, date("Y-m-d H:i:s") . " 唯一识别码【{$content['rand_str']}】 ===> 用户【{$uid}】月账单发送成功：" . json_encode($content));
                            printAndLog(date("Y-m-d H:i:s") . " 唯一识别码【{$content['rand_str']}】 ===> 用户【{$uid}】月账单发送成功：" . json_encode($content));
                            // 判断发送成功
                            M('msg_remind')->where(['id' => $newId])->save(['status' => 1]);
                        } else {
                            output($logFile, date("Y-m-d H:i:s") . " 唯一识别码【{$content['rand_str']}】 ===> 用户【{$uid}】月账单发送失败：" . json_encode($content));
                            printAndLog(date("Y-m-d H:i:s") . " 唯一识别码【{$content['rand_str']}】 ===> 用户【{$uid}】月账单发送失败：" . json_encode($content));
                            M('msg_remind')->where(['id' => $newId])->save(['status' => 1, 'type' => 'bill_remind_fail']);
                        }
                    } else {
                        printAndLog(date("Y-m-d H:i:s") . " 未获取到用户uid，无法发送模板消息【Bill】". json_encode($content));
                    }
                default:

                    break;
            }

            return true;    // TODO 处理完毕，无论成功与否，必须返回 true 或 false，上层会根据这个状态修改数据库status状态。
        } catch (Exception $e) {
            /****************************************************************\
             *  请不要抛出异常到上一层，上一层只处理基本逻辑，不处理异常    *
             * \****************************************************************/
            printAndLog("mqConsumerForDisclosure 收到异常返回：" . $e->getMessage());
            return false;   // TODO 处理完毕，无论成功与否，必须返回 true 或 false，上层会根据这个状态修改数据库status状态。
        }
    }

    /***
     * 短信发送 消费者逻辑
     *
     * @param $param
     * @param $logFile
     * @return bool
     */
    private function mqConsumerForSms($param, $logFile) {

        $graylogHash = get_random_str(40); // 生成一个Graylog标识字符串

        try {
            $content   = $param['content'];     // 存储的信息正文内容，格式为数组，即送入队列时的信息
            $ip        = $param['ip'];          // 送入队列时候的IP
            $addTime   = $param['add_time'];    // 送入队列时候的时间
            $paramType = $param['type'];        // 业务类型

            printAndLog("开始执行短信发送消费者逻辑，param：" . json_encode($param));
            $logic = strtolower($content['logic']);

            $service = service("Admin/Wechat");

            switch ($logic) {
                case "sendsmscl":   // 创蓝发送
                    //代码中模板格式
                    //$mqParam             = [];
                    //$mqParam['logic']    = 'sendSmsCL';
                    //$mqParam['cellphone']= $contacts;
                    //$mqParam['smskey']   = 'warning';
                    //$mqParam['content']  = $val['description'];
                    //$mqParam['continue'] = false;
                    //$mqRes               = service('Admin/MQ')->mqProducer($mqParam, 'sms');
                    if (is_array($content['cellphone'])) {
                        foreach ($content['cellphone'] as $cellphone) {
                            $r = sendSmsCL($cellphone, $content['smskey'], $content['content'], $content['continue']);
                        }
                    } else {
                        $r = sendSmsCL($content['cellphone'], $content['smskey'], $content['content'], $content['continue']);
                    }
                    break;
                case "sendsms":   // 云片发送
                    if (is_array($content['cellphone'])) {
                        foreach ($content['cellphone'] as $cellphone) {
                            $r = sendSms($cellphone, $content['smskey'], $content['content'], $content['continue']);
                        }
                    } else {
                        $r = sendSms($content['cellphone'], $content['smskey'], $content['content'], $content['continue']);
                    }
                    break;
                default:

                    break;
            }

            return true;    // TODO 处理完毕，无论成功与否，必须返回 true 或 false，上层会根据这个状态修改数据库status状态。
        } catch (Exception $e) {
            /****************************************************************\
             *  请不要抛出异常到上一层，上一层只处理基本逻辑，不处理异常    *
             * \****************************************************************/
            printAndLog("mqConsumerForDisclosure 收到异常返回：" . $e->getMessage());
            return false;   // TODO 处理完毕，无论成功与否，必须返回 true 或 false，上层会根据这个状态修改数据库status状态。
        }
    }

    /***
     * 图片处理
     *
     * 私有方法，同时必须返回true或者false，不可向上层抛异常。
     *
     * @param $param
     * @param $logFile
     * @return bool
     */
    private function mqConsumerForImage ($param, $logFile) {

        $graylogHash = get_random_str(40); // 生成一个Graylog标识字符串

        try {
            $content   = $param['content'];     // 存储的信息正文内容，格式为数组，即送入队列时的信息
            $ip        = $param['ip'];          // 送入队列时候的IP
            $addTime   = $param['add_time'];    // 送入队列时候的时间
            $paramType = $param['type'];        // 业务类型

            $type      = strtolower($content['logic']);

            switch ($type) {
                case "disclosure_image":
                    $assetNo    = $content['asset_no'];
                    $existListTemp = M('image') -> where(['asset_no' => $assetNo]) -> select();
                    $existList = [];
                    foreach ($existListTemp as $val) {
                        $existList[$val['hash']] = $val['id'];
                    }
                    foreach ($content['photos_info'] as $key => $one) {
                        foreach ($one as $k => $v) {
                            $imageUrl = $v['image'];
                            if (strpos($v['image'], '?') > 0) {
                                $imageUrl = substr($imageUrl, 0, strpos($imageUrl, '?'));
                            }
                            $hash       = sha1($imageUrl);

                            $modelImage = M('image');
                            $record     = $modelImage->where(['hash' => $hash, 'asset_no' => $assetNo])->find();
                            
                            if ($existList[$hash]) {
                                unset($existList[$hash]);
                            }
                            // 如果图片已经存在，不处理
                            if ($record) {
                                continue;
                            }

                            $res = D('Admin/Image')->saveImage($v['image'], $hash);
                            if (isset($res['error'])) {
                                // 微信报警 Start
                                $exception['send_mode']      = ['wechat'];
                                $exception['exception_area'] = 'mqException';   // 消息队列处理相关异常专用Key
                                $exception['description']    = "mqConsumerForImage {$paramType} 获取图片失败"; // 可自定义，字数不要太多
                                D("Admin/Exception")->addLog($exception);
                                // 微信报警 End

                                throw_exception($res['error']);
                            }
                            $myParam = [
                                'name'            => $hash,
                                'type'            => $key,
                                'category'        => 'disclosure_image',
                                'full_img'        => $res['origin'],
                                'thumb_img'       => $res['thumb'],
                                'water_full_img'  => $res['water_origin'],
                                'water_thumb_img' => $res['water_thumb'],
                                'status'          => 1,
                            ];

                            if (!$record) {
                                $myParam['asset_no']    = $assetNo;
                                $myParam['add_time']    = time();
                                $myParam['add_ip']      = $ip;
                                $myParam['hash']        = $hash;
                                $myParam['origin_file'] = $v['image'];
                                $modelImage->add($myParam);
                            } else {
                                $myParam['update_time'] = time();
                                $myParam['update_ip']   = $ip;
                                $modelImage->where(['hash' => $hash, 'asset_no' => $assetNo])->save($myParam);
                            }
                        }
                    }
                    if (!empty($existList)) {
                        M('image') -> where(['id' => ['in', $existList]]) -> save(['status' => 2]);
                    }
                    break;
                default:
                    return false;
                    break;
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    /***
     * Push处理
     *
     * 私有方法，同时必须返回true或者false，不可向上层抛异常。
     *
     * @param $param
     * @param $logFile
     * @return bool
     */
    private function mqConsumerForPush ($param, $logFile) {

        $graylogHash = get_random_str(40); // 生成一个Graylog标识字符串

        try {
            $content   = $param['content'];     // 存储的信息正文内容，格式为数组，即送入队列时的信息
            $ip        = $param['ip'];          // 送入队列时候的IP
            $addTime   = $param['add_time'];    // 送入队列时候的时间
            $paramType = $param['type'];        // 业务类型

            $content['pushParam']['mqAddTime'] = $addTime;
            $content['pushParam']['mqAddIp']   = $ip;

            try {
                service('Admin/Push') -> push($content['pushAppId'], $content['pushMethod'], $content['pushParam'], $content['messageTemplate'], $content['isQueue']);
            } catch (Exception $e) {
                printAndLog($e -> getMessage());
            }

            return true;
        } catch (Exception $e) {
            output('Log/zzzl.log', "出现异常：". $e -> getMessage());
            return false;
        }
    }
    /***
     * 派券
     *
     * 私有方法，同时必须返回true或者false，不可向上层抛异常。
     *
     * @param $param
     * @param $logFile
     * @return bool
     */
    private function mqConsumerForSendpack ($param, $logFile) {

        $graylogHash = get_random_str(40); // 生成一个Graylog标识字符串

        try {
            $content   = $param['content'];     // 存储的信息正文内容，格式为数组，即送入队列时的信息
            $ip        = $param['ip'];          // 送入队列时候的IP
            $addTime   = $param['add_time'];    // 送入队列时候的时间
            $paramType = $param['type'];        // 业务类型

            return send_pack($content['uid'],$content['packId']);

        } catch (Exception $e) {
            return false;
        }
    }

    /***
     * 投资回调的非核心逻辑
     *
     * 私有方法，同时必须返回true或者false，不可向上层抛异常。
     *
     * @param $param
     * @param $logFile
     * @return bool
     */
    private function mqConsumerForAfterinvest ($param, $logFile) {

        $graylogHash = get_random_str(40); // 生成一个Graylog标识字符串

        try {
            $content   = $param['content'];     // 存储的信息正文内容，格式为数组，即送入队列时的信息
            $ip        = $param['ip'];          // 送入队列时候的IP
            $addTime   = $param['add_time'];    // 送入队列时候的时间
            $paramType = $param['type'];        // 业务类型
            $service = service("Admin/Borrow");
            $service -> afterInvestCallback($content['borrowId']);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /***
     * 延时队列处理 【不处理延迟逻辑】
     *
     * TODO 注意，实际逻辑处理和推送的队列类型有关，延迟处理的逻辑类型为delay_letter，故相关逻辑请在mqConsumerForDelayLetter中处理
     *
     * @param $param
     * @param $logFile
     * @return bool
     */
    private function mqConsumerForDelayQueue ($param, $logFile) {

        $graylogHash = get_random_str(40); // 生成一个Graylog标识字符串

        // 处理数据时请合理使用事务，同时注意连贯操作下的数据库主从读取问题。

        try {
            $content   = $param['content'];     // 存储的信息正文内容，格式为数组，即送入队列时的信息
            $ip        = $param['ip'];          // 送入队列时候的IP
            $addTime   = $param['add_time'];    // 送入队列时候的时间
            $paramType = $param['type'];        // 业务类型

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /***
     * 延时队列处理
     *
     * 私有方法，同时必须返回true或者false，不可向上层抛异常。
     *
     * @param $param
     * @param $logFile
     * @return bool
     */
    private function mqConsumerForDelayLetter ($param, $logFile) {

        $graylogHash = get_random_str(40); // 生成一个Graylog标识字符串

        // 处理数据时请合理使用事务，同时注意连贯操作下的数据库主从读取问题。

        try {
            $content   = $param['content'];     // 存储的信息正文内容，格式为数组，即送入队列时的信息
            $ip        = $param['ip'];          // 送入队列时候的IP
            $addTime   = $param['add_time'];    // 送入队列时候的时间
            $paramType = $param['type'];        // 业务类型

            output($logFile, date("Y-m-d H:i:s") . ' 收到消息，消息内容：'.json_encode($param));

            $exception['send_mode']      = ['wechat'];
            $exception['exception_area'] = 'mqException';   // 消息队列处理相关异常专用Key
            $exception['description']    = json_encode($content);
            D("Admin/Exception")->addLog($exception);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /***
     * 计划还款通知
     *
     * 私有方法，同时必须返回true或者false，不可向上层抛异常。
     *
     * @param $param
     * @param $logFile
     * @return bool
     */
    private function mqConsumerForPlanRepaymentNotice ($param, $logFile) {

        $graylogHash = get_random_str(40); // 生成一个Graylog标识字符串

        // 处理数据时请合理使用事务，同时注意连贯操作下的数据库主从读取问题。

        try {
            $content   = $param['content'];     // 存储的信息正文内容，格式为数组，即送入队列时的信息
            $ip        = $param['ip'];          // 送入队列时候的IP
            $addTime   = $param['add_time'];    // 送入队列时候的时间
            $paramType = $param['type'];        // 业务类型

            $res = service('Admin/BorrowPlan') -> planRepaymentNotice($content['day']);

            if ($res['alarm'] === true) {
                $exception['send_sms']       = ['wechat'];
                $exception['exception_area'] = 'mqException';   // 消息队列处理相关异常专用Key
                $exception['description']    = "截至" . date('Y-m-d H:i:s') . "，今日仍有{$res['wait_count']}笔【智慧投】回款未完成。";
                D("Admin/Exception")->addLog($exception);

                $graylogConfig                = $res;
                $graylogConfig['graylogHash'] = $graylogHash;       // 标识，可表示关联output打印
                $graylogConfig['graylogName'] = $this->graylogName;
                $graylogMessage               = "截至" . date('Y-m-d H:i:s') . "，今日仍有{$res['wait_count']}笔智慧投回款未完成。";
                glog($graylogMessage, $graylogConfig);
            } else {
                $graylogConfig                = $res;
                $graylogConfig['graylogHash'] = $graylogHash;       // 标识，可表示关联output打印
                $graylogConfig['graylogName'] = $this->graylogName;
                $graylogMessage               = "今日智慧投回款状态";
                glog($graylogMessage, $graylogConfig);
            }

            $outputMessage = date("Y-m-d H:i:s") . " mqConsumerForPlanRepaymentNotice";
            output($logFile, $outputMessage . "，Graylog查询码[graylogHash]：{$graylogHash}");

            // repayment_status 为 2，表示还存在未回完的记录，继续送入队列，准备下次搜索
            if ($res['repayment_status'] == 2) {
                $redis     = RedisCache::G();
                $delayTime = time() + MQParam::$defaultDelayTime;
                $mqRes     = service('Admin/MQ')->mqProducer([], 'plan_repayment_notice', $delayTime);
                if ($mqRes == true) {
                    $redis->Set(MQParam::$redisPlanRepaymentNoticeKey, $delayTime, MQParam::$defaultDelayTime);
                }
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /***
     * 分发债权到队列
     *
     * 私有方法，同时必须返回true或者false，不可向上层抛异常。
     *
     * @param $param
     * @param $logFile
     * @return bool
     */
    private function mqConsumerForDistributeCreditor ($param, $logFile) {

        $graylogHash = get_random_str(40); // 生成一个Graylog标识字符串

        // 处理数据时请合理使用事务，同时注意连贯操作下的数据库主从读取问题。

        try {
            $content   = $param['content'];     // 存储的信息正文内容，格式为数组，即送入队列时的信息
            $ip        = $param['ip'];          // 送入队列时候的IP
            $addTime   = $param['add_time'];    // 送入队列时候的时间
            $paramType = $param['type'];        // 业务类型

            $logic = $content['logic'];

            $redis            = RedisCache::G();

            switch (strtolower($logic)) {
                case 'distribute_creditor':
                    try {
                        $res = service('Admin/BorrowPlan') -> distributeCreditor();
                    } catch (Exception $e) {
//                        $delayTime        = time() + intval(MQParam::$defaultDelayTime / 10);
//                        $mqParam          = [];
//                        $mqParam['logic'] = "distribute_creditor";
//                        $mqRes            = $this->mqProducer($mqParam, 'distribute_creditor', $delayTime);
//                        if ($mqRes == true) {
//                            $redis->Set(MQParam::$redisDistributeCreditorKey, $delayTime, intval(MQParam::$defaultDelayTime / 10));
//                        }
                        send_exception('planMatchError', '消费者：'.$e->getMessage(), ['wechat']); // 异步警报
                    }

                    if ($res['result'] != 'empty') {
                        // 分发下一次任务，延时处理，不用计划任务，但需要一个全局计划任务去校验。
//                        $delayTime        = time() + intval(MQParam::$defaultDelayTime / 10);
//                        $mqParam          = [];
//                        $mqParam['logic'] = "distribute_creditor";
//                        $mqRes            = $this->mqProducer($mqParam, 'distribute_creditor', $delayTime);
//                        if ($mqRes == true) {
//                            $redis->Set(MQParam::$redisDistributeCreditorKey, $delayTime, intval(MQParam::$defaultDelayTime / 10));
//                        }
                    }

                    break;
                default:

                    break;
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /***
     * 债权匹配
     *
     * 私有方法，同时必须返回true或者false，不可向上层抛异常。
     *
     * @param $param
     * @param $logFile
     * @return bool
     */
    private function mqConsumerForPlanMatch ($param, $logFile) {

        $graylogHash = get_random_str(40); // 生成一个Graylog标识字符串

        // 处理数据时请合理使用事务，同时注意连贯操作下的数据库主从读取问题。

        try {
            $content   = $param['content'];     // 存储的信息正文内容，格式为数组，即送入队列时的信息
            $ip        = $param['ip'];          // 送入队列时候的IP
            $addTime   = $param['add_time'];    // 送入队列时候的时间
            $paramType = $param['type'];        // 业务类型

            $assetNo = $content['asset_no'];
            $logic   = $content['logic'];

            switch (strtolower($logic)) {
                case "plan_match":
                    $res = service('Admin/BorrowPlan') -> batchPlanMatch($assetNo);
                    break;
                default:

                    break;
            }

            // 匹配完毕后获取下信批数据，加速页面显示
            try {
                service('Admin/MQ')->mqProducer(['assetNo' => $assetNo], 'disclosure');
            } catch (Exception $e) {

            }

            // TODO 后续记录相关日志

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /***
     * 异步报警提醒
     *
     * 私有方法，同时必须返回true或者false，不可向上层抛异常。
     *
     * @param $param
     * @param $logFile
     * @return bool
     */
    private function mqConsumerForException ($param, $logFile) {

        $graylogHash = get_random_str(40); // 生成一个Graylog标识字符串

        // 处理数据时请合理使用事务，同时注意连贯操作下的数据库主从读取问题。

        try {
            $content   = $param['content'];     // 存储的信息正文内容，格式为数组，即送入队列时的信息
            $ip        = $param['ip'];          // 送入队列时候的IP
            $addTime   = $param['add_time'];    // 送入队列时候的时间
            $paramType = $param['type'];        // 业务类型

            $logic   = $content['logic'];

            switch (strtolower($logic)) {
                case "exception":
                    $exception['send_mode']      = $content['send_mode'];
                    $exception['exception_area'] = $content['exception_area'];
                    $exception['description']    = $content['description'];
                    D("Admin/Exception")->addLog($exception);
                    break;
                default:

                    break;
            }

            return true;    // TODO 处理完毕，无论成功与否，必须返回 true 或 false，上层会根据这个状态修改数据库status状态。
        } catch (Exception $e) {
            /****************************************************************\
             *  请不要抛出异常到上一层，上一层只处理基本逻辑，不处理异常    *
            \****************************************************************/
            return false;   // TODO 处理完毕，无论成功与否，必须返回 true 或 false，上层会根据这个状态修改数据库status状态。
        }
    }

    /***
     * 日志相关消费者
     *
     * 私有方法，同时必须返回true或者false，不可向上层抛异常。
     *
     * @param $param
     * @param $logFile
     * @return bool
     */
    private function mqConsumerForLog ($param, $logFile) {

        $graylogHash = get_random_str(40); // 生成一个Graylog标识字符串

        // 处理数据时请合理使用事务，同时注意连贯操作下的数据库主从读取问题。

        try {
            $content   = $param['content'];     // 存储的信息正文内容，格式为数组，即送入队列时的信息
            $ip        = $param['ip'];          // 送入队列时候的IP
            $addTime   = $param['add_time'];    // 送入队列时候的时间
            $paramType = $param['type'];        // 业务类型

            $logic   = $content['logic'];

            switch (strtolower($logic)) {
                case "escrow_request":

                    $modelCRL = M('cg_request_log');

                    $hash   = $content['hash'];
                    $save   = json_decode($content['saveParam'], true);
                    $resLog = $modelCRL->where(['hash' => $hash])->find();
                    if ($resLog) {
                        $modelCRL->where(['hash' => $hash])->save($save);
                    } else {
                        $save['hash'] = $hash;
                        $modelCRL->add($save);
                    }
                    break;
                default:

                    break;
            }

            return true;    // TODO 处理完毕，无论成功与否，必须返回 true 或 false，上层会根据这个状态修改数据库status状态。
        } catch (Exception $e) {
            return false;   // TODO 处理完毕，无论成功与否，必须返回 true 或 false，上层会根据这个状态修改数据库status状态。
        }
    }

    /**
     * 异步导出
     * @param $param
     * @param $logFile
     * @return bool
     */
    private function mqConsumerForExport($param, $logFile) {
        try {
            printAndLog('开始导出文件' . json_encode($param));
            $sql       = $param['sql'];
            $fieldList = $param['fieldList'];
            $name      = $param['name'];
            $type      = $param['type'];
            $aUid      = $param['aUid'];
            $res = service('Admin/Export')->execute($sql, $fieldList, $name, $type,$aUid);
            return $res;
        } catch (Exception $e) {
            return false;
        }
    }

    /***
     * 存管文件异步导出
     * @param $param
     * @param $logFile
     * @return bool
     *
     *
     * 异步导出方法说明：
     *
     *  0、async 参数为1标记为异步导出，同时必须满足export=1，即在导出模式下同时有async标记则异步处理
     *  1、async_export_file() 函数外层必须添加 try{} catch{}
     *  2、参数：
     *      $sql：   请不要用 M()->getLastSql() 获取SQL，请用buildSql()直接生成sql语句作为函数第一参数（无需顾及buildSql前后的括号问题）
     *      $method：原导出时组装数据的方法【该方法必须为 public 】【请注意：该方法中需判断CLI模式下为 return $row; 不可 exportData()，详情参考示例】
     *      $filename：导出的文件名称，后台下载时将以该文件命名，本地存储文件非该名称。
     *      $preMethod：数据预处理方法【该方法必须为 public 】，如不存在，需自定义，用于调用$method前的数据处理，当然也可以直接写在$method中。非必填
     *  3、有修改导出代码的逻辑后务必杀死消息队列【存管文件异步导出】的消费者，否则异步导出时代码不会生效。
     *  4、在HTML中添加导出按钮，并一同添加async隐藏域。
     *  5、部分引入的CLI文件可能需要处理引入路径
     *
     * 示例：
     ***************************************************************

    控制器：

        if (I('async') == 1) {
            $buildSql = M()
                //-> ...
                //-> ...
                //-> ...
                ->buildSql();

            try {
                $asyncResult = async_export_file($buildSql, "【导出的处理方法】", "导出的文件名");
                if ($asyncResult !== true) {
                    $this->error("任务数据入队异常，请联系技术部");
                }
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
            $this->success("添加异步导出任务成功，稍后请移步至【存管】->【文件下载】->【文件生成记录】处下载文件");
            return true;
        }

      *---------------------------------------------------------------

        public function _export($list)
        {
            $row[0] = array('xxx', 'xxx', 'xxx', 'xxx');
            $i      = 1;
            foreach ($list as $key => $v) {
                $row[$i]['id'] = $v['id'];

                // ...

                $i++;
            }
            // 判断CLI模式并返回参数
            if (IS_CLI) {
                return $row;
            } else {
                exportData('xxxx', $row);
            }
        }

    HTML：

    <input id="async" name="async" type="hidden" value="0">
    <input type="button" class="bGreyish buttonL" value="Excel异步导出" onclick="this.form.export.value=1;this.form.async.value=1;this.form.submit();"/>&nbsp;&nbsp;

     ***************************************************************
     *
     */

    private function mqConsumerForCgExport($param, $logFile) {
        $graylogHash = get_random_str(40); // 生成一个Graylog标识字符串

        try {
            $content   = $param['content'];     // 存储的信息正文内容，格式为数组，即送入队列时的信息
            $ip        = $param['ip'];          // 送入队列时候的IP
            $addTime   = $param['add_time'];    // 送入队列时候的时间
            $paramType = $param['type'];        // 业务类型
            $adminName = $param['admin_name'];  // 管理员名字
            $adminId   = $param['admin_id'];    // 管理员ID

            sleep(2); // 停两秒
            M()->hint('master'); // 操作主库

            $logic  = $content['logic'];
            switch (strtolower($logic)) {
                case "cg_export":

                    $sql        = $content['sql'];
                    $asyncParam = $content['async_param'];
                    $class      = $content['class'];
                    $method     = $content['method'];
                    $preMethod  = $content['pre_method'];
                    $filename   = $content['filename']; // 保存的文件名称
                    $insertId   = $content['insert_id']; // 数据库存储的记录ID

                    // 系统参数
                    $systemParam = [
                        'systemParam' => [
                            'systemAdminId' => $adminId
                        ]
                    ];

                    output($logFile, date("Y-m-d H:i:s"),"进入mqConsumerForCgExport",$param);
                    output($logFile, date("Y-m-d H:i:s"),"准备执行");

                    ini_set("memory_limit", "8192M");
                    set_time_limit(0);
                    ini_set('max_execution_time', 0);

                    $path = "./Upload/Export/";
                    if (IS_CLI) $path = "/xjlc/php/www.xiaojilicai.com/Upload/Export/";
                    if (!is_dir($path)) {
                        mkdir($path, 0755, true);
                    }
                    $randFileName = $filename . "-" . date('YmdHis') . "-" . $insertId  . "-" . mt_rand(1000000000, 9999999999);

                    $modelCE = M('cg_export');

                    // 如果发现已经有开始时间，说明是第二次进来，则直接置为 失败，并返回true将消息从队列中删除，防止爆内存死循环造成存储空间用完。
                    $checkRecord = $modelCE->where(['id' => $insertId])->find();
                    if ($checkRecord['begin_time'] > 0) {
                        $modelCE->where(['id' => $insertId])->save([
                            'status' => 4
                        ]);
                        return true;
                    }

                    $modelCE->where(['id' => $insertId])->save([
                        'path'          => "/Upload/Export/" . $randFileName . ".xls",
                        'hash'          => get_random_str(40),
                        'begin_time'    => time(),
                        'save_filename' => $randFileName . ".xls",
                    ]);

                    $object       = A($class);
                    $commonObject = A('Admin/Common');

                    $countPage = 3000; // 每页3000条

                    $modelCE -> where(['id' => $insertId]) -> save(['status' =>  1]); // 中间态

                    $localFilePath = $path . $randFileName . ".xls";

                    $stopTarget = 10000;

                    for ($page = 1; $page <= $stopTarget; $page++) {
                        $fp  = fopen($localFilePath, 'a');
                        if ($page == 1) {
                            fwrite($fp, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\" xmlns:x=\"urn:schemas-microsoft-com:office:excel\" xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\" xmlns:html=\"http://www.w3.org/TR/REC-html40\">");
                            fwrite($fp, "\n<Worksheet ss:Name=\"" . $filename . "\">\n<Table>\n");
                        }

                        if (!empty($asyncParam) && is_array($asyncParam)) {
                            // 如果使用的是 _list
                            $asyncSystemParam = [
                                'systemParam' => [
                                    'timestamp' => $addTime,
                                    'page'      => $page - 1,
                                    'pageRows'  => $countPage
                                ]
                            ];
                            $asyncParam[1][count($asyncParam[1])] = $asyncSystemParam;
                            output($logFile, date("Y-m-d H:i:s")," asyncParam：",$asyncParam);
                            $dataList = call_user_func_array([$commonObject, "_async_list"], $asyncParam);
                        } else {
                            // 获取数据列表
                            $dataList = M()->query($sql . " LIMIT " . (($page - 1) * $countPage) . ",{$countPage}");
                            output($logFile, date("Y-m-d H:i:s")," SQL：".$sql . " LIMIT " . (($page - 1) * $countPage) . ",{$countPage}");
                            // 自定义函数处理
                            if ($preMethod) {
                                $dataList = $object->$preMethod($dataList);
                            }
                        }
                        if (empty($dataList)) {
                            $stopTarget = -1;
                        }

                        // 导出前数据集
                        $dataRow = $object->$method($dataList, $systemParam);
                        $len     = 0;
                        foreach ($dataRow as $array) {
                            if (($page > 1) && ($len == 0)) {
                                $len ++;
                                continue;
                            }

                            $cells = "";
                            foreach($array as $k => $v):
                                if (($k == 'i') && is_numeric($v)) $v += (($page-1)*$countPage); // 序号自增
                                $type = 'String';
                                $v = htmlentities($v, ENT_COMPAT, "UTF-8");
                                $cells .= "<Cell><Data ss:Type=\"$type\">" . $v . "</Data></Cell>\n";
                            endforeach;

                            fwrite($fp, "<Row>\n" . $cells . "</Row>\n");
                        }

                        if ($stopTarget == -1) {
                            fwrite($fp, "</Table>\n</Worksheet>\n");
                            fwrite($fp, "</Workbook>");
                        }
                        unset($dataRow);
                        fclose($fp);
                    }
                    output($logFile, date("Y-m-d H:i:s"),"准备完毕");

                    $fileSize            = get_file_size($localFilePath);
                    $save                = [];
//                    $save['status']      = 2;
                    $save['filesize']    = $fileSize ? $fileSize : "";
                    $save['finish_time'] = time();
                    $modelCE->where(['id' => $insertId])->save($save); // 已下载



                    $zip = new ZipArchive();
                    if ($zip->open($path.$filename.'.zip', ZipArchive::OVERWRITE) === TRUE)
                    {
                        $zip->addFile($localFilePath, iconv("UTF-8","GBK//IGNORE",$filename . ".xls"));
                        $zip->close();

                        // 如果文件生成成功，保存文件大小。
                        $zipFileSize           = get_file_size($path . $filename . '.zip');
                    }

                    // 上传文件到阿里云OSS
                    $exportBucket = "xjlcexport";
                    $ossFilePath  = "Export/excel/" . date('Ymd') . "/" . get_random_str(40) . "/" . $filename . ".zip";
                    $ossService   = service("Api/Oss");
                    $ossService->setBucket($exportBucket);
                    $ossService->uploadFile($ossFilePath, $path.$filename.'.zip');
                    $checkFileExist = $ossService->fileExist($ossFilePath);
                    if ($checkFileExist == true) {
                        $saveRes = $modelCE->where(['id' => $insertId])->save([
                            'oss_file_path' => $ossFilePath,
                        ]); // 已下载
                        if ($saveRes) {
                            unlink($localFilePath); // 删除本地文件
                            unlink($path.$filename.'.zip'); // 删除本地文件
                        }
                    }

                    $mapEnd                = [];
                    $mapEnd['status']      = 2;
                    $mapEnd['zipfilesize'] = $zipFileSize ? $zipFileSize : "";
                    $modelCE->where(['id' => $insertId])->save($mapEnd); // 已下载

                    break;
            }
            unset($object);
            unset($ossService);
            return true;
        } catch (Exception $e) {
            output($logFile, date("Y-m-d H:i:s"),"收到异常信息：".$e->getMessage());
            return false;
        }
    }
}