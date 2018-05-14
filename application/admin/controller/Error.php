<?php

namespace app\admin\controllers;

use app\common\controller\BaseController;

class Error extends BaseController {

	//无权限访问页面
    public function Forbidden(){
    	return $this->fetch("forbidden");
	}
}

