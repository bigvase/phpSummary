<?php
/**
 * Created by PhpStorm.
 * User: bigsave
 * Date: 2018/6/8
 * Time: 17:11
 */

namespace app\admin\service;


class RedisAppService extends QueueService
{

    function __construct($redisKey = '', $expire = 0, $redis = 'default')
    {
        parent::__construct($redisKey, $expire, $redis);
    }

    /**
     * 事务
     * @param $value
     */
    public function rank($value){
        $rankKey = 'RANK-LIST-KEY';
        $listData = $this->handler->get($rankKey);
        //标记一个事务块的开始
        $this->handler->multi(\Redis::PIPELINE);
        $this->handler->lRem($listData,0,$value);
        $this->handler->lPush($listData,$value);
        $this->handler->lTrim($rankKey,0,99);
        $this->handler->exec();
    }

    public function gather($op = 'add',$key,$val,$isOnly = 1){
        if(empty($op)) return false;
        switch ($op){
            case 'add':
                if($isOnly){
                    $has = $this->handler->sIsMember($key,$val);
                    if($has) return true;
                }
                $ret = $this->handler->sAdd($key,$val);
                if($ret) return true;
                break;

            case 'del':
                if(!$this->handler->sIsMember($key,$val)) return false;
                $ret = $this->handler->sRem($key,$val);
                if($ret) return true;
                break;

            case 'all':
                if(!$this->handler->sCard($key)) return false;

                $data = $this->handler->sMembers($key);
                return $data;
                break;
            default:
                echo 'no has :'.$op;
        }
        return false;
    }

    /**
     * 集合计算 //todo 支持多个集合
     * @param string $op
     * @param $key1
     * @param $key2
     * @param array $keyArr
     * @return bool|array
     */
    public function computeGather($op = 'diff',$key1,$key2,$store='',$keyArr = []){
        if(empty($key1) || empty($key2)) return false;
//        if(!empty($keyArr));
        if(!$this->handler->sCard($key1) || !$this->handler->sCard($key2)) return false;
            $op = strtoupper($op);
        switch ($op){
            case 'DIFF':// 将那些存在于第一个集合但并不存在于其他集合中的元素（数学上的差集运算）
                if(empty($store)){
                    $data = $this->handler->sDiff($key1,$key2);
                }else{
                    $data = $this->handler->sDiffStore($store,$key1,$key2);
                }
                return $data;
                break;
            case 'INTER'://将那些同时存在于所有集合的元素（数学上的交集运算）
                if(empty($store)){
                    $data = $this->handler->sInter($key1,$key2);
                }else{
                    $data = $this->handler->sInterStore($store,$key1,$key2);
                }
                return $data;
                break;
            case 'UNION'://将那些至少存在于一个集合中的元素（数学上的并集计算）
                if(empty($store)){
                    $data = $this->handler->sUnion($key1,$key2);
                }else{
                    $data = $this->handler->sUnionStore($store,$key1,$key2);
                }
                return $data;
                break;
            default:
                echo 'no has :'.$op;
        }
    }

    public function hashOp($op = 'add',$hashKey,$key,$val){
        if(empty($hashKey)) return false;
        switch ($op){
            case 'add':
                $ret = $this->handler->hSet($key,$hashKey,$val);
                if($ret) return true;
                break;
            case 'get':
                if(!$this->handler->hExists($key,$hashKey)) return false;
                $ret = $this->handler->hGet($key,$hashKey);
                return $ret;
                break;
            case 'all':
                $data = $this->handler->hGetAll($hashKey);
                return $data;
                break;
            case 'key':
                $data = $this->handler->hKeys($hashKey);
                return $data;
                break;
            case 'value':
                $data = $this->handler->hVals($hashKey);
                return $data;
                break;
            case 'del':
                $ret = $this->handler->hDel($hashKey,$key);
                if($ret) return true;
                break;
            default:
                echo 'no has :'.$op;
        }
        return false;
    }

    public function sortGather($op='add',$key,$score,$val,$start,$end){
        if(empty($key)) return false;
        switch ($op){
            case 'add':
                if(is_numeric($score) || empty($val)) return false;

                $ret = $this->handler->zAdd($key,$score,$val);
                if($ret) return true;
                break;
            case 'range':
                $data = $this->handler->zRange($key,$start,$end);
                return $data;
                break;
            case 'score':
                $data = $this->handler->zRangeByScore($key,$start,$end);
                return $data;
            case 'del':
                $ret = $this->handler->zDelete($key,$score);
                if($ret) return true;
                break;
            default:
                echo 'no has :'.$op;
        }
    }

    public function computeSortGather($op='RANK',$key,$member,$start,$end){
        if(empty($key1) || empty($key2)) return false;

        $op = strtoupper($op);
        switch ($op){
            case 'RANK':
                $data = $this->handler->zRevRank($key,$member);
                return $data;
                break;
            case 'RANGE':
                $data = $this->handler->zRevRange($key,$start,$end);
                return $data;
                break;
//                ZRANGEBYSCORE  ZRANGEBYSCORE key min max [WITHSCORES] [LIMIT offset count] — 返回
//                有序集合中，分值介于 min 和 max 之间的所有成员
//
//                ZREVRANGEBYSCORE  ZREVRANGEBYSCORE key max min [WITHSCORES] [LIMIT offset count] —
//                获取有序集合中分值介于 min 和 max 之间的所有成员，并按照分值从大到小的顺序来返回它们
//
//                ZREMRANGEBYRANK  ZREMRANGEBYRANK key-name start stop — 移除有序集合中排名介于 start 和 stop
//                之间的所有成员
//                ZREMRANGEBYSCORE  ZREMRANGEBYSCORE key-name min max — 移除有序集合中分值介于 min 和 max 之
//                间的所有成员
//                ZINTERSTORE  ZINTERSTORE dest-key key-count key [key ...] [WEIGHTS weight
//                            [weight ...]] [AGGREGATE SUM|MIN|MAX] — 对给定的有序集合执行类似于集合的
//                交集运算
//                ZUNIONSTORE  ZUNIONSTORE dest-key key-count key [key ...] [WEIGHTS weight
//                            [weight ...]] [AGGREGATE SUM|MIN|MAX] — 对给定的有序集合执行类似于集合的
//                并集运算
            default:
                echo 'no has :'.$op;
        }
    }

    public function postSee($channel){
//        SUBSCRIBE  SUBSCRIBE channel [channel ...] — 订阅给定的一个或多个频道
//        UNSUBSCRIBE  UNSUBSCRIBE [channel [channel ...]] — 退订给定的一个或多个频道，如果执行时没
//        有给定任何频道，那么退订所有频道

//        PUBLISH  PUBLISH channel message — 向给定频道发送消息
//        PSUBSCRIBE  PSUBSCRIBE pattern [pattern ...] — 订阅与给定模式相匹配的所有频道
//        PUNSUBSCRIBE  PUNSUBSCRIBE [pattern [pattern ...]] — 退订给定的模式，如果执行时没有给定任何
//        模式，那么退订所有模式
//        $this->handler
        $this->handler->subscribe($channel,[$this,'subscribeCallback']);

    }

    private function subscribeCallback(){
        echo "订阅成功";
    }



}