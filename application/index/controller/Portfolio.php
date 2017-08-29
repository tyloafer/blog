<?php
namespace app\index\controller;
use think\Controller;

/**
* 作品
*/
class Portfolio extends Common {

	function index(){
		$works = $this->db->name('works')->field('id, title, img, link')->where(['is_del' => 0])->order('is_top desc, sort asc, add_time desc')->paginate(9,false,['query' => request()->param()]);
		$this->assign('works',$works);
		return $this->fetch();
	}

	function detail($id = 0){
		if(!$id){
			header('Location:'.url('portfolio/index'));
		}
		$works = $this->db->name('works')->where(['id' => $id])->find();
		if(!$works){
			header('Location:'.url('portfolio/index'));
		}
		$showImg = explode(';', $works['showImg']);
		$imgUrl = config('imgUrl');
		$works['showImg'] = [];
		foreach ($showImg as $value) {
			$works['showImg'][] = $imgUrl.$value;
		}
		$this->assign('works', $works);
		// 获取最新作品
        $detail_works = $this->db->name('works')->field('id, title, img, link')->where(['is_del' => 0])->order('add_time desc')->limit(4)->select();
        $this->assign('detail_works', $detail_works);
		return $this->fetch();
	}
}