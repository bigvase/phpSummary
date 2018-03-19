<?php
class RedisSet {
    private $key = '';
    private $prefix = '';
    private $expire = 3600;
    private $handler = null;
    
    static function getInstance($redisKey,$expire=3600){ 
    	   return new self($redisKey, $expire);
    }
    
    function __construct($redisKey, $expire){
        if ( !extension_loaded('redis') ) {
            throw_exception(L('_NOT_SUPPERT_').':redis');
        }
        $options = array (
                'host'          => C('REDIS_HOST') ? C('REDIS_HOST') : '127.0.0.1',
                'port'          => C('REDIS_PORT') ? C('REDIS_PORT') : 6379,
                'timeout'       => C('DATA_CACHE_TIMEOUT') ? C('DATA_CACHE_TIMEOUT') : false,
        );
        
        $this->expire =  isset($expire)?  $expire  :   C('DATA_CACHE_TIME');
        $this->prefix =  C('DATA_CACHE_PREFIX');

        try {
            $this->handler = new Redis;
            $this->key = $this->prefix . $redisKey;
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
     * 为一个Key添加一个值。如果这个值已经在这个Key中，则返回FALSE。
     * @param unknown $value
     * @return boolean
     */
    function sAdd($value){
        if(!$value) return false;
        $value = serialize($value);
        return $this->handler->sAdd($this->key,$value);
    }
    
    /**
     * 删除Key中指定的value值
     * @param unknown $value
     * @return boolean
     */
    function sRemove($value){
        if(!$value) return false;
        $value = serialize($value);
        return $this->handler->sRemove($this->key,$value);
    }
    
//     /**
//      * 返回集合中存储值的数量
//      */
//     function sSize(){
//         return $this->handler->sSize($this->key);
//     }
    
    /**
     * 获得该集合的大小
     * @return int 集合的大小
     */
    public function count() {
    	return $this->handler->sCard($this->key);
    }
    
    /**
     * 名称为key的集合中查找是否有value元素，有ture 没有 false
     * @param unknown $value
     */
    function sContains($value){
        if(!$value) return false;
        $value = serialize($value);
        return $this->handler->sContains($this->key,$value);
    }
    
    /**
     * 返回SET集合中的所有元素。
     */
    function sMembers(){
    	return $this->handler->sMembers($this->key);
    }
    
}