<?php
/**
 * Created by PhpStorm.
 * User: bigsave
 * Date: 2017/10/20
 * Time: 11:14
 */
namespace app\home\controller;


class Mq{

    public function index(){
        $service -> initConsumer();
        echo 123;die;
    }


}

