<?php

include_once(ADDON_PATH.'/libs/Requests/library/Requests.php');
import_addon("libs.XmlTool");
Requests::register_autoloader();

class YmrtSms{
    
    private $sendSmsUrl = 'http://sdk999ws.eucp.b2m.cn:8080/sdkproxy/sendsms.action';
    private $queryBalanceUrl = 'http://sdk999ws.eucp.b2m.cn:8080/sdkproxy/querybalance.action';
    private $cdkey = '9SDK-EMY-0999-RETMP';
    private $password = '237754';
    
    /**
     * 发送短信
     */
    public function sendSms($mobiles, $content){
        $mobiles = is_array($mobiles) ? $mobiles : array($mobiles);
        if (!$mobiles){
            return false;
        }
        $mobiles = implode(',', $mobiles);
		
        try {
        	$postData = array(
        		'cdkey' => $this->cdkey,
        		'password' => $this->password,
        		'phone' => $mobiles,
        		'message' => $content
        	);
        	$request = Requests::post($this->sendSmsUrl, array(), $postData);
        	
        	if($request->status_code == 200 && $request->success == 1){
        		//请求成功
        		$request->body = preg_replace('/^[^<]*/', '', $request->body);
        		$return = XmlTool::xml2array($request->body);
        		return $return['error'];
        	}else{
        		self::saveLog('status: '.$request->status_code.',success: '.$request->success, array('phone' => $mobiles, 'message' => $content));
        	}
        } catch (Exception $e) {
        	self::saveLog($e->getMessage(), array('phone' => $mobiles, 'message' => $content));
        }
    }
    
    /**
     * 查询余额
     */
    public function getBalance(){
        try {
        	$postData = array(
	            'cdkey' => $this->cdkey, 
	            'password' => $this->password,
	        );
        	$request = Requests::post($this->queryBalanceUrl, array(), $postData);
        	 
        	if($request->status_code == 200 && $request->success == 1){
        		//请求成功
        		$request->body = preg_replace('/^[^<]*/', '', $request->body);
        		$return = XmlTool::xml2array($request->body);
        		return $return['message'];
        	}else{
        		self::saveLog('status: '.$request->status_code.',success: '.$request->success, array());
        	}
        } catch (Exception $e) {
        	self::saveLog($e->getMessage(), array());
        }
    }
    
    /**
     * 出错保存错误日志
     * @param string $log 异常信息
     * @param array $param 传递的参数
     * @return void
     */
    private static function saveLog($log, $param){
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
