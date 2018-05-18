<?php

namespace app\admin\Validate;

use think\Validate;

/**
 * This is the model class for table "app_access_log".
 *
 * @property integer $id
 * @property integer $uid
 * @property string $target_url
 * @property string $query_params
 * @property string $ua
 * @property string $ip
 * @property string $note
 * @property string $created_time
 */
class AppAccessLog extends Validate
{

    //验证规则
    protected $rule = [
        'uid'     => 'require|number|checkEmpty',
        'query_params'    => 'require|checkEmpty',
        'created_time'   => 'require|checkContent',
        'target_url'    => 'require|checkEmpty',
        'ip'   => 'require|ip|checkContent',
        'note'   => 'require|checkContent',
    ];

    //错误消息
    protected $message = [
        'uid'    => '状态不能为空',
        'query_params'  => '更新时间不能为空',
        'created_time'   => '标题不能为空',
        'target_url' => 'url不能为空',
        'ip'        => 'ip不能为空',
        'note'      => '消息不能为空',
    ];

    //验证场景
    protected $scene = [
        'add'  => ['uid', 'query_params', 'created_time','target_url','ip','note'],
        'edit' => ['query_params', 'target_url'],
        'del'  => ['id']
    ];

    protected function checkEmpty($value)
    {
        if (is_string($value)) {
            $value = trim($value);
        }
        if (empty($value)) {
            return false;
        }
        return true;
    }

    protected function checkContent($value)
    {
        $value = strip_tags($value);
        $value = str_replace('&nbsp;', '', $value);
        $value = trim($value);
        if (empty($value)) {
            return false;
        }
        return true;
    }
}
