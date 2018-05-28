<?php
/**
 * Class UserController
 */

namespace app\admin\controller;

use app\admin\model\User;
use app\admin\model\UserRole;
use app\common\controller\BaseController;
use think\Db;
use think\Request;

class Users extends  BaseController{

    protected function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
    }

    //用户列表
	public function Index(){
        echo 12121212;die;
		//查询所有用户
		$user_list = Db::name('User')->where([ 'status' => 1 ])->order([ 'id' => SORT_DESC ])->select();
		//判断当前用户时候有访问添加或编辑用户的权限
		$set_flag = $this->checkPrivilege( "user/set" );
//		dump($user_list);die;
		return $this->fetch('index',[
			'list' => $user_list,
			'set_flag' => $set_flag
		]);
	}

	/*
	 * 添加或者编辑用户页面
	 * get 展示页面
	 * post 处理添加或者编辑用户
	 */
	public function userSet(){
        if(Request::instance()->get('id')){
            $uid = input('id');
            $user = Db::name('user')->where(['id'=>$uid,'status'=>1])->find();
            $this->assign('user',$user);
        }
        if(Request::instance()->post('id')){
            $id = intval( input("id",0) );
            $name = trim( input("name","") );
            $email = trim( input("email","") );

//            $this->validate()
            if( mb_strlen($name,"utf-8") < 1 || mb_strlen($name,"utf-8") > 20 ){
                 $this->error('',Url('/admin/users/index'));
            }

            if( !filter_var( $email , FILTER_VALIDATE_EMAIL) ){
                return $this->renderJSON([],'请输入合法的邮箱~~',-1);
            }
            //查询该邮箱是否已经存在
            $has_in = DB::name('User')->where([ 'email' => $email ])->Where('id','neq',$id)->count();
        }

        return $this->fetch('userSet',[]);
	}




	//用户登录页面
	public function actionLogin(){
		return $this->fetch("login",[
			'host' => $_SERVER['HTTP_HOST']
		]);
	}

	//伪登录业务方法,所以伪登录功能也是需要有auth_token
	public function actionVlogin(){
		$uid = $this->get("uid",0);
		$reback_url = url("/");
		if( !$uid ){
			$this->redirect( $reback_url );
		}
		$user_info = DB::name('User')->where([ 'id' => $uid ])->find();
		if( !$user_info ){
			$this->redirect( $reback_url );
		}
		//cookie保存用户的登录态,所以cookie值需要加密，规则：user_auth_token + "#" + uid
		$this->createLoginStatus( $user_info );
        $this->redirect( $reback_url );
	}
}
