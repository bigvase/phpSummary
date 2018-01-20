<?php

namespace App\Lib\Service;

use \BaseService;

/**
 * 功能：银行存管接口服务基类[对接银行] http协议
 * 描述：此类事与java通讯的最底层的类，其它银行存管业务了都调用此类
 *       在此类定义了接口参数，接口类
 * add by lbk 2017-06-15
 */
class EscrowJavaHttpService extends BaseService {

    //目前不需要，考虑后期扩展 
    const API_KEY = 'ESCROW_xxxxx';
    const API_SECRET = 'xxxxxxxxxxxxxxxxxxxxx';

    //平台编号
    private $platformNo;
    //地址
    private $_url;
    //
    //直连地址
    private $_urldir;
    //网关地址
    private $_urlgate;
    //对账文件下载地址地址
    private $_urldown;
    //私钥
    private $privatekey;
    //公钥
    private $publickey;
    //请求来源
    private $_platform;
    //请求参数
    private $_param = array();
    //请求成功标志
    protected $_isSuccessed;
    //错误代码
    protected $_errorCode;
    //错误消息 
    protected $_errorMsg;
    //请求后的返回数据
    protected $_response = array();
    //是否调试状态
    protected $_debug = false;
    private $_secret;
    //app的key，现在用不到，以后扩展需要
    private $_api_client_key;
    protected $timeout = 1100;  // 超时时间

    protected $_checkParamLengthSwitch = false;

    public function __construct() {
        parent::__construct();
        $this->_debug = C('APP_STATUS') == 'product' ? false : true;
        //银行存管地址
        $this->_urldir = C('APP_STATUS') == 'product' ? C('ESCROW_PRODUCI_URL_DIRECT') : C('ESCROW_TEST_URL_DIRECT');     //直连
        $this->_urlgate = C('APP_STATUS') == 'product' ? C('ESCROW_PRODUCI_URL_GATEWAY') : C('ESCROW_TEST_URL_GATEWAY'); //网关
        $this->_urldown = C('APP_STATUS') == 'product' ? C('ESCROW_PRODUCI_URL_DOWN') : C('ESCROW_TEST_URL_DOWN'); //网关
        //存管私钥
        //$this->privatekey = C('APP_STATUS') == 'product' ? C('ESCROW_PRODUCI_KEY_PRIVATE') : C('ESCROW_TEST_KEY_PRIVATE');
        //公钥
        //$this->publickey = C('APP_STATUS') == 'product' ? C('ESCROW_PRODUCI_URL_PUBLIC') : C('ESCROW_TEST_URL_PUBLIC');
        //*********** 存管不同环境下的 私钥 公钥获取 **************//
        $escrow_ras_key    =  C('ESCROW_RAS_KEY');
        $prikey = strtoupper(C('ESC_ENV_STATUS')).'_KEY_PRIVATE';
        $pubkey = strtoupper(C('ESC_ENV_STATUS')).'_KEY_PUBLIC';
        //存管私钥
        $this->privatekey = $escrow_ras_key[$prikey];
        //存管公钥
        $this->publickey =  $escrow_ras_key[$pubkey];
    }

    /**
     *  是否成功
     * @return type
     */
    protected function isSuccessed() {
        return $this->_isSuccessed;
    }

    /**
     * 获取错误信息
     * @return type
     */
    protected function getErrInfo() {
        $format = '%s request java api and errors happens, the result is %s, code is %s, and the request data is %s ';
        return sprintf($format, $this->_platform, $this->_errorMsg, $this->_errorCode, json_encode($this->_param));
    }

    /**
     * 获取 http 的地址
     * @return type
     */
    private function getUrl($param) {
        //网关接口名称数组
        $urlgate = ['PERSONAL_REGISTER_EXPAND',
            'ENTERPRISE_REGISTER',
            'PERSONAL_BIND_BANKCARD_EXPAND',
            'ENTERPRISE_BIND_BANKCARD',
            'UNBIND_BANKCARD',
            'RESET_PASSWORD',
            'CHECK_PASSWORD',
            'MODIFY_MOBILE_EXPAND',
            'ENTERPRISE_INFORMATION_UPDATE',
            'ACTIVATE_STOCKED_USER',
            'RECHARGE',
            'WITHDRAW',
            'USER_PRE_TRANSACTION',
            'USER_AUTHORIZATION',
            'VERIFY_DEDUCT',
        ];
        //对账接口服务名称数组
        $urldown = ['DOWNLOAD_CHECKFILE'];
        //默认直连地址
        $url = $this->_urldir;
        //是否网关地址
//        if (array_key_exists($param['serviceName'], $urlgate)) {
        if (in_array($param['serviceName'], $urlgate)) {
            $url = $this->_urlgate;
        }
        //是否对账文件接口地址
        if (in_array($param['serviceName'], $urldown)) {
            $url = $this->_urldown;
        }
        return $url;
    }

    /**
     * 设置参数
     * @pram mixed param
     */
    private function setParam($param) {
        $this->_param = $param;
    }

    /**
     * 获取参数
     * @return type
     */
    private function getParam() {
        return $this->_param;
    }

    /**
     *  组装http head 信息
     * @param type $request
     * @return type
     */
    private function getHttpHeader($request) {
        //把sessin传过去，现在不要，以后扩展可能用得到
        $session_id = session_id();
        $is_test = $this->_debug === true ? 1 : 0;
        //暂时不要,以后扩展需要
        $sign = $this->createSign($request);
        $header = array(
            "Content-Type: application/json; charset=utf-8",
            "Content-Length: " . strlen($request),
            "API-CLIENT-KEY:{$this->_api_client_key}",
            "API-CLIENT-SESSION-ID:{$session_id}",
            "API-CLIENT-IS-TEST:{$is_test}",
        );
        $header[] = "API-CLIENT-SIGN:{$sign}";
        return $header;
    }

    /**
     * JAVA API REQUEST 接口
     * @param string oper_type api type
     * @param string req_data_no 唯一数据标识
     * @param mixed param 接口其他参数
     *
     */
    public function escrowJavaHttpApi($param = array()) {
        if (!C('ESC_STATUS')) {
            throw new \Exception('存管暂时关闭，请联系技术相关人员！');
        }
        $data = $param;
        $http_header = $this->getHttpHeader(json_encode($data));
        $url = $this->getUrl($param);
        if ($url == $this->_urlgate) {
            $this->_requestgate($url, $http_header, $data);
        } else if ($url == $this->_urldown) {
            $this->_requestFile($url, $http_header, $data);
        } else {
            $this->_request($url, $http_header, $data);
        }
        //保存api接口的log
    }

    /**
     *  网关
     * @param type $url
     * @param type $headers
     * @param type $data
     * @param type $key
     */
    private function _requestgate($url, $headers, $data, $key) {

        // 配置输出调试信息
        $debugStatus = \escrowPara::$debugOutputGateInfo;

        $temp = $data;
        $sHtml = '
            <!DOCTYPE HTML>
            <html>
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
                <title>跳转中...</title>
            </head>
            <body>';
        $sHtml .= "<form id='xinwang' name='xinwang' action='" . $url . "' method='POST'>";
        foreach ($temp as $key => $val) {
            if ($debugStatus) {
                $sHtml .= "$key:&nbsp;&nbsp;&nbsp;&nbsp;<input type='text'  name='" . $key . "' value='" . $val . "'  /><br>";
            } else {
                $sHtml .= "<input type='hidden'  name='" . $key . "' value='" . $val . "'  /><br>";
            }
        }
        $sHtml .= "<input type='submit' value='submit' ".($debugStatus ? '' : 'hidden' ).">";
        $sHtml .= '
            </form>
            <script type="text/javascript">' .
            ($debugStatus ? 'alert(document.getElementById("j_serviceName").name);
                document.forms["xinwang"].submit();' : 'document.forms["xinwang"].submit();')
            . '</script></body></html>';
        echo $sHtml;
    }

    /**
     * @param string url
     * @param mixed http_header
     * @param mixed data
     *
     */
    private function _request($url, $headers, $data, $key) {
        $response = curl_post_with_ssl($url, $headers, $data, FALSE, $this->timeout);
        output('Log/depos.log', date('Y-m-d H:i:s') . '直连返回结果：', $response);
        if ($response['code'] != 200) {
            $this->_isSuccessed = false;
            throw new \Exception('请求失败, data:' . $response['msg']);
        } else {//成功
            $this->_isSuccessed = true;
            $this->_response = $response['data'];
        }
    }

    /**
     * @param $url
     * @param $headers
     * @param $data
     * @param $key
     * @throws \Exception
     */
    private function _requestFile($url, $headers, $data, $key) {
        $response = curl_post_with_ssl($url, $headers, $data, FALSE, $this->timeout);
        $zipFile = SITE_PATH."/Log/bill_" . date('Ymd',strtotime("-1 day")) . ".zip";
        file_put_contents($zipFile, $response['data']);
        output(SITE_PATH.'/Log/depos.log', date('Y-m-d H:i:s') . '直连返回结果：', $response);
        if ($response['code'] != 200) {
            $this->_isSuccessed = false;
            throw new \Exception('请求失败, data:' . $response['msg']);
        } else {//成功
            $this->_isSuccessed = true;
            $this->_response = $zipFile;
        }
    }

    /**
     * JAVA 回调API 接口总处理
     * 回调api的名字(处理service里面的函数名字前缀)
     * @param string name 回调函数名
     * @param string param 参数
     */
    public function escrowJavaCallbackHttpApi($name, $param) {

        $respData = json_decode($param['respData'], 1);
        $logTemp = $param;
        if (is_array($respData) && is_array($param)) {
            $logTemp = array_merge($param, $respData);
        }
        $logTemp['name']               = $name;
        $logTemp['escrow_log_type']    = 'cg_request_log';
        glog(["网关回调流水记录：", $name], $logTemp);

        $callback_name = sprintf('%s%s', $name, '_CallbackApi');
        $this->_response['data'] = call_user_func_array([$this, $callback_name], [$param]);
    }

    /**
     * 返回请求的数据
     * @return mixed
     *
     */
    public function response() {
        return $this->_response;
    }

    /**
     * 创建签名
     * @param $data
     * @return string
     */
    public function createSign($data) {
        ksort($data); //排序
        $param_tmp = '';
        foreach ($data AS $key => $val) {
            $param_tmp .= $key . '=' . htmlspecialchars_decode($val) . '&';
        }

        //服务端加密串
        $sign_check = md5(rtrim($param_tmp, '&') . $this->_secret);

        return $sign_check;
    }
    /*******************************************************************
     *
     *   desc:  以下为 加签订、验签
     *
     ********************************************************************/
    /**
     *  获取最外层公共参数
     * @param type $serviceName
     * @param array $json
     * @return type
     */
    public function getTopParaData($serviceName, $reqData = array()) {
        $data['serviceName'] = $serviceName;
        $data['platformNo'] = getEscPlatformNo(); //平台编号
        //$data['reqData'] = json_encode($reqData);//业务数据报文 在下一层调用用户时组装
        $rData = json_encode($reqData);
        $data['keySerial'] = 1;
        $data['sign'] = $this->ecsSign($rData, $this->privatekey);
        return $data;
    }

    /**
     *  RSA加签
     * @param $data reqData字符串
     * @param $priKey
     * @return string
     */
    public function ecsSign($data, $priKey) {
        $priKey = $this->format_secret_key($priKey ? $priKey : $this -> privatekey, 'pri');
        $passphrase = '';
        $algo = OPENSSL_ALGO_SHA1;
        // 加载私钥
        $privatekey = openssl_pkey_get_private($priKey, $passphrase);

        // 生成摘要
        //$digest = openssl_digest("", $digestAlgo);
        // 签名
        $signature = '';
        openssl_sign($data, $signature, $privatekey, $algo);
        $signature = base64_encode($signature);
        return $signature;
    }

    /**
     * RSA验签
     * @param $data
     * @param $signature
     * @param $pubKey
     * @return bool
     */
    public function verify($data, $signature, $pubKey) {

        $pubKey = $pubKey ? $pubKey : $this->publickey;

        //将字符串格式公私钥转为pem格式公私钥
        $pubKeyPem = $this->format_secret_key($pubKey, 'pub');
        /// 摘要及签名的算法，同上面一致
        $digestAlgo = 'sha512';
        $algo = OPENSSL_ALGO_SHA1;

        // 加载公钥
        $publickey = openssl_pkey_get_public($pubKeyPem);

        // 生成摘要
        //$digest = openssl_digest("", $digestAlgo);
        // 验签
        $verify = openssl_verify($data, base64_decode($signature), $publickey, $algo);
        //返回资源是否成功
        return $verify;
    }

    /**
     * string转pem
     * @param $secret_key
     * @param $type
     * @return string
     */
    private function format_secret_key($secret_key, $type) {
        //64个英文字符后接换行符"\n",最后再接换行符"\n"
        $key = (wordwrap($secret_key, 64, "\n", true)) . "\n";
        //添加pem格式头和尾
        if ($type == 'pub') {
            $pem_key = "-----BEGIN PUBLIC KEY-----\n" . $key . "-----END PUBLIC KEY-----\n";
        } else if ($type == 'pri') {
            $pem_key = "-----BEGIN RSA PRIVATE KEY-----\n" . $key . "-----END RSA PRIVATE KEY-----\n";
        } else {
            echo('公私钥类型非法');
            exit();
        }
        return $pem_key;
    }

    /********************************************
     *
     *      以下为接口参数校验
     *
     *******************************************/

}
