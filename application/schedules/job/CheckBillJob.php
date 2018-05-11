<?php
/**
 * desc:这是定时任务规范：
 * 1.一个任务一个进程（一个文件完成一个任务）
 * 2.定时任务文件里一般不写业务逻辑，它只是触发业务逻辑，调用servic里的业务逻辑
 * 3.为了后期定时任务多，统一管理，定时任务统一放到job目录下，定时任务文件统一命名：xxxJob.php
 * 4.一些耗时的业务逻辑，都建议放到计划任务里
 */

/**
 * Class CheckBillJob 系统定时对账 00.30
 * 手动按日期执行定时任务：在checkBillJob后面加上时间参数
 * 例子：  /usr/local/xjlc/php/bin/php -c /usr/local/xjlc/php/etc/php.ini -f /xjlc/php/fzcs.xiaojilicai.com/app/schedules/cli.php CheckBillJob 20171008 >> /xjlc/php/planLog/checkBill.log 2>&1
 *
 **/

class CheckBillJob extends Job {//文件名，都是规定以Job结尾的
    protected $needLocked = true;  //这个很重要（你的任务处理的业务是允许多个进程同时处理：TRUE 不允许；false 允许）
    public function notified() {//函数名是规定的
        //获取参数
        //........
//        dump($_SERVER['argv']);die;
        $para1 = $_SERVER['argv'][2];
        try {
            if($para1){
                echo $para1."\n";
            }
            echo "success";
        } catch (Exception $e) {
            echo iconv('utf-8', 'gbk', $e->getMessage());
            //如需要：错信息写入文件，发邮件，发短信.....
        }
    }

}
