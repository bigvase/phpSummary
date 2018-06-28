<?php
/**
 * 短信服务类
 * Created by PhpStorm.
 * User: bigsave
 * Date: 2018/6/14
 * Time: 11:50
 */

namespace app\admin\service;


use think\Config;
use think\Db;
use think\Log;

class SmsSendService
{
    private $temp ;
    private $smsStatus = 0;//0失败1成功
    private $smsErr    = '';
    function __construct()
    {
        if(Config::has('smsTemplate')){
            $this->temp = Config::get('smsTemplate');
        }
        //日志文件
        Log::init([
            'type'  =>  'File',
            'path'  =>  APP_PATH.'logs/'
        ]);

    }

    /**
     * 短信统一发送入口
     * @param $channel
     * @param array $content
     * @param $template
     * @param int $type
     * @return string
     */
    public function sendSMS($mobile,$channel,$content=[],$template,$type = 1){
//        header("Content-type:text/html;charset=utf-8");
        Log::record($channel);
        if(empty($channel) || empty($mobile)) return '渠道是必须传';

        if(!preg_match("/^1[34578]{1}\d{9}$/",$mobile)) return '手机号格式不正确~！';

        $con = $this->solveTemplate($template,$content);
        if(!array_key_exists($channel,$this->temp['channel'])) return '渠道没配置';
        $param = [
            'mobile'=>$mobile,
            'content'=>$con,
            'type'=>$type,
        ];
        $r = call_user_func_array([$this,$this->temp['channel'][$channel]],[$param]);
        if($r){
            $this->smsStatus = 1;
            $this->smsErr = $r;
        }
        $this->saveSmsLog($mobile,$channel,$con,$type,$template);

        return false;
    }

    private function sendCl($param){

        return true;
    }


    /**
     * 保存发送日志
     * @param $channel
     * @param $content
     * @param $type
     * @param $template
     * @return bool
     */
    private function saveSmsLog($mobile,$channel,$content,$type,$template){
        $data = [
            'mobile'=>$mobile,
            'channel'=>$channel,
            'content'=>$content,
            'type'=>$type,
            'tamp'=>$template,
            'add_time'=>time(),
            'status'=>$this->smsStatus,
            'mark'=>$this->smsErr,
        ];
        $r = Db::name('sms_log')->insert($data);
        if(!$r) return false;
        return true;
    }

    /**
     * 模板替换
     * @param $temp
     * @param $con
     * @return mixed|string
     */
    private function solveTemplate($temp,$con){
        if(!array_key_exists($temp,$this->temp['template'])){
            return $con;
        }
        $template = $this->temp['template'][$temp];
        //接收模版的里面的变量
        $replace_content=$con;

        //计算短信模版中有多少需要替换的内容变量
        $replace=substr_count($template,'#');
        //计算几个变量
        $number=intval(floor($replace/2));

        //重复，去除，分割数组(由内到外)
        $pattern=explode(',', rtrim(str_repeat("/#\w+#/,", $number),','));
        //正则匹配
        $tpl_content = preg_replace($pattern, $replace_content, $template , 1);

        return $tpl_content;
    }

}