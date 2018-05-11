<?php
Import("libs.Counter.Counter", ADDON_PATH);
class MultiCounter extends Counter{
	protected function redisType(){
		return 'RedisHash';
	}

	/**
	 * 为了让IDE自动提示
	 * @param const $prefix //只能是Counter定义的GROUP常量
	 * @return MultiKey
	 */
	public static function init($prefix, $lifeTime = self::LIFETIME_INFINITE) {
		return parent::init($prefix, $lifeTime);
	}

	/**
	 * @return int	当前值
	 */
	public function incr($childKey, $step = 1) {//使用该函数 过期时间会失效
		//if (!is_numeric($step))	throw new \Exception('计数器的步进必须是数字');
		return $this->instance->incr($childKey, $step);
	}

	/**
	 * @return int	当前值
	 */
	public function desc($childKey, $step = 1) {//使用该函数 过期时间会失效
		//if (!is_numeric($step))	throw new Exception('计数器的减值必须是数字');
		return $this->instance->incr($childKey, -$step);
	}

	/**
	 * @return int	当前值
	 */
	public function get($childKey) {
		return $this->instance->get($childKey);
	}

	/**
	 * @return mixed 返回值:<br>
	 *	1: $childKey不存在且操作成功<br>
	 *	0: $childKey存在且操作成功<br>
	 *	FALSE:操作失败
	 */
	public function set($childKey, $num) {
		if (!is_numeric($num))	throw new \Exception($num.'参数必须是数字');
		return $this->instance->set($childKey, $num);
	}

	public function mSet(array $array) {
		return $this->instance->mSet($array);
	}

	/**
	 * 返回一个包含该计数器所有key的数组
	 * @return array
	 */
	public function allKeys() {
		return $this->instance->keys();
	}

	/**
	 * 返回一个包含该计数器所有$key => $value的数组
	 * @return array
	 */
	public function getAll() {
		return $this->instance->getAll();
	}

	/**
	 * @return int childKey的数量
	 */
	public function size() {
		return $this->instance->count();
	}

	/**
	 * @return boolean
	 */
	public function hasKey($childKey) {
		return $this->instance->hasKey($childKey);
	}

	/**
	 * @return boolean
	 */
	public function delKey($childKey) {
		return $this->instance->delKey($childKey);
	}

}