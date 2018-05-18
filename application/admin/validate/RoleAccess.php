<?php

namespace app\admin\validate;

use think\Validate;

/**
 * This is the model class for table "role_access".
 *
 * @property integer $id
 * @property integer $role_id
 * @property integer $access_id
 * @property string $created_time
 */
class RoleAccess extends Validate
{

    //验证规则
    protected $rule = [
        'role_id'     => 'require|number|checkEmpty',
        'created_time'    => 'require|checkEmpty',
    ];

    //错误消息
    protected $message = [
        'role_id'    => 'id不能为空',
        'updated_time'  => '更新时间不能为空',
    ];

    //验证场景
    protected $scene = [
        'add'  => ['role_id'],
        'edit' => ['role_id'],
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
