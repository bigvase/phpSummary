<?php
namespace Addons\Libs\Cache;
/**
 * php  redis 工具类库
 * 环境：php5.4 - php.5.6  redis2.8.0 - redis 3.0.4 mysql5.5 - 5.7
 * 初步实现了在上述环境下的读写分离
 * 部分读方法集成了当缓存没有命中时，自动读取mysql并更新（如无需此类集成，可以$model -> getRedis() -> hget()来绕过）
 * Created by PhpStorm.
 * User: linhao
 * Date: 2017/3/13
 * Time: 11:27
 */
class CacheRedis
{
    public static $instance = NULL;
    public static $linkHandle = array();
    private $conf;
    private $dbindex;
    private $pre;
    public function __construct($config)
    {
        if(!extension_loaded('redis')) {
            throw_exception("缺失redis扩展");
        }
        if(empty($config)) {
            $this->conf = C("REDIS_CONFIG");
        }else {
            $this->conf = $config;
        }
        $this->pre = $this->conf['PREFIX'] ? $this->conf['PREFIX'] : "redis_";
    }
    /**
     * 获得类实例
     * @param string $configs
     * @return object
     */
    static function getInstance($configs)
    {
        if (!self::$instance) {
            self::$instance = new self($configs);
        }
        return self::$instance;
    }


    /**
     * redis实例
     * 目前只是粗糙的实现了多实例下的使用
     * <php7.0 无法使用phpredis3.0扩展，最高只能支持redis3.0
     * 如果可以，最好能使用 phpredis 3.0 + php7.0 将支持 redis3.2的cluster集群
     * @param string $tag  master/slave
     * @return object     返回redis实例
     */
    public function getRedis($tag='master'){
        $safe_lock = S("redis_safe_lock");
		//避免在无法连接时导致的连锁问题
        if($safe_lock === 0) return false;
        if(!empty(self::$linkHandle[$tag])){
            return self::$linkHandle[$tag];
        }
        $key = uniqid();
        $redis_arr  = $this->conf[$tag];
        $arr_nums   = count($this->conf[$tag]);
        if($tag == "slave" && $arr_nums == 0) {
            $tag        = 'master';
            $redis_arr  = $this->conf[$tag];
        }
        $arr_index = $this->getHostByHash($key,count($this->conf[$tag])); //获得相应主机的数组下标
        $obj = new \Redis();
        //设置长连接，10秒超时
        $res = $obj->pconnect($redis_arr[$arr_index]['host'],$redis_arr[$arr_index]['port'],10);
        if(!$res) {
            S("redis_safe_lock",0,60);
            throw_exception("redis连接超时");
        }else {
            S("redis_safe_lock",1,60);
        }
        $redis_arr[$arr_index]['auth'] ? ($obj->auth($redis_arr[$arr_index]['auth'])) : '';
        self::$linkHandle[$tag] = $obj;
        return $obj;
    }

    /**
     * 随机取出主机
     * @param $key
     * @param $n 主机数
     * @return string
     */
    private function getHostByHash($key,$n){
        if($n<2) return 0;
        $id = sprintf("%u", crc32($key));
        $m = base_convert( intval(fmod($id, $n)), 10, $n);
        return $m{0};
    }

    /**
     * 关闭连接
     * pconnect 连接是无法关闭的
     *
     * @param int $flag 关闭选择 0:关闭 Master 1:关闭 Slave 2:关闭所有
     * @return boolean
     */
    public function close($flag = 2){
        switch($flag){
            // 关闭 Master
            case 0:
                foreach (self::$linkHandle['master'] as $var){
                    $var->close();
                }
                break;
            // 关闭 Slave
            case 1:
                foreach (self::$linkHandle['slave'] as $var){
                    $var->close();
                }
                break;
            // 关闭所有
            case 1:
                $this->close(0);
                $this->close(1);
                break;
        }
        return true;
    }

    /**[KEY]
     * 是否存在指定key
     * @param $key
     * @return bool
     */
    public function exists($key){
        $handle = $this->getRedis();
        if(!$handle) return false;
        return $handle->exists($this->pre.$key);
    }

    /**[KEY]
     * 设置一个key的过期时间
     * @param  $key
     * @param  $exp    过期时间
     * @return bool
     */
    public function setExpire($key,$exp){
        $handle = $this->getRedis();
        if(!$handle) return false;
        $handle->expire($this->pre.$key,$exp);
    }

    /**[KEY]
     * KEYS pattern
     * 查找所有匹配给定的模式的键
     * @param $is_key   默认是一个非正则表达式，使用模糊查询
     * @param $key
     * @return string
     */
    public function keys($key,$is_key = true){
        if ($is_key) {
            return $this->getRedis('slave')->keys("*$key*");
        }
        return $this->getRedis('slave')->keys("$key");
    }


    /**
     * 开启redis 事务
     */
    public function multi(){
        $handle = $this->getRedis();
        if(!$handle) return false;
        $handle->multi();
    }

    /**
     * redis pipeline 管道
     * 将redis的一串请求打包，有效解决网络传输的损耗，大幅度提高速度，约10倍
     */
    public function pipeline(){
        $handle = $this->getRedis();
        if(!$handle) return false;
        $handle->pipeline();
    }

    /**
     * 提交 multi 或者 pipeline
     */
    public function exec(){
        $handle = $this->getRedis();
        if(!$handle) return false;
        return $handle->exec();
    }
//------------------------数据结构--------------------------------------------------------

    /**[STRING]
     * 返回key的value。
     * @param $key
     * @return string
     */
    public function get($key){
        $handle = $this->getRedis("slave");
        if(!$handle) return false;
        return $handle -> get($this->pre.$key);
    }


    /**获得hash table的某一行
     * @param $key
     * @param $hashKey
     * @return array|string
     */
    public function hGet($key,$hashKey) {
        $handle = $this->getRedis("slave");
        if(!$handle) return false;
        if($handle -> hexists($this->pre.$key,$hashKey)){
            $res =  $handle -> hGet($this->pre.$key,$hashKey);
        }else {
            $redis_field = C("REDIS_FIELD");
            $db_pre      = C("DB_PREFIX");
            $redis_table = C("REDIS_TABLE");
            $res = false;
            if(in_array($db_pre.$key,$redis_table)) {
                $res = M($key) -> field($redis_field[$db_pre.$key]) -> find($hashKey);
                unset($res['id']);
                if($res) {
                    $handle1 = $this->getRedis();
                    if(!$handle1) return false;
                    $handle1 -> hset($this->pre.$key,$hashKey,json_encode($res));
                }
            }
        }
        return $res;
    }

    /**
     * 返回指定hash table的长度
     * @param $key
     * @return int
     */
    public function hLen($key) {
        $handle = $this->getRedis("slave");
        if(!$handle) return false;
        return $handle->hLen($this->pre.$key);
    }
    /**
     * 返回指定的hash table
     * @param $key
     * @return array
     */
    public function hGetAll($key){
        $model = $this->getRedis("slave");
        if($model -> exists($this->pre.$key)){
            $res =  $model -> hGetAll($this->pre.$key);
        }else {
            $redis_field    = C("REDIS_FIELD");
            $db_pre         = C("DB_PREFIX");
            $redis_table    = C("REDIS_TABLE");
            $res = false;
            if(in_array($db_pre.$key,$redis_table)) {
                $list           = M($key) -> field($redis_field[$db_pre.$key]) -> select();
                $map            = [];
                foreach($list as $i => $j) {
                    $tmp = $j['id'];
                    unset($j['id']);
                    $map[$tmp] = json_encode($j);
                }
                $res = $map;
                if(count($res)) {
                    $this->getRedis()->hmset($this->pre.$key,$res);
                }
            }
        }
        return $res;
    }



    /**
     * redis 字符串（String） 类型
     * 将key和value对应。
     * 特殊：如果key已经存在了，它会被覆盖，而不管它是什么类型。
     * @param $key
     * @param $value
     * @return bool
     */
    public function set($key,$value){
        $handle = $this->getRedis();
        if(!$handle) return false;
        $handle -> set($this->pre.$key,$value);
    }
    /**
     * 设置key对应字符串value，并且设置key在给定的seconds时间之后超时过期
     * @param  $key
     * @param  $value
     * @param  $exp
     * @return bool|string
     */
    public function setex($key,$exp = 0,$value){
        $handle = $this->getRedis();
        if(!$handle) return false;
        $handle -> setex($this->pre.$key,$exp,$value);
    }

    /**
     * 批量填充HASH表。不是字符串类型的VALUE，自动转换成字符串类型。使用标准的值。NULL值将被储存为一个空的字符串。
     * 可以批量添加更新 value,key 不存在将创建，存在则更新值
     * @param  $key
     * @param  $fieldArr
     * @return string
     * 如果命令执行成功，返回OK。
     * 当key不是哈希表(hash)类型时，返回一个错误。
     */
    public function hMSet($key,$fieldArr){
        $handle = $this->getRedis();
        if(!$handle) return false;
        return $handle -> hmset($this->pre.$key,$fieldArr);
    }
    /**
     * 设置 key 指定的哈希集中指定字段的值。
     * 如果 key 指定的哈希集不存在，会创建一个新的哈希集并与 key 关联。如果字段在哈希集中存在，则更新
     * @param $key
     * @param $field_name
     * @param $field_value
     * @return string  新key return 1; 已存在并更新  return 0;
     */
    public function hSet($key,$field_name,$field_value){
        $handle = $this->getRedis();
        if(!$handle) return false;
        return $handle->hset($this->pre.$key,$field_name,$field_value);
    }

    /**
     * 批量的添加多个key 到redis
     * @param $fieldArr
     */
    public function mSetnx($fieldArr){
        $this->getRedis()->mSetnx($fieldArr);
    }

    /**
     * 向已存在于redis里的Hash 添加多个新的字段及值
     *
     * @param  $key            redis 已存在的key
     * @param  $field_arr    kv形数组
     */
    public function hAddFields($key,$field_arr){
        foreach ($field_arr as $k=>$v){
            $this->hAddFieldOne($key, $k, $v);
        }
    }

    /**
     * 向已存在于redis里的Hash 添加一个新的字段及值
     * @param  $key
     * @param  $field_name
     * @param  $field_value
     * @return bool
     */
    public function hAddFieldOne($key,$field_name,$field_value){
        return $this->getRedis()->hsetnx($this->pre.$key,$field_name,$field_value);
    }

    /**
     * 向Hash里添加多个新的字段或修改一个已存在字段的值
     * @param $key
     * @param $field_arr
     */
    public function hSetsValues($key,$field_arr){
        foreach ($field_arr as $k=>$v){
            $this->hSetsValueOne($key, $k, $v);
        }
    }
    /**
     * 向Hash里添加多个新的字段或修改一个已存在字段的值
     * @param  $key
     * @param  $field_name
     * @param  $field_value
     * @return boolean
     * 1 if value didn't exist and was added successfully,
     * 0 if the value was already present and was replaced, FALSE if there was an error.
     */
    public function hSetsValueOne($key,$field_name,$field_value){
        return $this->getRedis()->hset($this->pre.$key,$field_name,$field_value);
    }

    #region 删除
    /**
     *  删除哈希表key中的多个指定域，不存在的域将被忽略。
     * @param $key
     * @param $field_arr
     */
    public function hDel($key,$field_arr){
        foreach ($field_arr as $var){
            $this->hDelOne($this->pre.$key,$var);
        }
    }

    /**
     * 删除哈希表key中的一个指定域，不存在的域将被忽略。
     *
     * @param $key
     * @param $field
     * @return BOOL TRUE in case of success, FALSE in case of failure
     */
    public function hDelOne($key,$field){
        return $this->getRedis()->hdel($this->pre.$key,$field);
    }

    /**
     * 删除一个或多个key
     * @param $keys
     */
    public function delKey($keys){
        if(is_array($keys)){
            foreach ($keys as $key){
                $this->getRedis()->del($this->pre.$key);
            }
        }else {
            $this->getRedis()->del($this->pre.$keys);
        }
    }
    #endregion

    //设置一个成员会过期的集合
    public function setSetExp($key,$member,$exp){
        $handle = $this->getRedis();
        if(!$handle) return false;
        $score = $exp + time();
        return $handle->zadd($this->pre.$key,$score,$member);
    }
    //是否是成员会过期的集合中的一员
    public function isMemberOfSetExp($key,$member){
        $handle = $this->getRedis();
        if(!$handle) return false;
        $score = $handle->zscore($this->pre.$key,$member);
        if(!$score ) {
            return false;
        }
        if($score < time()) {
            $handle->zrem($this->pre.$key,$member);
            return false;
        }
        return true;
    }
}