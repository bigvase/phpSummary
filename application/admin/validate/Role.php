<?php

namespace app\admin\Validate;

use think\Validate;

/**
 * This is the model class for table "role".
 *
 * @property integer $id
 * @property string $name
 * @property integer $status
 * @property string $updated_time
 * @property string $created_time
 */
class Role extends Validate
{


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['status'], 'integer'],
            [['updated_time', 'created_time'], 'safe'],
            [['name'], 'string', 'max' => 50],
        ];
    }

    //验证规则
    protected $rule = [
        'status'     => 'require|number|checkEmpty',
        'updated_time'    => 'require|checkEmpty',
        'name'   => 'require|checkContent',
    ];

    //错误消息
    protected $message = [
        'status'    => '状态不能为空',
        'updated_time'  => '更新时间不能为空',
        'name'   => '标题不能为空',
    ];

    //验证场景
    protected $scene = [
        'add'  => ['status', 'name', ],
        'edit' => ['status',],
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
