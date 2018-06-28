<?php
/**
 * Created by PhpStorm.
 * User: bigsave
 * Date: 2018/6/14
 * Time: 17:21
 */
return [
    //短信渠道=>对应的方法
    'channel'=>[
        'ddk'=>'sendCl',
    ],
    //短信模板
    'template'=>[
        'test'=>"这是一条短息测试模板金额#money#，发送的时间是#time#,结束了",
    ],
];