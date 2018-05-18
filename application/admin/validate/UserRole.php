<?php

namespace app\admin\validate;

use think\Validate;

/**
 * This is the model class for table "user_role".
 *
 * @property integer $id
 * @property integer $uid
 * @property integer $role_id
 * @property string $created_time
 */
class UserRole extends Validate
{


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'role_id'], 'integer'],
            [['created_time'], 'safe'],
        ];
    }

    //验证规则
    protected $rule = [
        'uid'     => 'require|number|checkEmpty',
        'updated_time'    => 'require|checkEmpty',
    ];

    //错误消息
    protected $message = [
        'uid'    => '状态不能为空',
        'updated_time'  => '更新时间不能为空',
    ];

    //验证场景
    protected $scene = [
        'add'  => ['uid', ],
        'edit' => ['uid'],
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
