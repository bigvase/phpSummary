<?php
class XuanWuSms{
    
    const MSG_WEBSERVERIP = '211.147.224.154';
    const USERNAME = 'xhh@xhh';
    const PASSWORD = 'XW47*!wx';
    private $sendSmsUrl = 'http://211.147.224.154:13013/cgi-bin/sendsms';
    
    /**
     * 发送短信
     */
    public function sendSms($mobile, $content){
        try {
            //发送前检查
            $mobile = self::_checkBeforeSend($mobile, $content);
            $content = iconv('UTF-8', 'GBK', $content);
            $postData = array(
                'username' => self::USERNAME,
                'password' => self::PASSWORD,
                'to' => $mobile,
                'text' => urlencode($content),
                'subid' => '',
                'msgtype' => 1,
            );
            $url_params = array();
            foreach ($postData as $key => $value) {
                $url_params[] = $key.'='.$value;
            }
            $url_string = $this->sendSmsUrl.'?'.implode('&', $url_params);
            if(($return_code = file_get_contents($url_string)) === '0'){
                return $return_code;
            }else{
                throw_exception(self::_getError($return_code));
            }
        } catch (Exception $e) {
            self::saveLog($e->getMessage(), array(
                'phone' => $mobile, 
                'message' => $content,
            ));
        }
    }
    
    /**
     * 查询余额
     */
    public function getBalance(){
        try {
            $result = self::_soapRequest('getRemainFee', 'getRemainFeeReturn');
            if (is_numeric($result) && $result > 0) {
                return (int)$result;
            } elseif ($result == 'ERROR') {
                $error_code = -6;    //用户名密码出错
            } elseif ($result == '') {
                $error_code = -1;
            }
            if (isset($error_code)) {
                throw_exception(self::_getError($error_code));
            }
        } catch (Exception $e) {
            self::saveLog($e->getMessage(), array());
        }
    }

    /**
     * 发请求
     * @param $returnTag
     * @return string
     */
    private static function _soapRequest($soapAction, $returnTag){
        
        //HTTP请求头字符串外壳
        define("HTTP_HEADER", "SOAPAction: \"http://%s/services/EsmsService/%s\"\r\n" .
            "User-Agent: SOAP Toolkit 3.0\r\n" .
            "Host: %s:8080\r\n" .
            "Content-Length: %d\r\n" .
            "Connection: Keep-Alive\r\n" .
            "Pragma: no-cache\r\n\r\n");

        //HTTP请求体字符串外壳
        define("HTTP_REQUEST_DATA", "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>" .
            "<SOAP-ENV:Envelope SOAP-ENV:encodingStyle=\"\" " .
            "xmlns:SOAPSDK1=\"http://www.w3.org/2001/XMLSchema\" " .
            "xmlns:SOAPSDK2=\"http://www.w3.org/2001/XMLSchema-instance\" " .
            "xmlns:SOAPSDK3=\"http://schemas.xmlsoap.org/soap/encoding/\" " .
            "xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\">" .
            "<SOAP-ENV:Body SOAP-ENV:encodingStyle=\"\">" .
            "<%s SOAP-ENV:encodingStyle=\"\">" .				//soap请求动作
            "<n1 SOAP-ENV:encodingStyle=\"\">%s</n1>" .		//用户名
            "<n2 SOAP-ENV:encodingStyle=\"\">%s</n2>" .		//密码
            "</%s>"	.						//soap请求动作
            "</SOAP-ENV:Body>" .
            "</SOAP-ENV:Envelope>");
        
        $Msg_WebServerIP = self::MSG_WEBSERVERIP;
        $userName = self::USERNAME;
        $password = self::PASSWORD;
        $soapError = "ERROR";

        //HTTP请求的数据
        $requestData = sprintf(HTTP_REQUEST_DATA, $soapAction, $userName, $password, $soapAction);

        //HTTP请求头
        $httpHeader = sprintf(HTTP_HEADER, $Msg_WebServerIP, $soapAction, $Msg_WebServerIP, strlen($requestData));

        $url = "POST /services/EsmsService?wsdl HTTP/1.1\r\n";

        $sock = fsockopen($Msg_WebServerIP, 8080);

        if ($sock == 0)
            return $soapError;

        fputs($sock, $url . $httpHeader . $requestData);	//发送HTTP请求到服务器

        //跳过HTTP的文件头
        for ($i = 0; $i < 7; $i++)
            fgets($sock, 100);

        $tagBegin = sprintf("<%s", $returnTag);
        $tagEnd = sprintf("</%s>", $returnTag);

        //获取XML字符串
        $buffer = "";
        $segGets = fgets($sock, 4096 * 3);
        while (strpos($segGets, $tagEnd) == FALSE)
        {
            $buffer .= $segGets;
            $segGets = fgets($sock, 4096 * 3);
            if ($segGets == FALSE)
                break;
        }
        fclose($sock);
        $buffer .= $segGets;

        $beginPos = strpos($buffer, $tagBegin);
        if ($beginPos == FALSE)
            return "";

        $beginPos = strpos($buffer, ">", $beginPos + strlen($tagBegin)) + 1;
        $endPos = strPos($buffer, $tagEnd, $beginPos);
        if ($endPos == FALSE)
            return "";

        return substr($buffer, $beginPos, $endPos - $beginPos);
    }

    /**
     * 发送前的校验
     * @param $mobile
     * @param $content
     */
    private static function _checkBeforeSend($mobile, $content){
        
        $mobile = is_array($mobile) ? $mobile : array($mobile);
        $error_code = 0;
        if (!$mobile) {
            $error_code = -14;
        }
        if (count($mobile) > 100) {
            $error_code = -11;
        }
        if (self::_getContentLen($content) > 70) {
            $error_code = -5;
        }
        if (empty($content)) {
            $error_code = -15;
        }
        if ($error_code) {
            throw_exception(self::_getError($error_code));
        }
        $mobile = implode('+', $mobile);
        return $mobile;
    }
    
    /**
     * 短信内容的长度
     * @param $str
     * @return int
     */
    private static function _getContentLen($str){
        $count = 0;
        $i = 0;
        $len = strlen($str);
        while ($i < $len){
            if (ord($str[$i]) > 128) {
                $i += 2;
            } else {
                $i += 1;
            }
            $count++;
        }
        return $count;
    }

    /**
     * 详细的错误信息
     * @param $error_code
     */
    private static function _getError($error_code){
        switch ($error_code) {
            case -1:
                $error_msg = '获取失败';
                break;
            case -5:
                $error_msg = '短信内容超长';
                break;
            case -6:
                $error_msg = '密码不正确';
                break;
            case -11:
                $error_msg = '群发号码超过100个';
                break;
            case -14:
                $error_msg = '发送号码为空';
                break;
            case -15:
                $error_msg = '短信内容为空';
                break;
            case -99:
                $error_msg = '其他错误返回';
                break;
            default:
                $error_msg = '未知错误';
                break;
        }
        return $error_msg;
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
