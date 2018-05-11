<?php
/*
 * e签宝快捷签 PHP SDK配置文件
 * 版本：2.0.000
 * Create Data ： 2016-12-8
 * 环境：需支持命名空间 php >= 5.3 、php7
 *
 * */
return array(
//==================请在这里配置您的接入信息===================
    //************************测试环境*******************************start
    ///项目初始化请求地址
//    'open_api_url' => 'http://121.40.164.61:8080/tgmonitor/rest/app!getAPIInfo2',//模拟环境
    //接入平台项目ID,必填
//    'project_id' => '1111563517',
//    项目密钥，必填
//    'project_secret' => '95439b0863c241c63a861b87d1e647b7',
     //签名方式 ：支持RSA、 HMACSHA256
     //使用RSA签名方式，需打开php_openssl扩展。
//    'sign_algorithm' => 'HMACSHA256',
    //'sign_algorithm' => 'RSA',

     //接入平台rsa私钥包含“-----BEGIN PRIVATE KEY-----”和“-----END PRIVATE KEY-----”。用于对请求数据进行签名。
     //如果签名方式设置为“RSA”时，请设置该参数；
     //如果为HMACSHA256，置空
//    'rsa_private_key' => '0EwCWPOh+AbfQK+9Ciy2b+Sk/obznMOM06recbByACfageDRe6GBpd7Rir24hquY
//da6n1Eg9bzbYhaBvHF++iQ8IO5TSvL66ipyyq9pl15YvMjhMC9VjPUxx7GEJSE6i
//m9LWrPiGbEEQpuTeIFtYdtuUaOTgIkWa2vn7lm5eVzAtsWQbE6vX2fEmP5zLHmls
//O/urQgxvZoFGuy4x8gxdGYjQzHuHSZ4xKIp+7O9mfmjBzGhwR+4wqYSzwpROGYCm
//+B6nkolmayYQLW3Wdzh1BanHMqrAXdBX0QxYIOZFWrnoAogTr+KucjkXSSlE6WMn
//lCRGoh6KjKs5J21OLCGPplHwcksTfvP8sy5jvPbXkolQb6bg0mohSbUm6/jfSiei
//pmayUxgRNCc/k04YjqXMU+cKqnLj26qkHCHQDYwv6vUpCkgoxf+euWOD0Qks75VU
//xaX93UfSSh1oZvGVzTSepAMYhtGOwsmi/hLWg0pZVpoxhDhYkFXFcrfAhBguWYIG
//oLzgGcz4BfoZQuLodSAqw1ksieaJ3Fh2deoD56XBuIGZ7XsYAkp+WCk+IHGrvKXJ
//gGBxA4jOCS4NOhcMMkEKZ73mXcNyxW52qtfIO5uNkkM8S7kqskTL7gWb/z67Qent
//q8IMmkRSn1M+OA3yR3wrf7cXmsxctHiz+wPgBbSF5EkO2zi4TaGr0qzHeThLyOJf
//osLxs6iW81DY513xYSMa+dg6dq514ncUVvfSPdkmDKl7JDpxpq5gkBFIqJZ3aQbC
//1qrGLL+X5Oco9bhgorKm/kkxSL9hcS6mIqZbZtN9LO4rtqyrtz2gvg==',

     //e签宝公钥,包含“-----BEGIN PUBLIC KEY-----”和“-----END PUBLIC KEY-----”。用于对响应数据进行验签。
     //如果签名方式设置为“RSA”时，请设置该参数
     //如果为HMACSHA256，置空
//    'esign_public_key' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDfzL8H04Wvnfvga7dTLRaLpTP
//YkPwLJVPKb/SzMGH1g38tIMOz3P1NJvUgwZZqiVGstHe4UY16p2ZGYoCsd5tvD4h
//i6j27xx0P+eN49RpbArYlYnpqlxmlYwdzo/mbqOHNM07wHtdzhRkDfdq7AzmOSK/
//RDqKtGn0riyt81fiXwIDAQAB',
//************************测试环境*******************************end
//
//************************生产线环境环境******************************start

    //项目初始化请求地址
    'open_api_url' => "http://openapi.tsign.cn:8080/tgmonitor/rest/app!getAPIInfo2", //正式环境
    //接入平台项目ID,必填
    'project_id' => '1111564982',
    //项目密钥，必填
    'project_secret' => '3c856282c75071ae969259fe80e61c36',

     //签名方式 ：支持RSA、 HMACSHA256
     //使用RSA签名方式，需打开php_openssl扩展。
    'sign_algorithm' => 'HMACSHA256',
    //'sign_algorithm' => 'RSA',

     //接入平台rsa私钥包含“-----BEGIN PRIVATE KEY-----”和“-----END PRIVATE KEY-----”。用于对请求数据进行签名。
     //如果签名方式设置为“RSA”时，请设置该参数；
     //如果为HMACSHA256，置空
    'rsa_private_key' => '0EwCWPOh+AbfQK+9Ciy2b+Sk/obznMOM06recbByACfageDRe6GBpd7Rir24hquY
da6n1Eg9bzbYhaBvHF++iQ8IO5TSvL66ipyyq9pl15YvMjhMC9VjPUxx7GEJSE6i
m9LWrPiGbEEQpuTeIFtYdtuUaOTgIkWa2vn7lm5eVzAtsWQbE6vX2fEmP5zLHmls
O/urQgxvZoFGuy4x8gxdGYjQzHuHSZ4xKIp+7O9mfmjBzGhwR+4wqYSzwpROGYCm
+B6nkolmayYQLW3Wdzh1BanHMqrAXdBX0QxYIOZFWrnoAogTr+KucjkXSSlE6WMn
lCRGoh6KjKs5J21OLCGPplHwcksTfvP8sy5jvPbXkolQb6bg0mohSbUm6/jfSiei
pmayUxgRNCc/k04YjqXMU+cKqnLj26qkHCHQDYwv6vUpCkgoxf+euWOD0Qks75VU
xaX93UfSSh1oZvGVzTSepAMYhtGOwsmi/hLWg0pZVpoxhDhYkFXFcrfAhBguWYIG
oLzgGcz4BfoZQuLodSAqw1ksieaJ3Fh2deoD56XBuIGZ7XsYAkp+WCk+IHGrvKXJ
gGBxA4jOCS4NOhcMMkEKZ73mXcNyxW52qtfIO5uNkkM8S7kqskTL7gWb/z67Qent
q8IMmkRSn1M+OA3yR3wrf7cXmsxctHiz+wPgBbSF5EkO2zi4TaGr0qzHeThLyOJf
osLxs6iW81DY513xYSMa+dg6dq514ncUVvfSPdkmDKl7JDpxpq5gkBFIqJZ3aQbC
1qrGLL+X5Oco9bhgorKm/kkxSL9hcS6mIqZbZtN9LO4rtqyrtz2gvg==',

     // e签宝公钥,包含“-----BEGIN PUBLIC KEY-----”和“-----END PUBLIC KEY-----”。用于对响应数据进行验签。
     // 如果签名方式设置为“RSA”时，请设置该参数
     // 如果为HMACSHA256，置空
    'esign_public_key' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDDfzL8H04Wvnfvga7dTLRaLpTP
YkPwLJVPKb/SzMGH1g38tIMOz3P1NJvUgwZZqiVGstHe4UY16p2ZGYoCsd5tvD4h
i6j27xx0P+eN49RpbArYlYnpqlxmlYwdzo/mbqOHNM07wHtdzhRkDfdq7AzmOSK/
RDqKtGn0riyt81fiXwIDAQAB',

 //************************生产线环境环境******************************end  
    //---------------------------------------------以下是公共的配置----------------
    /* http请求代理服务器设置;不使用代理的时候置空 */
    'proxy_ip' => '',
    'proxy_port' => '',
    /* 与服务端(e签宝)通讯方式设置。HTTP或HTTPS */
    'http_type' => 'HTTPS',
    'retry' => 3,
    /* 本地java服务 */
    'java_server' => 'http://47.96.19.53:8080',
    //http://javaserver.xiaojilicai.com
    'sign_env' => 'product',//test测试环境，product生产环境


 
);



