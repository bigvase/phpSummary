<?php

//邮件发送类,基于PHPMailer类
class Email
{
    static public $config; //存储配置的静态变量
    static $email_prefix = NULL;
    static $email_suffix = NULL;

    //设定邮件参数
    static public function init($config = array())
    {
        $config = $config ? $config : getSysData("email", "smtp_config");
        self::$config['SMTP_HOST'] = isset($config['SMTP_HOST']) ? $config['SMTP_HOST'] : 'smtp.qq.com'; //smtp服务器地址
        self::$config['SMTP_PORT'] = isset($config['SMTP_PORT']) ? $config['SMTP_PORT'] : 25; //smtp服务器端口
        self::$config['SMTP_SSL'] = isset($config['SMTP_SSL']) ? $config['SMTP_SSL'] : false; //是否启用SSL安全连接	，gmail需要启用sll安全连接
        self::$config['SMTP_USERNAME'] = isset($config['SMTP_USERNAME']) ? $config['SMTP_USERNAME'] : ''; //smtp服务器帐号，如：你的qq邮箱
        self::$config['SMTP_PASSWORD'] = isset($config['SMTP_PASSWORD']) ? $config['SMTP_PASSWORD'] : ''; //smtp服务器帐号密码，如你的qq邮箱密码
        self::$config['SMTP_AUTH'] = isset($config['SMTP_AUTH']) ? $config['SMTP_AUTH'] : true; //启用SMTP验证功能，一般需要开启
        self::$config['SMTP_CHARSET'] = isset($config['SMTP_CHARSET']) ? $config['SMTP_CHARSET'] : 'utf-8'; //发送的邮件内容编码
        self::$config['SMTP_FROM_TO'] = isset($config['SMTP_FROM_TO']) ? $config['SMTP_FROM_TO'] : ''; //发件人邮件地址
        self::$config['SMTP_FROM_NAME'] = isset($config['SMTP_FROM_NAME']) ? $config['SMTP_FROM_NAME'] : ''; //发件人姓名
        self::$config['SMTP_DEBUG'] = FALSE; //不显示调试信息
    }

    // 设置邮件头部HTML
    static function setEmailPrefix($s = NULL)
    {
        if (is_null($s)) {
            $s = <<<DOC
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>EDM</title>
</head>
<body>
<table width="700" border="0" cellspacing="0" cellpadding="0" align="center">
  <tbody>
  <tr>
    <td style=" background:url(https://www.tourongjia.com/public/images/email/edm_bk_bg.png) no-repeat;" height="1186">
    	<table width="700" border="0" style="text-align:center">
          <tbody><tr>
            <td style="padding:66px 0px 80px 0px; "><img src="https://www.tourongjia.com/public/images/email/edm_01.png"></td>
          </tr>
        </tbody></table>
        <table width="700" border="0">
              <tbody><tr>
                <td style=" padding:0px 32px 25px 32px;font-size:16px; line- text-indent:2em;">
DOC;
        }
        self::$email_prefix = $s;
    }

    // 设置邮件尾部HTML
    static function setEmailSuffix($s = NULL)
    {
        if (is_null($s)) {
            $s = <<<DOC
             </td>
              </tr>
        </tbody></table>
         <table width="700" border="0">
              <tbody>
              <tr>
<td style="padding-left:8px;"><img src="https://www.tourongjia.com/public/images/email/edm_02.png" width="680" border="0" usemap="#Map"></td>
             </tr>
            </tbody></table>

    </td>
  </tr>
  <tr>
    <td style="background-color:#fff; padding:10px 0px; font-size: 14px;" align="left">如果邮件无法正常显示，请点击<a style="text-decoration: underline;" href="#" target="email">这里</a>浏览。</td>
  </tr>
</tbody></table>



<map name="Map" id="Map">
  <area shape="rect" coords="143,240,328,60"  href="http://www.tourongjia.com"  target="_blank"/>
</map>
</body>
</html>
DOC;
        }
        self::$email_suffix = $s;
    }

    // 适用HTML包装邮件
    static function wrapEmail($body)
    {
        if (is_null(self::$email_prefix)) self::setEmailPrefix();
        if (is_null(self::$email_suffix)) self::setEmailSuffix();
        return self::$email_prefix . $body . self::$email_suffix;
    }

    public static function wrapTest(){
        $body = "测试";
        $content = self::$email_prefix . $body . self::$email_suffix;
        echo $content;

    }


    //发送邮件
    static public function send($mail_to, $mail_subject, $mail_body, $mail_attach = NULL, $userTpl = 1,$api_key = '', $client = '')
    {
        if(empty($api_key)){
            $api_key = BaseModel::getApiKey('api_key');
        }
        $userTpl_value = 0;
        if ($userTpl && $api_key == '1234567890') {
            $userTpl_value = 1; //若接入点是xxx，会发送一系列图片
        }

        //邮箱设置开关
        if (C('IS_CLOSE_EMAIL')) return true;
        try {
            $key = "XIN_HE_HUI_EMAIL_QUEUE";
            import_addon("libs.Cache.RedisList");
            $redisObj = RedisList::getInstance($key);
            $data = array();
            $data['mail_to'] = $mail_to;
            $data['mail_subject'] = $mail_subject;
            $data['mail_body'] = $mail_body;
            $data['mail_attach'] = $mail_attach;
            $data['userTpl'] = $userTpl_value;
            $data['api_key'] = $api_key; //记录邮件的api_key
            $data['client'] = $client;
            return $redisObj->lPush($data);
        } catch (Exception $e) {
            return errorReturn($e->getMessage());
        }
    }

    //发送邮件
    static public function realsend($mail_to, $mail_subject, $mail_body, $mail_attach = NULL, $userTpl = 1, $api_key = '1234567890', $client = '')
    {
        // $mail_body = "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"><div>".$mail_body."</div>";

        if ($userTpl) {
            $mail_body = preg_replace("/<p\s*(style=\".*?\")/", "<p ", $mail_body);
            if ($client) {
                $service_name = ucfirst($client) . 'Email';
                import_addon("libs.Email.Client.{$service_name}");
                $mail_body = $service_name::wrapEmail($mail_body);
            } else {
                $mail_body = self::wrapEmail($mail_body);
            }
        }
        try {
            BaseModel::setApiKey($api_key, '', ''); //邮件需要根据接点不同使用不同配置文件发送。
            self::init(); //每次要读配置了。以后要改成邮件队列根据接入点分类，这样可以少读配置。
            @error_reporting(E_ERROR | E_WARNING | E_PARSE); //屏蔽出错信息
            require_once(dirname(__FILE__) . '/phpmailer/class.phpmailer.php');
            $mail = new PHPMailer();
            //没有调用配置方法，则调用一次config方法

            if (!isset(self::$config) || empty(self::$config)) {
                self::config(); //奇怪！这里哪里有config方法？——LSY注
                self::Find_the_function_config(); //反正不执行到不报错
            }
            $mail->IsSMTP(); //// 使用SMTP方式发送
            $mail->Host = self::$config['SMTP_HOST']; //smtp服务器地址
            $mail->Port = self::$config['SMTP_PORT']; //smtp服务器端口
            $mail->Username = self::$config['SMTP_USERNAME']; //smtp服务器帐号，
            $mail->Password = self::$config['SMTP_PASSWORD']; // smtp服务器帐号密码
            $mail->SMTPAuth = self::$config['SMTP_AUTH']; //启用SMTP验证功能，一般需要开启
            $mail->CharSet = self::$config['SMTP_CHARSET']; //发送的邮件内容编码
            $mail->SetFrom(self::$config['SMTP_FROM_TO'], self::$config['SMTP_FROM_NAME']); // 发件人的邮箱和姓名
            $mail->AddReplyTo(self::$config['SMTP_FROM_TO'], self::$config['SMTP_FROM_NAME']); // 回复时的邮箱和姓名，一般跟发件人一样
            //是否启用SSL安全连接
            if (self::$config['SMTP_SSL']) {
                $mail->SMTPSecure = "ssl"; //gmail需要启用sll安全连接
            }
            //开启调试信息
            if (self::$config['SMTP_DEBUG']) {
                $mail->SMTPDebug = 1;
            }

            $mail->Subject = $mail_subject; //邮件标题
            $mail->MsgHTML($mail_body); //邮件内容，支持html代码
//            p($mail); //调试
            //发送邮件
            if (is_array($mail_to)) {
                //同时发送给多个人
                foreach ($mail_to as $key => $value) {
                    $mail->AddAddress($value, ""); // 收件人邮箱和姓名
                }
            } else { //只发送给一个人
                $mail->AddAddress($mail_to, ""); // 收件人邮箱和姓名
            }

            //发送多个附件
            if (is_array($mail_attach)) {
                foreach ($mail_attach as $value) {
                    if (file_exists($value)) //附件必须存在，才会发送
                    {
                        $mail->AddAttachment($value); // attachment
                    }
                }
            }
            //发送一个附件
            if (!empty($mail_attach) && is_string($mail_attach)) {

                if (file_exists($mail_attach)) //附件必须存在，才会发送
                {
                    $mail->AddAttachment($mail_attach); //发送附件
                }
            }

            if (!$mail->Send()) {
                if (self::$config['SMTP_DEBUG']) {
                    echo "Mailer Error: " . $mail->ErrorInfo;
                }
                return false;
            } else {
                return true;
            }
        } catch (Exception $e) {
            if (self::$config['SMTP_DEBUG']) {
                echo "Mailer Error: " . $e->getMessage();
            }
            return false;
        }
    }
}

?>
