<?php
namespace app\admin\controller;
use think\Controller;


/**
* 公共处理类
*/
class Common extends Controller{
	public $admin_ip;       // 用户的真实ip
	public $admin;
	public $db;
	function __construct() {
		parent::__construct();
		$this->db = db();
		$this->admin_ip = getip();
		$this->admin = session('admin', '', 'admin');
		if(strtolower(request()->controller()) != 'login' && !$this->admin){
			if(!($this->admin_ip && $_SERVER['HTTP_USER_AGENT'])){
				// 根据用户IP和用户浏览器类型来判断用户是否是正常浏览
				session(null, 'admin');
				header("Location:".url('login/index'));
			}
		}elseif(strtolower(request()->controller()) == 'login' && $this->admin){
			header("Location:".url('index/index'));
		}
	}

    // alert弹窗
    public function alert($title = '', $content = '',$url = NULL) {
    	$this->assign('title', $title);
		$this->assign('content', $content);
		$this->assign('url', $url);
		return $this->fetch('Public:alert');
    }

    /**
     * 服务器资源监控
     * lan_outtraffic		内网出带宽	Mbps
	 * lan_intraffic		内网入带宽	Mbps
	 * lan_outpkg			内网出包量	个/秒
	 * lan_inpkg			内网入包量	个/秒
	 * wan_outtraffic		外网出带宽	Mbps
	 * wan_intraffic		外网入带宽	Mbps
	 * acc_outtraffic		外网出流量	MB
	 * wan_outpkg			外网出包量	个/秒
	 * wan_inpkg			外网入包量	个/秒
 	 *
	 * cpu_usage			CPU使用率	%
	 * cpu_loadavg			CPU平均负载，分析/proc/loadavg中的数据，取1分钟内cpu平均负载最大值得到 (windows机器没有此指标)
	 * mem_used				内存使用量	MByte
	 * mem_usage			内存利用率	%
	 * disk_read_traffic	磁盘读流量	KB/s
	 * disk_write_traffic	磁盘写流量	KB/s
	 * disk_io_await		磁盘IO等待	ms
     */
    public function monitor($metricNames = array()){
    	if(!$metricNames){
    		return ;
    	}
    	$secretId  = config('monitor.secretId');
		$secretKey = config('monitor.secretKey');
		$params = [
			"Action"             => "GetMonitorData",
			// "Nonce"              => rand(100000,999999),
			"Region"             => "sh",
			"SecretId"           => $secretId,
			// "Timestamp"          => time(),
			'namespace'          =>	'qce/cvm',
			// "metricName"         =>	'cpu_usage',
			"dimensions.0.name"  =>	'unInstanceId',
			"dimensions.0.value" =>	'ins-eukbmvkz',
    	];
    	$data = [];
    	foreach ($metricNames as $metricName){
    		unset($params['Signature']);
			$params['metricName'] = $metricName;
			$params['Nonce']      = rand(1000000, 9999999);
			$params['Timestamp']  =	time();
    		ksort($params);
			$signStr = urlencode(base64_encode(hash_hmac('sha1', "GETmonitor.api.qcloud.com/v2/index.php?".urldecode(http_build_query($params)), $secretKey, true)));

			$params = array_merge($params,['Signature' => $signStr]);
			$ch = curl_init("https://monitor.api.qcloud.com/v2/index.php?".urldecode(http_build_query($params)));
			$opt = array(
				CURLOPT_HEADER         => 0,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_SSL_VERIFYHOST => 2,
		        CURLOPT_SSL_VERIFYPEER => FALSE,
		        );
			curl_setopt_array($ch, $opt);
			$data[$metricName] = json_decode(curl_exec($ch), true);

    	}
		curl_close($ch);
		return $data;
    }
}
