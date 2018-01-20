<?php
import_addon("libs.Email.Email");
class Sms {
	const STATUS_SUCCESS = 1;
	const STATUS_FAILED = 2;
	const STATUS_ERROR = 3;
	const STATUS_DISABLE = 4;

    protected static $signature = '【小鸡理财】';
    protected static $signature_new = '';
    protected static $mi_no = '1234567890';

	//重新设置短信新签名
	public static function setNewSignature($signature_new){
		self::$signature_new = '【' . $signature_new . '】';
	}
	public static function resetSignature(){
		if(self::$signature_new)
			self::$signature = self::$signature_new;
	}

	public static function getCallFunNum($callFun){
		if(self::$signature_new)
			return 11; //新签名用创蓝通道
		return $callFun;
	}

	public static function getCode($mobile, $seed){
		$key = 'yrzif5liuj' . $seed;
		$code = substr(str_pad(hexdec(substr(md5($mobile . $key), -7)), 6, '0', STR_PAD_LEFT), -6);
		return $code;
	}
	
	public static function send($mobiles, $content, $source, $uid=0, $isReal=false, $isNotice=false, $sign='',$mi_no='') {//超过70字，自动拆分；总长不得超过700字
		if(!$mobiles)
			return array('boolen'=>0, 'message'=>"执行异常，手机为空", 'count'=>0, 'id'=>0);
		$logDataArr = array();
		$org_mobiles = $mobiles = is_array($mobiles) ? array_unique($mobiles) : array($mobiles);
		if(!in_array($uid, array(1,2,3,4,5,6))){
			$mobiles = self::filterNumbers($mobiles);
		}

        if(empty($mi_no))
            $mi_no = BaseModel::getApiKey('api_key');

        self::$mi_no = $mi_no;

		$first_mobile = $mobiles[0];
		$failedMobiles = array_diff($org_mobiles, $mobiles);
		foreach($failedMobiles as $fmobile){
			$logData = array();
			$logData['uid'] = $uid;
			$logData['mobile'] = $fmobile;
			$logData['content'] = $content;
			$logData['source'] = $source;
			$logData['status'] = self::STATUS_ERROR;
			$logDataArr[] = $logData;
		}
		$sms_counter = '';
		$sms_counter_i = '';
		
		$sms_regist_counter = '';
		$sms_login_counter = '';

		if(!$isNotice){
			Import("libs.Counter.MultiCounter", ADDON_PATH);
			$clientIp = get_client_ip();
			$checkWhiteIp = self::checkSmsWhiteIp($clientIp);
			$clientIp = $checkWhiteIp ? '' : $clientIp;

			$sms_ip_counter = MultiCounter::init(Counter::GROUP_IP_COUNTER."_ip", Counter::LIFETIME_TODAY);

			$sms_counter = MultiCounter::init(Counter::GROUP_SMS_COUNTER, Counter::LIFETIME_TODAY);
			$sms_counter_i = MultiCounter::init(Counter::GROUP_SMS_COUNTER.'_i', Counter::LIFETIME_THISI);
			
			$sms_regist_counter = MultiCounter::init(Counter::GROUP_SMS_COUNTER."_regist", Counter::LIFETIME_TODAY);
			$sms_login_counter = MultiCounter::init(Counter::GROUP_SMS_COUNTER."_login", Counter::LIFETIME_TODAY);
			$sms_login_counter2 = MultiCounter::init(Counter::GROUP_SMS_COUNTER."_login2", Counter::LIFETIME_TODAY);
		}

		$nums = array();
		foreach ($mobiles as $val) {

			if (self::isDisabled($val)){
				$logData = array();
				$logData['uid'] = $uid;
				$logData['mobile'] = $val;
				$logData['content'] = $content;
				$logData['source'] = $source;
				$logData['status'] = self::STATUS_DISABLE;
				$logData['return_str'] = '您的手机号违规已被系统屏蔽，有疑问请致电客服：4009001988';
				$logDataArr[] = $logData;
				continue;
			}
			if($isNotice){
				$nums[] = $val;
				continue;
			}

			$return_str = '';
			if($clientIp && $sms_ip_counter->get($clientIp) > 20){
				$return_str =  '您的IP' . $clientIp . '违规已被系统屏蔽，有疑问请致电客服：4009001988';
			}
			else if($source == '注册' && $sms_regist_counter->get($val)>=5){
				//self::addDisable($val);
				$return_str =  '您已连续获取手机动态码5次，请'. $sms_regist_counter->getLimitHours() .'小时后再试或联系客服4009001988';
				//$sms_regist_counter->set($val, 0);
			}
			else if($source == '登录' && $sms_login_counter2->get($val) >= 5){
				$return_str = '您已连续获取手机动态码5次，请'. $sms_login_counter2->getLimitHours() .'小时后再试或联系客服4009001988';
			}
			else if($source == '登录' && $sms_login_counter->get($val) >= 20){
				$return_str = '您当天通过短信登录的次数已超过最大限制，请选择普通登录！';
			}
			else if($sms_counter_i->get($val) > 1){
				$return_str = '您短信获取过于频繁，请2分钟之后再尝试';
			}
			//else if($sms_counter->get($val)>=20){
			//	$return_str = '该手机今天将不再收到短信，今天已经收到短信条数已经达到最大值了';
			//}
			if($return_str) {
				$logData = array();
				$logData['uid'] = $uid;
				$logData['mobile'] = $val;
				$logData['content'] = $content;
				$logData['source'] = $source;
				$logData['status'] = self::STATUS_DISABLE;
				$logData['return_str'] = $return_str;
				$logDataArr[] = $logData;
				continue;
			}

			$nums[] = $val;
		}
		if (count($nums) >= 100){
			return array('boolen'=>0, 'message'=>"超过了一次批量发送的最大数量", 'count'=>0, 'id'=>0); //一次发送的最多手机号个数
		}
		foreach($logDataArr as $logData){
			$id = self::saveLog($logData);
		}
		
		if (count($nums) == 0){
			if(count($logDataArr) < 1)
				return array('boolen'=>0, 'message'=>"没有有效的号码", 'count'=>0, 'id'=>$id);

			$logData = end($logDataArr);
			if($logData['return_str']){
				if(count($logDataArr) == 1)
					return array('boolen'=>0, 'message'=>$logData['return_str'], 'count'=>0, 'id'=>$id);
				else
					return array('boolen'=>0, 'message'=>"没有有效的号码, 最后一条错误是：".$logData['return_str'], 'count'=>0, 'id'=>$id);
			}
		}
		
		if (C('APP_STATUS') != 'product' && $isReal == false) {
			foreach($nums as $num){
				$logData = array();
				$logData['uid'] = $uid;
				$logData['mobile'] = $num;
				$logData['content'] = $content;
				$logData['source'] = $source;
				$logData['status'] = self::STATUS_SUCCESS;
				$logData['return_str'] = 'test';
				$id = self::saveLog($logData);
				if($sms_counter) $sms_counter->incr($num);//临时测试
				if($sms_counter_i) $sms_counter_i->incr($num);//临时测试
				if($source == '注册' && $sms_regist_counter) $sms_regist_counter->incr($num);
				if($source == '登录' && $sms_login_counter){
					$sms_login_counter->incr($num);
					$sms_login_counter2->incr($num);
				}
			}
			return array('boolen'=>1 ,
					 'message'=>"有效号码".count($nums)."个，------\n收件人:".implode(',', $nums)."\n内容:{$content}\nsource：{$source}\n"
					, 'count'=>count($nums), 'id'=>$id);
		}

		$data = D('Admin/SystemManage')->viewSmsConfig($mi_no);
        if(isset($data['api_info']['mi_name']))
            self::$signature = '【'.$data['api_info']['mi_name'].'】';

		if($clientIp)
			$sms_ip_counter->incr($clientIp);

		$mobileType = self::mobileType($first_mobile);
		if($mobileType)
			$funcNum = $data['value'][$mobileType];
		if(!$funcNum)
			$funcNum = '11';
		self::resetSignature();
		$funcNum = self::getCallFunNum($funcNum);
		$deal = call_user_func_array(array('Sms', 'dealApi'.$funcNum), array($content, $nums));
		if($deal['boolen'] == 1){
			return call_user_func_array(array('Sms', 'doDeal' . $funcNum), array($deal, $nums, $uid, $content,$source, $sms_counter, $sms_counter_i,$sms_regist_counter, $sms_login_counter, $sms_login_counter2));
		}

		foreach($nums as $num){
			$logData = array();
			$logData['uid'] = $uid;
			$logData['mobile'] = $num;
			$logData['content'] = $content;
			$logData['source'] = $source;
			$logData['status'] = self::STATUS_FAILED;
			$logData['return_str'] = $deal['data'];
			$id = self::saveLog($logData);
		}
		return array('boolen'=>0 , 'message'=>"执行异常，请联系系统管理员", 'count'=>0, 'id'=>0);
	}
	/****已暂停使用短信渠道****
	//1-最小sp
	private static function dealApi1($content, $mobiles){
		$mobiles = is_array($mobiles) ? $mobiles : array($mobiles);
		$pho = implode(";", $mobiles);
		$cnt    = substr_count($pho,";")+1;
		$money = 0;
		
		if (!isset($content) || !isset($pho)){
			return array("boolen"=>'0', 'message'=>'系统入参错误，请通知管理员');
		}
		
		$ws     = "http://www.sms188.net:88/HZYHY_SMS_Service.dll/wsdl/ISMS_Service";
		$client = new SoapClient ( $ws, array ("trace"          => 1,
				"uri"           => "http://www.sms188.net:88/HZYHY_SMS_Service.dll",
				"encoding"      => "utf-8"
		)
		);
		try
		{
			$arr=array("UserName"     =>"upg",
					"UserPW"       =>"4008858871",
					"AtTime"       =>"",
					"SourceAddr"   =>"088000",
					"DestAddr"     =>"$pho",
					"Content"      =>"$content",
					"SMSCount"     => &$cnt,
					"AccountMoney" => &$money,
					"ServiceID"    =>0);
		
			$result =$client->__soapCall("SubmitShortMessageMulti",$arr);
			return array('boolen'=>1, 'data'=>$result);
		}
		catch (SoapFault $Fault){
			return array('boolen'=>0, 'data'=>$Fault);
		}
	}
	
	//1-最小sp 处理发送短信后的结果
	private static function doDeal1($deal, $nums, $uid, $content, $source, $sms_counter, $sms_counter_i, $sms_regist_counter, $sms_login_counter, $sms_login_counter2){
		$accountMoney = $deal['data']["AccountMoney"];
		$returnArr = $deal['data']['return'];
		$i = 0;
		foreach($returnArr as $return){
			if(substr($return, strlen($return)-1, 1) == ';') $return = substr($return, 0, strlen($return)-1);
			if(strpos($return, ";")){
				$re_param = explode(";", $return);
				if($re_param[1] == '1000'){
					$logData = array();
					$logData['uid'] = $uid;
					$logData['channel'] = 'ubsp';
					$logData['mobile'] = $nums[$i];
					$logData['content'] = $content;
					$logData['source'] = $source;
					$logData['status'] = self::STATUS_SUCCESS;
					$logData['return_str'] = $return;
					$logData['account_money'] = $accountMoney;
					$id = self::saveLog($logData);
		
					if($sms_counter) $sms_counter->incr($nums[$i]);
					if($sms_counter_i) $sms_counter_i->incr($nums[$i]);
					if($source == '注册' && $sms_regist_counter) $sms_regist_counter->incr($nums[$i]);
					if($source == '登录' && $sms_login_counter){
						$sms_login_counter->incr($nums[$i]);
						$sms_login_counter2->incr($nums[$i]);
					}
				} else {
					$logData = array();
					$logData['uid'] = $uid;
					$logData['channel'] = 'ubsp';
					$logData['mobile'] = $nums[$i];
					$logData['content'] = $content;
					$logData['source'] = $source;
					$logData['status'] = self::STATUS_FAILED;
					$logData['return_str'] = $return;
					$logData['account_money'] = $accountMoney;
					$id = self::saveLog($logData);
				}
			} else {
				$logData = array();
				$logData['uid'] = $uid;
				$logData['channel'] = 'ubsp';
				$logData['mobile'] = $nums[$i];
				$logData['content'] = $content;
				$logData['source'] = $source;
				$logData['status'] = self::STATUS_FAILED;
				$logData['return_str'] = $return;
				$logData['account_money'] = $accountMoney;
				$id = self::saveLog($logData);
			}
			$i++;
		}
			
		if($deal['data']["SMSCount"]){
			return array('boolen'=>1 ,
					'message'=>"发送成功"
					, 'count'=>$deal['data']["SMSCount"], 'id'=>$id);
		} else {
			return array('boolen'=>0 ,
					'message'=>"发送失败"
					, 'count'=>0, 'id'=>$id);
		}
	}
	
	//2-联合维拓 
    private static function dealApi2($content, $mobiles){
	    Import("libs.Sms.driver.LhwtSms", ADDON_PATH);
	    $lhwtObj = new LhwtSms();
	    $content = $content.self::$signature;
	    $statusCode = $lhwtObj->sendSms($mobiles, $content);
	    
		if($statusCode == 0){
			$result['returnstatus'] = $statusCode;
			$result['remainpoint'] = (int)$lhwtObj->getBalance();
			$result['successCounts'] = count($mobiles);
			$result['mobiles'] = $mobiles;
			return array('boolen'=>1, 'data'=>$result);
		} else {
			$result['returnstatus'] = $statusCode;
			$result['remainpoint'] = (int)$lhwtObj->getBalance();
			$result['successCounts'] = 0;
			$result['mobiles'] = $mobiles;
			return array('boolen'=>1, 'data'=>$result);
		}
	}
	
	//2-联合维拓  处理发送短信后的结果
	private static function doDeal2($deal, $nums, $uid, $content, $source, $sms_counter, $sms_counter_i,$sms_regist_counter, $sms_login_counter, $sms_login_counter2){
		if($deal['boolen'] == 0) return $deal;
		$accountMoney = $deal['data']["remainpoint"];
		if($accountMoney < 0 ) $accountMoney = 0;
		$i = 0;
		foreach($deal['data']["mobiles"] as $mobile){
			if($deal['data']["returnstatus"] == 0){
				$logData = array();
				$logData['uid'] = $uid;
				$logData['channel'] = 'univetro';
				$logData['mobile'] = $mobile;
				$logData['content'] = $content;
				$logData['source'] = $source;
				$logData['status'] = self::STATUS_SUCCESS;
				$logData['return_str'] = $deal['data']['returnstatus'];
				$logData['account_money'] = $accountMoney;
				$id = self::saveLog($logData);
				if($sms_counter) $sms_counter->incr($nums[$i]);
				if($sms_counter_i) $sms_counter_i->incr($nums[$i]);
				if($source == '注册' && $sms_regist_counter) $sms_regist_counter->incr($nums[$i]);
				if($source == '登录' && $sms_login_counter){
					$sms_login_counter->incr($nums[$i]);
					$sms_login_counter2->incr($nums[$i]);
				}
			} else {
				$logData = array();
				$logData['uid'] = $uid;
				$logData['channel'] = 'univetro';
				$logData['mobile'] = $mobile;
				$logData['content'] = $content;
				$logData['source'] = $source;
				$logData['status'] = self::STATUS_FAILED;
				$logData['return_str'] = $deal['data']['returnstatus'];
				$logData['account_money'] = $accountMoney;
				$id = self::saveLog($logData);
				
				if($accountMoney<2500){
					$cache_key = "sms_doDeal2_nomoney";
					if(!cache($cache_key)){
						Email::send(array("lbkbox@163.com","lbkbox@163.com", "lbkbox@163.com", "lbkbox@163.com")
							, "联合维托短信平台已经快没钱了，赶紧充值啊", "联合维托短信平台已经快没钱了，赶紧充值啊");
						cache($cache_key,1);
					}
				} 
			}
			$i++;
		}
			
		if($deal['data']["successCounts"]){
			return array('boolen'=>1 ,
					'message'=>"发送成功"
					, 'count'=>$deal['data']["successCounts"], 'id'=>$id);
		} else {
			return array('boolen'=>0 ,
					'message'=>"发送失败"
					, 'count'=>0, 'id'=>$id);
		}
	}
	
	//3-创明
	private static function dealApi3($content, $mobiles){
		$mobiles = is_array($mobiles) ? $mobiles : array($mobiles);
		$pho = implode(",", $mobiles);
		$cnt    = substr_count($pho,",")+1;
		$money = 0;
		
		if (!isset($content) || !isset($pho)){
			return array("boolen"=>'0', 'message'=>'系统入参错误，请通知管理员');
		}
		
		$ws = "http://smsapi.c123.cn/OpenPlatform/OpenApi";
		$arr=array(
				"action"	=> "sendOnce",
				"ac"     =>"1001@500705180001",
				"authkey"       =>"9B2FA4C8BE0201A2E601B875A58C69DB",
				"cgid"       =>"326",
				"c"     => urlencode($content),
				"m"      => $pho,
		);
		try
		{
			Import("libs.Http", ADDON_PATH);
			$param = array();
			foreach($arr as $key=>$val){
				$param[] = $key."=".$val;
			}
			$resultXml = Http::getUrl($ws."?".implode("&", $param));
			$xml = simplexml_load_string($resultXml);
			$result = array();
			foreach($xml->attributes() as $key => $val) {
				$result[''.$key] = ''.$val;
			}
			$item = $xml->Item;
			foreach($item->attributes() as $key => $val) {
				$result[''.$key] = ''.$val;
			}
			$result['mobiles'] = $mobiles;
			return array('boolen'=>1, 'data'=>$result);
		}
		catch (Exception $Fault){
			return array('boolen'=>0, 'data'=>$Fault);
		}
	}
	
	//3-创明 处理发送短信后的结果
	private static function doDeal3($deal, $nums, $uid, $content, $source, $sms_counter, $sms_counter_i, $sms_regist_counter, $sms_login_counter, $sms_login_counter2){
		if($deal['boolen'] == 0) return $deal;
		$accountMoney = $deal['data']["remain"];
		$i = 0;
		foreach($deal['data']["mobiles"] as $mobile){
			if($deal['data']["result"] == 1){
				$logData = array();
				$logData['uid'] = $uid;
				$logData['channel'] = 'c123';
				$logData['mobile'] = $mobile;
				$logData['content'] = $content;
				$logData['source'] = $source;
				$logData['status'] = self::STATUS_SUCCESS;
				$logData['return_str'] = print_r($deal['data'], 1);
				$logData['account_money'] = $accountMoney;
				$id = self::saveLog($logData);
				if($sms_counter) $sms_counter->incr($nums[$i]);
				if($sms_counter_i) $sms_counter_i->incr($nums[$i]);
				if($source == '注册' && $sms_regist_counter) $sms_regist_counter->incr($nums[$i]);
				if($source == '登录' && $sms_login_counter){
					$sms_login_counter->incr($nums[$i]);
					$sms_login_counter2->incr($nums[$i]);
				}
			} else {
				$logData = array();
				$logData['uid'] = $uid;
				$logData['channel'] = 'c123';
				$logData['mobile'] = $mobile;
				$logData['content'] = $content;
				$logData['source'] = $source;
				$logData['status'] = self::STATUS_FAILED;
				$logData['return_str'] = print_r($deal['data'], 1);
				$logData['account_money'] = $accountMoney;
				$id = self::saveLog($logData);
			}
			$i++;
		}
			
		if($deal['data']["total"]){
			return array('boolen'=>1 ,
					'message'=>"发送成功"
					, 'count'=>$deal['data']["total"], 'id'=>$id);
		} else {
			return array('boolen'=>0 ,
					'message'=>"发送失败"
					, 'count'=>0, 'id'=>$id);
		}
	}
	
	//4-海岩
	private static function dealApi4($content, $mobiles){
		$mobiles = is_array($mobiles) ? $mobiles : array($mobiles);
		$pho = implode(",", $mobiles);
		$cnt    = substr_count($pho,",")+1;
		$money = 0;
		
		if (!isset($content) || !isset($pho)){
			return array("boolen"=>'0', 'message'=>'系统入参错误，请通知管理员');
		}
// 		$content = urlencode($content);
		$ws = "http://www.duanxin10086.com/sms.aspx";
		$arr=array("userid"     =>"5951",
				"account"       =>"A1753",
				"password"       =>"123456",
				"mobile"   => $pho,
				"content"     => $content,
				"sendTime"      => "",
				"action"     => 'send',
				"checkcontent" => 1,
				"taskName"    => "",
				"countnumber" => $cnt,
				"mobilenumber" => $cnt,
				"telephonenumber" => 0,
				);
		try
		{
			Import("libs.Http", ADDON_PATH);
			$resultXml = Http::postUrl($ws, $arr);
			$xml = simplexml_load_string($resultXml);
			$result = array();
			$result['returnstatus'] = (string)$xml->returnstatus;
			$result['message'] = (string)$xml->message;
			$result['remainpoint'] = (string)$xml->remainpoint;
			$result['taskID'] = (string)$xml->taskID;
			$result['successCounts'] = (string)$xml->successCounts;
			$result['mobiles'] = $mobiles;
			return array('boolen'=>1, 'data'=>$result);
		}
		catch (Exception $Fault){
			return array('boolen'=>0, 'data'=>$Fault);
		}
	}
	
	//4-海岩 处理发送短信后的结果
	private static function doDeal4($deal, $nums, $uid, $content, $source, $sms_counter, $sms_counter_i, $sms_regist_counter, $sms_login_counter, $sms_login_counter2){
		if($deal['boolen'] == 0) return $deal;
		$accountMoney = $deal['data']["remainpoint"];
		$i = 0;
		foreach($deal['data']["mobiles"] as $mobile){
				if($deal['data']["returnstatus"] == 'Success'){
					$logData = array();
					$logData['uid'] = $uid;
					$logData['channel'] = 'duanxin10086';
					$logData['mobile'] = $mobile;
					$logData['content'] = $content;
					$logData['source'] = $source;
					$logData['status'] = self::STATUS_SUCCESS;
					$logData['return_str'] = $deal['data']['taskID'];
					$logData['account_money'] = $accountMoney;
					$id = self::saveLog($logData);
					if($sms_counter) $sms_counter->incr($nums[$i]);
					if($sms_counter_i) $sms_counter_i->incr($nums[$i]);
					if($source == '注册' && $sms_regist_counter) $sms_regist_counter->incr($nums[$i]);
					if($source == '登录' && $sms_login_counter){
						$sms_login_counter->incr($nums[$i]);
						$sms_login_counter2->incr($nums[$i]);
					}
				} else {
					$logData = array();
					$logData['uid'] = $uid;
					$logData['channel'] = 'duanxin10086';
					$logData['mobile'] = $mobile;
					$logData['content'] = $content;
					$logData['source'] = $source;
					$logData['status'] = self::STATUS_FAILED;
					$logData['return_str'] = $deal['data']['taskID'].":".$deal['data']['message'];
					$logData['account_money'] = $accountMoney;
					$id = self::saveLog($logData);
				}
			$i++;
		}
			
		if($deal['data']["successCounts"]){
			return array('boolen'=>1 ,
					'message'=>"发送成功"
					, 'count'=>$deal['data']["successCounts"], 'id'=>$id);
		} else {
			return array('boolen'=>0 ,
					'message'=>"发送失败"
					, 'count'=>0, 'id'=>$id);
		}
	}
	
	//5-中国网建
	private static function dealApi5($content, $mobiles){
		$mobiles = is_array($mobiles) ? $mobiles : array($mobiles);
		$pho = implode(",", $mobiles);
		$cnt    = substr_count($pho,",")+1;
		$money = 0;
	
		if (!isset($content) || !isset($pho)){
			return array("boolen"=>'0', 'message'=>'系统入参错误，请通知管理员');
		}
		$ws = "http://utf8.sms.webchinese.cn/";
		$arr=array(
				"Uid"	=> "wangyun58",
				"Key"     =>"0d53ee80023bd3854546",
				"smsMob"      => $pho,
				"smsText"     => urlencode($content),
		);
		try
		{
			Import("libs.Http", ADDON_PATH);
			$param = array();
			foreach($arr as $key=>$val){
				$param[] = $key."=".$val;
			}
			$resultStr = Http::getUrl($ws."?".implode("&", $param));
			$result['result'] = $resultStr;
			$result['mobiles'] = $mobiles;
			return array('boolen'=>1, 'data'=>$result);
		}
		catch (Exception $Fault){
			return array('boolen'=>0, 'data'=>$Fault);
		}
	}
	
	//5-中国网建 处理发送短信后的结果
	private static function doDeal5($deal, $nums, $uid, $content, $source, $sms_counter, $sms_counter_i, $sms_regist_counter, $sms_login_counter, $sms_login_counter2){
		if($deal['boolen'] == 0) return $deal;
		$accountMoney = $deal['data']["remain"];
		$i = 0;
		foreach($deal['data']["mobiles"] as $mobile){
			if($deal['data']["result"] >= 1){
				$logData = array();
				$logData['uid'] = $uid;
				$logData['channel'] = 'webchinese';
				$logData['mobile'] = $mobile;
				$logData['content'] = $content;
				$logData['source'] = $source;
				$logData['status'] = self::STATUS_SUCCESS;
				$logData['return_str'] = print_r($deal['data']["result"], 1);
				$logData['account_money'] = $accountMoney;
				$id = self::saveLog($logData);
				if($sms_counter) $sms_counter->incr($nums[$i]);
				if($sms_counter_i) $sms_counter_i->incr($nums[$i]);
				if($source == '注册' && $sms_regist_counter) $sms_regist_counter->incr($nums[$i]);
				if($source == '登录' && $sms_login_counter){
					$sms_login_counter->incr($nums[$i]);
					$sms_login_counter2->incr($nums[$i]);
				}
			} else {
				$logData = array();
				$logData['uid'] = $uid;
				$logData['channel'] = 'webchinese';
				$logData['mobile'] = $mobile;
				$logData['content'] = $content;
				$logData['source'] = $source;
				$logData['status'] = self::STATUS_FAILED;
				$logData['return_str'] = print_r($deal['data']["result"], 1);
				$logData['account_money'] = $accountMoney;
				$id = self::saveLog($logData);
			}
			$i++;
		}
			
		if($deal['data']["result"]){
			return array('boolen'=>1 ,
					'message'=>"发送成功"
					, 'count'=>$deal['data']["result"], 'id'=>$id);
		} else {
			return array('boolen'=>0 ,
					'message'=>"发送失败"
					, 'count'=>0, 'id'=>$id);
		}
	}
	
	//6-国都
	private static function dealApi6($content, $mobiles){
		$mobiles = is_array($mobiles) ? $mobiles : array($mobiles);
		$pho = implode(",", $mobiles);
		$content = $content.self::$signature;
		if (!isset($content) || empty($pho)){
			return array("boolen"=>'0', 'message'=>'系统入参错误，请通知管理员');
		}
		$ws = "http://221.179.180.158:9007/QxtSms/QxtFirewall";
	
		$postData = array(
				'OperID' => 'zxth3',
				'OperPass' => 'zxth44',
				'SendTime' => '',
				'ValidTime' => '',
				'AppendID' => '',
				'DesMobile' => $pho,
				'Content' => iconv('UTF-8', 'GBK', $content),
				'ContentType' => 15
		);
	
		//错误集合
		$errorMsg = array(
				'00' => '批量短信提交成功',
				'02' => 'IP限制',
				'03' => '单条短信提交成功',
				'04' => '用户名错误',
				'05' => '密码错误',
				'07' => '发送时间错误',
				'08' => '信息内容为黑内容',
				// 				'09' => '该用户的该内容 受同天内，内容不能重复发 限制',
		// 				'10' => '扩展号错误',
				'11' => '余额不足',
				'-1' => '程序异常'
		);
	
		Import("libs.Http", ADDON_PATH);
		$ws .= '?'.http_build_query($postData);
		$resultStr = Http::getUrl($ws);
	
		//处理返回信息 xml
		$resultObj = simplexml_load_string($resultStr);
		$resultArray = objectToArray($resultObj);
	
		if(in_array($resultArray['code'], array('00', '03'))){
			array_shift($resultArray);
			return array('boolen'=>1, 'data'=>$resultArray);
		}else{
			return array('boolen'=>0, 'data'=>$errorMsg[$resultArray['code']]);
		}
	}
	
	//6-国都 处理发送短信后的结果
	private static function doDeal6($deal, $nums, $uid, $content, $source, $sms_counter, $sms_counter_i, $sms_regist_counter, $sms_login_counter, $sms_login_counter2){
		if($deal['boolen'] == 0) return $deal;
		$i = 0;
		foreach($deal['data'] as $key => $mobile){
			$mobile_str = $mobile['desmobile'];
			if(!$mobile['desmobile']){
				$mobile_str = $mobile[0]['desmobile'];
			}
			$logData = array();
			$logData['uid'] = $uid;
			$logData['channel'] = 'guodu';
			$logData['mobile'] = $mobile_str;
			$logData['content'] = $content;
			$logData['source'] = $source;
			$logData['status'] = self::STATUS_SUCCESS;
			$logData['return_str'] = print_r($mobile, 1);
			$logData['account_money'] = '';
			$id = self::saveLog($logData);
			if($sms_counter) $sms_counter->incr($nums[$i]);
			if($sms_counter_i) $sms_counter_i->incr($nums[$i]);
			if($source == '注册' && $sms_regist_counter) $sms_regist_counter->incr($nums[$i]);
			if($source == '登录' && $sms_login_counter){
				$sms_login_counter->incr($nums[$i]);
				$sms_login_counter2->incr($nums[$i]);
			}
			$i++;
		}
		return array(
				'boolen'=>1 ,
				'message'=>"发送成功",
				'count'=>$i, 
				'id'=>$id
		);
	}
	
	//漫道科技
	private static function dealApi7($content, $mobiles){
	    $mobiles = is_array($mobiles) ? $mobiles : array($mobiles);
	    $pho = implode(",", $mobiles);
	    if (empty($content) || empty($pho)){
	        return array("boolen"=>'0', 'message'=>'系统入参错误，请通知管理员');
	    }
	    $content = $content.self::$signature;
	    Import("libs.Sms.driver.MdkjSms", ADDON_PATH);
	    $sms = new MdkjSms();
	    //调用发送短信
	    $sendMsgRes = $sms->Sendsms($pho, $content, '', '', '');
	    
	    if($sendMsgRes === true){
	        $result['returnstatus'] = 0;
	        $result['remainpoint'] = $sms->Balance();
	        $result['successCounts'] = count($mobiles);
	        $result['mobiles'] = $mobiles;
	        return array('boolen'=>1, 'data'=>$result);
	    } else {
	        $result['returnstatus'] = $sendMsgRes;
	        $result['remainpoint'] = $sms->Balance();
	        $result['successCounts'] = 0;
	        $result['mobiles'] = $mobiles;
	        return array('boolen'=>1, 'data'=>$result);
	    }
	}
	
	//漫道科技
	private static function doDeal7($deal, $nums, $uid, $content, $source, $sms_counter, $sms_counter_i, $sms_regist_counter, $sms_login_counter, $sms_login_counter2){
	    if($deal['boolen'] == 0) return $deal;
	    $accountMoney = $deal['data']["remainpoint"];
	    if($accountMoney < 0 ) $accountMoney = 0;
		$i = 0;
		foreach($deal['data']["mobiles"] as $mobile){
			if($deal['data']["returnstatus"] == 0){
				$logData = array();
				$logData['uid'] = $uid;
				$logData['channel'] = 'mdkj';
				$logData['mobile'] = $mobile;
				$logData['content'] = $content;
				$logData['source'] = $source;
				$logData['status'] = self::STATUS_SUCCESS;
				$logData['return_str'] = $deal['data']['returnstatus'];
				$logData['account_money'] = $accountMoney;
				$id = self::saveLog($logData);
				if($sms_counter) $sms_counter->incr($nums[$i]);
				if($sms_counter_i) $sms_counter_i->incr($nums[$i]);
				if($source == '注册' && $sms_regist_counter) $sms_regist_counter->incr($nums[$i]);
				if($source == '登录' && $sms_login_counter){
					$sms_login_counter->incr($nums[$i]);
					$sms_login_counter2->incr($nums[$i]);
				}
			} else {
				$logData = array();
				$logData['uid'] = $uid;
				$logData['channel'] = 'mdkj';
				$logData['mobile'] = $mobile;
				$logData['content'] = $content;
				$logData['source'] = $source;
				$logData['status'] = self::STATUS_FAILED;
				$logData['return_str'] = $deal['data']['returnstatus'];
				$logData['account_money'] = $accountMoney;
				$id = self::saveLog($logData);
				
				if($accountMoney<250){
					$cache_key = "sms_doDeal7_nomoney";
					if(!cache($cache_key)){
						Email::send(array("lbkbox@163.com","lbkbox@163.com", "lbkbox@163.com", "lbkbox@163.com")
							, "漫道科技短信平台已经快没钱了，赶紧充值啊", "漫道科技短信平台已经快没钱了，赶紧充值啊");
						cache($cache_key,1);
					}
				} 
			}
			$i++;
		}
	   
		if($deal['data']["successCounts"]){
			return array('boolen'=>1 ,
					'message'=>"发送成功"
					, 'count'=>$deal['data']["successCounts"], 'id'=>$id);
		} else {
			return array('boolen'=>0 ,
                'message'=>"发送失败",
                'count'=>0, 'id'=>$id);
		}
	}
	*/
	
	//亿美软通
	private static function dealApi8($content, $mobiles){
		Import("libs.Sms.driver.YmrtSms", ADDON_PATH);
		$ymrtObj = new YmrtSms();
		$content = self::$signature.$content;
		$statusCode = $ymrtObj->sendSms($mobiles, $content);
		 
		if(!is_null($statusCode) && $statusCode === '0'){
			$result['returnstatus'] = $statusCode;
			$result['remainpoint'] = (int)$ymrtObj->getBalance();
			$result['successCounts'] = count($mobiles);
			$result['mobiles'] = $mobiles;
			return array('boolen'=>1, 'data'=>$result);
		} else {
			$result['returnstatus'] = $statusCode;
			$result['remainpoint'] = (int)$ymrtObj->getBalance();
			$result['successCounts'] = 0;
			$result['mobiles'] = $mobiles;
			return array('boolen'=>1, 'data'=>$result);
		}
	}
	
	//亿美软通
	private static function doDeal8($deal, $nums, $uid, $content, $source, $sms_counter, $sms_counter_i, $sms_regist_counter, $sms_login_counter, $sms_login_counter2){
		if($deal['boolen'] == 0) return $deal;
		$accountMoney = $deal['data']["remainpoint"];
		if($accountMoney < 0 ) $accountMoney = 0;
        if($accountMoney<2500){
//					$cache_key = "sms_doDeal8_nomoney";
//					if(!cache($cache_key)){
//						Email::send(array("lbkbox@163.com","lbkbox@163.com", "lbkbox@163.com", "lbkbox@163.com")
//						, "亿美软通短信平台已经快没钱了，赶紧充值啊", "亿美软通短信平台已经快没钱了，赶紧充值啊");
//						cache($cache_key,1);
//					}
            self::smsBalance('亿美','doDeal8');
        }
		$i = 0;
		foreach($deal['data']["mobiles"] as $mobile){
			if(!is_null($deal['data']["returnstatus"]) && $deal['data']["returnstatus"] === '0'){
				$logData = array();
				$logData['uid'] = $uid;
				$logData['channel'] = 'ymrt';
				$logData['mobile'] = $mobile;
				$logData['content'] = $content;
				$logData['source'] = $source;
				$logData['status'] = self::STATUS_SUCCESS;
				$logData['return_str'] = $deal['data']['returnstatus'];
				$logData['account_money'] = $accountMoney;
				$id = self::saveLog($logData);
				if($sms_counter) $sms_counter->incr($nums[$i]);
				if($sms_counter_i) $sms_counter_i->incr($nums[$i]);
				if($source == '注册' && $sms_regist_counter) $sms_regist_counter->incr($nums[$i]);
				if($source == '登录' && $sms_login_counter){
					$sms_login_counter->incr($nums[$i]);
					$sms_login_counter2->incr($nums[$i]);
				}
			} else {
				$logData = array();
				$logData['uid'] = $uid;
				$logData['channel'] = 'ymrt';
				$logData['mobile'] = $mobile;
				$logData['content'] = $content;
				$logData['source'] = $source;
				$logData['status'] = self::STATUS_FAILED;
				$logData['return_str'] = $deal['data']['returnstatus'];
				$logData['account_money'] = $accountMoney;
				$id = self::saveLog($logData);

			}
			$i++;
		}
			
		if($deal['data']["successCounts"]){
			return array('boolen'=>1 ,
					'message'=>"发送成功"
					, 'count'=>$deal['data']["successCounts"], 'id'=>$id);
		} else {
			return array('boolen'=>0 ,
					'message'=>"发送失败"
					, 'count'=>0, 'id'=>$id);
		}
	}
/*
    //玄武
    private static function dealApi9($content, $mobiles){
//        $content = $content.self::$signature;
        Import("libs.Sms.driver.XuanWuSms", ADDON_PATH);
        $xuanwuObj = new XuanWuSms();
        $statusCode = $xuanwuObj->sendSms($mobiles, $content);
        
        if($statusCode === '0'){
            $result['returnstatus'] = $statusCode;
            $result['remainpoint'] = (int)$xuanwuObj->getBalance();
            $result['successCounts'] = count($mobiles);
            $result['mobiles'] = $mobiles;
            return array('boolen'=>1, 'data'=>$result);
        } else {
            $result['returnstatus'] = $statusCode;
            $result['remainpoint'] = (int)$xuanwuObj->getBalance();
            $result['successCounts'] = 0;
            $result['mobiles'] = $mobiles;
            return array('boolen'=>1, 'data'=>$result);
        }
    }

    //玄武
    private static function doDeal9($deal, $nums, $uid, $content, $source, $sms_counter, $sms_counter_i, $sms_regist_counter, $sms_login_counter, $sms_login_counter2){
        if($deal['boolen'] == 0) return $deal;
        $accountMoney = $deal['data']["remainpoint"];
        if($accountMoney < 0 ) $accountMoney = 0;
        $i = 0;
        foreach($deal['data']["mobiles"] as $mobile){
            if($deal['data']["returnstatus"] === '0'){
                $logData = array();
                $logData['uid'] = $uid;
                $logData['channel'] = 'xuanwu';
                $logData['mobile'] = $mobile;
                $logData['content'] = $content;
                $logData['source'] = $source;
                $logData['status'] = self::STATUS_SUCCESS;
                $logData['return_str'] = $deal['data']['returnstatus'];
                $logData['account_money'] = $accountMoney;
                $id = self::saveLog($logData);
                if($sms_counter) $sms_counter->incr($nums[$i]);
				if($sms_counter_i) $sms_counter_i->incr($nums[$i]);
                if($source == '注册' && $sms_regist_counter) $sms_regist_counter->incr($nums[$i]);
                if($source == '登录' && $sms_login_counter){
                    $sms_login_counter->incr($nums[$i]);
                    $sms_login_counter2->incr($nums[$i]);
                }
            } else {
                $logData = array();
                $logData['uid'] = $uid;
                $logData['channel'] = 'xuanwu';
                $logData['mobile'] = $mobile;
                $logData['content'] = $content;
                $logData['source'] = $source;
                $logData['status'] = self::STATUS_FAILED;
                $logData['return_str'] = $deal['data']['returnstatus'];
                $logData['account_money'] = $accountMoney;
                $id = self::saveLog($logData);

                if($accountMoney<2500){
                    $cache_key = "sms_doDeal9_nomoney";
                    if(!cache($cache_key)){
                        Email::send(array("lbkbox@163.com","lbkbox@163.com", "lbkbox@163.com", "lbkbox@163.com")
                            , "玄武短信平台已经快没钱了，赶紧充值啊", "玄武短信平台已经快没钱了，赶紧充值啊");
                        cache($cache_key,1);
                    }
                }
            }
            $i++;
        }
        
        if($deal['data']["successCounts"]){
            return array('boolen'=>1 ,
                'message'=>"发送成功"
            , 'count'=>$deal['data']["successCounts"], 'id'=>$id);
        } else {
            return array('boolen'=>0 ,
                'message'=>"发送失败"
            , 'count'=>0, 'id'=>$id);
        }
    }
*/

	//李成
	private static function dealApi10($content, $mobiles){

		import_addon("libs.Sms.driver.LichengSms");
		$lichengSms = new LichengSms();
		$content = self::$signature.$content;
		try{
			$statusCode = $lichengSms->sendSms($mobiles, $content);
		}catch (Exception $e){
			return array('boolen'=>0, 'data'=>$e->getMessage());
		}

		if(!is_null($statusCode) && $statusCode === '0'){
			$result['successCounts'] = count($mobiles);
		} else {
			$result['successCounts'] = 0;
		}
		$result['returnstatus'] = $statusCode;
		$result['remainpoint'] = (int)$lichengSms->getBalance();
		$result['mobiles'] = $mobiles;
		return array('boolen'=>1, 'data'=>$result);
	}
	//李成
	private static function doDeal10($deal, $nums, $uid, $content, $source, $sms_counter, $sms_counter_i, $sms_regist_counter, $sms_login_counter, $sms_login_counter2){
		if($deal['boolen'] == 0)
			return $deal;
		$accountMoney = $deal['data']["remainpoint"];
		if($accountMoney < 0 )
			$accountMoney = 0;
		//余额不足，发邮件提醒
        if($accountMoney<2500){
            self::smsBalance('李成','doDeal10');
        }
		$i = 0;
		foreach($deal['data']["mobiles"] as $mobile){
			$logData = array();
			if(!is_null($deal['data']["returnstatus"]) && $deal['data']["returnstatus"] === '0'){
				$logData['status'] = self::STATUS_SUCCESS;
				if($sms_counter) $sms_counter->incr($nums[$i]);
				if($sms_counter_i) $sms_counter_i->incr($nums[$i]);
				if($source == '注册' && $sms_regist_counter) $sms_regist_counter->incr($nums[$i]);
				if($source == '登录' && $sms_login_counter){
					$sms_login_counter->incr($nums[$i]);
					$sms_login_counter2->incr($nums[$i]);
				}
			} else {
				$logData['status'] = self::STATUS_FAILED;

			}
			$logData['uid'] = $uid;
			$logData['channel'] = 'licheng';
			$logData['mobile'] = $mobile;
			$logData['content'] = $content;
			$logData['source'] = $source;
			$logData['return_str'] = $deal['data']['returnstatus'];
			$logData['account_money'] = $accountMoney;
			$id = self::saveLog($logData);

			$i++;

		}

		if($deal['data']["successCounts"]){
			return array('boolen'=>1 , 'message'=>"发送成功" , 'count'=>$deal['data']["successCounts"], 'id'=>$id);
		} else {
			return array('boolen'=>0 , 'message'=>"发送失败，code：" . $deal['data']["returnstatus"] , 'count'=>0, 'id'=>$id);
		}
	}

	//创蓝--发送短信
    private static function dealApi11($content, $mobiles){
        import_addon("libs.Sms.driver.ChuanglanSms");
        $chuanglanSms = new ChuanglanSms();
        $content = self::$signature.$content;
        try{
            $statusCode = $chuanglanSms->sendSms($mobiles, $content);
        }catch (Exception $e){
            return array('boolen'=>0, 'data'=>$e->getMessage());
        }

        if(!is_null($statusCode) && $statusCode === '0'){
            $result['successCounts'] = count($mobiles);
        } else {
            $result['successCounts'] = 0;
        }
        $result['returnstatus'] = $statusCode;
        $result['remainpoint'] = (int)$chuanglanSms->queryBalance();
        $result['mobiles'] = $mobiles;
        return array('boolen'=>1, 'data'=>$result);
    }
    //创蓝--处理短信发送结果
    private static function doDeal11($deal, $nums, $uid, $content, $source, $sms_counter, $sms_counter_i, $sms_regist_counter, $sms_login_counter, $sms_login_counter2){
        if($deal['boolen'] == 0)
            return $deal;
        $accountMoney = $deal['data']["remainpoint"];//余额
        if($accountMoney < 0 )
            $accountMoney = 0;
        //余额不足，发邮件提醒
        if($accountMoney<2500){
            self::smsBalance('创蓝','doDeal11');
        }
        $i = 0;
        foreach($deal['data']["mobiles"] as $mobile){
            $logData = array();
            if(!is_null($deal['data']["returnstatus"]) && $deal['data']["returnstatus"] === '0'){
                $logData['status'] = self::STATUS_SUCCESS;
                if($sms_counter) $sms_counter->incr($nums[$i]);
                if($sms_counter_i) $sms_counter_i->incr($nums[$i]);
                if($source == '注册' && $sms_regist_counter) $sms_regist_counter->incr($nums[$i]);
                if($source == '登录' && $sms_login_counter){
                    $sms_login_counter->incr($nums[$i]);
                    $sms_login_counter2->incr($nums[$i]);
                }
            } else {
                $logData['status'] = self::STATUS_FAILED;
            }
            $logData['uid'] = $uid;
            $logData['channel'] = 'chuanglan';
            $logData['mobile'] = $mobile;
            $logData['content'] = $content;
            $logData['source'] = $source;
            $logData['return_str'] = $deal['data']['returnstatus'];
            $logData['account_money'] = $accountMoney;
            $id = self::saveLog($logData);
            $i++;
        }

        if($deal['data']["successCounts"]){
            return array('boolen'=>1 , 'message'=>"发送成功" , 'count'=>$deal['data']["successCounts"], 'id'=>$id);
        } else {
            return array('boolen'=>0 , 'message'=>"发送失败，code：" . $deal['data']["returnstatus"] , 'count'=>0, 'id'=>$id);
        }
    }

    //余额不足，发送余额提醒邮件，并将提示信息存缓存一天
    public static function smsBalance($channelName,$cache_key){

		return ; //不再发邮件，通道有短信提醒

        $cache = "sms_".$cache_key."_nomoney";
        if(!cache($cache)){
			cache($cache,1,86400);
            Email::realsend(array( "lj@tourongchangfu.com","stwy@tourongjia.com", "wangxl@tourongjia.com","weiguanglong@tourongjia.com","wangliuzheng@tourongjia.com"), $channelName."短信平台已经快没钱了，赶紧充值啊", $channelName."短信平台已经快没钱了，赶紧充值啊");
        }
    }

	//短信IP限制--白名单
	private static function checkSmsWhiteIp($ip){
		$ips = self::getSmsWhiteIps();
		foreach($ips as $val){
			if($val['ip'] == $ip)
				return true;
		}
		return false;
	}

	private static function getSmsWhiteIps(){
		$key = 'smsWhiteIps';
		$ips = cache($key);
		//if($ips)
		//	return $ips;

		$ips = M('sms_white_ips')->select();
		cache($key, $ips, array('expire'=>600));
		return $ips;
	}

	private static function saveLog($param, $id=0){
		if($id){
			$param['id'] = $id;
			$param['mtime'] = time();
			M('sms')->save($param);
			$sid = $id;
		} else {
			$param['ctime'] = time();
			$param['mi_no'] = self::$mi_no;
			$sid = M('sms')->add($param);
			
			$path = SITE_DATA_PATH . "/logs/sms/".date('Y')."/".date('m');
			$logPath = $path."/".date('d').".txt";
			if(!is_dir($path)) mkdir($path,0777,true);
			$logstr = date('Y-m-d H:i:s')."       ";
			$logstr .= get_client_ip() . "        ";
			$logstr .= 'http://'.$_SERVER['SERVER_NAME'].$_SERVER["REQUEST_URI"] . "      ";//url
			$logstr .= "\r\n";
			$logstr .= $sid;
			$logstr .= "\r\n";
			$logstr .= (M('sms')->getDbError())."-".(M('sms')->getError());
			$logstr .= "\r\n";
			$logstr .= print_r($param, 1);
			file_put_contents($logPath, $logstr,FILE_APPEND);
		}
		return $sid;
	}
	
	public static function addDisable($mobile, $cuid=1){
		$disable = M('sms_disable')->find($mobile);
		if($disable) return array('boolen'=>1, 'message'=>'已经加入');
		$mData['mobile'] = $mobile;
		$mData['cuid'] = $cuid;
		$mData['ctime'] = time();
		$con = M('sms_disable')->add($mData);
		if($con) return array('boolen'=>1, 'message'=>'添加成功');
		else return array('boolen'=>0, 'message'=>'添加失败');
	}
	
	public static function delDisable($mobile){
		$disable = M('sms_disable')->find($mobile);
		if(!$disable) return array('boolen'=>1, 'message'=>'不存在');
		$con = M('sms_disable')->where(array('mobile'=>$mobile))->delete();
		if($con) return array('boolen'=>1, 'message'=>'删除成功');
		else return array('boolen'=>0, 'message'=>'删除失败');
	}
	
	// 黑名单
	public static function isDisabled($mobile){
		$key = 'sms_mobile_disabled';
		$mdisableArr = cache($key);
		if(!$mdisableArr){
			$mdisableArr = M('sms_disable')->select();
			$mdisableArr[] = array('mobile'=>1);
			cache($key, $mdisableArr, array('expire'=>600));
		}
		foreach ($mdisableArr as $mdisable){
			if($mdisable['mobile'] == $mobile)
				return true;
		}
		return false;
	}
	
	/**
	 * 滤掉不合法的手机号
	 * @param string|array $mobiles
	 * @return array
	 */
	private static function filterNumbers($mobiles) {
		$mobiles = is_array($mobiles) ? array_unique($mobiles) : array($mobiles);
		$mobs = array();
		foreach ($mobiles as $mob) {
			if (self::isMobile($mob))	$mobs[] = $mob;
		}
		return $mobs;
	}
	
	//获取手机的供应商类型，移动 联通 电信
	public static function mobileType($mobile){
		$lt_arr = array('130', '131', '132'. '155', '156', '185', '186', '176'); //联通
		$dx_arr = array('133', '153', '180'. '181', '189', '177'); //电信
		$yd_arr = array('134', '135', '136'. '137', '138', '139', '150', '151', '152', '158', '159', '182', '183', '187', '188', '178'); //移动
		foreach($lt_arr as $lt){
			if(strpos($mobile, $lt) === 0) return 'LT';
		}
		foreach($dx_arr as $dx){
			if(strpos($mobile, $dx) === 0) return 'DX';
		}
		foreach($yd_arr as $yd){
			if(strpos($mobile, $yd) === 0) return 'YD';
		}
	}
	
	public static function isMobile($mobile) {
// 		if (!preg_match("/^13[0-9]{1}[0-9]{8}$|15[0189]{1}[0-9]{8}$|189[0-9]{8}$/",$mobile)) return false;
		if (!preg_match('/^1(3|4|5|7|8)\d{9}$/',$mobile)) return false;
		//这里写要封停的手机号；
		return true;
	}
}
