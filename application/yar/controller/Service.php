<?php
/**
 * Created by PhpStorm.
 * User: bigsave
 * Date: 2018/4/27
 * Time: 16:13
 */

namespace app\yar\controller;


use app\common\controller\CommonController;

class Service extends CommonController
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
    }

    public function service(){
        $client = new \Yar_Client("http://php.xjlc.com/Operator.php");


//        $client->SetOpt(YAR_OPT_TIMEOUT, 0); // 设置RPC不超时
        var_dump($client->__call("add", array(3, 2)));

    }

    public function test(){
        \Yar_Concurrent_Client::call("http://php.xjlc.com/Operator.php", "add", array("parameters"), "callback");
//        \Yar_Concurrent_Client::call("http://php.xjlc.com/Operator.php", "add", array("parameters"), "callback");
//        \Yar_Concurrent_Client::call("http://php.xjlc.com/Operator.php", "add", array("parameters"), "callback");
//        \Yar_Concurrent_Client::call("http://php.xjlc.com/Operator.php", "add", array("parameters"), "callback");
        \Yar_Concurrent_Client::loop(); //send
    }
    function callback($retval, $callinfo) {
        var_dump($retval);
    }
    public function test1(){
        $demo = \think\Loader::model('yar/YarService','service');
        dump($demo);
    }

}