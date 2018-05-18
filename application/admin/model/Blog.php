<?php

namespace app\admin\model;

use think\Model;

class Blog extends Model
{
    //只读字段
    protected $readonly = ['name','email'];
    protected $handle = '';
    //
    protected function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
    }

    public function blogSave(){
        $blog = Blog::get(4);
        // 更改某些字段的值
        $blog->title = 'TOPThink';
        $blog->text = 'Topthink@gmail.com';
        $blog->add_time = time();
        // 保存更改后的用户数据
        $ret = $blog->save();

        return $ret;
    }

    public function blogAdd(){
        $blog = new Blog;
        // 注册回调到beforeInsert函数
        $blog::event('before_insert', function () {
            echo "before_insert";
        });
//        $blog::event('before_insert', 'beforeInsert');

        $blog->data([
            'title'  =>  'thinkphp',
            'text' =>  'thinkphp@qq.com',
            'add_time'=>time(),
        ]);
        $blog->save();
        if($blog->id){
            dump($blog->id);die;
        }

        return $blog->id;
    }

    public function blogDel(){
        $blog = Blog::get(1);
        $ret = $blog->delete();
        return $ret;
    }


}