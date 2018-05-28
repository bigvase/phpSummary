<?php
/**
 * 常用静态类，这里主要整理了一些PHP常常会用到的方法。
 */
class C {
  /*
   * 私有处理随机数的内置参数
   * array 随机数数组/param 随机数长度
   * 返回一个随机数
   */
  static private function Random($array , $param) {
    $randArray = $array;
    $randCount = count($randArray);
    $num = intval($param);
    $resultStr = "";
    for($i = 0 ; $i < $num ; $i++){
      $resultStr .= $randArray[rand(0, intval($randCount) - 1)];
    }
    return $resultStr;
  }

  //随机数（数字类型）
  static public function Randnum($param = 8){
    $randArray = str_split("1234567890");
    $resultStr = C::Random($randArray,$param);
    return $resultStr;
  }

  //随机数（混合类型） - 无0
  static public function RandStr($param = 8 , $capslock = FALSE){
    $randArray = str_split("abcdefghijklmnopqrstuvwxyz123456789ABCDEFGHIGKLMNOPQRSTUVWXYZ");
    $resultStr = C::Random($randArray,$param);
    if($capslock){
      return strtoupper($resultStr);
    }
    else {
      return $resultStr;
    }
  }

  //加密字符串
  static public function EnBaseCode($data, $key = "ZCStrong"){
    $key = md5($key);//对于预设的KEY，MD5
    $x  = 0;
    $len = strlen($data);
    $l  = strlen($key);
    for ($i = 0; $i < $len; $i++){
      if ($x == $l){
        $x = 0;
      }
      $char = $key{$x};
      $x++;
    }
    for ($i = 0; $i < $len; $i++){
      $str = chr(ord($data{$i}) + (ord($char{$i})) % 256);
    }
    return base64_encode($str);
  }

  //机密字符串
  static public function DeBaseCode($data, $key = "ZCStrong"){
    $key = md5($key);
    $x = 0;
    $data = base64_decode($data);
    $len = strlen($data);
    $l = strlen($key);
    for ($i = 0; $i < $len; $i++){
      if ($x == $l){
        $x = 0;
      }
      $char = substr($key, $x, 1);
      $x++;
    }
    for ($i = 0; $i < $len; $i++){
      if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))){
        $str = chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
      }
      else{
        $str = chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
      }
    }
    return $str;
  }

  //正则手机号 /^((1[3,5,8][0-9])|(14[5,7])|(17[0,6,7,8]))\\d{8}$/
  static public function RegularPhone($string){
    $resultStr = preg_match("/^((1[3,5,8][0-9])|(14[5,7])|(17[0,6,7,8]))\\d{8}$/",$string);
    if(intval($resultStr) == 1){
      return TRUE;
    }
    else{
      return FALSE;
    }
  }

  //正则邮箱
  static public function RegularEmail($string){
    $resultStr = preg_match("/^([0-9A-Za-z\\\\-_\\\\.]+)@([0-9a-z]+\\\\.[a-z]{2,3}(\\\\.[a-z]{2})?)$/i",$string);
    if(intval($resultStr) == 1){
      return TRUE;
    }
    else{
      return FALSE;
    }
  }

  //正则验证身份证/(^([d]{15}|[d]{18}|[d]{17}x)$)/
  static public function RegularIdCard($string){
    $resultStr = preg_match("/(^([d]{15}|[d]{18}|[d]{17}x)$)/",$string);
    if(intval($resultStr) == 1){
      return TRUE;
    }
    else{
      return FALSE;
    }
  }

  //处理字符串信息
  static public function hStr($string){
    if(isset($string) && !empty($string)){
      return addslashes(strip_tags($string));
    }
    else{
      return "";
    }
  }
}