<?php
/**
 * Class RoleController
 */

namespace app\admin\controller;


use app\admin\model\Role;
use app\admin\model\RoleAccess;
use app\common\controller\BaseController;
use think\Db;
use think\Request;

class Roles extends  BaseController {
	//角色列表页面
	public function Index(){
		$list = DB::name('Role')->order('id desc')->select();

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
	public function roleSet(){
		if( Request::instance()->get() ){
			$id = input("id",0);
			$info = [];
			if( $id ){
				$info = DB::name('Role')->where([ 'id' => $id ])->find();
			}
			return $this->fetch("set",[
				"info" => $info
			]);
		}

		$id = input("id",0);
		$name = input("name","");
		if( !$name ){
            $this->error("请输入合法的角色名称~~");
		}
		//查询是否存在角色名相等的记录
		$role_info = DB::name('Role')
			->where([ 'name' => $name,'id'=>['<>',$id]])
			->find();
		if( $role_info ){
			$this->error("该角色名称已存在，请输入其他的角色名称~~");
		}

		$info = DB::name('Role')->where([ 'id' => $id ])->find();
		$saveDt = [
		    'name'=>$name,
            'status'=>1
        ];
		if( $info ){//编辑动作
            $saveDt['updated_time'] = time();
            $ret = DB::name('Role')->where(['id'=>$id])->update($saveDt);
            if($ret) $this->success('修改成功~~');
		}else{//添加动作
            $saveDt['created_time'] = time();
            $ret = DB::name('Role')->insert($saveDt);
            if($ret) $this->success('添加成功');
		}
		$this->error("操作失败~~");
	}

	//设置角色和权限的关系逻辑
	public function Access(){
		//http get 请求 展示页面
		if( Request::instance()->get() ){
			$id = input("id",0);
			$reback_url = Url("/role/index");
			if( !$id ){
                $this->redirect( $reback_url );
			}
			$info = DB::name('Role')->where([ 'id' => $id ])->find();
			if( !$info ){
                $this->redirect( $reback_url );
			}

			//取出所有的权限
			$access_list = DB::name('Access')->where([ 'status' => 1 ])->order('id desc')->select();

			//取出所有已分配的权限
			$role_access_list = DB::name('RoleAccess')->where([ 'role_id' => $id ])->select();
			$access_ids = array_column( $role_access_list,"access_id" );
			return $this->fetch("access",[
				"info" => $info,
				'access_list' => $access_list,
				"access_ids" => $access_ids
			]);
		}
		//实现保存选中权限的逻辑
		$id = input("id",0);
		$access_ids = $_POST["access_ids"];
		if( !$id ){
            $this->error("您指定的角色不存在");
		}

		$info = DB::name('Role')->where([ 'id' => $id ])->find();
		if( !$info ){
            $this->error("您指定的角色不存在");
		}

		//取出所有已分配给指定角色的权限
		$role_access_list = DB::name('RoleAccess')->where([ 'role_id' => $id ])->select();

		$assign_access_ids = array_column( $role_access_list , 'access_id' );
		/**
		 * 找出删除的权限
		 * 假如已有的权限集合是A，界面传递过得权限集合是B
		 * 权限集合A当中的某个权限不在权限集合B当中，就应该删除
		 * 使用 array_diff() 计算补集
		 */
		$delete_access_ids = array_diff( $assign_access_ids,$access_ids );

		if( $delete_access_ids ){
            $delete_access_string = implode(',',$delete_access_ids);
			DB::name('RoleAccess')->where([ 'role_id' => $id,'access_id' => [ 'in',$delete_access_string ] ])->delete();
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
			    $saveDt = [
			        'role_id'=>$id,
                    'access_id'=>$_access_id,
                    'created_time'=>time()
                ];
                DB::name('RoleAccess')->insert($saveDt);
			}
		}
        $this->success("操作成功~~");
	}
}