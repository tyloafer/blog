<?php
namespace app\admin\controller;
use qcloudcos\Cosapi;

class Index extends Common {
	function __construct() {
		parent::__construct();
	}
    public function index() {
    	// dump(get_defined_constants());
        $metricName = ['cpu_usage','cpu_usage','cpu_loadavg','mem_used','mem_usage','disk_read_traffic','disk_write_traffic','disk_io_await'];
        // $monitor = $this->monitor($metricName);
        // $this->assign('monitor', $monitor);
    	$this->assign('admin',$this->admin);
    	return $this->fetch('index');
    }
    public function issue($title = '', $cate = 0){
        if($cate){
            
        }
    }

}