<?php

class escrowPara {
    public static $Investor = '0';                        //普通投资用户类型 0
    public static $singleCreditorUserType = '1';          //普通借款用户类型 1
    public static $singleCreditorCompanyUserType = '10';  //单债权公司用户类型 10
    // 外部回调地址（银行）都写在这
    const LINALIAN_FROM_URL = 'https://cashier.lianlianpay.com/payment/bankgateway.htm';   //连连网关充值表单跳转
    const RENZHONG_PC_AUTH_LIANLIAN_URL = 'https://cashier.lianlianpay.com/payment/authpay.htm'; // 连连web认证支付地址
    const RENZHONG_WAP_AUTH_LIANLIAN_URL = 'https://wap.lianlianpay.com/authpay.htm';      // 连连wap认证支付地址
    // 平台回调地址都写在这
    const XIAOJI_WANG_GUAN_CALLBACK = 'https://cg.xiaojilicai.com/Home/escrowPc/callback'; //回调链接   // 网关充值回调
    //
    //
    //存管一些参数控制
    const ESC_STATUS = true;   // 是否开启存管
    const MAX_PAGE = 200;      // 查询条数
    public static $checkOrderStatusTimeLimit = [300, 604800]; // 五分钟 -> 七天
    public static $xiaojiTenderRechargeCallback = 'https://cg.xiaojilicai.com/Wechat/Invests/rechargeInvest'; //投标充值回调 网关

    public static $allinpayExpectPayCompany = 'ALLINPAY';// 通联支付公司
    public static $startAllinpayTime        = 1506614400;// 通联支付开始使用时间 1506614400|2017-09-29 00:00:00

    /***
     * 是否输出网关跳转前的调试信息
     * @var bool
     */
    public static $debugOutputGateInfo = false;
    //平台编号
    public static $platformNo = [
        'test' => '6000001929', // 测试平台编号
        'gray' => '6000001751', // 灰度平台编号
        'move' => '6000001752', // 迁移平台编号
        'product' => '6000001753', // 正式平台编号
   
    ];
    public static $RequesType = [
        'TENDER'                        =>['TENDER','AA','投标'],                       //TENDER 投标
        'REPAYMENT'                     =>['REPAYMENT','AB','还款'],                    //REPAYMENT 还款
        'CREDIT_ASSIGNMENT'             =>['CREDIT_ASSIGNMENT','AC','债权认购'],            //CREDIT_ASSIGNMENT 债权认购
        'COMPENSATORY'                  =>['COMPENSATORY','AD','直接代偿'],                 //COMPENSATORY 直接代偿
        'INDIRECT_COMPENSATORY'         =>['INDIRECT_COMPENSATORY','AD','间接代偿'],        //INDIRECT_COMPENSATORY 间接代偿
        'COMPENSATORY_REPAYMENT'        =>['COMPENSATORY_REPAYMENT','AE','还代偿款'],       //COMPENSATORY_REPAYMENT 还代偿款
        'PLATFORM_INDEPENDENT_PROFIT'   =>['PLATFORM_INDEPENDENT_PROFIT','AF','独立分润'],  //PLATFORM_INDEPENDENT_PROFIT 独立分润
        'MARKETING'                     =>['MARKETING','AG','营销红包'],                    //MARKETING 营销红包
        'INTEREST'                      =>['INTEREST','AH','派息'],                     //INTEREST 派息
        'ALTERNATIVE_RECHARGE'          =>['ALTERNATIVE_RECHARGE','AI','代充值'],         //ALTERNATIVE_RECHARGE 代充值
        'INTEREST_REPAYMENT'            =>['INTEREST_REPAYMENT','AJ','还派息款'],           //INTEREST_REPAYMENT 还派息款
        'COMMISSION'                    =>['COMMISSION','AK','佣金'],                             //COMMISSION 佣金
        'PROFIT'                        =>['PROFIT','AL','关联分润'],                            //PROFIT 关联分润
        'DEDUCT'                        =>['DEDUCT','AM','平台服务费'],                           //DEDUCT 平台服务费
        'FUNDS_TRANSFER'                =>['FUNDS_TRANSFER','AN','平台资金划拨'],               //FUNDS_TRANSFER 平台资金划拨
        'PLATFORM_SERVICE_DEDUCT'       =>['PLATFORM_SERVICE_DEDUCT','AO','收费'],             //PLATFORM_SERVICE_DEDUCT 收费
        'MONEY_IN_PLAT'                 =>['MONEY_IN_PLAT','AP','充值'],                       //MONEY_IN_PLAT  充值
        'MONEY_OUT_PLAT'                =>['MONEY_OUT_PLAT','AR','提现'],                      //MONEY_OUT_PLAT  提现
        'USE_REGIST_INFO'               =>['USE_REGIST_INFO','AS','开户相关'],                   //USE_REGIST_INFO  开户相关
        'MONEY_OUT_PLAT_CONFIRM'        =>['MONEY_OUT_PLAT_CONFIRM','AT','提现确认'],            //MONEY_OUT_PLAT_CONFIRM  提现确认
        'MONEY_OUT_PLAT_CANCEL'         =>['MONEY_OUT_PLAT_CANCEL','AU','取消提现'],             //MONEY_OUT_PLAT_CANCEL  取消提现
        'ESTABLISH_PROJECT'             =>['ESTABLISH_PROJECT','AV','创建标的'],                 //ESTABLISH_PROJECT  创建标的
        'MODIFY_PROJECT'                =>['MODIFY_PROJECT','AW','变更标的'],                   //MODIFY_PROJECT  变更标的
        'USER_PRE_TRANSACTION'          =>['USER_PRE_TRANSACTION','AX','用户预处理'],            //USER_PRE_TRANSACTION  用户预处理
        'CANCEL_PRE_TRANSACTION'        =>['CANCEL_PRE_TRANSACTION','AY','预处理取消'],          //CANCEL_PRE_TRANSACTION  预处理取消
        'ASYNC_TRANSACTION'             =>['ASYNC_TRANSACTION','AZ','批量交易'],                //ASYNC_TRANSACTION  批量交易
        'QUERY_USER_INFORMATION'        =>['QUERY_USER_INFORMATION','BA','用户信息查询'],         //QUERY_USER_INFORMATION  用户信息查询
        'SYNC_TRANSACTION'              =>['SYNC_TRANSACTION','BB','单笔交易'],                 //SYNC_TRANSACTION  单笔交易
        'USER_AUTHORIZATION'            =>['USER_AUTHORIZATION','BC','用户授权'],               //USER_AUTHORIZATION  用户授权
        'CANCEL_USER_AUTHORIZATION'     =>['CANCEL_USER_AUTHORIZATION','BD','用户取消授权'],      //CANCEL_USER_AUTHORIZATION  用户取消授权
        'USER_AUTO_PRE_TRANSACTION'     =>['USER_AUTO_PRE_TRANSACTION','BE','授权预处理'],       //USER_AUTO_PRE_TRANSACTION  授权预处理
        'VERIFY_DEDUCT'                 =>['VERIFY_DEDUCT','BF','验密扣费'],                        //VERIFY_DEDUCT 验密扣费
        'FREEZE'                        =>['FREEZE','BG','资金冻结'],                               //FREEZE 资金冻结
        'UNFREEZE'                      =>['UNFREEZE','BH','资金解冻'],                             //UNFREEZE 资金解冻
        'DOWNLOAD_CHECKFILE'            =>['DOWNLOAD_CHECKFILE','BI','对账文件下载'],             //DOWNLOAD_CHECKFILE 对账文件下载
        'UNFREEZE_TRADE_PASSWORD'       =>['UNFREEZE_TRADE_PASSWORD','BJ','交易密码解冻'],        //UNFREEZE_TRADE_PASSWORD 交易密码解冻
        'QUERY_TRANSACTION'             =>['QUERY_TRANSACTION','BK','交易密码解冻'],              //QUERY_TRANSACTION 单笔交易查询
        'QUERY_PROJECT_INFORMATION'     =>['QUERY_PROJECT_INFORMATION','BL','标的信息查询'],      //QUERY_PROJECT_INFORMATION 标的信息查询
        'CONFIRM_CHECKFILE'             =>['CONFIRM_CHECKFILE','BM','对账文件确认'],              //CONFIRM_CHECKFILE 对账文件确认
        'PERSONAL_REGISTER_EXPAND'      =>['PERSONAL_REGISTER_EXPAND','BN','个人绑卡注册'],       //PERSONAL_REGISTER_EXPAND 个人绑卡注册
        'PERSONAL_BIND_BANKCARD_EXPAND' =>['PERSONAL_BIND_BANKCARD_EXPAND','BO','个人绑卡'],        //PERSONAL_BIND_BANKCARD_EXPAND 个人绑卡
        'MODIFY_MOBILE_EXPAND'          =>['MODIFY_MOBILE_EXPAND','BP','预留手机号更新'],          //MODIFY_MOBILE_EXPAND 预留手机号更新
        'UNBIND_BANKCARD'               =>['UNBIND_BANKCARD','BQ','解绑银行卡'],                 //UNBIND_BANKCARD 解绑银行卡
        'AUTO_WITHDRAW'               =>['AUTO_WITHDRAW','BR','自动提现'],                 //AUTO_WITHDRAW 自动提现
        //如有遗漏的业务类型再次补充
        
         'OTT_OPERATE_INFO'            =>['OTT_OPERATE_INFO','AT'],    //OTT_OPERATE_INFO  其他操作
    ];

    /**
     * @var array 存管支付通道 编号
     */
    public static $payment_channel = [
        'rongbao' => '032', // xx
        'kuaijie_lianlian' => '029', // 快捷支付连连
        'wangguan_lianlian' => '039', // 网关支付连连
        'xinwang_yinghang' => '099', // 北京银行内部
    ];

    /**
     * @var array 存管支付通道 名称
     */
    public static $payment_channel_name = [
//        'YEEPAY'        => '易宝支付',
//        'FUIOU'         => '富友支付',
        'LIANLIAN'      => '连连支付',
        'ALLINPAY'      => '通联支付',
    ];

    /***
     * 支付方式
     * @var array
     */
    public static $escrow_recharge_way = array(
        'WEB'       => '网银',
        'SWIFT'     => '快捷支付',    // 快捷支付仅支持个人
    );

    /***
     * 存管银行列表
     * @var array
     */
    public static $escrow_bank_list = array(
        'ABOC' => '中国农业银行',
        'BKCH' => '中国银行',
        'CIBK' => '中信银行',
        'EVER' => '中国光大银行',
        'FJIB' => '兴业银行',
        'GDBK' => '广发银行',
        'HXBK' => '华夏银行',
        'ICBK' => '中国工商银行',
        'MSBC' => '中国民生银行',
        'PCBC' => '中国建设银行',
        'PSBC' => '中国邮政储蓄银行',
        'SZDB' => '平安银行',
        'SPDB' => '浦发银行',
        'BJCN' => '北京银行',
        'CMBC' => '招商银行',
        'BOSH' => '上海银行',
        'COMM' => '交通银行',
        'BKNB' => '宁波银行',
    );

    /***
     * 银行存管限额，需要关联表 tc_cg_bank_limit
     * @var array
     */
    public static $bank_recharge_limit = array(
        'LLPAY_BANK_CODE' => [
            'ABOC' => '01030000',
            'BKCH' => '01040000',
            'CIBK' => '03020000',
            'EVER' => '03030000',
            'FJIB' => '03090000',
            'GDBK' => '03060000',
            'HXBK' => '03040000',
            'ICBK' => '01020000',
            'MSBC' => '03050000',
            'PCBC' => '01050000',
            'PSBC' => '01000000',
            'SZDB' => '03070000',
            'SPDB' => '03100000',
            'BJCN' => '04031000',
            'CMBC' => '03080000',
            'COMM' => '03010000',
            'BOSH' => '04012900',
            'BKNB' => '04083320',
        ],
    );

    /***
     * 网银类型
     * @var array
     */
    public static $escrow_pay_type = array(
        'B2C'  => '个人网银',
        'B2B'  => '企业网银',
    );

    /*********
     * 账户相关
     */
    /**
     * 平台功能账户 编号 => 名称
     */
    public static $account_plat = array(
        'SYS_GENERATE_000'  => '平台总账户',
        'SYS_GENERATE_001'  => '平台代偿账户',
        'SYS_GENERATE_002'  => '平台营销款账户',
        'SYS_GENERATE_003'  => '平台分润账户',
        'SYS_GENERATE_004'  => '平台收入账户',
        'SYS_GENERATE_005'  => '平台派息账户',
        'SYS_GENERATE_006'  => '平台代充值账户',
        'SYS_GENERATE_007'  => '平台垫资账户',
    );
    /***
     * 平台功能账户 uid、新网功能账户编号 互转
     * @var array
     */
    public static $account_transfer = array(
        'SYS_GENERATE_000'  => '1',
        'SYS_GENERATE_001'  => '2',
        'SYS_GENERATE_002'  => '3',
        'SYS_GENERATE_003'  => '4',
        'SYS_GENERATE_004'  => '5',
        'SYS_GENERATE_005'  => '6',
        'SYS_GENERATE_006'  => '7',
        'SYS_GENERATE_007'  => '227535',
        '1'  => 'SYS_GENERATE_000',
        '2'  => 'SYS_GENERATE_001',
        '3'  => 'SYS_GENERATE_002',
        '4'  => 'SYS_GENERATE_003',
        '5'  => 'SYS_GENERATE_004',
        '6'  => 'SYS_GENERATE_005',
        '7'  => 'SYS_GENERATE_006',
        '227535'  => 'SYS_GENERATE_007',
    );
    //平台总账户
    public static $account_plat_master = array(
        'name' => '平台总账户', //账户名称
        'plat_account' => '1', //平台内部账户编号
        'escrow_account' => 'SYS_GENERATE_000', //银行存管账户编号
    );
    //代偿金账户
    public static $account_plat_daichangjin = array(
        'name' => '代偿金账户', //账户名称
        'plat_account' => '2', //平台内部账户编号
        'escrow_account' => 'SYS_GENERATE_001', //银行存管账户编号
    );
    //营销款账户
    public static $account_plat_yingxiao = array(
        'name' => '营销款账户', //账户名称
        'plat_account' => '3', //平台内部账户编号
        'escrow_account' => 'SYS_GENERATE_002', //银行存管账户编号
    );
    //分润账户
    public static $account_plat_fenrun = array(
        'name' => '分润账户', //账户名称
        'plat_account' => '4', //平台内部账户编号
        'escrow_account' => 'SYS_GENERATE_003', //银行存管账户编号
    );
    //收入账户
    public static $account_plat_shouru = array(
        'name' => '收入账户', //账户名称
        'plat_account' => '5', //平台内部账户编号
        'escrow_account' => 'SYS_GENERATE_004', //银行存管账户编号
    );
    //派息账户
    public static $account_plat_paixi = array(
        'name' => '派息账户', //账户名称
        'plat_account' => '6', //平台内部账户编号
        'escrow_account' => 'SYS_GENERATE_005', //银行存管账户编号
    );
    //代充值账户
    public static $account_plat_daichongzhi = array(
        'name' => '代充值账户', //账户名称
        'plat_account' => '7', //平台内部账户编号
        'escrow_account' => 'SYS_GENERATE_006', //银行存管账户编号
    );
    //平台垫资账户
    public static $account_plat_pingtaidianzi = array(
        'name' => '平台垫资账户', //账户名称
        'plat_account' => '227535', //平台内部账户编号
        'escrow_account' => 'SYS_GENERATE_007', //银行存管账户编号
    );

    /***
     * 请求参数规则校验
     *
     * 整体框架形式
     *
     * 接口名  => [
     *      接口信息    => [
     *          xxx => xxx
     *          xxx => xxx
     *      ],
     *      参数信息    => [
     *          xxx => xxx
     *          xxx => xxx
     *      ],
     * ]
     *
     * 各个参数信息包含参数的类型、长度、是否必填、备注、上级依赖值
     *
     * type：        类型参考存管文档，如 S|字符串 A|金额 等
     * length：      参数长度，目前新网不校验长度，校验被关闭，如需开启，请将 EscrowHttpService的父类中的 $_checkParamLengthSwitch 设置为true
     * isRequired：  是否必填，true|必填 false|非必填
     * note：        备注信息，如果必填参数未填会提示该信息
     * refer：       上级依赖：
     *                      1、"" （空字符串）表示无依赖
     *                      2、一维数组，若有多个参数，则关系为or，即有一个情况满足即可。
     *                          对于每个参数（key => value），若value === true，key上级依赖的参数需必填，若为空，则会返回错误。
     *                          如：platformUserNo => ['refer' => ['requestNo' => true]] platformUserNo 依赖于 requestNo，requestNo 不为空，则platformUserNo 必填
     *                          若value为某一特定值或某一数组，则当上级依赖的值为value的特定值或数组中的某一值时该参数必填
     *                          如：platformUserNo => ['refer' => ['requestNo' => '123']] platformUserNo 依赖于 requestNo，requestNo == '123' 时，platformUserNo 必填，requestNo == '1234' 时，platformUserNo 可不填
     *                      3、二维数组、二位数组内的参数关系为and，即需要全部满足才需要必填
     *                          如：platformUserNo => [['a' => '123', 'b' => '234']] platformUserNo 依赖于 a 和 b，a == '123'，b == '234' 时，platformUserNo 必填
     *
     * @var array
     */
    public static $ESCROW = [

        /*******
         * 账户接口
         */
        // 个人绑卡注册 PERSONAL_REGISTER_EXPAND
        "PERSONAL_REGISTER_EXPAND"  => [
            'serverParam' => [
                'serviceName' => 'PERSONAL_REGISTER_EXPAND', //接口名称
                'redirectUrl' => self::XIAOJI_WANG_GUAN_CALLBACK, //回调链接
            ],
            'dataParam' => [
                'platformUserNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '平台用户编号不能为空',
                    'refer'         => '',
                ],
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => '',
                ],
                'realName'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => false,
                    'note'          => '用户真实姓名不能为空',
                    'refer'         => '',
                ],
                'idCardNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => false,
                    'note'          => '用户证件号不能为空',
                    'refer'         => '',
                ],
                'bankcardNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => false,
                    'note'          => '银行卡号不能为空',
                    'refer'         => '',
                ],
                'mobile'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => false,
                    'note'          => '银行预留手机号不能为空',
                    'refer'         => '',
                ],
                'idCardType'  => [
                    'type'          => 'E',
                    'length'        => '',
                    'isRequired'    => false,
                    'note'          => '证件类型不能为空',
                    'refer'         => '',
                ],
                'userRole'  => [
                    'type'          => 'E',
                    'length'        => '',
                    'isRequired'    => false,
                    'note'          => '角色信息不能为空',
                    'refer'         => '',
                ],
                'failTime'  => [
                    'type'          => 'S',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '授权过期时间不能为空',
                    'refer'         => '',
                    'allowEmpty'    => true,
                ],
                'amount'  => [
                    'type'          => 'S',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '冻结金额不能为空',
                    'refer'         => '',
                    'allowEmpty'    => true,
                ],
                'checkType'  => [
                    'type'          => 'E',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '鉴权验证类型不能为空',
                    'refer'         => '',
                ],
                'redirectUrl'  => [
                    'type'          => 'S',
                    'length'        => '100',
                    'isRequired'    => true,
                    'note'          => '页面回跳URL不能为空',
                    'refer'         => '',
                ],
                'userLimitType'  => [
                    'type'          => 'E',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '验证身份证唯一性不能为空',
                    'refer'         => '',
                    'allowEmpty'    => true,
                ],
                'authList'  => [
                    'type'          => 'S',
                    'length'        => '',
                    'isRequired'    => false,
                    'note'          => '用户权限不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],
        // 企业绑卡注册 ENTERPRISE_REGISTER
        "ENTERPRISE_REGISTER"  => [
            'serverParam' => [
                'serviceName' => 'ENTERPRISE_REGISTER', //接口名称
                'redirectUrl' => self::XIAOJI_WANG_GUAN_CALLBACK, //回调链接
            ],
            'dataParam' => [
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => '',
                ],
                'platformUserNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '平台用户编号不能为空',
                    'refer'         => '',
                ],
                'enterpriseName'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '企业名称不能为空',
                    'refer'         => '',
                ],
                'bankLicense'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '开户银行许可证号不能为空',
                    'refer'         => '',
                ],
                'orgNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => false,
                    'note'          => '组织机构代码不能为空',
                    'refer'         => '',
                ],
                'businessLicense'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => false,
                    'note'          => '营业执照编号不能为空',
                    'refer'         => '',
                ],
                'taxNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => false,
                    'note'          => '税务登记号不能为空',
                    'refer'         => '',
                ],
                'unifiedCode'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => false,
                    'note'          => '统一社会信用代码不能为空',
                    'refer'         => '',
                ],
                'creditCode'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => false,
                    'note'          => '机构信用代码不能为空',
                    'refer'         => '',
                ],
                'legal'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '法人姓名不能为空',
                    'refer'         => '',
                ],
                'idCardType'  => [
                    'type'          => 'E',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '证件类型不能为空',
                    'refer'         => '',
                ],
                'legalIdCardNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '法人证件号不能为空',
                    'refer'         => '',
                ],
                'contact'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '企业联系人不能为空',
                    'refer'         => '',
                ],
                'contactPhone'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '联系人手机号不能为空',
                    'refer'         => '',
                ],
                'userRole'  => [
                    'type'          => 'E',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '用户角色不能为空',
                    'refer'         => '',
                ],
                'bankcardNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '企业对公账户显示后四位不能为空',
                    'refer'         => '',
                ],
                'bankcode'  => [
                    'type'          => 'E',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '银行编码不能为空',
                    'refer'         => '',
                ],
                'redirectUrl'  => [
                    'type'          => 'S',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '跳转地址不能为空',
                    'refer'         => '',
                ],
                'authList'  => [
                    'type'          => 'S',
                    'length'        => '',
                    'isRequired'    => false,
                    'note'          => '用户权限不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],
        // 个人绑卡 PERSONAL_BIND_BANKCARD_EXPAND
        "PERSONAL_BIND_BANKCARD_EXPAND"  => [
            'serverParam' => [
                'serviceName' => 'PERSONAL_BIND_BANKCARD_EXPAND', //接口名称
                'redirectUrl' =>  self::XIAOJI_WANG_GUAN_CALLBACK, //回调链接
            ],
            'dataParam' => [
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => [],
                ],
                'platformUserNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '平台用户编号不能为空',
                    'refer'         => '',
                ],
                'redirectUrl'  => [
                    'type'          => 'S',
                    'length'        => '100',
                    'isRequired'    => true,
                    'note'          => '页面回跳URL不能为空',
                    'refer'         => '',
                ],
                'checkType'  => [
                    'type'          => 'E',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '鉴权验证类型不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],
        // 企业绑卡 ENTERPRISE_BIND_BANKCARD
        "ENTERPRISE_BIND_BANKCARD"  => [
            'serverParam' => [
                'serviceName' => 'ENTERPRISE_BIND_BANKCARD', //接口名称
                'redirectUrl' =>  self::XIAOJI_WANG_GUAN_CALLBACK, //回调链接
            ],
            'dataParam' => [
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => [],
                ],
                'platformUserNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '平台用户编号不能为空',
                    'refer'         => '',
                ],
                'redirectUrl'  => [
                    'type'          => 'S',
                    'length'        => '100',
                    'isRequired'    => true,
                    'note'          => '页面回跳URL不能为空',
                    'refer'         => '',
                ],
                'bankcardNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '银行账户号不能为空',
                    'refer'         => '',
                ],
                'bankcode'  => [
                    'type'          => 'E',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '银行编码不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],
        // 解绑银行卡 UNBIND_BANKCARD
        "UNBIND_BANKCARD"  => [
            'serverParam' => [
                'serviceName' => 'UNBIND_BANKCARD', //接口名称
                'redirectUrl' =>  self::XIAOJI_WANG_GUAN_CALLBACK, //回调链接
            ],
            'dataParam' => [
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => [],
                ],
                'platformUserNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '平台用户编号不能为空',
                    'refer'         => '',
                ],
                'redirectUrl'  => [
                    'type'          => 'S',
                    'length'        => '100',
                    'isRequired'    => true,
                    'note'          => '页面回跳URL不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],
        // 修改密码 RESET_PASSWORD
        "RESET_PASSWORD"  => [
            'serverParam' => [
                'serviceName' => 'RESET_PASSWORD', //接口名称
                'redirectUrl' =>  self::XIAOJI_WANG_GUAN_CALLBACK, //回调链接
            ],
            'dataParam' => [
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => [],
                ],
                'platformUserNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '平台用户编号不能为空',
                    'refer'         => '',
                ],
                'redirectUrl'  => [
                    'type'          => 'S',
                    'length'        => '100',
                    'isRequired'    => true,
                    'note'          => '页面回跳URL不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],
        // 验证密码 CHECK_PASSWORD
        "CHECK_PASSWORD"  => [
            'serverParam' => [
                'serviceName' => 'CHECK_PASSWORD', //接口名称
                'redirectUrl' =>  self::XIAOJI_WANG_GUAN_CALLBACK, //回调链接
            ],
            'dataParam' => [
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => [],
                ],
                'platformUserNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '平台用户编号不能为空',
                    'refer'         => '',
                ],
                'redirectUrl'  => [
                    'type'          => 'S',
                    'length'        => '100',
                    'isRequired'    => true,
                    'note'          => '页面回跳URL不能为空',
                    'refer'         => '',
                ],
                'bizTypeDescription'  => [
                    'type'          => 'S',
                    'length'        => '25',
                    'isRequired'    => true,
                    'note'          => '平台根据自定的业务描述不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],
        // 预留手机号更新 MODIFY_MOBILE_EXPAND
        "MODIFY_MOBILE_EXPAND"  => [
            'serverParam' => [
                'serviceName' => 'MODIFY_MOBILE_EXPAND', //接口名称
                'redirectUrl' =>  self::XIAOJI_WANG_GUAN_CALLBACK, //回调链接
            ],
            'dataParam' => [
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => [],
                ],
                'platformUserNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '平台用户编号不能为空',
                    'refer'         => '',
                ],
                'redirectUrl'  => [
                    'type'          => 'S',
                    'length'        => '100',
                    'isRequired'    => true,
                    'note'          => '页面回跳URL不能为空',
                    'refer'         => '',
                ],
                'checkType'  => [
                    'type'          => 'E',
                    'length'        => '',
                    'isRequired'    => false,
                    'note'          => '鉴权验证类型不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],
        // 企业信息修改 ENTERPRISE_INFORMATION_UPDATE
        "ENTERPRISE_INFORMATION_UPDATE"  => [
            'serverParam' => [
                'serviceName' => 'ENTERPRISE_INFORMATION_UPDATE', //接口名称
                'redirectUrl' =>  self::XIAOJI_WANG_GUAN_CALLBACK, //回调链接
            ],
            'dataParam' => [
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => [],
                ],
                'platformUserNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '平台用户编号不能为空',
                    'refer'         => '',
                ],
                'redirectUrl'  => [
                    'type'          => 'S',
                    'length'        => '100',
                    'isRequired'    => true,
                    'note'          => '页面回跳URL不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],
        // 会员激活 ACTIVATE_STOCKED_USER
        "ACTIVATE_STOCKED_USER"  => [
            'serverParam' => [
                'serviceName' => 'ACTIVATE_STOCKED_USER', //接口名称
                'redirectUrl' =>  self::XIAOJI_WANG_GUAN_CALLBACK, //回调链接
            ],
            'dataParam' => [
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => [],
                ],
                'platformUserNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '平台用户编号不能为空',
                    'refer'         => '',
                ],
                'redirectUrl'  => [
                    'type'          => 'S',
                    'length'        => '100',
                    'isRequired'    => true,
                    'note'          => '页面回跳URL不能为空',
                    'refer'         => '',
                ],
                'authList'  => [
                    'type'          => 'S',
                    'length'        => '100',
                    'isRequired'    => false,
                    'note'          => '页面回跳URL不能为空',
                    'refer'         => '',
                ],
                'checkType'  => [
                    'type'          => 'E',
                    'length'        => '',
                    'isRequired'    => false,
                    'note'          => '鉴权验证类型不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],

        /*******
         * 充提接口
         */
        // 充值
        'RECHARGE'      => [
            'serverParam' => [
                'serviceName' => 'RECHARGE', //接口名称
                'redirectUrl' =>  self::XIAOJI_WANG_GUAN_CALLBACK, //回调链接
                'expired'       => 100, // 页面过期时间
            ],
            'dataParam' => [
                'platformUserNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '平台用户编号不能为空',
                    'refer'         => '',
                ],
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => '',
                ],
                'amount'  => [
                    'type'          => 'A',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '充值金额不能为空',
                    'refer'         => '',
                ],
                'commission'  => [
                    'type'          => 'A',
                    'length'        => '',
                    'isRequired'    => false,
                    'note'          => '平台佣金不能为空',
                    'refer'         => '',
                ],
                'expectPayCompany'  => [
                    'type'          => 'E',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '偏好支付公司不能为空',
                    'refer'         => '',
                ],
                'rechargeWay'  => [
                    'type'          => 'E',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '支付方式不能为空',
                    'refer'         => '',
                ],
                'bankcode'  => [
                    'type'          => 'E',
                    'length'        => '50',
                    'isRequired'    => false,
                    'note'          => '银行编码不能为空',
                    'refer'         => ['rechargeWay' => [['SWIFT','WEB']]],
                ],
                'payType'  => [
                    'type'          => 'S',
                    'length'        => '',
                    'isRequired'    => false,
                    'note'          => '网银类型不能为空',
                    'refer'         => [['rechargeWay' => 'WEB','bankcode' => true], 'rechargeWay' => 'WEB'],
                ],
                'authtradeType'  => [
                    'type'          => 'E',
                    'length'        => '',
                    'isRequired'    => false,
                    'note'          => '授权交易类型不能为空',
                    'refer'         => ['authtenderAmount' => true],
                ],
                'authtenderAmount'  => [
                    'type'          => 'C',
                    'length'        => '',
                    'isRequired'    => false,
                    'note'          => '授权投标金额不能为空',
                    'refer'         => ['authtradeType'=>'TENDER'],
                ],
                'projectNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => false,
                    'note'          => '标的号不能为空',
                    'refer'         => ['authtradeType'=>'TENDER'],
                ],
                'redirectUrl'  => [
                    'type'          => 'S',
                    'length'        => '100',
                    'isRequired'    => true,
                    'note'          => '页面回跳URL不能为空',
                    'refer'         => '',
                ],
                'expired'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '页面过期时间不能为空',
                    'refer'         => '',
                ],
                'callbackMode'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => false,
                    'note'          => '快捷充值回调模式不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ],
        ],
        // 自动充值 DIRECT_RECHARGE
        'DIRECT_RECHARGE'      => [
            'serverParam' => [
                'serviceName' => 'DIRECT_RECHARGE', //接口名称
            ],
            'dataParam' => [
                'platformUserNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '平台用户编号不能为空',
                    'refer'         => '',
                ],
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => '',
                ],
                'amount'  => [
                    'type'          => 'A',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '充值金额不能为空',
                    'refer'         => '',
                ],
                'commission'  => [
                    'type'          => 'A',
                    'length'        => '',
                    'isRequired'    => false,
                    'note'          => '平台佣金不能为空',
                    'refer'         => '',
                ],
                'expectPayCompany'  => [
                    'type'          => 'E',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '偏好支付公司不能为空',
                    'refer'         => '',
                ],
                'rechargeWay'  => [
                    'type'          => 'E',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '支付方式不能为空',
                    'refer'         => '',
                ],
                'bankcode'  => [
                    'type'          => 'E',
                    'length'        => '50',
                    'isRequired'    => false,
                    'note'          => '银行编码不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ],
        ],
        // 提现 WITHDRAW
        'WITHDRAW'      => [
            'serverParam' => [
                'serviceName'   => 'WITHDRAW', //接口名称
                'redirectUrl'   => self::XIAOJI_WANG_GUAN_CALLBACK, //回调链接
                'expired'       => 100, // 页面过期时间
            ],
            'dataParam' => [
                'platformUserNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '平台用户编号不能为空',
                    'refer'         => '',
                ],
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => '',
                ],
                'expired'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '页面过期时间不能为空',
                    'refer'         => '',
                ],
                'redirectUrl'  => [
                    'type'          => 'S',
                    'length'        => '100',
                    'isRequired'    => true,
                    'note'          => '页面回跳URL不能为空',
                    'refer'         => '',
                ],
                'withdrawType'  => [
                    'type'          => 'E',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '提现方式不能为空',
                    'refer'         => '',
                ],
                'withdrawForm'  => [
                    'type'          => 'E',
                    'length'        => '',
                    'isRequired'    => false,
                    'note'          => '提现类型不能为空',
                    'refer'         => '',
                ],
                'amount'  => [
                    'type'          => 'A',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '充值金额不能为空',
                    'refer'         => '',
                ],
                'commission'  => [
                    'type'          => 'A',
                    'length'        => '',
                    'isRequired'    => false,
                    'note'          => '提现分佣不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ],
        ],
        // 提现确认 CONFIRM_WITHDRAW
        "CONFIRM_WITHDRAW"  => [
            'serverParam' => [
                'serviceName' => 'CONFIRM_WITHDRAW', //接口名称
            ],
            'dataParam' => [
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => '',
                ],
                'preTransactionNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '待确认提现请求流水号不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],
        // 取消提现 CANCEL_WITHDRAW
        "CANCEL_WITHDRAW"  => [
            'serverParam' => [
                'serviceName' => 'CANCEL_WITHDRAW', //接口名称
            ],
            'dataParam' => [
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => '',
                ],
                'preTransactionNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '待确认提现请求流水号不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],
        // 自动提现 AUTO_WITHDRAW
        "AUTO_WITHDRAW"  => [
            'serverParam' => [
                'serviceName' => 'AUTO_WITHDRAW', //接口名称
            ],
            'dataParam' => [
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => '',
                ],
                'platformUserNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '平台用户编号不能为空',
                    'refer'         => '',
                ],
                'withdrawType'  => [
                    'type'          => 'E',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '提现方式不能为空',
                    'refer'         => '',
                ],
                'amount'  => [
                    'type'          => 'A',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '提现金额不能为空',
                    'refer'         => '',
                ],
                'commission'  => [
                    'type'          => 'A',
                    'length'        => '',
                    'isRequired'    => false,
                    'note'          => '提现分佣不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],
        // 提现拦截 INTERCEPT_WITHDRAW
        "INTERCEPT_WITHDRAW"  => [
            'serverParam' => [
                'serviceName' => 'INTERCEPT_WITHDRAW', //接口名称
            ],
            'dataParam' => [
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => '',
                ],
                'withdrawRequestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '提现请求流水号不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],

        /*******
         * 交易接口
         */
        // 创建标的 ESTABLISH_PROJECT
        "ESTABLISH_PROJECT"  => [
            'serverParam' => [
                'serviceName' => 'ESTABLISH_PROJECT', //接口名称
            ],
            'dataParam' => [
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => '',
                ],
                'platformUserNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '借款方平台用户编号不能为空',
                    'refer'         => '',
                ],
                'projectNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '标的号不能为空',
                    'refer'         => '',
                ],
                'projectAmount'  => [
                    'type'          => 'A',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '标的金额不能为空',
                    'refer'         => '',
                ],
                'projectName'  => [
                    'type'          => 'S',
                    'length'        => '300',
                    'isRequired'    => true,
                    'note'          => '标的名称不能为空',
                    'refer'         => '',
                ],
                'projectDescription'  => [
                    'type'          => 'S',
                    'length'        => '300',
                    'isRequired'    => false,
                    'note'          => '标的描述不能为空',
                    'refer'         => '',
                ],
                'projectType'  => [
                    'type'          => 'E',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '标的类型不能为空',
                    'refer'         => '',
                ],
                'projectPeriod'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => false,
                    'note'          => '标的期限不能为空',
                    'refer'         => '',
                ],
                'annnualInterestRate'  => [
                    'type'          => 'F',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '年化利率不能为空',
                    'refer'         => '',
                ],
                'repaymentWay'  => [
                    'type'          => 'E',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '还款方式不能为空',
                    'refer'         => '',
                ],
                'extend'  => [
                    'type'          => 'O',
                    'length'        => '',
                    'isRequired'    => false,
                    'note'          => '标的扩展信息不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],
        // 变更标的 MODIFY_PROJECT
        "MODIFY_PROJECT"  => [
            'serverParam' => [
                'serviceName' => 'MODIFY_PROJECT', //接口名称
            ],
            'dataParam' => [
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => '',
                ],
                'projectNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '标的号不能为空',
                    'refer'         => '',
                ],
                'status'  => [
                    'type'          => 'E',
                    'length'        => '',
                    'isRequired'    => false,
                    'note'          => '标的状态不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],
        // 用户预处理 USER_PRE_TRANSACTION
        "USER_PRE_TRANSACTION"  => [
            'serverParam' => [
                'serviceName' => 'USER_PRE_TRANSACTION', //接口名称
                'redirectUrl' => self::XIAOJI_WANG_GUAN_CALLBACK, //回调链接
            ],
            'dataParam' => [
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => '',
                ],
                'platformUserNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '出款人平台用户编号不能为空',
                    'refer'         => '',
                ],
                'bizType'  => [
                    'type'          => 'E',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '预处理业务类型不能为空',
                    'refer'         => '',
                ],
                'amount'  => [
                    'type'          => 'A',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '冻结金额不能为空',
                    'refer'         => '',
                ],
                'preMarketingAmount'  => [
                    'type'          => 'A',
                    'length'        => '',
                    'isRequired'    => false,
                    'note'          => '预备使用的红包金额不能为空',
                    'refer'         => '',
                ],
                'expired'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '页面过期时间不能为空',
                    'refer'         => '',
                ],
                'remark'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => false,
                    'note'          => '备注不能为空',
                    'refer'         => '',
                ],
                'redirectUrl'  => [
                    'type'          => 'S',
                    'length'        => '100',
                    'isRequired'    => true,
                    'note'          => '页面回跳URL不能为空',
                    'refer'         => '',
                ],
                'projectNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '标的号不能为空',
                    'refer'         => '',
                ],
                'share'  => [
                    'type'          => 'A',
                    'length'        => '',
                    'isRequired'    => false,
                    'note'          => '购买债转份额不能为空',
                    'refer'         => ['bizType' => 'CREDIT_ASSIGNMENT'],
                ],
                'creditsaleRequestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => false,
                    'note'          => '债权出让请求流水号不能为空',
                    'refer'         => ['bizType' => 'CREDIT_ASSIGNMENT'],
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],
        // 预处理取消 CANCEL_PRE_TRANSACTION
        "CANCEL_PRE_TRANSACTION"  => [
            'serverParam' => [
                'serviceName' => 'CANCEL_PRE_TRANSACTION', //接口名称
            ],
            'dataParam' => [
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => '',
                ],
                'preTransactionNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '预处理业务流水号不能为空',
                    'refer'         => '',
                ],
                'amount'  => [
                    'type'          => 'A',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '取消金额不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],
        // 单笔交易 SYNC_TRANSACTION
        "SYNC_TRANSACTION"  => [
            'serverParam' => [
                'serviceName' => 'SYNC_TRANSACTION', //接口名称
            ],
            'dataParam' => [
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => '',
                ],
                'tradeType'  => [
                    'type'          => 'E',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '交易类型不能为空',
                    'refer'         => '',
                ],
                'projectNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => false,
                    'note'          => '标的号不能为空',
                    'refer'         => '',
                ],
                'saleRequestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => false,
                    'note'          => '债权出让请求流水号不能为空',
                    'refer'         => '',
                ],
                'details'  => [
                    'type'          => 'C',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '业务明细不能为空',
                    'refer'         => '',
                    'details'   => [
                        'bizType'  => [
                            'type'          => 'E',
                            'length'        => '',
                            'isRequired'    => true,
                            'note'          => '业务类型不能为空',
                            'refer'         => '',
                        ],
                        'freezeRequestNo'  => [
                            'type'          => 'S',
                            'length'        => '50',
                            'isRequired'    => false,
                            'note'          => '预处理请求流水号不能为空',
                            'refer'         => '',
                        ],
                        'sourcePlatformUserNo'  => [
                            'type'          => 'S',
                            'length'        => '50',
                            'isRequired'    => false,
                            'note'          => '出款方用户编号不能为空',
                            'refer'         => '',
                        ],
                        'targetPlatformUserNo'  => [
                            'type'          => 'S',
                            'length'        => '50',
                            'isRequired'    => false,
                            'note'          => '收款方用户编号不能为空',
                            'refer'         => '',
                        ],
                        'amount'  => [
                            'type'          => 'A',
                            'length'        => '',
                            'isRequired'    => true,
                            'note'          => '交易金额不能为空',
                            'refer'         => '',
                        ],
                        'income'  => [
                            'type'          => 'A',
                            'length'        => '',
                            'isRequired'    => false,
                            'note'          => '利息不能为空',
                            'refer'         => '',
                        ],
                        'share'  => [
                            'type'          => 'A',
                            'length'        => '',
                            'isRequired'    => false,
                            'note'          => '债权份额不能为空',
                            'refer'         => '',
                        ],
                        'customDefine'  => [
                            'type'          => 'S',
                            'length'        => '50',
                            'isRequired'    => false,
                            'note'          => '平台商户自定义参数不能为空',
                            'refer'         => '',
                        ],
                        'remark'  => [
                            'type'          => 'S',
                            'length'        => '50',
                            'isRequired'    => false,
                            'note'          => '备注不能为空',
                            'refer'         => '',
                        ],
                    ],
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],
        // 批量交易 ASYNC_TRANSACTION
        "ASYNC_TRANSACTION"  => [
            'serverParam' => [
                'serviceName' => 'ASYNC_TRANSACTION', //接口名称
            ],
            'dataParam' => [
                'batchNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '批次号不能为空',
                    'refer'         => '',
                ],
                'bizDetails'  => [
                    'type'          => 'C',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '交易明细不能为空',
                    'refer'         => '',
                    'details'       => [
                        'requestNo'  => [
                            'type'          => 'S',
                            'length'        => '50',
                            'isRequired'    => true,
                            'note'          => '交易明细订单号不能为空',
                            'refer'         => '',
                        ],
                        'tradeType'  => [
                            'type'          => 'E',
                            'length'        => '',
                            'isRequired'    => true,
                            'note'          => '交易类型不能为空',
                            'refer'         => '',
                        ],
                        'projectNo'  => [
                            'type'          => 'S',
                            'length'        => '50',
                            'isRequired'    => false,
                            'note'          => '标的编号不能为空',
                            'refer'         => '',
                        ],
                        'saleRequestNo'  => [
                            'type'          => 'S',
                            'length'        => '50',
                            'isRequired'    => false,
                            'note'          => '债权出让请求流水号不能为空',
                            'refer'         => '',
                        ],
                        'details'  => [
                            'type'          => 'C',
                            'length'        => '',
                            'isRequired'    => false,
                            'note'          => '业务明细不能为空',
                            'refer'         => '',
                            'details'   => [
                                'bizType'  => [
                                    'type'          => 'E',
                                    'length'        => '',
                                    'isRequired'    => true,
                                    'note'          => '业务类型不能为空',
                                    'refer'         => '',
                                ],
                                'freezeRequestNo'  => [
                                    'type'          => 'S',
                                    'length'        => '50',
                                    'isRequired'    => false,
                                    'note'          => '预处理请求流水号不能为空',
                                    'refer'         => '',
                                ],
                                'sourcePlatformUserNo'  => [
                                    'type'          => 'S',
                                    'length'        => '50',
                                    'isRequired'    => false,
                                    'note'          => '出款方用户编号不能为空',
                                    'refer'         => '',
                                ],
                                'targetPlatformUserNo'  => [
                                    'type'          => 'S',
                                    'length'        => '50',
                                    'isRequired'    => false,
                                    'note'          => '收款方用户编号不能为空',
                                    'refer'         => '',
                                ],
                                'amount'  => [
                                    'type'          => 'A',
                                    'length'        => '',
                                    'isRequired'    => true,
                                    'note'          => '交易金额不能为空',
                                    'refer'         => '',
                                ],
                                'income'  => [
                                    'type'          => 'A',
                                    'length'        => '',
                                    'isRequired'    => false,
                                    'note'          => '利息不能为空',
                                    'refer'         => '',
                                ],
                                'share'  => [
                                    'type'          => 'A',
                                    'length'        => '',
                                    'isRequired'    => false,
                                    'note'          => '债权份额不能为空',
                                    'refer'         => '',
                                ],
                                'customDefine'  => [
                                    'type'          => 'S',
                                    'length'        => '50',
                                    'isRequired'    => false,
                                    'note'          => '平台商户自定义参数不能为空',
                                    'refer'         => '',
                                ],
                                'remark'  => [
                                    'type'          => 'S',
                                    'length'        => '50',
                                    'isRequired'    => false,
                                    'note'          => '备注不能为空',
                                    'refer'         => '',
                                ],
                            ],
                        ],
                    ],
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],
        // 单笔债权出让 DEBENTURE_SALE
        "DEBENTURE_SALE"  => [
            'serverParam' => [
                'serviceName' => 'DEBENTURE_SALE', //接口名称
            ],
            'dataParam' => [
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => '',
                ],
                'platformUserNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '债权出让平台用户编号不能为空',
                    'refer'         => '',
                ],
                'projectNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '标的号不能为空',
                    'refer'         => '',
                ],
                'saleShare'  => [
                    'type'          => 'A',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '出让份额不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],
        // 取消债权出让 CANCEL_DEBENTURE_SALE
        "CANCEL_DEBENTURE_SALE"  => [
            'serverParam' => [
                'serviceName' => 'CANCEL_DEBENTURE_SALE', //接口名称
            ],
            'dataParam' => [
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => '',
                ],
                'creditsaleRequestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '债权出让请求流水号不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],
        // 用户授权 USER_AUTHORIZATION
        "USER_AUTHORIZATION"  => [
            'serverParam' => [
                'serviceName' => 'USER_AUTHORIZATION', //接口名称
                'redirectUrl' => self::XIAOJI_WANG_GUAN_CALLBACK, //回调链接
            ],
            'dataParam' => [
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => '',
                ],
                'platformUserNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '平台用户编号不能为空',
                    'refer'         => '',
                ],
                'authList'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '用户授权列表不能为空',
                    'refer'         => '',
                ],
                'redirectUrl'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '页面回跳URL不能为空',
                    'refer'         => '',
                ],
                'failTime'  => [
                    'type'          => 'S',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '授权过期时间不能为空',
                    'refer'         => '',
                    'allowEmpty'    => true,
                ],
                'amount'  => [
                    'type'          => 'S',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '冻结金额不能为空',
                    'refer'         => '',
                    'allowEmpty'    => true,
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],
        // 用户取消授权 CANCEL_USER_AUTHORIZATION
        "CANCEL_USER_AUTHORIZATION"  => [
            'serverParam' => [
                'serviceName' => 'CANCEL_USER_AUTHORIZATION', //接口名称
            ],
            'dataParam' => [
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => '',
                ],
                'platformUserNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '平台用户编号不能为空',
                    'refer'         => '',
                ],
                'authList'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '用户授权列表不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],
        // 授权预处理 USER_AUTO_PRE_TRANSACTION
        "USER_AUTO_PRE_TRANSACTION"  => [
            'serverParam' => [
                'serviceName' => 'USER_AUTO_PRE_TRANSACTION', //接口名称
            ],
            'dataParam' => [
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => '',
                ],
                'platformUserNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '平台用户编号不能为空',
                    'refer'         => '',
                ],
                'originalRechargeNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => false,
                    'note'          => '关联充值请求流水号不能为空',
                    'refer'         => ['bizType' => 'TENDER'],
                ],
                'bizType'  => [
                    'type'          => 'E',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '预处理业务类型不能为空',
                    'refer'         => '',
                ],
                'amount'  => [
                    'type'          => 'A',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '冻结金额不能为空',
                    'refer'         => '',
                ],
                'preMarketingAmount'  => [
                    'type'          => 'A',
                    'length'        => '',
                    'isRequired'    => false,
                    'note'          => '预备使用的红包金额不能为空',
                    'refer'         => ['bizType' => 'TENDER'],
                ],
                'remark'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => false,
                    'note'          => '备注不能为空',
                    'refer'         => '',
                ],
                'projectNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '标的号不能为空',
                    'refer'         => '',
                ],
                'share'  => [
                    'type'          => 'A',
                    'length'        => '',
                    'isRequired'    => false,
                    'note'          => '购买债转份不能为空',
                    'refer'         => ['bizType' => 'CREDIT_ASSIGNMENT'],
                ],
                'creditsaleRequestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => false,
                    'note'          => '债权出让请求流水号不能为空',
                    'refer'         => ['bizType' => 'CREDIT_ASSIGNMENT'],
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],
        // 验密扣费 VERIFY_DEDUCT
        "VERIFY_DEDUCT"  => [
            'serverParam' => [
                'serviceName' => 'VERIFY_DEDUCT', //接口名称
                'redirectUrl' => self::XIAOJI_WANG_GUAN_CALLBACK, //回调链接
            ],
            'dataParam' => [
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => '',
                ],
                'platformUserNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '出款方平台用户编号不能为空',
                    'refer'         => '',
                ],
                'targetPlatformUserNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '收款方平台用户编号不能为空',
                    'refer'         => '',
                ],
                'amount'  => [
                    'type'          => 'A',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '扣费金额不能为空',
                    'refer'         => '',
                ],
                'customDefine'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => false,
                    'note'          => '扣费说明不能为空',
                    'refer'         => '',
                ],
                'redirectUrl'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '页面回跳URL不能为空',
                    'refer'         => '',
                ],
                'expired'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '页面过期时间不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],
        // 资金冻结 FREEZE
        "FREEZE"  => [
            'serverParam' => [
                'serviceName' => 'FREEZE', //接口名称
            ],
            'dataParam' => [
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => false,
                    'note'          => '请求流水号不能为空',
                    'refer'         => ['generalFreezeRequestNo' => false],
                ],
                'platformUserNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '平台用户编号不能为空',
                    'refer'         => '',
                ],
                'generalFreezeRequestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => false,
                    'note'          => '通用冻结请求流水号不能为空',
                    'refer'         => ['requestNo' => false],
                ],
                'amount'  => [
                    'type'          => 'A',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '冻结金额不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],


        // FIXME 检查到这里。。。下面还没检查
        // 资金解冻 UNFREEZE
        "UNFREEZE"  => [
            'serverParam' => [
                'serviceName' => 'UNFREEZE', //接口名称
            ],
            'dataParam' => [
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => '',
                ],
                'platformUserNo'  => [
                    'type'          => 'S',
                    'length'        => '',
                    'isRequired'    => false,
                    'note'          => '平台用户编号不能为空',
                    'refer'         => '',
                ],
                'originalFreezeRequestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => false,
                    'note'          => '原冻结的请求流水号不能为空',
                    'refer'         => '',
                ],
                'amount'  => [
                    'type'          => 'A',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '解冻金额不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],
        // 对账文件下载
        "DOWNLOAD_CHECKFILE"  => [
            'serverParam' => [
                'serviceName' => 'DOWNLOAD_CHECKFILE', //接口名称
            ],
            'dataParam' => [
                'fileDate'  => [
                    'type'          => 'D',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '对账文件日期不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],
        // 对账文件确认
        "CONFIRM_CHECKFILE"  => [
            'serverParam' => [
                'serviceName' => 'CONFIRM_CHECKFILE', //接口名称
            ],
            'dataParam' => [
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => '',
                ],
                'detail'  => [
                    'type'          => 'E',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => 'detail参数不能为空',
                    'refer'         => '',
                ],
                'fileDate'  => [
                    'type'          => 'D',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '对账文件日期不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],
        // 交易密码解冻
        "UNFREEZE_TRADE_PASSWORD"  => [
            'serverParam' => [
                'serviceName' => 'UNFREEZE_TRADE_PASSWORD', //接口名称
            ],
            'dataParam' => [
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '请求流水号不能为空',
                    'refer'         => '',
                ],
                'platformUserNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '平台用户编号不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],

        /*******
         * 查询接口
         */
        // 用户信息查询 QUERY_USER_INFORMATION
        "QUERY_USER_INFORMATION"  => [
            'serverParam' => [
                'serviceName' => 'QUERY_USER_INFORMATION', //接口名称
            ],
            'dataParam' => [
                'platformUserNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '平台用户编号不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],
        // 单笔交易查询
        "QUERY_TRANSACTION"  => [
            'serverParam' => [
                'serviceName' => 'QUERY_TRANSACTION', //接口名称
            ],
            'dataParam' => [
                'requestNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => false,
                    'note'          => '请求流水号不能为空',
                    'refer'         => '',
                ],
                'transactionType'  => [
                    'type'          => 'E',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '交易查询类型不能为空',
                    'refer'         => '',
                ],
                'platformUserNo'  => [
                    'type'          => 'S',
                    'length'        => '',
                    'isRequired'    => false,
                    'note'          => '平台用户编号不能为空',
                    'refer'         => ['transactionType' => 'GENERAL_FREEZE'],
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],
        // 标的信息查询
        "QUERY_PROJECT_INFORMATION"  => [
            'serverParam' => [
                'serviceName' => 'QUERY_PROJECT_INFORMATION', //接口名称
            ],
            'dataParam' => [
                'projectNo'  => [
                    'type'          => 'S',
                    'length'        => '50',
                    'isRequired'    => true,
                    'note'          => '标的号不能为空',
                    'refer'         => '',
                ],
                'timestamp'  => [
                    'type'          => 'T',
                    'length'        => '',
                    'isRequired'    => true,
                    'note'          => '请求时间戳不能为空',
                    'refer'         => '',
                ],
            ]
        ],

    ];


    public static $CG_FREEZE_LOG_TYPE = [
        'freeze'    => '存管资金冻结',
        'unfreeze'  => '存管资金解冻',
    ];
    public static $CG_FREEZE_LOG_STATUS = [
        '1'     => '<span style="color: red">资金冻结</span>',
        '2'     => '<span style="color: blue">资金解冻</span>',
        '3'     => '<span style="color: grey">资金已被解冻</span>',
    ];

    /***
     * 平台资金流转类型
     * @var array
     */
    public static $account_transfer_type = [
        'B2B'   => '平台到平台其他子账户',
        'B2C'   => '平台到个人或企业账户',
        'C2B'   => '个人到平台',
    ];

}
