<?php

namespace app\admin\validate;

use think\Validate;

/**
 * This is the model class for table "user".
 *
 * @property integer $id
 * @property string $name
 * @property string $email
 * @property integer $is_admin
 * @property integer $status
 * @property string $updated_time
 * @property string $created_time
 */
class User extends Validate
{

    //验证规则
    protected $rule = [
        'is_admin'     => 'require|number|checkEmpty',
        'updated_time'    => 'require|checkEmpty',
        'name'   => 'require|checkContent',
        'email'    => 'require|checkEmpty'
    ];

    //错误消息
    protected $message = [
        'is_admin'    => 's是否超级管理员不能为空',
        'updated_time'  => '更新时间不能为空',
        'name'   => '标题不能为空',
        'email' => 'email不能为空'
    ];

    //验证场景
    protected $scene = [
        'add'  => ['is_admin', 'name'],
        'edit' => ['is_admin', 'name'],
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
