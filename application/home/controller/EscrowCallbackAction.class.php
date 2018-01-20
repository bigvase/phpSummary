<?php

/**
 * 1.存管相关处理(银行回调平台的入口；
 * 2.回调的后续业务处理service：EscrowCallbackService
 *
 */
class EscrowCallbackAction extends HCommonAction {
    private $_service;
    public function _initialize() {
        parent::_initialize();
        $this->_service = service('Admin/EscrowCallback');
    }
    /**
     * 银行回调java接口(外部调用)
     * @TODO 可以考虑用队列去处理
     */
    public function escrowJavaCallbackApi() {
        output('Log/Callback.log','收到新网异步回调，回调时间'.date('Y-m-d',time()));
        output('Log/Callback.log','POST参数',$_POST);
        output('Log/Callback.log','GET参数',$_GET);
        output('Log/Callback.log','REQUEST参数',$_REQUEST);
        try {
            $param = $_POST;
//            $param = json_decode($data, true);
            if (empty($param) || !$param['serviceName']) {
                output('Log/Callback.log','数据参数错误·「缺少必须的参数serviceName」');
                throw new \Exception('数据参数错误·「缺少必须的参数serviceName」');
            }
            //平台编号检测：platformNo
            if (empty($param) || $param['platformNo'] != getEscPlatformNo()) {
                output('Log/Callback.log','数据参数错误·「参数不对：platformNo 不是小鸡理财的平台编号」');
                throw new \Exception('数据参数错误·「参数不对：platformNo 不是小鸡理财的平台编号」');
            }
            //回调类型检测：
            if (empty($param) || $param['responseType'] != 'NOTIFY') {
                output('Log/Callback.log','数据参数错误·「参数不对：不是异步回调」');
                throw new \Exception('数据参数错误·「参数不对：不是异步回调」');
            }            
            //验签
            $res = $this->_service->verify($param['respData'], $param['sign']);
            output('Log/Callback.log','验证结果',$res,$param);
            //回调跳转到具体的业务处理函数
            $this->_service->escrowJavaCallbackHttpApi($param['serviceName'], $param);
            $return = $this->_service->response();
        } catch (\Exception $e) {
            $result['msg'] = $e->getMessage();
            output('Log/Callback.log', '回调失败，原因：'.$result['msg']);
            echo '回调失败，原因：'.$result['msg'];
        }
        return $return;
        //这里是返回给银行
        //echo json_encode($result['msg']);
        //返回给平台前端
        //$this->ajaxReturn($result);
    }


}
