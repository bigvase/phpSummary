<?php
class TripleDES {
private $key = "";
    private $iv = 0;
    private $is_pkcs7 = true;
    private $algo = MCRYPT_3DES;
    private $mode = MCRYPT_MODE_ECB;
    /**
    * 构造，传递二个已经进行base64_encode的KEY与IV
    *
    * @param string $key
    * @param string $iv
    */
    function __construct ($key)
    {
        $this->key = $key;
    }
    
    public function setIsPkcs7($is=true){
    	$this->is_pkcs7 = $is;
    }
    
    public function setAlgo($algo){
    	$this->algo = $algo;
    }
    
    public function setMode($mode){
    	$this->mode = $mode;
    }
 
    /**
    *加密
    * @param <type> $value
    * @return <type>
    */
    public function encrypt ($value)
    {
        $td = mcrypt_module_open($this->algo, '', $this->mode, '');
        if($this->is_pkcs7) $value = $this->PaddingPKCS7($value);
        $key = base64_decode($this->key);
        mcrypt_generic_init($td, $key, $this->iv);
        $ret = base64_encode(mcrypt_generic($td, $value));
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return $ret;
    }
 
    /**
    *解密
    * @param <type> $value
    * @return <type>
    */
    public function decrypt ($value)
    {
        $td = mcrypt_module_open($this->algo, '', $this->mode, '');
        $key = base64_decode($this->key);
        mcrypt_generic_init($td, $key, $this->iv);
        $ret = trim(mdecrypt_generic($td, base64_decode($value)));
        if($this->is_pkcs7) $ret = $this->UnPaddingPKCS7($ret);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return $ret;
    }
 
    private function PaddingPKCS7 ($data)
    {
        $block_size = mcrypt_get_block_size('tripledes', 'cbc');
        $padding_char = $block_size - (strlen($data) % $block_size);
        $data .= str_repeat(chr($padding_char), $padding_char);
        return $data;
    }
 
    private function UnPaddingPKCS7($text)
    {
        $pad = ord($text{strlen($text) - 1});
        if ($pad > strlen($text)) {
            return false;
        }
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
            return false;
        }
        return substr($text, 0, - 1 * $pad);
    } 
}

?>