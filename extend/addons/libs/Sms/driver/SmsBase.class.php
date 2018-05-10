<?php
/**
 * Created by PhpStorm.
 * User: weiguanglong
 * Date Time: 2017/3/16 12:51
 */
class SmsBase {

    /**
     * 出错保存错误日志
     * @param string $log 异常信息
     * @param array $param 传递的参数
     * @return void
     */
    public static function saveLog($log, $param){
        $path = SITE_DATA_PATH . "/logs/sms/".date('Y')."/".date('m');
        $logPath = $path."/".date('d').".txt";
        if(!is_dir($path)) mkdir($path,0777,true);
        $logstr = date('Y-m-d H:i:s')."       ";
        $logstr .= get_client_ip() . "        ";
        $logstr .= 'http://'.$_SERVER['SERVER_NAME'].$_SERVER["REQUEST_URI"] . "      ";//url
        $logstr .= "\r\n";
        $logstr .= $log;
        $logstr .= "\r\n";
        $logstr .= var_export($param, true);
        file_put_contents($logPath, $logstr,FILE_APPEND);
    }
}