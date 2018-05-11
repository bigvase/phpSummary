<?php

/* *
 * 类名：ChuanglanSmsApi
 * 功能：创蓝接口请求类
 * 详细：构造创蓝短信接口请求，获取远程HTTP数据
 * 版本：1.3
 * 日期：2016-07-16
 */
include_once(ADDON_PATH.'/libs/Requests/library/Requests.php');
import_addon("libs.XmlTool");
Requests::register_autoloader();
import_addon('libs.Sms.driver.SmsBase');

class ChuanglanSms extends SmsBase {

	//创蓝发送短信接口URL
	const API_SEND_URL='http://sms.253.com/msg/send';

	//创蓝短信余额查询接口URL
	const API_BALANCE_QUERY_URL='http://sms.253.com/msg/balance';

	const API_ACCOUNT='N7581937';//创蓝接口账号

	const API_PASSWORD='M3FBD0WGUK771e';//创蓝接口密码

	/**
	 * 发送短信
	 *
	 * @param string $mobile 		手机号码
	 * @param string $msg 			短信内容
	 * @param string $needstatus 	是否需要状态报告
	 */
	public function sendSms( $mobiles, $content, $needstatus = 0) {
        $mobiles = is_array($mobiles) ? $mobiles : array($mobiles);
        if (!$mobiles){
            return false;
        }
        $mobiles_str = implode(',', $mobiles);
		//创蓝接口参数
        try{
            $postData = array (
                'un' => self::API_ACCOUNT,
                'pw' => self::API_PASSWORD,
                'msg' => $content,
                'phone' => $mobiles_str,
                'rd' => $needstatus,
                'ex' => null,
            );
            $request = Requests::post(self::API_SEND_URL, array(), $postData);
            $body=$request->body; // 成功body里有：时间，状态码（换行）messageid
            $body=explode(',',$body);   //提取状态码'0'
            $body=$body[1];             //提取状态码'0'
            $body=explode("\n",$body);  //提取状态码'0'
            $body=$body[0];             //提取状态码'0'
            if($request->status_code == 200 && $request->success === true && $body === '0'){
                //请求成功
                return $body;
            }else{
                parent::saveLog(serialize($request), array('phone' => $mobiles, 'message' => $content));
                return $request->body;
            }
        }catch (Exception $e){
            parent::saveLog($e->getMessage(), array('phone' => $mobiles, 'message' => $content));
            throw_exception($e->getMessage());
        }
	}
	
	/**
	 * 查询余额
	 */
	public function queryBalance() {
        try {
            $postData = array(
                'un' => self::API_ACCOUNT,
                'pw' => self::API_PASSWORD,
            );
            $request = Requests::post(self::API_BALANCE_QUERY_URL, array(), $postData);

            if($request->status_code == 200 && $request->success === true){
                //请求成功
                $body=$request->body; // 成功body里有：时间，状态码（换行） ，余额
                $body=explode(',',$body);   //提取余额
                $body=$body[1];             //提取余额
                $body=explode("\n",$body);  //提取余额
                $body=$body[1];             //提取余额
                return   $body;

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
?>