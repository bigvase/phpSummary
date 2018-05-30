<?php
/**
 * Class TestController
 */

namespace app\admin\controller;


use app\common\controller\BaseController;

class Test extends  BaseController {
	public function test(){
        $demo = \think\Loader::model('admin/HttpRequestService','service');
        $demo->test();
    }
    public function index(){
        $file = ROOT_PATH.'data/log.txt';
        $file1 = ROOT_PATH.'data/log1.txt';
//        $queue = \think\Loader::model('admin/QueueService','service');
//        $key = 'aaa_bbb11';
//        $lock = $queue->lock($key);
//        if($lock){
//            $ib = file_get_contents($file1);
//            file_put_contents($file1,$ib+1);
//            return false;
//        }
        $ic = file_get_contents($file);
        for ($i= 0;$i<100000;$i++){
            $a = 1;
        }
        if($ic == 0){
            return false;
        }
        file_put_contents($file,$ic-1);
//        $queue->unlock($key);
    }
}