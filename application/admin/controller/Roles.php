<?php
/**
 * Class RoleController
 */

namespace app\admin\controllers;


use app\common\controller\BaseController;
use app\models\Access;
use app\models\Role;
use app\models\RoleAccess;
use app\services\UrlService;
use think\Db;
use think\Request;

class Roles extends  BaseController {
	//角色列表页面
	public function Index(){
		$list = Db::table('Role')->order('id desc')->select();

		return $this->fetch("index",[
			'list' => $list
		]);
	}

	public function actionAdd(){

	}

	public function actionEdit(){

	}
	/*
	 * 添加或者编辑角色页面
	 * get 展示页面
	 * post 处理添加或者编辑动作
	 */
	public function actionSet(){
		if( Request::instance()->get() ){
			$id = input("id",0);
			$info = [];
			if( $id ){
				$info = Db::table('Role')->where([ 'id' => $id ])->find();
			}
			return $this->fetch("set",[
				"info" => $info
			]);
		}

		$id = input("id",0);
		$name = input("name","");
		$date_now = date("Y-m-d H:i:s");
		if( !$name ){
			return $this->renderJSON([],"请输入合法的角色名称~~",-1);
		}
		//查询是否存在角色名相等的记录
		$role_info = Db::table('Role')
			->where([ 'name' => $name ])->Where('id','new',$id)
			->find();
		if( $role_info ){
			return $this->renderJSON([],"该角色名称已存在，请输入其他的角色名称~~",-1);
		}

		$info = Db::table('Role')->where([ 'id' => $id ])->find();
		if( $info ){//编辑动作
			$role_model = $info;
		}else{//添加动作
			$role_model = new Role();
			$role_model->created_time = $date_now;
		}
		$role_model->name = $name;
		$role_model->updated_time = $date_now;

		$role_model->save(0);
		return $this->renderJSON([],"操作成功~~",200);
	}

	//设置角色和权限的关系逻辑
	public function actionAccess(){
		//http get 请求 展示页面
		if( Request::instance()->get() ){
			$id = input("id",0);
			$reback_url = Url("/role/index");
			if( !$id ){
                $this->redirect( $reback_url );
			}
			$info = Db::table('Role')->where([ 'id' => $id ])->find();
			if( !$info ){
                $this->redirect( $reback_url );
			}

			//取出所有的权限
			$access_list = Db::table('Access')->where([ 'status' => 1 ])->order('id desc')->select();

			//取出所有已分配的权限
			$role_access_list = Db::table('RoleAccess')->where([ 'role_id' => $id ])->select();
			$access_ids = array_column( $role_access_list,"access_id" );
			return $this->fetch("access",[
				"info" => $info,
				'access_list' => $access_list,
				"access_ids" => $access_ids
			]);
		}
		//实现保存选中权限的逻辑
		$id = input("id",0);
		$access_ids = input("access_ids",[]);

		if( !$id ){
			return $this->renderJSON([],"您指定的角色不存在",-1);
		}

		$info = Db::table('Role')->where([ 'id' => $id ])->find();
		if( !$info ){
			return $this->renderJSON([],"您指定的角色不存在",-1);
		}

		//取出所有已分配给指定角色的权限
		$role_access_list = Db::table('RoleAccess')->where([ 'role_id' => $id ])->select();
		$assign_access_ids = array_column( $role_access_list,'access_id' );
		/**
		 * 找出删除的权限
		 * 假如已有的权限集合是A，界面传递过得权限集合是B
		 * 权限集合A当中的某个权限不在权限集合B当中，就应该删除
		 * 使用 array_diff() 计算补集
		 */
		$delete_access_ids = array_diff( $assign_access_ids,$access_ids );
		if( $delete_access_ids ){
			Db::table('RoleAccess')->where([ 'role_id' => $id,'access_id' => $delete_access_ids ])->delete();
		}

		/**
		 * 找出添加的权限
		 * 假如已有的权限集合是A，界面传递过得权限集合是B
		 * 权限集合B当中的某个权限不在权限集合A当中，就应该添加
		 * 使用 array_diff() 计算补集
		 */
		$new_access_ids = array_diff( $access_ids,$assign_access_ids );
		if( $new_access_ids ){
			foreach( $new_access_ids as $_access_id  ){
				$tmp_model_role_access = new RoleAccess();
				$tmp_model_role_access->role_id = $id;
				$tmp_model_role_access->access_id = $_access_id;
				$tmp_model_role_access->created_time = date("Y-m-d H:i:s");
				$tmp_model_role_access->save( 0 );
			}
		}
		return $this->renderJSON([],"操作成功~~",200 );
	}
}