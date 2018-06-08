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

    public function rank($value){
        $rankKey = 'RANK-LIST-KEY';
        $listData = $this->handler->get($rankKey);
        //标记一个事务块的开始
        $pipelined = $this->handler->multi(\Redis::PIPELINE);

        $this->handler->lRem($value,$listData,0);
        $this->handler->lPush($value,$listData);
        $this->handler->lTrim($rankKey,0,99);
        $this->handler->exec();

    }

}