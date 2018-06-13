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
        'private_key' => 'MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQC0xCuegDXWM1FUj5s/hFif5Ca0XvVvo1W3b7XnM9TdEi1c3C0kSwrQp15fmVRoogsQ0LD0i/hbkgNhXZOgUqnu0hLZhvJl2O8HqFNeaVMQ0bccZ7BkaT594yvetgjyfn/u7YouKxR+GhrQVdN0FEGPHNH1jjSQgX+vfMe0ykibo4lOenEfx4uyTBcbQBVlvBzoVKw4gbxkQgTDdM3dwSaYS3CXmQB5QCs3VWXRhPCj/pJWolg3gJsdzkGrsmOQiA0VlOfyRJcmKLSEhRcLpdsNI44Az/hGoPZmvbWILJ7WJS4cddQTC9/NEOV6qO7h8a9/lfB5rpQmO7F4s7WPK9AFAgMBAAECggEAKpCm1MPL6Yxb8lV+cQ5w7/WBR6e0k30aif88Dh0eWpAVLnCKEKm6+jbu+gPY5GqDwInjoTH0YVuYgCzQvke4zAubdK1aFrFmV59DQk/6x1Makw23c2100Z/UjLTAlplC9rfoecabJLZw6e3LxOGgLlrS9cduiTh1IJV5URDw1/Tc6Nvvppza04ewM+ie8YJ04iSeh4lsTGcT4pfKqzZ0+z3EfK/waXNXG1O8phVKKi3Vn3GsGHT5JKcWSOWaDv/6Dylc3MgtWK3YDCjOBypxmWJkiadijQJWUI00QqVprdg2xnPBmfFeeTj02FOFXzTamqaw0lDuKnNAKqDBcGPNYQKBgQDXSjp/GLcxmQXJcHcvvyN+Z8itJtRuxvFz6KsW1A0Mp0gfQqMKrDo6X9DYrDoIvizMm9p7tC4YYnftjmfwFdFH6ZchrslOPOvzC1OalicTb93clfM4SGMjoGMuiZWadLJUIgIywFw76IM53g/32VbL8gYsK0GyWoSEmqgiRRDQDwKBgQDW8rfGdL3IHCcN5/NzN+RKjWUZzsqRmUcBf6/huJkoYPnEWhAnA4FaXpIWmrW7+qe0yafzs76C1mgQKqFT81CQoeoy/CHNV7udm68gn46PSAFhl0FodqmHnMGyZL/0AM2umpeYEkWY1bL+iPM/LuvyBtaTt6LjMxL30WwfJevKqwKBgQCINo3GRmP5/IB90CuIyR1y58U/UJcNs8+m72n4WpgbDmgCZ03y/b8lmePwgx+A0ppTprRYmkqj4QFSC0zVyWgNYMzfYdA6MS90KhFueFwm3xt3amRlkt8u9lZqZmCCRh1iP9Y2OCDjQpxsa4Sc4yUYinu/TGsXpk+7+oIwlJQrnwKBgDjLAMuq5MoOxjLiamyzA9q+6UucW+GEgkJfHnWhdLY6iUPcGBB22KKsAiV+0y3L2Kvn7Dxz7Y5cYDqFSQMJcuwEHAFEpRnAaI4IKImSHvS0rci/UkTrtXdjb7pW7HDoFXBg4FUJ3uG29QhT3xF+sFDOhbuZ9avaPtTDvLGuL1LpAoGBAK+DfaiwQk2NlIEpej4aZDN/itNk6dMPxvHG/Msg4VvjlGAXsfYpzeqa4ytJcH60mBSf2VZXaDmdwX9EOdBRGNVBypEr7sOmnxllemPnXvA+pc3yIWulzxxxT6Qr2AlZxjyTngW5W/QkUE0CpbAP9HEg1EoiHDSdJMm8L5CzU24q',
        'public_key'  => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAtMQrnoA11jNRVI+bP4RYn+QmtF71b6NVt2+15zPU3RItXNwtJEsK0KdeX5lUaKILENCw9Iv4W5IDYV2ToFKp7tIS2YbyZdjvB6hTXmlTENG3HGewZGk+feMr3rYI8n5/7u2KLisUfhoa0FXTdBRBjxzR9Y40kIF/r3zHtMpIm6OJTnpxH8eLskwXG0AVZbwc6FSsOIG8ZEIEw3TN3cEmmEtwl5kAeUArN1Vl0YTwo/6SVqJYN4CbHc5Bq7JjkIgNFZTn8kSXJii0hIUXC6XbDSOOAM/4RqD2Zr21iCye1iUuHHXUEwvfzRDleqju4fGvf5Xwea6UJjuxeLO1jyvQBQIDAQAB',

        'request_url' => '',
        'aesKey'      => '5hod8TsThKtkx1LH',
        'aesIV'       => 'qvuXixMQ33m0rko2',
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