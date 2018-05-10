<?php
/**
 * 市场活动专用
 * Author liubingkai <lbkbox@163.com>
 * Create 15-07-04 15:45
 */
class MyRedis { 
    private $key = '';
    private $prefix = '';
    private $expire = 3600;
    private $handler = null;

    static function getInstance($redisKey = '',$expire=-1,$serializer = false){
        return new self($redisKey, $expire, $serializer);
    }

    function __construct($redisKey, $expire, $serializer){
        $this->expire =  isset($expire)?  $expire  :  86400;
        $this->prefix =  '';
        $this->handler = \Addons\Libs\Cache\Redis::getInstance('activity',$serializer);
        $this->key = $this->prefix.$redisKey;

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
    function setExpire($expire='', $key = ''){
        $key = $key ? $key : $this->key;
        $expire = $expire ? $expire : $this->expire;
        return $this->handler->expire($this->key, $expire);
    }

    /**
     * 删除
     */
    function delete(){
        return $this->handler->del($this->key);
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

    //add by alex 2015/05/05 16:51
    function zRem( $value, $key = '' ){
        $key = $key ? $key : $this->key;
        return $this->handler->zRem($key, $value );
    }

    function set( $value, $key = '', $expire = '' ){
        $key = $key ? $key : $this->key;
        $expire = $expire ? $expire : $this->expire;
        return $this->handler->set($key, $value,$expire );
    }

    function del( $value, $key = '' ){
        $key = $key ? $key : $this->key;
        return $this->handler->del($key, $value);
    }

    function lPush($value, $key = ''){
        $key = $key ? $key : $this->key;
        return $this->handler->lPush($key, $value);
    }

    function keys( $key = '' ){
        $key = $key ? $key : $this->key;
        return $this->handler->keys($key);
    }

    function hMSet( $value, $key = '' ){
        $key = $key ? $key : $this->key;
        return $this->handler->hMSet($key, $value);
    }

    function hIncrBy( $value, $step = 1, $key = '' ){
        $key = $key ? $key : $this->key;
        return $this->handler->hIncrBy($key, $value, $step);
    }

    function hGet( $value, $key = '' ){
        $key = $key ? $key : $this->key;
        return $this->handler->hGet($key, $value);
    }

    function get( $key = '' ){
        $key = $key ? $key : $this->key;
        return $this->handler->get( $key );
    }

    function incrBy( $step = 1, $key = '', $expire = '' ){
        $key = $key ? $key : $this->key;
        $ret =  $this->handler->incrBy( $key, $step );
        if( $expire ) $this->handler->expire( $key, $expire );
        return $ret;
    }

    function ttl( $key = ''){
        $key = $key ? $key : $this->key;
        return $this->handler->ttl( $key );
    }

    /**
     * 获得该集合的大小
     * @return int 集合的大小
     */
    public function countNew($key = '') {
        $key = $key ? $key : $this->key;
        return $this->handler->zCard($key);
    }
}