<?php
/**
 * H端发送邮件 暂只提供拼接头尾
 * Created by PhpStorm.
 * User: lm
 * Date: 15-4-9
 * Time: 下午7:46
 */
class BclientEmail{
    
    static public function wrapEmail($mail_body){
        $prefix = self::setEmailPrefix();
        $suffix = self::setEmailSuffix();
        return $prefix.$mail_body.$suffix;
    }

    // 设置邮件头部HTML
    static function setEmailPrefix()
    {
            $prefix = <<<DOC
<meta charset="UTF-8">
<style>
html, body, p, td,dl,dd{
  margin: 0;
  padding: 0;
  font: 16px/normal Microsoft Yahei;
}
	*{margin:0;padding:0; text-indent:0;}
	img{vertical-align:middle;border:0;}
	em{font-style: normal;}
	p{
	margin:0;
	padding:5px 0;
	line-height:30px;
	}
</style>
<table border="0" cellpadding="0" cellspacing="0" style="width:800px;padding:0;margin:0 auto;color:#333">
	<tr>
		<td style="text-align:center"><img src="http://img.ifsc.com.cn/yrzmail/20140702/edm02.png" width="800" height="76" alt="融资端"></td>
	</tr>
	<tr>
		<td style="padding:40px 10px">
		<dl style="margin: 0; padding:0;">
		 <dd style="font:16px/normal Simhei;color:#333333; padding:0; margin:0;">
DOC;
        return $prefix;
    }

    // 设置邮件尾部HTML
    // 设置邮件尾部HTML
    static function setEmailSuffix($s = NULL)
    {
        $suffix = <<<DOC
        </dd>
			</dl>
		</td>
	</tr>
</table>
DOC;
        return $suffix;
    }
}