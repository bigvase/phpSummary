<?php

/**
 * 功能：银行存管接口服务基类[对接银行] -- HTTP接口
 * 描述：java银行存管业务中间层类
 *       此类定义了接口参数，接口类
 * add by lbk 2017-06-16 lbkbox@163.com
 */
use App\Lib\Service\EscrowJavaHttpService;

class EscrowHttpService extends EscrowJavaHttpService {

    /**
     * desc:接口调用公共中间层
     * $EscrowPara:   escrowPara文件中配置的接口服务数组
     * $ParaData:    调用者传递的接口的二层参数数组
     * $ClientType:  调用者的终端类型
     * $timeout   ： 调用接口的超时时间
     */
    public function mergeReques($EscrowPara = [], $ParaData = [], $RequesType = '', $ClientType = 'PC', $timeout) {
        try {
            $ParaData['timestamp'] = "" . date("YmdHis");
//            $ParaData['timestamp'] = '20170726'.date("His");//测试回款更改到回款日期
            //-----内层业务参数校验------
            $this->checkParam($EscrowPara, $ParaData);
            //-----外层参数组装------
            $topPara = $this->getTopParaData($EscrowPara['serverParam']['serviceName'], $ParaData, $ClientType);
            //------两层参数合并-----
            $mergeParaData = $topPara;
            //合并两层参数数组（业务数据报文，JSON 格式）
            $mergeParaData['reqData'] = json_encode($ParaData);

            //记录请求参数日志，如果是直连接口，返回后根据记录id保存返回数据；网关接口回调中根据requestNo保存返回数据
            $save                       = [];
            $save['requestNo']          = $ParaData['requestNo'] ? $ParaData['requestNo'] : $ParaData['batchNo'];
            $save['operation_type']     = $RequesType;
            $save['interface']          = $EscrowPara['serverParam']['serviceName'];
            $save['send_data']          = json_encode($mergeParaData);
            $save['add_time']           = time();
            $logId = M('cg_request_log') -> add($save);

            $logTemp                        = $mergeParaData;
            if (is_array($logTemp) && is_array($ParaData)) {
                $logTemp = array_merge($logTemp, $ParaData);
            }
            $logTemp['escrow_log_type']     = 'cg_request_log';
            glog(["添加流水记录：", $save, M()->getLastSql()], $logTemp);

            //------调用底层通讯接口，向银行发起请求-------
            $this->escrowJavaHttpApi($mergeParaData);
            $response = $this->response();  //银行返回的
            $output = !is_json($response) ? json_encode($response) : $response;

            if ($response) {
                unset($save);
                $save['response']           = get_json_format($response);
                M('cg_request_log') -> where(['id' => $logId]) -> save($save);

                $logTemp                        = $mergeParaData;
                if (is_array($logTemp) && is_array($ParaData)) {
                    $logTemp = array_merge($logTemp, $ParaData);
                }
                $logTemp['escrow_log_type']     = 'cg_request_log';
                glog(["直连接口更新流水记录", $save, M()->getLastSql()], $logTemp);
            }
        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }
        return $output;
    }

    /**
     * 类内部调用的子函数:
     * 功能：
     * 一、多维数组转换成一维数组
     * 格式：  $inputData = array('uid'=>1001,'bid'=>1002,array('uid'=>123456,'uname'=>'张三'));
     * 转换成：
     * 格式：  $inputData = array('uid'=>1001,'bid'=>1002,'uid'=>123456,'uname'=>'张三');
     * @param $paraData
     * @param bool $flag1 | false不转换多维数组
     * @return bool
     */
    private function _merge_single(&$paraData, $flag1 = true) {
        if (!is_array($paraData)) {
            return false;
        }
        $paraDataTemp = [];
        foreach ($paraData as $key => &$temp) {
            if (is_array($temp)) {
                if ($flag1) {
                    $this->_merge_single($temp);
                } else {
                    $paraDataTemp[$key] = $temp;
                }
            } else {
                $paraDataTemp[$key] = $temp;
            }
        }
        return $paraDataTemp;
    }

    /***
     * 将二维数组键名改成大写
     *
     * @param $array
     * @param int $case
     */
    public function array_case(&$array, $case = CASE_LOWER) {
        $array = array_change_key_case($array, $case);
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $this->array_case($array[$key], $case);
            }
        }
    }

    /***
     * 检验请求参数
     *
     * 用于检验所请求的参数是否满足约定的格式，包括类型，长度【暂未开启长度校验】，名称及对应是否存在上级依赖
     *
     * @param $phpParam         格式约定，格式约定于escrowPara.php文件中
     * @param $paraData         请求参数数组
     * @return bool
     * @throws Exception
     */
    private function checkParam(&$phpParam, &$paraData) {

        // 存储参数临时值，因中途校验会修改键名为大写，后续需要还原键名
        $paraDataTemp = $paraData;

        $this->array_case($phpParam['dataParam'], CASE_UPPER);
        $this->array_case($paraData, CASE_UPPER);
        if (count($paraData) === 0) {
            throw new \Exception('传入的业务参数有问题，参数不能为空！');
        }
        //对入参数校验
        if (!empty($phpParam['dataParam'])) {

            // 递归去除多维数组中的键值为空的变量
            $paraData = $this -> unsetEmptyVariable($paraData, $phpParam['dataParam']);

            $paramStatus = '';
            foreach ($paraData as $key => $value) {//参数循环
                try {
                    $this->checkParamOne($phpParam['dataParam'], $key, $paraData, $paramStatus);
                } catch (Exception $e) {
                    throw new \Exception($e->getMessage());
                }
            }

            $paraData = $this -> unsetEmptyVariable($paraDataTemp, $phpParam['dataParam']);
        }
        return true;
    }

    /***
     * 释放数组中键值为空的变量
     *
     * @param $param
     */
    private function unsetEmptyVariable($param, $dataParam) {

        foreach ($param as $k => $v) {
            if (is_array($v)) {
                $param[$k] = $this -> unsetEmptyVariable($v, $dataParam[$k]['DETAILS']);
            } else if (empty($v) && ($dataParam[$k]['ISREQUIRED'] != true)) {
                unset($param[$k]);
            }
        }
        return $param;
    }

    /**
     * 获取接口的同步回调地址，若不存在返回false
     *
     * @param $interfaceName
     * @return bool
     */
    public function getRedirectUrl($interfaceName) {
        $interfaceName = strtoupper($interfaceName);
        $interfaceList = escrowPara::$ESCROW;
        $this->array_case($interfaceList, CASE_UPPER);
        if (empty($interfaceName) || empty($interfaceList[$interfaceName]['SERVERPARAM']['REDIRECTURL'])) {
            return false;
        }
        return $interfaceList[$interfaceName]['SERVERPARAM']['REDIRECTURL'];
    }

    /***
     * 获取页面过期时间，如果不存在则默认10分钟有效
     *
     * @param $interfaceName
     * @return false|string
     */
    public function getExpiredTime($interfaceName) {
        $interfaceName = strtoupper($interfaceName);
        $interfaceList = escrowPara::$ESCROW;
        $this->array_case($interfaceList, CASE_UPPER);
        if (empty($interfaceName) || empty($interfaceList[$interfaceName]['SERVERPARAM']['EXPIRED'])) {
            return date('YmdHis', time() + 600);
        }
        return date('YmdHis', time() + $interfaceList[$interfaceName]['SERVERPARAM']['EXPIRED']);
    }

    /***
     * 对单个请求参数进行校验
     *
     * @param $dataParamFormat      约定的参数格式，参见escrowPara.php文件
     * @param $key                  键名
     * @param $phpParam             请求的参数数组，需要判断当前键名是否依赖于其他参数
     * @param $paramStatus          记录已处理的键名状态，防止出现递归死循环
     * @return bool
     * @throws Exception
     */
    public function checkParamOne(&$dataParamFormat, $key, &$phpParam, &$paramStatus) {
        if (empty($dataParamFormat[$key])) {
            throw new \Exception("参数错误，{$key}.' -- '.{$dataParamFormat[$key]}配置参数不存在");
        } // 不存在该键名

        if (!isset($phpParam[$key])) {
            throw new \Exception("参数错误，请求参数不存在键名为【{$key}】的键值");
        } // 不存在该键值
        // 检验配置参数是否完整
        if (!isset($dataParamFormat[$key]['TYPE'], $dataParamFormat[$key]['LENGTH'], $dataParamFormat[$key]['ISREQUIRED'], $dataParamFormat[$key]['NOTE'], $dataParamFormat[$key]['REFER'])) {
            throw new \Exception('参数配置错误，部分参数配置不存在');
        }

        // 存在上级依赖
        if (!empty($dataParamFormat[$key]['REFER'])) {
            // 如果是数组，即可能存在多个参数，关系为 or
            if (is_array($dataParamFormat[$key]['REFER'])) {
                $lockMessage        = '';   // 每次轮询的第一条错误信息
                $tempLockMessage    = '';   // 总轮询的第一条错误信息
                // 分别遍历各个参数
                foreach ($dataParamFormat[$key]['REFER'] as $k => $v) {
                    // 发现有子数组，子数组内关系为 and
                    if (is_array($v) && !is_array($v[0])) {
                        $res = $this->checkParamSome($dataParamFormat, $v, $phpParam, $paramStatus);
                        if ($res == false) {
                            if (empty($lockMessage)) $lockMessage = "参数错误，xxxx";
                        }
                    } else {
                        if ($v === true) {
                            if (!isset($phpParam[$k])) {
                                $paramStatus[$k] = -1;
                                if (empty($lockMessage)) $lockMessage = "参数错误，{$k}不存在";
                            } else if (isset($phpParam[$k])) {
                                try {
                                    $this->checkParamType($dataParamFormat, $k, $phpParam, $paramStatus);
                                } catch (Exception $e) {
                                    if (empty($lockMessage)) $lockMessage = $e->getMessage();
                                }
                                $paramStatus[$k] = 1;
                            }
                        } else if (!is_array($v) && ($phpParam[$k] != $v)) {
                            $paramStatus[$k] = -1;
                            if (empty($lockMessage)) $lockMessage = "参数错误，{$key}的上级依赖{$k}值为" . ($phpParam[$k] ? $phpParam[$k] : '空') . "而非'{$v}'，{$key}应不传";
                        }else if (is_array($v) && is_array($v[0]) && !in_array($phpParam[$k], $v[0])) {
                            $paramStatus[$k] = -1;
                            if (empty($lockMessage)) $lockMessage = "参数错误，{$key}的上级依赖{$k}值【{$phpParam[$k]}】不在数组【" . implode('-', $v[0]) . "】中";
                        } else {
                            $paramStatus[$k] = 1;
                        }
                    }
                    // 如果$tempLockMessage为空，则将$tempLockMessage赋为$lockMessage，无论$lockMessage是否为空
                    if (empty($tempLockMessage)) $tempLockMessage = $lockMessage;
                    // 如果单次轮询发现$lockMessage为空，则说明校验通过，符合该级约束，$tempLockMessage置空，并跳出轮询；否则置空$lockMessage
                    if (empty($lockMessage)) {
                        $tempLockMessage = '';
                        break;
                    } else {
                        $lockMessage = '';
                    }
                }
                // 循环遍历检查后仍就有$tempLockMessage信息，说明检验未通过，抛出该信息为异常信息
                if ($tempLockMessage) throw new \Exception($tempLockMessage);
            } else {
                $this->checkParamOne($dataParamFormat, $phpParam[$key]['REFER'], $phpParam, $paramStatus);
            }
        }

        if ($dataParamFormat[$key]['DETAILS'] && is_array($phpParam[$key])) {
            foreach ($phpParam[$key] as $kkk => $vvv) {
                foreach ($dataParamFormat[$key]['DETAILS'] as $kk => $vv) {
                    if (!isset($vvv[$kk]) && ($dataParamFormat[$key]['DETAILS'][$kk]['ISREQUIRED'] == false)) {
                        continue;
                    }
                    $this->checkParamOne($dataParamFormat[$key]['DETAILS'], $kk, $vvv, $paramStatus[$kkk][$kk]);
                }
            }
        }

        // 检验参数是否符合要求
        try {
            $this->checkParamType($dataParamFormat, $key, $phpParam, $paramStatus);
        } catch (Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return true;
    }

    /**
     * 检验参数是否正确
     * @param $dataParamFormat
     * @param $key
     * @param $phpParam
     * @param $paramStatus
     * @throws Exception
     */
    public function checkParamType(&$dataParamFormat, $key, &$phpParam, &$paramStatus) {
        // 检验类型
        $ck = $key;
        foreach ($dataParamFormat[$ck] as $k => $v) {

            if (!(array_key_exists($ck, $paramStatus) || ($dataParamFormat[$ck]['ISREQUIRED'] == true))) {
                // 如果对应参数为空字符串，则撤销该参数，此处检验正确性有待实验|2017年7月4日14:38:10
                if ($phpParam[$ck] == '') {
                    unset($phpParam[$ck]);
                }

                continue;
            } else {
                if (!isset($phpParam[$ck]) || (isset($phpParam[$ck]) && empty($phpParam[$ck]) && !is_numeric($phpParam[$ck]) && (!$dataParamFormat[$ck]['ALLOWEMPTY']))) {
                    throw new \Exception("参数错误，{$dataParamFormat[$ck]['NOTE']}【{$key}】");
                }
            }
            if (strtoupper($k) == 'TYPE') {
                // 检验类型
                switch ($v) {
                    case "I": // 整数
                        if (intval($phpParam[$ck]) != $phpParam[$ck]) {
                            throw new \Exception("参数类型错误，{$ck}类型为非整数：{$phpParam[$ck]}【参考值：1234】");
                        }
                        break;
                    case "S": // 字符串
                        if (!is_string($phpParam[$ck]) && !is_numeric($phpParam[$ck])) {
                            throw new \Exception("参数类型错误，{$ck}类型为非字符串：{$phpParam[$ck]}【参考值：'1234'】");
                        }
                        // 字符串长度校验开关 $this->_checkParamLengthSwitch
                        if (!empty($dataParamFormat[$ck]['LENGTH'])) {
                            // 需判断检验长度开关是否开启
                            if (($this->_checkParamLengthSwitch) && (strlen($phpParam[$ck]) > $dataParamFormat[$ck]['LENGTH'])) {
                                throw new \Exception("参数长度错误，{$ck}长度大于允许的最大值：{$phpParam[$ck]}【当前长度：" . strlen($phpParam[$ck]) . "】【最大允许长度：{$dataParamFormat[$ck]['LENGTH']}】");
                            }
                        }
                        break;
                    case "A": // 金额
                        if (!is_numeric($phpParam[$ck])) {
                            throw new \Exception("参数类型错误，{$ck}类型为非金额值：{$phpParam[$ck]}【参考值：2.33】");
                        }
                        break;
                    case "D": // 日期
                        if (!strtotime($phpParam[$ck] . '000000')) {
                            throw new \Exception("参数类型错误，{$ck}类型为非日期类型：{$phpParam[$ck]}【参考值：20170626】");
                        }
                        break;
                    case "E": // 枚举值

                        break;
                    case "F": // 浮点数
                        if (!is_numeric($phpParam[$ck]) && !is_float($phpParam[$ck]) && !is_int($phpParam[$ck])) {
                            throw new \Exception("参数类型错误，{$ck}类型为非浮点数值：{$phpParam[$ck]}【参考值：2.336666】");
                        }
                        break;
                    case "B": // 布尔值
                        if (!is_bool($phpParam[$ck])) {
                            throw new \Exception("参数类型错误，{$ck}类型为非布尔值：{$phpParam[$ck]}【参考值：true】");
                        }
                        break;
                    case "T": // 时间
                        if (!strtotime($phpParam[$ck])) {
                            throw new \Exception("参数类型错误，{$ck}类型为非时间类型：{$phpParam[$ck]}【参考值：20170626060408】");
                        }
                        break;
                    case "": // 空白不判断
                    case "C": // 数组内部嵌套键值对
                    case "O": // 嵌套键值对

                        break;
                }
            }
        }
    }

    /**
     * 存在多重并行验证，即满足多个条件时，参数才必须填
     *
     * @param $dataParamFormat
     * @param $list
     * @param $phpParam
     * @param $paramStatus
     * @return bool
     */
    public function checkParamSome($dataParamFormat, $list, $phpParam, &$paramStatus) {
        foreach ($list as $key => $value) {
            $res = $this->checkParamOne($dataParamFormat, $key, $phpParam, $paramStatus);
            if (!$res) {
                return false;
            }
        }
        return true;
    }

    /***
     * 向新网提交请求
     *
     * @param $interfaceName        接口名
     * @param $json                 请求参数
     * @return mixed|string
     */
    private function submitEscrow($interfaceName, $json, $bizType) {

        try {
            //参数1：escrowPara文件中配置的接口服务数组  /参数2：调用者传递的接口的二层参数数组
            $output = $this->mergeReques(escrowPara::$ESCROW[$interfaceName],$json, $bizType ? $bizType : escrowPara::$RequesType[$interfaceName][0]);
        } catch (Exception $e) {
            $output['code']         = 1;
            $output['status']       = 'INIT';
            $output['errorMessage'] = $e->getMessage();
            echo json_encode($output);
            return json_encode($output);
        }

        if (is_array($output)) $output = json_encode($output);
//        echo $output; // 测试查看返回数据
        return $output;
    }
    
    /**************************************\
     *       以下为新网接口相关
     *
     *      2017年7月4日09:46:12
    \**************************************/

    /***
     * 个人绑卡注册 PERSONAL_REGISTER_EXPAND
     *
     * @param array $paramList
     * @param string $bizType
     * @return mixed
     */
    public function personalRegisterExpand($paramList = [],$bizType = 'PERSONAL_REGISTER_EXPAND') {

        $interfaceName              = 'PERSONAL_REGISTER_EXPAND';

        $platformUserNo             = get_param($paramList,'platformUserNo');               // 平台用户编号
        $realName                   = get_param($paramList,'realName');                     // 用户真实姓名
        $idCardNo                   = get_param($paramList,'idCardNo');                     // 用户证件号
        $bankcardNo                 = get_param($paramList,'bankcardNo');                   // 银行卡号
        $mobile                     = get_param($paramList,'mobile');                       // 银行预留手机号
        $idCardType                 = get_param($paramList,'idCardType','PRC_ID'); // 证件类型
        $userRole                   = get_param($paramList,'userRole','INVESTOR');  // 用户角色
        $checkType                  = get_param($paramList,'checkType', 'LIMIT');   // 鉴权验证类型
        $userLimitType              = get_param($paramList,'userLimitType', 'ID_CARD_NO_UNIQUE');      // 鉴权验证类型
        $authList                   = get_param($paramList,'authList', 'TENDER,REPAYMENT,CREDIT_ASSIGNMENT,COMPENSATORY,WITHDRAW,RECHARGE');      // 鉴权验证类型

        $redirectUrl                = $this->getRedirectUrl($interfaceName)  . '?request_source=' . session('request_source');
        $requestNo                  = getEscRequesOrder(escrowPara::$RequesType[$bizType][1], $platformUserNo);

        // 接口参数 START
        $json                       = [];
        $json['platformUserNo']     = $platformUserNo;      // 平台用户编号
        $json['realName']           = $realName;            // 用户真实姓名
        $json['idCardNo']           = $idCardNo;            // 用户证件号
        $json['bankcardNo']         = $bankcardNo;          // 银行卡号
        $json['mobile']             = $mobile;              // 银行预留手机号
        $json['idCardType']         = $idCardType;          // 证件类型
        $json['userRole']           = $userRole;            // 用户角色
        $json['checkType']          = $checkType;           // 鉴权验证类型
        $json['userLimitType']      = $userLimitType;       // 鉴权验证类型
        $json['authList']           = $authList;            // 自动授权
        $json['redirectUrl']        = $redirectUrl;
        $json['requestNo']          = $requestNo;
        // 接口参数 END

        return $this -> submitEscrow($interfaceName, $json, $bizType);
    }

    /***
     * 企业绑卡注册 ENTERPRISE_REGISTER
     *
     * @param array $paramList
     * @param string $bizType
     * @return mixed
     */
    public function enterpriseRegister($paramList = [],$bizType = 'ENTERPRISE_REGISTER') {

        $interfaceName              = 'ENTERPRISE_REGISTER';

        $platformUserNo             = get_param($paramList,'platformUserNo');               // 平台用户编号
        $enterpriseName             = get_param($paramList,'enterpriseName');               // 企业名称
        $bankLicense                = get_param($paramList,'bankLicense');                  // 开户银行许可证号
        $orgNo                      = get_param($paramList,'orgNo');                        // 组织机构代码
        $businessLicense            = get_param($paramList,'businessLicense');              // 营业执照编号
        $taxNo                      = get_param($paramList,'taxNo');                        // 税务登记号
        $unifiedCode                = get_param($paramList,'unifiedCode');                  // 统一社会信用代码
        $creditCode                 = get_param($paramList,'creditCode');                   // 机构信用代码
        $legal                      = get_param($paramList,'legal');                        // 法人姓名
        $idCardType                 = get_param($paramList,'idCardType');                   // 证件类型
        $legalIdCardNo              = get_param($paramList,'legalIdCardNo');                // 法人证件号
        $contact                    = get_param($paramList,'contact');                      // 企业联系人
        $contactPhone               = get_param($paramList,'contactPhone');                 // 联系人手机号
        $userRole                   = get_param($paramList,'userRole');                     // 用户角色
        $bankcardNo                 = get_param($paramList,'bankcardNo');                   // 企业对公账户显示后四位
        $bankcode                   = get_param($paramList,'bankcode');                     // 银行编码
        $authList                   = get_param($paramList,'authList', 'TENDER,REPAYMENT,CREDIT_ASSIGNMENT,COMPENSATORY,WITHDRAW,RECHARGE');      // 鉴权验证类型

        $redirectUrl                = $this->getRedirectUrl($interfaceName)  . '?request_source=' . session('request_source');
        $requestNo                  = getEscRequesOrder(escrowPara::$RequesType[$bizType][1], $platformUserNo);

        // 接口参数 START
        $json                       = [];
        $json['platformUserNo']		= $platformUserNo;
        $json['enterpriseName']		= $enterpriseName;
        $json['bankLicense']		= $bankLicense;
        $json['orgNo']		        = $orgNo;
        $json['businessLicense']    = $businessLicense;
        $json['taxNo']		        = $taxNo;
        $json['unifiedCode']		= $unifiedCode;
        $json['creditCode']		    = $creditCode;
        $json['legal']		        = $legal;
        $json['idCardType']		    = $idCardType;
        $json['legalIdCardNo']		= $legalIdCardNo;
        $json['contact']		    = $contact;
        $json['contactPhone']		= $contactPhone;
        $json['userRole']		    = $userRole;
        $json['bankcardNo']		    = $bankcardNo;
        $json['bankcode']		    = $bankcode;
        $json['authList']		    = $authList;
        $json['redirectUrl']		= $redirectUrl;
        $json['requestNo']		    = $requestNo;

        // 接口参数 END

        return $this -> submitEscrow($interfaceName, $json, $bizType);
    }

    public function enterpriseInformationUpdate($paramList = [],$bizType = 'ENTERPRISE_INFORMATION_UPDATE') {
        $interfaceName              = 'ENTERPRISE_INFORMATION_UPDATE';

        $platformUserNo             = get_param($paramList,'platformUserNo');               // 平台用户编号

        $redirectUrl                = $this->getRedirectUrl($interfaceName)  . '?request_source=' . session('request_source');
        $requestNo                  = getEscRequesOrder(escrowPara::$RequesType[$bizType][1], $platformUserNo);

        // 接口参数 START
        $json                       = [];
        $json['platformUserNo']		= $platformUserNo;
        $json['redirectUrl']		= $redirectUrl;
        $json['requestNo']		    = $requestNo;
        // 接口参数 END

        return $this -> submitEscrow($interfaceName, $json, $bizType);
    }
    /***
     * 个人绑卡 PERSONAL_BIND_BANKCARD_EXPAND
     *
     * @param array $paramList
     * @param string $bizType
     * @return mixed
     */
    public function personalBindBankcardExpand($paramList = [],$bizType = 'PERSONAL_BIND_BANKCARD_EXPAND') {

        $interfaceName              = 'PERSONAL_BIND_BANKCARD_EXPAND';

        $platformUserNo             = get_param($paramList,'platformUserNo');               // 平台用户编号
        $checkType                  = get_param($paramList,'checkType', 'LIMIT');   // 鉴权验证类型

        $redirectUrl                = $this->getRedirectUrl($interfaceName)  . '?request_source=' . session('request_source');
        $requestNo                  = getEscRequesOrder(escrowPara::$RequesType[$bizType][1], $platformUserNo);

        // 接口参数 START
        $json                       = [];
        $json['platformUserNo']		= $platformUserNo;
        $json['checkType']		    = $checkType;
        $json['redirectUrl']		= $redirectUrl;
        $json['requestNo']		    = $requestNo;
        // 接口参数 END

        return $this -> submitEscrow($interfaceName, $json, $bizType);
    }

    /***
     * 企业绑卡 ENTERPRISE_BIND_BANKCARD
     *
     * @param array $paramList
     * @param string $bizType
     * @return mixed
     */
    public function enterpriseBindBankcard($paramList = [],$bizType = 'ENTERPRISE_BIND_BANKCARD') {

        $interfaceName              = 'ENTERPRISE_BIND_BANKCARD';

        $platformUserNo             = get_param($paramList,'platformUserNo');           // 平台用户编号
        $bankcardNo                 = get_param($paramList,'bankcardNo');               // 银行账户号
        $bankcode                   = get_param($paramList,'bankcode');                 // 银行编码

        $redirectUrl                = $this->getRedirectUrl($interfaceName)  . '?request_source=' . session('request_source');
        $requestNo                  = getEscRequesOrder(escrowPara::$RequesType[$bizType][1], $platformUserNo);

        // 接口参数 START
        $json                       = [];
        $json['platformUserNo']		= $platformUserNo;
        $json['bankcardNo']		    = $bankcardNo;
        $json['bankcode']		    = $bankcode;
        $json['redirectUrl']		= $redirectUrl;
        $json['requestNo']		    = $requestNo;
        // 接口参数 END

        return $this -> submitEscrow($interfaceName, $json, $bizType);
    }

    /***
     * 解绑银行卡 UNBIND_BANKCARD
     *
     * @param array $paramList
     * @param string $bizType
     * @return mixed
     */
    public function unbindBankcard($paramList = [],$bizType = 'UNBIND_BANKCARD') {

        $interfaceName              = 'UNBIND_BANKCARD';

        $platformUserNo             = get_param($paramList,'platformUserNo');           // 平台用户编号

        $redirectUrl                = $this->getRedirectUrl($interfaceName)  . '?request_source=' . session('request_source');
        $requestNo                  = getEscRequesOrder(escrowPara::$RequesType[$bizType][1], $platformUserNo);

        // 接口参数 START
        $json                       = [];
        $json['platformUserNo']		= $platformUserNo;
        $json['redirectUrl']		= $redirectUrl;
        $json['requestNo']		    = $requestNo;
        // 接口参数 END

        return $this -> submitEscrow($interfaceName, $json, $bizType);
    }

    /***
     * 修改密码 RESET_PASSWORD
     *
     * @param array $paramList
     * @param string $bizType
     * @return mixed
     */
    public function resetPassword($paramList = [],$bizType = 'RESET_PASSWORD') {

        $interfaceName              = 'RESET_PASSWORD';

        $platformUserNo             = get_param($paramList,'platformUserNo');           // 平台用户编号

        $redirectUrl                = $this->getRedirectUrl($interfaceName)  . '?request_source=' . session('request_source');
        $requestNo                  = getEscRequesOrder(escrowPara::$RequesType[$bizType][1], $platformUserNo);

        // 接口参数 START
        $json                       = [];
        $json['platformUserNo']		= $platformUserNo;
        $json['redirectUrl']		= $redirectUrl;
        $json['requestNo']		    = $requestNo;
        // 接口参数 END

        return $this -> submitEscrow($interfaceName, $json, $bizType);
    }

    /***
     * 预留手机号更新 MODIFY_MOBILE_EXPAND
     *
     * @param array $paramList
     * @param string $bizType
     * @return mixed
     */
    public function modifyMobileExpand($paramList = [],$bizType = 'MODIFY_MOBILE_EXPAND') {

        $interfaceName              = 'MODIFY_MOBILE_EXPAND';

        $platformUserNo             = get_param($paramList,'platformUserNo');               // 平台用户编号
        $checkType                  = get_param($paramList,'checkType', 'LIMIT');   // 鉴权验证类型

        $redirectUrl                = $this->getRedirectUrl($interfaceName)  . '?request_source=' . session('request_source');
        $requestNo                  = getEscRequesOrder(escrowPara::$RequesType[$bizType][1], $platformUserNo);

        // 接口参数 START
        $json                       = [];
        $json['platformUserNo']		= $platformUserNo;
        $json['checkType']		    = $checkType;
        $json['redirectUrl']		= $redirectUrl;
        $json['requestNo']		    = $requestNo;
        // 接口参数 END

        return $this -> submitEscrow($interfaceName, $json, $bizType);
    }

    /***
     * 会员激活 ACTIVATE_STOCKED_USER
     *
     * @param array $paramList
     * @param string $bizType
     * @return mixed
     */
    public function activateStockedUser($paramList = [],$bizType = 'ACTIVATE_STOCKED_USER') {

        $interfaceName              = 'ACTIVATE_STOCKED_USER';

        $platformUserNo             = get_param($paramList,'platformUserNo');               // 平台用户编号
        $checkType                  = get_param($paramList,'checkType', 'LIMIT');   // 鉴权验证类型
        $authList                   = get_param($paramList,'authList', 'TENDER,REPAYMENT,CREDIT_ASSIGNMENT,COMPENSATORY,WITHDRAW,RECHARGE');      // 鉴权验证类型

        $redirectUrl                = $this->getRedirectUrl($interfaceName)  . '?request_source=' . session('request_source');
        $requestNo                  = getEscRequesOrder(escrowPara::$RequesType[$bizType][1], $platformUserNo);

        // 接口参数 START
        $json                       = [];
        $json['platformUserNo']		= $platformUserNo;
        $json['redirectUrl']		= $redirectUrl;
        $json['requestNo']		    = $requestNo;
        $json['checkType']          = $checkType;
        $json['authList']           = $authList;
        // 接口参数 END

        return $this -> submitEscrow($interfaceName, $json, $bizType);
    }

    /***
     * 根据规则校准用户的支付方式
     *
     * @param $uid
     * @param $rechargeWay
     * @param $defaultExpectPayCompany
     * @return string
     *
     */
    private function getExpectPayCompany($uid, $rechargeWay, $defaultExpectPayCompany) {

        // 通联只支持快捷，非快捷返回默认支付方式【LIANLIAN】
        if (!($rechargeWay == 'SWIFT')) return $defaultExpectPayCompany;

        $startTime = escrowPara::$startAllinpayTime;

        $regTime = M('member') -> where(['id' => $uid]) -> getField('reg_time');

        if ($regTime > $startTime && $startTime) {
            return escrowPara::$allinpayExpectPayCompany;
        }

        return $defaultExpectPayCompany;
    }


    /***
     * 充值
     */
    public function recharge($paramList = [], $bizType = 'MONEY_IN_PLAT') {

        $interfaceName = 'RECHARGE';

        $platformUserNo     = get_param($paramList,'platformUserNo');    // 平台用户编号
        $amount             = get_param($paramList,'amount');            // 获取充值的资金金额
        $rechargeWay        = get_param($paramList,'rechargeWay');       // 支付方式
        $expectPayCompany   = get_param($paramList,'expectPayCompany');  // 偏好支付公司
        $bankcode           = get_param($paramList,'bankcode');          // 银行编码
        $authtradeType      = get_param($paramList,'authtradeType');     // 授权交易类型
        $callbackMode       = get_param($paramList,'callbackMode');      // 快捷充值回调模式，如传入DIRECT_CALLBACK，则订单支付不论成功、失败、处理中均直接同步、异步通知商户；未传入订单仅在支付成功时通知商户；
        $authtenderAmount   = get_param($paramList,'authtenderAmount');  // 授权投标金额，充值成功后可操作对应金额范围内的投标授权预处理；若传入了【授权交易类型】，则此参数必传；
        $projectNo          = get_param($paramList,'projectNo');         // 标的号；若传入了【授权交易类型】，则此参数必传。

        $payType            = get_param($paramList,'payType');
        $redirectUrl        = get_param($paramList,'redirectUrl',$this->getRedirectUrl($interfaceName)  . '?request_source=' . session('request_source'));
        $requestNo          = getEscRequesOrder(escrowPara::$RequesType[$bizType][1], $platformUserNo);

        $this -> uid = $platformUserNo;
        if (is_numeric(escrowPara::$account_transfer[$platformUserNo])) {
            $uid = escrowPara::$account_transfer[$platformUserNo];
            $this -> uid = $platformUserNo;
        } else {
            $uid = $this -> uid;
        }

        $expectPayCompany = $this -> getExpectPayCompany($uid, $rechargeWay, $expectPayCompany);

        // uid 229324 第一个用通联的用户，通联农行和宁波银行关闭，路由至连连支付。2017年12月8日 16:54:11 ZZH
        // 通联已恢复限额，此处路由注释，2017年12月28日 16:06:13 ZZH
        if (($uid >= 229324) && in_array($bankcode, ['ABOC','BKNB']) && (time() < strtotime('2017-12-29 00:00:00'))) {
            $expectPayCompany = apiParam::$defaultExpectPayCompany;
        }

        // 连连维护中或下架的银行直接提示弹框，ZZH 2017年12月25日 17:52:06
        if (($expectPayCompany == apiParam::$defaultExpectPayCompany) && in_array($_GET['source'], [2,3])) {
            $redis = \Addons\Libs\Cache\Redis::getInstance();
            if ($redis -> exists('MAINTENANCE_CHANNEL_BANK_CODE')) {
                $tempBanks = $redis -> get('MAINTENANCE_CHANNEL_BANK_CODE');
            } else {
                $modelBankLimit = M('cg_bank_limit');
                $lastUpdateTime = $modelBankLimit->where(['source_code' => 'll001', 'is_valid' => 1])->order('update_time desc')->limit(1)->getField('update_time');
                $whBanks        = $modelBankLimit->where(['is_valid' => 1, 'source_code' => 'll001', 'update_time' => ['lt', $lastUpdateTime - 300]])->select();
                $tempBanks      = [];
                foreach ($whBanks as $key => $val) {
                    $tempBanks[] = $val['xw_bank_code'];
                }
                $redis -> setex('MAINTENANCE_CHANNEL_BANK_CODE', 600, $tempBanks);
            }
            if (in_array($bankcode, $tempBanks)) {
                $output['code']         = 1;
                $output['status']       = 'INIT';
                $output['errorStatus']  = 'BANK_MAINTENANCE';
                $output['errorMessage'] = '您的银行渠道正在维护中，如有充值需要可在账户设置解绑银行卡后绑定新的银行卡进行充值。';
                throw_exception(json_encode($output));
            }
        }

        // 增加充值记录 START
        $paydetail['uid']       = $uid;
        $paydetail['bank']      = $bankcode;
        $paydetail['money']     = ffloor($amount);
        $paydetail['fee']       = 0;
        $paydetail['status']    = 0;
        $paydetail['add_time']  = time();
        $paydetail['add_ip']    = get_client_ip();
        $paydetail['billno']    = $requestNo;
        $paydetail['way']       = $expectPayCompany;
        $paydetail['recharge_way']   = $rechargeWay;
        $paydetail['source']    = $_GET['source'] ? $_GET['source'] : '0';
        $res = M("member_payonline") -> add($paydetail);
        if (!$res) {
            throw new \Exception('充值记录写入数据失败');
            //exit('充值记录写入数据失败，SQL：'. M()->getLastSql());
        }
        // 增加充值记录 END

        // 接口参数 START
        $json                       = [];
        $json['platformUserNo']     = $this -> uid;             // 平台用户编号
        $json['requestNo']          = $requestNo;               // 请求流水号
        $json['amount']             = $amount;                  // 充值金额
        $json['expectPayCompany']   = $expectPayCompany;        // 偏好支付公司，见【支付公司】
        $json['rechargeWay']        = $rechargeWay;             // 【支付方式】，支持网银（WEB）、快捷支付（SWIFT）
        $json['bankcode']           = $bankcode;                // 【见银行编码】若支付方式为快捷支付，此处必填；若支付方式为网银，此处可以不填；网银支付方式下，若此处填写，则直接跳转至银行页面，不填则跳转至支付公司收银台页面；
        $json['payType']            = $payType;                 // 【网银类型】，若支付方式填写为网银，且对【银行编码】进行了填写，则此处必填。
        $json['bankcode']           = $bankcode;                // 【见银行编码】若支付方式为快捷支付，此处必填；若支付方式为网银，此处可以不填；网银支付方式下，若此处填写，则直接跳转至银行页面，不填则跳转至支付公司收银台页面；
        $json['redirectUrl']        = $redirectUrl;             // 页面回跳URL
        $json['expired']            = $this->getExpiredTime($interfaceName);  // 超过此时间即页面过期
        $json['authtradeType']      = $authtradeType;           // 【授权交易类型】，若想实现充值+投标单次授权，则此参数必传，固定“TENDER”
        $json['authtenderAmount']   = $authtenderAmount;        // 授权投标金额，充值成功后可操作对应金额范围内的投标授权预处理；若传入了【授权交易类型】，则此参数必传；
        $json['projectNo']          = $projectNo;               // 标的号；若传入了【授权交易类型】，则此参数必传。
        $json['callbackMode']       = $callbackMode;            // 快捷充值回调模式，如传入DIRECT_CALLBACK，则订单支付不论成功、失败、处理中均直接同步、异步通知商户；未传入订单仅在支付成功时通知商户；
        // 接口参数 END

        return $this -> submitEscrow($interfaceName, $json, $bizType);
    }

    /***
     * 提现申请 WITHDRAW
     */
    public function withdraw($paramList = [], $bizType = 'MONEY_OUT_PLAT') {

        $interfaceName = 'WITHDRAW';
        // TODO 提现申请需先记录数据到平台

        $platformUserNo = get_param($paramList,'platformUserNo');
        $amount         = get_param($paramList,'amount');
        $type           = get_param($paramList,'type', 'withdraw');

        // 功能账户提现，$platformUserNo 转 uid
        if (!is_numeric($platformUserNo) && is_numeric(escrowPara::$account_transfer[$platformUserNo])) {
            $uid = escrowPara::$account_transfer[$platformUserNo];
        } else {
            $uid = $platformUserNo;
        }

        $requestNo          = getEscRequesOrder(escrowPara::$RequesType[$bizType][1], $uid);

        $checkRes = $this -> checkWithdraw($uid, $amount, $type);
        if (is_array($checkRes)) {
            // 平台功能账户不收手续费
            if (!empty($checkRes['withdraw_fee']) && (!(!is_numeric($platformUserNo) && is_numeric(escrowPara::$account_transfer[$platformUserNo])))) {
                $commission = $checkRes['withdraw_fee'];
            } else {
                $commission = 0;
            }
        } else {
            $output['code'] = 1;
            $output['status'] = 'INIT';
            $output['errorMessage'] = ''.$checkRes;
            throw_exception(json_encode($output));
        }

        M()->startTrans();

        $withdraw_money = $amount;

        // 2016年6月16日11:18:30 by lh  从优先使用账户资金池修改为优先使用回款资金池
        $cash_pool = getMoneyPool($uid, $withdraw_money, 'backmoney');  //取得资金池
        $moneydata['withdraw_bankid']  = ''.$checkRes['cardId'];
        $moneydata['withdraw_money']   = $withdraw_money;
        $moneydata['withdraw_account'] = $cash_pool['account_money'];
        $moneydata['withdraw_back']    = $cash_pool['back_money'];
        $moneydata['withdraw_reward']  = $cash_pool['reward_money'];
        $moneydata['withdraw_priority']= $cash_pool['priority_money'];
        $moneydata['withdraw_fee']     = $commission;
        $moneydata['withdraw_status']  = -1;    // 默认状态：-1【用户发起提现】存管返回后状态改成0【待审核】
        $moneydata['uid']              = $uid;
        $moneydata['add_time']         = time();
        $moneydata['add_ip']           = get_client_ip();
        $moneydata['request_no']        = $requestNo;
        $moneydata['source']            = $_GET['source'] ? $_GET['source'] : '0';

        switch (strtolower($type)) {
            case 'wages' :
                $moneydata['withdraw_account'] = 0;
                $moneydata['withdraw_back']    = 0;
                $moneydata['withdraw_reward']  = $withdraw_money;
                // $moneydata['withdraw_priority']= $withdrawMoney;
                $moneydata['trade_no']         = "salary";
                break;
            case 'withdraw' :
                $moneydata['withdraw_account'] = $cash_pool['account_money'];
                $moneydata['withdraw_back']    = $cash_pool['back_money'];
                $moneydata['withdraw_reward']  = $cash_pool['reward_money'];
                $moneydata['withdraw_priority']= $cash_pool['priority_money'];
                break;
        }
        $newid = M('member_withdraw')->add($moneydata);

        if ($newid) {
            switch (strtolower($type)) {
                case 'withdraw' :
                    noTransMoneyLog($uid, 'cash_wait', -$cash_pool['account_money'], -$cash_pool['back_money'], -$cash_pool['reward_money'], 0, "提现,默认自动扣减手续费{$commission}元", '0', $newid);
                    // 提现模板
                    mq_producer('wechat', get_trade_message($uid, "您好，您的小鸡理财账户最新交易提醒\n", '提现申请', sprintf("%.2f", $withdraw_money)."元", '提现申请成功', C('CONFIG_WEIXIN.WITHDRAW_JOIN_LIST'), "点击【详情】查看更多交易信息"));
                    break;
            }
            M()->commit();
        } else{
            M()->rollback();
            $output['code'] = 1;
            $output['status'] = 'INIT';
            $output['errorMessage'] = '提现申请失败'.": ".M()->getLastSql();
            throw_exception(json_encode($output));
        }

        $redirectUrl        = $this->getRedirectUrl($interfaceName) . '?request_source=' . session('request_source');

        // 接口参数 START
        $json                       = [];
        $json['platformUserNo']     = $platformUserNo;                  // 平台用户编号
        $json['requestNo']          = $requestNo;                       // 请求流水号
        $json['expired']            = $this->getExpiredTime($interfaceName);          // 超过此时间即页面过期
        $json['redirectUrl']        = $redirectUrl;                     // 页面回跳URL
        $json['withdrawType']       = 'NORMAL';                         // 【提现方式】，NORMAL 表示普通T1，URGENT 表示加急T0，NORMAL_URGENT表示智能T0;
        $json['withdrawForm']       = 'CONFIRMED';                      // 提现类型，IMMEDIATE 为直接提现，CONFIRMED 为待确认提现，不传默认为直接提现方式；仅直接提现支持加急T0 或智能T0；
        $json['amount']             = $amount;                          // 提现金额
        $json['commission']         = $commission;                      // 平台佣金【提现手续费】
        // 接口参数 END

        return $this -> submitEscrow($interfaceName, $json, $bizType);
    }

    /***
     * 提现确认
     *
     * @param string $bizType
     */
    public function confirmWithdraw($paramList = [],$bizType = 'MONEY_OUT_PLAT_CONFIRM') {
        $interfaceName = 'CONFIRM_WITHDRAW';
        // TODO 提现申请需先记录数据到平台

        $preTransactionNo   = get_param($paramList,'preTransactionNo');
        $dealInfo           = get_param($paramList,'deal_info', '');
        $dealTime           = get_param($paramList,'deal_time', time());
        $dealUser           = get_param($paramList,'deal_user', session('adminname'));

        $model = M('member_withdraw');

        $withdrawInfo       = $model -> where(['request_no' => $preTransactionNo]) -> find();
        if (empty($withdrawInfo)) throw_exception('未找到订单信息');
        if ($withdrawInfo['withdraw_status'] != 1) throw_exception('订单为非待审核状态');

        $requestNo          = getEscRequesOrder(escrowPara::$RequesType[$bizType][1], $withdrawInfo['uid']);

        $save = [];
        $save['confirm_request_no'] = $requestNo;
        $save['deal_info']          = $dealInfo;
//        $save['withdraw_status']    = 2;
        $save['deal_time']          = $dealTime;
        $save['deal_user']          = $dealUser;

        $res = $model -> where(['id' => $withdrawInfo['id']]) -> save($save);
        if (!$res) throw_exception('修改订单信息失败');

        // 接口参数 START
        $json                       = [];
        $json['requestNo']          = ''.$requestNo;            // 请求流水号
        $json['preTransactionNo']   = ''.$preTransactionNo;     // 待确认提现请求流水号
        // 接口参数 END

        return $this -> submitEscrow($interfaceName, $json, $bizType);
    }

    /***
     * 取消提现
     *
     * @param string $bizType
     */
    public function cancelWithdraw($paramList = [],$bizType = 'MONEY_OUT_PLAT_CANCEL') {
        $interfaceName = 'CANCEL_WITHDRAW';
        // TODO 提现申请需先记录数据到平台

        $preTransactionNo   = get_param($paramList,'preTransactionNo');
        $dealInfo           = get_param($paramList,'deal_info', '');
        $dealTime           = get_param($paramList,'deal_time', time());
        $dealUser           = get_param($paramList,'deal_user', session('adminname'));

        $model = M('member_withdraw');

        $withdrawInfo       = $model -> where(['request_no' => $preTransactionNo]) -> find();
        if (empty($withdrawInfo)) throw_exception('未找到订单信息');
        if ($withdrawInfo['withdraw_status'] == 2) throw_exception('订单已完成提现');

        $requestNo          = getEscRequesOrder(escrowPara::$RequesType[$bizType][1], $withdrawInfo['uid']);

        $save = [];
        $save['confirm_request_no'] = $requestNo;
        $save['deal_info']          = $dealInfo;
//        $save['withdraw_status']    = 0;
        $save['deal_time']          = $dealTime;
        $save['deal_user']          = $dealUser;

        $res = $model -> where(['id' => $withdrawInfo['id']]) -> save($save);
        if (!$res) throw_exception('修改订单信息失败');

        // 接口参数 START
        $json                       = [];
        $json['requestNo']          = ''.$requestNo;            // 请求流水号
        $json['preTransactionNo']   = ''.$preTransactionNo;     // 待确认提现请求流水号
        // 接口参数 END

        return $this -> submitEscrow($interfaceName, $json, $bizType);
    }

    /***
     * 自动提现 _AUTOWITHDRAW
     */
    public function autoWithdraw($paramList = [], $bizType = 'AUTO_WITHDRAW') {

        $interfaceName = 'AUTO_WITHDRAW';
        // TODO 提现申请需先记录数据到平台

        $platformUserNo = get_param($paramList,'platformUserNo');
        $amount         = get_param($paramList,'amount');
        $type           = get_param($paramList,'type', 'withdraw');
        $withdrawType   = get_param($paramList,'withdrawType', 'URGENT');
        // 功能账户提现，$platformUserNo 转 uid
        if (!is_numeric($platformUserNo) && is_numeric(escrowPara::$account_transfer[$platformUserNo])) {
            $uid = escrowPara::$account_transfer[$platformUserNo];
        } else {
            $uid = $platformUserNo;
        }

        $requestNo          = getEscRequesOrder(escrowPara::$RequesType[$bizType][1], $uid);

        $checkRes = $this -> checkWithdraw($uid, $amount, $type);
        if (is_array($checkRes)) {
            // 借款人提现不收手续费
            $commission = 0;
        } else {
            $output['code'] = 1;
            $output['status'] = 'INIT';
            $output['errorMessage'] = ''.$checkRes;
            throw_exception(json_encode($output));
        }

        M()->startTrans();

        $withdraw_money = $amount;

        // 2016年6月16日11:18:30 by lh  从优先使用账户资金池修改为优先使用回款资金池
        $cash_pool = getMoneyPool($uid, $withdraw_money, 'backmoney');  //取得资金池
        $moneydata['withdraw_bankid']   = '' . $checkRes['cardId'];
        $moneydata['withdraw_money']    = $withdraw_money;
        $moneydata['withdraw_account']  = $cash_pool['account_money'];
        $moneydata['withdraw_back']     = $cash_pool['back_money'];
        $moneydata['withdraw_reward']   = $cash_pool['reward_money'];
        $moneydata['withdraw_priority'] = $cash_pool['priority_money'];
        $moneydata['withdraw_fee']      = $commission;
        $moneydata['withdraw_status']   = 1;    //  0【待审核】
        $moneydata['uid']               = $uid;
        $moneydata['add_time']          = time();
        $moneydata['add_ip']            = get_client_ip();
        $moneydata['request_no']        = $requestNo;
        $moneydata['source']            = $_GET['source'] ? $_GET['source'] : '0';
        if ($moneydata['withdraw_status'] == 1) {
            $moneydata['deal_info']         = '平台用户自动提现';
            $moneydata['deal_time']         = time();
            $moneydata['deal_user']         = '平台';
            $moneydata['second_fee']        = $commission;
            $moneydata['success_money']     = $withdraw_money - $commission;
        }

        switch (strtolower($type)) {
            case 'wages' :
                $moneydata['withdraw_account'] = 0;
                $moneydata['withdraw_back']    = 0;
                $moneydata['withdraw_reward']  = $withdraw_money;
                // $moneydata['withdraw_priority']= $withdrawMoney;
                $moneydata['trade_no']         = "salary";
                break;
            case 'withdraw' :
                $moneydata['withdraw_account'] = $cash_pool['account_money'];
                $moneydata['withdraw_back']    = $cash_pool['back_money'];
                $moneydata['withdraw_reward']  = $cash_pool['reward_money'];
                $moneydata['withdraw_priority']= $cash_pool['priority_money'];
                break;
        }
        $newid = M('member_withdraw')->add($moneydata);

        if ($newid) {
            switch (strtolower($type)) {
                case 'withdraw' :
//                    noTransMoneyLog($uid, 'cash_wait', -$cash_pool['account_money'], -$cash_pool['back_money'], -$cash_pool['reward_money'], 0, "提现,默认自动扣减手续费{$commission}元", '0', $newid);
                    break;
            }
            M()->commit();
        } else{
            M()->rollback();
            $output['code'] = 1;
            $output['status'] = 'INIT';
            $output['errorMessage'] = '提现申请失败'.": ".M()->getLastSql();
            throw_exception(json_encode($output));
        }

        // 接口参数 START
        $json                       = [];
        $json['platformUserNo']     = $platformUserNo;                  // 平台用户编号
        $json['requestNo']          = $requestNo;                       // 请求流水号
        $json['withdrawType']   	= $withdrawType;                    // 【提现方式】，NORMAL 表示普通T1，URGENT 表示加急T0，NORMAL_URGENT表示智能T0;
        $json['amount']             = $amount;                          // 提现金额
        $json['commission']         = $commission;                      // 平台佣金【提现手续费】
        // 接口参数 END

        if ($newid) {
            $res = $this -> submitEscrow($interfaceName, $json, $bizType);
            $res = json_decode($res, true);

            if ($res['status'] != 'SUCCESS') {

                $r = M('member_withdraw') -> where(['id' => $newid]) -> save(['withdraw_status' => 3]);

                $output['code'] = 1;
                $output['status'] = 'INIT';
                $output['errorMessage'] = "自动提现新网返回失败: ".$res['errorCode'].'|'.$res['errorMessage'];
                throw_exception(json_encode($output));
            } else {
                switch (strtolower($type)) {
                    case 'withdraw' :
                        M()->startTrans();
                        noTransMoneyLog($uid, 'cash_wait', -$cash_pool['account_money'], -$cash_pool['back_money'], -$cash_pool['reward_money'], 0, "提现,默认自动扣减手续费{$commission}元", '0', $newid);
                        M()->commit();
                        mq_producer('wechat', get_trade_message($uid, "您好，您的小鸡理财账户最新交易提醒\n", '提现申请', sprintf("%.2f", $withdraw_money)."元", '提现申请成功', C('CONFIG_WEIXIN.WITHDRAW_JOIN_LIST'), "点击【详情】查看更多交易信息"));
                        break;
                }
            }
        }
        return $res;
    }


    /***
     * 创建标的
     *
     * @param string $bizType
     */
    public function establishProject($paramList = [],$bizType = 'ESTABLISH_PROJECT') {

        $interfaceName      = 'ESTABLISH_PROJECT';

        $projectNo          = get_param($paramList,'projectNo' );              // 标的号
        $platformUserNo     = get_param($paramList,'platformUserNo');          // 借款方平台用户编号
        $projectAmount      = get_param($paramList,'projectAmount');           // 标的金额
        $projectName        = get_param($paramList,'projectName');             // 标的名称
        $projectDescription = get_param($paramList,'projectDescription');      // 标的描述 N
        $projectType        = get_param($paramList,'projectType', 'STANDARDPOWDER');               // 标的类型 STANDARDPOWDER 散标
        $projectPeriod      = get_param($paramList,'projectPeriod');           // 标的期限（单位：天）（只做记录，不做严格校验）
        $annnualInterestRate= get_param($paramList,'annnualInterestRate');     // 年化利率（只做记录，不做严格校验）
        $repaymentWay       = get_param($paramList,'repaymentWay', 'FIRSEINTREST_LASTPRICIPAL') ;  // 见【还款方式】（只做记录，不做严格校验）
        $extend             = get_param($paramList,'extend');                  // 标的扩展信息 O[键值对]

        $requestNo          = getEscRequesOrder(escrowPara::$RequesType[$bizType][1], $platformUserNo);

        // 接口参数 START
        $json                       = [];
        $json['platformUserNo']     = $platformUserNo;      // 借款方平台用户编号
        $json['requestNo']          = $requestNo;           // 请求流水号
        $json['projectNo']          = $projectNo;           // 标的号
        $json['projectAmount']      = $projectAmount;       // 标的金额
        $json['projectName']        = $projectName;         // 标的名称
        $json['projectDescription'] = $projectDescription;  // 标的描述 N
        $json['projectType']        = $projectType;         // 标的类型 STANDARDPOWDER 散标
        $json['projectPeriod']      = $projectPeriod;       // 标的期限（单位：天）（只做记录，不做严格校验）
        $json['annnualInterestRate']= $annnualInterestRate; // 年化利率（只做记录，不做严格校验）
        $json['repaymentWay']       = $repaymentWay;        // 见【还款方式】（只做记录，不做严格校验）
        $json['extend']             = $extend;       // 标的扩展信息 O[键值对]
        // 接口参数 END

        return $this -> submitEscrow($interfaceName, $json, $bizType);
    }

    /***
     * 变更标的
     *
     * @param string $bizType
     */
    public function modifyProject($paramList = [],$bizType = 'MODIFY_PROJECT') {

        $interfaceName      = 'MODIFY_PROJECT';

        $projectNo          = get_param($paramList,'projectNo' );      // 标的号
        $status             = get_param($paramList,'status');          // 标的状态

        $requestNo          = getEscRequesOrder(escrowPara::$RequesType[$bizType][1], 0);

        // 接口参数 START
        $json                       = [];
        $json['requestNo']          = $requestNo;           // 请求流水号
        $json['projectNo']          = $projectNo;           // 标的号
        $json['status']             = $status;              // 标的状态
        // 接口参数 END

        return $this -> submitEscrow($interfaceName, $json, $bizType);
    }

    /***
     * 用户预处理 USER_PRE_TRANSACTION
     *
     * @param string $bizType
     */
    public function userPreTransaction($paramList = [],$bizType = 'USER_PRE_TRANSACTION') {

        $interfaceName      = 'USER_PRE_TRANSACTION';

        $platformUserNo     = get_param($paramList,'platformUserNo');    // 借款方平台用户编号
        $bType              = get_param($paramList,'bizType');           // 预处理业务类型  TENDER|投标 REPAYMENT|还款 CREDIT_ASSIGNMENT|债权认购 COMPENSATORY|代偿
        $amount             = get_param($paramList,'amount');            // 冻结金额
        $preMarketingAmount = get_param($paramList,'preMarketingAmount');// 预备使用的红包金额，只记录不冻结，仅限投标业务类型
        $remark             = get_param($paramList,'remark');            // 备注
        $projectNo          = get_param($paramList,'projectNo');         // 标的号
        $share              = get_param($paramList,'share');             // 购买债转份额，业务类型为债权认购时，需要传此参数
        $creditsaleRequestNo= get_param($paramList,'creditsaleRequestNo');// 债权出让请求流水号，只有债权认购业务需填此参数

        $requestNo          = getEscRequesOrder(escrowPara::$RequesType[$bizType][1], $platformUserNo);

        // 接口参数 START
        $json                       = [];
        $json['requestNo']          = $requestNo;           // 请求流水号
        $json['platformUserNo']     = $platformUserNo;      // 借款方平台用户编号
        $json['bizType']            = $bType;               // 预处理业务类型
        $json['projectNo']          = $projectNo;           // 标的号
        $json['amount']             = $amount;              // 冻结金额
        $json['preMarketingAmount'] = $preMarketingAmount;  // 预备使用的红包金额
        $json['expired']            = $this->getExpiredTime(); // 超过此时间即页面过期
        $json['remark']             = $remark;              // 备注
        $json['redirectUrl']        = $this->getRedirectUrl($interfaceName);         // 页面回跳URL
        $json['share']              = $share;               // 购买债转份额，业务类型为债权认购时，需要传此参数
        $json['creditsaleRequestNo']= $creditsaleRequestNo; // 债权出让请求流水号，只有债权认购业务需填此参数
        // 接口参数 END

        return $this -> submitEscrow($interfaceName, $json, $bizType);
    }

    /***
     * 预处理取消 CANCEL_PRE_TRANSACTION
     *
     * @param string $bizType
     */
    public function cancelPreTransaction($paramList = [],$bizType = 'CANCEL_PRE_TRANSACTION') {
        $interfaceName      = 'CANCEL_PRE_TRANSACTION';

        $preTransactionNo   = get_param($paramList,'preTransactionNo' );   // 预处理业务流水号
        $amount             = get_param($paramList,'amount');              // 取消金额

        $requestNo          = getEscRequesOrder(escrowPara::$RequesType[$bizType][1], 0);

        // 接口参数 START
        $json                       = [];
        $json['requestNo']          = $requestNo;           // 请求流水号
        $json['preTransactionNo']   = $preTransactionNo;    // 预处理业务流水号
        $json['amount']             = $amount;              // 取消金额
        // 接口参数 END

        return $this -> submitEscrow($interfaceName, $json, $bizType);
    }

    /***
     * 单笔交易 SYNC_TRANSACTION
     *
     * @param string $bizType
     */
    public function syncTransaction($paramList = [],$bizType = 'SYNC_TRANSACTION') {
        $interfaceName      = 'SYNC_TRANSACTION';

        $tradeType          = get_param($paramList,'tradeType');       // 交易类型
        $projectNo          = get_param($paramList,'projectNo');       // 标的号
        $saleRequestNo      = get_param($paramList,'saleRequestNo');   // 债权出让请求流水号
        $details            = get_param($paramList,'details');         // 业务明细

        $requestNo          = getEscRequesOrder(escrowPara::$RequesType[$bizType][1], 0);

        // 接口参数 START
        $json                   = [];
        $json['requestNo']      = $requestNo;       // 批次号
        $json['tradeType']      = $tradeType;       // 交易类型
        $json['projectNo']      = $projectNo;       // 标的号
        $json['saleRequestNo']  = $saleRequestNo;   // 债权出让请求流水号
        $json['details']        = $details;         // 业务明细
        // 接口参数 END

        return $this -> submitEscrow($interfaceName, $json, $bizType);
    }

    /***
     * 批量交易 ASYNC_TRANSACTION
     *
     * @param string $bizType
     */
    public function asyncTransaction($paramList = [],$bizType = 'ASYNC_TRANSACTION') {
        $interfaceName      = 'ASYNC_TRANSACTION';

        $bizDetails         = get_param($paramList,'bizDetails');      // 交易明细

        $batchNo          = getEscRequesOrder(escrowPara::$RequesType[$bizType][1], 0);

        // 接口参数 START
        $json                       = [];
        $json['batchNo']            = $batchNo;         // 批次号
        $json['bizDetails']         = $bizDetails;      // 交易明细
        // 接口参数 END

        return $this -> submitEscrow($interfaceName, $json, $bizType);
    }

    /***
     * 用户授权 USER_AUTHORIZATION
     */
    public function userAuthorization($paramList = [],$bizType = 'USER_AUTHORIZATION') {
        $interfaceName              = 'USER_AUTHORIZATION';

        $platformUserNo             = get_param($paramList,'platformUserNo');      // 平台用户编号
        $authList                   = get_param($paramList,'authList');            // 用户授权列表

        $requestNo                  = getEscRequesOrder(escrowPara::$RequesType[$bizType][1], $platformUserNo);

        // 接口参数 START
        $json                       = [];
        $json['platformUserNo']     = $platformUserNo;                                  // 平台用户编号
        $json['requestNo']          = $requestNo;                                       // 请求流水号
        $json['authList']           = $authList;                                        // 用户授权列表
        $json['redirectUrl']        = $this->getRedirectUrl($interfaceName);  // 页面回跳URL
        // 接口参数 END

        return $this -> submitEscrow($interfaceName, $json, $bizType);
    }

    /***
     * 用户取消授权 CANCEL_USER_AUTHORIZATION
     */
    public function cancelUserAuthorization($paramList = [],$bizType = 'CANCEL_USER_AUTHORIZATION') {
        $interfaceName              = 'CANCEL_USER_AUTHORIZATION';

        $platformUserNo             = get_param($paramList,'platformUserNo');      // 平台用户编号
        $authList                   = get_param($paramList,'authList');            // 用户授权列表

        $requestNo                  = getEscRequesOrder(escrowPara::$RequesType[$bizType][1], $platformUserNo);

        // 接口参数 START
        $json                       = [];
        $json['platformUserNo']     = $platformUserNo;                                  // 平台用户编号
        $json['requestNo']          = $requestNo;                                       // 请求流水号
        $json['authList']           = $authList;                                        // 用户授权列表
        // 接口参数 END

        return $this -> submitEscrow($interfaceName, $json, $bizType);
    }

    /***
     * 授权预处理 USER_AUTO_PRE_TRANSACTION
     */
    public function userAutoPreTransaction($paramList = [],$bizType = 'USER_AUTO_PRE_TRANSACTION') {
        $interfaceName              = 'USER_AUTO_PRE_TRANSACTION';

        $platformUserNo             = get_param($paramList,'platformUserNo');      // 平台用户编号
        $originalRechargeNo         = get_param($paramList,'originalRechargeNo');  // 关联充值请求流水号
        $bType                      = get_param($paramList,'bizType');             // 预处理业务类型
        $amount                     = get_param($paramList,'amount');              // 冻结金额
        $preMarketingAmount         = get_param($paramList,'preMarketingAmount');  // 预备使用的红包金额
        $remark                     = get_param($paramList,'remark');              // 备注
        $share                      = get_param($paramList,'share');               // 购买债转份额
        $projectNo                  = get_param($paramList,'projectNo');           // 标的号
        $creditsaleRequestNo        = get_param($paramList,'creditsaleRequestNo'); // 债权出让请求流水号

        $requestNo                  = getEscRequesOrder(escrowPara::$RequesType[$bizType][1], $platformUserNo);

        // 接口参数 START
        $json                           = [];
        $json['platformUserNo']         = $platformUserNo;      // 平台用户编号
        $json['requestNo']              = $requestNo;           // 请求流水号
        $json['originalRechargeNo']     = $originalRechargeNo;  // 关联充值请求流水号
        $json['bizType']                = $bType;               // 预处理业务类型
        $json['amount']                 = $amount;              // 冻结金额
        $json['preMarketingAmount']     = $preMarketingAmount;  // 预备使用的红包金额
        $json['remark']                 = $remark;              // 备注
        $json['share']                  = $share;               // 购买债转份额
        $json['projectNo']              = $projectNo;           // 标的号
        $json['creditsaleRequestNo']    = $creditsaleRequestNo; // 债权出让请求流水号
        // 接口参数 END

        return $this -> submitEscrow($interfaceName, $json, $bizType);
    }

    /***
     * 验密扣费 VERIFY_DEDUCT 【平台账户取值需处理】
     *
     * @param string $bizType
     */
    public function verifyDeduct($paramList = [],$bizType = 'VERIFY_DEDUCT') {

        $interfaceName = 'VERIFY_DEDUCT';

        $platformUserNo         = get_param($paramList,'platformUserNo');        // 出款方平台用户编号
        $amount                 = get_param($paramList,'amount');                // 扣费金额
        $customDefine           = get_param($paramList,'customDefine');          // 扣费说明
        $targetPlatformUserNo   = get_param($paramList,'targetPlatformUserNo');  // 收款方平台用户编号

        $requestNo              = getEscRequesOrder(escrowPara::$RequesType[$bizType][1], $platformUserNo);

        // 接口参数 START
        $json                       = [];
        $json['requestNo']          = ''.$requestNo;            // 请求流水号
        $json['platformUserNo']     = ''.$platformUserNo;       // 出款方平台用户编号
        $json['amount']             = ''.$amount;               // 扣费金额
        $json['customDefine']       = ''.$customDefine;         // 扣费说明
        $json['targetPlatformUserNo']  = ''.$targetPlatformUserNo;         // 收款方平台用户编号
        $json['expired']            = $this->getExpiredTime(); // 超过此时间即页面过期
        $json['redirectUrl']        = $this->getRedirectUrl($interfaceName);         // 页面回跳URL
        // 接口参数 END

        return $this -> submitEscrow($interfaceName, $json, $bizType);
    }

    /***
     * 资金冻结 FREEZE
     *
     * @param string $bizType
     */
    public function freeze($paramList = [],$bizType = 'FREEZE') {

        $interfaceName = 'FREEZE';

        $platformUserNo         = get_param($paramList,'platformUserNo');           // 出款方平台用户编号
        $generalFreezeRequestNo = get_param($paramList,'generalFreezeRequestNo');   // 通用冻结请求流水号（若传入，则为“通用冻结”，且不能传入requestNo）
        $amount                 = get_param($paramList,'amount');                   // 冻结金额

        $requestNo              = getEscRequesOrder(escrowPara::$RequesType[$bizType][1], $platformUserNo);

        // 接口参数 START
        $json                       = [];
        $json['requestNo']          = $generalFreezeRequestNo ? '' : $requestNo;            // 请求流水号
        $json['generalFreezeRequestNo']   = $generalFreezeRequestNo;            // 通用冻结请求流水号
        $json['platformUserNo']     = $platformUserNo;       // 出款方平台用户编号
        $json['amount']             = $amount;               // 扣费金额
        // 接口参数 END

        return json_encode(array_merge(json_decode($this -> submitEscrow($interfaceName, $json, $bizType), true), $json));
    }

    /***
     * 资金解冻 UNFREEZE
     *
     * @param string $bizType
     */
    public function unfreeze($paramList = [],$bizType = 'UNFREEZE') {

        $interfaceName = 'UNFREEZE';

        $platformUserNo         = get_param($paramList,'platformUserNo');           // 出款方平台用户编号
        $originalFreezeRequestNo= get_param($paramList,'originalFreezeRequestNo');  // 通用冻结请求流水号（若传入，则为“通用冻结”，且不能传入requestNo）
        $amount                 = get_param($paramList,'amount');                   // 冻结金额

        $requestNo              = getEscRequesOrder(escrowPara::$RequesType[$bizType][1], $platformUserNo);

        // 接口参数 START
        $json                       = [];
        $json['requestNo']          = $requestNo;            // 请求流水号
        $json['originalFreezeRequestNo']  = ''.$originalFreezeRequestNo;   // 原冻结的请求流水号
        $json['platformUserNo']     = ''.$platformUserNo;       // 出款方平台用户编号
        $json['amount']             = ''.$amount;               // 扣费金额
        // 接口参数 END

        return json_encode(array_merge(json_decode($this -> submitEscrow($interfaceName, $json, $bizType), true), $json));
    }

    /***
     * 对账文件下载 DOWNLOAD_CHECKFILE
     *
     * @param string $bizType
     */
    public function downloadCheckfile($paramList = [],$bizType = 'DOWNLOAD_CHECKFILE') {

        $interfaceName = 'DOWNLOAD_CHECKFILE';

        $fileDate               = get_param($paramList,'fileDate');           // 对账文件日期

        // 接口参数 START
        $json                   = [];
        $json['fileDate']       = ''.$fileDate;               // 对账文件日期
        // 接口参数 END

        return $this -> submitEscrow($interfaceName, $json, $bizType);
    }

    /***
     * 对账文件确认 CONFIRM_CHECKFILE
     *
     * @param string $bizType
     */
    public function confirmCheckfile($paramList = [],$bizType = 'CONFIRM_CHECKFILE') {

        $interfaceName = 'CONFIRM_CHECKFILE';


        $fileDate               = get_param($paramList,'fileDate');           // 对账文件日期
        $detail                 = get_param($paramList,'detail');

        $requestNo              = getEscRequesOrder(escrowPara::$RequesType[$bizType][1], 0);

        // 接口参数 START
        $json                       = [];
        $json['fileDate']       = $fileDate;       // 对账文件日期
        $json['detail']         = $detail;
        $json['requestNo']      = $requestNo;
        // 接口参数 END

        return $this -> submitEscrow($interfaceName, $json, $bizType);
    }

    /***
     * 交易密码解冻 UNFREEZE_TRADE_PASSWORD
     *
     * @param string $bizType
     */
    public function unfreezeTradePassword($paramList = [],$bizType = 'UNFREEZE_TRADE_PASSWORD') {

        $interfaceName = 'UNFREEZE_TRADE_PASSWORD';

        $platformUserNo         = get_param($paramList,'platformUserNo');           // 平台用户编号

        $requestNo              = getEscRequesOrder(escrowPara::$RequesType[$bizType][1], $platformUserNo);

        // 接口参数 START
        $json                       = [];
        $json['platformUserNo']     = $platformUserNo;       // 平台用户编号
        $json['requestNo']          = $requestNo;
        // 接口参数 END

        return $this -> submitEscrow($interfaceName, $json, $bizType);
    }

    /***
     * 用户信息查询 QUERY_USER_INFORMATION
     */
    public function queryUserInformation($paramList = [],$bizType = 'QUERY_USER_INFORMATION') {
        $interfaceName              = 'QUERY_USER_INFORMATION';

        $platformUserNo             = get_param($paramList,'platformUserNo');      // 平台用户编号

        // 接口参数 START
        $json                       = [];
        $json['platformUserNo']     = $platformUserNo;         // 平台用户编号
        // 接口参数 END

        return $this -> submitEscrow($interfaceName, $json, $bizType);
    }

    /***
     * 单笔交易查询 QUERY_TRANSACTION
     */
    public function queryTransaction($paramList = [],$bizType = 'QUERY_TRANSACTION') {
        $interfaceName              = 'QUERY_TRANSACTION';

        $platformUserNo             = get_param($paramList,'platformUserNo');       // 平台用户编号
        $transactionType            = get_param($paramList,'transactionType');      // 交易查询类型
        $requestNo                  = get_param($paramList,'requestNo');            // 请求流水号

        // 接口参数 START
        $json                       = [];
        $json['platformUserNo']     = $platformUserNo;          // 平台用户编号
        $json['transactionType']    = $transactionType;         // 交易查询类型
        $json['requestNo']          = $requestNo;
        // 接口参数 END

        return $this -> submitEscrow($interfaceName, $json, $bizType);
    }

    /***
     * 标的信息查询 QUERY_PROJECT_INFORMATION
     */
    public function queryProjectInformation($paramList = [],$bizType = 'QUERY_PROJECT_INFORMATION') {
        $interfaceName              = 'QUERY_PROJECT_INFORMATION';

        $projectNo                  = get_param($paramList,'projectNo');      // 标的号

        // 接口参数 START
        $json                       = [];
        $json['projectNo']          = $projectNo;         // 标的号
        // 接口参数 END

        return $this -> submitEscrow($interfaceName, $json, $bizType);
    }


    /***
     * App 提现检验接口（可用余额+工资）
     *
     * 用于手续费等检测
     *
     * @param bool $status
     */
    private function checkWithdraw($uid, $amount, $type) {

        if (empty($amount)) return '充值金额不能为空';

        $mmm = M('member_banks')->where(array('uid'=>$uid, 'status' => 1))->find();
//        if (!$mmm) return "未找到银行卡信息"; // FIXME 是否有必要判定银行卡

        $userType = M('member') -> where(['id' => $uid]) -> getField('user_type');
        if ($userType == 1) {
            $autoWithdrawMinMoney   = apiParam::$autoWithdrawMinMoney;
            if ($amount < $autoWithdrawMinMoney) return get_escrow_error_info(21417);
        } else {
            $minWithdrawMoney   = apiParam::$minWithdrawMoney;
            if ($amount < $minWithdrawMoney) return get_escrow_error_info(21402);
        }

        //验证提现金额格式
        if(!preg_match("/^[1-9]*[1-9][0-9]*+(\.\d{1,2})?$/",$amount) || $amount < 1) return get_escrow_error_info(21403);

        //获取用户账户资金
        $minfo = getAccount($uid);

        switch (strtolower($type)) {
            case 'withdraw' :
                if ($minfo['avaliable_money'] < $amount) return get_escrow_error_info(21407);
                $data = ($userType == 1) ? [] : $this->calcFee($uid, $amount);
                break;
            default :
                return get_escrow_error_info(20000);
                break;
        }

        if (is_array($data)) $data['cardId'] = $mmm['id'];

        return $data;
    }

    /***
     * 提现手续费计算
     *
     * @param $withdraw_money
     * @param $uid
     * @return mixed
     */
    public function calcFee($uid, $withdraw_money = 0) {
        //1、每位客户（不管普通客户还是VIP），每天（0：00初始化）拥有2次免费提现的机会，如需多笔提现（第3笔开始）加收0.2%手续费。
        //2、VIP会员提现上限设定：单笔提现上限10万元，每天提现上限20万元。
        //3、普通会员提现上限制设定：单笔提现5万，每天提现上限10万。

        $vipInfo = service('Admin/Vip') -> vipGradeWithdraw($uid);
        $dayFreeWithdrawCount = $vipInfo['free_withdraw_count'];
        $withdraw_fee_rate = C('WITHDRAW_FEE_RATE');

        $modelWithdraw = M("member_withdraw");

        // 查询单次金额限制
        $minWithdrawMoney   = apiParam::$minWithdrawMoney;
        if ((!empty($withdraw_money) && ($withdraw_money < $minWithdrawMoney))) return get_escrow_error_info(21402);

        $this->minfo = getAccount($uid, true);
        $is_valid_vip = $this->minfo['is_valid_vip'];
        $limit_top = $vipInfo['max_withdraw_limit'];

        if (!empty($withdraw_money) && ($withdraw_money > $limit_top)) {
            return ($is_valid_vip>0 ? 'VIP'.$this->minfo['user_level'].'会员' : '非VIP会员')."单笔最高提现额为{$limit_top}元";
        }

        $itime = strtotime(date("Y-m", time()) . "-01 00:00:00") . "," . strtotime(date("Y-m-", time()) . date("t", time()) . " 23:59:59");

        // 总金额限制
        $where['_string']           = 'trade_no is NULL'; // 注释掉，工资提现也算次数
        $where['uid']               = $uid;
        $where['withdraw_status']   = array('in', array(0,1,2,3));
        $where['add_time']          = array("between", "{$itime}");
//        $daySumWithdrawMoney = $modelWithdraw -> where($where) -> sum('withdraw_money');
//        $limit_max = $vipInfo['max_withdraw_limit'];
//        if (!empty($withdraw_money) && ($daySumWithdrawMoney + $withdraw_money > $limit_max)) {
//            return $is_valid_vip>0 ? 'VIP'.$is_valid_vip.'会员' : '非VIP会员')."每日最高提现额为{$limit_max}元，您今日还可提现" . ($limit_max-$daySumWithdrawMoney) . '元';
//        }

//        unset($where['withdraw_status']);

        // 查询用户当月提现次数，并算取利息
        // 若已经查到X次记录，则说明当前是第X+1次提现，若以达到VIP免费提现次数X次，则将收取手续费，所以下面是 >=
        $withdrawCount = $modelWithdraw -> where($where) -> count();
        if (($withdrawCount >= $dayFreeWithdrawCount) && (!empty($dayFreeWithdrawCount))) {
            $cash_fee = $withdraw_money * $withdraw_fee_rate;
        } else {
            $cash_fee = 0;
        }

        $cash_fee = floor($cash_fee * 100) / 100;

        $data['withdraw_money'] = ''. ($withdraw_money);
        $data['withdraw_count'] = ''. ($withdrawCount + 1);
        $data['surplus_withdraw_count'] = ''. ($dayFreeWithdrawCount - $withdrawCount > 0 ? $dayFreeWithdrawCount - $withdrawCount : 0);
        $data['withdraw_fee']   = ''. ($cash_fee);
        $data['withdraw_rate']   = '0.002';
        $data['surplus_withdraw_count_str']   = empty($dayFreeWithdrawCount) ? '您当前有无限次免费提现次数' : ($data['surplus_withdraw_count'] == 0 ? '您当前免费提现次数已用完，将收取提现金额'.($data['withdraw_rate']*100).'%的手续费' : '您当前还可免费提现'.$data['surplus_withdraw_count'].'次');
        $data['max_withdraw_money_str'] = '每次最高'.($limit_top / 10000).'万元';
        $data['max_withdraw_money'] = ''.$limit_top;
        //查询默认的银行卡 是否填写银行详细信息
        $data['bank_info'] = M('member_banks')->field('bank_id,bank_name,bank_province,bank_city,bank_address')->where(['uid' => $uid, 'status' => 1])->find();
        $data['withdraw_remark'] = <<<EOF
平台提现审核时间：工作日9:00-16:00
平台审核通过后T+1个工作日到账
EOF;
        return $data;
    }
}
