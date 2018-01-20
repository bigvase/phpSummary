<?php
class RSAOpenssl{
	/**
	 * 对明文进行加密
	 * @param $data 要加密的明文
	 */
	public static function encrypt($data, $cert_file) {
		if (! File_exists ( $cert_file )) {
			throw new Exception( "未找到密钥,请检查配置!" );
		}
		$fp = fopen ( $cert_file, "r" );
		$public_key = fread ( $fp, 8192 );
		fclose ( $fp );
		$public_key = openssl_get_publickey ( $public_key );
		//private encrypt
		openssl_public_encrypt ( $data, $crypttext, $public_key );
		//加密後產生出參數$crypttext
		//public decrypt
		//openssl_public_encrypt ( $crypttext, $newsource, $public_key );
		$encryptDate = base64_encode ( $crypttext );
		//解密後的結果$newsource
		return $encryptDate;
	}
	
	/**
	 * 密文解密
	 * @param $data 要解密的密文
	 * @param $data 商户号
	 */
	public static function decrypt($data, $priv_key_file) {
		if(!File_exists($priv_key_file)){
// 			return FALSE;
			throw new Exception("The key is not found, please check the configuration!");
		}
		$fp = fopen ( $priv_key_file, "r" );
		$private_key = fread ( $fp, 8192 );
		fclose ( $fp );
		$private_key = openssl_get_privatekey ( $private_key );
		openssl_private_decrypt( base64_decode($data), $decrypted, $private_key );
		$decryptDate =iconv("GBK", "UTF-8", $decrypted);
		return $decryptDate;
	}
	
	/**
	 * 数据签名
	 * @param $plain	签名明文串
	 * @param $priv_key_file	商户租钥证书
	 */
	public static function sign($plain,$priv_key_file){
		try{
			if(!File_exists($priv_key_file)){
				throw new Exception("The key is not found, please check the configuration!");
			}
			$fp = fopen($priv_key_file, "rb");
	
			$priv_key = fread($fp, 8192);
			@fclose($fp);
			$pkeyid = openssl_get_privatekey($priv_key);
			if(!is_resource($pkeyid)){ return FALSE;}
			// compute signature
			@openssl_sign($plain, $signature, $pkeyid);
			// free the key from memory
			@openssl_free_key($pkeyid);
			return base64_encode($signature);
		}catch(Exception $e){
			throw new Exception("Signature attestation failure".iconv('GBK','UTF-8',$e->getMessage()));
		}
	}
	
	/**
	 * 签名数据验签
	 * @param $plain 验签明文
	 * @param $signature 验签密文
	 */
	public static function verify($plain,$signature, $cert_file){
		if(!File_exists($cert_file)){
			throw new Exception("未找到密钥,请检查配置!");
		}
		$signature = base64_decode($signature);
	
		$fp = fopen($cert_file, "r");
		$cert = fread($fp, 8192);
		fclose($fp);
	
		$pubkeyid = openssl_get_publickey($cert);
		if(!is_resource($pubkeyid)){
			return FALSE;
		}
		$ok = openssl_verify($plain,$signature,$pubkeyid);
		@openssl_free_key($pubkeyid);
		if ($ok == 1) {//1
			return TRUE;
		} elseif ($ok == 0) {//2
			return FALSE;
		} else {//3
			return FALSE;
		}
		return FALSE;
	}
}

