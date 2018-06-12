<?php
/**
 * Class TestController
 */

namespace app\admin\controller;


use app\common\controller\BaseController;
use think\Config;

class Test extends  BaseController {
	public function test(){
	    dump(config::get());die;
        $queue = \think\Loader::model('admin/QueueService','service');
//        echo($queue->rGet('kkk-vvv'));
//        $demo = \think\Loader::model('admin/HttpRequestService','service');
//        $demo->test();
        $message='新年快乐';
        $ret = $queue->handler->publish('中央广播电台',$message);
        dump($ret);
    }
    public function index(){
        ini_set('default_socket_timeout', -1);
//	    $a = Config::get('interfaceParam');
//	    dump($a);die;
//	    die;
//        $file = ROOT_PATH.'data/log.txt';
//        $file1 = ROOT_PATH.'data/log1.txt';
        $queue = \think\Loader::model('admin/RedisAppService','service');
        $queue->handler->subscribe(array('中央广播电台'), [$this,'callback']);
//        $key = 'aaa_bbb11';
//        $lock = $queue->lock($key);
//        if($lock){
//            $ib = file_get_contents($file1);
//            file_put_contents($file1,$ib+1);
//            return false;
//        }
//        $ic = file_get_contents($file);
//        for ($i= 0;$i<100000;$i++){
//            $a = 1;
//        }
//        if($ic == 0){
//            return false;
//        }
//        file_put_contents($file,$ic-1);
//        $queue->unlock($key);
    }

    function callback($instance,$channelName,$message){
        echo 'instance:'.$instance."channelName:".$channelName."message".$message;
    }


}