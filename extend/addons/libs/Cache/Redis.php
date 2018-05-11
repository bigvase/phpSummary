<?php
/**
 * 1，使用时指定用哪个redis实例，同类数据放在一起，做到数据分离，分散主redis服务压力，保证关键业务不受一般业务影响。
 * 2，不再自己重写redis操作方法，而是使用redis原生方法，支持所有redis方法。不明白的查文档就好。文档推荐 http://redisdoc.com
 * 3，所有操作方法都有代码提示（需要IDE支持）见图4
 * 4，指定redis实例时，也需要指定database，明确自己数据存在哪里。
 * 5，保存的数据自动序列化，数组、对象之类的数据不用自己格式化。取出自动反序列化。
 * 6，同样配置的redis是单例模式，一次请求，多处使用，只会建立一次连接。
 */
namespace Addons\Libs\Cache;

class Redis
{

    private static $serializer;
    private static $redis_connect = array();

    /**
     * 单例模式 返回当前Server Name的redis连接
     * @param string $server_name
     * @param bool $serializer 要保存的数据是否序列化 这里保持默认值null的话 根据配置文件里的配置来决定是否序列化
     * @return \Redis
     */
    public static function getInstance($server_name = 'default',$serializer = null)
    {
        self::$serializer = $serializer;
        $redis = self::getRedisConnectByName($server_name);
        /* @var $redis \Redis() */
        return $redis;
    }

    /**
     * 保存的数据会被序列化，incr等某些方法不能用这种方式
     * @param string $server_name
     * @return \Redis
     */
    public static function getSerializerInstance($server_name = 'default')
    {
        return self::getInstance($server_name,true);
    }

    /**
     * 保存的数据不会被序列化 保存数组对象等数据需要自己序列化
     * @param string $server_name
     * @return \Redis
     */
    public static function getUnSerializerInstance($server_name = 'default')
    {
        return self::getInstance($server_name,false);
    }

    /**
     * 根据Server Name获取对应的配置
     * 没定义的Server Name返回默认Default的配置
     * 定义的Server Name下不完整的子项使用Default的对应子项
     * @param string $server_name
     * @return mixed
     */
    private static function getRedisConfigByName($server_name = 'default')
    {
        if (!extension_loaded('redis')) {
            throw new \think\Exception('_NOT_SUPPERT_:' . ':redis');
        }
        $redis_config = config('REDIS');
        if (!isset($redis_config['default'])) {
            throw new \think\Exception('请在配置文件中增加redis默认配置');
        }
        if (!isset($redis_config[$server_name])) {
            return $redis_config['default'];
        }
        $curr_redis_config = $redis_config[$server_name];

        foreach ($redis_config['default'] as $key => $val) {
            if (!isset($curr_redis_config[$key])) {
                $curr_redis_config[$key] = $val;
            }
        }
        return $curr_redis_config;
    }

    /**
     * 根据Server Name返回对应配置的Redis连接
     * @param $server_name
     * @return \Redis
     */
    private static function getRedisConnectByName($server_name)
    {
        if (isset(self::$redis_connect[$server_name])) {
            return self::$redis_connect[$server_name];
        }

        $redis_config = self::getRedisConfigByName($server_name);

        try {
            $redis = new \Redis();
            $connect_type = 'connect';
            if ($redis_config['pconnect']) {
                $connect_type = 'pconnect';
            }
            $redis->$connect_type(
                $redis_config['host'],
                $redis_config['port'],
                $redis_config['timeout']
            );
            if ($redis_config['password'] && !$redis->auth($redis_config['password'])) {
                throw new \think\Exception("redis 认证密码错误");
            }
            if ($redis_config['database']) {
                $redis->select($redis_config['database']);
            }
            if ($redis_config['prefix']) {
                $redis->setOption(\Redis::OPT_PREFIX, $server_name . ':' . $redis_config['prefix'] . ':');
            }
            $serializer = self::$serializer === null ? $redis_config['serializer'] : self::$serializer;
            if ($serializer) {
                $redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
            }
        } catch (\RedisException $e) {
            //die('连接redis失败'.$e->getMessage());
            \Addons\Libs\Log\Logger::err($e->getMessage() . "[host:{$redis_config['host']};port:{$redis_config['port']}]","Redis");
            throw $e;
        }

        self::$redis_connect[$server_name] = $redis;
        return $redis;
    }

}