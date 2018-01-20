<?php
/**
 * Created by PhpStorm.
 * User: 001181
 * Date: 2015/10/15
 * Time: 10:00
 */
namespace Addons\Libs\Lock;

use Addons\Libs\Cache\Redis;

class Lock
{
    private $_redis;
    private $_prefix = 'lock_';
    private $_expire_time;
    private $_key;
    private $_step;

    public function __construct()
    {
        $this->_redis = Redis::getInstance("default");
        $this->_expire_time = 60;
    }

    /**
     * 计数器增加(加锁)
     * @param $key
     * @param $step
     * @return int
     */
    public function lock($key, $step = 1)
    {
        $this->setKey($key);
        $this->setStep($step);

        $ret = $this->_redis->incrBy($this->_key, $this->_step);
        $this->_redis->expire($this->_key, $this->_expire_time);

        return $ret;


    }

    /**
     * 计数器减少
     * @param $key
     * @param $step
     * @return int
     */
    public function unlock($key, $step = 1)
    {
        $this->setKey($key);
        $this->setStep($step);
        //获取原来的值,如果等于0直接删除
        $val = $this->_redis->get($this->_key);
        if ($val <= 0) {
            return $this->delLock($this->_key);
        }

        return $this->_redis->decrBy($this->_key, $this->_step);
    }

    /**
     * 获取当前的key的值
     * @param $key
     * @return int
     */
    public function getLockCount($key)
    {
        $this->setKey($key);
        //获取原来的值,如果等于0直接删除
        $val = (int)$this->_redis->get($this->_key);
        if ($val <= 0) {
            return 0;
        }

        return $val;
    }

    /**
     * 删除锁
     * @param $key
     * @return bool
     */
    public function delLock($key)
    {
        $this->setKey($key);
        $is_exist = $this->_redis->exists($this->_key);
        if ($is_exist) {
            return $this->_redis->del($this->_key);
        }

        return true;
    }

    private function setKey($key)
    {
        if (empty($key)) {
            throw_exception("key不能为空");
        }
        $this->_key = $this->_prefix . $key;
    }

    private function setStep($step)
    {
        $step = (int)$step;
        if ($step < 1) {
            throw_exception("步长最小值为1");
        }
        $this->_step = $step;
    }
}