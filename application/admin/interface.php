<?php
/**
 * Created by PhpStorm.
 * User: bigsave
 * Date: 2018/5/30
 * Time: 19:17
 */
class interfaceParam{
    //基本配置
    public static $baseInfo = [
        'public_key'=>'',
        'private_key'=>'',
        'request_url' => '',
    ];
    //接口列表
    public static $interfaceName = [
        'TEST',
    ];

    //参数校验列表
    public static $signParam = [
        // 查询用户信息 QUERY_USER_INFORMATION
        "TEST" => [
            'serverParam' => [
                'serviceName' => 'TEST', //接口名称
            ],
            'dataParam'   => [
                'requestNo'   => [
                    'type'       => 'S',
                    'length'     => '50',
                    'isRequired' => true,
                    'note'       => '请求流水号不能为空',
                    'refer'      => '',
                ],
                'username'  =>[
                    'type'       => 'S',
                    'length'     => '50',
                    'isRequired' => true,
                    'note'       => '请求流水号不能为空',
                    'refer'      => '',
                ],
                'timestamp' => [
                    'type'       => 'TS',
                    'length'     => '',
                    'isRequired' => true,
                    'note'       => '请求时间戳不能为空',
                    'refer'      => '',
                ],
            ],
        ],
    ];
}