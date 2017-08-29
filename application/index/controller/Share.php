<?php
namespace app\index\controller;
use think\Controller;
use app\admin\model\Cate;
/**
* 作品
*/
class Share extends Common {

	function index($cate_id = 0){
		if($cate_id != 0){
			$cateModel = new Cate();
			$cateArray = $this->db->name('cate')->field('cate_id, cate_name, parent_id')->where(['target' => 'issue'])->order('cate_sort')->select();
			$cateData  = $cateModel->get_cate_tree($cate_id, $cateArray);
			$cateIds   = implode(',', array_merge(array_column($cateData, 'cate_id'),array($cate_id)));
			$shares     = $this->db->name('share')->where(['is_del' => 0, 'cate_id' => ['in', $cateIds]])->order(' add_time desc')->paginate(9,false,['query' => request()->param()]);
		}else{
			$shares = $this->db->name('share')->field('id, title, img, link, pass')->where(['is_del' => 0])->order('add_time desc')->paginate(9,false,['query' => request()->param()]);
		}
		
		$this->assign('shares',$shares);
		return $this->fetch();
	}
	// 分享详情
	function detail($id = 0){
		if(!$id){
			header('Location:'.url('share/index'));
		}
		$share = $this->db->name('share')->where(['id' => $id])->find();
		if(!$share){
			header('Location:'.url('share/index'));
		}
		$showImg = explode(';', $share['showImg']);
		$imgUrl = config('imgUrl');
		$share['showImg'] = [];
		foreach ($showImg as $value) {
			$share['showImg'][] = $imgUrl.$value;
		}
		$this->assign('share', $share);
		// 获取最新分享
        $recent_shares = $this->db->name('share')->field('id, title, img, link, pass')->where(['is_del' => 0])->order('add_time desc')->limit(4)->select();
        $this->assign('recent_shares', $recent_shares);
		return $this->fetch();
	}
}