<?php
/**
 * Created by PhpStorm.
 * User: bigsave
 * Date: 2018/5/30
 * Time: 16:24
 */

namespace app\admin\service;


use app\common\service\BaseService;
use think\Config;
use think\Db;
use think\Exception;

class HttpRequestService extends BaseService
{
    protected $_url = '';//请求的url
    protected $_check = true;//验签
    public $reqData = [];
    public $inter;
    public $sign;
    protected $timestamp;
    public $username;
    public $requestNo;
    public $errMsg = '';
    public $interConfig;
    function __construct(){
        parent::__construct();
        $this->interConfig = Config::get('interfaceParam');

        $sign = \think\Loader::model('admin/SignService','service');
        $ret = $sign->verify($_POST['param'],$_POST['sign'],['base_info']['public_key']);
        dump($ret);die;
        $sign->public_decrypt($_POST['param'],$this->interConfig['base_info']['public_key']);

        $signParam = $sign->param_aes_encode(json_encode($data),$this->private_key,$this->aesKey);


        $this->reqData   = json_decode($_POST['detailData'],true);
        $this->inter     = $_POST['interName'];
        $this->sign      = $_POST['sign'];
        $this->timestamp = $_POST['timestamp'];
        $this->username  = $_POST['platform'];
        $this->requestNo = $this->reqData['requestNo'];

        $this->requestEntrance();
    }
    /**
     * 1.统一请求入口
     * 2.时间差校验，验签校验,参数及数据格式校验，
     * 3.发送请求
     */
    /**
     * 请求入口
     */
    public function requestEntrance(){
        if(empty($this->reqData)) exception('参数不能为空~~',4000);
        try{
            $this->checkInterface();
            //校验参数
            $this->checkParam($this->interConfig['sign_param'][$this->inter],$this->reqData);
            $this->saveRequestNo($this->username,$this->requestNo);
        }catch (Exception $e){
            $this->errMsg = $e->getMessage();
            exception($e->getMessage());
        }
    }

    public function checkInterface(){
        if(empty($this->inter)) exception('接口名称不能为空~~');
        $interface = strtoupper($this->inter);

        if(!$this->interConfig['interface_name'] || !in_array($interface,$this->interConfig['interface_name'])){
            exception('不存在约定好的接口名称~~',4003);
        }

        if(!($this->timestamp <= (time() + 86400)) || !($this->timestamp >= (time()-1300))){
            exception('请求接口失效,请重新发起~~',4004);
        }
        return true;
    }

    /**
     * 校验流水号
     * @param string $platform
     * @param string $requestNo
     * @return bool
     */
    private function saveRequestNo($platform = '', $requestNo = '') {
        if (empty($platform)) {
            exception('缺少必要参数~~');
        } elseif (empty($requestNo)) {
            exception('请求流水号为空~~');
        } else {
            $requestNoHis = DB::name('service_request_log')->where(['platform' => $platform, 'requestNo' => $requestNo])->find();
            if (!empty($requestNoHis)) {
            } else {
                //保存
                if (is_array($this->reqData)) {
                    $reqData = json_encode($this->reqData);
                } else {
                    $reqData = $this->reqData;
                }
                $clientIp = $_SERVER['SERVER_ADDR'];
                $saveData = ['platform' => $platform, 'requestNo' => $requestNo, 'request' => $reqData, 'requestTime' => time(), 'requestIp' => $clientIp, 'serviceName' => $this->inter];
                if (empty($requestNo)) {
                    $saveData['return'] = '请求流水号为空';
                }
                $res = DB::name('service_request_log')->insert($saveData);
                if (!$res) {
                    exception('服务器繁忙，请稍后再试~~');
                }
            }
        }
        return true;
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
    public function checkParam(&$phpParam, &$paraData) {
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
            $paraData = $this->unsetEmptyVariable($paraData, $phpParam['dataParam']);
            $paramStatus = '';
            foreach ($paraData as $key => $value) {//参数循环
                try {
                    $this->checkParamOne($phpParam['dataParam'], $key, $paraData, $paramStatus);
                } catch (Exception $e) {
                    throw new \Exception($e->getMessage());
                }
            }

            $paraData = $this->unsetEmptyVariable($paraDataTemp, $phpParam['dataParam']);
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
                if ($dataParam[strtoupper($k)]['ISREQUIRED'] != true)
                    $param[$k] = $this->unsetEmptyVariable($v, $dataParam[strtoupper($k)]['DETAILS']);
            } else if (empty($v) && ($dataParam[strtoupper($k)]['ISREQUIRED'] != true)) {
                unset($param[$k]);
            }
        }
        return $param;
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
                $lockMessage     = '';   // 每次轮询的第一条错误信息
                $tempLockMessage = '';   // 总轮询的第一条错误信息
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
                        } else if (is_array($v) && is_array($v[0]) && !in_array($phpParam[$k], $v[0])) {
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

        if (isset($dataParamFormat[$key]['DETAILS']) && is_array($phpParam[$key])) {
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
            //!(array_key_exists($ck, $paramStatus) ||
            if ($dataParamFormat[$ck]['ISREQUIRED'] == true) {
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
                    case "TS": // 时间戳
                        if (date('Y', $phpParam[$ck]) < 2017) {
                            throw new \Exception("参数类型错误，{$ck}类型为非时间类型：{$phpParam[$ck]}【参考值：1516240045】");
                        }
                        break;
                }
            }
        }
    }




}