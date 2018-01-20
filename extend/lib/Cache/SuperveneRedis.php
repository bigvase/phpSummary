<?php
/**
 * Created by PhpStorm.
 * User: bigsave
 * Date: 2018/1/12
 * Time: 17:26
 */

namespace Addons\Libs\Cache;


class SuperveneRedis
{
    //句柄
    public static $instance;

    function __construct()
    {

        if(!self::$instance){
            self::$instance = $this->getInstance();
        }
        return self::$instance;
    }
    //单例
    private function getInstance(){

    }

}