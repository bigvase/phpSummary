<?php

namespace app\admin\controllers;

use app\admin\model\Access;
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
    public function Set(){
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
		$date_now = date("Y-m-d H:i:s");
		if( mb_strlen($title,"utf-8") < 1 || mb_strlen($title,"utf-8") > 20 ){
			return $this->renderJSON([],'请输入合法的权限标题~~',-1);
		}

		if( !$urls ){
			return $this->renderJSON([],'请输入合法的Urls~~',-1);
		}

		$urls = explode("\n",$urls);
		if( !$urls ){
			return $this->renderJSON([],'请输入合法的Urls~~',-1);
		}

		//查询同一标题的是否存在
		$has_in = DB::name('Access')->where([ 'title' => $title ])->where('id','neq',$id )->count();
		if( $has_in ){
			return $this->renderJSON([],'该权限标题已存在~~',-1);
		}

		//查询指定id的权限
		$info = DB::name('Access')->where([ 'id' => $id ])->select();
		if( $info ){//如果存在则是编辑
			$model_access = $info;
		}else{//不存在就是添加
			$model_access = new Access();
			$model_access->status = 1;
			$model_access->created_time =  $date_now;
		}
		$model_access->title = $title;
		$model_access->urls = json_encode( $urls );//json格式保存的
		$model_access->updated_time = $date_now;
		$model_access->save(0);

		return $this->renderJSON([],'操作成功~~');
	}
}
