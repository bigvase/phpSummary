<?php
use App\Lib\Service\EscrowJavaHttpService;

/**
 * 功能：银行回调后续处理service
 * 描述：银行回调后续业务都统一写在这里
 * add by lbk 2017-06-16 lbkbox@163.com
 */
class EscrowCallbackService extends EscrowJavaHttpService {

    /**
     *  功能：这是一个回调处理的例子
     *  银行回调业务处理 (回调名字 + _CallbackApi 的格式)
     * @param mixed param
     */
    protected function applyBankAccount_CallbackApi($param) {
        //1.对银行的参数校验[业务处理回调函数只校验数据参数，公共参数校验统一在入口函数校验]

        //2.回调成功的后续业务处理

        //3.根据业务处理结果返回给银行一个通知应答,r=0 失败；r=1成功；msg：返回信息
        $ret = array(
            'r' => 1,
            'msg' => 'SUCCESS'
        );
        return $ret;
    }

    /**
     * 批量交易入口方法，因为批量交易接口比较特殊，可能混杂多种情况
     * @param $param
     */
    protected function async_transaction_CallbackApi($param) {
        $respData = json_decode($param['respData'], 1);
        $flag = false;
        foreach ($respData['details'] as $one) {
            switch ($one['bizType']) {
                case "TENDER": //投资
                    $tenderInfo = M("cg_tender_ext")
                        ->where(["request_no" => $one['asyncRequestNo'], "status" => 0])
                        ->find();
                    if (!$tenderInfo) { //查询是否已处理该流水
                        $isSuccess = M("cg_tender_ext")
                            ->where(["request_no" => $one['asyncRequestNo'], "status" => 1])
                            ->find();
                        if (!$isSuccess) {
                            $flag = true;
                            break;
                        }
                    }
                    if ($one["status"] == "SUCCESS") {
                        $update["status"] = 1;
                    } else {
                        //todo 出现失败，这里需要警告
                        $update["status"] = -1;
                        $update['remark'] = $one;
                    }
                    $update['update_time'] = NOW_TIME;
                    $r = M("cg_tender_ext")->where(["id" => $tenderInfo["id"]])->save($update);
                    if (!$r) $flag = true;
                    break;
                case "COMPENSATORY":    //回款
                    $compensatoryInfo = M("cg_repayment_ext")
                        ->where(["request_no" => $one['asyncRequestNo'], "status" => 0])
                        ->find();
                    if (!$compensatoryInfo) { //查询是否已处理该流水
                        $isSuccess = M("cg_repayment_ext")
                            ->where(["request_no" => $one['asyncRequestNo'], "status" => 1])
                            ->find();
                        if (!$isSuccess) {
                            $flag = true;
                            break;
                        }
                    }
                    if ($one["status"] == "SUCCESS") {
                        $update["status"] = 1;
                    } else {
                        //todo 出现失败，这里需要警告
                        $update["status"] = -1;
                        $update['remark'] = $one;
                    }
                    $update['update_time'] = NOW_TIME;
                    $r = M("cg_repayment_ext")->where(["id" => $compensatoryInfo["id"]])->save($update);
                    if (!$r) $flag = true;
                    break;
            }
        }
        //todo 此处意味着出现异常，需要根据报文处理
        if ($flag) {
            //todo
            output("Callback Error!" . date("Y-m-d H:i:s", time()), $respData);
        } else {
            echo "SUCCESS"; //通知存管系统
        }
    }

    /***
     * 充值回调函数
     *
     * @param $param
     */
    protected function recharge_CallbackApi($param) {
        $logFile = 'Log/recharge.log';

        // 验签成功，处理逻辑
        $response = json_decode($param['respData'], true);

        output($logFile, $param);
        output($logFile, $response);

        $rs = M('cg_request_log')->where(['requestNo' => $response['requestNo']])->save(['response' => json_encode($param)]);

        $memberPayOnline = D('member_payonline');

        $requestNo = $response['requestNo'];
        $uid = $response['platformUserNo'];
        if (!is_numeric($uid) && is_numeric(escrowPara::$account_transfer[$uid])) {
            $uid = escrowPara::$account_transfer[$uid];
        }

        switch (strtoupper($response['rechargeStatus'])) {
            case 'SUCCESS':

                $rechargeWayConfig = C('RECHARGE_WAY');

                $rechargeWay = $response['rechargeWay'];

                M()->startTrans();

                $vo = $memberPayOnline->field('id,uid,money,fee,status,bank')->where(['billno' => $requestNo, 'status' => 0])->lock(true)->find();

                if (!is_array($vo)) {
                    output($logFile, '未找到订单信息');
                    // TODO 报警提醒
                    M()->rollback();
                    throw_exception('未找到订单信息');
                }

                $updata['status'] = 1;
                $updata['success_time'] = time();
                $xid = $memberPayOnline->where(['uid' => $uid, 'billno' => $requestNo, 'status' => 0])->save($updata);

                $targetuname = $rechargeWayConfig[$rechargeWay] ? $rechargeWayConfig[$rechargeWay] : '@平台@';

                $tmoney = floatval($vo['money'] - $vo['fee']);
                if ($xid) $newid = memberMoneyLog($vo['uid'], 'recharge', $tmoney, 0, 0, 0, "充值订单号:" . $requestNo, 0, $vo['id']); //更新成功才充值,避免重复充值

                // 充值记录模板消息
//              service('Admin/Wechat') -> sendTradeMessage($vo['uid'], "您好，您的小鸡理财账户最新交易提醒\n", '在线充值', sprintf("%.2f", $vo['money'])."元", '充值成功', C('PAY_JOIN_LIST'), "点击【详情】查看更多交易信息");


                // 并发检测
                $redis = \Addons\Libs\Cache\Redis::getInstance();
                if ($redis -> exists('RECHARGE_SUCCESS_'.$requestNo)) {
                    output($logFile, 'RECHARGE_SUCCESS_'.$requestNo.' 已存在，不操作短信发送，参数如下：', $response);
                } else {
                    $redis -> setex('RECHARGE_SUCCESS_'.$requestNo, 300, 1);
                    output($logFile, 'RECHARGE_SUCCESS_'.$requestNo.' 设置成功，发送短信提醒，参数如下：', $response);
                    // 发送消息 短信+站内信
                    $cellphone = M('member') -> where(['id' => $vo['uid']]) -> getField('cellphone');
                    $res = mq_producer(['sms','inner_msg'], $vo['uid'], 'payonline', [["#cellphone#","#money#"], [$cellphone, $vo['money']]]);
                }
                output($logFile, '充值成功');
                M()->commit();
                echo 'SUCCESS';
                break;
            case 'FAIL':
                output($logFile, $response['status'] . ' -- ' . $response['errorCode'] . ' -- ' . $response['errorMessage']);

                $updata = [];
                $updata['status'] = 3;
                $updata['response_message'] = $response['errorMessage'];
                // TODO 报警提醒
                $xid = $memberPayOnline->where(['uid' => $uid, 'billno' => $requestNo, 'status' => 0])->save($updata);
                break;
            case 'PENDDING':
                output($logFile, $response['status'] . ' -- ' . $response['errorCode'] . ' -- ' . $response['errorMessage']);
                // TODO 报警提醒
                break;
            default:
                output($logFile, $response['status'] . ' -- 非法参数');
                // TODO 报警提醒
                break;
        }
    }

    /***
     * 提现回调
     */
    protected function withdraw_CallbackApi($param) {
        $logFile = 'Log/withdraw.log';

        // 验签成功，处理逻辑
        $response = json_decode($param['respData'], true);

        output($logFile, $param);

        $rs = M('cg_request_log')->where(['requestNo' => $response['requestNo']])->save(['response' => json_encode($param)]);

        switch (strtoupper($response['withdrawStatus'])) {
            case 'CONFIRMING':  // 待确认
                $uid = $response['platformUserNo'];

                $requestNo = $response['requestNo'];

                $model = M('member_withdraw');

                $res = $model->where(['request_no' => $requestNo])->find();

                if ($res['withdraw_status'] == -1) {
                    $save['withdraw_status'] = 0;
                    $rrr = $model->where(['id' => $res['id']])->save($save);
                    if ($rrr) {
                        $this->_response = 'SUCCESS';

                        // 并发检测
                        $redis = \Addons\Libs\Cache\Redis::getInstance();
                        if ($redis -> exists('WITHDRAW_CONFIRMING_'.$requestNo)) {
                            output($logFile, 'WITHDRAW_CONFIRMING_'.$requestNo.' 已存在，不操作短信发送，参数如下：', $response);
                        } else {
                            $redis -> setex('WITHDRAW_CONFIRMING_'.$requestNo, 300, 1);
                            output($logFile, 'WITHDRAW_CONFIRMING_'.$requestNo.' 设置成功，发送短信提醒，参数如下：', $response);
                            // 发送提现短信和站内信
                            mq_producer(['sms','inner_msg'], $res['uid'], 'mq_withdraw', [["#time#", "#money#", "#fee#"], [date('Y-m-d H:i:s',$res['add_time']), fmtFloat($res['withdraw_money']), fmtFloat($res['withdraw_fee'])]]);
                        }

                        echo 'SUCCESS';
                    } else if ($res['withdraw_status'] == 0) {
                        echo 'SUCCESS';
                    } else {
                        echo('INIT'); // 未知错误
                    }
                } elseif ($res['withdraw_status']) {
                    echo('INIT'); // 重复请求
                } else {
                    echo('INIT'); // 非法请求
                }
                break;
            case 'FAIL':
                echo('FAIL');
                break;
            case 'SUCCESS':
                $requestNo = $response['requestNo'];

                $model = M('member_withdraw');

                $res = $model->where(['request_no' => $requestNo])->find();

                // 并发检测
                $redis = \Addons\Libs\Cache\Redis::getInstance();
                if ($redis -> exists('WITHDRAW_SUCCESS_'.$requestNo)) {
                    output($logFile, 'WITHDRAW_SUCCESS_'.$requestNo.' 已存在，不操作短信发送，参数如下：', $response);
                } else {
                    $redis -> setex('WITHDRAW_SUCCESS_'.$requestNo, 300, 1);

                    $field = 'w.*,(mm.account_money+mm.back_money+mm.reward_money) all_money';
                    $vo = M("member_withdraw w")->field($field)->join("tc_member_account mm on w.uid = mm.uid")->find($res['id']);
                    if ($vo['withdraw_status'] == 1) {
                        $saveEnd = [];
                        $saveEnd['withdraw_money']  = $vo['success_money'];
                        $saveEnd['second_fee']      = $vo['withdraw_fee'];
                        $saveEnd['withdraw_status'] = 2;    // 提现完毕，新网回调成功，资金到达银行卡，状态设置为成功，状态：2。
                        $type = "cash_success";
                        if($vo['trade_no'] == 'salary')  $type = "salary_success";
                        $tip = "提现成功,扣除实际手续费" . round($vo['second_fee'],2) . "元，实际到帐金额" . (round($vo['success_money'],2)) . "元";
                        memberMoneyLog($vo['uid'], $type, -$vo['withdraw_account'], -$vo['withdraw_back'], -$vo['withdraw_reward'], 0, $tip, '0', $vo['id']);
                        memberMoneyLog(escrowPara::$account_plat_shouru['plat_account'], 'withdraw_fee', $vo['withdraw_fee'], 0, 0, 0, '提现手续费入账，来自：'.$vo['uid'], $vo['uid'], $vo['id']);

                        $result = $model -> where(['id' => $vo['id']]) ->save($saveEnd);

                        output($logFile, 'WITHDRAW_SUCCESS_'.$requestNo.' 设置成功，发送短信提醒，参数如下：', $response);
                        // 发送提现短信和站内信
                        mq_producer(['sms','inner_msg'], $res['uid'], 'mq_withdraw_success', [["#time#", "#money#", "#fee#", "#success_money#"], [date('Y-m-d H:i:s'), fmtFloat($res['withdraw_money']), fmtFloat($res['withdraw_fee']), fmtFloat($res['success_money'])]]);
                    } else {
                        output($logFile, '提现状态为非中间态：1', $response, '记录信息：', $vo);
                    }
                }
                echo "SUCCESS";
                break;
            default:
                echo strtoupper($response['withdrawStatus']);
                break;
        }
    }

    /***
     * 资金回退充值
     * @param $param
     */
    protected function backroll_recharge_CallbackApi($param) {
        $logFile = 'Log/xw_sign.log';

        // 验签成功，处理逻辑
        $response = json_decode($param['respData'], true);

        if ($response['status'] == "SUCCESS") {

            $model = M('member_withdraw');

            M()->startTrans();

            $withdrawInfo = $model->where(['confirm_request_no' => $response['requestNo']])->lock(true)->find();

            /*if ($withdrawInfo['withdraw_status'] == 2) {
                // TODO 打印信息，状态为已提现状态，记录日志
                throw new \Exception('订单状态异常' . $withdrawInfo['withdraw_status']);
                M()->rollback();
                return false;
            } else */
            if (in_array($withdrawInfo['withdraw_status'], [-1, 0, 1, 2])) {
                $status = 5; // 资金回冲，新网未通过
                $id = $withdrawInfo['id'];

                $field = 'w.*,(mm.account_money+mm.back_money+mm.reward_money) all_money';
                $vo = M("member_withdraw w")->field($field)->join("tc_member_account mm on w.uid = mm.uid")->find($id);
                if ((in_array($withdrawInfo['withdraw_status'], [-1, 0, 1, 2])) && $status == 5) {

                    $map = [];
                    $map['id'] = $id;
                    $map['uid'] = $withdrawInfo['uid'];
                    if (in_array($withdrawInfo['withdraw_status'], [-1, 0, 1, 2])) {
                        $vo = M('member_withdraw')->where($map)->find();
                    }
                    if (!is_array($vo)) {
                        // TODO 查找信息失败 记录日志
                        throw_exception('订单信息异常');
                        return false;
                    }

                    $data['withdraw_status'] = $status;   // 提现撤回状态
                    $data['deal_info'] = '新网资金回退充值';

                    $newid = M('member_withdraw')->where($map)->save($data);
                    if ($newid) {
                        $res = memberMoneyLog($withdrawInfo['uid'], 'cash_cacel', $vo['withdraw_account'], $vo['withdraw_back'], $vo['withdraw_reward'], 0, "新网资金回退充值", '0', $id);
//                        memberMoneyLog(escrowPara::$account_plat_shouru['plat_account'], 'withdraw_fee_back', -$vo['secondfee'], 0, 0, 0, '提现手续费退回，来自：'.$vo['uid'], $vo['uid'], $vo['id']);
                    }
                    if ($res) {
                        M()->commit();
                        // TODO 记录日志
                        $this->_response = 'SUCCESS';
                        return true;
                    } else {
                        M()->rollback();
                        // TODO 记录日志
                        throw new \Exception('取消提现失败');
                    }
                } else {
                    M()->rollback();
                    throw_exception('非可回冲状态');
                }
            } else {
                M()->rollback();
                throw_exception('订单状态异常' . $withdrawInfo['withdraw_status']);
            }
        }
    }

    /***
     * 提现确认
     *
     * @param $param
     * @return bool
     * @throws Exception
     */
    protected function confirm_withdraw_CallbackApi($param) {
        $logFile = 'Log/xw_sign.log';

        // 验签成功，处理逻辑
        $response = json_decode($param['respData'], true);

        if ($response['status'] == "SUCCESS") {

            $model = M('member_withdraw');

            $withdrawInfo = $model->where(['confirm_request_no' => $response['requestNo']])->find();

            if ($withdrawInfo['withdraw_status'] == -1) {
                // TODO 打印信息，状态为非待确认状态，记录日志
                throw new \Exception('订单状态异常' . $withdrawInfo['withdraw_status']);
                return false;
            } else if ($withdrawInfo['withdraw_status'] == 1) {
                // 已通过初审（初审后状态为1），本次可修改资金记录
                $status = 2;
                $id = $withdrawInfo['id'];
                $secondfee = $withdrawInfo['withdraw_fee'];

                $save = [];
                $save['withdraw_status'] = $status;
//                $save['deal_time']          = time();
//                $save['deal_user']          = session('adminname');

                $field = 'w.*,(mm.account_money+mm.back_money+mm.reward_money) all_money';
                $vo = M("member_withdraw w")->field($field)->join("tc_member_account mm on w.uid = mm.uid")->find($id);
                if ($vo['withdraw_status'] == 1 && $status == 2) {
                    $success_money = $vo['withdraw_money'];
                    $moneydata['id'] = $vo['id'];
                    $moneydata['uid'] = $vo['uid'];
                    $moneydata['withdraw_bankid'] = $vo['withdraw_bankid'];
                    $moneydata['withdraw_money'] = $success_money;
                    $moneydata['withdraw_fee'] = $secondfee;
                    $save['second_fee'] = $secondfee;
                    $save['success_money'] = $success_money;
                    $type = "cash_success";
                    if ($vo['trade_no'] == 'salary') $type = "salary_success";
                    $tip = "提现成功,扣除实际手续费" . round($secondfee, 2) . "元，实际到帐金额" . (round($success_money, 2)) . "元";
                    memberMoneyLog($vo['uid'], $type, -$vo['withdraw_account'], -$vo['withdraw_back'], -$vo['withdraw_reward'], 0, $tip, '0', $vo['id']);
                    memberMoneyLog(escrowPara::$account_plat_shouru['plat_account'], 'withdraw_fee', $secondfee, 0, 0, 0, '提现手续费入账，来自：'.$vo['uid'], $vo['uid'], $vo['id']);
                    $result = $model->where(['id' => $id])->save($save);

                    $this->_response = 'SUCCESS';
                    return true;
//                    exit('SUCCESS');

//                    try {
//                        $record = M('member') -> where(array('id' => $vo['uid'])) -> find();
//                        // 提现成功 短信 + 站内信
//                        $smsTxt = FS("Webconfig/smstxt");
//                        $smsTxt = de_xie($smsTxt);
//                        $msgTxt = FS("Webconfig/msgtxt");
//                        $msgTxt = de_xie($msgTxt);
//                        $msgType = 'mq_withdraw_success';
//                        // 提现成功短信
//                        mq_producer('sms',$record['cellphone'], $msgType, str_replace(array("#username#","#time#","#money#", "#fee#", "#success_money#"), array($record['username'], date('Y-m-d H:i:s'), round($vo['withdraw_money'],2), round($vo['second_fee'],2), round($vo['success_money'],2)), $smsTxt[$msgType]['content']));
//                        // 提现成功站内信
//                        add_inner_msg($record['id'], $msgType, str_replace(array("#username#","#time#","#money#", "#fee#", "#success_money#"), array($record['username'], date('Y-m-d H:i:s'), round($vo['withdraw_money'],2), round($vo['second_fee'],2), round($vo['success_money'],2)), $msgTxt[$msgType]['content']));
//
//                        mq_producer('wechat',get_trade_message($vo['uid'], "您好，您的小鸡理财账户最新交易提醒：\n", '提现审核', sprintf("%.2f", $vo['success_money'])."元", '提现成功', C('WITHDRAW_JOIN_LIST'), "本次提现金额共".round($vo['withdraw_money'],2)."元，扣除手续费".round($vo['second_fee'],2)."元，实际到账".round($vo['success_money'],2)."元，具体到账时间请以银行为准，请耐心等待"));
//                    } catch (Exception $e) {
//                        get_warning_template_message('服务器提现复审异常', '提现复审', '高', '提现复审发送消息出现未知错误', '请查阅MQ相关日志，本次异常提示：'.$e->getMessage());
//                    }
                }
            } else {
                throw new \Exception('订单状态异常' . $withdrawInfo['withdraw_status']);
            }
        }
    }

    /***
     * 取消提现
     * @param $param
     * @return bool
     * @throws Exception
     */
    protected function cancel_withdraw_CallbackApi($param) {

        $logFile = 'Log/xw_sign.log';

        // 验签成功，处理逻辑
        $response = json_decode($param['respData'], true);


        if ($response['status'] == "SUCCESS") {

            $model = M('member_withdraw');

            $withdrawInfo = $model->where(['confirm_request_no' => $response['requestNo']])->find();

            if ($withdrawInfo['withdraw_status'] == 2) {
                // TODO 打印信息，状态为已提现状态，记录日志
                throw new \Exception('订单状态异常' . $withdrawInfo['withdraw_status']);
                return false;
            } else if (in_array($withdrawInfo['withdraw_status'], [-1, 0, 1])) {
                // 已通过初审（初审后状态为1），本次可修改资金记录
                $status = 4; // 提现撤回
                $id = $withdrawInfo['id'];

                $field = 'w.*,(mm.account_money+mm.back_money+mm.reward_money) all_money';
                $vo = M("member_withdraw w")->field($field)->join("tc_member_account mm on w.uid = mm.uid")->find($id);
                if ((in_array($withdrawInfo['withdraw_status'], [-1, 0, 1])) && $status == 4) {

                    $map = [];
                    $map['id'] = $id;
                    $map['uid'] = $withdrawInfo['uid'];
                    if (in_array($withdrawInfo['withdraw_status'], [-1, 0, 1])) {
                        $vo = M('member_withdraw')->where($map)->find();
                    }
                    if (!is_array($vo)) {
                        // TODO 查找信息失败 记录日志
                        throw new \Exception('订单信息异常');
                        return false;
                    }

                    $data['withdraw_status'] = $status;   // 提现撤回状态
                    $data['deal_info'] = '取消提现';

                    M()->startTrans();
                    $newid = M('member_withdraw')->where($map)->save($data);
                    if ($newid) {
                        $res = memberMoneyLog($withdrawInfo['uid'], 'cash_cacel', $vo['withdraw_account'], $vo['withdraw_back'], $vo['withdraw_reward'], 0, "撤消提现", '0', $id);
                    }
                    if ($res) {
                        M('member_withdraw')->commit();
                        // TODO 记录日志
                        $this->_response = 'SUCCESS';
                        return true;
                    } else {
                        M('member_withdraw')->rollback();
                        // TODO 记录日志
                        throw new \Exception('取消提现失败');
                    }
                }
            } else {
                throw new \Exception('订单状态异常' . $withdrawInfo['withdraw_status']);
            }
        }
    }

    //用户模块回调
    /**
     * 四要素注册
     * @param $param
     */
    protected function personal_register_expand_CallbackApi($param) {
        $response = json_decode($param['respData'], true);
        switch ($response['auditStatus']) {
            case 'PASSED':
                $userTypeList = C('DEP_MEMBER_TYPE_NO');
                $userType     = $userTypeList[$response['userRole']];
                $uid          = $response['platformUserNo'];
                $res          = $this->checkRequestNo($response['requestNo'], $uid);
                if (!$res) die('SUCCESS');
                $userService = service('Admin/User');
                $userService->setUid($uid);
                //$response = $userService->queryUserInformation();
                //$response = json_decode($response, true);
                //添加身份证信息
                $memberInfo = function ($response) {
                    $memberInfo['card_type'] = $response['idCardType'];
                    $memberInfo['idcard']    = $response['idCardNo'];
                    $memberInfo['up_time']   = time();
                    return $memberInfo;
                };
                //更新实名信息
                $member = function ($response) {
                    $member['realName'] = $response['realName'];
                    $member['userRole'] = $response['userRole'];
                    return $member;
                };
                //银行卡信息
                $memberBank = function ($response) {
                    $bankList                = C('DEP_BANK_LIST');
                    $memberBank['bank_num']  = $response['bankcardNo'];
                    $memberBank['bank_id']   = $response['bankcode'];
                    $memberBank['bank_name'] = $bankList[$response['bankcode']];
                    $memberBank['add_time']  = time();
                    $memberBank['cellphone'] = $response['mobile'];
                    $memberBank['realname']  = $response['realName'];
                    $memberBank['status']    = 1;
                    return $memberBank;
                };
                //存管授权信息
                $authInfo = function ($response){
                    $authInfo['authlist'] = explode(',',$response['authList']);
                    $authInfo['amount']   = $response['amount'];
                    $authInfo['failTime'] = $response['failTime'];
                    return $authInfo;
                };
                M()->startTrans();
                $cgInfo = M('cg_member_ext')->field('status')->lock(true)->where(['uid' => $uid])->find();
                $res    = $userService->saveIdCard($memberInfo($response), $userType);
                if ($res) $res = $userService->saveBankCard($memberBank($response), $userType);
                if ($res) {
                    $userService->updateUserInfo($member($response), $userType);
                    $userService->updateAuthorization($authInfo($response), $userType);
                    $userService->updateExt($response['requestNo'], $userType);
                    M()->commit();

                    // 并发检测
                    $redis = \Addons\Libs\Cache\Redis::getInstance();
                    if ($redis->exists('PERSONAL_REGISTER_EXPAND_PASSED_' . $response['requestNo'])) {
                    } else {
                        $redis->setex('PERSONAL_REGISTER_EXPAND_PASSED_' . $response['requestNo'], 300, 1);
                        mq_producer(['sms', 'inner_msg'], $uid, 'mq_escrow_open', []);
                    }

                    echo 'SUCCESS';
                } else {
                    M()->rollback();
                    echo M()->getLastSql();
                    echo($res);
                }
                break;
            case 'AUDIT':
                $uid = $response['platformUserNo'];
                //判断用户当前状态
                $userService = service('Admin/User');
                $userService->setUid($uid);
                $cgStatus = $userService->getCgstatus();
                if(2 != $cgStatus || 6 != $cgStatus){
                    M('cg_member_ext')->where(['uid' => $uid])->save(['request_status' => '1', 'request_time' => time()]);
                }
                exit('SUCCESS');
                break;
            case 'BACK':
            case 'REFUSED':
                $uid = $response['platformUserNo'];
                M('cg_member_ext')->where(['uid' => $uid])->save(['request_status' => '2', 'request_time' => '0', 'error_message' => $response['errorMessage']]);
                exit('SUCCESS');
                break;
            default:
                exit('INIT');
                break;
        }
    }

    /**
     * 绑定银行卡
     * @param $param
     */
    protected function personal_bind_bankcard_expand_CallbackApi($param) {
        $response = json_decode($param['respData'], true);
        switch ($response['status']) {
            case 'SUCCESS':
                $uid = $response['platformUserNo'];
                $res = $this->checkRequestNo($response['requestNo'], $uid);
                if (!$res) die('SUCCESS');
                $userService = service('Admin/User');
                $userService->setUid($uid);
                $memberBank = function ($response) {
                    $bankList                = C('DEP_BANK_LIST');
                    $memberBank['bank_num']  = $response['bankcardNo'];
                    $memberBank['bank_id']   = $response['bankcode'];
                    $memberBank['bank_name'] = $bankList[$response['bankcode']];
                    $memberBank['add_time']  = time();
                    $memberBank['cellphone'] = $response['mobile'];
                    $memberBank['status']    = 1;
                    return $memberBank;
                };
                M()->startTrans();
                $cgInfo = M('cg_member_ext')->field('status')->lock(true)->where(['uid' => $uid])->find();
                M()->commit();
                $res = $userService->saveBankCard($memberBank($response));
                if ($res) $res = $userService->updateExt($response['requestNo']);
                if ($res) echo 'SUCCESS';
                else echo M()->getLastSql();
                break;
            case 'INIT':
                exit('INIT');
                break;
            default:
                exit('INIT');
                break;
        }
    }

    /**
     * 解绑银行卡
     * @param $param
     */
    protected function unbind_bankcard_CallbackApi($param) {
        $response = json_decode($param['respData'], true);
        switch ($response['status']) {
            case 'SUCCESS':
                $uid = $response['platformUserNo'];
                $res = $this->checkRequestNo($response['requestNo'], $uid);
                if (!$res) die('SUCCESS');
                $userService = service('Admin/User');
                $userService->setUid($uid);
                $memberBank = function ($response) {
                    $memberBank = [];
                    return $memberBank;
                };
                $userService->saveBankCard($memberBank($response));
                $userService->updateExt($response['requestNo']);

                $model = M('member_banks');

                $res = $model->where(['uid' => $uid, 'status' => 1])->find();

                $save           = [];
                $save['status'] = 0;
                $model->where(['id' => $res['id']])->save($save);
                echo "SUCCESS";
                break;
            case 'INIT':
                exit('INIT');
                break;
            default:
                exit('INIT');
                break;
        }
    }

    /**
     * 重设支付密码
     * @param $response
     */
    protected function reset_password_CallbackApi($param) {
        $response = json_decode($param['respData'], true);
        switch ($response['status']) {
            case 'SUCCESS':
                $logFile   = 'Log/reset_password.log';
                $requestNo = $response['requestNo'];
                // 并发检测
                $redis = \Addons\Libs\Cache\Redis::getInstance();
                if ($redis->exists('RESET_PASSWORD_SUCCESS_' . $requestNo)) {
                    output($logFile, 'RESET_PASSWORD_SUCCESS_' . $requestNo . ' 已存在，不操作短信发送，参数如下：', $response);
                } else {
                    $redis->setex('RESET_PASSWORD_SUCCESS_' . $requestNo, 300, 1);
                    output($logFile, 'RESET_PASSWORD_SUCCESS_' . $requestNo . ' 设置成功，发送短信提醒，参数如下：', $response);

                    $res = M('cg_request_log')->where(['requestNo' => $requestNo])->getField('send_data');
                    if ($res) {
                        $res = json_decode($res, true);
                        $res = $res['reqData'];
                        $res = json_decode($res, true);
                        $uid = $res['platformUserNo'];

                        // 并发检测
                        $redis = \Addons\Libs\Cache\Redis::getInstance();
                        if ($redis->exists('RESET_PASSWORD_SUCCESS_' . $response['requestNo'])) {
                        } else {
                            $redis->setex('RESET_PASSWORD_SUCCESS_' . $response['requestNo'], 300, 1);
                            // 发送提现短信和站内信
                            mq_producer(['sms', 'inner_msg'], $uid, 'modify_pay_pwd_success', [["#time#"], [date('Y-m-d H:i:s')]]);
                        }

                    }
                }
                echo "SUCCESS";
                //nothing to do
                break;
            case 'INIT':
                exit('INIT');
                break;
            default:
                exit('INIT');
                break;
        }
    }

    /**
     * 修改预留手机号码
     * @param $param
     */
    protected function modify_mobile_expand_CallbackApi($param) {
        $response = json_decode($param['respData'], true);
        switch ($response['status']) {
            case 'SUCCESS':
                $uid = $response['platformUserNo'];
                $res = $this->checkRequestNo($response['requestNo'], $uid);
                if (!$res) die('SUCCESS');
                $userService = service('Admin/User');
                $userService->setUid($uid);
                //银行卡参数
                $memberBank = function ($response) {
                    $bankList                = C('DEP_BANK_LIST');
                    $memberBank['bank_num']  = $response['bankcardNo'];
                    $memberBank['bank_id']   = $response['bankcode'];
                    $memberBank['bank_name'] = $bankList[$response['bankcode']];
                    $memberBank['add_time']  = time();
                    $memberBank['cellphone'] = $response['mobile'];
                    $memberBank['realname']  = $response['realName'];
                    $memberBank['id_card']   = $response['idCardNo'];
                    $memberBank['status']    = 1;
                    return $memberBank;
                };
                M()->startTrans();
                $cgInfo = M('cg_member_ext')->field('status')->lock(true)->where(['uid' => $uid])->find();
                $res    = $userService->saveBankCard($memberBank($response));
                if ($res) $res = $userService->updateExt($response['requestNo']);
                if ($res) {
                    echo 'SUCCESS';
                    M()->commit();
                } else {
                    echo M()->getLastSql();
                    M()->rollback();
                }
                break;
            case 'INIT':
                exit('INIT');
                break;
            default:
                exit('INIT');
                break;
        }
    }

    /**
     * 用户激活
     * @param $param
     */
    protected function activate_stocked_user_CallbackApi($param) {
        $response = json_decode($param['respData'], true);
        switch ($response['auditStatus']) {
            case 'PASSED':
                $uid = $response['platformUserNo'];
                $res = $this->checkRequestNo($response['requestNo'], $uid);
                if (!$res) die('SUCCESS');
                $userService = service('Admin/User');
                $userService->setUid($uid);
                $response = $userService->queryUserInformation();
                $response = json_decode($response, true);
                //添加身份证信息
                $memberInfo = function ($response) {
                    $memberInfo['card_type'] = $response['idCardType'];
                    $memberInfo['idcard'] = $response['idCardNo'];
                    $memberInfo['up_time'] = time();
                    return $memberInfo;
                };
                //更新实名信息
                $member = function ($response) {
                    $member['realName'] = $response['name'];
                    $member['userRole'] = $response['userRole'];
                    return $member;
                };
                //银行卡信息
                $memberBank = function ($response) {
                    $bankList = C('DEP_BANK_LIST');
                    $memberBank['bank_num'] = $response['bankcardNo'];
                    $memberBank['bank_id'] = $response['bankcode'];
                    $memberBank['bank_name'] = $bankList[$response['bankcode']];
                    $memberBank['add_time'] = time();
                    $memberBank['cellphone'] = $response['mobile'];
                    $memberBank['status'] = 1;
                    return $memberBank;
                };
                M()->startTrans();
                $cgInfo = M('cg_member_ext')->field('status')->lock(true)->where(['uid' => $uid])->find();
                $res = $userService->saveIdCard($memberInfo($response));
                if ($res) $res = $userService->saveBankCard($memberBank($response));
                if ($res) {
                    $userService->updateUserInfo($member($response));
                    $userService->updateExt($response['requestNo']);
                    M()->commit();
                    echo 'SUCCESS';
                } else {
                    M()->rollback();
                    echo M()->getLastSql();
                    echo($res);
                }
                break;
            case 'AUDIT':
                $uid = $response['platformUserNo'];
                //判断用户当前状态
                $userService = service('Admin/User');
                $userService->setUid($uid);
                $cgStatus = $userService->getCgstatus();
                if(2 != $cgStatus || 6 != $cgStatus){
                    M('cg_member_ext')->where(['uid' => $uid])->save(['request_status' => '1', 'request_time' => time()]);
                }
                exit('SUCCESS');
                break;
            case 'REFUSED':
                $uid = $response['platformUserNo'];
                //判断用户当前状态
                $userService = service('Admin/User');
                $userService->setUid($uid);
                $cgStatus = $userService->getCgstatus();
                if(2 != $cgStatus || 6 != $cgStatus){
                    M('cg_member_ext')->where(['uid' => $uid])->save(['request_status' => '2', 'request_time' => '0', 'error_message' => $response['errorMessage']]);
                }
                break;
            case 'INIT':
                exit('INIT');
                break;
            default:
                exit('INIT');
                break;
        }

    }

    /**
     * 判断请求流水  是不是已经被处理
     * @param string $requestNo
     * @param string $userId
     * @return bool
     */
    private function checkRequestNo($requestNo = '', $userId = '') {
        return true;
        exit;
        $map['uid'] = $userId;
        $cgInfo = M('cg_member_ext')->field('request_no')->where($map)->find();
        if ($cgInfo['request_no'] == $requestNo) return false;
        else return true;
    }


    protected function user_authorization_CallbackApi($param) {
        output('Log/ccy_test.log', '开始业务处理', $param);
        $response = json_decode($param['respData'], true);
        if ($response['status'] == 'SUCCESS') {
            output('Log/ccy_test.log', $response);
            try {
                output('Log/ccy_test.log', '---------------------------------------user_authorization_CallbackApi<0>',$response['authList'],gettype($response['authList']));
                $uid = $response['platformUserNo'];
                //组装请求参数
                $authInfo             = [];
                $authInfo['authlist'] = explode(',',$response['authList']);
                output('Log/ccy_test.log', '---------------------------------------user_authorization_CallbackApi<1>',$authInfo['authlist'],gettype($authInfo['authlist']));
                $authInfo['amount']   = $response['amount'];
                $authInfo['failTime'] = $response['failTime'];
                $userService          = service('Admin/User');
                $userService->setUid($uid);
                output('Log/ccy_test.log', '---------------------------------------user_authorization_CallbackApi<2>',$authInfo,gettype($authInfo));
                $userService->updateAuthorization($authInfo);
            } catch (Exception $e) {
            }
        }
        echo 'SUCCESS';
    }
}
