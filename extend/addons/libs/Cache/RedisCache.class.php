<?php

/**
 *
 * redis内存缓存相关操作类
 * 全局，单列
 */
if (!defined("CACHE_SYS_pre"))
    define("CACHE_SYS_pre", "cachenorm");

class RedisCache {

    var $enable;
    var $obj;       // Redis 对象类
    private static $instance = null;

    public static function ver() {
        return __FILE__;
    }

    public static function G($config = array()) {
        if (self::$instance instanceof self) {

        } else {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    public function __destruct() {
        //if($this->obj) ;
    }

    public function RedisCache($config = array()) {
        if (!extension_loaded('redis')) {
            die('没有发现 Redis 类，请配置模块');
        }
        $options['host']       = $options['host'] ? $options['host'] : C('REDIS_HOST');
        $options['port']       = $options['port'] ? $options['port'] : C('REDIS_PORT');
        $options['timeout']    = C('DATA_CACHE_TIMEOUT') ? C('DATA_CACHE_TIMEOUT') : false;
        $options['persistent'] = 1;

        $this->options           = $options;
        $this->options['expire'] = isset($options['expire']) ? $options['expire'] : C('DATA_CACHE_TIME');
        $this->options['prefix'] = isset($options['prefix']) ? $options['prefix'] : C('REDIS_DB_PREFIX');
        $this->options['length'] = isset($options['length']) ? $options['length'] : 0;
        $func                    = $options['persistent'] ? 'pconnect' : 'connect';
        $this->obj               = new Redis;
        $options['timeout'] === false ?
            $this->obj->$func($options['host'], $options['port']) :
            $this->obj->$func($options['host'], $options['port'], $options['timeout']);
        if (C('REDIS_PWD')) {
            $rel = $this->obj->auth(C('REDIS_PWD'));
            if (!$rel) throw_exception("redis 认证密码错误");
        }
    }

    function Set($key, $val, $ttl = 1800) {//g
        $val = json_encode($val);
        if ($ttl) {
            return $this->obj->setex(CACHE_SYS_ID . $key, $ttl, $val);
        } else {
            return $this->obj->set(CACHE_SYS_ID . $key, $val);
        }
    }

    function Get($key, $default = null) {//
        if (is_array($key)) {
            return $this->get_multi($key);
        }
        return json_decode($this->obj->get(CACHE_SYS_ID . $key), TRUE);
    }

    function Del($key) {
        //设置过期时间为1毫秒
        $ret = $this->obj->del(CACHE_SYS_ID . $key);
        return $ret;
    }

    function Have($key) {
        // echo CACHE_SYS_ID.$key;
        // echo "<BR>";
        return $this->obj->exists(CACHE_SYS_ID . $key);
    }

    function Inc($key, $step = 1, $ttl = false) {
        //return $this->obj->incr(CACHE_SYS_ID.$key, $step);
        $ret = $this->obj->incr(CACHE_SYS_ID . $key, $step);
        if ($ret == $step && $ttl) {
            $this->obj->expire(CACHE_SYS_ID . $key, $ttl);
        }
        return $ret;
    }

    function Dec($key, $step = 1, $ttl = false) {
        $ret = $this->obj->decr(CACHE_SYS_ID . $key, $step);
        if ($ret == $step && $ttl) {
            $this->obj->expire(CACHE_SYS_ID . $key, $ttl);
        }
        return $ret;
    }

    function get_multi($keys) {
        $result    = $this->obj->getMultiple($keys);
        $newresult = array();
        $index     = 0;
        foreach ($keys as $key) {
            if ($result[$index] !== false) {
                $newresult[$key] = json_decode($result[$index], TRUE);
            }
            $index++;
        }
        unset($result);
        return $newresult;
    }

    function select($db = 0) {
        //集群不支持选库
        return $this->obj->select($db);
    }

    function set_multi($arr, $ttl = 0) {
        if (!is_array($arr)) {
            return false;
        }
        foreach ($arr as $key => $val) {
            $this->Set($key, $val, $ttl);
        }
        return true;
    }

    function clear() {
        return $this->obj->flushAll();
    }

    function keys($key) {
        return $this->obj->keys(CACHE_SYS_ID . $key);
    }

    function expire($key, $second) {
        return $this->obj->expire(CACHE_SYS_ID . $key, $second);
    }

    function sort($key, $opt) {
        return $this->obj->sort(CACHE_SYS_ID . $key, $opt);
    }

    //----------------------------------加锁方法
    /**
     * 计数器增加(加锁 默认生命周期)
     * @param $key
     * @param $step
     * @return int
     */
    public function inclock($key, $step = 1, $ttl = 0) {
        $ret = $this->obj->incrBy(CACHE_SYS_ID . $key, $step);
        if ($ttl != 0) {
            $this->obj->expire(CACHE_SYS_ID . $key, $ttl);
        }
        return $ret;
    }

    /**
     * 计数器减少（不删除 ）
     * @param $key
     * @param $step
     * @return int
     */
    public function declock($key, $step = 1) {
        //获取原来的值,如果等于0 不删除
        //$val = $this->_redis->get(CACHE_SYS_ID .$key);
        //if ($val <= 0) {
        //    return $this->delLock(CACHE_SYS_ID .$key);
        //}
        return $this->obj->decrBy(CACHE_SYS_ID . $key, $step);
    }

    /**
     * 删除锁
     * @param $key
     * @return bool
     */
    public function delLock($key) {
        $is_exist = $this->obj->exists(CACHE_SYS_ID . $key);
        if ($is_exist) {
            return $this->obj->del(CACHE_SYS_ID . $key);
        }
        return true;
    }

    /**
     * 仅当key不存在时，保存数据。
     * @param string $key
     * @param Scalar $value
     * @param Scalar $ttl 生命周期
     * @return bool 操作是否成功
     */
    public function setNx($key, $value, $ttl = 0) {
        $ret = $this->obj->setNx(CACHE_SYS_ID . $key, $value);
        if ($ret && $ttl != 0) {
            $this->obj->expire(CACHE_SYS_ID . $key, $ttl);
        }
        return $ret;
    }

    /**
     * 判断锁是否存在
     * @param type $key
     * @return boolean
     */
    public function exists($key) {
        $is_exist = $this->obj->exists(CACHE_SYS_ID . $key);
        if ($is_exist) {
            return true;
        }
        return false;
    }

    public function getSet($key, $value) {
        $res = $this->obj->getSet(CACHE_SYS_ID . $key, $value);
        return $res;
    }

    public function getKey($key) {
        $res = $this->obj->get(CACHE_SYS_ID . $key);
        return $res;
    }

    //redis LIST方法
    /**
     * 获取队列长度
     * @param $key
     * @return int
     */
    public function Llen($key) {
        $res = $this->obj->Llen(CACHE_SYS_ID . $key);
        return $res;
    }

    /**
     * 从队列的左边出队一个元素
     * @param $key
     * @param $value
     * @return int
     */
    public function Lpush($key, $value) {
        $res = $this->obj->Lpush(CACHE_SYS_ID . $key, $value);
        return $res;
    }

    /**
     * 从队列的左边出队一个元素
     * @param $key
     * @return string
     */
    public function Lpop($key) {
        $res = $this->obj->Lpop(CACHE_SYS_ID . $key);
        return $res;
    }

    /**
     * 从队列的右边入队一个元素
     * @param $key
     * @param $value
     * @return int
     */
    public function Rpush($key, $value) {
        $res = $this->obj->Rpush(CACHE_SYS_ID . $key, $value);
        return $res;
    }

    /**
     * 从队列的右边出队一个元素
     * @param $key
     * @return string
     */
    public function Rpop($key) {
        $res = $this->obj->Rpop(CACHE_SYS_ID . $key);
        return $res;
    }

    //redis 集合方法
    /**
     * 将一个或多个 member 元素及其 score 值加入到有序集 key 当中
     * @param $key
     * @param $score
     * @param $value
     * @return int
     */
    public function ZADD($key, $score, $value) {
        $res = $this->obj->ZADD(CACHE_SYS_ID . $key, $score, $value);
        return $res;
    }

    /**
     * 通过score返回有序集合指定区间内的成员
     * @param $key
     * @param $start
     * @param $end
     * @param array $options
     * @return array
     */
    public function ZRANGEBYSCORE($key, $start, $end, $options = array()) {
        $res = $this->obj->ZRANGEBYSCORE(CACHE_SYS_ID . $key, $start, $end, $options);
        return $res;
    }

    /**
     * 移除有序集合中的一个或多个成员
     * @param $key
     * @param $member1
     * @param null $member2
     * @param null $memberN
     * @return int
     */
    public function ZREM($key, $member1, $member2 = null, $memberN = null) {
        $res = $this->obj->ZREM(CACHE_SYS_ID . $key, $member1, $member2, $memberN);
        return $res;
    }
}

?>