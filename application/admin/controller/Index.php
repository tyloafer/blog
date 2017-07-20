<?php
namespace app\admin\controller;

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

    public function index2(){
    	echo 333333;
    }

    public function test(){
        echo time()."<br />";
        echo date('Y-m-d H:i:s',time());
    }

}