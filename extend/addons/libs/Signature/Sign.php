<?php
namespace Addons\Libs\Signature;
header("Content-type: text/html; charset=utf-8"); //设置输出编码格式
//echo 'phpversion ', phpversion(), '\r\n<br/>';
//自动捕获异常,可以看情况是否需要
set_exception_handler(function ($e) {
    file_put_contents(__DIR__ . '/exception.log', date('Y-m-d H:i:s') . ' - ' . $e . PHP_EOL . PHP_EOL, FILE_APPEND);
});
include("eSignOpenAPI.php");

use Signature\core\eSign;
use Signature\constants\PersonArea;
use Signature\constants\PersonTemplateType;
use Signature\constants\OrganizeTemplateType;
use Signature\constants\SealColor;
use Signature\constants\UserType;
use Signature\constants\OrganRegType;
use Signature\constants\SignType;
use Signature\constants\LicenseType;
use Signature\core\Util;
use Signature\core\Upload;
//签章自定义的配置文件
use Signature\constants\SignParam;

class Sign {

    const STATUS_SUCCESS = 1;
    const STATUS_FAILED = 2;
    const STATUS_ERROR = 3;
    const STATUS_DISABLE = 4;

    protected static $signature = '【小鸡理财】';
    protected static $signature_new = '';
    protected static $mi_no = '1234567890';
    //
    private static $esign = null;
    private $pre = 'tc_';
    //本类的链接资源
    private static $_instance = null;

    public static function getInstance(){
        if(!self::$esign){
            try {
                self::$esign = new eSign();
            } catch (\Exception $e) {
                throw new \Exception(Util::jsonEncode($e));
            }
        }
        if(!self::$_instance){
            self::$_instance = new Sign();
        }
        return self::$_instance;
    }

    /**
     * 初始化和登录
     * 使用场景：配置文件变更，调用此方法使其生效
     * @return mixed|string
     */
    function init() {
        $iRet = self::$esign->init();
        if (0 === $iRet) {
            $ret = array(
                "errCode" => 0,
                "msg" => "初始化成功",
                "errShow" => true
            );
        }else{
            $ret = array(
                "errCode" => -1,
                "msg" => "初始化失败",
                "errShow" => true
            );
        }
        return Util::jsonEncode($ret);

    }

    /**
     *  获取个人信息
     * @global type $esign
     * 
     */
    function getAccountInfo($uid) {

        $idNo = M('member_info')->where(['uid'=>$uid])->getField("idcard");
        if(!$idNo){
            return ['errCode'=>-1,'msg'=>'用户身份证号码不存在，uid='.$uid];
        }
        //11是大陆
        $ret = self::$esign->getAccountInfoByIdNo($idNo, 11);
        return $ret;
    }

    /**
     * 添加个人账号
     * @param $uid
     * @return array
     * $mobile, $name, $idNo, $personarea='', $email='', $organ='', $title='', $address=''
     */
    public function addPersonAccount($uid) {
        //todo 判断是否有账号
        $member = M('member as m')
            ->field("m.realname,m.cellphone,mi.idcard,ms.id_status")
            ->join($this->pre."member_info as mi on mi.uid=m.id")
            ->join($this->pre."member_status as ms on ms.uid=m.id")
            ->where(['m.id'=>$uid])
            ->find();
        if(!$member) return ['errCode'=>-1,'msg'=>'不存在该用户'];
        // todo 借款人不维护id_status字段？
//        if($member['id_status'] !=1) return ['errCode'=>-1,'msg'=>'该用户没有实名验证'];

        if(empty($member['cellphone']) || empty($member['realname']) || empty($member['idcard'])) return ['errCode'=>-1,'msg'=>'用户信息不全'];
        $user_uuid = M('fi_sign_user')->where(['uid'=>$uid])->find();
        if($user_uuid && $user_uuid['user_uuid']) return ['errCode'=>-1,'msg'=>'该用户已经添加的,无需再次添加'];
        //发送请求
        $ret = self::$esign->addPersonAccount($member['cellphone'], $member['realname'], $member['idcard'], $personarea = PersonArea::MAINLAND, $email = '', $organ = '', $title = '', $address = '');
        //保存创建后账号在e签宝平台中的唯一标识

        $data = array(
            'uid'            =>$uid,
            'user_uuid'      =>$ret['accountId'],
            'user_type'      =>1,
            'person_name'    =>$member['realname'],
            'person_id_card' =>$member['bank_num'],
            'person_mobile'  =>$member['cellphone'],
            'remark'=>$ret['errCode'] ? $ret['msg'] : '',
            'status'=>$ret['errCode'] ? -1:'',
            'ctime'=>time(),
        );
        if($user_uuid){
            $data1 = array(
                'uid'            =>$uid,
                'user_uuid'      =>$ret['accountId'],
                'user_type'      =>1,
                'person_name'    =>$member['realname'],
                'person_id_card' =>$member['bank_num'],
                'person_mobile'  =>$member['cellphone'],
                'remark'=>$ret['errCode'] ? $ret['msg'] : '',
                'status'=>$ret['errCode'] ? -1:'',
                'mtime'=>time(),
            );
            M('fi_sign_user')->where(['uid'=>$uid])->save($data1);
        }else{
             M('fi_sign_user')->add($data);
        }
        return $ret;
    }

    /**
     * @return type
     * @global type $esign
     */
    /**
     * 更新个人账号
     * @param $uid
     * @param $modifyArray
     * @return array|string
        $modifyArray = array(
            'mobile' => '13588888888',
            'email' => '',
            'title' => '222',
            'address' => '',
            'organ' => NULL,
            'name' => $name
        );
     */
    public function updatePsersonAccount($uid,$modifyArray) {
        $accountId = M('fi_sign_user')->where(['uid'=>$uid,'status'=>1])->getField('user_uuid');
        //没有认证成功或没有认证
        if(!$accountId) return  ['errCode'=>-1,'msg'=>'没有认证成功或没有认证'] ;
        //名字不能为空
        if(empty($modifyArray['name']))return ['errCode'=>-1,'msg'=>'名字不能为空'];
        $ret = self::$esign->updatePersonAccount($accountId, $modifyArray);

        //可修改字段email organ address
        $data = array(
            'person_mobile' =>$modifyArray['mobile'],
            'person_name'   =>$modifyArray['name'],
            'mtime'         =>time(),
        );
        $msg = M('fi_sign_user')->where(['uid'=>$uid])->save($data);
        return $ret;
    }

    /**
     * 删除用户信息
     * @param $uid
     * @return array
     */
    public function delUserAccount($uid) {
        $accountId = M('fi_sign_user')->where(['uid'=>$uid,'status'=>0])->getField("user_uuid");
        if(!$accountId) return ['errCode'=>-1,'msg'=>'没有认证成功或没有认证'] ;
        $ret = self::$esign->delUserAccount($accountId);
        //删除成功修改用户认证状态
        if($ret['errCode'] === 0){
            M('fi_sign_user')->where(['uid'=>$uid])->save(['status'=>-1,'mtime'=>time()]);
        }
        return $ret;
    }

    /**
     * 添加企业账号
     * 
     */
    public function addOrgAccount($qyid) {
        if(!$qyid) return ['errCode'=>-1,'msg'=>'必须传入企业id'];
        $company = M('company_list')
            ->field("enterprise_name,org_no,tax_no,unified_code,business_license,legal,contact_phone,legal_id_card_no")
            ->where(['platform_user_no'=>$qyid])
            ->find();

        if(!$company) return ['errCode'=>-1,'msg'=>'该公司不存在'];

        $user_uuid = M('fi_sign_user')->where(['uid'=>$qyid])->find();
        if($user_uuid && $user_uuid['user_uuid']) return ['errCode'=>-1,'msg'=>'该用户已经添加的,无需再次添加'];
        //组织机构代码号 或 社会信用代码号 或 工商注册号
        if(!is_null($company['unified_code']) && !empty($company['unified_code'])){
            $company_code = $company['unified_code'];
            $orgRegType  = OrganRegType::MERGE;// 三证合一营业执照
        }else{
            $company_code = $company['org_no'];
            $orgRegType  = OrganRegType::NORMAL;// 组织机构代码号
        }

        $mobile = $company['contact_phone'];//联系号码
        $name = $company['enterprise_name'];
        $organType = '0';
        $email = '';//
        $organCode = $company_code;
        $regType = $orgRegType;
        $legalArea = PersonArea::MAINLAND;
        $userType = UserType::USER_LEGAL;//代理人注册
        $agentName = '';//代理人名字
        $agentIdNo = '';//代理人身份证
        $legalName = $company['legal'];
        $legalIdNo = $company['legal_id_card_no'];

        $ret = self::$esign->addOrganizeAccount($mobile, $name, $organCode, $regType, $email, $organType, $legalArea, $userType, $agentName, $agentIdNo, $legalName, $legalIdNo, $address = '', $scope = '');

        $data=array(
            'user_type'            => 2,
            'uid'                  => $qyid,
            'user_uuid'            => $ret['accountId'],
            'org_name'             => $name,
            'org_code'             => $organCode,
            'org_reg_type'         => $orgRegType,
            'org_type'             => 0,
            'org_business_num'     => '',
            'org_legal_name'       => $legalName,
            'org_user_type'         => 2,//1为代理人注册2为法人注册
            'org_transactor_name'   => '',
            'org_transactor_id_card'=> '',
            'org_transactor_mobile' => '',
            'remark'                => $ret['errCode'] ? $ret['msg'] : '',
            'status'                => $ret['errCode'] ? -1:'',
        );

        if($user_uuid){
            $data['mtime'] = time();
            M('fi_sign_user')->where(['uid'=>$qyid])->save($data);
        }else{
            $data['ctime'] = time();
            M('fi_sign_user')->add($data);
        }
        return $ret;
    }

    /**
     * 更新企业账号
     */
    public function updateOrgAccount($uid,$modifyArray) {
        $accountId = M('fi_sign_user')->where(['id'=>$uid])->find();
        //需要修改的字段集
        $ret = self::$esign->updateOrganizeAccount($accountId['user_uuid'], $modifyArray);
        if($ret['errCode'] !=0){
            $data = array(
                "email" => $modifyArray['email'], // '' 或 NULL 表示清空改字段
                "mobile" => $modifyArray['mobile'],
                "name" => $modifyArray['name'], //不修改
                "organType" => $modifyArray['organType'], //0-普通企业  不修改
                "userType" => UserType::USER_LEGAL, //1-代理人注册，2-法人注册；0-默认
//            "agentIdNo" => '', //代理人身份证号 userType = 1 此项不能为空
//            "agentName" => '', //代理人姓名 userType = 1 此项不能为空
                "legalIdNo" => $modifyArray['legalIdNo'], //法人身份证号  userType = 2 此项不能为空
                "legalName" => $modifyArray['legalName'], //法人身份证号  userType = 2 此项不能为空
                "legalArea" => $modifyArray['legalArea'] //用户归属地 0-大陆
            );
            M('fi_sign_user')->where(['uid'=>$uid])->save($data);
        }
        return $ret;
    }

    /**
     * 个人模板印章,，返回印章imgbase64
     * @return array
     */
    public function addPersonTemplateSeal($uid,$is_update=0){
        $accountId = M('fi_sign_user')->where(['uid' => $uid])->find();
        if (!$accountId) return ['errCode' => -1, 'msg' => '用户没有认证信息'];
        $ret = self::$esign->addTemplateSeal($accountId['user_uuid'], $templateType = PersonTemplateType::RECTANGLE, $color = SealColor::RED);
        if($ret['errCode'] != 0){
            return $ret;
        }
        // 保存用户签章
        $username = M('member')->where(['id'=>$uid])->getField('username');
        //创建目录并赋予权限
        $signDir =  SITE_PATH."/Upload/sign/templateSeal/";
        if(!is_dir($signDir)){
            mkdir($signDir,0775,true);
            @chmod($signDir,0775);
        }
        $path = $signDir.$username.'-'.date("Y-m-d",time()).'.jpg';

        //当没有签章路径时或者用户信息修改时
        if(!$accountId['sign_img'] || $is_update){
            if(file_put_contents($path, base64_decode($ret['imageBase64']))){
                M('fi_sign_user')->where(['uid'=>$uid])->save(['sign_img'=>str_replace(SITE_PATH,'',$path)]);
            }
        }
        return $ret;
    }

    /**
     * 企业模板印章，返回印章imgbase64
     * @return array
     */
    public function addOrgTemplateSeal($uid,$is_update) {

        $accountId = M('fi_sign_user')->where(['uid' => $uid])->find();
        if (!$accountId) return ['errCode' => -1, 'msg' => '用户没有认证信息'];
        $ret = self::$esign->addTemplateSeal(
            $accountId['user_uuid'], $templateType = OrganizeTemplateType::STAR, $color = SealColor::RED, $hText = '合同专用', $qText = ''
        );
        if($ret['errCode'] != 0){
            return $ret;
        }
        // 保存用户签章
        $username = M('member')->where(['id'=>$uid])->getField('username');
        //创建目录并赋予权限
        $signDir =  SITE_PATH."/Upload/sign/templateSeal/";
        if(!is_dir($signDir)){
            mkdir($signDir,0775,true);
            @chmod($signDir,0775);
        }
        $path = $signDir.$username.'-'.date("Y-m-d",time()).'.jpg';

        //当没有签章路径时或者用户信息修改时
        if(!$accountId['sign_img'] || $is_update){
            if(file_put_contents($path, base64_decode($ret['imageBase64']))){
                M('fi_sign_user')->where(['uid'=>$uid])->save(['sign_img'=>str_replace(SITE_PATH,'',$path)]);
            }
        }
        return $ret;
    }

    /**
     * 文本签署
     */
    public function signDataHash() {
        $data = '123456789987777';
        $accountId = '7816E92F75DC4F848BDADD694267FCBC';
        $res = self::$esign->signDataHash($data, $accountId);
        print_r($res);
    }

    public function localVerifyText() {
        $srcData = '123456';
        $signResult = "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\n";
        $res = self::$esign->localVerifyText($srcData, $signResult);
        print_r($res);
    }

    /**
     * 事件证书签署（暂时不用）
     * @return type
     */
    public function eventSignPDF() {
        //印章模板
        //$sealData = '';
        $sealImg = file_get_contents('3.png');
        $sealData = base64_encode($sealImg);

        //创建事件证书，获取证书ID
        $cert = $this->addEventCertId();
        if ($cert['errCode'] !== 0) {
            echo "创建事件证书失败";
            return;
        }
        $certId = $cert['certId'];

        $signType = SignType::SINGLE;
        $signPos = array(
            'posPage' => 1,
            'posX' => 100,
            'posY' => 100,
            'key' => '',
            'width' => ''
        );
        $signFile = array(
            'srcPdfFile' => 'E:\test.pdf',
            'dstPdfFile' => 'E:\3-dst.pdf',
            'fileName' => '',
            'ownerPassword' => ''
        );
        $res = self::$esign->eventSignPDF($signFile, $signPos, $signType, $certId, $sealData, $stream = true);
        print_r(Util::jsonEncode($res));
        if (isset($res['errCode']) && $res['errCode'] !== 0) {
            echo '事件证书签署失败';
            return;
        }

        $signServiceId = $res['signServiceId'];
        $res = self::$esign->getSignDetail($signServiceId);
        print_r($res);
        //$esign->selfSignPDF();
    }
    /**
     * 添加事件证书(暂时不用，用于批量签署)
     * @return type
     */
    public function addEventCertId() {
        $content = '1111111111';
        $objects = array(
            array('name' => '参与者1', 'licenseType' => LicenseType::NORMALIDNO, 'license' => '111111111111111111'),
            array('name' => '参与者2', 'licenseType' => LicenseType::NORMALIDNO, 'license' => '222222222222222222'),
            array('name' => '参与者3', 'licenseType' => LicenseType::NORMALIDNO, 'license' => '333333333333333333')
        );
        //$objects = array();
        $a = self::$esign->addEventCert($content, $objects);
        print_r(Util::jsonEncode($a));
        //var_dump(json_encode($a, JSON_UNESCAPED_UNICODE));
        return $a;
    }
    /**
     * 查询事件证书详情（暂时不用）
     * 
     */
    public function getSignDetail() {
        $signServiceId = '829215267337818119';
        $res = self::$esign->getSignDetail($signServiceId);
        print_r(Util::jsonEncode($res));
    }

    /**
     * userSignPDF();平台用户签
     */
    public function userSignPDF($id,$type=0) {
        //通过调用印章模板、获取印章数据
        $is_local = M('fi_sign_contract')->where(['id'=>$id])->find();
        if(!$is_local){
            return ['errCode'=>-1,'msg'=>'不存在待签署协议'];
        }
        $signUser = M('fi_sign_user')->where(['uid'=>$is_local['uid']])->field('user_uuid')->find();
        $user_type = M('member')->where(['id'=>$is_local['uid']])->getField('user_type');
        //用户没有认证过
        if(!$signUser) {
            if($user_type != 10){
                $personAccount = $this->addPersonAccount($is_local['uid']);
            }else{
                $personAccount = $this->addOrgAccount($is_local['uid']);
            }

            $accountId = $personAccount['accountId'];
        }else{
            //用户认证过了
            if(!$signUser['user_uuid']){
                return ['errCode'=>-1,'msg'=>'用户认证失败！！！'];
            }
            $accountId = $signUser['user_uuid'];
        }
        if($user_type != 10){
            $seal = $this->addPersonTemplateSeal($is_local['uid']);
        }else{
            $seal = $this->addOrgTemplateSeal($is_local['uid']);
        }

        if($seal['errCode'] !=0){
            return ['errCode'=>-1,'msg'=>'获取个人签章失败'];
        }
        //用户签章bese64数据流
        $sealData = $seal['imageBase64'];
        //单页签署
        $signType = SignType::SINGLE;

        //获取动态文件保存路径
        switch ($is_local['bus_type']){
            //凤宝宝
            case 'storage':
                //签章位置配置文件中定义
                $signPos = SignParam::$signPosStoragePerson;
                break;
            case 'plan':
                $signPos = SignParam::$signPosRegPerson;
                break;
            case 'borrower':
                $signPos = SignParam::$signPosInvestPerson;
                break;
            default:
                $dstPdfFile = "./Upload/sign/default/storage.pdf";
                $doc_num = '';
        }
        $pdfFile = SITE_PATH.$is_local['sign_path'];
        //协议文件母版放在配置文件
        $signFile = array(
            'srcPdfFile' => $pdfFile,
            'dstPdfFile' => $pdfFile,
            'fileName' => 'aaa.pdf',
            'ownerPassword' => '' //若加密，需要添加密码
        );
        //发送请求 以文件流的方式
        $ret = self::$esign->userSignPDF($accountId, $signFile, $signPos, $signType, $sealData, $stream = true);
        //保存记录
        $data = array(
            'status'=>$ret['errCode'] ? 0 : 2,
            'sign_id'=>$ret['signServiceId'],
            'mtime'=>time(),
        );
        M('fi_sign_contract')->where(['id'=>$is_local['id']])->save($data);
        //平台签章
        $ret1 = $this->selfSignPDF($is_local['bus_type'],$pdfFile,$is_local['id']);

        //借款人签章
        if($type == 1){
            //通过调用印章模板、获取印章数据
            $borrow_uid = M('borrow_info')->where(['id'=>$is_local['borrow_id']])->getField("borrow_uid");

            if(!$borrow_uid){
                return ['errCode'=>1001,'借款人不存在！！'];
            }

            $signUserBorrower = M('fi_sign_user')->where(['uid'=>$borrow_uid])->field('user_uuid')->find();
            $user_type1 = M('member')->where(['id'=>$borrow_uid])->getField('user_type');
//            dump($user_type1);die;
            //用户没有认证过
            if(!$signUserBorrower['user_uuid']) {
                if($user_type1 != 10){
                    $personAccountBorrower = $this->addPersonAccount($borrow_uid);
                }else{
                    $personAccountBorrower = $this->addOrgAccount($borrow_uid);
                }

                $accountIdBorrower = $personAccountBorrower['accountId'];
            }else{
                //用户认证过了
                if(!$signUserBorrower['user_uuid']){
                    return ['errCode'=>-1,'msg'=>'用户认证失败！！！'];
                }
                $accountIdBorrower = $signUserBorrower['user_uuid'];
            }

            if($user_type1 != 10){
                $sealBorrower = $this->addPersonTemplateSeal($borrow_uid);
            }else{
                $sealBorrower = $this->addOrgTemplateSeal($borrow_uid);
            }

            if($sealBorrower['errCode'] !=0){
                return ['errCode'=>-1,'msg'=>'获取个人签章失败'];
            }
            //用户签章bese64数据流
            $sealDataBorrower = $sealBorrower['imageBase64'];
            //单页签署
            $signTypeBorrower = SignType::SINGLE;
            //保存路径
            $signPosBorrower = SignParam::$signPosBorrowerPerson;

            $pdfFile = SITE_PATH.$is_local['sign_path'];
            //协议文件母版放在配置文件
            $signFile = array(
                'srcPdfFile' => $pdfFile,
                'dstPdfFile' => $pdfFile,
                'fileName' => 'aaa.pdf',
                'ownerPassword' => '' //若加密，需要添加密码
            );
            //发送请求 以文件流的方式
            $ret1 = self::$esign->userSignPDF($accountIdBorrower, $signFile, $signPosBorrower, $signTypeBorrower, $sealDataBorrower, $stream = true);
        }
        return $ret1;
    }

    /**
     * 借款人专用
     * @param $id
     * @return array|mixed
     */
    public function userSignPDFBorrower($id) {
        set_time_limit(0);
        //通过调用印章模板、获取印章数据
        $is_local = M('fi_sign_contract')->where(['id'=>$id])->find();
        if(!$is_local){
            return ['errCode'=>-1,'msg'=>'不存在待签署协议'];
        }

        $list = M('borrow_invest')
            ->field("invest_uid")
            ->where(['borrow_id'=>$is_local['borrow_id']])
            ->select();
//      签章位置初始化 暂时设定十二个换一页
        $posX = 100;
        $posY = 350;
        $posPage = 7;
        $total = count($list);//总的个数
        //出借人签章
        foreach ($list as $key=>$val){
            $signUser = M('fi_sign_user')->where(['uid'=>$val['invest_uid']])->field('user_uuid')->find();
            $user_type = M('member')->where(['id'=>$val['invest_uid']])->getField('user_type');
            //用户没有认证过
            if(!$signUser) {
                if($user_type != 10){
                    $personAccount = $this->addPersonAccount($val['invest_uid']);
                }else{
                    $personAccount = $this->addOrgAccount($val['invest_uid']);
                }
                $accountId = $personAccount['accountId'];
            }else{
                //用户认证过了
                if(!$signUser['user_uuid']){
                    return ['errCode'=>-1,'msg'=>'用户已认证但失败,id:'.$val['invest_uid']];
                }
                $accountId = $signUser['user_uuid'];
            }

            if($user_type != 10){
                $seal = $this->addPersonTemplateSeal($val['invest_uid']);
            }else{
                $seal = $this->addOrgTemplateSeal($val['invest_uid']);
            }

            if($seal['errCode'] !=0){
                return ['errCode'=>-1,'msg'=>'获取个人签章失败,用户id：'.$val['invest_uid']];
            }
            //用户签章bese64数据流
            $sealData = $seal['imageBase64'];
            //单页签署
            $signType = SignType::SINGLE;
            //获取动态文件保存路径
            if($key == 11 || (($key-11)%29)==0){
                $posPage += 1;
                $posY = 775;
            }
            $signPos = array(
                'posPage' => $posPage,
                'posX' => $posX,
                'posY' => $posY,
                'key' => '',
                'width' => '50'
            );
            $posY -=25;

            $pdfFile = SITE_PATH.$is_local['sign_path'];
            //协议文件母版放在配置文件
            $signFile = array(
                'srcPdfFile' => $pdfFile,
                'dstPdfFile' => $pdfFile,
                'fileName' => 'aaa.pdf',
                'ownerPassword' => '' //若加密，需要添加密码
            );
            //发送请求 以文件流的方式
            $ret = self::$esign->userSignPDF($accountId, $signFile, $signPos, $signType, $sealData, $stream = true);
        }

        //保存记录
        $data = array(
            'status'=>$ret['errCode'] ? 0 : 2,
            'sign_id'=>$ret['signServiceId'],
            'mtime'=>time(),
        );
        M('fi_sign_contract')->where(['id'=>$is_local['id']])->save($data);

        //借款人签章
        //通过调用印章模板、获取印章数据
        
        $borrow_uid = M('borrow_info')->where(['id'=>$is_local['borrow_id']])->getField("borrow_uid");

        if(!$borrow_uid){
            return ['errCode'=>1001,'借款人不存在！！'];
        }

        $signUserBorrower = M('fi_sign_user')->where(['uid'=>$borrow_uid])->field('user_uuid')->find();
        $user_type1 = M('member')->where(['id'=>$borrow_uid])->getField('user_type');
        //用户没有认证过
        if(!$signUserBorrower['user_uuid']) {
            if($user_type1 != 10){
                $personAccountBorrower = $this->addPersonAccount($borrow_uid);
            }else{
                $personAccountBorrower = $this->addOrgAccount($borrow_uid);
            }
            $accountIdBorrower = $personAccountBorrower['accountId'];
        }else{
            //用户认证过了
            if(!$signUserBorrower['user_uuid']){
                return ['errCode'=>-1,'msg'=>'用户认证失败！！！'];
            }
            $accountIdBorrower = $signUserBorrower['user_uuid'];
        }
        if($user_type1 != 10){
            $sealBorrower = $this->addPersonTemplateSeal($borrow_uid);
        }else{
            $sealBorrower = $this->addOrgTemplateSeal($borrow_uid);
        }

        if($sealBorrower['errCode'] !=0){
            return ['errCode'=>-1,'msg'=>'获取个人签章失败'];
        }
        //用户签章bese64数据流
        $sealDataBorrower = $sealBorrower['imageBase64'];
        //单页签署
        $signTypeBorrower = SignType::SINGLE;
        //保存路径
        if($posY<=100){
            $posPage += 1;
            $posY = 775;
        }
        $signPosBorrower = array(
            'posPage' => $posPage,
            'posX' => $posX+80,
            'posY' => $posY-50,
            'key' => '',
            'width' => '50'
        );
        $pdfFile = SITE_PATH.$is_local['sign_path'];
        //发送请求 以文件流的方式
        $ret1 = self::$esign->userSignPDF($accountIdBorrower, $signFile, $signPosBorrower, $signTypeBorrower, $sealDataBorrower, $stream = true);
        //平台签章
        if($posY<=100){
            $posPage += 1;
            $posY = 800;
        }
        $SelfSignPos = array(
            'posPage' => $posPage,
            'posX' => $posX+80,
            'posY' => $posY-180,
            'key' => '',
            'width' => '90'
        );
        $ret2 = $this->selfSignPDF($is_local['bus_type'],$pdfFile,$is_local['id'],$SelfSignPos);

        return $ret2;
    }

//平台自身签署
    public function selfSignPDF($type,$path='',$sign_id='',$signPos='') {
        $sealId = '0';
        $signType = SignType::SINGLE;
        if(!$path){
            $path = "./Upload/sign/storage.pdf";
        }
        switch ($type){
            //凤宝宝
            case 'storage':
                $signPos = SignParam::$signPosStoragePc;
                $signFile = array(
                    'srcPdfFile' => $path,
                    'dstPdfFile' => $path,
                    'fileName' => "st".time().'.pdf',
                    'ownerPassword' => ''
                );
                break;
            //智慧投
            case 'plan':
                $signPos = SignParam::$signPosRegPc;
                $signFile = array(
                    'srcPdfFile' => $path,
                    'dstPdfFile' => $path,
                    'fileName' => "reg".time().'.pdf',
                    'ownerPassword' => ''
                );
                break;
            //借款服务
            case 'borrower':
                $signPos = SignParam::$signPosBorrowerPc;
                $signFile = array(
                    'srcPdfFile' => $path,
                    'dstPdfFile' => $path,
                    'fileName' => "borrower".time().'.pdf',
                    'ownerPassword' => ''
                );
                break;
            case 'borrow':
                $signPos = $signPos;
                $signFile = array(
                    'srcPdfFile' => $path,
                    'dstPdfFile' => $path,
                    'fileName' => "borrower".time().'.pdf',
                    'ownerPassword' => ''
                );
                break;
            default:
                break;
        }


        $ret = self::$esign->selfSignPDF($signFile, $signPos, $sealId, $signType, $stream = true);

        if($sign_id && $ret['errCode'] == 0){
            M('fi_sign_contract')->where(['id'=>$sign_id])->save(['sign_pc_id'=>$ret['signServiceId'],'status'=>1]);
        }
        output("./Log/sign_server.log", json_encode($ret));
        return $ret;
    }

    /**
     * 文档验签
     * 
     */
    public function fileVerify($filePath) {
        if(!is_file($filePath)){
            return ['code'=>0,'msg'=>'请传入有效的路径'];
        }
        $ret = self::$esign->fileVerify($filePath, true);
        return $ret;
    }

    /**
     * 定义行业类型
     * @param $name 行业名称
     * @return mixed
     */
    public function createBusiness($name){
        $ret = self::$esign->createBusiness($name);
        return $ret;

    }

    /**
     * 定义业务凭证名称
     * @param $busId
     * @return mixed
     */
    public function createScene($busId,$name){
        $ret = self::$esign->createScene($busId,$name);
        return $ret;
    }

    public function createSeg($sceneId,$name){
        //设置业务凭证中某一证据点参数
        $ret = self::$esign->createSeg($sceneId,$name);
        return $ret;
    }

    /**
     *定义业务凭证中某一证据点的字段属性
     * @param $segId
     * @param $array
     * @return mixed
     */
    public function createSegProp($segId,$properties){
        //设置业务凭证中某一证据点的字段属性参数
        $ret = self::$esign->createSegProp($segId,$properties);
        return $ret;
    }


    /**
     * 构建证据链
     * @param $sceneName
     * @param $sceneTemplateId
     * @param $linkIds
     * @return mixed
     */
    public function createEviChain($sceneName,$sceneId,$linkIds){
        //设置构建证据链参数
        $ret = self::$esign->createEviChain($sceneName,$sceneId,$linkIds);
        return $ret;


    }

    /**
     * 创建 基础版 存证证据点
     * @param $filePath
     * @param $segmentTempletId
     * @return mixed
     */
    public function createEviSpotBasics($filePath,$segmentTempletId,$segmentData){
        $ret = self::$esign->createEviSpotBasics($filePath,$segmentTempletId,$segmentData);
//        if($ret)
        $retUpload = self::$esign->uploadFile($ret['url'],$filePath);
        $ret['Upload'] = $retUpload;
        return $ret;
    }

//将存证证据点追加到证据链中
    function addEviChain($sceneId,$linkIds){
        $ret = self::$esign->addEviChain($sceneId,$linkIds);
        return $ret;
    }
    //存证记录关联到指定用户
    function sceneEvIdWithUser($sceneId,$certificates){
        $ret = self::$esign->sceneEvIdWithUser($sceneId,$certificates);
        return $ret;
    }
    //拼接查看存证证明URL
    function getViewCertificateInfoUrl($sceneId,$id_card,$reverse="true",$cardType = "ID_CARD"){
        $ret = self::$esign->getViewCertificateInfoUrl($sceneId,$id_card,$reverse,$cardType);
        return $ret;
    }


}
