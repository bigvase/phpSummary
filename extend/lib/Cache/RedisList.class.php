<?php
class RedisList {
    private $key = '';
    private $prefix = '';
    private $expire = 3600;
    private $handler = null;
    
    static function getInstance($redisKey,$expire=3600){
    	   return new self($redisKey, $expire);
    }
    
    function __construct($redisKey, $expire, $redis='default'){
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
    
    /**
     * 删除左边$count位置后面的包含$value的list
     * @param unknown $count
     * @param unknown $value
     */
    function lRemove($count,$value){
        return $this->handler->lRemove($this->key,$value,$count);
    }
    
    //移除过期时间
    function persist(){
        return $this->handler->persist($this->key);
    }
    
    /**
     * 设置过期时间
     * @param string $expire
     */
    function setExpire($expire=''){
        $expire =  isset($expire)?  $expire  :   C('DATA_CACHE_TIME');
        return $this->handler->expire($this->key, $expire);
    }
    
    /**
     * 删除
     */
    function delete(){
    	return $this->handler->delete($this->key);
    }
    
    /**
     * 添加一个字符串值到LIST容器的顶部（左侧），如果KEY不存在，则创建一个LIST容器，如果KEY存在并且不是一个LIST容器，那么返回FLASE
     * 返回LIST容器最新的长度，如果ADD成功。失败则返回FALSE。
     * @param unknown $value
     */
    function lPush($value){
        $value = serialize($value);
        return $this->handler->lPush($this->key,$value);
    }
    
    /**
     * 由列表尾部添加字符串值。如果不存在该键则创建该列表。如果该键存在，而且不是一个列表，返回FALSE。
     */
    function rPush($value){
        $value = serialize($value);
        return $this->handler->rPush($this->key,$value);
    }
    
    /**
     * 添加一个VALUE到LIST容器的顶部（左侧）如果这个LIST存在的话。
     * 如果ADD成功， 返回LIST容器最新的长度。失败则返回FALSE。
     * @param unknown $value
     */
    function lPushx($value){
        $value = serialize($value);
        return $this->handler->lPushx($this->key,$value);
    }
    
    /**
     * 
     * @param unknown $value
     */
    function rPushx($value){
        $value = serialize($value);
        return $this->handler->rPushx($this->key,$value);
    }
    
    /**
     * 返回和移除列表的第一个元素
     */
    function lPop(){
    	return $this->handler->lPop($this->key);
    }
    
    /**
     * 返回和移除列表的最后一个元素
     */
    function rPop(){
        return $this->handler->rPop($this->key);
    }
    
    /**
     * 如果KEY存在并且为LIST且有元素，那么返回KEY从$start到$end的数据。
     */
    function lRange($start, $end){
        return $this->handler->lRange($this->key, $start, $end);
    }
    
    /**
     * 如果KEY存在并且为LIST且有元素，那么返回KEY的长度，为空或者不存在返回0。
     */
    function lSize(){
    	return $this->handler->lSize($this->key);
    }
    
    /**
     * 返回指定键存储在列表中指定的元素。 0第一个元素，1第二个… -1最后一个元素，-2的倒数第二…错误的索引或键不指向列表则返回FALSE。
     * @param unknown $index
     */
    function lGet($index){
        $index = (int) $index;
        return $this->handler->lGet($this->key,$index);
    }
    
    /**
     * 为列表指定的索引赋新的值,若不存在该索引返回false.
     * @param unknown $index
     * @param unknown $value
     */
    function lSet($index,$value){
        $value = serialize($value);
        $index = (int) $index;
        return $this->handler->lSet($this->key,$index,$value);
    }
    
    /**
     * 返回在该区域中的指定键列表中开始到结束存储的指定元素，lGetRange(key, start, end)。0第一个元素，1第二个元素… -1最后一个元素，-2的倒数第二…
     * @param unknown $startIndex
     * @param unknown $endIndex
     */
    function lgetrange($startIndex,$endIndex){
        $startIndex = (int) $startIndex;
        $endIndex = (int) $endIndex;
    	return $this->handler->lGetRange($this->key,$startIndex,$endIndex);
    }
    
}