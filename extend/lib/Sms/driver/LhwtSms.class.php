<?php
class LhwtSms{
    
    private $sendSmsUrl = 'http://sdk.univetro.com.cn:6200/sdkproxy/sendsms.action';
    private $queryBalanceUrl = 'http://sdk.univetro.com.cn:6200/sdkproxy/querybalance.action';
    private $cdkey = '7SDK-LHW-0588-PFQUR';
    private $password = '357580';
    
    /**
     * 发送短信
     */
    public function sendSms($mobiles, $content){
        $mobiles = is_array($mobiles) ? $mobiles : array($mobiles);
        if (!$mobiles){
            return false;
        }
        $mobiles = implode(',', $mobiles);
        Import("libs.Http", ADDON_PATH);
        $postData = array(
                'cdkey' => $this->cdkey, 
                'password' => $this->password,
                'phone' => $mobiles,
                'message' => $content
        );
        $return = Http::postUrl($this->sendSmsUrl, http_build_query($postData));
        preg_match_all('/\<error\>(.+)\<\/error\>/', $return, $arr);
        return $arr[1][0];
    }
    
    /**
     * 查询余额
     */
    public function getBalance(){
        Import("libs.Http", ADDON_PATH);
        $postData = array(
                'cdkey' => $this->cdkey, 
                'password' => $this->password,
        );
        $return = Http::postUrl($this->queryBalanceUrl, http_build_query($postData));
        preg_match_all('/\<message\>(.+)\<\/message\>/', $return, $arr);
        return $arr[1][0];
    }
}
