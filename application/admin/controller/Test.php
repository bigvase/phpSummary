<?php
/**
 * Class TestController
 */

namespace app\admin\controllers;


use app\common\controller\BaseController;

class Test extends  BaseController {
	public function actionPage1(){
		return $this->fetch("page1");
	}

	public function actionPage2(){
		return $this->fetch("page2");
	}

	public function actionPage3(){
		return $this->fetch("page3");
	}

	public function actionPage4(){
		return $this->fetch("page4");
	}

	public function actionPage5(){
		return $this->fetch("page5");
	}
}