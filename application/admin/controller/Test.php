<?php
namespace app\admin\controller;

/**
* 登陆处理
*/
class Test extends Common {
	
	public function index(){
		return $this->fetch();
	}

	public function check($name,$pass,$captcha){
		if(captcha_check($captcha)){
			// 验证码验证成功
			echo '验证成功';
			echo $name.$pass;
		}else{
			echo '验证失败';
			echo $name.$pass;
		}
	}
}