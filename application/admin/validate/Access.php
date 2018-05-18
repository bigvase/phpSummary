<?php

namespace app\admin\validate;
use think\Validate;


/**
 * This is the model class for table "access".
 *
 * @property integer $id
 * @property string $title
 * @property string $urls
 * @property integer $status
 * @property string $updated_time
 * @property string $created_time
 */
class Access extends Validate
{
    //验证规则
    protected $rule = [
        'status'     => 'require|number|checkEmpty',
        'updated_time'    => 'require|checkEmpty',
        'title'   => 'require|checkContent',
        'urls'    => 'require|checkEmpty'
    ];

    //错误消息
    protected $message = [
        'status'    => '状态不能为空',
        'updated_time'  => '更新时间不能为空',
        'title'   => '标题不能为空',
        'urls' => 'url不能为空'
    ];

    //验证场景
    protected $scene = [
        'add'  => ['status', 'title', 'urls'],
        'edit' => ['status', 'updated_time', 'urls'],
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
