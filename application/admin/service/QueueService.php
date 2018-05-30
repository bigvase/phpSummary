<?php
namespace app\admin\service;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: bigsave
 * Date: 2017/12/15
 * Time: 10:02
 */
class QueueService
{
    private $key = '';
    private $prefix = '';
    private $handler = null;
    private $expire = 3600;

    function __construct($redisKey = '', $expire=0, $redis='default')
    {
        if ( !extension_loaded('redis') ) {
            exception('[not_support])'.':redis');
        }
        $redis_config = Config('REDIS');
//        dump($redis_config);die;
        $curr_redis_config = $redis_config[$redis];
        if (!isset($redis_config['default'])) {
            exception('请在配置文件中增加redis默认配置');
        }

        foreach ($redis_config['default'] as $key => $val) {
            if (!isset($curr_redis_config[$key])) {
                $curr_redis_config[$key] = $val;
            }
        }

        $options = array (
            'host'          => $curr_redis_config['host'] ? $curr_redis_config['host'] : '127.0.0.1',
            'port'          => $curr_redis_config['port'] ? $curr_redis_config['port'] : 6379,
            'timeout'       => $curr_redis_config['timeout'] ? $curr_redis_config['timeout'] : false,
        );

        $this->expire =  isset($expire)?  $expire  :   Config('DATA_CACHE_TIME');
        $this->prefix =  Config('DATA_CACHE_PREFIX');

        try {
            $this->handler = new \Redis;
            $this->key = $this->prefix . $redisKey;
            $options['timeout'] === false ?
                $this->handler->connect($options['host'], $options['port']) :
                $this->handler->connect($options['host'], $options['port'], $options['timeout']);
            if ($curr_redis_config['password']) {
                $rel = $this->handler->auth($curr_redis_config['password']);
                if (!$rel) {
                    exception("redis 认证密码错误");
                }
            }

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 缓存数据(string)
     * @param $key
     * @param $value
     * @param int $ttl
     * @return bool
     */
    public function rSet($key,$value,$ttl=3600){
        if(!strval($key) || !strval($value)) return false;
        if($this->handler->set($key,$value,$ttl)) return true;
        return false;
    }

    /**
     * 获取数据(string)
     * @param $key
     * @return bool|string
     */
    public function rGet($key){
        if(!is_string($key)) return false;
        return $this->handler->get($key);
    }

    /**
     * 重新设置缓存变量的生存时间
     *
     * @access public
     * @param string $key 缓存变量名
     * @param string $ttl 缓存生存时间，单位:秒
     * @return boolean
     */
    public function expire($key, $ttl=3600) {
        $key = strval($key);
        $ttl = intval($ttl);

        if ($key == '') {
            return false;
        }
        $time = $this->pTtl($key);
        if($time == -2){
            exception($key.'不存在');
        }
        try {
            $result = $this -> handler -> expire($key, $ttl);
        }catch (Exception $e) {
            exception("queue error");
            return false;
        }
        return $result ? true : false;
    }

    /**
     * 查看缓存过期时间
     * 当 key 不存在时，返回 -2 。
     * 当 key 存在但没有设置剩余生存时间时，返回 -1 。
     * 否则，以毫秒为单位，返回 key 的剩余生存时间。
     * @param $key
     * @return bool|int
     */
    public function pTtl($key){
        $key = strval($key);
        if($key == '') return false;
        $result = '';
        try{
            $result = $this->handler->pttl($key);
        }catch(Exception $e){
            exception('queue pttl error');
        }
        return $result;
    }

    /**
     * 批量删除缓存变量
     *
     * @access public
     * @param mixed $key [string|array] 当为string时，自动转换为array
     * @return boolean
     */
    public function delete($key) {
        !is_array($key) and $key = array($key);

        $tmp_arr = array();
        foreach ($key as $val) {
            $tmp_str = strval($val);
            $tmp_str !== '' and $tmp_arr[$tmp_str] = 1;
        }
        $key = array_keys($tmp_arr);

        try {
            $ret = true;
            foreach ($key as $val) {
                $result = $this -> _redis -> delete($val);
                !$result and $ret = false;
            }
        }catch(Exception $e) {
            return false;
        }

        return $ret;
    }

    /**
     * 清空redis中的所有数据
     *
     * @access public
     * @return boolean
     */
    public function clear() {
        try {
            $result = $this -> handler -> flushAll();
        }catch(Exception $e){
            return false;
        }

        return $result ? true : false;
    }

    /**
     * 将缓存变量放入redis队列，仅支持字符串及整型
     *
     * @access public
     * @param string $key 缓存变量名
     * @param string $value 缓存变量值
     * @param boolean $to_right 是否从右边入列
     * @return boolean
     */
    public function push($key, $value, $to_right=true) {
        $key = strval($key);
        $value = strval($value);
        if ($key === '' or $value === '') {
            return false;
        }

        $func = 'rPush';
        if(!$to_right)  $func = 'lPush';

        try {
            $result = $this -> handler -> $func($key, $value);
        }catch (Exception $e) {
            exception('push queue error!');
            return false;
        }

        return $result ? true : false;
    }
//队列测试
//for ($i=1;$i<=10;$i++){
//            $jsonDt = array(
//                'val'=>$i,
//                'num'=>0,
//                'time'=>time()
//            );
//            $queue->push('redis_test2',json_encode($jsonDt));
//        }
//        $val = $queue->pop('redis_test2');
//        $reload = json_decode($val,true);
//        dump($reload);
//        if(($reload['val'] ==5 || $reload['val']==6) && time()>$reload['time']){
//            $jsonDt1 = array(
//                'val'=>$reload['val'],
//                'num'=>$reload['num']+1,
//                'time'=>$reload['time']+700
//            );
//            $queue->push('redis_test2',json_encode($jsonDt1));
//        }

    /**
     * 缓存变量出列
     *
     * @access public
     * @param string $key 缓存变量名
     * @param boolean $from_left 是否从左边出列
     * @return boolean 成功返回缓存变量值，失败返回false
     */
    public function pop($key , $from_left=true) {
        $key = strval($key);
        if ($key === '') {
            return false;
        }

        $func = 'lPop';
        if(!$from_left)  $func = 'rPop';

        try {
            $result = $this -> handler -> $func($key);
        }catch(Exception $e){
            exception('pop queue error1');
            return false;
        }

        return $result;
    }

    /**
     * 缓存变量自增
     *
     * @access public
     * @param string $key 缓存变量名
     * @return boolean
     */
    public function increase($key) {
        $key = strval($key);
        if ($key === '') {
            return false;
        }

        try {
            $result = $this -> handler -> incr($key);
        }catch(Exception $e){
            exception('queue incr error');
            return false;
        }

        return $result ? true : false;
    }

    /**
     * 缓存变量自减
     *
     * @access public
     * @param string $key 缓存变量名
     * @return boolean 成功返回TRUE，失败返回FALSE
     */
    public function decrease($key) {
        $key = strval($key);
        if ($key === '') {
            return false;
        }
        try {
            $result = $this -> handler -> decr($key);
        }catch(Exception $e){
            exception('queue decr error');
            return false;
        }

        return $result ? true : false;
    }

    /**
     * 判断缓存变量是否已经存在
     *
     * @access public
     * @param string $key 缓存变量名
     * @return boolean 存在返回TRUE，否则返回FALSE
     */
    public function exists($key) {
        $key = strval($key);
        if ($key === '') {
            return false;
        }

        try {
            $result = $this -> handler -> exists($key);
        }catch (Exception $e) {
            return false;
        }

        return $result ? true : false;
    }

    /**
     * redis事务
     * @return bool
     */
    public function tranfer(){
        //监视count的值
        $this->handler->watch('count');
        //开启事务
        $this->handler->multi();
        //操作count
        $this->handler->set('count',time());
        //--------------------------------
        //模拟并发下其他进程对count的操作
        //redis-cli 执行 $redis->set('count','is simulate');
        sleep(10);
        //--------------------------------
        //提交事务
        $res = $this->handler->exec();
        if($res) return true;
        return false;
    }

    /**
     * 获取锁
     * @param  String  $key    锁标识
     * @param  Int     $expire 锁过期时间
     * @return Boolean
     */
    public function lock($key, $expire=5){
        $is_lock = $this->handler->setnx($key, time()+$expire);
        // 不能获取锁
        if(!$is_lock){
            // 判断锁是否过期
            $lock_time = $this->handler->get($key);
            // 锁已过期，删除锁，重新获取
            if(time()>$lock_time){
                $this->unlock($key);
                $is_lock = $this->handler->setnx($key, time()+$expire);
            }
        }
        return $is_lock? true : false;
    }

    /**
     * 释放锁
     * @param  String  $key 锁标识
     * @return Boolean
     */
    public function unlock($key){
        return $this->handler->del($key);
    }




}