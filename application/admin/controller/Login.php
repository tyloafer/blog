<?php
namespace app\admin\controller;
use app\admin\model\Log;


/**
* 登陆处理
*/
class Login extends Common {
	
	public function index(){
		return $this->fetch();
	}

	public function check($name,$pass,$captcha){
		$title = $content = $url = '';
		if(captcha_check($captcha)){
			// 验证码验证成功
			$admin = $this->db->name('admin_user')->field('*')->where("name = '$name'")->find();
			if($admin && $admin['pass'] == pass($pass, $admin['salt'])){
				session('admin', $admin, 'admin');
				$title   = '欢迎回来，'.$admin['name'];
				$content = '即将为你跳转';
				$url     = url('Index/index');

				// 将此次信息记录到admin_log表中
				$log = new Log();
				$log->write($admin['user_id'],'用户登陆', $this->admin_ip['ip'] ? $this->admin_ip['ip'] : '');

				// 判断本次登陆与上次登陆地址是否相同
				if($admin['last_login_address'] && $admin['last_login_address'] != '本地' && $admin['last_login_address'] != $this->admin_ip['address']){
					// 邮件通知管理员异地登陆
					
				}
				// 更新用户的上次登录时间 登陆ip等信息
				$this->db->execute("set names utf8");
				$data = [
					'last_login'	=>	time(),
					'last_ip'		=>	$this->admin_ip['ip'],
					'last_login_address'	=>	$this->admin_ip['address']
				];
				$this->db->name('admin_user')->where(['user_id' => $admin['user_id']])->update($data);

			}else{
				$title   = '用户名或密码错误';
				$content = '请重新尝试';
				$url     = url('login/index');
			}
			// return $this->alert('欢迎回来', '即将为你跳转', url('index/index'));
		}else{
				$title   = '验证码错误';
				$content = '请重新尝试';
				$url     = url('login/index');
		}

		return $this->alert($title, $content, $url);

	}

	public function logout(){
		session(null, 'admin');
		return $this->alert('退出成功', '', url('login/index'));
	}


}
