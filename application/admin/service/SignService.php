<?php
namespace app\admin\service;
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

    public function __construct($publicKey,$privateKey)
    {
        //读配置文件
        $this->privateKey = $privateKey;
        $this->publicKey  = $publicKey;
        if(empty($this->privateKey) || empty($this->publicKey)){
            exception('key not null ~~');
        }
        try{
            if($this->is_file){
                if(!file_exists($this->public_key_file) || !file_exists($this->private_key_file)) {
                    exception('key file no exists');
                }
                if (false == ($this->public_key = file_get_contents($this->public_key_file)) || false == ($this->private_key = file_get_contents($this->private_key_file))) {
                    exception('read key file fail');
                }
                if(false == ($this->public_key_resource = $this->is_bad_public_key($this->publicKey)) || false == ($this->private_key_resource = $this->is_bad_private_key($this->privateKey))) {
                    exception('public key or private key no usable');
                }
            }
            if(false == ($this->public_key_resource = $this->is_bad_public_key($this->publicKey)) || false == ($this->private_key_resource = $this->is_bad_private_key($this->privateKey))) {
                exception("public key or private key no usable");
            }

        }catch(Exception $e){
            exception('system error!');
        }

    }

    /**
     * 判断公钥是否有效
     * @param $public_key
     * @return resource
     */
    private function is_bad_public_key($public_key) {
        return openssl_pkey_get_public($public_key);
    }

    /**
     * 判断私钥是否有效
     * @param $private_key
     * @return bool|resource
     */
    private function is_bad_private_key($private_key) {
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
    public function private_encrypt($input) {
        openssl_private_encrypt($input,$output,$this->private_key_resource);
        return base64_encode($output);
    }
    /**
     * 解密 私密钥加密后的密文
     */
    public function public_decrypt($input) {
        openssl_public_decrypt(base64_decode($input),$output,$this->public_key_resource);
        return $output;
    }
    /**
     * 用公密钥加密
     */
    public function public_encrypt($input) {
        openssl_public_encrypt($input,$output,$this->public_key_resource);
        return base64_encode($output);
    }
    /**
     * 解密 公密钥加密后的密文
     */
    public function private_decrypt($input) {
        openssl_private_decrypt(base64_decode($input),$output,$this->private_key_resource);
        return $output;
    }
//测试方法
//$demo = service("Home/Demo");
//$key = $demo->create_key();
//dump($key);die;
//$str = '加密解密';
//$str = $demo->public_encrypt($str); //用公密钥加密
//echo $str.'</br>';
//$str = $demo->private_decrypt($str); //用私密钥解密
//echo $str.'</br>';
//    //=============================================================
//$str = $demo->private_encrypt($str); //用丝密钥加密
//echo $str.'</br>';
//$str = $demo->public_decrypt($str); //用公密钥解密
//echo $str.'</br>';
//die;

}