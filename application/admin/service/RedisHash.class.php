<?php
class RedisHash {
	private $key = '';
	private $prefix = '';
	private $handler = null;
	private $expire = 3600;
	
	public function __construct($redisKey, $expire=0, $redis='default') {
		if ( !extension_loaded('redis') ) {
            throw_exception(L('_NOT_SUPPERT_').':redis');
        }
		$redis_config = C('REDIS');
		$curr_redis_config = $redis_config[$redis];
		if (!isset($redis_config['default'])) {
			throw_exception('请在配置文件中增加redis默认配置');
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
            
        $this->expire =  isset($expire)?  $expire  :   C('DATA_CACHE_TIME');
        $this->prefix =  C('DATA_CACHE_PREFIX');

        try {
            $this->handler = new Redis;
            $this->key = $this->prefix . $redisKey;
            $options['timeout'] === false ?
                $this->handler->connect($options['host'], $options['port']) :
                $this->handler->connect($options['host'], $options['port'], $options['timeout']);
            if ($curr_redis_config['password']) {
                $rel = $this->handler->auth($curr_redis_config['password']);
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
	
	public function count() {
		return $this->handler->hLen($this->key);
	}

	/**
	 * 向Hash中保存数据, 如果key已经存在，返回false .不设置过期时间
	 * @param string $key
	 * @param Scalar $value
	 * @return bool 操作是否成功
	 */
	function noExpireSet($key, $value){
		return $this->handler->hSet($this->key, $key, $value);
	}
	
	/**
	 * 向Hash中保存数据, 如果key已经存在，返回false
	 * @param string $key
	 * @param Scalar $value
	 * @return bool 操作是否成功
	 */
	public function set($key, $value) {
		$ret = $this->handler->hSet($this->key, $key, $value);
		if ($ret && $this->expire) $this->expire($this->expire);
		return $ret;
	}
	/**
	 * 仅当key不存在时，向Hash中保存数据。
	 * @param string $key
	 * @param Scalar $value
	 * @return bool 操作是否成功
	 */
	public function setNx($key, $value) {
		$ret = $this->handler->hSetNx($this->key, $key, $value);
		if ($ret && $this->expire) $this->expire($this->expire);
		return $ret;
	}
	/**
	 * 向Hash中保存数据
	 * @param array $data key=>value模式的数组
	 * @return bool 操作是否成功
	 */
	public function mSet(array $data) {
		$ret = $this->handler->hMSet($this->key, $data);
		if ($ret && $this->expire) $this->expire($this->expire);
		return $ret;
	}
	/**
	 * 从Hash中取出指定Key的值
	 * @param Mixed $item 需要从Hash获取的$item，可以是多个value或者一个数组
	 * @return array key=>value模式的数组
	 */
	public function get($key) {
		if (is_array($key)) {
			return $this->handler->hMGet($this->key, $key);
		} else {
			return $this->handler->hGet($this->key, $key);
		}
	}
	/**
	 * 返回Hash表中的所有内容
	 * @return array $key => $value
	 */
	public function getAll() {
		return $this->handler->hGetAll($this->key);
	}
	
	/**
	 * 返回Hash表的Key
	 * @return array Hash的Key组成的数组，如果Hash不存在，则返回array()
	 */
	public function keys() {
		return $this->handler->hKeys($this->key);
	}
	/**
	 * 返回Hash表的Value
	 * @return array Hash的Value组成的数组，如果Hash不存在，则返回array()
	 */
	public function values() {
		return $this->handler->hVals($this->key);
	}
	
	public function incr($key, $step = 1) {
		$ret = $this->handler->hIncrBy($this->key, $key, $step);
		if ($ret == $step && $this->expire){
			$this->expire($this->expire);
		}
		return $ret;
	}
	
	public function hasKey($key) {
		return $this->handler->hExists($this->key, $key);
	}
	
	public function delKey($key) {
		$ret = $this->handler->hDel($this->key, $key);
		return $ret;
	}
	
	public function expire($expire){
		$this->handler->expire($this->key, $expire);
	}
}