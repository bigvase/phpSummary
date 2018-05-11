<?php
abstract class Counter {
	const	LIFETIME_INFINITE = 'infinite';//无穷
	const	LIFETIME_THISI = 'i';
	const	LIFETIME_THISHOUR = 'H';
	const	LIFETIME_TODAY = 'd';
	const	LIFETIME_THISWEEK = 'W';
	const	LIFETIME_THISMONTH = 'm';
    const   LIFETIME_THISSECOND = 'S';
	
	const	REDIS_IDENTIFY = 'CNT';

	//此处定义需要计数组的常量名，比例是密码修改次数相关的，还是错误处理的相关的计数
	const	GROUP_DEFAULT = 1;
	const	GROUP_VERIFY = 2;
	const	GROUP_SMS_COUNTER = 3;
	const	GROUP_PRJ_COUNTER = 4;
	const	GROUP_IP_COUNTER = 3;

	const	GROUP_QUEUE_COUNTER = 'Queue_push_';

	const	GROUP_IDAUTH_COUNTER = 'IdAuth_Api_';

    const	GROUP_DOPRJREPAYMENT_COUNTER = 'Do_repayment_';

    const	GROUP_FASTCASH_COUNTER = 'Do_fastcash_';

    const	GROUP_USERBONUS_ENABLE_COUNTER = 'UserBonusEnable_';

    const	GROUP_LISTEN_EVENT_COUNTER = 'listenLoginEvent_';

    const	GROUP_NEWREGHONGBAO_COUNTER = 'newRegHongBao_';

    const    GROUP_REWARD_INVEST_COUNTER = 'Reward_invest_';

    const    GROUP_USER_VIRTUAL_INVEST = 'Virtual_invest_';
    const    GROUP_RED_JUN_GOLD_COUNTER = 'RedJunGold_';

    const GROUP_CREATE_ORDER_REPAY_PLAN = 'Create_order_repay_plan';

	const GROUP_FLOAT_ORDER_REPAY_PLAN = 'Float_order_repay_plan';

	protected static $redis;
	protected $instance, $redisPrefix;
	protected $period = array(
		self::LIFETIME_INFINITE => -1,
        self::LIFETIME_THISSECOND =>1,
		self::LIFETIME_THISI => 60,
		self::LIFETIME_THISHOUR => 3600,
		self::LIFETIME_TODAY => 86400,
		self::LIFETIME_THISWEEK => 604800,
		self::LIFETIME_THISMONTH => 2592000
		);
	protected $limitHours;

	/**
	 *@return string 返回实际存储时的数据结构
	 */
	abstract protected function redisType();
	
	private static function genRedisPrefix($prefix, $lifeTime) {
		$suffix = '';
		if ($lifeTime != self::LIFETIME_INFINITE) {
			$return = 'Y';
			if ($lifeTime == self::LIFETIME_THISWEEK)	$return .= self::LIFETIME_THISWEEK;
			else {
				$array = array(self::LIFETIME_THISMONTH, self::LIFETIME_TODAY, self::LIFETIME_THISHOUR, self::LIFETIME_THISI);
				foreach ($array as $str) {
					$return .= $str;
					if ($lifeTime == $str) break;
				}
			}
			$suffix .= '_'. date($return);
		}
		return self::REDIS_IDENTIFY. $prefix . $suffix;
	}
	
	private function __construct($prefix, $lifeTime = self::LIFETIME_INFINITE) {
		if (!$prefix)	throw new Exception('$prefix参数设值有误');
		if (!isset($this->period[$lifeTime]))	throw new Exception('$lifeTime参数设值有误');
		$redisType = $this->redisType();
		Import("libs.Cache.".$redisType, ADDON_PATH);
		$this->redisPrefix = self::genRedisPrefix($prefix, $lifeTime);
		$this->instance = new $redisType($this->redisPrefix, $this->period[$lifeTime]);
		$this->instance->serviceName = 'redisCounter';	//name in ENV_FILE
		$this->limitHours = $this->period[$lifeTime] / 3600;
	}

	public function getLimitHours(){
		return $this->limitHours;
	}
	
	public function getPrefix(){
		return $this->redisPrefix;
	}
	
	/**
	 * 初始化一个Counter实例
	 * @param const $prefix
	 * @return Counter
	 */
	public static function init($prefix, $lifeTime = self::LIFETIME_INFINITE) {
		$className = get_called_class();
		self::$redis = new $className($prefix, $lifeTime);
		return self::$redis;
	}

	/**
	 * 检查该计数器是否在Redis中实际存在
	 */
	public static function exists($prefix) {
		self::init($prefix);
		return self::$redis->instance->exists();
	}

	/**
	 * 在Redis中删除该计数器
	 */
	public static function del($prefix) {
		self::init($prefix);
		return self::$redis->instance->del();
	}

}
?>