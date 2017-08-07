<?php
namespace app\admin\controller;
use qcloudcos\Cosapi;

class Index extends Common {
	function __construct() {
		parent::__construct();
	}
    public function index() {
        set_time_limit(180);
        $metricName = ['cpu_usage','cpu_usage','cpu_loadavg','mem_used','mem_usage','disk_read_traffic','disk_write_traffic','disk_io_await'];
        $monitor = $this->monitor($metricName);
        // 构建时间数组
        $now_time = time();
        $time_array = [];
        for($time = strtotime(date("Y-m-d", $now_time)); $time < $now_time; $time = $time+300){
            $time_array[] = date("m-d H:i", $time);
        }

        $this->assign('time_array', '"'.implode('","', $time_array).'"');
        // 服务器数据字符串
        foreach ($monitor as $key => $value) {
            $monitor[$key]['data'] = '"'.implode('","', $value['dataPoints']).'"';
        }
        $this->assign('monitor', $monitor);
    	$this->assign('admin',$this->admin);
    	return $this->fetch('index');
    }
    public function issue($title = '', $cate = 0){
        if($cate){
            
        }
    }

}