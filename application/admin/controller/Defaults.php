<?php
/**
 * Class DefaultController
 */

namespace app\admin\controller;

use app\common\controller\BaseController;

class Defaults extends  BaseController{
	//我才是默认首页
	public function Index(){
		return $this->fetch("index");
	}
}