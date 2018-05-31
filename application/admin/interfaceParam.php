<?php
/**
 * Created by PhpStorm.
 * User: bigsave
 * Date: 2018/5/30
 * Time: 19:17
 */
return [
    //基本信息配置
    'base_info'=>[
        'public_key'=>'',
        'private_key'=>'-----BEGIN PRIVATE KEY-----
MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBALxo/CpZNV0uvM1w
1mrUKB/6HWdNx19hFFB1lZdmJB/q48a7qkVa7YsKBqV2WW3ehmUZ8m5xhq9q2e38
mp2Fm0KRdCeCz+qedm33EuY8P6s/RIYnFXkmPNV5tHsYVb8U+ZF8wOn1TrNvpLP8
fZxU/K4BpgMwaNqNKqc+XoLwnjG5AgMBAAECgYAigQ99Ke0t7XtCHGIIGmZmxMVR
v9q9SugPfBkzKs9+0ON557BdFzPdfK6O6seh6VivdzsAouXJhkX24YXnRyqRizTW
sBUc5ytTyh9biFir4bIo5fAp8WQENElg0LnnKmY7q7/MUzVAzv6gUHefg7VtFz8M
0yMElsAmqywZ6TYtSQJBAPpHjFhI3rXBDcRjoN/i52oHmNlWPMyFrzXv84cx2bub
5aD5lEStKokAlG4yAquPSxBNmBS5+/8qEwKmlPYfi7sCQQDAt2wtpVs/8T/UxDlh
OKChiKKP46tnHIRoa9EcHBdsyfCVW+qXz+fFQC/JKrXJ6e9y8S5FiRQuxTWWDhQs
Z48bAkEAuO4vJHjLnw8EBi37jBLUHYN5jHXtPM429arUjfvmv3plBToFNW2itVKp
Wnc3g97Af05mZkI6koNbQqUmAmqyywJAeg9y6BJUklJV8If8I9S/ALDO7b3woRVR
0+V+A4TTXWcmByO5mT/od2mpGov/qgyOScnyWb5x1xG3V/xA0MMiwwJATNZYQXRS
TTjvCvGRAy/neI6TJ6d8S26L8Y8qaAmwv82M933SDtgbb7ncj7JpIxc8r1+4UAkK
cWOLaU+lLhFh0Q==
-----END PRIVATE KEY-----',
        'request_url' => '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC8aPwqWTVdLrzNcNZq1Cgf+h1n
TcdfYRRQdZWXZiQf6uPGu6pFWu2LCgaldllt3oZlGfJucYavatnt/JqdhZtCkXQn
gs/qnnZt9xLmPD+rP0SGJxV5JjzVebR7GFW/FPmRfMDp9U6zb6Sz/H2cVPyuAaYD
MGjajSqnPl6C8J4xuQIDAQAB
-----END PUBLIC KEY-----',
    ],
    //接口名称配置
    'interface_name' => [
        'TEST',
    ],
    //接口参数配置
    'sign_param' => [
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
    ],

];