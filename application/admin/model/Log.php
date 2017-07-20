<?php
namespace app\admin\model;
use think\Model;

/**
 * 管理员操作日志记录表
 */

class Log extends Model {

	public function write($user_id, $log_info, $ip) {
		$data = [
			'log_time'   =>	time(),
			'user_id'    =>	$user_id,
			'log_info'   =>	$log_info,
			'ip_address' =>	$ip,
		];
		db('admin_log')->insert($data);
	}
}


