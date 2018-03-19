<?php
class RedisSimply {
	private $key = '';
	private $prefix = '';
	
	protected static $redis;
	
	public static function getRedis(){
		$className = get_called_class();
		self::$redis = new $className();
		return self::$redis->handler;
	}
	
	public function __construct($redisKey='', $expire='') {
		if ( !extension_loaded('redis') ) {
			throw_exception(L('_NOT_SUPPERT_').':redis');
		}
		$options = array (
				'host'          => C('REDIS_HOST') ? C('REDIS_HOST') : '127.0.0.1',
				'port'          => C('REDIS_PORT') ? C('REDIS_PORT') : 6379,
				'timeout'       => C('DATA_CACHE_TIMEOUT') ? C('DATA_CACHE_TIMEOUT') : false,
		);
	
		$this->expire =  isset($expire)?  $expire  :   C('DATA_CACHE_TIME');
		$this->prefix =  C('DATA_CACHE_PREFIX').$redisKey;
        try {
            $this->handler = new Redis;
            $options['timeout'] === false ?
                $this->handler->connect($options['host'], $options['port']) :
                $this->handler->connect($options['host'], $options['port'], $options['timeout']);

            if (C('REDIS_PWD')) {
                $rel = $this->handler->auth(C('REDIS_PWD'));
                if (!$rel) {
                    throw_exception("redis 认证密码错误");
                }
            }

        } catch (RedisException $e) {
            \Addons\Libs\Log\Logger::err($e->getMessage() . "[host:{$options['host']};port:{$options['port']}]",
                "Redis");
            throw $e;
        }
	}
	
	function select($channel){
		return $this->handler->select($channel);
	}
	
	public function strlen($key) {
		return $this->handler->strlen($this->prefix.$key);
	}
	
	/**
	 * 向Hash中保存数据, 如果key已经存在，返回false
	 * @param string $key
	 * @param Scalar $value
	 * @return bool 操作是否成功
	 */
	public function set($key, $value) {
		$ret = $this->handler->set($this->prefix.$key, $value);
		if ($ret && $this->expire) $this->expire($this->prefix.$key, $this->expire);
		return $ret;
	}

    public function setArray($key, $arr){
        $value = serialize($arr);
        return $this->set($key, $value);
    }

    public function getArray($key){
        $ret = $this->get($key);
        return unserialize($ret);
    }

	/**
	 * 仅当key不存在时，向Hash中保存数据。
	 * @param string $key
	 * @param Scalar $value
	 * @return bool 操作是否成功
	 */
	public function setNx($key, $value) {
		$ret = $this->handler->setNx($this->prefix.$key, $value);
		if ($ret && $this->expire) $this->expire($this->prefix.$key, $this->expire);
		return $ret;
	}
	
	public function setEx($key, $value){
		$ret = $this->handler->setex($this->prefix.$key, $this->expire, $value);
		return $ret;
	}
	
	/**
	 * 向Hash中保存数据
	 * @param array $data key=>value模式的数组
	 * @return bool 操作是否成功
	 */
	public function mset(array $data) {
		$tdata = array();
		foreach($data as $key=>$value){
			$tdata[$this->prefix.$key] = $value;
		}
		$ret = $this->handler->mset($tdata);
		if ($ret && $this->expire) {
            foreach($data as $key=>$value) {
                $this->expire($this->prefix.$key,$this->expire);
            }
        }
		return $ret;
	}
	/**
	 * 从Hash中取出指定Key的值
	 * @param Mixed $item 需要从Hash获取的$item，可以是多个value或者一个数组
	 * @return array key=>value模式的数组
	 */
	public function get($key) {
		if (is_array($key)) {
			foreach($key as $akey=>$value){
				$tdata[$this->prefix.$akey] = $value;
			}
			return $this->handler->mget($tdata);
		} else {
			return $this->handler->get($this->prefix.$key);
		}
	}
	
	public function incr($key, $step = 1) {
		$ret = $this->handler->incrBy($this->prefix.$key, $step);
				if ($ret == $step && $this->expire){
					$this->expire($this->prefix.$key,$this->expire);
				}
		return $ret;
	}
	
	public function decr($key, $step = 1) {
		$ret = $this->handler->decrBy($this->prefix.$key, $step);
				if ($ret == $step &&  $this->expire){
					$this->expire($this->prefix.$key,$this->expire);
				}
		return $ret;
	}
	
	public function keys($key) {
		return $this->handler->keys($this->prefix.$key);
	}
	
	public function values() {
		return $this->handler->hVals($this->key);
	}
	
	public function hasKey($key) {
		return $this->handler->exists($this->prefix.$key);
	}
	
	public function del($key) {
		$ret = $this->handler->del($this->prefix.$key);
		return $ret;
	}
	
	public function expire($key, $expire){
		if($expire) $this->handler->expire($key, $expire);
	}

    public function ttl($key) {
        return $this->handler->ttl($this->prefix.$key);
    }
}