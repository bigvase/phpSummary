<?php
/**
 * Created by PhpStorm.
 * User: 000429
 * Date: 2014/11/12
 * Time: 18:49
 */
class RedisSortSet {
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
    function zAdd($value, $score){
        if(!$value) return false;
        $value = serialize($value);
        return $this->handler->zAdd($this->key, $score,$value);
    }

    /**
     * 删除Key中指定的value值
     * @param unknown $value
     * @return boolean
     */
    function zDelete($value){
        if(!$value) return false;
        $value = serialize($value);
        return $this->handler->zDelete($this->key,$value);
    }

    function zremrangebyrank($start=0, $end=-1){
        return $this->handler->zremrangebyrank($this->key, $start, $end);
    }

    /**
     * 获得该集合的大小
     * @return int 集合的大小
     */
    public function count() {
        return $this->handler->zCard($this->key);
    }

    /**
     * 返回SET集合中的所有元素。
     */
    function zRange($start=0, $end=-1){
        return $this->handler->zRange($this->key, $start, $end);
    }

    /**
     * 返回对应的权重范围内SET集合中的大小。
     */
    function countByScore($start=0, $end=-1){
        return $this->handler->zCount($this->key, $start, $end);
    }

    //获取某个权重范围内的所有列表
    function zRangeByScore($soreStar, $soreEnd, $param=array()){
        return $this->handler->zRangeByScore($this->key, $soreStar, $soreEnd, $param);
    }

    function zRemRangeByScore($soreStar, $soreEnd){
        return $this->handler->zRemRangeByScore($this->key, $soreStar, $soreEnd);
    }

    //add by alex 2015/04/28 10:28
    function zIncrBy($value, $score, $key = ''){
        $key = $key ? $key : $this->key;
        return $this->handler->zIncrBy($key, $score, $value);
    }

    //add by alex 2015/04/28 10:46
    function zRevRange($start=0, $end=-1, $score = false, $key = ''){
        $key = $key ? $key : $this->key;
        return $this->handler->zRevRange($key, $start, $end, $score);
    }

    //add by alex 2015/04/28 10:50
    function zRevRank( $value, $key = '' ){
        $key = $key ? $key : $this->key;
        return $this->handler->zRevRank($key, $value );
    }

    //add by alex 2015/04/28 10:50
    function zScore( $value, $key = '' ){
        $key = $key ? $key : $this->key;
        $value = serialize($value);
        return $this->handler->zScore($key, $value );
    }

    //add by alex 2015/05/04 14:50
    function zNewRangeByScore($soreStar, $soreEnd, $params = array(), $key = ''){
        $key = $key ? $key : $this->key;
        return $this->handler->zRangeByScore($key, $soreStar, $soreEnd, $params);
    }

    //add by alex 2015/05/05 16:50
    function zNewScore( $value, $score, $key = '' ){
        $key = $key ? $key : $this->key;
        return $this->handler->zAdd($key, $score, $value );
    }

    //add by alex 2015/05/05 16:50
    function zRem( $value, $key = '' ){
        $key = $key ? $key : $this->key;
        return $this->handler->zRem($key, $value );
    }
}