<?php
namespace app\admin\service;
use think\Config;
use think\Exception;

/**
 * Created by PhpStorm.
 * User: bigsave
 * Date: 2018/1/18
 * Time: 11:13
 */
class SignService
{
    private $privateKey;//私钥
    private $publicKey;//公钥
    private $public_key_file;//地址
    private $private_key_file;
    private $is_file = 0;
    private $public_key_resource;//资源标志
    private $private_key_resource;

    public function __construct()
    {
        //读配置文件
//        $this->privateKey = privateKey;
//        $this->publicKey  = $publicKey;
//        if(empty($this->privateKey) || empty($this->publicKey)){
//            exception('key not null ~~');
//        }
//        try{
//            if($this->is_file){
//                if(!file_exists($this->public_key_file) || !file_exists($this->private_key_file)) {
//                    exception('key file no exists');
//                }
//                if (false == ($this->public_key = file_get_contents($this->public_key_file)) || false == ($this->private_key = file_get_contents($this->private_key_file))) {
//                    exception('read key file fail');
//                }
//                if(false == ($this->public_key_resource = $this->is_bad_public_key($this->publicKey)) || false == ($this->private_key_resource = $this->is_bad_private_key($this->privateKey))) {
//                    exception('public key or private key no usable');
//                }
//            }
//            if(false == ($this->public_key_resource = $this->is_bad_public_key($this->publicKey)) || false == ($this->private_key_resource = $this->is_bad_private_key($this->privateKey))) {
//                exception("public key or private key no usable");
//            }
//
//        }catch(Exception $e){
//            exception('system error!');
//        }

    }

    /**
     * 判断公钥是否有效
     * @param $public_key
     * @return resource
     */
    private function is_bad_public_key($public_key,$pem = 1) {
        if(!$pem) $public_key = $this->format_secret_key($public_key,'pub');
        return openssl_pkey_get_public($public_key);
    }

    /**
     * 判断私钥是否有效
     * @param $private_key
     * @return bool|resource
     */
    private function is_bad_private_key($private_key,$pem = 1) {
        if(!$pem) $private_key = $this->format_secret_key($private_key,'pri');
        return openssl_pkey_get_private($private_key);
    }
    /**
     * 生成一对公私密钥 成功返回 公私密钥数组 失败 返回 false
     */
    public function create_key() {
        //需要配置文件
        $res = openssl_pkey_new();
        if($res == false) return false;
        openssl_pkey_export($res, $private_key);
        $public_key = openssl_pkey_get_details($res);
        return array('public_key'=>$public_key["key"],'private_key'=>$private_key);
    }
    /**
     * 用私密钥加密
     */
    public function private_encrypt($input,$privateKey = '') {
        $privateKey = $privateKey == '' ?  $this->private_key_resource : $privateKey;
        $key  = $this->is_bad_private_key($privateKey) ? $privateKey : $this->format_secret_key($privateKey,'pri');
        if(!$this->is_bad_private_key($key)) exception('私密钥无效！');

        openssl_private_encrypt($input,$output,$key);
        return base64_encode($output);
    }
    /**
     * 解密 私密钥加密后的密文
     */
    public function public_decrypt($input,$publicKey = '') {
        $publicKey = $publicKey == '' ?  $this->public_key_resource : $publicKey;

        $key  = $this->is_bad_public_key($publicKey) ? $publicKey : $this->format_secret_key($publicKey,'pub');

        if($this->is_bad_public_key($key)) exception('公钥无效！');

        openssl_public_decrypt(base64_decode($input),$output,$key);
        return $output;
    }
    /**
     * 用公密钥加密
     */
    public function public_encrypt($input,$publicKey = '') {
        $publicKey = $publicKey == '' ?  $this->public_key_resource : $publicKey;
        $key  = $this->is_bad_public_key($publicKey) ? $publicKey : $this->format_secret_key($publicKey,'pub');
        if($this->is_bad_public_key($key)) exception('公钥无效！');

        openssl_public_encrypt($input,$output,$key);
        return base64_encode($output);
    }
    /**
     * 解密 公密钥加密后的密文
     */
    public function private_decrypt($input,$privateKey = '') {
        $privateKey = $privateKey == '' ?  $this->private_key_resource : $privateKey;
        $key  = $this->is_bad_private_key($privateKey) ? $privateKey : $this->format_secret_key($privateKey,'pri');
        if(!$this->is_bad_private_key($key)) exception('私密钥无效！');

        openssl_private_decrypt(base64_decode($input),$output,$key);
        return $output;
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

    /**
     * RSA验签
     * @param $data
     * @param $signature
     * @param $pubKey
     * @return bool
     */
    public function verify($data, $signature, $pubKey) {

        $pubKey = $pubKey ? $pubKey : $this->publicKey;

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


    /***
     * 参数AES加密
     *
     * @param $param
     * @return string
     */
    public function param_aes_encode($param, $privateKey, $iv) {

        if (empty($param)) return $param;

        $privateKey = $privateKey ? $privateKey : Config::get('AES_KEY');
        $privateKey = $this->format_secret_key($privateKey,'pri');
        $iv = $iv ? $iv : Config::get('AES_VI');
        return strtoupper(bin2hex(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $privateKey, $param, MCRYPT_MODE_CBC, $iv))));
    }

    /***
     * 参数AES解密
     *
     * @param $param
     * @return string
     */
    public function param_aes_decode($param, $privateKey, $iv) {

        if (empty($param)) return $param;

        $privateKey = $privateKey ? $privateKey : Config::get('AES_KEY');
        $iv = $iv ? $iv : Config::get('AES_VI');

        return trim(''.(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $privateKey, base64_decode(pack('H*', $param)), MCRYPT_MODE_CBC, $iv)));
    }
}