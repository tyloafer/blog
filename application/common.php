<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
// 加密密码
function pass($pass, $salt){
	return md5(md5($pass).$salt);
}

// 获取用户的ip信息级地址信息
function getip() {
    $unknown = 'unknown';
    if ( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] && strcasecmp($_SERVER['HTTP_X_FORWARDED_FOR'], $unknown) ) {
    	$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif ( isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], $unknown) ) {
      	$ip = $_SERVER['REMOTE_ADDR'];
	}
	/*
	处理多层代理的情况
	或者使用正则方式：$ip = preg_match("/[\d\.]{7,15}/", $ip, $matches) ? $matches[0] : $unknown;
	*/
	if (false !== strpos($ip, ','))
	    $ip = reset(explode(',', $ip));
	$data['ip'] = $ip;
	// 根据ip获取用户的地址
	if($ip == "127.0.0.1"){
		$data['address'] = '本地';
	}else{
		$lbs = json_decode(file_get_contents(config('lbs.uri').'?ip='.$ip."&key=".config('lbs.key')),TRUE);
		if($lbs['status'] == 0){
			$data['adderss'] = $lbs['result']['ad_info']['province'].$lbs['result']['ad_info']['city'];
		}else{
			$data['address'] = '';
		}
	}
	return $data;
}

// 获取服务器的负载状态
function get_used_status(){
	$fp = popen('top -b -n 2 | grep -E "^(Cpu|Mem|Tasks)"',"r");//获取某一时刻系统cpu和内存使用情况
	$rs = "";
	while(!feof($fp)){
		$rs .= fread($fp,1024);
	}
	pclose($fp);
	$sys_info = explode("\n",$rs);

	$tast_info = explode(",",$sys_info[3]);//进程 数组
	$cpu_info = explode(",",$sys_info[4]);  //CPU占有量  数组
	$mem_info = explode(",",$sys_info[5]); //内存占有量 数组

	//正在运行的进程数
	$tast_running = trim(trim($tast_info[1],'running')); 
	//CPU占有量
	$cpu_usage = trim(trim($cpu_info[0],'Cpu(s): '),'%us');  //百分比

	//内存占有量
	$mem_total = trim(trim($mem_info[0],'Mem: '),'k total');  
	$mem_used = trim($mem_info[1],'k used');
	$mem_usage = round(100*intval($mem_used)/intval($mem_total),2);  //百分比

	/*硬盘使用率 begin*/
	$fp = popen('df -lh | grep -E "^(/)"',"r");
	$rs = fread($fp,1024);
	pclose($fp);
	$rs = preg_replace("/\s{2,}/",' ',$rs);  //把多个空格换成 “_”
	$hd = explode(" ",$rs);
	$hd_avail = trim($hd[3],'G'); //磁盘可用空间大小 单位G
	$hd_usage = trim($hd[4],'%'); //挂载点 百分比
	//print_r($hd);
	/*硬盘使用率 end*/  

	//检测时间
	$fp = popen("date +\"%Y-%m-%d %H:%M\"","r");
	$rs = fread($fp,1024);
	pclose($fp);
	$detection_time = trim($rs);
	$data = [
		'process'	=>	$tast_running,
		'memory'	=>	$mem_usage,
		'cpu'		=>	$cpu_usage
	];
}

function trimarray($array = array()){
	foreach ($array as $key => $value) {
		$rray[$key] = trim($value);
	}
	return $array;
}

function output($data){
	$result = [
		'status'	=>	200,
		'data'		=>	$data,
	];
	echo json_encode($result);
	exit;
}