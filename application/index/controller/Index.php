<?php
namespace app\index\controller;
use think\Db;

class Index
{
    public function index()
    {
        $msg = array(
            'name'=>'name1',
        );
        Db::name('list1')
            ->select();
        echo db()->getConnection();
        echo "写入数据<br>";

        $data1 = Db::name('type')
            ->select();
        echo db()->getConnection();
        echo "<br>";

        dump($data1);die;
    }


    public function insertData(){

        $start = strtotime("2017-08-01");
        $end = strtotime("2017-09-01");
        for($i=$start;$i<=$end;$i+=86400){
            reportRegister($i);
        }
        exit("122");

    }

    public function test(){
        $array = [strtotime('2017-09-19'),strtotime('2017-08-25'),strtotime('2017-08-26'),strtotime('2017-08-27'),
            strtotime('2017-08-28'),strtotime('2017-08-29'),strtotime('2017-08-30'),strtotime('2017-06-16'),strtotime('2017-06-17')];
        foreach($array as $val){
            reportChannel($val);
        }
        exit();
    }
}
