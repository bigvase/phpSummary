<?php
/**
 * Created by PhpStorm.
 * User: bigsave
 * Date: 2018/4/24
 * Time: 19:37
 */

//$client = new Yar_Client("http://php.xjlc.com/Operator.php");
//
////var_dump($client);die;
//
//$client->SetOpt(YAR_OPT_TIMEOUT, 0); // 设置RPC不超时
//
///* call directly */
//
//$a = $client->add(1, 2);
//var_dump($a);
//
///* call via call */
//
//var_dump($client->__call("add", array(3, 2)));
//
///* __add can not be called */
//
//var_dump($client->_add(1, 2));
    Yar_Concurrent_Client::call("http://php.xjlc.com/Operator.php", "add", array("parameters"), "callback");
    Yar_Concurrent_Client::call("http://php.xjlc.com/Operator.php", "add", array("parameters"), "callback");
    Yar_Concurrent_Client::call("http://php.xjlc.com/Operator.php", "add", array("parameters"), "callback");
    Yar_Concurrent_Client::call("http://php.xjlc.com/Operator.php", "add", array("parameters"), "callback");
    Yar_Concurrent_Client::loop(); //send

function callback($retval, $callinfo) {
    var_dump($retval);
}

