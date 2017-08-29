<?php

namespace app\index\controller;
use think\Controller;

/**
* SS帐号
*/
class Shadowsock extends Common {

	function index(){
		$ssacounts = $this->db->name('ssacount')->select();
		// 由于图片只有12张，所以对每个ss帐号所对应的图片进行对12取余，保证都有背景图片
		foreach ($ssacounts as $key => $ssacount) {
			// 获取分类及class
			if(!isset($cates[$ssacount['region_class']])) {
				$cates[$ssacount['region_class']] = $ssacount['region'];
			}
			$ssacounts[$key]['img_id'] = ($ssacount['id'] - 1) % 12 + 1;
		}
		$this->assign('ssacounts', $ssacounts);
		$this->assign('cates', $cates);
		return $this->fetch();
	}

	function get() {
        $content = file_get_contents('http://ss.ishadowx.com/');
        $pattern = '/.*?/';
        $pattern = '/<div\s+id="portfolio">.*?<div\s+class="categories">\s+<ul class="cat">\s+<li>\s+<ol class="type">(.*?)<\/ol>.*?<div class="row">\s+<div class="portfolio-items">(.*?)<\/div>\s+<\/div>\s+<div class="row text-center center">/si';
        preg_match_all($pattern, $content, $matches);
        // $match[1][0]为分类，构建分类数组
        $cates = $matches[1][0];
        $ssacount = $matches[2][0];
        $cates = array_filter(trimarray(explode("\r\n", $cates)));
        $pattern = '/data-filter=[\'"][\.]*(.*?)[\'"].*?>(.*?)</';
        foreach ($cates as $value) {
            preg_match_all($pattern, $value, $cate_match);
            $data['cate'][$cate_match[1][0]] = $cate_match[2][0];
        }
        // 正则获取所有的ss帐号
        $pattern = '/<div class="col-sm-6 col-md-4 col-lg-4\s+([a-zA-Z0-9]*?)">.*?<h4>IP Address:<span.*?>(.*?)<\/span>.*?<h4>Port：([\d]*)<\/h4>\s+<h4>Password:<span.*?>(.*?)<\/span>.*?<h4>Method:(.*?)<\/h4>.*?<img\s+src="(.*?)"/si';
        preg_match_all($pattern, $ssacount, $matches);
        for($i = 0; isset($matches[0][$i]); $i++) {
            $data['ssacount'][] = [
                'ip'           =>  $matches[2][$i],
                'port'         =>  $matches[3][$i],
                'password'     =>  $matches[4][$i],
                'method'       =>  $matches[5][$i],
                'qrcode'       =>  $matches[6][$i],
                'region'       =>  $data['cate'][$matches[1][$i]],
                'region_class' =>  $matches[1][$i],
            ];
        }
        $this->db->query('truncate table '.config('database.prefix').'ssacount');
        $this->db->name('ssacount')->insertAll($data['ssacount']);
    }
}