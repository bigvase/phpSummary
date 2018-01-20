<?php
/**
 * Created by PhpStorm.
 * User: weiguanglong
 * Date Time: 2017/3/16 10:29
 */
include_once(ADDON_PATH.'/libs/Requests/library/Requests.php');
import_addon("libs.XmlTool");
Requests::register_autoloader();
import_addon('libs.Sms.driver.SmsBase');
class LichengSms extends SmsBase{

    private $sendSmsUrl = 'http://112.124.61.46/stardy/send.jsp';
    private $queryBalanceUrl = 'http://112.124.61.46/stardy/balance.jsp';
    private $cdkey = 'hangyjk';
    private $password = 'hangyjk789';

    public function  sendSms($mobiles, $content){

        $mobiles = is_array($mobiles) ? $mobiles : array($mobiles);
        if (!$mobiles){
            return false;
        }
        $mobiles_str = implode(',', $mobiles);
        $randNum = $this->randNum($mobiles);
        try {
            $postData = array(
                'tjpc' => $randNum,
                'usr' => $this->cdkey,
                'pwd' => $this->password,
                'mobile' => $mobiles_str,
                'msg' => iconv('UTF-8', 'GBK', $content),
                'yzm' => $this->pwdKey($mobiles)
            );
           $request = Requests::post($this->sendSmsUrl, array(), $postData);
            //$str = str_replace(PHP_EOL, '', $request->body);
            $body = ltrim($request->body, "\r"); // 返回结果成功时，body中有是“换行0”
            $body = ltrim($body, "\n"); // 返回结果成功时，body中有是“换行0”
            if($request->status_code == 200 && $request->success === true && $body === '0'){
                //请求成功
                return $body;
            }else{
                parent::saveLog(serialize($request), array('phone' => $mobiles, 'message' => $content));
                return $request->body;
            }
        } catch (Exception $e) {
            parent::saveLog($e->getMessage(), array('phone' => $mobiles, 'message' => $content));
            throw_exception($e->getMessage());
        }
    }

    private function randNum($mobile){
        $mobile_str = is_array($mobile) ? $mobile[0] : $mobile;
        return $mobile_str . rand(1, 9999);
    }

    private function pwdKey($mobile){
        $mobile_str = is_array($mobile) ? $mobile[0] : $mobile;
        $subStr = (int)substr($mobile_str, -4, 4);
        return $subStr * 3 + 1212;
    }


    /**
     * 查询余额
     */
    public function getBalance(){
        try {
            $postData = array(
                'usr' => $this->cdkey,
                'pwd' => $this->password,
            );
            $request = Requests::post($this->queryBalanceUrl, array(), $postData);

            if($request->status_code == 200 && $request->success === true){
                //请求成功
                return $request->body;
            }else{
                self::saveLog('status: '.$request->status_code.',success: '.$request->success . ',body:'
                    . $request->body, array());
                return $request->body;
            }
        } catch (Exception $e) {
            self::saveLog($e->getMessage(), array());
            return $e->getMessage();
        }
    }

}