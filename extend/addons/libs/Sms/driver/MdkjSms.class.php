<?php
class MdkjSms
{
    public $_flag = 0;
    public $_argv = array();
    public $_errno = 1;
    public $_params = '';
    public $_errstr = 'can not open sockect';
    public $_sockect = '';

    private $_sn = 'SDK-WSS-010-06866';
    private $_pwd = '874]]8]-';
    private $_mobile = '';
    private $_content = '';
    private $_ext = '';
    private $_rrid = '';
    private $_stime = '';


    /*
    * return md5 password
    */
    private function Md5smspwd()
    {
        return strtoupper(md5($this->_sn . $this->_pwd));
    }

    /*
    * change content to gb2312
    */
    private function Changecontent()
    {
        return iconv("UTF-8", "gb2312//IGNORE", $this->_content);
    }

    /*
    * return array
    */
    private function Getarr()
    {
        $this->_argv = array(
            'sn' => $this->_sn,
            'pwd' => $this->Md5smspwd(),
            'mobile' => $this->_mobile,
            'content' => $this->Changecontent(),
            'ext' => $this->_ext,
            'rrid' => $this->_rrid,
            'stime' => $this->_stime,
            );
        return $this->_argv;
    }


   
    private function Getarr2()
    {
        $this->_argv = array(
            'sn' => $this->_sn,
            'pwd' => $this->_pwd,
            );
        return $this->_argv;
    }
	
	/*
	*扫描主域是否可以连接
	**/
	private function Get_sockect()
	{
		
		for($j=0;$j<3;$j++)
		{
			$fp = @fsockopen("sdk2.entinfo.cn", 8060, $errno, $errstr, 30);
			$timeb=time();
			if(!$fp)
			{
				$timee=time();
				$r=$timee-$timeb;
				if($r<10) sleep($r);
			}
			else
			{
			  $sockect = 1;
			  break;
			}
		  	
		}
		return  $sockect;
	}
    /*
    * send sms 发送短信
    */
    public function Sendsms($mobile, $content, $ext, $rrid, $stime)
    {

        $this->_content = $content;
        $this->_mobile = $mobile;
        $this->_stime = $stime;
        $this->_ext = $ext;
        $this->_rrid = $rrid;
        //print_r($this->Getarr());
        foreach ($this->Getarr() as $key => $value)
        {
            if ($this->_flag != 0)
            {
                $this->_params .= "&";
                $this->_flag = 1;
            }
            $this->_params .= $key . "=";
            $this->_params .= urlencode($value);
            $this->_flag = 1;
        }
        $length = strlen($this->_params);
        if ($length == 0)
        {
            return false;
        }
		
		
		if($this->Get_sockect())
		{
			$this->_sockect = "sdk2.entinfo.cn";
		}else{
			$this->_sockect = "sdk.entinfo.cn";
		}

		
		
        $fp = @fsockopen($this->_sockect, 8060, $this->_errno, $this->_errstr, 30) or exit($this->_errstr."  ---> ".$this->_errno.$this->_sockect);  
        

        $header = "POST /webservice.asmx/mt HTTP/1.1\r\n";
        $header .= "Host:" . $this->_sockect . "\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-Length: " . $length . "\r\n";
        $header .= "Connection: Close\r\n\r\n";
        //添加post的字符串
        $header .= $this->_params . "\r\n";
        //发送post的数据
        fputs($fp, $header);
        $inheader = 1;
        while (!feof($fp))
        {
            $line = fgets($fp, 1024); //去除请求包的头只显示页面的返回数据
            if ($inheader && ($line == "\n" || $line == "\r\n"))
            {
                $inheader = 0;
            }
            if ($inheader == 0)
            {
                //echo $line.'<br>';
            }
        }
        preg_match('/<string xmlns=\"http:\/\/tempuri.org\/\">(.*)<\/string>/', $line, $str);
        $result = explode("-", $str[1]);

        if (count($result) > 1)
        {

            return $str[1];

        } else
        {

            return true;
        }

    }

    //获取短信条数
    public function Balance()
    {
        $this->_params = '';
        foreach ($this->Getarr2() as $key => $value)
        {
            if ($this->_flag != 0)
            {
                $this->_params .= "&";
                $this->_flag = 1;
            }
            $this->_params .= $key . "=";
            $this->_params .= urlencode($value);
            $this->_flag = 1;
        }
        $length = strlen($this->_params);
        if ($length == 0)
        {
            return false;
        }
        
        if($this->Get_sockect())
		{
			$this->_sockect = "sdk2.entinfo.cn";
		}else{
			$this->_sockect = "sdk.entinfo.cn";
		}

		
		
        $fp = @fsockopen($this->_sockect, 8060, $this->_errno, $this->_errstr, 30) or exit($this->_errstr." ---> ".$this->_errno.$this->_sockect);  

        $header = "POST /webservice.asmx/GetBalance HTTP/1.1\r\n";
        $header .= "Host:" . $this->_sockect . "\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-Length: " . $length . "\r\n";
        $header .= "Connection: Close\r\n\r\n";
        //添加post的字符串
        $header .= $this->_params . "\r\n";
        //发送post的数据
        fputs($fp, $header);
        $inheader = 1;
        while (!feof($fp))
        {
            $line = fgets($fp, 1024); //去除请求包的头只显示页面的返回数据
            if ($inheader && ($line == "\n" || $line == "\r\n"))
            {
                $inheader = 0;
            }
            if ($inheader == 0)
            {
                //echo $line.'<br>';
            }
        }
        preg_match('/<string xmlns=\"http:\/\/tempuri.org\/\">(.*)<\/string>/', $line, $str);
        return $str[1];
    }


}
