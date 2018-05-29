<?php

namespace app\admin\controller;

use app\common\controller\BaseController;
use think\Db;
use think\Request;

class Accessed extends BaseController {

	//权限列表
    public function Index(){
		$access_list = Db::name('Access')->where([ 'status' => 1 ])->order('id desc')->select();
		return $this->fetch('index',[
			'list' => $access_list
		]);
    }

	/*
	 * 添加或者编辑权限
	 * get 展示页面
	 * post 处理添加或者编辑权限
	 */
    public function accessedSet(){
		//如果是get请求则演示页面
		if( Request::instance()->get() ){
			$id = $this->get("id",0);
			$info = [];
			if( $id ){
				$info = DB::name('Access')->where([ 'status' => 1 ,'id' => $id ])->find();
			}
			return $this->fetch('set',[
				'info' => $info
			]);
		}
		$id = intval( input("id",0) );
		$title = trim( input("title","") );
		$urls = trim( input("urls","") );
		if( mb_strlen($title,"utf-8") < 1 || mb_strlen($title,"utf-8") > 20 ){
            $this->error('请输入合法的权限标题~~');
		}

		if( !$urls ){
            $this->error('请输入合法的Urls~~');
		}

//		$urls = explode("\n",$urls);
//		if( !$urls ){
//            $this->error('请输入合法的Urls~~');
//		}

		//查询同一标题的是否存在
		$has_in = DB::name('Access')->where(['title'=>$title,'id'=>['neq',$id]])->count();
		if( $has_in ){
            $this->error('该权限标题已存在~~');
		}

		//查询指定id的权限
		$info = DB::name('Access')->where([ 'id' => $id ])->select();
        $saveDt['title'] = $title;
        $saveDt['urls'] = $urls;
        $saveDt['status'] = 1;
		if( $info ){//如果存在则是编辑
            $saveDt['updated_time'] = time();
			$ret = DB::name('Access')->where(['id'=>$id])->update($saveDt);
			if($ret) $this->success('更新成功~~');
		}else{//不存在就是添加
            $saveDt['created_time'] = time();
            $ret = DB::name('Access')->insert($saveDt);
            if($ret) $this->success('操作成功~~');
		}
	}
}
